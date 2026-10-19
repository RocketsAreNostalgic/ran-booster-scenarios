# Releases and Core compatibility

Release Please is the sole fixture version/changelog/tag/release authority.
It manages version.txt, the plugin header, package.json, compatibility.json's
fixture_version and readme.txt. Core pins are not derived from the fixture
version: each fixture release records one exact supported Core release.

Pre-release acceptance uses the separate [candidate pairing journey](docs/candidate-acceptance.md).
Its manual Quality invocation consumes exact successful Core CI artifacts,
produces distinctly named acceptance ZIPs and no Profile B promotion manifest.
It cannot publish or certify a release. Generated candidate metadata is refused
by the ordinary release builder. Final published-artifact checks still apply.

Quality is read-only and qualifies exact source revisions. It builds twice,
checks byte equality, runs PHP/frontend/host checks and installs the exact ZIP
on disposable WordPress under PHP8.4 and8.5. It uploads the six tested ZIP/checksum
assets with a run-ID/attempt-bound ProfileB promotion manifest. The pinned shared
ProfileB workflow admits only successful canonical main-push Quality and promotes
those exact bytes after the approved Release Please PR is merged. PR/manual
Quality alone does not admit publication. No second publisher or version engine
exists here. Existing immutable fixture releases retain their original Core pin.

A daily/manual compatibility workflow checks the newest100 Core releases,
including beta releases. It accepts only a newer supported SemVer with immutable
published state, an exact expected runtime ZIP URL, GitHub SHA256, matching tag
commit and archive release marker, header version and unambiguous API declarations.
It never executes downloaded source while proposing. Only compatibility.json
changes in the proposal. Existing proposal branches are left untouched. The
workflow explicitly dispatches Quality because GITHUB_TOKEN-created PRs may not
trigger pull_request workflows. Failed/partial branch or dispatch creation needs
maintainer inspection; no force-push, automatic reopening or silent repair occurs.

Review Core route/API changes and the issue's operator scenarios before merging
a compatibility proposal. A fix(core) Conventional Commit prompts Release Please
to propose the next fixture release; the proposal itself does not certify routes
or bump versions. Release Please's generated PR requires its own exact-head
qualification and owner-approved merge.

The first immutable release, [v0.1.1](https://github.com/RocketsAreNostalgic/ran-booster-scenarios/releases/tag/v0.1.1),
was published from release PR3. The read-only **Release Liveness** workflow now
runs hourly and on manual dispatch, using the starter's pinned shared observer.
It matches the manifest version to its merged release proposal and reports
published, pending or explicitly reconciled state; an unresolved candidate
beyond the default 60-minute grace period fails after publisher activity stops.
Changes to the caller also run the observer on pull requests. It observes the
default branch's release state and never publishes, repairs or changes labels.
Profile B remains responsible for exact asset promotion and immutable readback.

Owner-managed prerequisites are Actions availability, reusable-workflow/Action
policy, permission for GITHUB_TOKEN to create PRs, appropriate required checks and
immutable releases. No administration credential is introduced to probe/change
these settings. ProfileB requires immutable readback after publication; a wrong
setting can leave a public mutable release and a failed workflow, not a rollback.
Inspect that state and follow the shared contract's next-version recovery; never
replace an asset, move a tag or rerun blindly. See the organisation
[ProfileB contract](https://github.com/RocketsAreNostalgic/.github/blob/593768db30a0101e940e85b9a084b2c773322785/docs/release/RELEASE_PROFILE_B.md).

This lifecycle publishes GitHub test-tool releases only. WordPress.org
publication, deployments and manual UI acceptance are separate work.

Connected journey inputs and prerequisites are documented in
[connected walkthroughs](docs/connected-walkthroughs.md). The ordinary six release assets remain unchanged in shape;
ten credential-free Blueprint inputs are deterministic nested plugin contents.
New recipes require the feature-bearing exact Core pair and refuse unavailable
APIs. External replacement/Migrator fixtures and genuine interrupted-operation
prerequisites are separately verified; no fixture action clears acceptance holds.
