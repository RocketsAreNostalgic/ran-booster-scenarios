<?php
/**
 * Plugin Name: RAN Booster Scenarios (disposable sites only)
 * Description: Version-locked, opt-in controlled release scenarios for Booster acceptance.
 * x-release-please-start-version
 * Version: 0.1.2
 * x-release-please-end
 * Requires PHP: 8.4
 * Requires at least: 7.0
 * License: GPL-2.0-only
 * Text Domain: ran-booster-scenarios
 * Domain Path: /languages
 * Update URI: https://github.com/RocketsAreNostalgic/ran-booster-scenarios
 *
 * @package RAN_Booster_Scenarios
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/journeys.php';
require_once __DIR__ . '/catalog.php';
require_once __DIR__ . '/pairing.php';

/**
 * Read the reviewed compatibility contract without accepting partial or malformed data.
 *
 * @since 0.1.0
 * @return array{schema_version: int, fixture_version: string, core_version: string, core_commit: string, core_zip_sha256: string, provider_api: int, addon_api: int, admin_interaction_api: int, pairing_mode?: string, candidate_metadata?: string}|null Validated identity or null.
 */
function ran_booster_scenarios_compatibility(): ?array {
	$data = ran_booster_scenarios_pairing_json( __DIR__ . '/compatibility.json' );
	if ( ! is_array( $data ) || 1 !== ( $data['schema_version'] ?? null ) ) {
		return null;
	}
	if ( ! is_string( $data['fixture_version'] ?? null ) || ! is_string( $data['core_version'] ?? null )
		|| ! is_string( $data['core_commit'] ?? null ) || ! is_string( $data['core_zip_sha256'] ?? null )
		|| ! is_int( $data['provider_api'] ?? null ) || $data['provider_api'] < 1
		|| ! is_int( $data['addon_api'] ?? null ) || $data['addon_api'] < 1
		|| ! is_int( $data['admin_interaction_api'] ?? null ) || $data['admin_interaction_api'] < 1 ) {
		return null;
	}
	if ( 1 !== preg_match( '/^[0-9]+\.[0-9]+\.[0-9]+(?:-[a-zA-Z0-9.-]+)?$/D', $data['fixture_version'] )
		|| 1 !== preg_match( '/^[0-9]+\.[0-9]+\.[0-9]+(?:-[a-zA-Z0-9.-]+)?$/D', $data['core_version'] )
		|| 1 !== preg_match( '/^[a-f0-9]{40}$/D', $data['core_commit'] )
		|| 1 !== preg_match( '/^[a-f0-9]{64}$/D', $data['core_zip_sha256'] )
		|| ( get_file_data( __FILE__, array( 'version' => 'Version' ), 'plugin' )['version'] ?? '' ) !== $data['fixture_version'] ) {
		return null;
	}
	$result = array(
		'schema_version'        => 1,
		'fixture_version'       => $data['fixture_version'],
		'core_version'          => $data['core_version'],
		'core_commit'           => $data['core_commit'],
		'core_zip_sha256'       => $data['core_zip_sha256'],
		'provider_api'          => $data['provider_api'],
		'addon_api'             => $data['addon_api'],
		'admin_interaction_api' => $data['admin_interaction_api'],
	);
	if ( array_key_exists( 'pairing_mode', $data ) || array_key_exists( 'candidate_metadata', $data ) || file_exists( __DIR__ . '/candidate-pairing.json' ) ) {
		if ( 'candidate' !== ( $data['pairing_mode'] ?? null ) || 'candidate-pairing.json' !== ( $data['candidate_metadata'] ?? null ) ) {
			return null;
		}
		$result['pairing_mode']       = 'candidate';
		$result['candidate_metadata'] = 'candidate-pairing.json';
	}
	return $result;
}

/**
 * Check the qualified PHP release series using PHP's numeric version format.
 *
 * @since 0.1.0
 * @param int $version_id Numeric PHP version, as represented by PHP_VERSION_ID.
 * @return bool Whether the runtime belongs to PHP 8.4 or 8.5.
 */
function ran_booster_scenarios_supports_php( int $version_id ): bool {
	return $version_id >= 80400 && $version_id < 80600;
}

/**
 * Re-evaluate the runtime, disposable-site and fixed pairing on every request.
 *
 * @since 0.1.0
 * @return string Empty when compatible; otherwise an administrator-facing reason.
 */
function ran_booster_scenarios_guard(): string {
	if ( ! ran_booster_scenarios_supports_php( PHP_VERSION_ID ) ) {
		return __( 'This fixture requires PHP 8.4 or 8.5. Other PHP versions are not qualified.', 'ran-booster-scenarios' );
	}
	if ( ! defined( 'RAN_BOOSTER_SCENARIOS_DISPOSABLE' ) || true !== constant( 'RAN_BOOSTER_SCENARIOS_DISPOSABLE' ) ) {
		return __( 'Set RAN_BOOSTER_SCENARIOS_DISPOSABLE to true on a disposable site.', 'ran-booster-scenarios' );
	}
	if ( ! in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) ) {
		return __( 'Only local and development WordPress environments are supported.', 'ran-booster-scenarios' );
	}
	$compatibility = ran_booster_scenarios_compatibility();
	if ( null === $compatibility ) {
		return __( 'The fixture compatibility manifest is missing, invalid, or does not match the plugin version.', 'ran-booster-scenarios' );
	}
	foreach ( array(
		'RAN_BOOSTER_PROVIDER_API_VERSION'          => $compatibility['provider_api'],
		'RAN_BOOSTER_ADDON_API_VERSION'             => $compatibility['addon_api'],
		'RAN_BOOSTER_ADMIN_INTERACTION_API_VERSION' => $compatibility['admin_interaction_api'],
	) as $marker => $version ) {
		if ( ! defined( $marker ) || constant( $marker ) !== $version ) {
			return __( 'Active Core APIs do not match the fixture compatibility manifest.', 'ran-booster-scenarios' );
		}
	}
	$core_file = WP_PLUGIN_DIR . '/ran-booster/ran-booster.php';
	if ( ! is_file( $core_file ) || ( get_file_data( $core_file, array( 'version' => 'Version' ), 'plugin' )['version'] ?? '' ) !== $compatibility['core_version'] ) {
		/* translators: %s: Required Booster Core version. */
		return sprintf( __( 'Install exactly Core %s in wp-content/plugins/ran-booster.', 'ran-booster-scenarios' ), $compatibility['core_version'] );
	}
	$marker_file = WP_PLUGIN_DIR . '/ran-booster/ran-booster-release.json';
	$marker      = ran_booster_scenarios_pairing_json( $marker_file );
	if ( ! is_array( $marker ) || 'ran-booster-core-release' !== ( $marker['schema'] ?? null ) || 1 !== ( $marker['schema_version'] ?? null ) || ( $marker['version'] ?? null ) !== $compatibility['core_version'] || ( $marker['commit'] ?? null ) !== $compatibility['core_commit'] ) {
		if ( 'candidate' === ( $compatibility['pairing_mode'] ?? null ) ) {
			/* translators: %s: Required Booster Core version. */
			return sprintf( __( 'Install the exact paired Core %s ZIP, including its build marker.', 'ran-booster-scenarios' ), $compatibility['core_version'] );
		}
		/* translators: %s: Required Booster Core version. */
		return sprintf( __( 'Install the exact published Core %s ZIP, including its release marker.', 'ran-booster-scenarios' ), $compatibility['core_version'] );
	}
	if ( 'candidate' === ( $compatibility['pairing_mode'] ?? null ) ) {
		return ran_booster_scenarios_candidate_guard( $compatibility );
	}
	return '';
}

/**
 * Read validated fixture selection, treating malformed persisted options as disabled.
 *
 * @since 0.1.0
 * @return array{scenario: string, stage: int, target: string} Validated fixture state.
 */
function ran_booster_scenarios_state(): array {
	$state   = get_option( 'ran_booster_scenarios_state', array() );
	$catalog = ran_booster_scenarios_catalog();
	if ( ! is_array( $state ) || ! is_string( $state['scenario'] ?? null ) || ! isset( $catalog[ $state['scenario'] ] ) || ! in_array( $state['stage'] ?? null, array( 0, 1 ), true ) || ! in_array( $state['target'] ?? null, array( 'both', 'plugin', 'theme' ), true ) ) {
		return array(
			'scenario' => '',
			'stage'    => 0,
			'target'   => 'both',
		);
	}
	return array(
		'scenario' => $state['scenario'],
		'stage'    => $state['stage'],
		'target'   => $state['target'],
	);
}

/**
 * Build a WordPress HTTP response containing controlled JSON.
 *
 * @since 0.1.0
 * @param mixed                 $body    JSON-compatible response data.
 * @param int                   $code    HTTP response status.
 * @param array<string, string> $headers Controlled response headers.
 * @return array{body: string, headers: array<string, string>, response: array{code: int, message: string}} Controlled HTTP response.
 */
function ran_booster_scenarios_response( mixed $body, int $code = 200, array $headers = array() ): array {
	$encoded = wp_json_encode( $body );
	return array(
		'body'     => is_string( $encoded ) ? $encoded : '',
		'headers'  => $headers,
		'response' => array(
			'code'    => $code,
			'message' => 'Controlled scenario',
		),
	);
}

/**
 * Serve bundled ZIPs only, with streams restricted to regular WordPress temporary files.
 *
 * @since 0.1.0
 * @param string               $slug Allowlisted synthetic target slug.
 * @param array<string, mixed> $args HTTP request arguments.
 * @return mixed Controlled archive response or a WordPress error.
 */
function ran_booster_scenarios_archive( string $slug, array $args ): mixed {
	if ( ! in_array( $slug, array( 'scenario-plugin', 'scenario-theme' ), true ) ) {
		return new WP_Error( 'ran_booster_scenarios_archive', __( 'Unknown fixture archive.', 'ran-booster-scenarios' ) );
	}
	$archive = __DIR__ . '/archives/' . $slug . '.zip';
	if ( ! is_file( $archive ) || is_link( $archive ) ) {
		return new WP_Error( 'ran_booster_scenarios_archive', __( 'Bundled archive missing.', 'ran-booster-scenarios' ) );
	}
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Serve bundled local fixture bytes, never a remote archive.
	$bytes = file_get_contents( $archive );
	if ( false === $bytes ) {
		return new WP_Error( 'ran_booster_scenarios_archive', __( 'Bundled archive unreadable.', 'ran-booster-scenarios' ) );
	}
	$response = array(
		'body'     => $bytes,
		'headers'  => array( 'content-type' => 'application/zip' ),
		'response' => array(
			'code'    => 200,
			'message' => 'Controlled fixture archive',
		),
	);
	if ( true === ( $args['stream'] ?? false ) ) {
		$filename = $args['filename'] ?? null;
		$temp     = realpath( get_temp_dir() );
		$resolved = is_string( $filename ) ? realpath( $filename ) : false;
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Match WordPress HTTP streaming into its validated temporary file using exact local fixture bytes.
		if ( ! is_string( $filename ) || false === $temp || false === $resolved || is_link( $filename ) || ! is_file( $filename ) || dirname( $resolved ) !== $temp || file_put_contents( $resolved, $bytes ) !== strlen( $bytes ) ) {
			return new WP_Error( 'ran_booster_scenarios_stream', __( 'Archive streaming requires a regular pre-created file directly in the WordPress temp directory.', 'ran-booster-scenarios' ) );
		}
		$response['body']     = '';
		$response['filename'] = $filename;
	}
	return $response;
}

/**
 * Describe one controlled public repository in GitHub's response format.
 *
 * @since 0.1.0
 * @param string $slug          Synthetic target slug.
 * @param int    $repository_id Synthetic provider repository identity.
 * @return array{id: int, name: string, full_name: string, private: bool, default_branch: string, html_url: string, owner: array{login: string}} Repository response data.
 */
function ran_booster_scenarios_repository( string $slug, int $repository_id ): array {
	return array(
		'id'             => $repository_id,
		'name'           => $slug,
		'full_name'      => 'ran-booster-scenarios/' . $slug,
		'private'        => false,
		'default_branch' => 'main',
		'html_url'       => 'https://github.com/ran-booster-scenarios/' . $slug,
		'owner'          => array( 'login' => 'ran-booster-scenarios' ),
	);
}

/**
 * Intercept only the reserved fake GitHub identity; never fabricate unrelated provider traffic.
 *
 * @since 0.1.0
 * @param mixed                $pre  Earlier HTTP filter result, preserved for unrelated requests.
 * @param array<string, mixed> $args HTTP request arguments.
 * @param string               $url  Requested URL.
 * @return mixed Controlled response, WordPress error, or unchanged earlier filter result.
 */
function ran_booster_scenarios_http( mixed $pre, array $args, string $url ): mixed {
	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	$path = rawurldecode( (string) wp_parse_url( $url, PHP_URL_PATH ) );
	$fake = in_array( $host, array( 'api.github.com', 'github.com', 'raw.githubusercontent.com', 'codeload.github.com' ), true )
		&& ( preg_match( '~^/(?:(?:repos|users|orgs)/)?ran-booster-scenarios(?:/|$)~i', $path ) === 1 || preg_match( '~^/repositories/94000[12](?:/|$)~', $path ) === 1 );
	if ( ! $fake ) {
		return $pre;
	}
	$blocked = new WP_Error( 'ran_booster_scenarios_blocked', __( 'Synthetic target blocked: fixture disabled, incompatible or unsupported request.', 'ran-booster-scenarios' ) );
	$state   = ran_booster_scenarios_state();
	if ( '' !== ran_booster_scenarios_guard() || '' !== ran_booster_scenarios_journey_guard( $state['scenario'] ) || '' === $state['scenario'] || 'api.github.com' !== $host || 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) || 'GET' !== strtoupper( is_string( $args['method'] ?? null ) ? $args['method'] : 'GET' ) ) {
		return $blocked;
	}
	$scenario = ran_booster_scenarios_catalog()[ $state['scenario'] ];
	$mode     = 1 === $state['stage'] && '' !== $scenario['next'] ? $scenario['next'] : $scenario['initial'];

	$repositories = array(
		'scenario-plugin' => 940001,
		'scenario-theme'  => 940002,
	);
	// Fault scenarios obey the same exact target and read-only endpoint boundary as successful scenarios.
	$allowed = in_array( $path, array( '/users/ran-booster-scenarios', '/orgs/ran-booster-scenarios/repos' ), true );
	foreach ( $repositories as $slug => $repository_id ) {
		if ( 'both' !== $state['target'] && 'scenario-' . $state['target'] !== $slug ) {
			continue;
		}
		$base    = '/repos/ran-booster-scenarios/' . $slug;
		$allowed = $allowed || in_array(
			$path,
			array(
				$base,
				'/repositories/' . $repository_id,
				$base . '/commits/main',
				$base . '/commits/v2.0.0',
				$base . '/commits/' . str_repeat( 'a', 40 ),
				$base . '/branches/main',
				$base . '/zipball/' . str_repeat( 'a', 40 ),
				$base . '/releases/assets/' . ( $repository_id + 200 ),
				$base . '/releases',
				$base . '/releases/latest',
				$base . '/releases/tags/v2.0.0',
				$base . '/releases/' . ( $repository_id + 100 ),
			),
			true
		);
	}
	if ( ! $allowed ) {
		return $blocked;
	}
	if ( in_array( $mode, array( 'partial-denied', 'partial-missing' ), true ) && ( str_starts_with( $path, '/repos/ran-booster-scenarios/scenario-theme' ) || '/repositories/940002' === $path ) ) {
		return ran_booster_scenarios_response( array( 'message' => 'Repository unavailable in this fixed partial-preview recipe' ), 'partial-denied' === $mode ? 403 : 404 );
	}
	if ( in_array( $mode, array( 'unavailable', 'denied', 'rate-limited' ), true ) ) {
		$codes = array(
			'unavailable'  => 503,
			'denied'       => 403,
			'rate-limited' => 429,
		);
		return ran_booster_scenarios_response(
			array( 'message' => $mode ),
			$codes[ $mode ],
			'rate-limited' === $mode ? array(
				'retry-after'           => '60',
				'x-ratelimit-remaining' => '0',
			) : array()
		);
	}
	if ( '/users/ran-booster-scenarios' === $path ) {
		return ran_booster_scenarios_response(
			array(
				'login' => 'ran-booster-scenarios',
				'type'  => 'Organization',
			)
		);
	}
	if ( '/orgs/ran-booster-scenarios/repos' === $path ) {
		$items = array();
		$query = (string) wp_parse_url( $url, PHP_URL_QUERY );
		parse_str( $query, $parameters );
		if ( isset( $parameters['page'] ) && '1' !== $parameters['page'] ) {
			return ran_booster_scenarios_response( $items );
		}
		foreach ( $repositories as $slug => $repository_id ) {
			if ( 'both' === $state['target'] || 'scenario-' . $state['target'] === $slug ) {
				$items[] = ran_booster_scenarios_repository( $slug, $repository_id );
			}
		}
		return ran_booster_scenarios_response( $items );
	}

	foreach ( $repositories as $slug => $repository_id ) {
		$repository = 'ran-booster-scenarios/' . $slug;
		$base       = '/repos/' . $repository;
		if ( $path !== $base && '/repositories/' . $repository_id !== $path && ! str_starts_with( $path, $base . '/' ) ) {
			continue;
		}
		if ( 'both' !== $state['target'] && 'scenario-' . $state['target'] !== $slug ) {
			return $blocked;
		}

		if ( $path === $base || '/repositories/' . $repository_id === $path ) {
			return ran_booster_scenarios_response( ran_booster_scenarios_repository( $slug, $repository_id ) );
		}
		if ( in_array( $path, array( $base . '/commits/main', $base . '/commits/v2.0.0', $base . '/commits/' . str_repeat( 'a', 40 ) ), true ) ) {
			$commit   = str_repeat( ( 'historical-commit' === $mode && $base . '/commits/main' === $path ) || 'wrong-commit' === $mode ? 'b' : 'a', 40 );
			$response = ran_booster_scenarios_response( array( 'sha' => $commit ) );
			$headers  = is_array( $args['headers'] ?? null ) ? array_change_key_case( $args['headers'], CASE_LOWER ) : array();
			if ( 'application/vnd.github.sha' === ( $headers['accept'] ?? null ) ) {
				$response['body']                    = $commit;
				$response['headers']['content-type'] = 'text/plain';
			}
			return $response;
		}
		if ( $base . '/branches/main' === $path ) {
			return ran_booster_scenarios_response(
				array(
					'name'   => 'main',
					'commit' => array( 'sha' => str_repeat( 'historical-commit' === $mode || 'wrong-commit' === $mode ? 'b' : 'a', 40 ) ),
				)
			);
		}
		if ( $base . '/zipball/' . str_repeat( 'a', 40 ) === $path ) {
			return ran_booster_scenarios_archive( $slug, $args );
		}
		if ( $base . '/releases/assets/' . ( $repository_id + 200 ) === $path ) {
			return in_array( $mode, array( 'immutable', 'mutable', 'partial-denied', 'partial-missing', 'historical-commit', 'wrong-commit' ), true ) ? ran_booster_scenarios_archive( $slug, $args ) : $blocked;
		}
		if ( ! in_array( $path, array( $base . '/releases', $base . '/releases/latest', $base . '/releases/tags/v2.0.0', $base . '/releases/' . ( $repository_id + 100 ) ), true ) ) {
			return $blocked;
		}
		if ( 'no-release' === $mode ) {
			return ran_booster_scenarios_response( $path === $base . '/releases' ? array() : array( 'message' => 'Not Found' ), $path === $base . '/releases' ? 200 : 404 );
		}
		if ( 'malformed' === $mode ) {
			return array(
				'body'     => '{invalid-json',
				'headers'  => array(),
				'response' => array(
					'code'    => 200,
					'message' => 'Controlled malformed JSON',
				),
			);
		}
		$archive = __DIR__ . '/archives/' . $slug . '.zip';
		if ( ! is_file( $archive ) || is_link( $archive ) || ! is_readable( $archive ) ) {
			return new WP_Error( 'ran_booster_scenarios_archive', __( 'Build or reinstall the fixture with its bundled archives before testing release offers.', 'ran-booster-scenarios' ) );
		}
		$release = array(
			'id'           => $repository_id + 100,
			'tag_name'     => 'v2.0.0',
			'draft'        => false,
			'prerelease'   => false,
			'immutable'    => in_array( $mode, array( 'immutable', 'partial-denied', 'partial-missing', 'historical-commit', 'wrong-commit' ), true ),
			'published_at' => '2026-10-13T00:00:00Z',
			'html_url'     => 'https://github.com/' . $repository . '/releases/tag/v2.0.0',
			'assets'       => array(
				array(
					'id'                   => $repository_id + 200,
					'name'                 => $slug . '.zip',
					'size'                 => (int) filesize( __DIR__ . '/archives/' . $slug . '.zip' ),
					'state'                => 'uploaded',
					'digest'               => 'sha256:' . hash_file( 'sha256', __DIR__ . '/archives/' . $slug . '.zip' ),
					'browser_download_url' => 'https://api.github.com' . $base . '/releases/assets/' . ( $repository_id + 200 ),
				),
			),
		);
		return ran_booster_scenarios_response( $path === $base . '/releases' ? array( $release ) : $release );
	}
	return $blocked;
}

/**
 * Change fixture selection without modifying Booster records, caches or logging settings.
 *
 * @since 0.1.0
 * @return void
 */
function ran_booster_scenarios_save(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html( __( 'Administrator access required.', 'ran-booster-scenarios' ) ) );
	}
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		wp_die( esc_html( __( 'POST required.', 'ran-booster-scenarios' ) ) );
	}
	check_admin_referer( 'ran_booster_scenarios_save' );
	$operation = isset( $_POST['operation'] ) && is_string( $_POST['operation'] ) ? sanitize_key( wp_unslash( $_POST['operation'] ) ) : '';
	if ( 'reset' === $operation ) {
		delete_option( 'ran_booster_scenarios_state' );
	} elseif ( '' === ran_booster_scenarios_guard() ) {
		$state   = ran_booster_scenarios_state();
		$catalog = ran_booster_scenarios_catalog();
		if ( 'select' === $operation ) {
			$selected = isset( $_POST['scenario'] ) && is_string( $_POST['scenario'] ) ? sanitize_key( wp_unslash( $_POST['scenario'] ) ) : '';
			$target   = isset( $_POST['target'] ) && is_string( $_POST['target'] ) ? sanitize_key( wp_unslash( $_POST['target'] ) ) : '';
			if ( isset( $catalog[ $selected ] ) && '' === ran_booster_scenarios_journey_guard( $selected ) && in_array( $target, array( 'both', 'plugin', 'theme' ), true ) ) {
				update_option(
					'ran_booster_scenarios_state',
					array(
						'scenario' => $selected,
						'stage'    => 0,
						'target'   => $target,
					),
					false
				);
			}
		} elseif ( 'advance' === $operation && '' === ran_booster_scenarios_journey_guard( $state['scenario'] ) && isset( $catalog[ $state['scenario'] ] ) && '' !== $catalog[ $state['scenario'] ]['next'] ) {
			update_option(
				'ran_booster_scenarios_state',
				array(
					'scenario' => $state['scenario'],
					'stage'    => 1,
					'target'   => $state['target'],
				),
				false
			);
		}
	}
	wp_safe_redirect( admin_url( 'tools.php?page=ran-booster-scenarios' ) );
	exit;
}

/**
 * Render scenario controls and links without bypassing Core's own operations.
 *
 * @since 0.1.0
 * @return void
 */
function ran_booster_scenarios_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$state         = ran_booster_scenarios_state();
	$guard         = ran_booster_scenarios_guard();
	$compatibility = ran_booster_scenarios_compatibility();
	if ( '' === $guard ) {
		$guard = ran_booster_scenarios_journey_guard( $state['scenario'] );
	}
	echo '<div class="wrap"><h1>' . esc_html( __( 'Booster scenarios', 'ran-booster-scenarios' ) ) . '</h1>';
	if ( null !== $compatibility ) {
		/* translators: 1: Fixture plugin version, 2: Required Booster Core version. */
		echo '<p>' . esc_html( sprintf( __( 'Fixture %1$s · Core %2$s', 'ran-booster-scenarios' ), $compatibility['fixture_version'], $compatibility['core_version'] ) ) . '</p>';
	}
	ran_booster_scenarios_pairing_status( $compatibility );
	$success = 'candidate' === ( $compatibility['pairing_mode'] ?? null )
		? __( 'Candidate file sets match this fixed pairing. Walkthrough acceptance remains separate.', 'ran-booster-scenarios' )
		: __( 'Compatible disposable environment.', 'ran-booster-scenarios' );
	echo '<p>' . esc_html( '' !== $guard ? $guard : $success ) . '</p>';
	echo '<p>' . esc_html( ran_booster_scenarios_status() ) . ' ' . esc_html( __( 'Selection persists across cron and browser requests.', 'ran-booster-scenarios' ) ) . '</p>';
	$catalog = ran_booster_scenarios_catalog();
	if ( isset( $catalog[ $state['scenario'] ] ) ) {
		echo '<p><strong>' . esc_html( __( 'Expected:', 'ran-booster-scenarios' ) ) . '</strong> ' . esc_html( $catalog[ $state['scenario'] ]['expected'] ) . '</p>';
	}
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="ran_booster_scenarios_save">';
	wp_nonce_field( 'ran_booster_scenarios_save' );
	echo '<label for="ran-scenario">' . esc_html( __( 'Scenario', 'ran-booster-scenarios' ) ) . ' </label><select id="ran-scenario" name="scenario">';
	foreach ( ran_booster_scenarios_catalog() as $key => $scenario ) {
		echo '<option value="' . esc_attr( $key ) . '" ' . selected( $state['scenario'], $key, false ) . ( '' !== ran_booster_scenarios_journey_guard( $key ) ? ' disabled' : '' ) . '>' . esc_html( $scenario['label'] ) . '</option>';
	}
	echo '</select> <label for="ran-target">' . esc_html( __( 'Target', 'ran-booster-scenarios' ) ) . '</label><select id="ran-target" name="target">';
	foreach ( array(
		'both'   => __( 'Both targets', 'ran-booster-scenarios' ),
		'plugin' => __( 'Plugin', 'ran-booster-scenarios' ),
		'theme'  => __( 'Theme', 'ran-booster-scenarios' ),
	) as $target => $label ) {
		echo '<option value="' . esc_attr( $target ) . '" ' . selected( $state['target'], $target, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select> <button class="button" name="operation" value="select">' . esc_html( __( 'Select / restart', 'ran-booster-scenarios' ) ) . '</button> <button class="button" name="operation" value="advance">' . esc_html( __( 'Advance to stage 1', 'ran-booster-scenarios' ) ) . '</button> <button class="button" name="operation" value="reset">' . esc_html( __( 'Disable / reset fixture selection', 'ran-booster-scenarios' ) ) . '</button></form>';
	echo '<p>' . esc_html( __( 'Use only ran-booster-scenarios/scenario-plugin and ran-booster-scenarios/scenario-theme. No real credentials needed. Install the documented local stub targets first. Only the bundled harmless plugin/theme archives can be installed through normal WordPress controls. These prove synthetic installation, not real GitHub publishing.', 'ran-booster-scenarios' ) ) . '</p>';
	echo '<p>' . esc_html( __( 'After changing stage, use Booster’s normal refresh action or cron; existing caches and history are intentionally preserved. Reset removes only fixture selection, not Booster records or history. Logging stays under Booster’s normal settings.', 'ran-booster-scenarios' ) ) . '</p><h2>' . esc_html( __( 'Test routes', 'ran-booster-scenarios' ) ) . '</h2><ul>';
	foreach ( ran_booster_scenarios_routes() as $label => $route ) {
		echo '<li><a href="' . esc_url( admin_url( $route ) ) . '">' . esc_html( $label ) . '</a></li>';
	}
	echo '</ul>';
	ran_booster_scenarios_render_journeys( $guard );
	echo '</div>';
}

/**
 * Describe the selected scenario with a complete translatable status sentence.
 *
 * @since 0.1.0
 * @return string Current fixture selection and manual stage.
 */
function ran_booster_scenarios_status(): string {
	$state   = ran_booster_scenarios_state();
	$catalog = ran_booster_scenarios_catalog();
	$targets = array(
		'both'   => __( 'Both targets', 'ran-booster-scenarios' ),
		'plugin' => __( 'Plugin', 'ran-booster-scenarios' ),
		'theme'  => __( 'Theme', 'ran-booster-scenarios' ),
	);
	/* translators: 1: Scenario label, 2: Manual stage number, 3: Target label. */
	return sprintf( __( 'Selected: %1$s; stage: %2$d; target: %3$s.', 'ran-booster-scenarios' ), $catalog[ $state['scenario'] ]['label'] ?? __( 'disabled', 'ran-booster-scenarios' ), $state['stage'], $targets[ $state['target'] ] );
}

/**
 * Show fixture state on administrator pages, including incompatible selections.
 *
 * @since 0.1.0
 * @return void
 */
function ran_booster_scenarios_notice(): void {
	if ( current_user_can( 'manage_options' ) ) {
		$guard = ran_booster_scenarios_guard();
		if ( '' === $guard ) {
			$guard = ran_booster_scenarios_journey_guard( ran_booster_scenarios_state()['scenario'] );
		}
		echo '<div class="notice notice-warning"><p>' . esc_html( __( 'Booster test fixture installed.', 'ran-booster-scenarios' ) ) . ' ' . esc_html( '' !== $guard ? $guard : ran_booster_scenarios_status() ) . ' <a href="' . esc_url( admin_url( 'tools.php?page=ran-booster-scenarios' ) ) . '">' . esc_html( __( 'Scenario controls', 'ran-booster-scenarios' ) ) . '</a></p></div>';
	}
}

add_action(
	'init',
	static function (): void {
		load_plugin_textdomain( 'ran-booster-scenarios', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
);

add_filter( 'pre_http_request', 'ran_booster_scenarios_http', PHP_INT_MAX, 3 );
add_action( 'admin_post_ran_booster_scenarios_save', 'ran_booster_scenarios_save' );
add_action( 'admin_notices', 'ran_booster_scenarios_notice' );
add_action(
	'admin_menu',
	static function (): void {
		add_management_page( __( 'Booster scenarios', 'ran-booster-scenarios' ), __( 'Booster scenarios', 'ran-booster-scenarios' ), 'manage_options', 'ran-booster-scenarios', 'ran_booster_scenarios_page' );
	}
);
