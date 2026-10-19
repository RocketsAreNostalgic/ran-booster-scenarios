<?php
/**
 * Exercise the candidate artifact to installed-runtime boundary in isolated trees.
 *
 * @package RAN_Booster_Scenarios
 */

declare(strict_types=1);

namespace RAN\Tests;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
/**
 * Candidate provenance, files and request-surface regression proofs.
 */
final class CandidatePairingTest extends TestCase {
	/**
	 * Private test tree.
	 *
	 * @var string
	 */
	private string $directory;

	/**
	 * Prepare synthetic installed artifacts without editing maintained source.
	 */
	protected function setUp(): void { // phpcs:ignore RANOwnedMethods.NamingConventions.ValidMethodName.NotSnakeCase -- PHPUnit lifecycle override.
		require __DIR__ . '/Support/ScenarioFixtureWordPressError.php';
		require __DIR__ . '/Support/ScenarioFixtureWordPressFunctions.php';
		$this->directory = sys_get_temp_dir() . '/ran-candidate-test-' . bin2hex( random_bytes( 8 ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Create private disposable test artifacts.
		mkdir( $this->directory . '/ran-booster', 0700, true );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Create private disposable test artifacts.
		mkdir( $this->directory . '/ran-booster-scenarios', 0700 );
		foreach ( array( 'ran-booster-scenarios.php', 'catalog.php', 'pairing.php', 'journeys.php' ) as $file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy -- Copy maintained runtime into an isolated installation.
			copy( dirname( __DIR__ ) . '/' . $file, $this->directory . '/ran-booster-scenarios/' . $file );
		}
		$contract                       = ran_booster_scenarios_test_contract();
		$contract['pairing_mode']       = 'candidate';
		$contract['candidate_metadata'] = 'candidate-pairing.json';
		$this->write_json( '/ran-booster-scenarios/compatibility.json', $contract );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write a controlled inert Core header.
		file_put_contents( $this->directory . '/ran-booster/ran-booster.php', '<?php // Core test fixture' );
		$this->write_json(
			'/ran-booster/ran-booster-release.json',
			array(
				'schema'         => 'ran-booster-core-release',
				'schema_version' => 1,
				'commit'         => $contract['core_commit'],
				'version'        => $contract['core_version'],
			)
		);
		$pairing = array_merge(
			ran_booster_scenarios_test_contract(),
			array(
				'schema'         => 'ran-booster-scenarios-candidate',
				'fixture_commit' => str_repeat( 'a', 40 ),
				'core'           => array(
					'repository'      => 'RocketsAreNostalgic/ran-booster',
					'source_commit'   => $contract['core_commit'],
					'tested_commit'   => $contract['core_commit'],
					'source_kind'     => 'source-head',
					'version'         => $contract['core_version'],
					'run_id'          => 123,
					'run_attempt'     => 2,
					'workflow_path'   => '.github/workflows/quality.yml',
					'artifact_id'     => 456,
					'artifact_name'   => 'controlled-test-artifact',
					'artifact_sha256' => str_repeat( 'b', 64 ),
					'zip_sha256'      => $contract['core_zip_sha256'],
				),
				'core_files'     => $this->hash_files( 'ran-booster' ),
				'fixture_files'  => $this->hash_files( 'ran-booster-scenarios' ),
			)
		);
		$this->write_json( '/ran-booster-scenarios/candidate-pairing.json', $pairing );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WordPress constant in an isolated process.
		define( 'ABSPATH', $this->directory . '/' );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WordPress constant in an isolated process.
		define( 'WP_PLUGIN_DIR', $this->directory );
		define( 'RAN_BOOSTER_SCENARIOS_DISPOSABLE', true );
		define( 'RAN_BOOSTER_PROVIDER_API_VERSION', $contract['provider_api'] );
		define( 'RAN_BOOSTER_ADDON_API_VERSION', $contract['addon_api'] );
		define( 'RAN_BOOSTER_ADMIN_INTERACTION_API_VERSION', $contract['admin_interaction_api'] );
		require $this->directory . '/ran-booster-scenarios/ran-booster-scenarios.php';
		$GLOBALS['ran_booster_scenario_options']['ran_booster_scenarios_state'] = array(
			'scenario' => 'unavailable',
			'stage'    => 0,
			'target'   => 'both',
		);
	}

	/**
	 * Remove all private test artifacts, including deliberately introduced links.
	 */
	protected function tearDown(): void { // phpcs:ignore RANOwnedMethods.NamingConventions.ValidMethodName.NotSnakeCase -- PHPUnit lifecycle override.
		$iterator = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $this->directory, \FilesystemIterator::SKIP_DOTS ), \RecursiveIteratorIterator::CHILD_FIRST );
		foreach ( $iterator as $file ) {
			if ( $file->isDir() && ! $file->isLink() ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Delete only private test directories.
				rmdir( $file->getPathname() );
			} else {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Delete only private test files or links.
				unlink( $file->getPathname() );
			}
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Delete the private test root.
		rmdir( $this->directory );
	}

	/**
	 * Prove selected fixture traffic uses fresh file verification on each request.
	 */
	public function test_browser_cron_and_cli_share_fixed_pairing_and_refuse_changed_bytes(): void {
		define( 'RAN_BOOSTER_SELF_UPDATE_MODE', 'disabled' );
		self::assertSame( '', ran_booster_scenarios_guard() );
		self::assertIsArray( $this->request() );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Model a later cron request through the identical HTTP callback.
		define( 'DOING_CRON', true );
		self::assertIsArray( $this->request() );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Model the same callback under the WP-CLI surface.
		define( 'WP_CLI', true );
		self::assertIsArray( $this->request() );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Tamper only with inert private Core bytes; the marker stays unchanged.
		file_put_contents( $this->directory . '/ran-booster/ran-booster.php', '<?php // changed' );
		self::assertStringContainsString( 'Installed Core files differ', ran_booster_scenarios_guard() );
		self::assertInstanceOf( \WP_Error::class, $this->request() );
	}

	/**
	 * Existing release markers may not enable candidate self-updates.
	 */
	public function test_candidate_refuses_without_explicitly_disabled_core_self_updates(): void {
		self::assertStringContainsString( 'SELF_UPDATE_MODE', ran_booster_scenarios_guard() );
		self::assertInstanceOf( \WP_Error::class, $this->request() );
	}

	/**
	 * Missing pairing metadata cannot silently return to the released contract.
	 */
	public function test_missing_pairing_metadata_refuses_without_release_fallback(): void {
		define( 'RAN_BOOSTER_SELF_UPDATE_MODE', 'disabled' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Delete only the private generated metadata fixture.
		unlink( $this->directory . '/ran-booster-scenarios/candidate-pairing.json' );
		self::assertStringContainsString( 'metadata is missing or invalid', ran_booster_scenarios_guard() );
	}

	/**
	 * Metadata must name the vetted repository, exact artifact and safe file map.
	 */
	public function test_malformed_provenance_commit_api_and_file_maps_refuse(): void {
		define( 'RAN_BOOSTER_SELF_UPDATE_MODE', 'disabled' );
		$path     = $this->directory . '/ran-booster-scenarios/candidate-pairing.json';
		$original = ran_booster_scenarios_pairing_json( $path );
		self::assertIsArray( $original );
		foreach ( array(
			'repository'      => 'other/repository',
			'tested_commit'   => str_repeat( 'c', 40 ),
			'run_attempt'     => 0,
			'artifact_id'     => '456',
			'artifact_sha256' => 'bad',
			'source_kind'     => 'synthetic-merge',
			'workflow_path'   => '.github/workflows/other.yml',
		) as $key => $value ) {
			$changed                 = $original;
			$changed['core'][ $key ] = $value;
			$this->write_json( '/ran-booster-scenarios/candidate-pairing.json', $changed );
			self::assertStringContainsString( 'metadata is missing or invalid', ran_booster_scenarios_guard(), $key );
		}
		foreach ( array( 'provider_api', 'addon_api', 'admin_interaction_api' ) as $api ) {
			$changed         = $original;
			$changed[ $api ] = 999;
			$this->write_json( '/ran-booster-scenarios/candidate-pairing.json', $changed );
			self::assertNotSame( '', ran_booster_scenarios_guard() );
		}
		foreach ( array( '../outside.php', '/absolute.php', 'dir/../file.php', 'dir\\file.php' ) as $unsafe ) {
			self::assertFalse( ran_booster_scenarios_file_map_valid( array( $unsafe => str_repeat( 'a', 64 ) ) ) );
		}
	}

	/**
	 * Extra installed files and links invalidate otherwise matching marker metadata.
	 */
	public function test_extra_files_and_links_refuse(): void {
		define( 'RAN_BOOSTER_SELF_UPDATE_MODE', 'disabled' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Add only a private untracked fixture file.
		file_put_contents( $this->directory . '/ran-booster/extra.php', '<?php' );
		self::assertNotSame( '', ran_booster_scenarios_guard() );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Remove only the private extra fixture file.
		unlink( $this->directory . '/ran-booster/extra.php' );
		self::assertSame( '', ran_booster_scenarios_guard() );
		symlink( $this->directory . '/ran-booster/ran-booster.php', $this->directory . '/ran-booster/link.php' );
		self::assertNotSame( '', ran_booster_scenarios_guard() );
	}

	/**
	 * Scenarios modifications and unsupported environments fail after a prior success.
	 */
	public function test_fixture_byte_changes_and_production_environment_refuse(): void {
		define( 'RAN_BOOSTER_SELF_UPDATE_MODE', 'disabled' );
		self::assertSame( '', ran_booster_scenarios_guard() );
		$GLOBALS['ran_booster_scenario_environment'] = 'production';
		self::assertInstanceOf( \WP_Error::class, $this->request() );
		$GLOBALS['ran_booster_scenario_environment'] = 'development';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Mutate only the private copied runtime file after it has loaded.
		file_put_contents( $this->directory . '/ran-booster-scenarios/catalog.php', '<?php // changed' );
		self::assertStringContainsString( 'Installed Scenarios files differ', ran_booster_scenarios_guard() );
		self::assertInstanceOf( \WP_Error::class, $this->request() );
	}

	/**
	 * Wrong active APIs and Core commits refuse before candidate traffic is served.
	 */
	public function test_wrong_active_api_and_core_marker_commit_refuse(): void {
		define( 'RAN_BOOSTER_SELF_UPDATE_MODE', 'disabled' );
		$compatibility = ran_booster_scenarios_compatibility();
		self::assertIsArray( $compatibility );
		$changed                 = $compatibility;
		$changed['provider_api'] = 999;
		$this->write_json( '/ran-booster-scenarios/compatibility.json', $changed );
		self::assertStringContainsString( 'Active Core APIs do not match', ran_booster_scenarios_guard() );
		$this->write_json( '/ran-booster-scenarios/compatibility.json', $compatibility );
		$this->write_json(
			'/ran-booster/ran-booster-release.json',
			array(
				'schema'         => 'ran-booster-core-release',
				'schema_version' => 1,
				'commit'         => str_repeat( 'd', 40 ),
				'version'        => $compatibility['core_version'],
			)
		);
		self::assertStringContainsString( 'Install the exact paired Core', ran_booster_scenarios_guard() );
		self::assertInstanceOf( \WP_Error::class, $this->request() );
	}

	/**
	 * Malformed JSON and removal of candidate flags cannot admit a released fallback.
	 */
	public function test_malformed_json_and_stripped_candidate_flags_refuse(): void {
		define( 'RAN_BOOSTER_SELF_UPDATE_MODE', 'disabled' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Corrupt only private generated pairing metadata.
		file_put_contents( $this->directory . '/ran-booster-scenarios/candidate-pairing.json', '{"schema":' );
		self::assertStringContainsString( 'metadata is missing or invalid', ran_booster_scenarios_guard() );
		$this->write_json( '/ran-booster-scenarios/compatibility.json', ran_booster_scenarios_test_contract() );
		self::assertNull( ran_booster_scenarios_compatibility() );
		self::assertInstanceOf( \WP_Error::class, $this->request() );
	}

	/**
	 * Display a concise candidate label and full provenance without byte-proof claims.
	 */
	public function test_operator_identity_display_is_fixed_and_discloses_full_provenance(): void {
		ob_start();
		ran_booster_scenarios_pairing_status( ran_booster_scenarios_compatibility() );
		$output = ob_get_clean();
		self::assertIsString( $output );
		self::assertStringContainsString( 'Unreleased candidate', $output );
		self::assertStringContainsString( 'Expected Core:', $output );
		self::assertStringContainsString( 'Detected marker:', $output );
		self::assertStringContainsString( (string) ran_booster_scenarios_test_contract()['core_commit'], $output );
		self::assertStringContainsString( 'fixture_commit', $output );
		self::assertStringContainsString( 'A marker alone does not prove installed byte equality', $output );
	}

	/**
	 * Write private synthetic JSON records.
	 *
	 * @param string               $relative Private path relative to the test tree.
	 * @param array<string, mixed> $data Controlled test record.
	 * @return void
	 */
	private function write_json( string $relative, array $data ): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Write synthetic metadata into the private installation only.
		file_put_contents( $this->directory . $relative, json_encode( $data, JSON_THROW_ON_ERROR ) );
	}

	/**
	 * Hash the controlled initial file trees.
	 *
	 * @param string $plugin Private plugin directory name.
	 * @return array<string, string> Expected installed hashes.
	 */
	private function hash_files( string $plugin ): array {
		$files = array();
		foreach ( new \DirectoryIterator( $this->directory . '/' . $plugin ) as $file ) {
			if ( $file->isFile() ) {
				$files[ $file->getFilename() ] = (string) hash_file( 'sha256', $file->getPathname() );
			}
		}
		return $files;
	}

	/**
	 * Enter the same synthetic HTTP callback used by real browser, cron and CLI work.
	 *
	 * @return mixed Controlled fixture response.
	 */
	private function request(): mixed {
		return ran_booster_scenarios_http( false, array(), 'https://api.github.com/repos/ran-booster-scenarios/scenario-plugin/releases/latest' );
	}
}
