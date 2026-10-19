=== RAN Booster Scenarios ===
Contributors: rocketsarenostalgic
Tags: development, testing
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 8.4
License: GPL-2.0-only
License URI: https://www.gnu.org/licenses/gpl-2.0.html
x-release-please-start-version
Stable tag: 0.1.2
x-release-please-end

Repeatable release, history and guidance scenarios for disposable Booster installations.

== Description ==

This development plugin supplies controlled GitHub responses for two synthetic repositories. Booster still performs its normal authorization, persistence, eligibility, inspection and rendering work. It never supplies arbitrary URLs or executes user-entered scenario code.

The release is locked to the exact Core version, commit and APIs in compatibility.json. Install the published Core ZIP with its release marker. A missing or incompatible contract disables scenarios and blocks synthetic requests. The manifest's Core ZIP SHA-256 pins the artifact used during qualification; runtime checks the installed header, provenance marker and APIs.

Supported environments are WordPress 7.0 or later, PHP 8.4–8.5, and a disposable WordPress local or development site. No WordPress.org publication or production-site use is intended.

== Installation ==

1. On a disposable site only, set WP_ENVIRONMENT_TYPE to local or development and define RAN_BOOSTER_SCENARIOS_DISPOSABLE as true in wp-config.php.
2. Install the exact compatible published Core ZIP and activate Booster.
3. Install the versioned scenarios ZIP and activate this plugin.
4. Open Tools > Booster scenarios. Select a scenario and plugin, theme or both targets.
5. Use the direct route links and normal Booster refresh controls. For an update test, install the supplied version 1 seed target before checking the controlled version 2 release.

Do not use real credentials. The only synthetic targets are ran-booster-scenarios/scenario-plugin and ran-booster-scenarios/scenario-theme. The bundled targets are inert. Public owner discovery and root-path repository inspection are supported; nested repository source paths and authenticated account browsing are not.

== Scenarios ==

* Immutable and mutable releases.
* No releases and malformed release data.
* Provider unavailable, denied and rate-limited responses.
* Manual mutable-to-immutable and immutable-to-mutable transitions.
* A valid offer that disappears after an explicit stage change.

Selection persists across independent browser and cron requests. Advancing changes the fixture stage; use normal Booster refresh or cron to update actual product evidence. Reset disables selection without erasing Booster records, history, settings or caches.

Planned product notices can remain absent in the supported Core version. The fixture must not manufacture product UI. See the acceptance matrix for the distinction between current behavior and unfinished product work.

== Extending the fixture ==

Scenario labels and stages live in catalog.php. Admin links remain fixed relative WordPress routes. New HTTP behavior must have an exact synthetic target and read-only endpoint allowlist, deterministic data and regression tests, including disabled and incompatible cases. Update compatibility.json and requalify the complete release before supporting another Core release.

Synthetic scenarios do not prove actual GitHub workflow publication or the complete installed acceptance journey against disposable remote repositories.
