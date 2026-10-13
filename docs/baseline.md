# Starter derivation and applicability

Reference: RocketsAreNostalgic/ran-starter-plugin commit
283ee0177022375d7e4b59194d05834b98c3884f (13 October2026).
This is a derived standalone product, not a GitHub fork relationship or a copy
of the starter's demonstration features. Original fixture source was reviewed
in CorePR311 at714b01aa3718cdf85e9137bc409a67fb813d39ef; Core receives none of it.
Starter-derived tooling retains its MIT notice in LICENSE.starter. Fixture code
continues under GPL-2.0-only in LICENSE.

| Baseline | Application |
| --- | --- |
| WordPress/PHP | WP7.0+, PHP8.4–8.5; header, Composer, PHPCompatibility and native CI agree. |
| PHP quality | Shared RANWordPressPlugin; stronger starter WordPress-Docs plus Booster snake_case; independent syntax, behavioral tests and Level8 production/development analysis. |
| Coverage | Maintained PHP must enter actual checkers or fail coverage; synthetic test symbols do not redefine runtime analysis. Negative controls protect gates. |
| Frontend | Shared RAN quality package for actual maintained stylesheet/formatting surface. No example controllers, React/Vite bundle or unused production JS is copied merely to populate a gate. |
| Dependencies | Tracked locks; existing qualified immutable shared-package revisions. Core is an exact external test host, not a packaged/runtime Composer dependency. No unused plugin-library dependency. |
| Packaging | Separate deterministic allowlisted ZIPs/checksums, manifest/version agreement, exact archive installation. No Core code, dev dependencies or credentials in the fixture ZIP. |
| Releases | Starter's Release Please ownership retained, extended from ProfileA to shared ProfileB because this product publishes installable ZIPs. No local publisher. |
| Core updates | Immutable release evidence produces a proposal PR; reviewed compatibility plus Quality precede Release Please's version PR. No automatic merge. |
| Skills/community | Portable RAN review skill and official WordPress skills; organisation public community defaults; repository-specific contribution scope. |
| Examples | Starter demo CPTs, dashboards, templates and assets removed as instructed by its derivation guide. Existing fixture control page remains simple procedural WordPress code; a PSR-4 class layer is unnecessary without a consumer. |

Production fixture code is independent from Booster Core. Moving it does not
certify missing Core notices, actual GitHub setup/publication or browser journeys.
See the scenario matrix and acceptance issue for those remaining obligations.

The starter shared quality workflow has no exact-Core-host setup input. This
repository's local read-only Quality reproduces the applicable canonical checks
and adds released-host and installed-archive proofs instead of weakening the
host-dependent composer check to fit that caller. Shared standards are still
consumed through the locked packages; the terminal quality check requires both
native PHP lanes. This CI specialization is part of independent PR review.

The copied WordPress skills have narrow local portability fixes: repository-root
commands use `.codex/skills/`, and plugin detectors accept conventional starred
PHP docblock headers as well as unstarred headers. Canonical tests exercise both
forms and discovery of this plugin; these fixes do not change runtime behavior.
