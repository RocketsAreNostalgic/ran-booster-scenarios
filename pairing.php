<?php
/**
 * Fixed candidate pairing and installed-file verification for disposable sites.
 *
 * @package RAN_Booster_Scenarios
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read bounded local JSON without executing its contents.
 *
 * @param string $file Absolute local metadata filename.
 * @return array<string, mixed>|null Decoded object or null.
 */
function ran_booster_scenarios_pairing_json( string $file ): ?array {
	if ( is_link( $file ) || ! is_file( $file ) || ! is_readable( $file ) || filesize( $file ) > 2097152 ) {
		return null;
	}
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Inspect bounded local build metadata, never remote content.
	$bytes = file_get_contents( $file );
	$data  = is_string( $bytes ) ? json_decode( $bytes, true, 32 ) : null;
	return is_array( $data ) && ! array_is_list( $data ) ? $data : null;
}

/**
 * Validate the generated provenance record against this package's fixed contract.
 *
 * Metadata declares build provenance; installed byte hashes independently check
 * the extracted file trees. Neither establishes publication or trusted PHP execution.
 *
 * @param array<string, mixed> $compatibility Fixed packaged compatibility record.
 * @return array<string, mixed>|null Validated pairing record or null.
 */
function ran_booster_scenarios_candidate_pairing( array $compatibility ): ?array {
	$data = ran_booster_scenarios_pairing_json( __DIR__ . '/candidate-pairing.json' );
	if ( null === $data || 'ran-booster-scenarios-candidate' !== ( $data['schema'] ?? null ) || 1 !== ( $data['schema_version'] ?? null )
		|| ! is_string( $data['fixture_commit'] ?? null ) || 1 !== preg_match( '/^[a-f0-9]{40}$/D', $data['fixture_commit'] ) ) {
		return null;
	}
	foreach ( array( 'fixture_version', 'core_version', 'core_commit', 'core_zip_sha256', 'provider_api', 'addon_api', 'admin_interaction_api' ) as $key ) {
		if ( ! array_key_exists( $key, $data ) || ( $compatibility[ $key ] ?? null ) !== $data[ $key ] ) {
			return null;
		}
	}
	$core = $data['core'] ?? null;
	if ( ! is_array( $core ) || 'RocketsAreNostalgic/ran-booster' !== ( $core['repository'] ?? null )
		|| '.github/workflows/quality.yml' !== ( $core['workflow_path'] ?? null ) || 'source-head' !== ( $core['source_kind'] ?? null )
		|| ( $core['source_commit'] ?? null ) !== $data['core_commit'] || ( $core['tested_commit'] ?? null ) !== $data['core_commit']
		|| ( $core['version'] ?? null ) !== $data['core_version'] || ( $core['zip_sha256'] ?? null ) !== $data['core_zip_sha256']
		|| ! is_string( $core['artifact_name'] ?? null ) || '' === $core['artifact_name'] || strlen( $core['artifact_name'] ) > 200
		|| ! is_string( $core['artifact_sha256'] ?? null ) || 1 !== preg_match( '/^[a-f0-9]{64}$/D', $core['artifact_sha256'] ) ) {
		return null;
	}
	foreach ( array( 'run_id', 'run_attempt', 'artifact_id' ) as $key ) {
		if ( ! is_int( $core[ $key ] ?? null ) || $core[ $key ] < 1 ) {
			return null;
		}
	}
	if ( ! ran_booster_scenarios_file_map_valid( $data['core_files'] ?? null ) || ! ran_booster_scenarios_file_map_valid( $data['fixture_files'] ?? null )
		|| ! isset( $data['core_files']['ran-booster.php'], $data['core_files']['ran-booster-release.json'], $data['fixture_files']['ran-booster-scenarios.php'], $data['fixture_files']['compatibility.json'], $data['fixture_files']['pairing.php'] )
		|| isset( $data['fixture_files']['candidate-pairing.json'] ) ) {
		return null;
	}
	return $data;
}

/**
 * Validate bounded relative file paths and SHA-256 values before filesystem access.
 *
 * @param mixed $files Proposed file map.
 * @return array<string, string>|null Validated paths and hashes.
 */
function ran_booster_scenarios_file_map( mixed $files ): ?array {
	if ( ! is_array( $files ) || array() === $files || count( $files ) > 1200 ) {
		return null;
	}
	$validated = array();
	foreach ( $files as $path => $digest ) {
		if ( ! is_string( $path ) || strlen( $path ) > 512 || 1 !== preg_match( '~^[a-zA-Z0-9_.@+-]+(?:/[a-zA-Z0-9_.@+-]+)*$~D', $path )
			|| in_array( '.', explode( '/', $path ), true ) || in_array( '..', explode( '/', $path ), true )
			|| ! is_string( $digest ) || 1 !== preg_match( '/^[a-f0-9]{64}$/D', $digest ) ) {
			return null;
		}
		$validated[ $path ] = $digest;
	}
	return $validated;
}

/**
 * Check whether a metadata value is a bounded file map.
 *
 * @param mixed $files Proposed file map.
 * @return bool Whether the file map is well formed.
 */
function ran_booster_scenarios_file_map_valid( mixed $files ): bool {
	return null !== ran_booster_scenarios_file_map( $files );
}

/**
 * Rehash all installed regular files and refuse links, omissions or extra files.
 *
 * There is no persistent success cache. This verifies extracted file equality,
 * not ZIP container bytes, executed opcode identity or concurrent-write safety.
 *
 * @param string                $directory Exact installed plugin directory.
 * @param array<string, string> $expected Vetted ZIP file hashes.
 * @param string                $excluded One metadata file excluded to avoid a self-hash.
 * @return bool Whether the complete installed regular-file set matches.
 */
function ran_booster_scenarios_verify_files( string $directory, array $expected, string $excluded = '' ): bool {
	if ( is_link( $directory ) || ! is_dir( $directory ) || ! ran_booster_scenarios_file_map_valid( $expected ) ) {
		return false;
	}
	$found = array();
	$total = 0;
	try {
		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $directory, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::SELF_FIRST );
		foreach ( $iterator as $file ) {
			$path = substr( $file->getPathname(), strlen( $directory ) + 1 );
			if ( $file->isLink() || $iterator->getDepth() > 20 ) {
				return false;
			}
			if ( $file->isDir() ) {
				continue;
			}
			if ( ! $file->isFile() || ! $file->isReadable() ) {
				return false;
			}
			if ( $excluded === $path ) {
				continue;
			}
			$total += $file->getSize();
			if ( $total > 134217728 || count( $found ) >= 1200 || ! isset( $expected[ $path ] ) || ! hash_equals( $expected[ $path ], (string) hash_file( 'sha256', $file->getPathname() ) ) ) {
				return false;
			}
			$found[ $path ] = true;
		}
	} catch ( UnexpectedValueException | RuntimeException $error ) {
		return false;
	}
	return count( $found ) === count( $expected );
}

/**
 * Enforce the candidate package's one fixed pairing for every request surface.
 *
 * @param array<string, mixed> $compatibility Fixed compatibility record.
 * @return string Empty on success; otherwise an operator-facing refusal reason.
 */
function ran_booster_scenarios_candidate_guard( array $compatibility ): string {
	$pairing = ran_booster_scenarios_candidate_pairing( $compatibility );
	if ( null === $pairing ) {
		return __( 'Candidate pairing metadata is missing or invalid. Install a newly qualified matched pair.', 'ran-booster-scenarios' );
	}
	if ( ! defined( 'RAN_BOOSTER_SELF_UPDATE_MODE' ) || 'disabled' !== constant( 'RAN_BOOSTER_SELF_UPDATE_MODE' ) ) {
		return __( 'Unreleased candidates require RAN_BOOSTER_SELF_UPDATE_MODE set to disabled on this disposable site.', 'ran-booster-scenarios' );
	}
	$core_files    = ran_booster_scenarios_file_map( $pairing['core_files'] ?? null );
	$fixture_files = ran_booster_scenarios_file_map( $pairing['fixture_files'] ?? null );
	if ( null === $core_files || null === $fixture_files ) {
		return __( 'Candidate file verification maps are invalid.', 'ran-booster-scenarios' );
	}
	if ( ! ran_booster_scenarios_verify_files( WP_PLUGIN_DIR . '/ran-booster', $core_files ) ) {
		return __( 'Installed Core files differ from the paired candidate artifact, or cannot be verified. Reinstall the exact Core ZIP.', 'ran-booster-scenarios' );
	}
	if ( ! ran_booster_scenarios_verify_files( __DIR__, $fixture_files, 'candidate-pairing.json' ) ) {
		return __( 'Installed Scenarios files differ from the paired candidate package, or cannot be verified. Reinstall its exact ZIP.', 'ran-booster-scenarios' );
	}
	return '';
}

/**
 * Display expected and detected identities without implying general certification.
 *
 * @param array{core_commit: string, core_zip_sha256: string, pairing_mode?: string}|null $compatibility Validated fixed pairing.
 * @return void
 */
function ran_booster_scenarios_pairing_status( ?array $compatibility ): void {
	$candidate = file_exists( __DIR__ . '/candidate-pairing.json' ) || ( null !== $compatibility && 'candidate' === ( $compatibility['pairing_mode'] ?? null ) );
	echo '<p><strong>' . esc_html( $candidate ? __( 'Unreleased candidate', 'ran-booster-scenarios' ) : __( 'Released pairing', 'ran-booster-scenarios' ) ) . '</strong></p>';
	if ( null === $compatibility ) {
		return;
	}
	$marker   = ran_booster_scenarios_pairing_json( WP_PLUGIN_DIR . '/ran-booster/ran-booster-release.json' );
	$detected = is_array( $marker ) && is_string( $marker['commit'] ?? null ) && 1 === preg_match( '/^[a-f0-9]{40}$/D', $marker['commit'] ) ? $marker['commit'] : __( 'unavailable', 'ran-booster-scenarios' );
	/* translators: 1: Expected Core commit abbreviation, 2: Detected Core marker commit abbreviation. */
	echo '<p>' . esc_html( sprintf( __( 'Expected Core: %1$s · Detected marker: %2$s', 'ran-booster-scenarios' ), substr( $compatibility['core_commit'], 0, 12 ), substr( $detected, 0, 12 ) ) ) . '</p>';
	$details = array(
		'expected_core_commit' => $compatibility['core_commit'],
		'detected_marker'      => $marker,
		'core_zip_sha256'      => $compatibility['core_zip_sha256'],
	);
	if ( $candidate ) {
		$pairing            = ran_booster_scenarios_candidate_pairing( $compatibility );
		$details['pairing'] = null === $pairing ? null : array_intersect_key( $pairing, array_flip( array( 'fixture_commit', 'core' ) ) );
	}
	echo '<details><summary>' . esc_html( __( 'Pairing details', 'ran-booster-scenarios' ) ) . '</summary><pre>' . esc_html( (string) wp_json_encode( $details ) ) . '</pre><p>' . esc_html( __( 'A marker alone does not prove installed byte equality. Candidate guards rehash the extracted Core and Scenarios files on every request; the pairing metadata itself is excluded from its self-hash. Verify both ZIP checksums against the handover before installation.', 'ran-booster-scenarios' ) ) . '</p></details>';
}
