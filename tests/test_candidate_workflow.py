"""Candidate acceptance must not satisfy source-quality or release admission."""

from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parent.parent


class CandidateWorkflowTest(unittest.TestCase):
    def test_candidate_terminal_context_is_distinct_from_required_quality(self):
        source = (ROOT / '.github/workflows/quality.yml').read_text()
        terminal = source.split('\n  quality:\n', 1)[1]
        self.assertIn("inputs.core_commit != '' && 'candidate pairing' || 'quality'", terminal)
        self.assertIn('test "$PROJECT_RESULT" = success', terminal)
        self.assertIn('test "$CANDIDATE_RESULT" = success', terminal)

    def test_candidate_execution_has_no_acquisition_token_or_promotion_output(self):
        source = (ROOT / '.github/workflows/candidate-pairing.yml').read_text()
        acquisition, execution = source.split('\n  qualify:\n', 1)
        self.assertIn('GH_TOKEN: ${{ github.token }}', acquisition)
        self.assertNotRegex(execution, r'(?m)^\s*GH_TOKEN:')
        self.assertNotIn('secrets:', source)
        self.assertNotRegex(source, r':\s*write\s*(?:\n|$)')
        self.assertIn('persist-credentials: false', execution)
        self.assertNotIn('build/release', execution)
        self.assertIn('PAIR_ARTIFACT_ID:', execution)


if __name__ == '__main__':
    unittest.main()
