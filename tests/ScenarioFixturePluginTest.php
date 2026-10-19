<?php
/**
 * Isolated scenario fixture behavioral regression support.
 *
 * @package RANBoosterScenarios
 */

declare(strict_types=1);

namespace RAN\Tests;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
/**
 * Isolated fixture regression suite.
 */
final class ScenarioFixturePluginTest extends TestCase {
	/**
	 * Test-owned temporary host directory.
	 *
	 * @var string
	 */
	private string $directory;
	/** Prepare the isolated WordPress host. */
	protected function setUp(): void { // phpcs:ignore RANOwnedMethods.NamingConventions.ValidMethodName.NotSnakeCase -- PHPUnit lifecycle override.
		$_SERVER['REQUEST_METHOD'] = 'POST';
		require __DIR__ . '/Support/ScenarioFixtureWordPressError.php';
		require __DIR__ . '/Support/ScenarioFixtureWordPressFunctions.php';
		$this->directory = sys_get_temp_dir() . '/ran-booster-scenario-' . bin2hex( random_bytes( 8 ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Private disposable test directory.
		mkdir( $this->directory . '/ran-booster', 0700, true );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Private disposable Core header fixture.
		file_put_contents( $this->directory . '/ran-booster/ran-booster.php', '<?php // Version: ' . ran_booster_scenarios_test_contract()['core_version'] );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Private disposable release provenance marker.
		file_put_contents(
			$this->directory . '/ran-booster/ran-booster-release.json',
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Encode the isolated local release marker.
			json_encode(
				array(
					'schema'         => 'ran-booster-core-release',
					'schema_version' => 1,
					'version'        => ran_booster_scenarios_test_contract()['core_version'],
					'commit'         => ran_booster_scenarios_test_contract()['core_commit'],
				),
				JSON_THROW_ON_ERROR
			)
		);
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WordPress-owned constant in an isolated test process.
		define( 'ABSPATH', $this->directory . '/' );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WordPress-owned constant in an isolated test process.
		define( 'WP_PLUGIN_DIR', $this->directory );
		define( 'RAN_BOOSTER_PROVIDER_API_VERSION', ran_booster_scenarios_test_contract()['provider_api'] );
		define( 'RAN_BOOSTER_ADDON_API_VERSION', ran_booster_scenarios_test_contract()['addon_api'] );
		define( 'RAN_BOOSTER_ADMIN_INTERACTION_API_VERSION', ran_booster_scenarios_test_contract()['admin_interaction_api'] );
		require dirname( __DIR__ ) . '/ran-booster-scenarios.php';
	}

	/**
	 * Remove the test-owned temporary filesystem.
	 */
	protected function tearDown(): void { // phpcs:ignore RANOwnedMethods.NamingConventions.ValidMethodName.NotSnakeCase -- PHPUnit lifecycle override.
		$files = glob( $this->directory . '/ran-booster/*' );
		foreach ( false === $files ? array() : $files as $file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Remove only files in this test's private directory.
			unlink( $file );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Remove this test's private directory.
		rmdir( $this->directory . '/ran-booster' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Remove this test's private directory.
		rmdir( $this->directory );
	}

	/**
	 * Keep ZIP runtime admission within both qualified PHP release series.
	 */
	public function test_php_support_rejects_versions_outside_the_qualified_series(): void {
		foreach ( array( 80300, 80399, 80600, 80601, 90000 ) as $unsupported ) {
			self::assertFalse( ran_booster_scenarios_supports_php( $unsupported ) );
		}
		foreach ( array( 80400, 80401, 80499, 80500, 80501, 80599 ) as $supported ) {
			self::assertTrue( ran_booster_scenarios_supports_php( $supported ) );
		}
		$this->enable();
		self::assertSame( ran_booster_scenarios_supports_php( PHP_VERSION_ID ), '' === ran_booster_scenarios_guard() );
	}

	/**
	 * Verify disabled fixture blocks only synthetic targets.
	 */
	public function test_disabled_fixture_blocks_only_synthetic_targets(): void {
		$sentinel = array( 'body' => 'already handled' );
		self::assertSame( $sentinel, ran_booster_scenarios_http( $sentinel, array(), 'https://api.github.com/repos/acme/real/releases' ) );
		$this->assert_blocked( $this->request() );
		self::assertNotSame( '', ran_booster_scenarios_guard() );
	}

	/**
	 * Verify environment and version changes disable selected scenarios.
	 */
	public function test_environment_and_version_changes_disable_selected_scenarios(): void {
		$this->enable();
		$this->select( 'immutable' );
		self::assertSame( '', ran_booster_scenarios_guard() );
		$GLOBALS['ran_booster_scenario_environment'] = 'production';
		$this->assert_blocked( $this->request() );
		$GLOBALS['ran_booster_scenario_environment'] = 'development';
		$GLOBALS['ran_booster_scenario_version']     = '9999.0.0';
		$this->assert_blocked( $this->request() );
	}

	/**
	 * Verify mutations unknown endpoints and insecure requests never escape.
	 */
	public function test_mutations_unknown_endpoints_and_insecure_requests_never_escape(): void {
		$this->enable();
		$this->select( 'immutable' );
		foreach ( array(
			array( 'https://api.github.com/repos/ran-booster-scenarios/scenario-plugin/releases', array( 'method' => 'POST' ) ),
			array( 'http://api.github.com/repos/ran-booster-scenarios/scenario-plugin/releases', array() ),
			array( 'https://api.github.com/repos/ran-booster-scenarios/scenario-plugin/hooks', array() ),
			array( 'https://github.com/ran-booster-scenarios/scenario-plugin/archive/main.zip', array() ),
			array( 'https://api.github.com/repos/ran-booster-scenarios/unknown/releases', array() ),
		) as list( $url, $args ) ) {
			$this->assert_blocked( ran_booster_scenarios_http( false, $args, $url ) );
		}
	}

	/**
	 * Verify provider faults remain confined to selected known read routes.
	 */
	public function test_provider_faults_remain_confined_to_selected_known_read_routes(): void {
		$this->enable();
		foreach ( array(
			'unavailable'  => 503,
			'denied'       => 403,
			'rate-limited' => 429,
		) as $scenario => $status ) {
			$this->submit(
				array(
					'operation' => 'select',
					'scenario'  => $scenario,
					'target'    => 'theme',
				)
			);
			$known = $this->request( 'scenario-theme' );
			self::assertIsArray( $known );
			self::assertSame( $status, $known['response']['code'] );
			foreach ( array(
				'https://api.github.com/repos/ran-booster-scenarios/scenario-plugin/releases/latest',
				'https://api.github.com/repositories/940001',
				'https://api.github.com/repos/ran-booster-scenarios/unknown/releases/latest',
				'https://api.github.com/repos/ran-booster-scenarios/scenario-theme/hooks',
				'https://api.github.com/orgs/ran-booster-scenarios/hooks',
			) as $url ) {
				$this->assert_blocked( ran_booster_scenarios_http( false, array(), $url ) );
			}
			$this->assert_blocked( ran_booster_scenarios_http( false, array( 'method' => 'POST' ), 'https://api.github.com/repos/ran-booster-scenarios/scenario-theme/releases' ) );
		}
	}

	/**
	 * Verify each release scenario matches its observable provider outcome.
	 */
	public function test_each_release_scenario_matches_its_observable_provider_outcome(): void {
		$this->enable();
		foreach ( array( 'scenario-plugin', 'scenario-theme' ) as $target ) {
			foreach ( array(
				'immutable'    => 200,
				'mutable'      => 200,
				'no-release'   => 404,
				'malformed'    => 200,
				'unavailable'  => 503,
				'denied'       => 403,
				'rate-limited' => 429,
			) as $scenario => $status ) {
				$this->select( $scenario );
				$response = $this->request( $target );
				self::assertIsArray( $response );
				self::assertSame( $status, $response['response']['code'] );
				if ( in_array( $scenario, array( 'immutable', 'mutable' ), true ) ) {
					$release = json_decode( $response['body'], true, 512, JSON_THROW_ON_ERROR );
					self::assertSame( 'immutable' === $scenario, $release['immutable'] );
					self::assertSame( 'v2.0.0', $release['tag_name'] );
				} elseif ( 'malformed' === $scenario ) {
					self::assertNull( json_decode( $response['body'] ) );
				} elseif ( 'rate-limited' === $scenario ) {
					self::assertSame( '60', $response['headers']['retry-after'] );
				}
			}
		}
	}

	/**
	 * Verify stage changes are explicit repeatable and do not consume history.
	 */
	public function test_stage_changes_are_explicit_repeatable_and_do_not_consume_history(): void {
		$this->enable();
		foreach ( array( 'becomes-immutable', 'becomes-mutable', 'offer-disappears' ) as $scenario ) {
			$this->select( $scenario );
			$first = $this->request();
			self::assertSame( $first, $this->request() );
			$this->submit( array( 'operation' => 'advance' ) );
			self::assertSame( 1, ran_booster_scenarios_state()['stage'] );
			$second = $this->request();
			self::assertNotSame( $first, $second );
			self::assertSame( $second, $this->request() );
			$this->submit(
				array(
					'operation' => 'select',
					'scenario'  => $scenario,
					'target'    => 'both',
				)
			);
			self::assertSame( $first, $this->request() );
		}
	}

	/**
	 * Verify target selection and invalid persisted state fail closed.
	 */
	public function test_target_selection_and_invalid_persisted_state_fail_closed(): void {
		$this->enable();
		$this->submit(
			array(
				'operation' => 'select',
				'scenario'  => 'immutable',
				'target'    => 'theme',
			)
		);
		self::assertFalse( $GLOBALS['ran_booster_scenario_autoload'] );
		self::assertIsArray( $this->request( 'scenario-theme' ) );
		$this->assert_blocked( $this->request( 'scenario-plugin' ) );
		foreach ( array(
			array(
				'scenario' => 'unknown',
				'stage'    => 0,
				'target'   => 'both',
			),
			array(
				'scenario' => 'immutable',
				'stage'    => '1',
				'target'   => 'both',
			),
			array(
				'scenario' => 'immutable',
				'stage'    => 2,
				'target'   => 'both',
			),
			array(
				'scenario' => 'immutable',
				'stage'    => 0,
				'target'   => 'arbitrary',
			),
		) as $state ) {
			$GLOBALS['ran_booster_scenario_options']['ran_booster_scenarios_state'] = $state;
			$this->assert_blocked( $this->request() );
		}
	}

	/**
	 * Verify missing release provenance blocks even matching headers.
	 */
	public function test_missing_release_provenance_blocks_even_matching_headers(): void {
		$this->enable();
		$this->select( 'immutable' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Remove the test-owned provenance marker to model a source checkout.
		unlink( $this->directory . '/ran-booster/ran-booster-release.json' );
		$this->assert_blocked( $this->request() );
	}

	/**
	 * Verify archive metadata matches bundled bytes and streaming is confined.
	 */
	public function test_archive_metadata_matches_bundled_bytes_and_streaming_is_confined(): void {
		$this->enable();
		$this->select( 'immutable' );
		foreach ( array( 'scenario-plugin', 'scenario-theme' ) as $target ) {
			$release = $this->request( $target );
			self::assertIsArray( $release );
			$metadata = json_decode( $release['body'], true, 512, JSON_THROW_ON_ERROR );
			$asset    = $metadata['assets'][0];
			$response = ran_booster_scenarios_http( false, array(), $asset['browser_download_url'] );
			self::assertIsArray( $response );
			self::assertSame( 200, $response['response']['code'] );
			self::assertSame( $asset['size'], strlen( $response['body'] ) );
			self::assertSame( $asset['digest'], 'sha256:' . hash( 'sha256', $response['body'] ) );
			$temp = tempnam( sys_get_temp_dir(), 'ran-scenario-stream-' );
			self::assertIsString( $temp );
			try {
				$stream = ran_booster_scenarios_http(
					false,
					array(
						'stream'   => true,
						'filename' => $temp,
					),
					$asset['browser_download_url']
				);
				self::assertIsArray( $stream );
				self::assertSame( '', $stream['body'] );
				self::assertSame( hash( 'sha256', $response['body'] ), hash_file( 'sha256', $temp ) );
				$outside = $this->directory . '/ran-booster/ran-booster.php';
				$before  = hash_file( 'sha256', $outside );
				$this->assert_blocked(
					ran_booster_scenarios_http(
						false,
						array(
							'stream'   => true,
							'filename' => $outside,
						),
						$asset['browser_download_url']
					)
				);
				self::assertSame( $before, hash_file( 'sha256', $outside ) );
				$link = $this->directory . '/ran-booster/stream-link';
				symlink( $temp, $link );
				$this->assert_blocked(
					ran_booster_scenarios_http(
						false,
						array(
							'stream'   => true,
							'filename' => $link,
						),
						$asset['browser_download_url']
					)
				);
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Remove this test's private symbolic link only.
				unlink( $link );
			} finally {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Remove this test's native temporary stream file.
				unlink( $temp );
			}
		}
	}

	/**
	 * Verify reset preserves booster records and logging settings.
	 */
	public function test_reset_preserves_booster_records_and_logging_settings(): void {
		$this->enable();
		$this->select( 'immutable' );
		$GLOBALS['ran_booster_scenario_options']['ran_booster_logging_enabled'] = false;
		$GLOBALS['ran_booster_scenario_options']['ran_booster_history']         = array( 'existing' );
		$this->submit( array( 'operation' => 'reset' ) );
		self::assertSame( '', ran_booster_scenarios_state()['scenario'] );
		self::assertSame(
			array(
				'ran_booster_logging_enabled' => false,
				'ran_booster_history'         => array( 'existing' ),
			),
			$GLOBALS['ran_booster_scenario_options']
		);
	}

	/**
	 * Verify get request cannot mutate fixture selection.
	 */
	public function test_get_request_cannot_mutate_fixture_selection(): void {
		$this->enable();
		$this->select( 'immutable' );
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_POST                     = array( 'operation' => 'reset' );
		try {
			ran_booster_scenarios_save();
			self::fail( 'GET mutation accepted.' );
		} catch ( RuntimeException $error ) {
			self::assertSame( 'POST required.', $error->getMessage() );
		}
		self::assertSame( 'immutable', ran_booster_scenarios_state()['scenario'] );
	}

	/**
	 * Verify permission and nonce fail before mutation.
	 */
	public function test_permission_and_nonce_fail_before_mutation(): void {
		$this->enable();
		$this->select( 'immutable' );
		$original = $GLOBALS['ran_booster_scenario_options'];
		foreach ( array( 'ran_booster_scenario_admin', 'ran_booster_scenario_nonce' ) as $ran_booster_scenario_guard ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Both selected global names use the approved ran_booster prefix.
			$GLOBALS[ $ran_booster_scenario_guard ] = false;
			$_POST                                  = array( 'operation' => 'reset' );
			try {
				ran_booster_scenarios_save();
				self::fail( 'Guard did not reject mutation.' );
			} catch ( RuntimeException $error ) {
				self::assertStringNotContainsString( 'https://', $error->getMessage() );
			}
			self::assertSame( $original, $GLOBALS['ran_booster_scenario_options'] );
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Both selected global names use the approved ran_booster prefix.
			$GLOBALS[ $ran_booster_scenario_guard ] = true;
		}
	}

	/**
	 * Verify routes are read only local admin destinations and fixture is not shipped.
	 */
	public function test_routes_are_read_only_local_admin_destinations_and_fixture_is_not_shipped(): void {
		foreach ( ran_booster_scenarios_routes() as $route ) {
			self::assertMatchesRegularExpression( '/^[a-z-]+\.php(?:\?|$)/', $route );
			self::assertStringNotContainsString( 'action=', $route );
			self::assertStringNotContainsString( '://', $route );
			self::assertStringNotContainsString( '..', $route );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Inspect the explicit standalone runtime manifest.
		$manifest = file_get_contents( dirname( __DIR__ ) . '/release-contents.txt' );
		self::assertIsString( $manifest );
		self::assertDoesNotMatchRegularExpression( '/^tests(?:\/|$)/m', $manifest );
	}

	/**
	 * Verify real bundled provider resolves identity branches and raw commit revisions.
	 */
	public function test_real_bundled_provider_resolves_identity_branches_and_raw_commit_revisions(): void {
		$this->enable();
		$this->select( 'immutable' );
		$credentials = $this->createMock( \RAN\RepositoryProvider\ProviderCredentialStore::class );
		$credentials->expects( self::never() )->method( 'credential_material' );
		$browser = new \RAN\BoosterGitHubProvider\V1\RepositoryBrowser( $credentials );
		foreach ( array(
			'scenario-plugin' => '940001',
			'scenario-theme'  => '940002',
		) as $target => $identity ) {
			$name = 'ran-booster-scenarios/' . $target;
			self::assertSame( $identity, $browser->repository( $name )->provider_repository_id );
			self::assertSame( str_repeat( 'a', 40 ), $browser->branch_head( $name, 'main', $identity ) );
			foreach ( array( 'main', 'v2.0.0', str_repeat( 'a', 40 ) ) as $ref ) {
				self::assertSame( str_repeat( 'a', 40 ), $browser->immutable_ref( $name, $ref, $identity ) );
			}
		}
	}

	/**
	 * Verify real bundled public browser lists selected targets and later pages are empty.
	 */
	public function test_real_bundled_public_browser_lists_selected_targets_and_later_pages_are_empty(): void {
		$this->enable();
		$this->select( 'immutable' );
		$credentials = $this->createMock( \RAN\RepositoryProvider\ProviderCredentialStore::class );
		$credentials->expects( self::never() )->method( 'credential_material' );
		$browser = new \RAN\BoosterGitHubProvider\V1\RepositoryBrowser( $credentials );
		$result  = $browser->browse( \RAN\RepositoryProvider\RepositoryBrowseRequest::public_owner( 'ran-booster-scenarios' ) );
		self::assertFalse( $result->is_partial() );
		self::assertSame( array( 'ran-booster-scenarios/scenario-plugin', 'ran-booster-scenarios/scenario-theme' ), array_map( static fn ( \RAN\RepositoryProvider\RepositoryDescriptor $repository ): string => $repository->locator, $result->repositories ) );
		$this->submit(
			array(
				'operation' => 'select',
				'scenario'  => 'immutable',
				'target'    => 'theme',
			)
		);
		$result = $browser->browse( \RAN\RepositoryProvider\RepositoryBrowseRequest::public_owner( 'ran-booster-scenarios' ) );
		self::assertCount( 1, $result->repositories );
		self::assertSame( 'ran-booster-scenarios/scenario-theme', $result->repositories[0]->locator );
		$response = ran_booster_scenarios_http( false, array(), 'https://api.github.com/orgs/ran-booster-scenarios/repos?type=public&per_page=30&page=2&sort=full_name' );
		self::assertIsArray( $response );
		self::assertSame( array(), json_decode( $response['body'], true, 512, JSON_THROW_ON_ERROR ) );
	}

	/**
	 * Verify real bundled release service inspects both fixture archives.
	 */
	public function test_real_bundled_release_service_inspects_both_fixture_archives(): void {
		$this->enable();
		$this->select( 'immutable' );
		$loader = require dirname( __DIR__ ) . '/vendor/autoload.php';
		self::assertInstanceOf( \Composer\Autoload\ClassLoader::class, $loader );
		$loader->addPsr4( 'RAN\\WPReleaseUpdater\\V1\\', ran_booster_scenarios_test_core() . '/vendor/ran/wp-release-updater/src/' );
		foreach ( array(
			'plugin' => 940001,
			'theme'  => 940002,
		) as $type => $identity ) {
			$service = new \RAN\WPReleaseUpdater\V1\Provider\GitHub\GitHubReleaseService(
				array(
					'canonical_repository_locator' => 'ran-booster-scenarios/scenario-' . $type,
					'canonical_update_uri'         => 'https://github.com/ran-booster-scenarios/scenario-' . $type,
					'php_runtime_version'          => PHP_VERSION,
					'maximum_artifact_bytes'       => 2000000,
					'release_channel'              => 'stable',
					'stable_repository_identity'   => (string) $identity,
					'target_type'                  => $type,
					'wordpress_runtime_version'    => '7.0',
				)
			);
			self::assertCount( 1, $service->list()['candidates'] );
			$inspection = $service->inspect_prospective( (string) ( $identity + 100 ), 'v2.0.0' );
			self::assertMatchesRegularExpression( '/^v2:[0-9a-f]{64}$/', $inspection->fingerprint_value() );
		}
	}

	/**
	 * Assert blocked.
	 *
	 * @param mixed $response Controlled test input.
	 */
	private function assert_blocked( mixed $response ): void {
		self::assertInstanceOf( \WP_Error::class, $response );
	}

	/** Verify every connected journey exposes executable phases and honest prerequisites. */
	public function test_connected_catalog_has_complete_phases_and_fixed_navigation(): void {
		$journeys = ran_booster_scenarios_journeys();
		self::assertCount( 19, $journeys );
		$ids = array();
		foreach ( $journeys as $key => $journey ) {
			$ids[] = $journey['id'];
			foreach ( array( 'setup', 'action', 'expected', 'reset', 'prerequisite' ) as $phase ) {
				self::assertNotSame( '', trim( $journey[ $phase ] ) );
			}
			self::assertSame( $journey['expected'], ran_booster_scenarios_catalog()[ $key ]['expected'] );
			foreach ( ran_booster_scenarios_journey_routes( $key ) as $route ) {
				self::assertStringStartsWith( 'admin.php?page=ran-booster', $route );
				self::assertStringNotContainsString( 'action=', $route );
				self::assertStringNotContainsString( '://', $route );
				self::assertStringNotContainsString( 'attempt=', $route );
			}
		}
		self::assertCount( 19, array_unique( $ids ) );
		self::assertSame( array(), ran_booster_scenarios_journey_routes( '../../unknown' ) );
		foreach ( ran_booster_scenarios_journey_routes( 'recovery-healthy' ) as $route ) {
			self::assertStringNotContainsString( 'recovery=', $route );
		}
		foreach ( ran_booster_scenarios_journey_routes( 'recovery-review' ) as $route ) {
			self::assertStringContainsString( 'recovery=1&recovery_target=scenario-', $route );
		}
	}

	/** Verify older Core APIs cannot activate or continue a connected recipe. */
	public function test_missing_connected_capability_refuses_selection_and_http(): void {
		$this->enable();
		$this->select( 'immutable' );
		self::assertNotSame( '', ran_booster_scenarios_journey_guard( 'blueprint-partial' ) );
		$this->submit(
			array(
				'operation' => 'select',
				'scenario'  => 'blueprint-partial',
				'target'    => 'both',
			)
		);
		self::assertSame( 'immutable', ran_booster_scenarios_state()['scenario'] );
		$this->select( 'blueprint-partial' );
		$this->assert_blocked( $this->request() );
		$this->submit( array( 'operation' => 'advance' ) );
		self::assertSame( 0, ran_booster_scenarios_state()['stage'] );
		$this->submit( array( 'operation' => 'reset' ) );
		self::assertSame( '', ran_booster_scenarios_state()['scenario'] );
	}

	/** Verify unavailable walkthroughs remain readable without actionable links or writes. */
	public function test_unavailable_walkthrough_instructions_render_without_mutation(): void {
		$this->enable();
		$this->select( 'immutable' );
		$before = $GLOBALS['ran_booster_scenario_options'];
		ob_start();
		ran_booster_scenarios_render_journeys( 'Exact pairing unavailable' );
		$html = ob_get_clean();
		self::assertIsString( $html );
		self::assertStringContainsString( 'BP-01', $html );
		self::assertStringContainsString( 'MIG-02', $html );
		self::assertStringContainsString( 'REC-07', $html );
		self::assertStringContainsString( 'manual', strtolower( $html ) );
		self::assertStringNotContainsString( 'href=', $html );
		self::assertSame( $before, $GLOBALS['ran_booster_scenario_options'] );
	}

	/** Verify public downloads stay within the explicit generated archive inventory. */
	public function test_blueprint_downloads_are_fixed_explicit_release_inputs(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Inspect the reviewed local package allowlist.
		$manifest = file_get_contents( dirname( __DIR__ ) . '/release-contents.txt' );
		self::assertIsString( $manifest );
		self::assertCount( 10, ran_booster_scenarios_blueprint_files() );
		foreach ( ran_booster_scenarios_blueprint_files() as $file ) {
			self::assertMatchesRegularExpression( '/^blueprint-[a-z-]+\.zip$/D', $file );
			self::assertContains( 'archives/' . $file, explode( "\n", $manifest ) );
		}
	}

	/**
	 * Enable.
	 */
	private function enable(): void {
		define( 'RAN_BOOSTER_SCENARIOS_DISPOSABLE', true );
	}

	/**
	 * Select.
	 *
	 * @param string $scenario Controlled test input.
	 */
	private function select( string $scenario ): void {
		$GLOBALS['ran_booster_scenario_options']['ran_booster_scenarios_state'] = array(
			'scenario' => $scenario,
			'stage'    => 0,
			'target'   => 'both',
		);
	}

	/**
	 * Request.
	 *
	 * @param string $target Controlled test input.
	 *
	 * @return mixed Controlled test result.
	 */
	private function request( string $target = 'scenario-plugin' ): mixed {
		return ran_booster_scenarios_http( false, array(), 'https://api.github.com/repos/ran-booster-scenarios/' . $target . '/releases/latest' );
	}

	/**
	 * Exercise the controlled WordPress fixture boundary.
	 *
	 * @param array<string, string> $post */
	private function submit( array $post ): void {
		$_POST = $post;
		try {
			ran_booster_scenarios_save();
			self::fail( 'Expected safe redirect.' );
		} catch ( RuntimeException $error ) {
			self::assertSame( 'https://disposable.example.test/wp-admin/tools.php?page=ran-booster-scenarios', $error->getMessage() );
		}
	}
}
