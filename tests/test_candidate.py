"""Offline negative controls for artifact custody, safe inspection and fixed pairing."""

import copy
from datetime import datetime, timedelta, timezone
import importlib.util
import io
import json
from pathlib import Path
import shutil
import sys
import tempfile
import unittest
from unittest.mock import patch
from types import SimpleNamespace
import base64
import urllib.error
import zipfile

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / 'scripts'))
import core_candidate as candidate

SPEC = importlib.util.spec_from_file_location('candidate_builder', ROOT / 'scripts/build-candidate.py')
builder = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(builder)


def zip_bytes(files):
    output = io.BytesIO()
    with zipfile.ZipFile(output, 'w') as archive:
        for name, content in files.items():
            archive.writestr(name, content)
    return output.getvalue()


class CandidateTest(unittest.TestCase):
    def fixture(self):
        commit, tree = 'a' * 40, 'b' * 40
        version = '1.0.0-beta.36'
        core = {
            'ran-booster/ran-booster-release.json': json.dumps({'schema': 'ran-booster-core-release', 'schema_version': 1, 'version': version, 'commit': commit}),
            'ran-booster/ran-booster.php': "/**\n * Version: 1.0.0-beta.36\n */\ndefine( 'RAN_BOOSTER_PROVIDER_API_VERSION', 15 );\ndefine( 'RAN_BOOSTER_ADDON_API_VERSION', 17 );\ndefine( 'RAN_BOOSTER_ADMIN_INTERACTION_API_VERSION', 3 );\n",
        }
        inner = zip_bytes(core)
        digest = candidate.sha256(inner)
        name = 'ran-booster-' + version + '.zip'
        checksum = f'{digest}  {name}\n'.encode()
        metadata = {'schema': 'ran-booster-ci-runtime', 'schema_version': 3, 'mode': 'built', 'lane': 'full',
                    'quality_commit': commit, 'source_commit': commit, 'source_tree': tree, 'version': version, 'sha256': digest,
                    'run': {'id': 12, 'attempt': 2, 'event': 'pull_request'},
                    'pull_request': {'number': 0, 'base_sha': '', 'head_sha': '', 'tested_commit': commit, 'tested_tree': tree}}
        promotion = {'schema': 'ran-profile-b-promotion', 'schema_version': 1, 'repository': candidate.CORE,
                     'quality_commit': commit, 'source_commit': commit, 'tag': 'v' + version,
                     'assets': [{'name': name, 'sha256': digest}, {'name': name + '.sha256', 'sha256': candidate.sha256(checksum)}]}
        files = {name: inner, name + '.sha256': checksum, 'ran-booster-ci-runtime.json': json.dumps(metadata),
                 'ran-profile-b-promotion.json': json.dumps(promotion)}
        wrapper = zip_bytes(files)
        run = {'id': 12, 'run_attempt': 2, 'repository': {'full_name': candidate.CORE, 'id': candidate.REPOSITORY_ID}, 'head_repository': {'full_name': candidate.CORE, 'id': candidate.REPOSITORY_ID},
               'workflow_id': candidate.WORKFLOW_ID, 'path': candidate.WORKFLOW, 'head_sha': commit, 'event': 'pull_request',
               'status': 'completed', 'conclusion': 'success'}
        jobs = [{'name': name, 'run_id': 12, 'run_attempt': 2, 'status': 'completed', 'conclusion': 'success'} for name in sorted(candidate.FULL_JOBS)]
        jobs.append({'name': 'Release candidate install readback', 'run_id': 12, 'run_attempt': 2, 'status': 'completed', 'conclusion': 'skipped'})
        artifact = {'id': 34, 'name': 'ran-booster-runtime-12-2', 'expired': False, 'digest': 'sha256:' + candidate.sha256(wrapper),
                    'size_in_bytes': len(wrapper), 'workflow_run': {'id': 12, 'head_sha': commit, 'repository_id': candidate.REPOSITORY_ID, 'head_repository_id': candidate.REPOSITORY_ID},
                    'expires_at': (datetime.now(timezone.utc) + timedelta(days=3)).isoformat()}
        return commit, tree, run, jobs, artifact, files, wrapper

    def inspect(self, fixture):
        commit, tree, run, _, artifact, _, wrapper = fixture
        return candidate.inspect_candidate(wrapper, run=run, artifact=artifact, commit=commit, tree=tree, attempt=2)

    def test_qualified_exact_source_artifact_and_inventory(self):
        commit, _, run, jobs, artifact, _, _ = fixture = self.fixture()
        candidate.verify_run(run, jobs, commit=commit, run_id=12, attempt=2)
        candidate.verify_artifact(artifact, artifact_id=34, run_id=12, attempt=2, commit=commit, expected_digest=artifact['digest'])
        evidence, inner = self.inspect(fixture)
        self.assertEqual(evidence['core']['source_kind'], 'source-head')
        self.assertEqual(evidence['core_zip_sha256'], candidate.sha256(inner))
        self.assertEqual(len(evidence['core_files']), 2)

    def test_wrong_repository_workflow_sha_attempt_or_incomplete_jobs_refuse(self):
        commit, _, run, jobs, _, _, _ = self.fixture()
        for key, value in [('repository', {'full_name': 'other/repo'}), ('path', '.github/workflows/other.yml'),
                           ('head_sha', 'c' * 40), ('run_attempt', 1), ('conclusion', 'failure'), ('workflow_id', 1)]:
            with self.subTest(key=key), self.assertRaises(ValueError):
                candidate.verify_run(dict(run, **{key: value}), jobs, commit=commit, run_id=12, attempt=2)
        for changed in [jobs[:-2], jobs + jobs[:1], [dict(job, conclusion='skipped') if job['name'] == 'Quality' else job for job in jobs]]:
            with self.assertRaises(ValueError):
                candidate.verify_run(run, changed, commit=commit, run_id=12, attempt=2)

    def test_wrong_expired_or_replaced_artifact_refuses(self):
        commit, _, _, _, artifact, _, _ = self.fixture()
        for key, value in [('expired', True), ('id', 35), ('name', 'latest'), ('digest', 'sha256:' + '0' * 64),
                           ('workflow_run', {'id': 13, 'head_sha': commit}), ('expires_at', '2000-01-01T00:00:00Z')]:
            with self.subTest(key=key), self.assertRaises(ValueError):
                candidate.verify_artifact(dict(artifact, **{key: value}), artifact_id=34, run_id=12, attempt=2,
                                          commit=commit, expected_digest=artifact['digest'])

    def test_wrapper_corruption_metadata_lies_and_synthetic_merge_refuse(self):
        fixture = self.fixture()
        with self.assertRaises(ValueError):
            self.inspect((*fixture[:-1], fixture[-1] + b'corrupt'))
        for field, value in [('source_commit', 'c' * 40), ('source_tree', 'c' * 40), ('schema_version', 2), ('lane', 'release-candidate')]:
            changed = list(copy.deepcopy(fixture))
            metadata = json.loads(changed[5]['ran-booster-ci-runtime.json'])
            metadata[field] = value
            changed[5]['ran-booster-ci-runtime.json'] = json.dumps(metadata)
            changed[6] = zip_bytes(changed[5])
            changed[4]['digest'] = 'sha256:' + candidate.sha256(changed[6])
            changed[4]['size_in_bytes'] = len(changed[6])
            with self.subTest(field=field), self.assertRaises(ValueError):
                self.inspect(changed)

    def test_unsafe_paths_links_duplicates_and_json_refuse(self):
        for path in ['../escape', '/escape', 'ran-booster/../../escape', 'ran-booster\\escape', 'ran-booster//escape', 'ran-booster/./escape']:
            with self.subTest(path=path), self.assertRaises(ValueError):
                candidate.archive_files(zip_bytes({path: b'bad'}), prefix='ran-booster/')
        output = io.BytesIO()
        with zipfile.ZipFile(output, 'w') as archive:
            info = zipfile.ZipInfo('ran-booster/link')
            info.create_system = 3
            info.external_attr = 0o120777 << 16
            archive.writestr(info, 'outside')
        with self.assertRaises(ValueError):
            candidate.archive_files(output.getvalue(), prefix='ran-booster/')
        with self.assertRaises(ValueError):
            candidate.strict_json('{"a":1,"a":2}')
        with self.assertRaises(ValueError):
            candidate.archive_files(zip_bytes({'ran-booster/file': b'a', 'ran-booster/file/child': b'b'}), prefix='ran-booster/')

    def source_copy(self, target):
        for name in ('version.txt', 'compatibility.json', 'ran-booster-scenarios.php', 'catalog.php', 'README.md', 'CONTRIBUTING.md',
                     'RELEASE.md', 'readme.txt', 'LICENSE', 'LICENSE.starter', 'release-contents.txt', '.release-please-manifest.json', 'package.json'):
            shutil.copy2(ROOT / name, target / name)
        for name in ('samples', 'docs', 'languages'):
            shutil.copytree(ROOT / name, target / name)
        # Additional runtime files added by this feature are release inputs too.
        for line in (ROOT / 'release-contents.txt').read_text().splitlines():
            if line.endswith('.php') and not (target / line).exists():
                shutil.copy2(ROOT / line, target / line)

    def test_reproducible_acceptance_only_build_and_installed_byte_refusal(self):
        evidence, inner = self.inspect(self.fixture())
        with tempfile.TemporaryDirectory() as temporary:
            temporary = Path(temporary).resolve()
            root = temporary / 'source'
            root.mkdir()
            self.source_copy(root)
            core_dir = temporary / 'acquired'
            core_dir.mkdir()
            (core_dir / 'core.zip').write_bytes(inner)
            (core_dir / 'core-candidate.json').write_text(json.dumps(evidence))
            output = temporary / 'pair'
            with patch.object(builder, 'exact_source'):
                fixture_zip = builder.build(root, core_dir, output, 'c' * 40)
            self.assertNotIn('ran-profile-b-promotion.json', {path.name for path in output.iterdir()})
            with zipfile.ZipFile(fixture_zip) as archive:
                archive.extractall(temporary / 'installed')
                generated = json.loads(archive.read('ran-booster-scenarios/compatibility.json'))
                self.assertEqual(generated['pairing_mode'], 'candidate')
                self.assertEqual(generated['candidate_metadata'], 'candidate-pairing.json')
            with zipfile.ZipFile(io.BytesIO(inner)) as archive:
                archive.extractall(temporary / 'installed')
            core = temporary / 'installed/ran-booster'
            scenarios = temporary / 'installed/ran-booster-scenarios'
            pairing = output / 'candidate-pairing.json'
            builder.verify_installed(pairing, core, scenarios)
            (core / 'extra.php').write_text('extra')
            with self.assertRaises(ValueError):
                builder.verify_installed(pairing, core, scenarios)
            (core / 'extra.php').unlink()
            (scenarios / 'catalog.php').write_text('changed')
            with self.assertRaises(ValueError):
                builder.verify_installed(pairing, core, scenarios)
            self.assertEqual(json.loads((root / 'compatibility.json').read_text())['core_version'], '1.0.0-beta.35')

    def test_candidate_release_admission_and_api_mismatch_refuse(self):
        evidence, inner = self.inspect(self.fixture())
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary).resolve() / 'source'
            root.mkdir()
            self.source_copy(root)
            evidence['provider_api'] += 1
            with self.assertRaises(ValueError):
                builder.build_once(root, Path(temporary) / 'bad', evidence, inner, 'c' * 40)
            (root / 'candidate-pairing.json').write_text('{}')
            with self.assertRaises(ValueError):
                builder.release_builder.build(root, Path(temporary) / 'release', 'c' * 40)

    def test_full_acquisition_uses_exact_routes_and_captures_fixed_pair(self):
        commit, tree, run, jobs, artifact, _, wrapper = self.fixture()
        workflow = b'reviewed producer'
        calls = []
        def fake_api(path):
            calls.append(path)
            if path.endswith('/attempts/2'):
                return run
            if '/attempts/2/jobs?' in path:
                return {'total_count': len(jobs), 'jobs': jobs}
            if '/contents/' in path:
                self.assertTrue(path.endswith('?ref=' + commit))
                return {'encoding': 'base64', 'content': base64.b64encode(workflow).decode()}
            if '/git/commits/' in path:
                self.assertTrue(path.endswith(commit))
                return {'tree': {'sha': tree}}
            if path.endswith('/artifacts/34'):
                return artifact
            self.fail('Unexpected API lookup: ' + path)
        with tempfile.TemporaryDirectory() as temporary:
            args = SimpleNamespace(repository=candidate.CORE, commit=commit, run_id=12, run_attempt=2,
                                   artifact_id=34, artifact_digest=artifact['digest'], output_dir=Path(temporary) / 'core')
            with patch.object(candidate, 'api', side_effect=fake_api), patch.object(candidate, 'download_artifact', return_value=wrapper) as download, patch.object(candidate, 'WORKFLOW_SHA256', candidate.sha256(workflow)):
                candidate.acquire(args)
            download.assert_called_once_with(34)
            self.assertEqual(candidate.sha256((args.output_dir / 'core.zip').read_bytes()), json.loads((args.output_dir / 'core-candidate.json').read_text())['core_zip_sha256'])
            self.assertEqual(sum('/artifacts/34' in route for route in calls), 2)
            self.assertFalse(any('latest' in route or '/releases' in route for route in calls))

    def test_api_download_redirect_drops_authorization(self):
        requests = []
        class Body:
            def __enter__(self):
                return self
            def __exit__(self, *args):
                return False
            def read(self, limit):
                return b'wrapper'
        class Opener:
            def __init__(self, redirect):
                self.redirect = redirect
            def open(self, request, timeout):
                requests.append(request)
                if self.redirect:
                    raise urllib.error.HTTPError(request.full_url, 302, 'Found', {'Location': 'https://artifact.example.invalid/fixed'}, None)
                return Body()
        with patch.dict('os.environ', {'GH_TOKEN': 'synthetic-test-token'}), patch.object(candidate.urllib.request, 'build_opener', side_effect=[Opener(True), Opener(False)]):
            self.assertEqual(candidate.download_artifact(34), b'wrapper')
        self.assertEqual(requests[0].get_header('Authorization'), 'Bearer synthetic-test-token')
        self.assertEqual(requests[0].host, 'api.github.com')
        self.assertIsNone(requests[1].get_header('Authorization'))


if __name__ == '__main__':
    unittest.main()
