<?php
/**
 * Isolated scenario fixture behavioral regression support.
 *
 * @package RANBoosterScenarios
 */

declare(strict_types=1);

// Isolated WordPress doubles: loaded only within separate PHPUnit processes.

/**
 * Wp get environment type.
 *
 * @return string Controlled test result.
 */
function wp_get_environment_type(): string { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Preserve the WordPress function identity.
	return $GLOBALS['ran_booster_scenario_environment'] ?? 'development';
}

/**
 * Exercise the controlled WordPress fixture boundary.
 *
 * @param array<string, string> $headers
 * @return array{version: string}
 */
/**
 * Get file data.
 *
 * @param string                $file Controlled test input.
 *
 * @param array<string, string> $headers Controlled test input.
 *
 * @param string                $context Controlled test input.
 *
 * @return array{version: string} Controlled test result.
 */
function get_file_data( string $file, array $headers, string $context = '' ): array { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Preserve the WordPress signature while returning controlled header metadata.
	return array( 'version' => str_ends_with( $file, '/ran-booster-scenarios.php' ) ? ran_booster_scenarios_test_contract()['fixture_version'] : ( $GLOBALS['ran_booster_scenario_version'] ?? ran_booster_scenarios_test_contract()['core_version'] ) );
}

/**
 * Get option.
 *
 * @param string $key Controlled test input.
 *
 * @param mixed  $fallback Controlled test input.
 *
 * @return mixed Controlled test result.
 */
function get_option( string $key, mixed $fallback = false ): mixed { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Preserve the WordPress function identity.
	return $GLOBALS['ran_booster_scenario_options'][ $key ] ?? $fallback;
}

/**
 * Update option.
 *
 * @param string $key Controlled test input.
 *
 * @param mixed  $value Controlled test input.
 *
 * @param bool   $autoload Controlled test input.
 *
 * @return bool Controlled test result.
 */
function update_option( string $key, mixed $value, bool $autoload = true ): bool { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Preserve the WordPress function identity.
	$GLOBALS['ran_booster_scenario_options'][ $key ] = $value;
	$GLOBALS['ran_booster_scenario_autoload']        = $autoload;
	return true;
}

/**
 * Delete option.
 *
 * @param string $key Controlled test input.
 *
 * @return bool Controlled test result.
 */
function delete_option( string $key ): bool { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Preserve the WordPress function identity.
	unset( $GLOBALS['ran_booster_scenario_options'][ $key ] );
	return true;
}

/**
 * Wp json encode.
 *
 * @param mixed $value Controlled test input.
 *
 * @return string|false Controlled test result.
 */
function wp_json_encode( mixed $value ): string|false { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Preserve the WordPress function identity.
	// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Implement the isolated WordPress JSON double.
	return json_encode( $value );
}

/**
 * Wp parse url.
 *
 * @param string $url Controlled test input.
 *
 * @param int    $component Controlled test input.
 *
 * @return mixed Controlled test result.
 */
function wp_parse_url( string $url, int $component = -1 ): mixed { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Preserve the WordPress function identity.
	// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Implement the isolated WordPress URL double.
	return parse_url( $url, $component );
}

/**
 * Add filter.
 *
 * @param string   $hook Controlled test input.
 *
 * @param callable $callback Controlled test input.
 *
 * @param int      $priority Controlled test input.
 *
 * @param int      $accepted_args Controlled test input.
 *
 * @return bool Controlled test result.
 */
function add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Record the host hook signature without a scheduler.
	$GLOBALS['ran_booster_scenario_hooks'][ $hook ] = $callback;
	return true;
}

/**
 * Add action.
 *
 * @param string   $hook Controlled test input.
 *
 * @param callable $callback Controlled test input.
 *
 * @return bool Controlled test result.
 */
function add_action( string $hook, callable $callback ): bool { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Preserve the WordPress function identity.
	return add_filter( $hook, $callback );
}

/**
 * Current user can.
 *
 * @param string $capability Controlled test input.
 *
 * @return bool Controlled test result.
 */
function current_user_can( string $capability ): bool { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Preserve the WordPress function identity.
	return 'manage_options' === $capability && ( $GLOBALS['ran_booster_scenario_admin'] ?? true );
}

/**
 * Wp die.
 *
 * @param string $message Controlled test input.
 * @throws RuntimeException To model WordPress termination.
 */
function wp_die( string $message ): never { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Preserve the WordPress function identity.
	throw new RuntimeException( $message );
}

/**
 * Check admin referer.
 *
 * @param string $action Controlled test input.
 * @throws RuntimeException When the test nonce is invalid.
 *
 * @return int Controlled test result.
 */
function check_admin_referer( string $action ): int { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Preserve the WordPress function identity.
	if ( 'ran_booster_scenarios_save' !== $action || ! ( $GLOBALS['ran_booster_scenario_nonce'] ?? true ) ) {
		throw new RuntimeException( 'Invalid nonce.' );
	}
	return 1;
}

/**
 * Sanitize key.
 *
 * @param string $key Controlled test input.
 *
 * @return string Controlled test result.
 */
function sanitize_key( string $key ): string { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Preserve the WordPress function identity.
	return (string) preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) );
}

/**
 * Wp unslash.
 *
 * @param string $value Controlled test input.
 *
 * @return string Controlled test result.
 */
function wp_unslash( string $value ): string { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Preserve the WordPress function identity.
	return stripslashes( $value );
}

/**
 * Admin url.
 *
 * @param string $path Controlled test input.
 *
 * @return string Controlled test result.
 */
function admin_url( string $path ): string { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Preserve the WordPress function identity.
	return 'https://disposable.example.test/wp-admin/' . $path;
}

/**
 * Wp safe redirect.
 *
 * @param string $url Controlled test input.
 *
 * @return bool WordPress redirect signature retained.
 * @throws RuntimeException To capture the redirect destination.
 */
function wp_safe_redirect( string $url ): bool { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Stop at redirect before the production callback exits its process.
	if ( '' === $url ) {
		return false;
	}
	throw new RuntimeException( $url );
}

/**
 * Get temp dir.
 *
 * @return string Controlled test result.
 */
function get_temp_dir(): string { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Preserve the WordPress function identity.
	return sys_get_temp_dir();
}

/**
 * Exercise the controlled WordPress fixture boundary.
 *
 * @param string               $url Requested remote URL.
 * @param array<string, mixed> $args Request arguments.
 * @return mixed Controlled HTTP response.
 * @throws RuntimeException When traffic falls outside the fixture.
 */
function wp_remote_get( string $url, array $args ): mixed { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Connect the real bundled provider client to the controlled HTTP boundary without network access.
	$response = ran_booster_scenarios_http( false, $args, $url );
	if ( false === $response ) {
		throw new RuntimeException( 'Unexpected real network request: ' . $url );
	}
	return $response;
}

/**
 * Is wp error.
 *
 * @param mixed $response Controlled test input.
 *
 * @return bool Controlled test result.
 */
function is_wp_error( mixed $response ): bool { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Preserve the WordPress function identity.
	return $response instanceof WP_Error;
}

/**
 * Exercise the controlled WordPress fixture boundary.
 *
 * @param array<string, mixed> $response */
/**
 * Wp remote retrieve response code.
 *
 * @param array<string, mixed> $response Controlled test input.
 *
 * @return int Controlled test result.
 */
function wp_remote_retrieve_response_code( array $response ): int { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Preserve the WordPress function identity.
	return $response['response']['code'];
}

/**
 * Exercise the controlled WordPress fixture boundary.
 *
 * @param array<string, mixed> $response */
/**
 * Wp remote retrieve body.
 *
 * @param array<string, mixed> $response Controlled test input.
 *
 * @return string Controlled test result.
 */
function wp_remote_retrieve_body( array $response ): string { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Preserve the WordPress function identity.
	return $response['body'];
}

/**
 * Exercise the controlled WordPress fixture boundary.
 *
 * @param array<string, mixed> $response */
/**
 * Wp remote retrieve header.
 *
 * @param array<string, mixed> $response Controlled test input.
 *
 * @param string               $header Controlled test input.
 *
 * @return string Controlled test result.
 */
function wp_remote_retrieve_header( array $response, string $header ): string { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Preserve the WordPress function identity.
	return $response['headers'][ strtolower( $header ) ] ?? '';
}

/**
 * Exercise the controlled WordPress fixture boundary.
 *
 * @param string               $url Requested remote URL.
 * @param array<string, mixed> $args Request arguments.
 * @return mixed Controlled HTTP response.
 * @throws RuntimeException When traffic falls outside the fixture.
 */
function wp_safe_remote_get( string $url, array $args ): mixed { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Connect the real updater's safe HTTP boundary to the same controlled fixture.
	return wp_remote_get( $url, $args );
}

/**
 * Wp tempnam.
 *
 * @param string $name Controlled test input.
 *
 * @return string|false Controlled test result.
 */
function wp_tempnam( string $name ): string|false { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Preserve WordPress temp-file semantics without network access.
	return tempnam( sys_get_temp_dir(), 'ran-scenario-' . basename( $name ) );
}

/**
 * Translate controlled fixture text without installed language packs.
 *
 * @param string $text Source text.
 * @param string $domain Translation domain.
 * @return string Untranslated fixture text.
 */
function __( string $text, string $domain ): string { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound, Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Preserve the WordPress translation signature.
	return $text;
}

/**
 * Escape fixture text as WordPress does.
 *
 * @param string $text Source text.
 * @return string Escaped text.
 */
function esc_html( string $text ): string { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Preserve the WordPress escaping identity.
	return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
}
