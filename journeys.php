<?php
/**
 * Connected feature walkthroughs using real Core screens and explicit prerequisites.
 *
 * @package RAN_Booster_Scenarios
 */

/**
 * Define bounded journeys; selecting one changes only synthetic HTTP responses.
 *
 * @return array<string, array<string, string>> Fixed instructions and response modes.
 */
function ran_booster_scenarios_journeys(): array {
	return array(
		'blueprint-archive'       => array(
			'id'           => 'BP-01',
			'feature'      => 'blueprint',
			'initial'      => 'immutable',
			'next'         => '',
			'label'        => __( 'Blueprint export and import', 'ran-booster-scenarios' ),
			'setup'        => __( 'Use the exact candidate pair. Link the harmless plugin/theme through normal Booster controls. Export selected packages; separately download a bundled Blueprint fixture below.', 'ran-booster-scenarios' ),
			'action'       => __( 'On Transporter, export without credentials, upload the archive and Preview. For password cases, manually export a synthetic credential with explicit consent and try correct and wrong passwords; also upload the malformed fixture.', 'ran-booster-scenarios' ),
			'expected'     => __( 'Real validation and messages appear. Invalid archives/passwords disclose no credentials and apply nothing. A preview is not an installation or acceptance pass.', 'ran-booster-scenarios' ),
			'reset'        => __( 'Discard downloaded credential archives securely. Reset fixture selection; use normal Stop managing/uninstall controls only for unchanged disposable targets.', 'ran-booster-scenarios' ),
			'prerequisite' => __( 'Plugin and theme. Administrator; export/import consent. Password/credential cases require a manually created synthetic export, never production secrets.', 'ran-booster-scenarios' ),
		),
		'blueprint-comparison'    => array(
			'id'           => 'BP-02',
			'feature'      => 'blueprint',
			'initial'      => 'immutable',
			'next'         => '',
			'label'        => __( 'Blueprint destination comparison', 'ran-booster-scenarios' ),
			'setup'        => __( 'Use branch plugin/theme/mixed fixtures with targets absent, installed unmanaged, or normally linked with matching/different configuration in separate trials.', 'ran-booster-scenarios' ),
			'action'       => __( 'Upload and Preview on Transporter. Inspect configuration and installed-equality columns; repeat with unsupported-release and unsupported-version archives.', 'ran-booster-scenarios' ),
			'expected'     => __( 'Configuration matches are distinct from artifact reproduction. Missing evidence stays unknown/not checked. Unsupported selection/capability refuses the affected operation.', 'ran-booster-scenarios' ),
			'reset'        => __( 'Close the preview. Restore only disposable targets through ordinary explicit controls; retain genuine history and shared credentials.', 'ran-booster-scenarios' ),
			'prerequisite' => __( 'Plugin and theme. Administrator plus the real per-target install/update capability. Installed/configuration variants require supported manual setup.', 'ran-booster-scenarios' ),
		),
		'blueprint-partial'       => array(
			'id'           => 'BP-03',
			'feature'      => 'blueprint',
			'initial'      => 'partial-denied',
			'next'         => 'partial-missing',
			'label'        => __( 'Blueprint partial preview: 403 → 404', 'ran-booster-scenarios' ),
			'setup'        => __( 'Download the mixed selected-commit fixture. Both targets must be absent. This recipe denies only the theme repository while the plugin remains available.', 'ran-booster-scenarios' ),
			'action'       => __( 'Upload and Preview. Advance explicitly to stage 1 and Preview afresh for theme 404. Separately select the existing rate-limit/unavailable recipes and repeat.', 'ran-booster-scenarios' ),
			'expected'     => __( 'Useful partial/not-checked results without false readiness, cross-use of credentials or unbounded retries. Record actual calls as bounded-work evidence only.', 'ran-booster-scenarios' ),
			'reset'        => __( 'Reset selection; no preview cleanup may delete Core records, history, credentials or locks.', 'ran-booster-scenarios' ),
			'prerequisite' => __( 'Both targets selected. Administrator. Mixed-provider, repeated-repository and saved/temporary credential variants require separate reviewed manual input; this fixed recipe does not certify them.', 'ran-booster-scenarios' ),
		),
		'blueprint-consent'       => array(
			'id'           => 'BP-04',
			'feature'      => 'blueprint',
			'initial'      => 'immutable',
			'next'         => '',
			'label'        => __( 'Blueprint credential and adoption consent', 'ran-booster-scenarios' ),
			'setup'        => __( 'Manually export synthetic credentials through Core, or create a compatible synthetic saved profile. Never use production credentials.', 'ran-booster-scenarios' ),
			'action'       => __( 'Compare leaving credentials unchanged, choosing an existing credential and explicitly importing transferred material. Withhold adoption consent, then grant it through the real form.', 'ran-booster-scenarios' ),
			'expected'     => __( 'Missing consent causes no persistence/action. An explicit import binds only the selected credential. Inspect labels, explanation and keyboard focus without exposing secret values.', 'ran-booster-scenarios' ),
			'reset'        => __( 'Remove only unchanged test-owned credentials through normal controls after their targets are unlinked. Keep all unrelated profiles and history.', 'ran-booster-scenarios' ),
			'prerequisite' => __( 'Plugin and theme. Manual synthetic credential setup and explicit permission for that disposable-site mutation.', 'ran-booster-scenarios' ),
		),
		'blueprint-stale'         => array(
			'id'           => 'BP-05',
			'feature'      => 'replacement',
			'initial'      => 'immutable',
			'next'         => '',
			'label'        => __( 'Blueprint stale provider review', 'ran-booster-scenarios' ),
			'setup'        => __( 'Use a separately verified replacement fixture and save a real Blueprint review. Provider selection changes require the local acceptance owner.', 'ran-booster-scenarios' ),
			'action'       => __( 'Change provider selection through Core, submit the old review, then Preview afresh and explicitly Apply the new review.', 'ran-booster-scenarios' ),
			'expected'     => __( 'The stale review refuses before credential disclosure/persistence, provider work or package mutation. Fresh Apply uses current authorization and checks.', 'ran-booster-scenarios' ),
			'reset'        => __( 'Return provider selection through its explicit supported consent flow. Do not overwrite selection options or replay old queues.', 'ran-booster-scenarios' ),
			'prerequisite' => __( 'Plugin and theme. Administrator/activate_plugins. Reviewed replacement artifact and authorized global selection change are manual prerequisites.', 'ran-booster-scenarios' ),
		),
		'blueprint-exact'         => array(
			'id'           => 'BP-06',
			'feature'      => 'blueprint',
			'initial'      => 'historical-commit',
			'next'         => 'wrong-commit',
			'label'        => __( 'Initial exact-commit install and refusal', 'ran-booster-scenarios' ),
			'setup'        => __( 'The selected plugin/theme targets must be absent. Use a selected-commit Blueprint: its a…a commit differs from the recipe’s b…b tracking-branch head.', 'ran-booster-scenarios' ),
			'action'       => __( 'Preview and explicitly Apply through Transporter; inspect the installed target in a fresh request. In a separate clean trial advance to wrong resolved commit and retry.', 'ran-booster-scenarios' ),
			'expected'     => __( 'Supported initial installation uses the selected commit and intended package identity, stays inactive and is managed Disabled. Wrong resolution refuses. Initial selection is not a permanent update lock; existing-target rollback/selected-release execution is not implied.', 'ran-booster-scenarios' ),
			'reset'        => __( 'Use normal Stop managing and WordPress uninstall only for the exact unchanged disposable target. Never erase protection or in-flight work to reset.', 'ran-booster-scenarios' ),
			'prerequisite' => __( 'Plugin and theme, one absent target at a time. Administrator and real installation capability. Use BP-02 for existing-target adoption, which preserves installed bytes.', 'ran-booster-scenarios' ),
		),
		'migrator-eligibility'    => array(
			'id'           => 'MIG-01',
			'feature'      => 'migrator',
			'initial'      => 'immutable',
			'next'         => '',
			'label'        => __( 'Migrator eligibility and retained-row review', 'ran-booster-scenarios' ),
			'setup'        => __( 'Use the separately verified Migrator source80 ZIP and helper81 shell wrapper documented in connected-walkthroughs.md. Supply the digest-verified official WP Pusher archive privately; keep WP Pusher inactive.', 'ran-booster-scenarios' ),
			'action'       => __( 'Run wrapper setup/seed/readback on its marked disposable site, then open Transporter and review the actual retained row. Coordinate active-source/unsupported-schema cases separately.', 'ran-booster-scenarios' ),
			'expected'     => __( 'Actual Migrator eligibility and honest reasons. A version/database declaration alone is insufficient. Retained-row fixtures do not prove WP Pusher activation-created data.', 'ran-booster-scenarios' ),
			'reset'        => __( 'Use only the wrapper cleanup phase with its original exact archives and unchanged fixture fingerprint. It never drops tables, uninstalls plugins or changes Core records.', 'ran-booster-scenarios' ),
			'prerequisite' => __( 'Reviewed helper covers one public plugin row only; its target/namespace differs from the two HTTP recipes. Theme/private/schema cases need owner-coordinated prerequisites and network isolation. Exact satellite files must be verified externally.', 'ran-booster-scenarios' ),
		),
		'migrator-apply'          => array(
			'id'           => 'MIG-02',
			'feature'      => 'migrator',
			'initial'      => 'immutable',
			'next'         => '',
			'label'        => __( 'Migrator stale/fresh Apply and exact cleanup', 'ran-booster-scenarios' ),
			'setup'        => __( 'Complete MIG-01 with the qualified satellite tuple. Install its harmless target through the reviewed consumer setup, block unrelated network work, and retain the real source fingerprint.', 'ran-booster-scenarios' ),
			'action'       => __( 'Review, then change provider selection or the fixture-owned source through an owner-approved procedure. Submit the old review; review afresh and explicitly Apply. Inspect a fresh request.', 'ran-booster-scenarios' ),
			'expected'     => __( 'Stale review preserves source/destination state. Successful target-verified adoption is Disabled and removes only the exact unchanged source row. Webhook/uninstall responsibilities remain explicit.', 'ran-booster-scenarios' ),
			'reset'        => __( 'Run wrapper readback/cleanup; changed rows refuse. Remove Core management through real controls if needed, without deleting genuine records/history or upstream plugins as a shortcut.', 'ran-booster-scenarios' ),
			'prerequisite' => __( 'Public plugin helper only. Theme, active-source, credential and source-change variants remain manual/owner-coordinated; no current-Core private proof is inferred.', 'ran-booster-scenarios' ),
		),
		'replacement-discovery'   => array(
			'id'           => 'REP-01',
			'feature'      => 'replacement',
			'initial'      => 'immutable',
			'next'         => '',
			'label'        => __( 'Replacement discovery and explicit adoption', 'ran-booster-scenarios' ),
			'setup'        => __( 'Start with bundled GitHub. Separately install one reviewed eligible replacement fixture; retain its exact artifact identity.', 'ran-booster-scenarios' ),
			'action'       => __( 'Activate it through WordPress, inspect GitHub pending adoption, and approve or decline using Core’s actual form.', 'ran-booster-scenarios' ),
			'expected'     => __( 'Activation alone grants no selection or credential access. Actual implementation identity, non-packaged status and explicit consent remain visible.', 'ran-booster-scenarios' ),
			'reset'        => __( 'Return explicitly to bundled through supported controls before deactivating/removing only the unchanged fixture plugin.', 'ran-booster-scenarios' ),
			'prerequisite' => __( 'Administrator/activate_plugins. Reviewed external replacement fixture; global provider changes belong to the acceptance owner, not this selector.', 'ran-booster-scenarios' ),
		),
		'replacement-consent'     => array(
			'id'           => 'REP-02',
			'feature'      => 'replacement',
			'initial'      => 'immutable',
			'next'         => '',
			'label'        => __( 'Replacement retained-credential consent', 'ran-booster-scenarios' ),
			'setup'        => __( 'Prepare compatible and incompatible synthetic profiles/constants through the approved disposable-site procedure. Preserve pre-existing credentials.', 'ran-booster-scenarios' ),
			'action'       => __( 'Inspect adoption while withholding reuse consent, then approve compatible reuse explicitly. Reload in a fresh request.', 'ran-booster-scenarios' ),
			'expected'     => __( 'No silent conversion/substitution or secret display. Actionable incompatibility guidance; reuse approval remains distinct from Blueprint credential import.', 'ran-booster-scenarios' ),
			'reset'        => __( 'Restore selection explicitly; remove only unchanged test-owned profiles through real controls after their consumers are detached.', 'ran-booster-scenarios' ),
			'prerequisite' => __( 'Manual synthetic credential setup, reviewed replacement artifact and administrator/activate_plugins. No real credential material.', 'ran-booster-scenarios' ),
		),
		'replacement-unavailable' => array(
			'id'           => 'REP-03',
			'feature'      => 'replacement',
			'initial'      => 'immutable',
			'next'         => '',
			'label'        => __( 'Replacement unavailable, conflict and stale form', 'ran-booster-scenarios' ),
			'setup'        => __( 'Use separately reviewed replacement fixtures and genuine supported selection/adoption. Keep a real adoption form open for the stale trial.', 'ran-booster-scenarios' ),
			'action'       => __( 'Deactivate the selected fixture; separately test its reviewed incompatible/multiple-candidate configurations and submit the older form.', 'ran-booster-scenarios' ),
			'expected'     => __( 'Visible unavailable/conflict and specific safe refusal. No bundled fallback, credential disclosure or package mutation under unsupported authority.', 'ran-booster-scenarios' ),
			'reset'        => __( 'Restore the exact fixture or explicitly select bundled using current Core consent. Never edit provider options to manufacture availability.', 'ran-booster-scenarios' ),
			'prerequisite' => __( 'Manual reviewed external fixture variants and administrator/activate_plugins. Selecting this recipe does not create a conflict.', 'ran-booster-scenarios' ),
		),
		'replacement-switch'      => array(
			'id'           => 'REP-04',
			'feature'      => 'replacement',
			'initial'      => 'immutable',
			'next'         => '',
			'label'        => __( 'Replacement switching and return to bundled', 'ran-booster-scenarios' ),
			'setup'        => __( 'Use reviewed A/B replacements with genuinely managed disposable targets and an actual reviewed/queued operation. Qualify Bitbucket separately if testing independence.', 'ran-booster-scenarios' ),
			'action'       => __( 'Switch A → B and explicitly return to bundled through Core. Inspect current status, stale Blueprint/Migrator forms and a fresh request.', 'ran-booster-scenarios' ),
			'expected'     => __( 'Package identity remains intact. Old work cannot deploy under new authority; no automatic queue replay is promised. Independently qualified Bitbucket remains separate.', 'ran-booster-scenarios' ),
			'reset'        => __( 'Restore approved selection through supported consent and allow real in-flight work to reach a safe outcome before removing fixtures.', 'ran-booster-scenarios' ),
			'prerequisite' => __( 'Administrator/activate_plugins; authorized provider transitions and real queued state. This recipe never inserts queue/history records.', 'ran-booster-scenarios' ),
		),
		'recovery-healthy'        => array(
			'id'           => 'REC-01',
			'feature'      => 'recovery',
			'initial'      => 'immutable',
			'next'         => '',
			'label'        => __( 'Healthy package-list recovery absence', 'ran-booster-scenarios' ),
			'setup'        => __( 'Use normal setup for harmless managed plugin/theme targets; compare empty, populated and filtered lists. Unavailable-provider state needs a supported owner-approved transition.', 'ran-booster-scenarios' ),
			'action'       => __( 'Open managed lists and ordinary package settings at desktop/narrow widths and with keyboard navigation.', 'ran-booster-scenarios' ),
			'expected'     => __( 'No routine Recover package management/removal invitation. Missing provider is not damaged settings. Deliberate Stop managing remains distinct from deleting installed files.', 'ran-booster-scenarios' ),
			'reset'        => __( 'Reset selection; only ordinary explicit cleanup of unchanged disposable targets, with genuine settings/history preserved.', 'ran-booster-scenarios' ),
			'prerequisite' => __( 'Plugin and theme. Administrator plus update_plugins/update_themes for the applicable real action.', 'ran-booster-scenarios' ),
		),
		'recovery-damaged'        => array(
			'id'           => 'REC-02',
			'feature'      => 'recovery',
			'initial'      => 'immutable',
			'next'         => '',
			'label'        => __( 'Damaged settings and contextual removal review', 'ran-booster-scenarios' ),
			'setup'        => __( 'Requires the recovery owner’s approved fault/restore procedure for one exact fixture-owned management record, with full preimage, token and installed fingerprint. No fault mutator is provided here.', 'ran-booster-scenarios' ),
			'action'       => __( 'Open the actual affected list/detail route and follow its contextual Review management removal. Separately try malformed/empty/array/oversize navigation without changing records.', 'ran-booster-scenarios' ),
			'expected'     => __( 'Honest settings-read failure, no misleading empty-state onboarding, and no diagnosis/removal invitation merely from bad navigation. Opening review changes nothing.', 'ran-booster-scenarios' ),
			'reset'        => __( 'Restore only an unchanged owned fault postimage using the approved conditional procedure. Refuse changed/protected/in-flight targets; never restore global snapshots or remove locks.', 'ran-booster-scenarios' ),
			'prerequisite' => __( 'Plugin and theme. Manual owner-approved fault custody remains required; this selector does not corrupt settings.', 'ran-booster-scenarios' ),
		),
		'recovery-review'         => array(
			'id'           => 'REC-03',
			'feature'      => 'recovery',
			'initial'      => 'immutable',
			'next'         => '',
			'label'        => __( 'Emergency Stop managing review', 'ran-booster-scenarios' ),
			'setup'        => __( 'Install and normally link the harmless exact target. Use the fixed emergency review route below; damaged-inventory trials additionally require REC-02 custody.', 'ran-booster-scenarios' ),
			'action'       => __( 'Open the pre-hydration plugin/theme removal review. Inspect retained-files/data/activation/history/credentials explanation, required confirmation and keyboard labels.', 'ran-booster-scenarios' ),
			'expected'     => __( 'Stop managing an installed package names the exact target/lifetime. Opening/reviewing makes no change; nonce and real row/installed binding remain Core-owned.', 'ran-booster-scenarios' ),
			'reset'        => __( 'Leave without confirming, then verify original state in a fresh request. No state-reset operation is needed for navigation.', 'ran-booster-scenarios' ),
			'prerequisite' => __( 'Plugin and theme; real update_plugins/update_themes capability. Recovery availability requires current eligible state, never navigation alone.', 'ran-booster-scenarios' ),
		),
		'recovery-stale'          => array(
			'id'           => 'REC-04',
			'feature'      => 'recovery',
			'initial'      => 'immutable',
			'next'         => '',
			'label'        => __( 'Refused or stale management removal', 'ran-booster-scenarios' ),
			'setup'        => __( 'Open a real removal review for an exact fixture-owned target. Record identity/preimage before an owner-approved installed-file or record change.', 'ran-booster-scenarios' ),
			'action'       => __( 'Submit the stale review. Separately inspect approved invalid-token/source/ambiguous identity, unavailable-storage and real queued/running/unresolved-work cases.', 'ran-booster-scenarios' ),
			'expected'     => __( 'Current safe refusal and useful next action, no silent removal or identity repair, and no generic SQL/schema-error invitation. Fresh review is required after a snapshot change.', 'ran-booster-scenarios' ),
			'reset'        => __( 'Restore only unchanged owned postimages under approved custody; leave protection/work state to actual Core recovery. Never delete processes/locks or real records.', 'ran-booster-scenarios' ),
			'prerequisite' => __( 'Plugin and theme. Manual owner-approved failure prerequisites; selector does not fabricate database, queue or storage failures.', 'ran-booster-scenarios' ),
		),
		'recovery-remove'         => array(
			'id'           => 'REC-05',
			'feature'      => 'recovery',
			'initial'      => 'immutable',
			'next'         => '',
			'label'        => __( 'Management-only removal and optional re-link', 'ran-booster-scenarios' ),
			'setup'        => __( 'Normally link an exact harmless installed target. Capture its files/activation/data, management lifetime and unrelated state before opening the real removal review.', 'ran-booster-scenarios' ),
			'action'       => __( 'Explicitly confirm Stop managing through Core. Inspect authoritative fresh state; optionally follow Link the package again through ordinary validated adoption.', 'ran-booster-scenarios' ),
			'expected'     => __( 'Only the confirmed management record is removed. Files, activation, data, shared credentials, genuine history and unrelated records remain. Re-link creates a new lifetime under normal policy/consent, not repair/rollback/restoration.', 'ran-booster-scenarios' ),
			'reset'        => __( 'Use ordinary explicit cleanup only for unchanged owned targets. Do not directly unlink SQL or re-adopt behind the action under test.', 'ran-booster-scenarios' ),
			'prerequisite' => __( 'Plugin and theme. Explicit action consent plus real update_plugins/update_themes capability and eligible unprotected target.', 'ran-booster-scenarios' ),
		),
		'recovery-native'         => array(
			'id'           => 'REC-06',
			'feature'      => 'replacement',
			'initial'      => 'immutable',
			'next'         => '',
			'label'        => __( 'Interrupted native installation recovery', 'ran-booster-scenarios' ),
			'setup'        => __( 'Requires a reviewed controlled interruption during an actual disposable-target native install, with exclusive owner custody. No marker, lock or uncertain outcome is seeded here.', 'ran-booster-scenarios' ),
			'action'       => __( 'Open GitHub’s real protected recovery screen. Inspect stopped-process/target-removal confirmations and occupied-lock refusal; perform only supported readback/clear-block/retry steps.', 'ran-booster-scenarios' ),
			'expected'     => __( 'Uncertainty is never successful adoption. Protection remains until authoritative checks permit completion. This differs from management-only removal and does not promise files are retained.', 'ran-booster-scenarios' ),
			'reset'        => __( 'Complete real Core recovery under the owner’s procedure. Never seed/erase markers, delete locks or invoke a parallel recovery service.', 'ran-booster-scenarios' ),
			'prerequisite' => __( 'Plugin/theme where supported. Administrator/activate_plugins and relevant installation capability. Manual real interruption required; pending until actually executed.', 'ran-booster-scenarios' ),
		),
		'recovery-activity'       => array(
			'id'           => 'REC-07',
			'feature'      => 'recovery',
			'initial'      => 'immutable',
			'next'         => '',
			'label'        => __( 'Deployment needs attention and reconciliation', 'ran-booster-scenarios' ),
			'setup'        => __( 'Requires a real bounded uncertain/stopped-worker deployment to the harmless target. Retain the actual attempt/correlation identity; no Activity row is inserted by this fixture.', 'ran-booster-scenarios' ),
			'action'       => __( 'Open Activity, select the genuine attempt and inspect reconciliation controls, stopped-process confirmation and current-state verification.', 'ran-booster-scenarios' ),
			'expected'     => __( 'Genuine protection/history and supported authoritative reconciliation. A navigation link or screenshot alone is not a reconciliation pass.', 'ran-booster-scenarios' ),
			'reset'        => __( 'Use real supported reconciliation and owner-controlled cleanup. Preserve journal history, retention, locks and unrelated records.', 'ran-booster-scenarios' ),
			'prerequisite' => __( 'Plugin and theme where supported. Manual real attempt required; do not fabricate an attempt ID or correlation reference.', 'ran-booster-scenarios' ),
		),
	);
}

/**
 * Check feature availability without constructing providers or reading credentials.
 *
 * @param string $key Fixed scenario key.
 * @return string Empty when the additional feature exists; otherwise a useful reason.
 */
function ran_booster_scenarios_journey_guard( string $key ): string {
	$journey = ran_booster_scenarios_journeys()[ $key ] ?? null;
	if ( null === $journey ) {
		return '';
	}
	$version = 'RAN\\Portability\\PackageBlueprint::VERSION';
	$api     = 'RAN\\AddOn\\Portability\\PortabilityFacade::API_VERSION';
	if ( ! defined( $version ) || 2 !== constant( $version ) || ! defined( $api ) || 4 !== constant( $api ) ) {
		return __( 'This connected journey needs the feature-bearing Core with Blueprint V2 and Portability API 4. Use a newly qualified exact candidate pair or a reviewed compatible release.', 'ran-booster-scenarios' );
	}
	if ( in_array( $journey['feature'], array( 'replacement', 'recovery', 'migrator' ), true ) && ! class_exists( 'RAN\\RepositoryProvider\\GitHubProviderSelection' ) ) {
		return __( 'The paired Core does not expose the required provider-selection capability.', 'ran-booster-scenarios' );
	}
	if ( 'recovery' === $journey['feature'] && ( ! class_exists( 'RAN\\PackageRemoval\\PackageRemovalService' ) || ! in_array( 'recovery_confirmation', get_class_methods( 'RAN\\PackageRemoval\\PackageRemovalService' ), true ) ) ) {
		return __( 'The paired Core does not expose management-removal review.', 'ran-booster-scenarios' );
	}
	if ( 'migrator' === $journey['feature'] && ( ! interface_exists( 'RAN\\Admin\\Interaction\\TransporterRowAdminInteractionFacade' ) || false === has_action( 'admin_post_ran_booster_wp_pusher_migrator_package' ) ) ) {
		return __( 'The required Migrator composition is unavailable. Install and externally verify the qualified Migrator artifact and helper; a detected handler alone is not byte-identity evidence.', 'ran-booster-scenarios' );
	}
	return '';
}

/**
 * Show real-operation instructions without claiming a scenario was executed.
 *
 * @param string $guard Current request's already evaluated pairing/selection guard.
 * @return void
 */
function ran_booster_scenarios_render_journeys( string $guard ): void {
	echo '<h2>' . esc_html( __( 'Connected walkthroughs', 'ran-booster-scenarios' ) ) . '</h2><p>' . esc_html( __( 'Selecting a recipe changes only fixture-owned selection and synthetic HTTP responses. It does not create provider conflicts, corrupt settings, seed retained rows, interrupt workers or mark acceptance passed. Follow each prerequisite and the packaged connected-walkthroughs.md guide.', 'ran-booster-scenarios' ) ) . '</p>';
	foreach ( ran_booster_scenarios_journeys() as $key => $journey ) {
		echo '<details id="ran-journey-' . esc_attr( $key ) . '"><summary>' . esc_html( $journey['id'] . ' · ' . $journey['label'] ) . '</summary>';
		$reason = ran_booster_scenarios_journey_guard( $key );
		if ( '' !== $reason ) {
			echo '<p><strong>' . esc_html( $reason ) . '</strong></p>';
		}
		foreach ( array(
			'prerequisite' => __( 'Applicability and prerequisites', 'ran-booster-scenarios' ),
			'setup'        => __( 'Setup', 'ran-booster-scenarios' ),
			'action'       => __( 'Open screen and act', 'ran-booster-scenarios' ),
			'expected'     => __( 'Expected result', 'ran-booster-scenarios' ),
			'reset'        => __( 'Reset and cleanup', 'ran-booster-scenarios' ),
		) as $field => $label ) {
			echo '<p><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $journey[ $field ] ) . '</p>';
		}
		if ( '' === $guard && '' === $reason ) {
			echo '<ul>';
			foreach ( ran_booster_scenarios_journey_routes( $key ) as $label => $route ) {
				echo '<li><a href="' . esc_url( admin_url( $route ) ) . '">' . esc_html( $label ) . '</a></li>';
			}
			echo '</ul>';
		}
		echo '</details>';
	}
	if ( '' === $guard && '' === ran_booster_scenarios_journey_guard( 'blueprint-archive' ) ) {
		echo '<h3>' . esc_html( __( 'Inert Blueprint downloads', 'ran-booster-scenarios' ) ) . '</h3><p>' . esc_html( __( 'Credential-free V2 input archives for the real Transporter upload. Unsupported/malformed files are intentional refusal trials. No archive contains provider plugins, secrets or installed-state proof.', 'ran-booster-scenarios' ) ) . '</p><ul>';
		foreach ( ran_booster_scenarios_blueprint_files() as $name ) {
			echo '<li><a href="' . esc_url( plugins_url( 'archives/' . $name, __FILE__ ) ) . '" download>' . esc_html( $name ) . '</a></li>';
		}
		echo '</ul>';
	}
}

/**
 * List only fixed generated credential-free archives, never a request-supplied path.
 *
 * @return list<string> Bundled Blueprint input filenames.
 */
function ran_booster_scenarios_blueprint_files(): array {
	return array( 'blueprint-branch-plugin.zip', 'blueprint-branch-theme.zip', 'blueprint-branch-mixed.zip', 'blueprint-selected-plugin.zip', 'blueprint-selected-theme.zip', 'blueprint-selected-mixed.zip', 'blueprint-unsupported-release.zip', 'blueprint-mixed-partial.zip', 'blueprint-malformed.zip', 'blueprint-unsupported-version.zip' );
}

/**
 * Resolve fixed read-only routes for the selected journey, not mutation endpoints.
 *
 * @param string $key Fixed scenario key.
 * @return array<string, string> Labels and allowlisted administrator URLs.
 */
function ran_booster_scenarios_journey_routes( string $key ): array {
	$journey = ran_booster_scenarios_journeys()[ $key ] ?? null;
	if ( null === $journey ) {
		return array();
	}
	if ( str_starts_with( $key, 'blueprint-' ) || str_starts_with( $key, 'migrator-' ) ) {
		return array( __( 'Open Transporter', 'ran-booster-scenarios' ) => 'admin.php?page=ran-booster&tab=portability' );
	}
	if ( str_starts_with( $key, 'replacement-' ) || 'recovery-native' === $key ) {
		return array( __( 'Open GitHub provider', 'ran-booster-scenarios' ) => 'admin.php?page=ran-booster&tab=gh' );
	}
	if ( 'recovery-activity' === $key ) {
		return array( __( 'Open Activity and choose the genuine attempt', 'ran-booster-scenarios' ) => 'admin.php?page=ran-booster&tab=troubleshooting&panel=activity' );
	}
	if ( in_array( $key, array( 'recovery-review', 'recovery-stale', 'recovery-remove' ), true ) ) {
		return array(
			__( 'Review fixture plugin management removal', 'ran-booster-scenarios' ) => 'admin.php?page=ran-booster-plugins&recovery=1&recovery_target=scenario-plugin%2Fscenario-plugin.php',
			__( 'Review fixture theme management removal', 'ran-booster-scenarios' ) => 'admin.php?page=ran-booster-themes&recovery=1&recovery_target=scenario-theme',
		);
	}
	return array(
		__( 'Managed plugins', 'ran-booster-scenarios' ) => 'admin.php?page=ran-booster-plugins',
		__( 'Managed themes', 'ran-booster-scenarios' )  => 'admin.php?page=ran-booster-themes',
	);
}
