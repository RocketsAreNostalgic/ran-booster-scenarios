<?php
/**
 * Prove connected fixture boundaries on a fresh exact candidate CI installation.
 *
 * @package RAN_Booster_Scenarios
 */

/**
 * Inspect real archive validation and HTTP recipes, never execute product mutations.
 *
 * @throws RuntimeException When the exact candidate, fixtures or their boundaries differ.
 * @return void
 */
function ran_booster_scenarios_connected_installed_proof(): void {
	if ( ! current_user_can( 'manage_options' ) || 'http://localhost' !== get_option( 'siteurl' ) || 'local' !== wp_get_environment_type() || '' !== ran_booster_scenarios_guard() || '' !== ran_booster_scenarios_journey_guard( 'blueprint-archive' ) ) {
		throw new RuntimeException( 'Connected fixture proof requires the exact opted-in candidate CI site and administrator.' );
	}
	$before = get_option( 'ran_booster_scenarios_state', null );
	$root   = WP_PLUGIN_DIR . '/ran-booster-scenarios';
	try {
		foreach ( ran_booster_scenarios_blueprint_files() as $name ) {
			$negative = in_array( $name, array( 'blueprint-malformed.zip', 'blueprint-unsupported-version.zip' ), true );
			try {
				$document = ( new \RAN\Portability\BlueprintArchive() )->read_from( $root . '/archives/' . $name, null );
			} catch ( InvalidArgumentException $error ) {
				if ( $negative ) {
					continue;
				}
				throw new RuntimeException( 'A valid connected Blueprint fixture was rejected.', 0, $error );
			}
			if ( $negative || array() !== $document->credentials || count( $document->packages ) < 1 ) {
				throw new RuntimeException( 'Blueprint fixture validation or credential isolation differs.' );
			}
		}
		$state = array(
			'scenario' => 'blueprint-partial',
			'stage'    => 0,
			'target'   => 'both',
		);
		foreach ( array(
			0 => 403,
			1 => 404,
		) as $stage => $expected ) {
			$state['stage'] = $stage;
			update_option( 'ran_booster_scenarios_state', $state, false );
			$plugin = wp_remote_get( 'https://api.github.com/repos/ran-booster-scenarios/scenario-plugin' );
			$theme  = wp_remote_get( 'https://api.github.com/repos/ran-booster-scenarios/scenario-theme' );
			$by_id  = wp_remote_get( 'https://api.github.com/repositories/940002' );
			if ( 200 !== wp_remote_retrieve_response_code( $plugin ) || wp_remote_retrieve_response_code( $theme ) !== $expected || wp_remote_retrieve_response_code( $by_id ) !== $expected ) {
				throw new RuntimeException( 'Per-repository partial preview escaped its exact target/stage.' );
			}
		}
		foreach ( array(
			0 => str_repeat( 'a', 40 ),
			1 => str_repeat( 'b', 40 ),
		) as $stage => $expected ) {
			update_option(
				'ran_booster_scenarios_state',
				array(
					'scenario' => 'blueprint-exact',
					'stage'    => $stage,
					'target'   => 'plugin',
				),
				false
			);
			$selected = wp_remote_get( 'https://api.github.com/repos/ran-booster-scenarios/scenario-plugin/commits/' . str_repeat( 'a', 40 ), array( 'headers' => array( 'accept' => 'application/vnd.github.sha' ) ) );
			$tracking = wp_remote_get( 'https://api.github.com/repos/ran-booster-scenarios/scenario-plugin/commits/main', array( 'headers' => array( 'accept' => 'application/vnd.github.sha' ) ) );
			if ( wp_remote_retrieve_body( $selected ) !== $expected || str_repeat( 'b', 40 ) !== wp_remote_retrieve_body( $tracking ) ) {
				throw new RuntimeException( 'Selected-commit fixture identity or wrong-resolution phase differs.' );
			}
			if ( ! is_wp_error( ran_booster_scenarios_http( false, array(), 'https://api.github.com/repos/ran-booster-scenarios/scenario-theme' ) ) || ! is_wp_error( ran_booster_scenarios_http( false, array( 'method' => 'POST' ), 'https://api.github.com/repos/ran-booster-scenarios/scenario-plugin' ) ) ) {
				throw new RuntimeException( 'Connected fixture target or read-only HTTP isolation failed.' );
			}
		}
		ob_start();
		ran_booster_scenarios_page();
		$html = ob_get_clean();
		if ( ! is_string( $html ) || ! str_contains( $html, 'BP-01' ) || ! str_contains( $html, 'REC-07' ) || ! str_contains( $html, 'blueprint-selected-plugin.zip' ) ) {
			throw new RuntimeException( 'Connected operator instructions/downloads are unavailable on the actual candidate.' );
		}
	} finally {
		if ( null === $before ) {
			delete_option( 'ran_booster_scenarios_state' );
		} else {
			update_option( 'ran_booster_scenarios_state', $before, false );
		}
	}
}

ran_booster_scenarios_connected_installed_proof();
