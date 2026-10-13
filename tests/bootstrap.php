<?php
/**
 * Load the fixture's tests against the exact external released Core.
 *
 * @package RANBoosterScenarios
 */

declare(strict_types=1);

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

/**
 * Return the verified external Core directory.
 *
 * @return string Absolute host path.
 * @throws RuntimeException When the host is missing or incompatible.
 */
function ran_booster_scenarios_test_core(): string {
	$directory = getenv( 'RAN_BOOSTER_CORE_DIR' );
	if ( false === $directory || ! is_dir( $directory ) ) {
		throw new RuntimeException( 'Set RAN_BOOSTER_CORE_DIR to the extracted exact compatible Core release.' );
	}
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Verify external local release metadata before loading its symbols.
	$metadata = file_get_contents( $directory . '/ran-booster-release.json' );
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read the reviewed local compatibility contract.
	$contract = file_get_contents( dirname( __DIR__ ) . '/compatibility.json' );
	$metadata = false === $metadata ? null : json_decode( $metadata, true, 512, JSON_THROW_ON_ERROR );
	$contract = false === $contract ? null : json_decode( $contract, true, 512, JSON_THROW_ON_ERROR );
	if ( ! is_array( $metadata ) || ! is_array( $contract ) || ( $metadata['version'] ?? null ) !== ( $contract['core_version'] ?? null ) || ( $metadata['commit'] ?? null ) !== ( $contract['core_commit'] ?? null ) ) {
		throw new RuntimeException( 'The external Core release must match compatibility.json exactly.' );
	}
	return $directory;
}

$ran_booster_scenarios_loader = new \Composer\Autoload\ClassLoader();
$ran_booster_scenarios_core   = ran_booster_scenarios_test_core();
$ran_booster_scenarios_loader->addPsr4( 'RAN\\', $ran_booster_scenarios_core . '/RAN/' );
$ran_booster_scenarios_loader->addPsr4( 'RAN\\BoosterGitHubProvider\\V1\\', $ran_booster_scenarios_core . '/vendor/ran/booster-github-provider/src/' );
$ran_booster_scenarios_loader->addPsr4( 'RAN\\WPReleaseUpdater\\V1\\', $ran_booster_scenarios_core . '/vendor/ran/wp-release-updater/src/' );
$ran_booster_scenarios_loader->addPsr4( 'RAN\\UpdaterSupport\\V1\\', $ran_booster_scenarios_core . '/vendor/ran/updater-support/src/' );
$ran_booster_scenarios_loader->register();

/**
 * Read the reviewed version and API contract used by tests.
 *
 * @return array<string, mixed> Compatibility record.
 * @throws RuntimeException When the contract is not an object.
 */
function ran_booster_scenarios_test_contract(): array {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read only the reviewed local compatibility metadata.
	$source   = file_get_contents( dirname( __DIR__ ) . '/compatibility.json' );
	$contract = false === $source ? null : json_decode( $source, true, 512, JSON_THROW_ON_ERROR );
	if ( ! is_array( $contract ) ) {
		throw new RuntimeException( 'Compatibility metadata is invalid.' );
	}
	return $contract;
}
