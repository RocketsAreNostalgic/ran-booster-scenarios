"""Negative controls for the upstream evidence used by compatibility proposals."""
import base64
import copy
import os
import urllib.error
from unittest.mock import patch
import hashlib
import importlib.util
import io
import json
from pathlib import Path
import sys
import tempfile
import unittest
import zipfile

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'scripts'))
from core_release import NoApiRedirect, inspect_archive, version_key
spec = importlib.util.spec_from_file_location('proposal', Path(__file__).resolve().parents[1] / 'scripts/propose-core-update.py')
proposal = importlib.util.module_from_spec(spec)
spec.loader.exec_module(proposal)


class CoreReleaseTest(unittest.TestCase):
    def setUp(self):
        temporary = tempfile.TemporaryDirectory()
        self.addCleanup(temporary.cleanup)
        self.proposal_root = Path(temporary.name)
        # Proposal tests own their starting release; a real compatibility bump
        # must not turn their mocked newer release into an equal-version no-op.
        current = {
            'schema_version': 1, 'fixture_version': '9.8.7',
            'core_version': '1.0.0-beta.35', 'core_commit': 'b' * 40,
            'core_zip_sha256': 'c' * 64, 'provider_api': 15,
            'addon_api': 17, 'admin_interaction_api': 3,
        }
        (self.proposal_root / 'compatibility.json').write_text(json.dumps(current) + '\n')
        location = patch.object(proposal, '__file__', str(self.proposal_root / 'scripts/propose-core-update.py'))
        location.start()
        self.addCleanup(location.stop)

    def fixture(self, extra=None):
        commit = 'a' * 40
        marker = {'schema': 'ran-booster-core-release', 'schema_version': 1, 'version': '1.0.0-beta.36', 'commit': commit}
        out = io.BytesIO()
        with zipfile.ZipFile(out, 'w') as archive:
            archive.writestr('ran-booster/ran-booster-release.json', json.dumps(marker))
            archive.writestr('ran-booster/ran-booster.php', "/**\n * Version: 1.0.0-beta.36\n */\ndefine( 'RAN_BOOSTER_PROVIDER_API_VERSION', 15 );\ndefine( 'RAN_BOOSTER_ADDON_API_VERSION', 17 );\ndefine( 'RAN_BOOSTER_ADMIN_INTERACTION_API_VERSION', 3 );\n")
            if extra:
                archive.writestr(extra, 'bad')
        data = out.getvalue()
        release = {'id': 12, 'draft': False, 'immutable': True, 'tag_name': 'v1.0.0-beta.36', 'assets': [{
            'name': 'ran-booster-1.0.0-beta.36.zip', 'size': len(data),
            'digest': 'sha256:' + hashlib.sha256(data).hexdigest(),
            'browser_download_url': 'https://github.com/RocketsAreNostalgic/ran-booster/releases/download/v1.0.0-beta.36/ran-booster-1.0.0-beta.36.zip',
        }]}
        return data, release, commit

    def test_api_redirects_never_forward_authorization(self):
        request = __import__('urllib.request', fromlist=['Request']).Request('https://api.github.com/repos/test/test', headers={'Authorization': 'Bearer synthetic-test-value'})
        for destination in ['https://example.invalid/steal', 'https://api.github.com/redirect']:
            with self.subTest(destination=destination), self.assertRaises(ValueError):
                NoApiRedirect().redirect_request(request, None, 302, 'Found', {}, destination)

    def test_exact_release_evidence(self):
        result = inspect_archive(*self.fixture())
        self.assertEqual(result['core_version'], '1.0.0-beta.36')
        self.assertEqual(result['provider_api'], 15)
        self.assertEqual(result['addon_api'], 17)
        self.assertEqual(result['admin_interaction_api'], 3)

    def test_mutable_draft_and_wrong_digest_rejected(self):
        data, release, commit = self.fixture()
        for key, value in [('immutable', False), ('draft', True)]:
            changed = dict(release, **{key: value})
            with self.assertRaises(ValueError):
                inspect_archive(data, changed, commit)
        with self.assertRaises(ValueError):
            inspect_archive(data + b'bad', release, commit)
        with self.assertRaises(ValueError):
            inspect_archive(data, release, 'b' * 40)

    def test_asset_url_and_duplicate_asset_rejected(self):
        data, release, commit = self.fixture()
        changed = copy.deepcopy(release)
        changed['assets'][0]['browser_download_url'] = 'https://example.com/core.zip'
        with self.assertRaises(ValueError):
            inspect_archive(data, changed, commit)
        release['assets'] *= 2
        with self.assertRaises(ValueError):
            inspect_archive(data, release, commit)

    def test_archive_path_escape_rejected(self):
        for path in ['ran-booster/../../outside.php', '/outside.php', 'other/plugin.php', 'ran-booster\\bad.php']:
            with self.subTest(path=path), self.assertRaises(ValueError):
                inspect_archive(*self.fixture(path))

    def test_monotonic_candidate_selection_including_prereleases(self):
        _, release, _ = self.fixture()
        self.assertIs(proposal.candidate([release], '1.0.0-beta.35'), release)
        self.assertIsNone(proposal.candidate([release], '1.0.0-beta.36'))
        self.assertIsNone(proposal.candidate([release], '1.0.0'))
        self.assertIsNone(proposal.candidate([dict(release, immutable=False)], '1.0.0-beta.35'))
        self.assertGreater(version_key('1.0.0'), version_key('1.0.0-rc.99'))

    def test_no_automatic_merge_or_fixture_version_mutation(self):
        source = (Path(__file__).resolve().parents[1] / 'scripts/propose-core-update.py').read_text()
        self.assertNotIn('/merge', source)
        self.assertNotIn('release-please-manifest.json', source)
        self.assertNotIn("'fixture_version':", source)
        self.assertIn('Proposal branch already exists', source)
        self.assertIn('/actions/workflows/quality.yml/dispatches', source)

    def test_proposal_writes_only_contract_then_dispatches(self):
        current_text = (self.proposal_root / 'compatibility.json').read_text()
        current = json.loads(current_text)
        _, release, _ = self.fixture()
        release['html_url'] = 'https://github.com/RocketsAreNostalgic/ran-booster/releases/tag/v1.0.0-beta.36'
        evidence = dict(current, core_version='1.0.0-beta.36', core_commit='a' * 40)
        evidence.pop('fixture_version')
        evidence.pop('schema_version')
        calls = []
        branch_created = False

        def fake_api(path, method='GET', payload=None):
            nonlocal branch_created
            calls.append((path, method, payload))
            if '/releases?' in path:
                return [release]
            if path.endswith('/git/ref/heads/main'):
                return {'object': {'sha': 'b' * 40}}
            if '/contents/compatibility.json?' in path:
                return {'content': base64.b64encode(current_text.encode()).decode()}
            if '/git/ref/heads/automation/' in path:
                if branch_created:
                    return {'object': {'sha': 'c' * 40}}
                raise urllib.error.HTTPError(path, 404, 'Not Found', {}, None)
            if '/git/commits/' in path:
                return {'tree': {'sha': 'd' * 40}}
            if path.endswith('/git/trees'):
                self.assertEqual(payload['tree'][0]['path'], 'compatibility.json')
                self.assertEqual(len(payload['tree']), 1)
                updated = json.loads(payload['tree'][0]['content'])
                self.assertEqual(updated['fixture_version'], current['fixture_version'])
                self.assertEqual(updated, dict(current, **evidence))
                return {'sha': 'e' * 40}
            if path.endswith('/git/commits'):
                return {'sha': 'c' * 40}
            if path.endswith('/git/refs'):
                branch_created = True
            return {}

        env = {'GITHUB_REPOSITORY': proposal.REPOSITORY, 'GITHUB_REF': 'refs/heads/main', 'GITHUB_SHA': 'b' * 40}
        with patch.object(proposal, 'api', side_effect=fake_api), patch.object(proposal, 'verified_release', return_value=(evidence, b'')), patch.dict(os.environ, env), patch.object(sys, 'argv', ['proposal', '--apply']):
            proposal.main()
        writes = [path for path, method, _ in calls if method == 'POST']
        self.assertEqual([path.rsplit('/', 1)[-1] for path in writes], ['trees', 'commits', 'refs', 'pulls', 'dispatches'])

    def test_equal_release_does_not_verify_or_write(self):
        path = self.proposal_root / 'compatibility.json'
        current = json.loads(path.read_text())
        current['core_version'] = '1.0.0-beta.36'
        path.write_text(json.dumps(current) + '\n')
        _, release, _ = self.fixture()
        with patch.object(proposal, 'api', return_value=[release]) as client, patch.object(proposal, 'verified_release') as verify, patch.object(sys, 'argv', ['proposal', '--apply']):
            proposal.main()
        client.assert_called_once_with('repos/' + proposal.CORE + '/releases?per_page=100')
        verify.assert_not_called()

    def test_wrong_repository_cannot_propose(self):
        _, release, _ = self.fixture()
        with patch.object(proposal, 'api', return_value=[release]) as client, patch.object(proposal, 'verified_release', return_value=({'core_version': '1.0.0-beta.36'}, b'')), patch.dict(os.environ, {'GITHUB_REPOSITORY': 'other/repo'}), patch.object(sys, 'argv', ['proposal', '--apply']):
            with self.assertRaises(ValueError):
                proposal.main()
            self.assertEqual(client.call_count, 1)


if __name__ == '__main__':
    unittest.main()
