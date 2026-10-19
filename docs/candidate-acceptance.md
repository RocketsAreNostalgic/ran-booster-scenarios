# Pre-release acceptance with a fixed candidate pair

Candidate pairing enables unreleased screen and journey inspection without
publishing either plugin. PHP **8.5** is the standard walkthrough environment;
ordinary released Quality still covers PHP8.4 and8.5. Results certify neither
general Core compatibility nor publication.

## Select and qualify

1. Obtain an admitted **source-head** runtime from the Core integration owner.
   Resolve branch/PR names to full commits. Record the Core commit, successful
   Quality run, attempt, runtime artifact ID and GitHub `sha256:` artifact digest.
   The resolver accepts only the reviewed full-lane producer contract. Synthetic
   merge and release-candidate lanes refuse explicitly. Changed producer files
   require a reviewed consumer contract update; no identity is silently relabelled.
2. Resolve the desired Scenarios branch to its exact commit. In Actions →
   **Quality** → **Run workflow**, choose that branch and supply `core_commit`,
   `core_run_id`, `core_run_attempt`, `core_artifact_id`, `core_artifact_digest`.
   Confirm the workflow's actual Scenarios commit equals your selected commit.
   Empty `core_commit` retains ordinary released-host Quality.
3. Acquisition verifies the repository, approved producer files, successful exact
   attempt and applicable jobs, artifact identity/non-expiry/digest, bounded ZIP
   members, provenance sidecar, marker and API declarations before Core executes.
   A separate job has no acquisition token, publication credentials or unrelated
   secrets. It reuses Core's ZIP unchanged, builds Scenarios twice, compares bytes,
   installs the pair on disposable WordPress7.0/PHP8.5 and checks both complete
   file inventories. Existing installed synthetic-boundary proofs run in fresh
   WP-CLI and cron-context requests. Browser walkthroughs remain separately pending.
4. After the complete candidate and Quality jobs succeed, download exactly
   `candidate-pair-RUN-ATTEMPT`. Retain its GitHub artifact ID/digest too. This is
   an outer container; install the **inner plugin ZIPs**. `HANDOVER.md` and
   `candidate-pairing-record.json` identify both commits, Core run/attempt/artifact,
   ZIP SHA-256 values, actual WordPress/PHP versions and passed/pending proofs.

Scripted dispatch uses the existing Quality workflow:

```sh
gh workflow run quality.yml --repo RocketsAreNostalgic/ran-booster-scenarios \
  --ref YOUR_SCENARIOS_BRANCH \
  -f core_commit=FULL_CORE_SHA -f core_run_id=RUN_ID \
  -f core_run_attempt=ATTEMPT -f core_artifact_id=ARTIFACT_ID \
  -f core_artifact_digest=sha256:OUTER_ARTIFACT_SHA256
```

For a local build, check out the exact clean Scenarios commit and run:

```sh
python3 scripts/core_candidate.py --repository RocketsAreNostalgic/ran-booster \
  --commit FULL_CORE_SHA --run-id RUN_ID --run-attempt ATTEMPT \
  --artifact-id ARTIFACT_ID --artifact-digest sha256:OUTER_ARTIFACT_SHA256 \
  --output-dir build/core-candidate
# Any read-only acquisition credential must not enter candidate execution.
unset GH_TOKEN
python3 scripts/build-candidate.py --core-dir build/core-candidate \
  --scenarios-commit FULL_SCENARIOS_SHA --output-dir build/candidate
```

Use new output directories for each pair. Do not rebuild Core or substitute a
branch archive for its qualified runtime ZIP. The candidate builder generates
`compatibility.json` and `candidate-pairing.json` inside build output; it never
rewrites the released source manifest or Release Please versions. Each installed
candidate has one fixed pairing, no selector or ignore-compatibility switch.
Candidate outputs use distinct filenames and contain no Profile B promotion
manifest. Ordinary PR/released-host Quality remains required for source changes.

## Install and verify

Use a new disposable site or the local acceptance owner's guarded replacement
procedure. The automated installer refuses existing candidate directories or
active plugins. Do not overwrite a shared site's database or genuine packages.
Set these constants before either candidate loads:

```php
define( 'WP_ENVIRONMENT_TYPE', 'local' ); // development also supported.
define( 'RAN_BOOSTER_SCENARIOS_DISPOSABLE', true );
define( 'RAN_BOOSTER_SELF_UPDATE_MODE', 'disabled' );
```

Verify the two inner ZIPs against the pairing record, install them without
activation, then verify installed files against the external pairing metadata:

```sh
python3 scripts/build-candidate.py --verify-installed \
  --pairing /path/to/download/candidate-pairing.json \
  --core-install /path/to/wp-content/plugins/ran-booster \
  --scenarios-install /path/to/wp-content/plugins/ran-booster-scenarios
```

This compares complete regular-file inventories and SHA-256 hashes, rejecting
missing/extra files and symlinks. Runtime admission repeats candidate file checks
without a persistent success cache, plus exact marker/version/API and
disposable/environment/self-update checks. Browser, cron and WP-CLI use the same
packaged pairing and persisted scenario selection.

Activate and open **Tools → Booster scenarios**: verify **Unreleased candidate**,
expected/detected Core identities, full details and a clear guard. A detected
marker is a declaration, not publication or installed-byte proof on its own.
File comparison does not prove ZIP-container equality, executing opcode identity
or safety against concurrent writes. Keep the site quiescent and restart PHP or
clear opcache after replacing plugin files. Metadata is not a signature: trust
comes from the reviewed CI chain and verified external downloads. An actor able
to rewrite PHP and all metadata is outside this disposable guard's threat model.

## Example local-agent handover

> Install this Core ZIP: `ran-booster-candidate-CORE_SHORT.zip`, with the exact
> SHA-256 from this run's record.
>
> Install this Scenarios ZIP:
> `ran-booster-scenarios-candidate-SCENARIOS_SHORT-core-CORE_SHORT.zip`, verifying
> its SHA-256 in the same record.
>
> Verify this pairing: run installed-file verification, then confirm expected and
> detected identities on Tools → Booster scenarios on disposable PHP8.5.
>
> Inspect these scenarios: list the agreed IDs from docs/scenarios.md; record
> setup → real screen → action → expected/actual result → cleanup for each target
> and fresh request, using real Core forms and authorization.
>
> Record results against these exact identities: both full commits, Core
> run/attempt/artifact, pairing run/artifact, WordPress/PHP versions, screenshots,
> executed/pending steps, refusals and findings. Never transfer a pass to a changed pair.

Blueprint, Migrator, replacement and recovery journeys remain separately owned
in [#6](https://github.com/RocketsAreNostalgic/ran-booster-scenarios/issues/6).
This infrastructure does not implement those scenarios, bundle private upstream
archives, bypass protections or complete their acceptance.

## Expiry, mismatch and cleanup

Missing/expired artifacts fail clearly. No latest-main, other-attempt or released
fallback occurs. Ask the Core owner for a newly qualified exact build and generate
a new pair. Any changed source commit/artifact requires new pairing qualification;
previous evidence remains attached to its original tuple.

On mismatch, leave simulation disabled and inspect the reason. Verify files and
reinstall the correct vetted ZIPs or generate a new pairing. Do not edit markers
or add bypasses. Reset selection removes only Scenarios' option, never Core
history, package records or locks. Follow ordinary target cleanup before
deactivation. To return to published use, remove candidate plugin directories
through the site's guarded process and install the exact supported immutable
released pair. This also removes candidate-only metadata. Review self-update
configuration explicitly before restoring the site's normal update mode.

Candidate walkthroughs do not replace final published-artifact checks or
establish live-provider, credential-transfer, security or concurrency acceptance.

Connected fixtures are qualified only when the installed Core exposes their
required V2/API4 features. Other otherwise valid fixed candidate pairs retain
their ordinary proofs and show those journeys unavailable; the pairing record
marks connected proofs explicitly not applicable, never passed. The connected
proof checks inert archive validation, HTTP target/stage boundaries and rendered
controls, while actual browser transitions and satellite qualification stay pending.
