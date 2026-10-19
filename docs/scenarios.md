# Scenario inventory

Use the exact supported released pair for published checks, or follow the fixed
[candidate pairing workflow](candidate-acceptance.md) for unreleased inspection.
Record both commits/artifact identities and actual installed versions with every
result. Connected Blueprint, Migrator, replacement and recovery recipes now appear on
the control page: BP-01–06, MIG-01–02, REP-01–04 and REC-01–07. Follow
[connected walkthroughs](connected-walkthroughs.md) for their real routes, fixed
Blueprint downloads, permissions, setup/action/reset and manual prerequisites.
Selecting a recipe does not execute or certify a journey. Scenarios#6 stays open
for owner-dependent fault fixtures and actual browser evidence.

## Scenario and test matrix

Each applicable scenario runs for both plugins and themes, with direct links to onboarding, managed package/Automatic controls, provider management, Activity, Troubleshooting and indexed documentation as appropriate. Parameterized or workflow cases must list explicit setup/action/expected result; they must not be presented as one-click proof.

| ID | Scenario | Expected result |
| --- | --- | --- |
| REL-01 | Immutable qualifying release | Normal metadata and eligibility; immutability alone never certifies all other gates. |
| REL-02 | Mutable release | Automatic install unavailable for that candidate; qualifying manual behavior and saved preference unchanged. |
| REL-03 | No release / no candidate | Honest empty result, no invented successful check or ready state. |
| REL-04 | Denied / missing access | Safe failure and bounded reason; no secret disclosure or elevated settings probe. |
| REL-05 | Rate-limited response | Bounded rate-limit outcome; no false readiness. |
| REL-06 | Unavailable / network failure | Unknown/unavailable; old success cannot revive current verification. |
| REL-07 | Malformed / ambiguous metadata | Rejected or unknown; no guessed release identity. |
| REL-08 | Mutable → immutable | Explicit phase advance; qualifying candidate can resume Automatic and obsolete notice clears when implemented. Manual remains Manual. |
| REL-09 | Offer disappears after successful check | History remains dated after transient offer disappears; current readiness follows current evidence. |
| REL-10 | Immutable → mutable / changed release | Fresh result and invalidation; historical immutable evidence does not authorize new candidate. |
| REL-11 | Stale observation / source or credential change | Historical result remains history; exact source/access/lifetime prevents current rebinding. |
| HIS-01 | Cron discovery → later HTTP request | Real database persists outcome for plugin/theme and later admin page reads it. |
| HIS-02 | Explicit refresh → redirect → later GET | Outcome survives redirect; rendering makes no remote calls. |
| HIS-03 | Diagnostic logging disabled | Shared history still works; logging and admin authentication are distinct. |
| HIS-04 | Cached/skipped discovery | No fabricated fresh observation. |
| HIS-05 | Late completion / re-adoption | Old management lifetime never becomes replacement's current observation. |
| HIS-06 | Pagination / timestamp ties / filters | Stable bounded pages, no duplicate or missing entries, protected observations retained. |
| UI-01 | Onboarding pre-confirmation | Correct plugin/theme route, candidate-specific evidence, no install on mere navigation. |
| UI-02 | Automatic controls and later checks | Truthful notices and reachable guidance; unchanged preference and eligibility. |
| UI-03 | Documentation and failure links | Existing indexed anchors work, localized/escaped text, graceful provider absence. |
| UI-04 | Non-starter repository / Branch mode | Relevant release notices on non-starter targets; branch/push behavior unaffected. |
| SET-01 | Setup with unreadable/incomplete settings | Proposal remains possible where permitted; no settings attestation gate; not a readiness claim. |
| SET-02 | Unsupported layout / existing edits / stale preview | Safe refusal or manual guidance; never overwrite maintained automation. |
| SET-03 | Safe setup PR readback and actual generated release | Actual disposable repositories required; synthetic responses alone cannot certify #81. |
| SEC-01 | No Core / wrong version / API / production site / no flag | Visible disabled fixture, no applied simulation. |
| SEC-02 | Unauthorized user / CSRF / unknown scenario/route | Mutation rejected, no arbitrary redirects or code execution. |
| SEC-03 | Scope isolation | Only exact synthetic namespace intercepted; unrelated traffic unchanged; unknown fixture endpoints/methods blocked. |
| SEC-04 | Cross-request and reset | Choice persists to cron/fresh processes; reset only fixture state; mismatch does not leave active hooks. |
| PKG-01 | Versioned artifact | Manifest/header/support contract agree; deterministic ZIP/checksum; no Core sources, dependencies, credentials or test plugin in production ZIP. |


These are acceptance requirements. A response recipe does not complete an installed/operator test. Tests cover both plugin and theme targets; missing Core UI remains an upstream issue.
