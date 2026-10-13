<?php
/**
 * Analyze each maintained PHP file in its isolated symbol world.
 *
 * @package RANBoosterScenarios
 */

declare(strict_types=1);

require dirname( __DIR__ ) . '/vendor/autoload.php';
$ran_booster_scenarios_root = dirname( __DIR__ );
// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_passthru -- Run the reviewed local coverage checker and retain its exit status.
passthru( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __DIR__ . '/check-analysis-coverage.php' ), $ran_booster_scenarios_status );
if ( 0 !== $ran_booster_scenarios_status ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Integer process exit status, not rendered content.
	exit( $ran_booster_scenarios_status );
}
$ran_booster_scenarios_container = ( new \PHPStan\DependencyInjection\ContainerFactory( $ran_booster_scenarios_root ) )->create( $ran_booster_scenarios_root . '/vendor/analysis-cache', array( $ran_booster_scenarios_root . '/phpstan.neon' ), array() );
$ran_booster_scenarios_files     = $ran_booster_scenarios_container->getService( 'fileFinderAnalyse' )->findFiles( $ran_booster_scenarios_container->getParameter( 'paths' ) )->getFiles();
foreach ( $ran_booster_scenarios_files as $ran_booster_scenarios_file ) {
	// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open -- Isolate test-owned globals and sample plugin worlds in separate real analyzer invocations.
	$ran_booster_scenarios_process = proc_open( array( PHP_BINARY, $ran_booster_scenarios_root . '/vendor/bin/phpstan', 'analyse', '-c', $ran_booster_scenarios_root . '/phpstan-file.neon', '--no-progress', '--memory-limit=1G', $ran_booster_scenarios_file ), array( STDIN, STDOUT, STDERR ), $ran_booster_scenarios_pipes );
	if ( ! is_resource( $ran_booster_scenarios_process ) ) {
		throw new RuntimeException( 'Could not start the locked analyzer.' );
	}
	if ( 0 !== proc_close( $ran_booster_scenarios_process ) ) {
		$ran_booster_scenarios_status = 1;
	}
}
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Integer process exit status, not rendered content.
exit( $ran_booster_scenarios_status );
