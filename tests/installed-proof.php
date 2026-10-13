<?php
/**
 * Disposable-site proof against the exact installed release archives.
 *
 * @package RAN_Booster_Scenarios
 */

/**
 * Verify the guarded fixture boundary in the real WordPress runtime.
 *
 * @throws RuntimeException When site identity or the installed fixture contract differs.
 */
function ran_booster_scenarios_installed_proof(): void {
	if ( 'local' !== wp_get_environment_type() || ! defined( 'RAN_BOOSTER_SCENARIOS_DISPOSABLE' ) || true !== RAN_BOOSTER_SCENARIOS_DISPOSABLE || 'http://localhost' !== get_option( 'siteurl' ) ) {
		throw new RuntimeException( 'The installed proof requires its disposable CI site.' );
	}
	if ( '' !== ran_booster_scenarios_guard() || null === ran_booster_scenarios_compatibility() ) {
		throw new RuntimeException( 'The installed exact Core contract was rejected.' );
	}
	update_option(
		'ran_booster_scenarios_state',
		array(
			'scenario' => 'immutable',
			'stage'    => 0,
			'target'   => 'both',
		),
		false
	);
	try {
		foreach ( array( 'scenario-plugin', 'scenario-theme' ) as $slug ) {
			$response = wp_remote_get( 'https://api.github.com/repos/ran-booster-scenarios/' . $slug . '/releases/latest' );
			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				throw new RuntimeException( 'Controlled installed release response failed.' );
			}
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! is_array( $body ) || true !== ( $body['immutable'] ?? null ) || 'v2.0.0' !== ( $body['tag_name'] ?? null ) ) {
				throw new RuntimeException( 'Controlled release identity differs.' );
			}
		}
		if ( false !== ran_booster_scenarios_http( false, array(), 'https://example.com/unrelated' ) ) {
			throw new RuntimeException( 'Unrelated requests must pass through.' );
		}
		if ( ! is_wp_error( ran_booster_scenarios_http( false, array( 'method' => 'POST' ), 'https://api.github.com/repos/ran-booster-scenarios/scenario-plugin/releases' ) ) ) {
			throw new RuntimeException( 'Synthetic writes must remain blocked.' );
		}
	} finally {
		delete_option( 'ran_booster_scenarios_state' );
	}
}

ran_booster_scenarios_installed_proof();
