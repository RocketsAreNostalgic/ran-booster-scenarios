# Contributing

This is an internal developer tool in a public repository. Organisation community
health, conduct, private security disclosure and safe issue-intake policies apply.
Do not post credentials, private repository/site identities or customer data.
No local issue-template override is supplied, so the complete organisation set
continues to apply. Public WordPress.org support is not offered.

Use PHP8.4 or8.5, Composer2, Node24.21.0 and the exact pnpm version in package.json.
Install tracked locks with composer install and pnpm install --frozen-lockfile.
Run composer check and pnpm check. The unit-test command generates its inert
sample archives automatically; no release build is required first. Download the pinned immutable Core with
python3 scripts/core_release.py --output-zip build/core.zip, extract into build/core,
and use RAN_BOOSTER_CORE_DIR=$PWD/build/core/ran-booster for host tests.
Never point tests at a live/shared site. Quality additionally verifies packaged
installation on WordPress and both declared PHP versions.

Keep changes focused, use Conventional Commits and record exact check evidence.
Release Please owns generated version/changelog changes. A Core update must
change compatibility.json, qualify the new exact release and pass scenario
review; it must not silently widen the runtime guard. See RELEASE.md.
