#!/usr/bin/env python3
"""Propose a reviewed Core compatibility update; never merge or bump fixture versions."""
import argparse
import base64
import json
import os
from pathlib import Path
import urllib.error
from core_release import api, CORE, verified_release, version_key

REPOSITORY = 'RocketsAreNostalgic/ran-booster-scenarios'


def candidate(releases, current):
    eligible = []
    for release in releases:
        if release.get('draft') is not False or release.get('immutable') is not True:
            continue
        try:
            key = version_key(release.get('tag_name', '').removeprefix('v'))
        except ValueError:
            continue
        if key > version_key(current):
            eligible.append((key, release))
    return max(eligible, key=lambda item: item[0])[1] if eligible else None


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--apply', action='store_true', help='Create the compatibility PR in the fixed repository')
    args = parser.parse_args()
    root = Path(__file__).resolve().parent.parent
    current_text = (root / 'compatibility.json').read_text()
    current = json.loads(current_text)
    release = candidate(api('repos/' + CORE + '/releases?per_page=100'), current['core_version'])
    if release is None:
        print('No newer immutable Core release in the latest 100 releases.')
        return
    evidence, _ = verified_release(release)
    print('Verified Core candidate ' + evidence['core_version'] + '; compatibility still requires review and CI.')
    if not args.apply:
        return
    if os.environ.get('GITHUB_REPOSITORY') != REPOSITORY or os.environ.get('GITHUB_REF') != 'refs/heads/main':
        raise ValueError('Proposals run only from this repository main')
    base = api('repos/' + REPOSITORY + '/git/ref/heads/main')['object']['sha']
    if os.environ.get('GITHUB_SHA') != base:
        raise ValueError('Proposal checkout is no longer current main')
    existing = api('repos/' + REPOSITORY + '/contents/compatibility.json?ref=' + base)
    if base64.b64decode(existing['content']).decode() != current_text:
        raise ValueError('Checked-out compatibility contract differs from main')
    branch = 'automation/core-' + evidence['core_version']
    branch_path = 'repos/' + REPOSITORY + '/git/ref/heads/' + branch
    desired = dict(current, **evidence)
    content = json.dumps(desired, indent=2) + '\n'
    try:
        api(branch_path)
        # Existing proposal is maintainer-owned: never overwrite or reopen it.
        print('Proposal branch already exists; inspect its PR and changes manually.')
        return
    except urllib.error.HTTPError as error:
        if error.code != 404:
            raise
    tree = api('repos/' + REPOSITORY + '/git/trees', 'POST', {
        'base_tree': api('repos/' + REPOSITORY + '/git/commits/' + base)['tree']['sha'],
        'tree': [{'path': 'compatibility.json', 'mode': '100644', 'type': 'blob', 'content': content}],
    })
    title = 'fix(core): support Core ' + evidence['core_version']
    commit = api('repos/' + REPOSITORY + '/git/commits', 'POST', {'message': title, 'tree': tree['sha'], 'parents': [base]})
    if api('repos/' + REPOSITORY + '/git/ref/heads/main')['object']['sha'] != base:
        raise ValueError('Main changed before proposal creation')
    api('repos/' + REPOSITORY + '/git/refs', 'POST', {'ref': 'refs/heads/' + branch, 'sha': commit['sha']})
    body = ('Updates only the exact Core version, commit, ZIP digest and API declarations from verified immutable release '
            + release['html_url'] + '.\n\nThis is a compatibility proposal, not certification. Review route/API changes, '
            'run host and installed scenarios, and inspect all Quality results before merging. '
            'Fixture version/header/changelog stay under Release Please ownership; the releasable fix commit '
            'will produce a new fixture release proposal after merge. Older fixture releases retain their exact Core pins.\n\n'
            '- [ ] Review Core changes and scenario routes\n- [ ] Pass exact-host and installed Quality\n'
            '- [ ] Perform applicable manual scenario walkthroughs\n')
    api('repos/' + REPOSITORY + '/pulls', 'POST', {'title': title, 'head': branch, 'base': 'main', 'body': body})
    # GITHUB_TOKEN-created PRs do not trigger pull_request workflows. Bind branch
    # immediately before input-free dispatch; Quality records its checked-out SHA.
    if api(branch_path)['object']['sha'] != commit['sha']:
        raise ValueError('Proposal changed before Quality dispatch')
    api('repos/' + REPOSITORY + '/actions/workflows/quality.yml/dispatches', 'POST', {'ref': branch})
    print('Created compatibility proposal and dispatched Quality. No merge or release was performed.')


if __name__ == '__main__':
    main()
