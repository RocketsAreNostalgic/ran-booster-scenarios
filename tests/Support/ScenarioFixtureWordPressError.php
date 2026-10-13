<?php
/**
 * Isolated scenario fixture behavioral regression support.
 *
 * @package RANBoosterScenarios
 */

declare(strict_types=1);

/**
 * Isolated fixture regression error double.
 */
class WP_Error /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Preserve the host-owned error identity. */ {
	/**
	 * Capture the host error.
	 *
	 * @param string $code Error identity.
	 * @param string $message Error explanation.
	 */
	public function __construct( public string $code, public string $message ) {}
}
