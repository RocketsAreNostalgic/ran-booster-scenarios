"""Connected proof availability never weakens generic candidate qualification."""

import hashlib
import importlib.util
import json
from pathlib import Path
import sys
import tempfile
import unittest
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / 'scripts'))
SPEC = importlib.util.spec_from_file_location('connected_proof', ROOT / 'scripts/candidate-proof.py')
proof = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(proof)


class ConnectedProofTest(unittest.TestCase):
    def setUp(self):
        temporary = tempfile.TemporaryDirectory()
        self.addCleanup(temporary.cleanup)
        self.site = Path(temporary.name) / 'wordpress'
        self.artifacts = Path(temporary.name) / 'pair'
        self.artifacts.mkdir()
        names = ('ran-booster-candidate-test.zip', 'ran-booster-scenarios-candidate-test.zip')
        for name in names:
            (self.artifacts / name).write_bytes(b'inert orchestration test input')
        self.record = {
            'publication': 'unreleased-acceptance-only', 'fixture_commit': 'a' * 40,
            'core': {'tested_commit': 'b' * 40, 'run_id': 12, 'run_attempt': 1, 'artifact_id': 34},
            'artifacts': {name: hashlib.sha256((self.artifacts / name).read_bytes()).hexdigest() for name in names},
            'proofs': {'browser_walkthrough': 'pending'},
        }
        self.record_path = self.artifacts / 'candidate-pairing-record.json'
        self.record_path.write_text(json.dumps(self.record))
        self.calls = []

    def exercise(self, feature, fail_generic=False):
        def command(site, *args):
            self.assertEqual(site, self.site)
            self.calls.append(args)
            if args == ('option', 'get', 'siteurl'):
                return 'http://localhost'
            if args[:2] == ('config', 'get'):
                return {'WP_ENVIRONMENT_TYPE': 'local', 'RAN_BOOSTER_SELF_UPDATE_MODE': 'disabled',
                        'RAN_BOOSTER_SCENARIOS_DISPOSABLE': 'true'}[args[2]]
            if args == ('core', 'version'):
                return '7.0'
            if 'eval' in args:
                return feature
            if fail_generic and 'eval-file' in args and args[-1].endswith('/tests/installed-proof.php'):
                raise RuntimeError('Generic pairing/request proof failed')
            return ''
        with patch.object(proof, 'wp', side_effect=command), patch.object(proof.builder, 'verify_installed') as readback, patch.object(proof.subprocess, 'check_output', return_value='8.5.11'):
            proof.prove(self.site, self.artifacts)
        self.assertEqual(readback.call_count, 2)
        return json.loads(self.record_path.read_text())

    def test_available_connected_proof_requires_both_request_contexts(self):
        record = self.exercise('available')
        calls = [args for args in self.calls if args[-1].endswith('/tests/connected-installed-proof.php')]
        self.assertEqual(len(calls), 2)
        self.assertTrue(any(any('DOING_CRON' in value for value in args) for args in calls))
        self.assertEqual(record['proofs']['connected_fixture_boundary'], 'passed')
        self.assertEqual(record['proofs']['browser_walkthrough'], 'pending')

    def test_unavailable_connected_apis_preserve_generic_proofs_and_record_na(self):
        record = self.exercise('unavailable')
        self.assertFalse(any(args[-1].endswith('/tests/connected-installed-proof.php') for args in self.calls))
        self.assertEqual(record['proofs']['installed_file_readback'], 'passed')
        self.assertEqual(record['proofs']['cron_context_synthetic_boundary'], 'passed')
        self.assertTrue(record['proofs']['connected_fixture_boundary'].startswith('not-applicable:'))
        self.assertEqual(record['proofs']['browser_walkthrough'], 'pending')

    def test_ambiguous_feature_output_never_records_a_pass(self):
        with self.assertRaises(ValueError):
            self.exercise('ambiguous')
        self.assertEqual(json.loads(self.record_path.read_text()), self.record)

    def test_generic_failure_remains_fatal_before_feature_detection(self):
        with self.assertRaisesRegex(RuntimeError, 'Generic pairing'):
            self.exercise('unavailable', fail_generic=True)
        self.assertFalse(any('eval' in args for args in self.calls))
        self.assertEqual(json.loads(self.record_path.read_text()), self.record)


if __name__ == '__main__':
    unittest.main()
