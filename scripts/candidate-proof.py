#!/usr/bin/env python3
"""Install and read back a fixed acceptance pair on a new disposable WP site."""

import argparse
import importlib.util
import json
from pathlib import Path
import subprocess

ROOT = Path(__file__).resolve().parent.parent
SPEC = importlib.util.spec_from_file_location('candidate_builder', ROOT / 'scripts/build-candidate.py')
builder = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(builder)


def wp(site, *arguments):
    return subprocess.check_output(['wp', '--path=' + str(site), *arguments], text=True).strip()


def prove(site, artifacts):
    record_path = artifacts / 'candidate-pairing-record.json'
    record = builder.strict_json(record_path.read_bytes())
    if (record.get('publication') != 'unreleased-acceptance-only'
            or wp(site, 'option', 'get', 'siteurl') != 'http://localhost'
            or wp(site, 'config', 'get', 'WP_ENVIRONMENT_TYPE') != 'local'
            or wp(site, 'config', 'get', 'RAN_BOOSTER_SELF_UPDATE_MODE') != 'disabled'
            or wp(site, 'config', 'get', 'RAN_BOOSTER_SCENARIOS_DISPOSABLE') not in ('1', 'true')
            or wp(site, 'plugin', 'list', '--status=active', '--field=name')):
        raise ValueError('Proof requires a new opted-in local site with no active plugins and Core self-updates disabled')
    core_names = [name for name in record['artifacts'] if name.startswith('ran-booster-candidate-')]
    fixture_names = [name for name in record['artifacts'] if name.startswith('ran-booster-scenarios-candidate-')]
    if len(core_names) != 1 or len(fixture_names) != 1:
        raise ValueError('Pair record must identify exactly two plugin ZIPs')
    for name, digest in record['artifacts'].items():
        if Path(name).name != name or builder.sha256((artifacts / name).read_bytes()) != digest:
            raise ValueError('Candidate ZIP identity differs from pair record')
    plugin_root = site / 'wp-content/plugins'
    if any((plugin_root / slug).exists() for slug in ('ran-booster', 'ran-booster-scenarios')):
        raise ValueError('Proof refuses an existing candidate installation')
    for name in (core_names[0], fixture_names[0]):
        print(wp(site, 'plugin', 'install', str((artifacts / name).resolve())))
    builder.verify_installed(artifacts / 'candidate-pairing.json', plugin_root / 'ran-booster', plugin_root / 'ran-booster-scenarios')
    print(wp(site, 'plugin', 'activate', 'ran-booster', 'ran-booster-scenarios'))
    proof = str(ROOT / 'tests/installed-proof.php')
    print(wp(site, 'eval-file', proof))
    # A separate WordPress bootstrap under the cron context uses the same fixed
    # pairing and synthetic request checks. Actual browser journeys remain pending.
    print(wp(site, '--exec=define("DOING_CRON", true);', 'eval-file', proof))
    builder.verify_installed(artifacts / 'candidate-pairing.json', plugin_root / 'ran-booster', plugin_root / 'ran-booster-scenarios')
    record['environment'] = {'wordpress': wp(site, 'core', 'version'),
                             'php': subprocess.check_output(['php', '-r', 'echo PHP_VERSION;'], text=True)}
    if not record['environment']['php'].startswith('8.5.'):
        raise ValueError('Candidate installed qualification requires PHP 8.5')
    record['proofs'].update({'installed_php_8_5': 'passed', 'installed_file_readback': 'passed',
                             'wp_cli_synthetic_boundary': 'passed', 'cron_context_synthetic_boundary': 'passed',
                             'browser_walkthrough': 'pending'})
    record_path.write_text(json.dumps(record, indent=2) + '\n')
    handover = [
        '# Unreleased candidate pairing', '',
        f'Scenarios commit: `{record["fixture_commit"]}`',
        f'Core commit: `{record["core"]["tested_commit"]}`',
        f'Core Quality: run `{record["core"]["run_id"]}`, attempt `{record["core"]["run_attempt"]}`; artifact `{record["core"]["artifact_id"]}`.',
        '', 'Install this Core ZIP: `' + core_names[0] + '`.',
        'Install this Scenarios ZIP: `' + fixture_names[0] + '`.',
        'Verify both ZIP SHA-256 values in candidate-pairing-record.json, then run the documented installed-file verifier.',
        'Use a disposable local/development PHP 8.5 site with Core self-updates disabled.',
        'Open Tools → Booster scenarios and verify the fixed expected/detected identities.',
        'Inspect the agreed catalog scenarios; record outcomes and screenshots against this pair.',
        '', 'Automated proof results and versions:', '```json', json.dumps({k: record[k] for k in ('environment', 'proofs')}, indent=2), '```',
        '', 'Browser walkthrough remains pending. This is acceptance evidence for this pair only; no publication, live-provider, security or concurrency certification.',
    ]
    (artifacts / 'HANDOVER.md').write_text('\n'.join(handover) + '\n')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--wordpress', type=Path, required=True)
    parser.add_argument('--artifacts', type=Path, required=True)
    args = parser.parse_args()
    prove(args.wordpress.resolve(), args.artifacts.resolve())
