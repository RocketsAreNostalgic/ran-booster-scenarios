<?php
/**
 * Reviewed scenario and navigation definitions, extended only through source changes.
 *
 * @package RAN_Booster_Scenarios
 */

/**
 * List reproducible scenarios and their manual transition stages.
 *
 * @since 0.1.0
 * @return array<string, array{label: string, initial: string, next: string, expected: string}> Reviewed scenario definitions.
 */
function ran_booster_scenarios_catalog(): array {
	$catalog = array(
		'immutable'         => array(
			'label'    => __( 'Immutable release', 'ran-booster-scenarios' ),
			'initial'  => 'immutable',
			'next'     => '',
			'expected' => __( 'A valid immutable 2.0.0 release is available; normal Core eligibility and installation checks still apply.', 'ran-booster-scenarios' ),
		),
		'mutable'           => array(
			'label'    => __( 'Mutable release', 'ran-booster-scenarios' ),
			'initial'  => 'mutable',
			'next'     => '',
			'expected' => __( 'Release evidence is mutable; record the supported Core version’s actual behavior. Planned #85 immutability notices may not exist yet.', 'ran-booster-scenarios' ),
		),
		'no-release'        => array(
			'label'    => __( 'No releases', 'ran-booster-scenarios' ),
			'initial'  => 'no-release',
			'next'     => '',
			'expected' => __( 'No current release is available. Existing history remains visible without inventing a new offer.', 'ran-booster-scenarios' ),
		),
		'malformed'         => array(
			'label'    => __( 'Malformed release response', 'ran-booster-scenarios' ),
			'initial'  => 'malformed',
			'next'     => '',
			'expected' => __( 'Invalid JSON must produce an unavailable/error result, never a successful release.', 'ran-booster-scenarios' ),
		),
		'unavailable'       => array(
			'label'    => __( 'Provider unavailable (503)', 'ran-booster-scenarios' ),
			'initial'  => 'unavailable',
			'next'     => '',
			'expected' => __( '503 must report provider unavailability, never deployment success.', 'ran-booster-scenarios' ),
		),
		'denied'            => array(
			'label'    => __( 'Provider access denied (403)', 'ran-booster-scenarios' ),
			'initial'  => 'denied',
			'next'     => '',
			'expected' => __( '403 must report access denial, never authenticate with real credentials.', 'ran-booster-scenarios' ),
		),
		'rate-limited'      => array(
			'label'    => __( 'Provider rate limited (429)', 'ran-booster-scenarios' ),
			'initial'  => 'rate-limited',
			'next'     => '',
			'expected' => __( '429 must report rate limiting without repeated background retry loops.', 'ran-booster-scenarios' ),
		),
		'becomes-immutable' => array(
			'label'    => __( 'Mutable → immutable (manual advance)', 'ran-booster-scenarios' ),
			'initial'  => 'mutable',
			'next'     => 'immutable',
			'expected' => __( 'Stage 0 mutable; advance explicitly, refresh normally, then observe immutable evidence in a later request.', 'ran-booster-scenarios' ),
		),
		'becomes-mutable'   => array(
			'label'    => __( 'Immutable → mutable (manual advance)', 'ran-booster-scenarios' ),
			'initial'  => 'immutable',
			'next'     => 'mutable',
			'expected' => __( 'Stage 0 immutable; advance and refresh to verify current mutable evidence replaces stale immutable evidence. Planned #85 notices may not exist yet.', 'ran-booster-scenarios' ),
		),
		'offer-disappears'  => array(
			'label'    => __( 'Immutable offer → no release (manual advance)', 'ran-booster-scenarios' ),
			'initial'  => 'immutable',
			'next'     => 'no-release',
			'expected' => __( 'Stage 0 valid offer; advance explicitly, refresh normally, then verify the offer disappears while earlier history remains.', 'ran-booster-scenarios' ),
		),
	);
	foreach ( ran_booster_scenarios_journeys() as $key => $journey ) {
		$catalog[ $key ] = array(
			'label'    => $journey['id'] . ' · ' . $journey['label'],
			'initial'  => $journey['initial'],
			'next'     => $journey['next'],
			'expected' => $journey['expected'],
		);
	}
	return $catalog;
}

/**
 * List fixed read-only administrator routes used during acceptance.
 *
 * @since 0.1.0
 * @return array<string, string> Translated labels mapped to allowlisted relative admin URLs.
 */
function ran_booster_scenarios_routes(): array {
	return array(
		__( 'Fixture plugin management', 'ran-booster-scenarios' ) => 'admin.php?page=ran-booster-plugins&package=scenario-plugin%2Fscenario-plugin.php',
		__( 'Fixture theme management', 'ran-booster-scenarios' ) => 'admin.php?page=ran-booster-themes&package=scenario-theme',
		__( 'Activity history', 'ran-booster-scenarios' )  => 'admin.php?page=ran-booster&tab=troubleshooting&panel=activity',
		__( 'Published release guidance', 'ran-booster-scenarios' ) => 'admin.php?page=ran-booster&tab=documentation#ran-booster-documentation-published-releases',
		__( 'Booster overview', 'ran-booster-scenarios' )  => 'admin.php?page=ran-booster&tab=overview',
		__( 'GitHub repositories', 'ran-booster-scenarios' ) => 'admin.php?page=ran-booster&tab=gh',
		__( 'Add plugin repository', 'ran-booster-scenarios' ) => 'admin.php?page=ran-booster-plugins-create',
		__( 'Add theme repository', 'ran-booster-scenarios' ) => 'admin.php?page=ran-booster-themes-create',
		__( 'Booster troubleshooting', 'ran-booster-scenarios' ) => 'admin.php?page=ran-booster&tab=troubleshooting',
		__( 'Installed plugins', 'ran-booster-scenarios' ) => 'plugins.php',
		__( 'Installed themes', 'ran-booster-scenarios' )  => 'themes.php',
		__( 'WordPress updates', 'ran-booster-scenarios' ) => 'update-core.php',
	);
}
