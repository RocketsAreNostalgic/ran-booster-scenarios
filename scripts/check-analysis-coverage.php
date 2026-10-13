<?php
/**
 * Verify actual maintained PHP selection and analyze executable PHP snippets.
 *
 * @package RanPlugin
 */

declare(strict_types = 1);
$ran_booster_scenarios_root = dirname( __DIR__ );
try {
	require_once $ran_booster_scenarios_root . '/vendor/autoload.php';
	$ran_booster_scenarios_container = ( new \PHPStan\DependencyInjection\ContainerFactory( $ran_booster_scenarios_root ) )->create( $ran_booster_scenarios_root . '/vendor/ran-scenarios-analysis-cache/coverage', array( $ran_booster_scenarios_root . '/phpstan.neon' ), array() );
	if ( 8 !== (int) $ran_booster_scenarios_container->getParameter( 'level' ) || false !== $ran_booster_scenarios_container->getParameter( 'treatPhpDocTypesAsCertain' ) || 80400 !== $ran_booster_scenarios_container->getParameter( 'phpVersion' ) ) {
		throw new RuntimeException( 'Maintained PHP requires Level 8, unchanged PHPDoc trust and PHP 8.4.' );
	}
	// Compare extension-provided stubs and runtime bootstraps with the unchanged locked extension, rather than admitting new executable configuration.
	$ran_booster_scenarios_defaults = ( new \PHPStan\DependencyInjection\ContainerFactory( $ran_booster_scenarios_root ) )->create( $ran_booster_scenarios_root . '/vendor/ran-scenarios-analysis-cache/defaults', array( $ran_booster_scenarios_root . '/vendor/szepeviktor/phpstan-wordpress/extension.neon' ), array() );
	foreach ( array( 'ignoreErrors', 'stubFiles', 'excludePaths', 'bootstrapFiles' ) as $ran_booster_scenarios_parameter ) {
		if ( $ran_booster_scenarios_defaults->getParameter( $ran_booster_scenarios_parameter ) !== $ran_booster_scenarios_container->getParameter( $ran_booster_scenarios_parameter ) ) {
			throw new RuntimeException( 'Analysis exclusions, stubs, suppressions or executable bootstraps require review.' );
		}
	}
	// The actual per-file profiles must preserve the same blocking floor and default exclusions.
	foreach ( array( 'phpstan-file.neon', 'phpstan-tests.neon' ) as $ran_booster_scenarios_profile ) {
		$ran_booster_scenarios_effective = ( new \PHPStan\DependencyInjection\ContainerFactory( $ran_booster_scenarios_root ) )->create( $ran_booster_scenarios_root . '/vendor/ran-scenarios-analysis-cache/' . $ran_booster_scenarios_profile, array( $ran_booster_scenarios_root . '/' . $ran_booster_scenarios_profile ), array() );
		if ( 8 !== (int) $ran_booster_scenarios_effective->getParameter( 'level' ) || false !== $ran_booster_scenarios_effective->getParameter( 'treatPhpDocTypesAsCertain' ) || 80400 !== $ran_booster_scenarios_effective->getParameter( 'phpVersion' ) ) {
			throw new RuntimeException( 'Maintained PHP requires Level 8, unchanged PHPDoc trust and PHP 8.4.' );
		}
		foreach ( array( 'ignoreErrors', 'stubFiles', 'excludePaths' ) as $ran_booster_scenarios_parameter ) {
			if ( $ran_booster_scenarios_defaults->getParameter( $ran_booster_scenarios_parameter ) !== $ran_booster_scenarios_effective->getParameter( $ran_booster_scenarios_parameter ) ) {
				throw new RuntimeException( 'Analysis exclusions, stubs and suppressions require review.' );
			}
		}
	}
	$ran_booster_scenarios_selected        = $ran_booster_scenarios_container->getService( 'fileFinderAnalyse' )->findFiles( $ran_booster_scenarios_container->getParameter( 'paths' ) )->getFiles();
	$ran_booster_scenarios_directory       = new RecursiveDirectoryIterator( $ran_booster_scenarios_root, FilesystemIterator::SKIP_DOTS );
	$ran_booster_scenarios_filter          = new RecursiveCallbackFilterIterator(
		$ran_booster_scenarios_directory,
		static function ( SplFileInfo $file ) use ( $ran_booster_scenarios_root ): bool {
			$relative = substr( $file->getPathname(), strlen( $ran_booster_scenarios_root ) + 1 );
			if ( in_array( $relative, array( '.git', '.dex', '.agents', '.codex', 'vendor', 'node_modules', 'build', '.phpunit.cache' ), true ) || '__pycache__' === $file->getFilename() ) {
				return false;
			}
			if ( $file->isLink() ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- This is a standalone CLI source path diagnostic, not rendered HTML.
				throw new RuntimeException( 'Maintained source link requires review: ' . $relative );
			}
			return true;
		}
	);
	$ran_booster_scenarios_count           = 0;
	$ran_booster_scenarios_snippets        = array();
	$ran_booster_scenarios_xml_declaration = '~\A(?:\xEF\xBB\xBF)?<\?xml[ \t\r\n]+version[ \t\r\n]*=[ \t\r\n]*(?:"1\.[01]"|\'1\.[01]\')(?:[ \t\r\n]+encoding[ \t\r\n]*=[ \t\r\n]*(?:"[A-Za-z][A-Za-z0-9._-]*"|\'[A-Za-z][A-Za-z0-9._-]*\'))?(?:[ \t\r\n]+standalone[ \t\r\n]*=[ \t\r\n]*(?:"(?:yes|no)"|\'(?:yes|no)\'))?[ \t\r\n]*\?>~';
	// Native PHP tokens distinguish executed code from strings and XML data without executing source.
	$ran_booster_scenarios_classify = static function ( string $source, string $origin ) use ( $ran_booster_scenarios_xml_declaration ): bool {
		$inspection = preg_replace( $ran_booster_scenarios_xml_declaration, '', $source );
		if ( null === $inspection ) {
			throw new RuntimeException( 'Cannot classify maintained source.' );
		}
		$has_php = false;
		foreach ( token_get_all( $inspection ) as $token ) {
			if ( ! is_array( $token ) ) {
				continue;
			}
			if ( ( T_INLINE_HTML === $token[0] && str_contains( $token[1], '<?' ) ) || ( T_OPEN_TAG === $token[0] && ! str_starts_with( strtolower( $token[1] ), '<?php' ) ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Standalone CLI diagnostic includes the actual maintained source origin, not HTML.
				throw new RuntimeException( 'Unsupported PHP opening tag requires review: ' . $origin );
			}
			if ( T_OPEN_TAG === $token[0] || T_OPEN_TAG_WITH_ECHO === $token[0] ) {
				$has_php = true;
			}
		}
		return $has_php;
	};
	foreach ( new RecursiveIteratorIterator( $ran_booster_scenarios_filter ) as $ran_booster_scenarios_file ) {
		if ( ! $ran_booster_scenarios_file->isFile() ) {
			continue;
		}
		$ran_booster_scenarios_path      = $ran_booster_scenarios_file->getPathname();
		$ran_booster_scenarios_relative  = substr( $ran_booster_scenarios_path, strlen( $ran_booster_scenarios_root ) + 1 );
		$ran_booster_scenarios_extension = strtolower( $ran_booster_scenarios_file->getExtension() );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Inspect local maintained source bytes without executing them or loading synthetic WordPress globals.
		$ran_booster_scenarios_source = file_get_contents( $ran_booster_scenarios_path );
		if ( false === $ran_booster_scenarios_source ) {
			throw new RuntimeException( 'Cannot inspect maintained source: ' . $ran_booster_scenarios_relative );
		}
		if ( in_array( $ran_booster_scenarios_extension, array( 'sh', 'yml', 'yaml' ), true ) || preg_match( '/\A#![^\n]*(?:bash|sh)\b/', $ran_booster_scenarios_source ) ) {
			$ran_booster_scenarios_remaining = preg_replace_callback(
				"/php -r '([^']*)'/s",
				static function ( array $match ) use ( &$ran_booster_scenarios_snippets, $ran_booster_scenarios_relative ): string {
					$ran_booster_scenarios_snippets[] = array(
						'source' => '<?php' . "\n" . $match[1],
						'origin' => $ran_booster_scenarios_relative,
					);
					return '';
				},
				$ran_booster_scenarios_source
			);
			if ( null === $ran_booster_scenarios_remaining ) {
				throw new RuntimeException( 'Cannot inspect inline PHP.' );
			}
			$ran_booster_scenarios_remaining = preg_replace_callback(
				"/<<'([A-Z_]+)'\\R(.*?)\\R\\1(?:\\R|$)/s",
				static function ( array $match ) use ( &$ran_booster_scenarios_snippets, $ran_booster_scenarios_relative, $ran_booster_scenarios_classify ): string {
					if ( $ran_booster_scenarios_classify( $match[2], $ran_booster_scenarios_relative ) ) {
						$ran_booster_scenarios_snippets[] = array(
							'source' => $match[2],
							'origin' => $ran_booster_scenarios_relative,
						);
						return '';
					}
					return $match[2];
				},
				$ran_booster_scenarios_remaining
			);
			if ( null === $ran_booster_scenarios_remaining ) {
				throw new RuntimeException( 'Cannot inspect PHP heredocs.' );
			}
			// The existing parser regression deliberately writes invalid PHP and must still fail composer lint:syntax.
			if ( 'tests/php-quality-contract.sh' === $ran_booster_scenarios_relative ) {
				$ran_booster_scenarios_remaining = str_replace( "printf '<?php function broken( {\\n' > \"\$fixture\"", '', $ran_booster_scenarios_remaining );
			}
			if ( preg_match( '/(?:^|\R|[;&|])\s*(?:run:\s*|if\s+|then\s+)?php\b[^\n]*(?:\s-[A-Za-z]*[rBRE]\b|\s--(?:run|process-begin|process-code|process-end)\b)|<\?/', $ran_booster_scenarios_remaining ) ) {
				throw new RuntimeException( 'Embedded PHP form requires review: ' . $ran_booster_scenarios_relative );
			}
			continue;
		}
		$ran_booster_scenarios_has_php = $ran_booster_scenarios_classify( $ran_booster_scenarios_source, $ran_booster_scenarios_relative );
		if ( in_array( $ran_booster_scenarios_extension, array( 'php', 'phtml', 'inc' ), true ) || $ran_booster_scenarios_has_php ) {
			if ( ! in_array( $ran_booster_scenarios_path, $ran_booster_scenarios_selected, true ) ) {
				throw new RuntimeException( 'PHPStan does not directly select maintained PHP: ' . $ran_booster_scenarios_relative );
			}
			++$ran_booster_scenarios_count;
		}
	}
	if ( 0 === $ran_booster_scenarios_count || array() === $ran_booster_scenarios_snippets ) {
		throw new RuntimeException( 'Maintained PHP or embedded PHP discovery was empty.' );
	}
	$ran_booster_scenarios_temporary = sys_get_temp_dir() . '/ran-scenarios-analysis-' . bin2hex( random_bytes( 8 ) );
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- Create the private owned temporary analysis layout; it is outside distributed source and removed after the child exits.
	if ( ! mkdir( $ran_booster_scenarios_temporary, 0700 ) ) {
		throw new RuntimeException( 'Cannot create embedded analysis directory.' );
	}
	try {
		$ran_booster_scenarios_paths = array();
		foreach ( $ran_booster_scenarios_snippets as $ran_booster_scenarios_index => $ran_booster_scenarios_snippet ) {
			$ran_booster_scenarios_extracted = $ran_booster_scenarios_temporary . '/snippet-' . $ran_booster_scenarios_index . '.php';
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Analyze the exact extracted executable snippet bytes; never execute their bodies or repair malformed inputs.
			if ( false === file_put_contents( $ran_booster_scenarios_extracted, $ran_booster_scenarios_snippet['source'] ) ) {
				throw new RuntimeException( 'Cannot write embedded analysis source.' );
			}
			$ran_booster_scenarios_paths[] = $ran_booster_scenarios_extracted;
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Emit CLI origin mapping for actual checker diagnostics, not HTML.
			echo 'Embedded PHP: ' . $ran_booster_scenarios_snippet['origin'] . ' -> ' . basename( $ran_booster_scenarios_extracted ) . PHP_EOL;
		}
		$ran_booster_scenarios_status = 0;
		// Each snippet has its own runtime symbol world; one process cannot satisfy another process's missing declarations.
		foreach ( $ran_booster_scenarios_paths as $ran_booster_scenarios_path ) {
			$ran_booster_scenarios_configuration  = "includes:\n\t- " . json_encode( $ran_booster_scenarios_root . '/vendor/szepeviktor/phpstan-wordpress/extension.neon', JSON_THROW_ON_ERROR ) . "\nparameters:\n\tlevel: 8\n\tphpVersion: 80400\n\ttreatPhpDocTypesAsCertain: false\n\tpaths:\n";
			$ran_booster_scenarios_configuration .= "\t\t- " . json_encode( $ran_booster_scenarios_path, JSON_THROW_ON_ERROR ) . "\n";
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Write only the private checker configuration containing the unchanged locked WordPress extension and exact extracted paths.
			if ( false === file_put_contents( $ran_booster_scenarios_temporary . '/phpstan.neon', $ran_booster_scenarios_configuration ) ) {
				throw new RuntimeException( 'Cannot write embedded analysis configuration.' );
			}
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open -- Run the locked checker with an argument vector and propagate its real child exit status.
			$ran_booster_scenarios_process = proc_open( array( PHP_BINARY, $ran_booster_scenarios_root . '/vendor/bin/phpstan', 'analyse', '--configuration=' . $ran_booster_scenarios_temporary . '/phpstan.neon', '--no-progress', '--memory-limit=1G' ), array( STDIN, STDOUT, STDERR ), $ran_booster_scenarios_pipes );
			if ( ! is_resource( $ran_booster_scenarios_process ) ) {
				throw new RuntimeException( 'Cannot start embedded PHP analysis.' );
			}
			if ( 0 !== proc_close( $ran_booster_scenarios_process ) ) {
				$ran_booster_scenarios_status = 1;
			}
		}
	} finally {
		foreach ( new DirectoryIterator( $ran_booster_scenarios_temporary ) as $ran_booster_scenarios_entry ) {
			if ( ! $ran_booster_scenarios_entry->isDot() ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Remove only files in the owned private extraction directory after the checker child has exited.
				unlink( $ran_booster_scenarios_entry->getPathname() );
			}
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Remove the now-empty owned private extraction directory.
		rmdir( $ran_booster_scenarios_temporary );
	}
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Emit CLI population evidence, not HTML.
	echo 'Maintained PHP direct analysis: ' . $ran_booster_scenarios_count . ' files; embedded PHP: ' . count( $ran_booster_scenarios_snippets ) . ' snippets.' . PHP_EOL;
	exit( 0 === $ran_booster_scenarios_status ? 0 : 1 );
} catch ( Throwable $ran_booster_scenarios_error ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Emit standalone checker diagnostics to STDERR, not rendered HTML.
	fwrite( STDERR, $ran_booster_scenarios_error->getMessage() . PHP_EOL );
	exit( 1 );
}
