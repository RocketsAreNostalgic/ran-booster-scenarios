#!/usr/bin/env python3
"""Acquire a fixed, qualified Core CI artifact without executing its contents."""

import argparse
import base64
from datetime import datetime, timezone
import hashlib
import io
import json
from pathlib import Path, PurePosixPath
import re
import stat
import urllib.error
import urllib.parse
import urllib.request
import zipfile

from core_release import API_FIELDS, CORE, MAX_ZIP, NoApiRedirect, api

WORKFLOW = '.github/workflows/quality.yml'
WORKFLOW_ID = 325317152
REPOSITORY_ID = 1319710173
# Reviewed producer contract. A changed workflow needs a reviewed consumer update.
WORKFLOW_SHA256 = 'fd12347e1987b96a349cc1252fc1bc3476a6ba1bea9990baeee42021c41b995f'
MAX_WRAPPER = 40 * 1024 * 1024
MAX_EXPANDED = 128 * 1024 * 1024
FULL_JOBS = {
    'Runtime archive', 'Repository quality', 'Quality',
    'WordPress 7.0 / MySQL 8.4 / PHP 8.2',
    'WordPress 7.0.3 / MySQL 8.4 / PHP 8.2',
    'WordPress 7.0.3 / MariaDB 10.11 / PHP 8.2',
    'WordPress 7.0.3 / MySQL 8.0 floor / PHP 8.2',
}


def sha256(data):
    return hashlib.sha256(data).hexdigest()


def strict_json(data):
    def unique(pairs):
        result = {}
        for key, value in pairs:
            if key in result:
                raise ValueError('Duplicate JSON key')
            result[key] = value
        return result
    def invalid_constant(value):
        raise ValueError('Non-finite JSON number')
    return json.loads(data, object_pairs_hook=unique, parse_constant=invalid_constant)


def archive_files(data, prefix='', max_size=MAX_EXPANDED, max_files=1200):
    """Inspect every member before reading; never extract untrusted archive paths."""
    result = {}
    with zipfile.ZipFile(io.BytesIO(data)) as archive:
        names = set()
        expanded = 0
        if len(archive.infolist()) > max_files:
            raise ValueError('Archive has too many members')
        for entry in archive.infolist():
            name = entry.filename
            path = PurePosixPath(name)
            mode = entry.external_attr >> 16
            expanded += entry.file_size
            if (not name or name in names or '\\' in name or path.is_absolute()
                    or '..' in path.parts or any(part in ('', '.') for part in name.rstrip('/').split('/'))
                    or len(name) > 512 or len(path.parts) > 21
                    or any(not re.fullmatch(r'[A-Za-z0-9_.@+-]+', part) for part in path.parts)
                    or not name.startswith(prefix) or entry.flag_bits & 1
                    or (stat.S_IFMT(mode) not in (0, stat.S_IFREG, stat.S_IFDIR))
                    or expanded > max_size):
                raise ValueError('Unsafe archive member')
            names.add(name)
        for entry in archive.infolist():
            if not entry.is_dir():
                name = entry.filename[len(prefix):]
                if not name:
                    raise ValueError('Empty archive member')
                result[name] = archive.read(entry)
        # Reject file/directory collisions which different extractors handle differently.
        for name in result:
            if any(str(parent) in result for parent in PurePosixPath(name).parents if str(parent) != '.'):
                raise ValueError('Archive file/directory collision')
    return result


def verify_run(run, jobs, *, commit, run_id, attempt):
    if (run.get('id') != run_id or run.get('run_attempt') != attempt
            or run.get('repository', {}).get('full_name') != CORE
            or run.get('repository', {}).get('id') != REPOSITORY_ID
            or run.get('head_repository', {}).get('full_name') != CORE
            or run.get('head_repository', {}).get('id') != REPOSITORY_ID
            or run.get('workflow_id') != WORKFLOW_ID or run.get('path') != WORKFLOW
            or run.get('head_sha') != commit or run.get('status') != 'completed'
            or run.get('conclusion') != 'success'
            or run.get('event') not in ('pull_request', 'push', 'workflow_dispatch')):
        raise ValueError('Core run repository, workflow, source, attempt or qualification mismatch')
    by_name = {}
    for job in jobs:
        name = job.get('name')
        if name in by_name:
            raise ValueError('Ambiguous Core qualification job')
        by_name[name] = job
        if job.get('run_id') != run_id or job.get('run_attempt') != attempt:
            raise ValueError('Core job belongs to another run attempt')
    if set(by_name) != FULL_JOBS | {'Release candidate install readback'}:
        raise ValueError('Unknown Core qualification job set; producer contract needs review')
    if any(by_name[name].get('conclusion') != 'success' or by_name[name].get('status') != 'completed' for name in FULL_JOBS):
        raise ValueError('Applicable Core qualification is incomplete')
    if by_name['Release candidate install readback'].get('conclusion') != 'skipped':
        raise ValueError('Only reviewed full source-head Core qualification is supported')


def verify_artifact(artifact, *, artifact_id, run_id, attempt, commit, expected_digest):
    expected_name = f'ran-booster-runtime-{run_id}-{attempt}'
    if (artifact.get('id') != artifact_id or artifact.get('name') != expected_name
            or artifact.get('expired') is not False
            or artifact.get('digest') != expected_digest
            or not re.fullmatch(r'sha256:[a-f0-9]{64}', expected_digest)
            or artifact.get('workflow_run', {}).get('id') != run_id
            or artifact.get('workflow_run', {}).get('repository_id') != REPOSITORY_ID
            or artifact.get('workflow_run', {}).get('head_repository_id') != REPOSITORY_ID
            or artifact.get('workflow_run', {}).get('head_sha') != commit
            or type(artifact.get('size_in_bytes')) is not int
            or not 0 < artifact['size_in_bytes'] <= MAX_WRAPPER):
        raise ValueError('Core artifact identity/digest mismatch or artifact expired; select no substitute')
    expiry = datetime.fromisoformat(artifact['expires_at'].replace('Z', '+00:00'))
    if expiry <= datetime.now(timezone.utc):
        raise ValueError('Core artifact expired; generate a new qualified pairing')


def inspect_candidate(data, *, run, artifact, commit, tree, attempt):
    """Bind API provenance, wrapper, metadata, inner ZIP and marker before execution."""
    if len(data) != artifact['size_in_bytes'] or 'sha256:' + sha256(data) != artifact['digest']:
        raise ValueError('Core artifact wrapper digest or size mismatch')
    files = archive_files(data, max_size=MAX_WRAPPER, max_files=4)
    metadata = strict_json(files['ran-booster-ci-runtime.json'])
    version = metadata.get('version', '')
    if not re.fullmatch(r'\d+\.\d+\.\d+(?:-[A-Za-z0-9.-]+)?', version):
        raise ValueError('Malformed Core candidate version')
    archive_name = f'ran-booster-{version}.zip'
    checksum_name = archive_name + '.sha256'
    if set(files) != {archive_name, checksum_name, 'ran-booster-ci-runtime.json', 'ran-profile-b-promotion.json'}:
        raise ValueError('Unexpected Core artifact members')
    inner = files[archive_name]
    if len(inner) > MAX_ZIP:
        raise ValueError('Core runtime ZIP too large')
    digest = sha256(inner)
    expected_metadata = {
        'schema': 'ran-booster-ci-runtime', 'schema_version': 3, 'mode': 'built', 'lane': 'full',
        'quality_commit': commit, 'source_commit': commit, 'source_tree': tree,
        'version': version, 'sha256': digest,
        'run': {'id': run['id'], 'attempt': attempt, 'event': run['event']},
        'pull_request': {'number': 0, 'base_sha': '', 'head_sha': '', 'tested_commit': commit, 'tested_tree': tree},
    }
    if metadata != expected_metadata:
        raise ValueError('Core candidate provenance mismatch; synthetic merge artifacts are unsupported')
    expected_checksum = f'{digest}  {archive_name}\n'.encode()
    if files[checksum_name] != expected_checksum:
        raise ValueError('Core runtime checksum mismatch')
    promotion = strict_json(files['ran-profile-b-promotion.json'])
    if promotion != {
        'schema': 'ran-profile-b-promotion', 'schema_version': 1, 'repository': CORE,
        'quality_commit': commit, 'source_commit': commit, 'tag': 'v' + version,
        'assets': [{'name': archive_name, 'sha256': digest}, {'name': checksum_name, 'sha256': sha256(expected_checksum)}],
    }:
        raise ValueError('Core build custody metadata mismatch')
    core_files = archive_files(inner, prefix='ran-booster/')
    marker = strict_json(core_files['ran-booster-release.json'])
    if type(marker.get('schema_version')) is not int or marker != {'schema': 'ran-booster-core-release', 'schema_version': 1, 'version': version, 'commit': commit}:
        raise ValueError('Core installed release marker mismatch')
    header = core_files['ran-booster.php'].decode()
    if not re.search(r'^\s*\* Version:\s*' + re.escape(version) + r'\s*$', header, re.M):
        raise ValueError('Core header version mismatch')
    result = {'core_version': version, 'core_commit': commit, 'core_zip_sha256': digest}
    for field, constant in API_FIELDS.items():
        values = re.findall(r"define\(\s*'" + constant + r"'\s*,\s*(\d+)\s*\)", header)
        if len(values) != 1 or int(values[0]) < 1:
            raise ValueError('Missing or ambiguous Core API declaration')
        result[field] = int(values[0])
    result['core_files'] = {name: sha256(content) for name, content in sorted(core_files.items())}
    result['core'] = {
        'repository': CORE, 'source_commit': commit, 'tested_commit': commit, 'source_kind': 'source-head',
        'version': version, 'run_id': run['id'], 'run_attempt': attempt, 'workflow_path': WORKFLOW,
        'artifact_id': artifact['id'], 'artifact_name': artifact['name'],
        'artifact_sha256': artifact['digest'].removeprefix('sha256:'), 'zip_sha256': digest,
    }
    return result, inner


class CaptureRedirect(urllib.request.HTTPRedirectHandler):
    """Capture the authenticated API redirect; follow it separately without credentials."""
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        raise urllib.error.HTTPError(req.full_url, code, 'Artifact redirect', headers, fp)


def download_artifact(artifact_id):
    headers = {'Accept': 'application/vnd.github+json', 'User-Agent': 'ran-booster-scenarios'}
    import os
    token = os.environ.get('GH_TOKEN', '')
    if token:
        headers['Authorization'] = 'Bearer ' + token
    request = urllib.request.Request(f'https://api.github.com/repos/{CORE}/actions/artifacts/{artifact_id}/zip', headers=headers)
    try:
        with urllib.request.build_opener(CaptureRedirect()).open(request, timeout=60) as response:
            return response.read(MAX_WRAPPER + 1)
    except urllib.error.HTTPError as error:
        if error.code not in (301, 302, 303, 307, 308):
            error.close()
            raise ValueError('Core artifact unavailable or expired; no substitute selected') from None
        location = error.headers['Location']
        error.close()
        target = urllib.parse.urlsplit(location)
        if target.scheme != 'https' or target.username or target.password or target.port not in (None, 443):
            raise ValueError('Unsafe Core artifact download redirect')
        # No token reaches the artifact host or any subsequent redirect.
        with urllib.request.build_opener(NoApiRedirect()).open(urllib.request.Request(location), timeout=60) as response:
            return response.read(MAX_WRAPPER + 1)


def acquire(args):
    if args.repository != CORE or not re.fullmatch(r'[a-f0-9]{40}', args.commit):
        raise ValueError('Exact canonical Core repository and source commit required')
    if any(value < 1 for value in (args.run_id, args.run_attempt, args.artifact_id)):
        raise ValueError('Positive run, attempt and artifact IDs required')
    path = f'repos/{CORE}/actions/runs/{args.run_id}/attempts/{args.run_attempt}'
    run = api(path)
    jobs = []
    page = 1
    while True:
        response = api(path + f'/jobs?per_page=100&page={page}')
        jobs.extend(response['jobs'])
        if len(jobs) >= response['total_count']:
            break
        if page >= 10:
            raise ValueError('Too many Core jobs')
        page += 1
    verify_run(run, jobs, commit=args.commit, run_id=args.run_id, attempt=args.run_attempt)
    workflow = api(f'repos/{CORE}/contents/{WORKFLOW}?ref={args.commit}')
    if workflow.get('encoding') != 'base64' or sha256(base64.b64decode(workflow['content'])) != WORKFLOW_SHA256:
        raise ValueError('Core producer workflow changed; reviewed contract update required')
    tree = api(f'repos/{CORE}/git/commits/{args.commit}')['tree']['sha']
    if not re.fullmatch(r'[a-f0-9]{40}', tree):
        raise ValueError('Malformed Core source tree')
    artifact = api(f'repos/{CORE}/actions/artifacts/{args.artifact_id}')
    verify_artifact(artifact, artifact_id=args.artifact_id, run_id=args.run_id, attempt=args.run_attempt,
                    commit=args.commit, expected_digest=args.artifact_digest)
    data = download_artifact(args.artifact_id)
    evidence, inner = inspect_candidate(data, run=run, artifact=artifact, commit=args.commit, tree=tree, attempt=args.run_attempt)
    fresh = api(f'repos/{CORE}/actions/artifacts/{args.artifact_id}')
    if any(fresh.get(key) != artifact.get(key) for key in ('id', 'name', 'digest', 'expired', 'size_in_bytes', 'workflow_run')):
        raise ValueError('Core artifact changed during verification')
    if args.output_dir.is_symlink():
        raise ValueError('Symlink Core acquisition output directory')
    args.output_dir.mkdir(parents=True, exist_ok=True)
    if any(args.output_dir.iterdir()):
        raise ValueError('Core candidate output directory must be empty')
    (args.output_dir / 'core.zip').write_bytes(inner)
    (args.output_dir / 'core-candidate.json').write_text(json.dumps(evidence, indent=2) + '\n')
    (args.output_dir / 'core-acquisition.json').write_text(json.dumps({'run': run, 'jobs': jobs, 'artifact': artifact}, indent=2) + '\n')
    print(f"Verified source-head Core {args.commit} from run {args.run_id}, attempt {args.run_attempt}, artifact {args.artifact_id}")


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--repository', required=True)
    parser.add_argument('--commit', required=True)
    parser.add_argument('--run-id', type=int, required=True)
    parser.add_argument('--run-attempt', type=int, required=True)
    parser.add_argument('--artifact-id', type=int, required=True)
    parser.add_argument('--artifact-digest', required=True)
    parser.add_argument('--output-dir', type=Path, required=True)
    acquire(parser.parse_args())


if __name__ == '__main__':
    main()
