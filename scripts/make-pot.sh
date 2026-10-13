#!/usr/bin/env bash
# Generate or verify the runtime gettext catalogue with pinned WP-CLI.
set -euo pipefail

fail() { printf 'make-pot: %s\n' "$*" >&2; exit 1; }
script_dir=$(CDPATH='' cd -- "$(dirname -- "$0")" && pwd)
cd "$script_dir/.."
mode=${1:-}
[[ $# -le 1 && ( -z "$mode" || "$mode" == '--check' ) ]] || fail 'usage: make-pot.sh [--check]'
wp_cli=$(command -v wp) || fail 'WP-CLI 2.12.0 is required on PATH.'
php_runner=(php -d memory_limit=512M -d 'error_reporting=E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED')
[[ $("${php_runner[@]}" "$wp_cli" --allow-root --version) == 'WP-CLI 2.12.0' ]] || fail 'WP-CLI 2.12.0 is required.'

# Derive extraction coverage from the actual release selection, excluding inert
# target samples and generated archives/catalogues. New runtime files join it.
includes=''
while IFS= read -r entry || [[ -n "$entry" ]]; do
    case "$entry" in ''|'#'*|samples/*|archives/*|languages|languages/*) continue ;; esac
    [[ -n "$includes" ]] && includes+=','
    includes+="$entry"
done < release-contents.txt
[[ -n "$includes" ]] || fail 'No runtime release inputs were found.'
temporary_dir=$(mktemp -d "${TMPDIR:-/tmp}/ran-booster-scenarios-i18n.XXXXXX")
trap 'rm -rf "$temporary_dir"' EXIT HUP INT TERM
args=(i18n make-pot . --allow-root --include="$includes"
    --exclude=tests,scripts,samples,archives,languages,vendor,node_modules,build,.git,.github,.agents,.codex
    --skip-block-json --skip-theme-json
    --package-name='RAN Booster Scenarios'
    --file-comment=$'Copyright (C) 2026 Rockets Are Nostalgic\nThis file is distributed under the GPL-2.0-only.'
    --headers='{"Project-Id-Version":"RAN Booster Scenarios","POT-Creation-Date":"2026-10-13T00:00:00+00:00","Report-Msgid-Bugs-To":"https://github.com/RocketsAreNostalgic/ran-booster-scenarios/issues"}')
for scope in domain all-domains; do
    domain_arg=--domain=ran-booster-scenarios
    [[ "$scope" == all-domains ]] && domain_arg=--ignore-domain
    if ! output=$("${php_runner[@]}" "$wp_cli" "${args[@]}" "$temporary_dir/$scope.pot" "$domain_arg" 2>&1); then
        printf '%s\n' "$output" >&2
        fail 'WP-CLI could not generate the catalogue.'
    fi
    printf '%s\n' "$output"
    [[ "$output" != *'Warning:'* ]] || fail 'WP-CLI reported gettext warnings.'
    sed '/^"X-Domain: /d' "$temporary_dir/$scope.pot" > "$temporary_dir/$scope.normalised"
done
cmp -s "$temporary_dir/domain.normalised" "$temporary_dir/all-domains.normalised" || fail 'Runtime gettext strings use an unexpected text domain.'
if [[ "$mode" == '--check' ]]; then
    cmp -s "$temporary_dir/domain.pot" languages/ran-booster-scenarios.pot || fail 'Catalogue is stale; run composer i18n.'
else
    mkdir -p languages
    chmod 0644 "$temporary_dir/domain.pot"
    mv "$temporary_dir/domain.pot" languages/ran-booster-scenarios.pot
fi
