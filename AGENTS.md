# RAN Booster Scenarios

This repository owns the disposable-site scenario plugin independently of Core.
Read README.md, CONTRIBUTING.md, RELEASE.md and docs/baseline.md before substantial changes.
Use the checked-in WordPress skills under .codex/skills for plugin/analysis work
and .agents/skills/code-review for every review. Use bounded independent agents
where useful; every published PR requires independent review of its exact base
and head. Prior source review or green CI does not replace that review.

Keep WordPress 7.0+, PHP >=8.4 <8.6, Node24.21.0 and exact packageManager metadata,
headers, compatibility checks, CI and docs aligned. Core's broader PHP support
does not widen this plugin's qualified range. All first-party PHP is directly
checked at PHPStan Level8, including scripts/tests and synthetic fixture worlds.
Use shared RANWordPressPlugin and applicable frontend shared quality exports.
Preserve WordPress-Docs and Booster's owned snake_case declarations; justify
foreign lifecycle signatures and each narrow PHPCS exception at its occurrence.
Do not introduce baselines, broad suppressions, per-file analysis waivers or
empty-success checks. Coverage and negative controls are required.

Canonical gates: composer check and pnpm check. Run packaging/workflow/host and
installed checks from Quality as well; do not infer manual scenario acceptance.
Use tracked locks, exact released host evidence and isolated test environments.
Never commit dependencies, credentials, .dex, caches or generated archives.
Dex is optional local execution bookkeeping when available; tracked decisions
and evidence belong in docs/issues/PRs, not personal working state.

Keep runtime simulation bounded to the reserved synthetic namespace and bundled
inert archives. Preserve exact Core release/version/API admission, disposable-site
opt-in, local/development restriction, capabilities, nonces, escaping and the real
Core behavior. Never manufacture notices/history or wipe real settings.

Release Please owns fixture versions, changelog, tags and draft releases. Shared
Profile B owns promotion of the exact tested ZIPs after trusted main Quality.
Core-update automation may propose a compatibility PR only; never auto-merge,
certify a candidate from metadata alone, or implement a second version engine.
Use Conventional Commits. Merge and release decisions require Ben's explicit
approval of the specific PR/release. Do not merge this implementation implicitly.
Public support/WordPress.org publication and deployments are separate decisions.

Do not invoke Blacksmith AI, codesmith, Autofix, CI Tuning or Testbox agents.
Ordinary Blacksmith runner infrastructure is allowed only when CI selects it.
