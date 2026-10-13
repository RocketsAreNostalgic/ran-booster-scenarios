#!/usr/bin/env python3
"""Read and verify immutable Core release evidence without executing Core code."""
import argparse
import hashlib
import io
import json
import os
from pathlib import Path, PurePosixPath
import re
import stat
import urllib.request
import zipfile

CORE = 'RocketsAreNostalgic/ran-booster'
MAX_ZIP = 32 * 1024 * 1024
API_FIELDS = {
    'provider_api': 'RAN_BOOSTER_PROVIDER_API_VERSION',
    'addon_api': 'RAN_BOOSTER_ADDON_API_VERSION',
    'admin_interaction_api': 'RAN_BOOSTER_ADMIN_INTERACTION_API_VERSION',
}


class NoApiRedirect(urllib.request.HTTPRedirectHandler):
    """Never forward API authorization to any redirect destination."""

    def redirect_request(self, req, fp, code, msg, headers, newurl):
        raise ValueError('GitHub API redirects are not permitted')


def api(path, method='GET', payload=None):
    """Confine the optional token to api.github.com; never print response bodies."""
    headers = {'Accept': 'application/vnd.github+json', 'User-Agent': 'ran-booster-scenarios'}
    token = os.environ.get('GH_TOKEN', '')
    if token:
        headers['Authorization'] = 'Bearer ' + token
    data = None if payload is None else json.dumps(payload).encode()
    request = urllib.request.Request('https://api.github.com/' + path, data=data, headers=headers, method=method)
    with urllib.request.build_opener(NoApiRedirect()).open(request, timeout=60) as response:
        body = response.read()
        return json.loads(body) if body else None


def version_key(version):
    match = re.fullmatch(r'(\d+)\.(\d+)\.(\d+)(?:-(alpha|beta|rc)\.(\d+))?', version)
    if not match:
        raise ValueError('Unsupported Core version format')
    major, minor, patch, phase, sequence = match.groups()
    return (int(major), int(minor), int(patch), {'alpha': 0, 'beta': 1, 'rc': 2, None: 3}[phase], int(sequence or 0))


def release_asset(release):
    if release.get('draft') is not False or release.get('immutable') is not True:
        raise ValueError('Core release must be published and immutable')
    tag = release.get('tag_name', '')
    if not tag.startswith('v'):
        raise ValueError('Core release tag must start with v')
    version_key(tag[1:])
    name = 'ran-booster-' + tag[1:] + '.zip'
    assets = [asset for asset in release.get('assets', []) if asset.get('name') == name]
    if len(assets) != 1:
        raise ValueError('Core release needs exactly one expected runtime ZIP')
    asset = assets[0]
    expected_url = 'https://github.com/' + CORE + '/releases/download/' + tag + '/' + name
    if asset.get('browser_download_url') != expected_url or not re.fullmatch(r'sha256:[a-f0-9]{64}', asset.get('digest', '')):
        raise ValueError('Core asset URL or digest is invalid')
    if type(asset.get('size')) is not int or not 0 < asset['size'] <= MAX_ZIP:
        raise ValueError('Core asset size is invalid')
    return asset


def inspect_archive(data, release, commit):
    asset = release_asset(release)
    digest = hashlib.sha256(data).hexdigest()
    if len(data) != asset['size'] or 'sha256:' + digest != asset['digest']:
        raise ValueError('Core ZIP digest or size mismatch')
    if not re.fullmatch(r'[a-f0-9]{40}', commit):
        raise ValueError('Core commit identity is invalid')
    with zipfile.ZipFile(io.BytesIO(data)) as archive:
        names = set()
        expanded = 0
        for entry in archive.infolist():
            path = PurePosixPath(entry.filename)
            mode = entry.external_attr >> 16
            expanded += entry.file_size
            if (entry.filename in names or not entry.filename.startswith('ran-booster/')
                    or '..' in path.parts or '\\' in entry.filename or path.is_absolute()
                    or stat.S_ISLNK(mode) or expanded > 128 * 1024 * 1024):
                raise ValueError('Unsafe Core ZIP member')
            names.add(entry.filename)
        marker = json.loads(archive.read('ran-booster/ran-booster-release.json'))
        header = archive.read('ran-booster/ran-booster.php').decode()
    version = release['tag_name'][1:]
    if marker != {'schema': 'ran-booster-core-release', 'schema_version': 1, 'version': version, 'commit': commit}:
        raise ValueError('Core release marker mismatch')
    if not re.search(r'^\s*\* Version:\s*' + re.escape(version) + r'\s*$', header, re.M):
        raise ValueError('Core plugin version mismatch')
    result = {'core_version': version, 'core_commit': commit, 'core_zip_sha256': digest}
    for field, constant in API_FIELDS.items():
        values = re.findall(r"define\(\s*'" + constant + r"'\s*,\s*(\d+)\s*\)", header)
        if len(values) != 1 or int(values[0]) < 1:
            raise ValueError('Core API declaration is missing or ambiguous')
        result[field] = int(values[0])
    return result


def tag_commit(tag):
    obj = api('repos/' + CORE + '/git/ref/tags/' + tag)['object']
    for _ in range(5):
        if obj['type'] == 'commit':
            return obj['sha']
        if obj['type'] != 'tag':
            break
        obj = api('repos/' + CORE + '/git/tags/' + obj['sha'])['object']
    raise ValueError('Core tag does not resolve to a commit')


def verified_release(release):
    asset = release_asset(release)
    # No authorization header follows the public asset's CDN redirect.
    with urllib.request.urlopen(asset['browser_download_url'], timeout=60) as response:
        data = response.read(MAX_ZIP + 1)
    evidence = inspect_archive(data, release, tag_commit(release['tag_name']))
    fresh = api('repos/' + CORE + '/releases/' + str(release['id']))
    fresh_asset = release_asset(fresh)
    stable_fields = ('id', 'name', 'size', 'digest', 'browser_download_url')
    if any(fresh.get(key) != release.get(key) for key in ('id', 'tag_name', 'prerelease')) or any(fresh_asset.get(key) != asset.get(key) for key in stable_fields):
        raise ValueError('Core release changed during verification')
    return evidence, data


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output-zip', type=Path, required=True)
    args = parser.parse_args()
    contract = json.loads((Path(__file__).resolve().parent.parent / 'compatibility.json').read_text())
    release = api('repos/' + CORE + '/releases/tags/v' + contract['core_version'])
    evidence, data = verified_release(release)
    if any(contract.get(key) != value for key, value in evidence.items()):
        raise ValueError('Released Core does not match compatibility.json')
    args.output_zip.parent.mkdir(parents=True, exist_ok=True)
    args.output_zip.write_bytes(data)
    print('Verified immutable Core ' + evidence['core_version'] + ' at ' + evidence['core_commit'])


if __name__ == '__main__':
    main()
