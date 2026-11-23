<?php
/**
 * Automatic snippet storage directory resolution.
 *
 * @package    Rf_Site_Functionality
 * @subpackage Rf_Site_Functionality/includes
 */

/**
 * Whether $path is inside open_basedir. An empty restriction allows every path.
 *
 * @param string $path    Absolute path.
 * @param string $basedir open_basedir value.
 * @return bool
 */
function rf_site_functionality_path_allowed( $path, $basedir ) {
	if ( ! is_string( $basedir ) || '' === $basedir ) {
		return true;
	}
	$path = strtolower( str_replace( '\\', '/', $path ) );
	foreach ( explode( PATH_SEPARATOR, $basedir ) as $allowed ) {
		$allowed = strtolower( rtrim( str_replace( '\\', '/', $allowed ), '/' ) );
		if ( '' !== $allowed && ( $path === $allowed || 0 === strpos( $path, $allowed . '/' ) ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Folder name for this site's private snippet directory.
 *
 * The hash keeps two sites on the same machine from sharing one folder.
 *
 * @param string|null $abspath Optional WordPress root path.
 * @return string
 */
function rf_site_functionality_snippet_folder_name( $abspath = null ) {
	$raw  = null === $abspath ? ABSPATH : (string) $abspath;
	$root = strtolower( rtrim( str_replace( '\\', '/', $raw ), '/' ) );
	return 'mc-snippets-' . substr( md5( $root ), 0, 12 );
}

/**
 * Preferred snippet directory, next to WordPress and outside the web root.
 *
 * Preserves the filesystem case of ABSPATH so case-sensitive hosts resolve the
 * real parent directory.
 *
 * @param string|null $abspath Optional WordPress root path.
 * @return string
 */
function rf_site_functionality_sibling_snippets_dir( $abspath = null ) {
	$raw  = null === $abspath ? ABSPATH : (string) $abspath;
	$root = rtrim( str_replace( '\\', '/', $raw ), '/' );
	return dirname( str_replace( '/', DIRECTORY_SEPARATOR, $root ) ) . DIRECTORY_SEPARATOR . rf_site_functionality_snippet_folder_name( $raw );
}

/**
 * Base folder for the automatic in-web-root fallback (wp-content/rf-snippets).
 *
 * @param string|null $wp_content_dir Optional wp-content path.
 * @return string
 */
function rf_site_functionality_wp_content_snippets_base_dir( $wp_content_dir = null ) {
	$content = null === $wp_content_dir ? WP_CONTENT_DIR : (string) $wp_content_dir;
	return rtrim( $content, '/\\' ) . DIRECTORY_SEPARATOR . 'rf-snippets';
}

/**
 * Find an existing 128-bit hex subfolder inside wp-content/rf-snippets or generate a new one.
 *
 * @param string $base_dir Base wp-content/rf-snippets directory.
 * @return string
 */
function rf_site_functionality_wp_content_snippets_subdir( $base_dir ) {
	$base_dir = rtrim( (string) $base_dir, '/\\' );
	if ( is_dir( $base_dir ) ) {
		$entries = scandir( $base_dir );
		if ( is_array( $entries ) ) {
			sort( $entries );
			foreach ( $entries as $entry ) {
				if ( 1 === preg_match( '/^[a-f0-9]{32}$/', $entry ) && is_dir( $base_dir . DIRECTORY_SEPARATOR . $entry ) ) {
					return $base_dir . DIRECTORY_SEPARATOR . $entry;
				}
			}
		}
	}
	return $base_dir . DIRECTORY_SEPARATOR . bin2hex( random_bytes( 16 ) );
}

/**
 * Automatic in-web-root fallback directory, inside a random 128-bit subfolder.
 *
 * @param string|null $wp_content_dir Optional wp-content path.
 * @return string
 */
function rf_site_functionality_wp_content_snippets_dir( $wp_content_dir = null ) {
	$base = rf_site_functionality_wp_content_snippets_base_dir( $wp_content_dir );
	return rf_site_functionality_wp_content_snippets_subdir( $base );
}

/**
 * Move any flat files from wp-content/rf-snippets/ (2.1.0 layout) into the random subfolder.
 *
 * Leaves lockdown files (index.php, .htaccess, web.config) in the base directory.
 *
 * @param string $base_dir Base wp-content/rf-snippets directory.
 * @param string $sub_dir  Random 128-bit subfolder.
 * @return void
 */
function rf_site_functionality_migrate_flat_fallback( $base_dir, $sub_dir ) {
	if ( ! is_dir( $base_dir ) || ! is_dir( $sub_dir ) ) {
		return;
	}
	$entries = scandir( $base_dir );
	if ( ! is_array( $entries ) ) {
		return;
	}
	$keep = array( '.', '..', 'index.php', '.htaccess', 'web.config' );
	foreach ( $entries as $entry ) {
		if ( in_array( $entry, $keep, true ) ) {
			continue;
		}
		$src = $base_dir . DIRECTORY_SEPARATOR . $entry;
		if ( ! is_file( $src ) ) {
			continue;
		}
		$dst = $sub_dir . DIRECTORY_SEPARATOR . $entry;
		if ( ! is_file( $dst ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			@rename( $src, $dst );
		}
	}
}

/**
 * Legacy temp fallback path used by 1.2.1–2.0.0 when open_basedir blocked the sibling.
 *
 * @param string|null $abspath  Optional WordPress root path.
 * @param string|null $temp_dir Optional temp directory.
 * @return string
 */
function rf_site_functionality_temp_snippets_dir( $abspath = null, $temp_dir = null ) {
	$base = null === $temp_dir ? sys_get_temp_dir() : (string) $temp_dir;
	return rtrim( $base, '/\\' ) . DIRECTORY_SEPARATOR . rf_site_functionality_snippet_folder_name( $abspath );
}

/**
 * Whether $path is located inside $parent_dir.
 *
 * @param string $path       Candidate path.
 * @param string $parent_dir Directory to test against.
 * @return bool
 */
function rf_site_functionality_path_is_inside_dir( $path, $parent_dir ) {
	$path_real   = realpath( $path );
	$parent_real = realpath( $parent_dir );
	$norm_path   = rtrim( str_replace( '\\', '/', false !== $path_real ? $path_real : $path ), '/' );
	$norm_parent = rtrim( str_replace( '\\', '/', false !== $parent_real ? $parent_real : $parent_dir ), '/' );
	if ( '' === $norm_path || '' === $norm_parent ) {
		return false;
	}
	if ( DIRECTORY_SEPARATOR === '\\' ) {
		$norm_path   = strtolower( $norm_path );
		$norm_parent = strtolower( $norm_parent );
	}
	return $norm_path === $norm_parent || 0 === strpos( $norm_path, $norm_parent . '/' );
}

/**
 * Test whether a candidate snippet directory is allowed and writable (creating it if needed).
 *
 * @param string $dir     Candidate directory path.
 * @param string $basedir open_basedir value.
 * @return array{ok: bool, reason: string, dir: string}
 */
function rf_site_functionality_can_use_dir( $dir, $basedir ) {
	if ( ! rf_site_functionality_path_allowed( $dir, $basedir ) ) {
		return array(
			'ok'     => false,
			'reason' => 'open_basedir',
			'dir'    => $dir,
		);
	}
	if ( is_dir( $dir ) ) {
		if ( is_writable( $dir ) ) {
			$real = realpath( $dir );
			return array(
				'ok'     => true,
				'reason' => '',
				'dir'    => ( is_string( $real ) && '' !== $real ) ? $real : $dir,
			);
		}
		return array(
			'ok'     => false,
			'reason' => 'dir_not_writable',
			'dir'    => $dir,
		);
	}
	$parent = dirname( $dir );
	if ( ! is_dir( $parent ) || ! is_writable( $parent ) ) {
		return array(
			'ok'     => false,
			'reason' => 'parent_not_writable',
			'dir'    => $dir,
		);
	}
	// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	if ( @mkdir( $dir, 0755, true ) && is_dir( $dir ) && is_writable( $dir ) ) {
		$real = realpath( $dir );
		return array(
			'ok'     => true,
			'reason' => '',
			'dir'    => ( is_string( $real ) && '' !== $real ) ? $real : $dir,
		);
	}
	return array(
		'ok'     => false,
		'reason' => 'mkdir_failed',
		'dir'    => $dir,
	);
}

/**
 * Resolve the best writable snippet storage directory and record how it was chosen.
 *
 * Order:
 * 1. Explicit RF_SITE_FUNCTIONALITY_SNIPPETS_DIR constant (if defined in wp-config.php).
 * 2. Private sibling outside ABSPATH, when allowed by open_basedir and writable.
 * 3. Locked-down wp-content/rf-snippets, when the sibling cannot be used.
 *
 * @param array<string, string> $options Optional overrides for tests.
 * @return array{dir: string, tier: string, fallback_reason: string, inside_web_root: bool, writable: bool}
 */
function rf_site_functionality_resolve_storage( $options = array() ) {
	$abspath        = isset( $options['abspath'] ) ? (string) $options['abspath'] : ABSPATH;
	$wp_content_dir = isset( $options['wp_content_dir'] ) ? (string) $options['wp_content_dir'] : WP_CONTENT_DIR;
	$basedir        = isset( $options['open_basedir'] ) ? (string) $options['open_basedir'] : (string) ini_get( 'open_basedir' );
	$temp_dir       = isset( $options['temp_dir'] ) ? (string) $options['temp_dir'] : sys_get_temp_dir();
	$custom_dir     = isset( $options['custom_dir'] ) ? trim( (string) $options['custom_dir'] ) : '';

	if ( '' !== $custom_dir ) {
		$custom_dir = rtrim( $custom_dir, '/\\' );
		$inside     = rf_site_functionality_path_is_inside_dir( $custom_dir, $abspath )
			|| rf_site_functionality_path_is_inside_dir( $custom_dir, $wp_content_dir );
		$writable   = ( is_dir( $custom_dir ) && is_writable( $custom_dir ) )
			|| ( ! is_dir( $custom_dir ) && is_dir( dirname( $custom_dir ) ) && is_writable( dirname( $custom_dir ) ) );
		return array(
			'dir'             => $custom_dir,
			'tier'            => 'custom',
			'fallback_reason' => '',
			'inside_web_root' => $inside,
			'writable'        => $writable,
		);
	}

	$sibling       = rf_site_functionality_sibling_snippets_dir( $abspath );
	$fallback_base = rf_site_functionality_wp_content_snippets_base_dir( $wp_content_dir );
	$temp          = rf_site_functionality_temp_snippets_dir( $abspath, $temp_dir );

	$sibling_check = rf_site_functionality_can_use_dir( $sibling, $basedir );
	if ( $sibling_check['ok'] ) {
		if ( is_dir( $temp ) && rf_site_functionality_path_allowed( $temp, $basedir ) ) {
			require_once __DIR__ . '/class-rf-site-functionality-snippet-migration.php';
			Rf_Site_Functionality_Snippet_Migration::adopt_store( $temp, $sibling_check['dir'] );
		}
		return array(
			'dir'             => $sibling_check['dir'],
			'tier'            => 'outside_root',
			'fallback_reason' => '',
			'inside_web_root' => false,
			'writable'        => true,
		);
	}

	$base_check = rf_site_functionality_can_use_dir( $fallback_base, $basedir );
	if ( $base_check['ok'] ) {
		require_once __DIR__ . '/class-rf-site-functionality-snippet-store.php';
		Rf_Site_Functionality_Snippet_Store::write_lockdown_files( $base_check['dir'] );
		$fallback       = rf_site_functionality_wp_content_snippets_subdir( $base_check['dir'] );
		$fallback_check = rf_site_functionality_can_use_dir( $fallback, $basedir );
		if ( $fallback_check['ok'] ) {
			Rf_Site_Functionality_Snippet_Store::write_lockdown_files( $fallback_check['dir'] );
			rf_site_functionality_migrate_flat_fallback( $base_check['dir'], $fallback_check['dir'] );
			if ( is_dir( $temp ) && rf_site_functionality_path_allowed( $temp, $basedir ) ) {
				require_once __DIR__ . '/class-rf-site-functionality-snippet-migration.php';
				Rf_Site_Functionality_Snippet_Migration::adopt_store( $temp, $fallback_check['dir'] );
			}
			return array(
				'dir'             => $fallback_check['dir'],
				'tier'            => 'wp_content',
				'fallback_reason' => $sibling_check['reason'],
				'inside_web_root' => true,
				'writable'        => true,
			);
		}
	}

	return array(
		'dir'             => $fallback_base,
		'tier'            => 'unwritable',
		'fallback_reason' => $sibling_check['reason'],
		'inside_web_root' => true,
		'writable'        => false,
	);
}

/**
 * Return the active storage resolution metadata for Settings and Site Health.
 *
 * @param array<string, mixed>|null $set Optional value to store during bootstrap.
 * @return array{dir: string, tier: string, fallback_reason: string, inside_web_root: bool, writable: bool}
 */
function rf_site_functionality_storage_info( $set = null ) {
	static $info = null;
	if ( is_array( $set ) ) {
		$info = $set;
	}
	if ( null === $info ) {
		$custom = defined( 'RF_SITE_FUNCTIONALITY_SNIPPETS_DIR' ) ? RF_SITE_FUNCTIONALITY_SNIPPETS_DIR : '';
		$info   = rf_site_functionality_resolve_storage( '' !== $custom ? array( 'custom_dir' => $custom ) : array() );
	}
	return $info;
}

/**
 * Snippet directory path resolved automatically for this host.
 *
 * Prefers a private sibling directory outside the web root and falls back to a
 * locked-down wp-content/rf-snippets directory when the parent is not writable.
 *
 * @return string
 */
function rf_site_functionality_private_snippets_dir() {
	$resolved = rf_site_functionality_resolve_storage();
	rf_site_functionality_storage_info( $resolved );
	return $resolved['dir'];
}
