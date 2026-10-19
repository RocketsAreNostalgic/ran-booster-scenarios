# RAN Booster Scenarios

An installable test plugin for repeatable walkthroughs on disposable WordPress
sites. It supplies controlled GitHub responses for two synthetic repositories;
Booster still performs normal validation, persistence, eligibility and rendering.
It never injects notices or marks acceptance tests as passed.

The complete scenario and acceptance inventory is
[the scenario inventory](docs/scenarios.md).
The first release targets exactly **Core 1.0.0-beta.35**, released commit
`5743df3af0a33c35839aec0bb369a72d084b3d7a`, with Provider API15, Add-on API17
and Admin Interaction API3. Install Core's published ZIP, including its release
marker, in `wp-content/plugins/ran-booster`. A matching version string alone is
insufficient. A later Core release needs a reviewed fixture compatibility update.

For **pre-release acceptance**, build a fixed **Unreleased candidate** pair from
an exact qualified Core CI artifact and exact Scenarios commit. Follow the
executable [candidate acceptance journey](docs/candidate-acceptance.md). It is
separate from the published journey below and publishes neither plugin.

## Build and install

From this repository root:

```sh
python3 scripts/build-release.py --output-dir build/release
```

Release Please owns the fixture version (currently0.1.1).
The output directory `build/release` contains three installable ZIPs
and their SHA-256 checksum files:

- `ran-booster-scenarios-0.1.1-core-1.0.0-beta.35.zip`: install and activate this plugin.
- `scenario-plugin-1.0.0.zip`: inert plugin target for update scenarios.
- `scenario-theme-1.0.0.zip`: minimal theme target for update scenarios; it need not be active.

The **Quality** workflow builds and tests these files. Release Please and the
organisation's shared Profile B workflow manage version proposals and publish
the exact tested assets as immutable GitHub releases after approved merges.
PR artifacts remain review artifacts until that lifecycle completes. See
[RELEASE.md](RELEASE.md) for Core-update proposals, release prerequisites and
recovery boundaries. This repository is independent of Core; its plugin is
never included in Core's production ZIP.

Development baseline: WordPress7.0+, PHP8.4–8.5, Composer2, Node24.21.0 and the
exact pnpm11.13.1 packageManager. See [CONTRIBUTING.md](CONTRIBUTING.md) and
[starter baseline mapping](docs/baseline.md). The test host is the verified
published Core archive for ordinary Quality; the candidate workflow uses the
separately verified exact CI artifact, never an arbitrary sibling checkout.

On the disposable site only, add before WordPress loads:

```php
define( 'WP_ENVIRONMENT_TYPE', 'local' ); // 'development' is also accepted.
define( 'RAN_BOOSTER_SCENARIOS_DISPOSABLE', true );
```

Open **Tools → Booster scenarios**. The page reports any compatibility problem.
Choose the scenario and plugin/theme target, then select **Select / restart**.
The selection survives later browser, cron and WP-CLI requests. No real GitHub
token is needed for the synthetic public repositories. Do not use production
credentials for this fixture.

For update tests, install the v1 target ZIPs using normal WordPress upload
controls, then adopt the relevant target through Booster's normal repository
management flow with one of these exact identities:

- `ran-booster-scenarios/scenario-plugin`
- `ran-booster-scenarios/scenario-theme`

For prospective installation tests, start without the corresponding target and
use the Add plugin/theme repository link. The fixture serves only its bundled
harmless v2 archive through the normal download/validation path. Synthetic
installation does not prove actual GitHub setup, publication or credentials.

## Walkthrough controls

The page links directly to the allowlisted Booster routes, including the sample
package pages, Activity, Troubleshooting and release documentation. These are
read-only navigation links. Use normal Booster controls to refresh, inspect,
confirm or change a managed target. The fixture does not bypass permissions,
nonces, confirmations or update policy.

For a two-stage scenario, first run the initial check, inspect its result, then
press **Advance to stage 1** and run a fresh normal check. Stage changes never
depend on HTTP call counts, so loading a page cannot unexpectedly advance them.
Select / restart returns to stage zero. Existing caches are deliberately not
cleared: distinguish a fresh discovery from cached or skipped work.

**Disable / reset fixture selection** removes only the fixture's own option.
It leaves Booster history, packages, settings, credentials and files intact.
While the fixture plugin remains active, unsupported requests for its synthetic
namespace are blocked even when no scenario is enabled. Deactivate the target
packages and remove their Booster management through normal controls before
removing the fixture plugin, to avoid later real requests for synthetic targets.

## Cases beyond a response selection

| Case | Operator action and expected result |
| --- | --- |
| Cron → later request | Trigger the existing WordPress update-check cron event, end that request, then open Activity and the target page in a separate authenticated HTTP request. Dated results must persist. |
| Refresh → redirect | Use the real refresh form; follow its redirect and verify the subsequent GET reads the recorded result without additional remote discovery. |
| Logging disabled | Turn off diagnostic capture through Booster's normal controls, repeat both journeys and verify Activity still works. Admin authentication remains enabled. |
| Offer disappears | Complete stage zero of the disappearing-offer scenario, advance, refresh, and verify old history remains while no current offer is invented. |
| Manual versus Automatic | Repeat release transitions with each preference; inspect unchanged stored preference and real eligibility. A metadata flag alone does not prove eligibility. |
| Changed source/access/lifetime | Use normal supported operations on disposable targets. Verify prior history stays dated and is not rebound to the new context. |
| Pagination/filtering | Generate distinct real operations; inspect stable pages and filters. This plugin does not insert fake Activity rows. |
| Permissions/localization | Visit as administrator and a less privileged user, and repeat with a translated locale. Scenario labels are test instructions; product messages must retain their translation behavior. |
| Setup and publication (#81) | Use separately authorized disposable repositories and real workflows. Synthetic namespace mutations are blocked and cannot establish live setup/PR/publishing acceptance. |

Core beta.35 does not implement every notice/guidance requirement in organisation
#85. The fixture makes the inputs repeatable; missing product UI remains a
product finding. Do not mark #81/#85 complete from a successful fixture response,
a screenshot alone, or source tests.

## Extend and release the fixture

Add a stable scenario key to `catalog.php`, its response mode to the HTTP fixture,
and a regression for both target types. Give it an expected outcome and explicit
phase sequence. Add or amend routes in the same reviewed catalog; use relative
WordPress admin paths and fixed sample package identities, never request-supplied
URLs, arbitrary PHP, SQL or remote callbacks. New routes must map to actual Core
routes in the supported release. Settings select reviewed definitions; they do
not create executable definitions.

For a new supported Core, use the proposed compatibility.json update and
refresh route and provider contracts,
run canonical fixture checks against the new released Core, rebuild twice and compare bytes,
and perform the installed walkthrough. Release Please owns the next fixture version and all generated version fields.
A fixture release is pinned to one Core release; do not silently widen compatibility ranges. Preserve older fixture
artifacts for the Core versions they support.

The build produces deterministic ZIPs and checksums. Generated files under
`archives/` are ignored; their source is the fixed inert `samples/` tree. Never
ship credentials, Core dependencies or an arbitrary remote archive in the test
plugin. The fixture cannot replace independent released-artifact qualification.
