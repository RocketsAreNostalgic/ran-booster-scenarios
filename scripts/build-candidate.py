#!/usr/bin/env python3
"""Build an acceptance-only fixed pair, or verify both installed file inventories."""

import argparse
import importlib.util
import json
from pathlib import Path
import re
import shutil
import subprocess
import tempfile

from core_candidate import API_FIELDS, CORE, WORKFLOW, archive_files, sha256, strict_json

ROOT = Path(__file__).resolve().parent.parent
SPEC = importlib.util.spec_from_file_location('release_builder', ROOT / 'scripts/build-release.py')
release_builder = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(release_builder)
SLUG = release_builder.SLUG
COMPATIBILITY_FIELDS = {'schema_version', 'fixture_version', 'core_version', 'core_commit',
                        'core_zip_sha256', 'provider_api', 'addon_api', 'admin_interaction_api'}


def exact_source(root, commit):
    if not re.fullmatch(r'[a-f0-9]{40}', commit):
        raise ValueError('Exact Scenarios source commit required')
    actual = subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=root, text=True).strip()
    if actual != commit:
        raise ValueError('Scenarios checkout differs from selected commit')
    subprocess.run(['git', 'diff', '--exit-code', commit, '--'], cwd=root, check=True, capture_output=True)
    # Generated archives are allowed; other untracked packaged inputs would falsify source identity.
    tracked = set(subprocess.check_output(['git', 'ls-files'], cwd=root, text=True).splitlines())
    generated = {'archives/scenario-plugin.zip', 'archives/scenario-theme.zip'} | {
        'archives/' + name for name in release_builder.BLUEPRINT_FILES
    }
    for path in release_builder.package_files(root):
        relative = path.relative_to(root).as_posix()
        if relative not in tracked and relative not in generated:
            raise ValueError('Untracked packaged file differs from selected Scenarios source')
    if (root / 'candidate-pairing.json').exists():
        raise ValueError('Candidate metadata must be generated only in build output')


def inventory(files):
    return {name: sha256(content) for name, content in sorted(files.items())}


def validate_evidence(evidence, core_files, core_data):
    if set(evidence) != {'core_version', 'core_commit', 'core_zip_sha256', 'provider_api', 'addon_api', 'admin_interaction_api', 'core', 'core_files'}:
        raise ValueError('Malformed acquired Core evidence')
    provenance = evidence['core']
    if (set(provenance) != {'repository', 'source_commit', 'tested_commit', 'source_kind', 'version', 'run_id', 'run_attempt',
                           'workflow_path', 'artifact_id', 'artifact_name', 'artifact_sha256', 'zip_sha256'}
            or provenance['repository'] != CORE or provenance['workflow_path'] != WORKFLOW
            or provenance['source_kind'] != 'source-head'
            or provenance['source_commit'] != evidence['core_commit'] or provenance['tested_commit'] != evidence['core_commit']
            or provenance['version'] != evidence['core_version'] or provenance['zip_sha256'] != evidence['core_zip_sha256']
            or not re.fullmatch(r'[a-f0-9]{40}', evidence['core_commit'])
            or not re.fullmatch(r'[a-f0-9]{64}', provenance['artifact_sha256'])
            or any(type(provenance[key]) is not int or provenance[key] < 1 for key in ('run_id', 'run_attempt', 'artifact_id'))
            or provenance['artifact_name'] != f'ran-booster-runtime-{provenance["run_id"]}-{provenance["run_attempt"]}'):
        raise ValueError('Acquired Core provenance is malformed or inconsistent')
    if inventory(core_files) != evidence['core_files'] or sha256(core_data) != evidence['core_zip_sha256']:
        raise ValueError('Acquired Core ZIP changed before pairing')
    marker = strict_json(core_files['ran-booster-release.json'])
    if marker != {'schema': 'ran-booster-core-release', 'schema_version': 1, 'commit': evidence['core_commit'], 'version': evidence['core_version']}:
        raise ValueError('Acquired Core marker changed before pairing')
    header = core_files['ran-booster.php'].decode()
    for field, constant in API_FIELDS.items():
        values = re.findall(r"define\(\s*'" + constant + r"'\s*,\s*(\d+)\s*\)", header)
        if type(evidence[field]) is not int or len(values) != 1 or int(values[0]) != evidence[field]:
            raise ValueError('Acquired Core API evidence changed before pairing')


def build_once(root, destination, evidence, core_data, commit):
    contract = strict_json((root / 'compatibility.json').read_bytes())
    if set(contract) != COMPATIBILITY_FIELDS:
        raise ValueError('Source compatibility must remain the released-mode contract')
    for field in ('provider_api', 'addon_api', 'admin_interaction_api'):
        if type(evidence[field]) is not int or evidence[field] != contract[field]:
            raise ValueError('Core candidate APIs differ from required Scenarios APIs')
    for field in ('core_version', 'core_commit', 'core_zip_sha256'):
        contract[field] = evidence[field]
    contract['pairing_mode'] = 'candidate'
    contract['candidate_metadata'] = 'candidate-pairing.json'
    version = (root / 'version.txt').read_text().strip()
    if not re.fullmatch(r'\d+\.\d+\.\d+(?:-[A-Za-z0-9.-]+)?', version) or contract['fixture_version'] != version:
        raise ValueError('Fixture version differs from Release Please version')
    if not re.search(r'^\s*\* Version:\s*' + re.escape(version) + r'\s*$', (root / f'{SLUG}.php').read_text(), re.M):
        raise ValueError('Scenarios plugin header version differs')
    if not re.search(r'^Stable tag:\s*' + re.escape(version) + r'\s*$', (root / 'readme.txt').read_text(), re.M):
        raise ValueError('Scenarios packaged readme version differs')
    for name, key in (('.release-please-manifest.json', '.'), ('package.json', 'version')):
        if strict_json((root / name).read_bytes()).get(key) != version:
            raise ValueError('Scenarios version differs in ' + name)
    destination.mkdir(parents=True)
    samples = release_builder.build_samples(root)
    files = {path.relative_to(root).as_posix(): path.read_bytes() for path in release_builder.package_files(root)}
    files['compatibility.json'] = (json.dumps(contract, indent=2) + '\n').encode()
    pairing = {key: value for key, value in contract.items() if key in COMPATIBILITY_FIELDS}
    pairing.update({'schema': 'ran-booster-scenarios-candidate', 'fixture_commit': commit,
                    'core': evidence['core'], 'core_files': evidence['core_files'], 'fixture_files': inventory(files)})
    files['candidate-pairing.json'] = (json.dumps(pairing, indent=2) + '\n').encode()
    fixture_name = f'{SLUG}-candidate-{commit[:12]}-core-{evidence["core_commit"][:12]}.zip'
    release_builder.write_zip(destination / fixture_name, {SLUG + '/' + key: value for key, value in files.items()})
    core_name = f'ran-booster-candidate-{evidence["core_commit"][:12]}.zip'
    (destination / core_name).write_bytes(core_data)
    for slug, sample_files in samples.items():
        release_builder.write_zip(destination / f'{slug}-1.0.0.zip', {
            name: data.replace(b'Version: 2.0.0', b'Version: 1.0.0') for name, data in sample_files.items()})
    for path in list(destination.glob('*.zip')):
        path.with_suffix('.zip.sha256').write_text(f'{sha256(path.read_bytes())}  {path.name}\n')
    record = {'schema': 'ran-booster-scenarios-candidate-record', 'schema_version': 1,
              'publication': 'unreleased-acceptance-only', 'fixture_commit': commit, 'core': evidence['core'],
              'artifacts': {path.name: sha256(path.read_bytes()) for path in sorted(destination.glob('*.zip'))},
              'proofs': {'reproducible_pair_build': 'passed', 'installed_php_8_5': 'pending', 'browser_walkthrough': 'pending'}}
    (destination / 'candidate-pairing-record.json').write_text(json.dumps(record, indent=2) + '\n')
    (destination / 'candidate-pairing.json').write_bytes(files['candidate-pairing.json'])
    return destination / fixture_name


def build(root, core_dir, destination, commit):
    release_builder.build_samples(root)
    exact_source(root, commit)
    if core_dir.is_symlink() or destination.is_symlink():
        raise ValueError('Symlink build directory')
    evidence = strict_json((core_dir / 'core-candidate.json').read_bytes())
    core_data = (core_dir / 'core.zip').read_bytes()
    core_files = archive_files(core_data, prefix='ran-booster/')
    validate_evidence(evidence, core_files, core_data)
    if destination.exists():
        raise ValueError('Candidate output must be a new directory')
    with tempfile.TemporaryDirectory() as temporary:
        temporary = Path(temporary)
        first = build_once(root, temporary / 'first', evidence, core_data, commit)
        build_once(root, temporary / 'second', evidence, core_data, commit)
        before = {path.name: path.read_bytes() for path in first.parent.iterdir()}
        after = {path.name: path.read_bytes() for path in (temporary / 'second').iterdir()}
        if before != after:
            raise ValueError('Candidate pairing is not reproducible')
        exact_source(root, commit)
        shutil.copytree(first.parent, destination)
    return destination / first.name


def installed_inventory(root):
    if root.is_symlink() or not root.is_dir():
        raise ValueError('Installed directory missing or symlink')
    result = {}
    size = 0
    for path in root.rglob('*'):
        if path.is_symlink() or (not path.is_file() and not path.is_dir()):
            raise ValueError('Unsafe installed file')
        if path.is_file():
            size += path.stat().st_size
            if len(result) >= 1200 or size > 128 * 1024 * 1024:
                raise ValueError('Installed inventory exceeds bounds')
            result[path.relative_to(root).as_posix()] = sha256(path.read_bytes())
    return result


def verify_installed(pairing_path, core_install, scenarios_install):
    pairing = strict_json(pairing_path.read_bytes())
    if pairing.get('schema') != 'ran-booster-scenarios-candidate' or pairing.get('schema_version') != 1:
        raise ValueError('Malformed candidate pairing metadata')
    if installed_inventory(core_install) != pairing['core_files']:
        raise ValueError('Installed Core bytes differ from fixed pair')
    fixture_files = installed_inventory(scenarios_install)
    metadata_digest = fixture_files.pop('candidate-pairing.json', None)
    if metadata_digest != sha256(pairing_path.read_bytes()) or fixture_files != pairing['fixture_files']:
        raise ValueError('Installed Scenarios bytes or metadata differ from fixed pair')
    print('Installed Core and Scenarios bytes match this exact pairing; publication and provider acceptance are not implied.')


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--core-dir', type=Path)
    parser.add_argument('--scenarios-commit')
    parser.add_argument('--output-dir', type=Path)
    parser.add_argument('--verify-installed', action='store_true')
    parser.add_argument('--pairing', type=Path)
    parser.add_argument('--core-install', type=Path)
    parser.add_argument('--scenarios-install', type=Path)
    args = parser.parse_args()
    if args.verify_installed:
        if not all((args.pairing, args.core_install, args.scenarios_install)):
            parser.error('Installed verification requires --pairing, --core-install and --scenarios-install')
        verify_installed(args.pairing, args.core_install, args.scenarios_install)
    else:
        if not all((args.core_dir, args.scenarios_commit, args.output_dir)):
            parser.error('Build requires --core-dir, --scenarios-commit and --output-dir')
        print(build(ROOT, args.core_dir, args.output_dir, args.scenarios_commit))


if __name__ == '__main__':
    main()
