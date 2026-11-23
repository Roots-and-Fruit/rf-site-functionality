<?php
/**
 * Filesystem access for snippet files.
 *
 * @package    Rf_Site_Functionality
 * @subpackage Rf_Site_Functionality/includes
 */

/**
 * Owns the snippet directory, the path jail, and enable/disable renames.
 *
 * @since 1.1.0
 */
class Rf_Site_Functionality_Snippet_Store {

	/**
	 * Snippet directory, without a trailing slash.
	 *
	 * @var string
	 */
	private $dir;

	/**
	 * @param string|null $dir Directory override. Null uses RF_SITE_FUNCTIONALITY_SNIPPETS_DIR.
	 */
	public function __construct( $dir = null ) {
		if ( null === $dir ) {
			$dir = defined( 'RF_SITE_FUNCTIONALITY_SNIPPETS_DIR' ) ? RF_SITE_FUNCTIONALITY_SNIPPETS_DIR : '';
		}
		$this->dir = rtrim( (string) $dir, '/\\' );
	}

	/**
	 * @return string
	 */
	public function get_dir() {
		return $this->dir;
	}

	/**
	 * Internal files inside the snippet directory that are never snippets.
	 *
	 * @var string[]
	 */
	const INTERNAL_FILES = array(
		'index.php',
		'safe-mode-key.php',
		'snippets-disabled.php',
		'rf-lockdown-probe.php',
		'safe-mode.key',
		'snippets-disabled',
		'.htaccess',
		'web.config',
	);

	/**
	 * Write web-access lockdown files (index.php, .htaccess, web.config) into a directory.
	 *
	 * @param string $dir Target directory.
	 * @return bool
	 */
	public static function write_lockdown_files( $dir ) {
		$dir = rtrim( (string) $dir, '/\\' );
		if ( '' === $dir || ! is_dir( $dir ) || ! is_writable( $dir ) ) {
			return false;
		}
		$index = $dir . '/index.php';
		if ( ! is_file( $index ) ) {
			file_put_contents( $index, "<?php\n// Silence is golden.\n" );
		}
		$htaccess         = $dir . '/.htaccess';
		$legacy_htaccess  = "<Files \"*\">\nRequire all denied\n</Files>\n";
		$desired_htaccess = "Options -Indexes\n<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tOrder allow,deny\n\tDeny from all\n</IfModule>\n";
		if ( ! is_file( $htaccess ) || $legacy_htaccess === file_get_contents( $htaccess ) ) {
			file_put_contents( $htaccess, $desired_htaccess );
		}
		$web_config = $dir . '/web.config';
		if ( ! is_file( $web_config ) ) {
			$iis = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
				. "<configuration>\n"
				. "\t<system.webServer>\n"
				. "\t\t<authorization>\n"
				. "\t\t\t<deny users=\"*\" />\n"
				. "\t\t</authorization>\n"
				. "\t</system.webServer>\n"
				. "</configuration>\n";
			file_put_contents( $web_config, $iis );
		}
		return true;
	}

	/**
	 * Create the directory if it is missing and write web-access lockdown files.
	 *
	 * @return bool
	 */
	public function ensure_dir() {
		if ( '' === $this->dir ) {
			return false;
		}
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! is_dir( $this->dir ) && ! @mkdir( $this->dir, 0755, true ) ) {
			return false;
		}
		if ( ! is_writable( $this->dir ) ) {
			return false;
		}
		return self::write_lockdown_files( $this->dir );
	}

	/**
	 * PHP-guarded safe-mode key file so Nginx/PHP-FPM cannot serve the secret.
	 *
	 * @return string
	 */
	public function key_path() {
		return $this->dir . '/safe-mode-key.php';
	}

	/**
	 * Legacy plain-text safe-mode key path (pre-2.1.0).
	 *
	 * @return string
	 */
	public function legacy_key_path() {
		return $this->dir . '/safe-mode.key';
	}

	/**
	 * PHP-guarded safe-mode flag path.
	 *
	 * @return string
	 */
	public function flag_path() {
		return $this->dir . '/snippets-disabled.php';
	}

	/**
	 * Legacy plain-text safe-mode flag path (pre-2.2.0).
	 *
	 * @return string
	 */
	public function legacy_flag_path() {
		return $this->dir . '/snippets-disabled';
	}

	/**
	 * Read the safe-mode key from the PHP-guarded file or legacy key file.
	 *
	 * @return string
	 */
	public function read_key() {
		$path = $this->key_path();
		if ( is_file( $path ) && is_readable( $path ) ) {
			$raw = (string) file_get_contents( $path );
			if ( preg_match( '/^\/\/\s*([a-f0-9]{16,128})\s*$/mi', $raw, $match ) ) {
				return trim( $match[1] );
			}
		}
		$legacy = $this->legacy_key_path();
		if ( is_file( $legacy ) && is_readable( $legacy ) ) {
			return trim( (string) file_get_contents( $legacy ) );
		}
		return '';
	}

	/**
	 * @return string
	 */
	public function ensure_key() {
		if ( ! $this->ensure_dir() ) {
			return '';
		}
		$path   = $this->key_path();
		$legacy = $this->legacy_key_path();
		if ( is_file( $path ) ) {
			$key = $this->read_key();
			if ( '' !== $key ) {
				if ( is_file( $legacy ) ) {
					// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					@unlink( $legacy );
				}
				return $key;
			}
		}
		$key = '';
		if ( is_file( $legacy ) && is_readable( $legacy ) ) {
			$key = trim( (string) file_get_contents( $legacy ) );
		}
		if ( '' === $key ) {
			$key = bin2hex( random_bytes( 16 ) );
		}
		$payload = "<?php\nexit;\n// " . $key . "\n";
		if ( false !== file_put_contents( $path, $payload ) && is_file( $legacy ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			@unlink( $legacy );
		}
		return $key;
	}

	/**
	 * @return bool
	 */
	public function is_disabled() {
		return is_file( $this->flag_path() ) || is_file( $this->legacy_flag_path() );
	}

	/**
	 * Write the disabled flag when the secret matches the key file.
	 *
	 * @param string $secret Request value.
	 * @return bool Whether the flag was written.
	 */
	public function maybe_disable( $secret ) {
		$key = $this->read_key();
		if ( '' === $key || ! is_string( $secret ) || ! hash_equals( $key, $secret ) ) {
			return false;
		}
		return false !== file_put_contents( $this->flag_path(), "<?php\nexit;\n" );
	}

	/**
	 * Public URL for this directory when it sits inside WP_CONTENT_DIR or ABSPATH.
	 *
	 * @return string Empty string when outside the web root.
	 */
	public function public_url() {
		if ( '' === $this->dir ) {
			return '';
		}
		$real_dir = realpath( $this->dir );
		$norm_dir = rtrim( str_replace( '\\', '/', false !== $real_dir ? $real_dir : $this->dir ), '/' );
		if ( defined( 'WP_CONTENT_DIR' ) && defined( 'WP_CONTENT_URL' ) ) {
			$real_content = realpath( WP_CONTENT_DIR );
			$norm_content = rtrim( str_replace( '\\', '/', false !== $real_content ? $real_content : WP_CONTENT_DIR ), '/' );
			$cmp_dir      = DIRECTORY_SEPARATOR === '\\' ? strtolower( $norm_dir ) : $norm_dir;
			$cmp_content  = DIRECTORY_SEPARATOR === '\\' ? strtolower( $norm_content ) : $norm_content;
			if ( 0 === strpos( $cmp_dir, $cmp_content . '/' ) ) {
				$rel = substr( $norm_dir, strlen( $norm_content ) );
				return rtrim( WP_CONTENT_URL, '/' ) . $rel;
			}
		}
		if ( defined( 'ABSPATH' ) && function_exists( 'site_url' ) ) {
			$real_abs = realpath( ABSPATH );
			$norm_abs = rtrim( str_replace( '\\', '/', false !== $real_abs ? $real_abs : ABSPATH ), '/' );
			$cmp_dir  = DIRECTORY_SEPARATOR === '\\' ? strtolower( $norm_dir ) : $norm_dir;
			$cmp_abs  = DIRECTORY_SEPARATOR === '\\' ? strtolower( $norm_abs ) : $norm_abs;
			if ( 0 === strpos( $cmp_dir, $cmp_abs . '/' ) ) {
				$rel = substr( $norm_dir, strlen( $norm_abs ) );
				return rtrim( site_url(), '/' ) . $rel;
			}
		}
		return '';
	}

	/**
	 * Probe whether direct HTTP requests to this directory are blocked.
	 *
	 * Returns one of: 'outside_root', 'unwritable', 'blocked', 'exposed', 'unverified'.
	 *
	 * @param array<string, mixed>|null $storage_info Optional storage resolution info.
	 * @param bool                      $force        Bypass the transient cache.
	 * @return string
	 */
	public function probe_web_access( $storage_info = null, $force = false ) {
		if ( is_array( $storage_info ) && empty( $storage_info['inside_web_root'] ) ) {
			return 'outside_root';
		}
		$base_url = $this->public_url();
		if ( '' === $base_url ) {
			return 'outside_root';
		}
		if ( ! is_dir( $this->dir ) || ! is_writable( $this->dir ) ) {
			return 'unwritable';
		}
		$cache_key = 'rf_sf_probe2_' . substr( md5( $this->dir ), 0, 12 );
		if ( ! $force && function_exists( 'get_transient' ) ) {
			$cached = get_transient( $cache_key );
			if ( is_string( $cached ) && in_array( $cached, array( 'blocked', 'exposed', 'unverified' ), true ) ) {
				return $cached;
			}
		}
		if ( ! function_exists( 'wp_remote_get' ) ) {
			return 'unverified';
		}
		$token      = 'rf-probe-' . bin2hex( random_bytes( 8 ) );
		$probe_file = $this->dir . '/rf-lockdown-probe.php';
		$probe_body = "<?php\nexit;\n// " . $token . "\n";
		if ( false === file_put_contents( $probe_file, $probe_body ) ) {
			return 'unwritable';
		}
		$status = 'unverified';
		try {
			$response = wp_remote_get(
				$base_url . '/rf-lockdown-probe.php',
				array(
					'timeout'     => 3,
					'redirection' => 0,
					'sslverify'   => false,
				)
			);
			if ( ! is_wp_error( $response ) ) {
				$code = (int) wp_remote_retrieve_response_code( $response );
				$body = (string) wp_remote_retrieve_body( $response );
				if ( 200 === $code && ( false !== strpos( $body, $token ) || false !== strpos( $body, '<?php' ) ) ) {
					$status = 'exposed';
				} elseif ( ( $code >= 400 && $code < 500 ) || 200 === $code ) {
					$status = 'blocked';
				}
			}
		} finally {
			if ( is_file( $probe_file ) ) {
				// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				@unlink( $probe_file );
			}
		}
		if ( function_exists( 'set_transient' ) ) {
			set_transient( $cache_key, $status, HOUR_IN_SECONDS );
		}
		return $status;
	}

	/**
	 * @param array<string, bool> $capabilities Capability map for the current user.
	 * @return bool
	 */
	public static function can_clear_flag( $capabilities ) {
		return is_array( $capabilities ) && ! empty( $capabilities['manage_options'] );
	}

	/**
	 * @param array<string, bool> $capabilities Capability map for the current user.
	 * @return bool
	 */
	public function clear_flag( $capabilities ) {
		if ( ! self::can_clear_flag( $capabilities ) ) {
			return false;
		}
		$ok = true;
		foreach ( array( $this->flag_path(), $this->legacy_flag_path() ) as $path ) {
			if ( is_file( $path ) && ! unlink( $path ) ) {
				$ok = false;
			}
		}
		return $ok;
	}

	/**
	 * Turn safe mode on or off for a user who can manage options.
	 *
	 * @param array<string, bool> $capabilities Capability map for the current user.
	 * @param bool                $disabled     True writes the flag. False clears it.
	 * @return bool
	 */
	public function set_disabled( $capabilities, $disabled ) {
		if ( ! self::can_clear_flag( $capabilities ) ) {
			return false;
		}
		if ( ! $disabled ) {
			return $this->clear_flag( $capabilities );
		}
		$this->ensure_dir();
		$legacy = $this->legacy_flag_path();
		if ( is_file( $legacy ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			@unlink( $legacy );
		}
		$path = $this->flag_path();
		if ( is_file( $path ) ) {
			return true;
		}
		return false !== file_put_contents( $path, "<?php\nexit;\n" );
	}

	/**
	 * Whether a path is the directory itself or a file inside it.
	 *
	 * @param string $path Absolute or relative path.
	 * @return bool
	 */
	public function is_inside( $path ) {
		$real_dir = realpath( $this->dir );
		if ( false === $real_dir ) {
			return false;
		}
		$real_path = realpath( $path );
		if ( false === $real_path ) {
			return false;
		}
		if ( $real_path === $real_dir ) {
			return true;
		}
		$prefix = $real_dir . DIRECTORY_SEPARATOR;
		return 0 === strpos( $real_path, $prefix );
	}

	/**
	 * Basename is a single snippet file name with no directories.
	 *
	 * @param string $filename File name.
	 * @return bool
	 */
	public function is_safe_filename( $filename ) {
		if ( ! is_string( $filename ) || '' === $filename ) {
			return false;
		}
		if ( basename( $filename ) !== $filename ) {
			return false;
		}
		if ( in_array( $filename, self::INTERNAL_FILES, true ) ) {
			return false;
		}
		if ( '.disabled.php' === substr( $filename, -13 ) || '.error.php' === substr( $filename, -10 ) ) {
			return false;
		}
		return 1 === preg_match( '/^[a-zA-Z0-9\-_\.]+\.php(\.disabled)?$/', $filename );
	}

	/**
	 * Enabled PHP snippets. Skips internal files and disabled/error siblings.
	 *
	 * @return string[] Absolute paths.
	 */
	public function enabled_php_files() {
		if ( ! is_dir( $this->dir ) ) {
			return array();
		}
		$paths = glob( $this->dir . DIRECTORY_SEPARATOR . '*.php' );
		if ( ! is_array( $paths ) ) {
			return array();
		}
		$files = array();
		foreach ( $paths as $path ) {
			if ( ! $this->is_loadable_php( $path ) ) {
				continue;
			}
			$files[] = $path;
		}
		return $files;
	}

	/**
	 * @param string $path Absolute path.
	 * @return bool
	 */
	public function is_loadable_php( $path ) {
		$base = basename( $path );
		if ( in_array( $base, self::INTERNAL_FILES, true ) ) {
			return false;
		}
		if ( '.disabled.php' === substr( $base, -13 ) || '.error.php' === substr( $base, -10 ) ) {
			return false;
		}
		if ( ! is_file( $path ) || ! is_readable( $path ) ) {
			return false;
		}
		if ( 'php' !== pathinfo( $path, PATHINFO_EXTENSION ) ) {
			return false;
		}
		$disabled_sibling = substr( $path, 0, -4 ) . '.disabled.php';
		if ( is_file( $disabled_sibling ) || is_file( $path . '.disabled' ) ) {
			return false;
		}
		return $this->is_inside( $path );
	}

	/**
	 * @param string $filename Basename ending in .php.
	 * @return bool
	 */
	public function is_enabled( $filename ) {
		$paths = $this->file_paths( $filename );
		if ( is_wp_error( $paths ) ) {
			return false;
		}
		return $this->is_loadable_php( $paths['enabled'] );
	}

	/**
	 * @param string $filename Basename ending in .php.
	 * @return bool
	 */
	public function enable( $filename ) {
		$paths = $this->file_paths( $filename );
		if ( is_wp_error( $paths ) ) {
			return false;
		}
		if ( ! is_file( $paths['disabled'] ) ) {
			return is_file( $paths['enabled'] );
		}
		if ( ! $this->is_inside( $paths['disabled'] ) ) {
			return false;
		}
		// Ensure direct-access guard is present before enabling.
		$source = file_get_contents( $paths['disabled'] );
		if ( is_string( $source ) ) {
			require_once __DIR__ . '/class-rf-site-functionality-snippet-meta.php';
			$guarded = Rf_Site_Functionality_Snippet_Meta::ensure_guard( $source );
			if ( $guarded !== $source ) {
				file_put_contents( $paths['disabled'], $guarded );
			}
		}
		return rename( $paths['disabled'], $paths['enabled'] );
	}

	/**
	 * @param string $filename Basename ending in .php.
	 * @return bool
	 */
	public function disable( $filename ) {
		$paths = $this->file_paths( $filename );
		if ( is_wp_error( $paths ) ) {
			return false;
		}
		if ( is_file( $paths['disabled'] ) ) {
			return true;
		}
		if ( ! $this->is_loadable_php( $paths['enabled'] ) ) {
			return false;
		}
		return rename( $paths['enabled'], $paths['disabled'] );
	}

	/**
	 * Rename a loaded snippet off and write a PHP-guarded sidecar with the fatal message.
	 *
	 * @param string $path    Absolute path to the .php file.
	 * @param string $message Fatal message.
	 * @param int    $line    Line number.
	 * @return bool
	 */
	public function quarantine( $path, $message, $line ) {
		if ( ! $this->is_inside( $path ) || ! is_file( $path ) ) {
			return false;
		}
		$base = basename( $path );
		if ( in_array( $base, self::INTERNAL_FILES, true ) ) {
			return false;
		}
		if ( 'php' !== pathinfo( $path, PATHINFO_EXTENSION ) || '.disabled.php' === substr( $base, -13 ) || '.error.php' === substr( $base, -10 ) ) {
			return false;
		}
		$stem     = substr( $path, 0, -4 );
		$note     = $stem . '.error.php';
		$clean    = str_replace( array( "\r", "\n" ), ' ', (string) $message );
		file_put_contents( $note, "<?php\nexit;\n// " . $clean . ' on line ' . (int) $line . "\n" );
		$disabled = $stem . '.disabled.php';
		if ( is_file( $disabled ) ) {
			return true;
		}
		return rename( $path, $disabled );
	}

	/**
	 * Migrate legacy .mcphp, .disabled, .error, and snippets-disabled files to PHP-guarded .php names.
	 *
	 * @return int Number of files migrated.
	 */
	public function migrate_legacy_files() {
		if ( '' === $this->dir || ! is_dir( $this->dir ) || ! is_writable( $this->dir ) ) {
			return 0;
		}
		$names = scandir( $this->dir );
		if ( ! is_array( $names ) ) {
			return 0;
		}
		$migrated = 0;
		require_once __DIR__ . '/class-rf-site-functionality-snippet-meta.php';
		foreach ( $names as $name ) {
			if ( '.' === $name || '..' === $name ) {
				continue;
			}
			$path = $this->dir . '/' . $name;
			if ( ! is_file( $path ) || ! $this->is_inside( $path ) ) {
				continue;
			}
			if ( 'snippets-disabled' === $name ) {
				if ( ! is_file( $this->flag_path() ) ) {
					file_put_contents( $this->flag_path(), "<?php\nexit;\n" );
				}
				// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				@unlink( $path );
				++$migrated;
				continue;
			}
			$target_name = self::storage_basename( $name );
			if ( $target_name === $name ) {
				continue;
			}
			$target = $this->dir . '/' . $target_name;
			if ( is_file( $target ) ) {
				continue;
			}
			if ( '.error.php' === substr( $target_name, -10 ) ) {
				$raw   = trim( (string) file_get_contents( $path ) );
				$clean = str_replace( array( "\r", "\n" ), ' ', $raw );
				if ( false !== file_put_contents( $target, "<?php\nexit;\n// " . $clean . "\n" ) ) {
					// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					@unlink( $path );
					++$migrated;
				}
				continue;
			}
			$source = file_get_contents( $path );
			if ( is_string( $source ) ) {
				$guarded = Rf_Site_Functionality_Snippet_Meta::ensure_guard( $source );
				if ( false !== file_put_contents( $target, $guarded ) ) {
					// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
					@unlink( $path );
					++$migrated;
				}
			}
		}
		return $migrated;
	}

	/**
	 * Enabled and disabled snippets for the admin table.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function all_snippets() {
		if ( ! is_dir( $this->dir ) ) {
			return array();
		}
		$snippets = array();
		$php      = glob( $this->dir . DIRECTORY_SEPARATOR . '*.php' );
		if ( is_array( $php ) ) {
			foreach ( $php as $path ) {
				if ( ! $this->is_loadable_php( $path ) ) {
					continue;
				}
				$snippets[] = array(
					'filename' => self::logical_basename( basename( $path ) ),
					'path'     => $path,
					'enabled'  => true,
					'status'   => 'enabled',
				);
			}
		}
		$disabled = glob( $this->dir . DIRECTORY_SEPARATOR . '*.disabled.php' );
		if ( is_array( $disabled ) ) {
			foreach ( $disabled as $path ) {
				if ( ! $this->is_inside( $path ) ) {
					continue;
				}
				$name = self::logical_basename( basename( $path ) );
				if ( in_array( $name, self::INTERNAL_FILES, true ) ) {
					continue;
				}
				$snippets[] = array(
					'filename' => $name,
					'path'     => $path,
					'enabled'  => false,
					'status'   => 'disabled',
				);
			}
		}
		return $snippets;
	}

	/**
	 * Snippet rows for the list ability. Paths stay inside this method.
	 *
	 * @since 1.2.0
	 * @param string $status Optional enabled or disabled filter. Empty lists both.
	 * @return array<int, array<string, string>>|WP_Error
	 */
	public function list_rows( $status = '' ) {
		if ( '' !== $status && ! in_array( $status, array( 'enabled', 'disabled' ), true ) ) {
			return new WP_Error( 'rf_site_functionality_invalid_status', 'Status must be enabled or disabled.' );
		}
		require_once __DIR__ . '/class-rf-site-functionality-snippet-meta.php';
		$rows = array();
		foreach ( $this->all_snippets() as $snippet ) {
			if ( '' !== $status && $snippet['status'] !== $status ) {
				continue;
			}
			$content = '';
			if ( isset( $snippet['path'] ) && is_string( $snippet['path'] ) && is_readable( $snippet['path'] ) ) {
				$loaded = file_get_contents( $snippet['path'] );
				if ( is_string( $loaded ) ) {
					$content = $loaded;
				}
			}
			$meta   = Rf_Site_Functionality_Snippet_Meta::parse( $content );
			$rows[] = array(
				'filename'    => (string) $snippet['filename'],
				'status'      => (string) $snippet['status'],
				'type'        => isset( $meta['type'] ) ? (string) $meta['type'] : 'php',
				'run_context' => isset( $meta['run_context'] ) ? (string) $meta['run_context'] : 'everywhere',
			);
		}
		return $rows;
	}

	/**
	 * Largest snippet source this store will write, in bytes.
	 *
	 * @since 1.2.0
	 * @var int
	 */
	const MAX_SOURCE_BYTES = 262144;

	/**
	 * Read one snippet's source and parsed header.
	 *
	 * @since 1.2.0
	 * @param string $filename Basename, with or without .php.
	 * @return array<string, mixed>|WP_Error
	 */
	public function read( $filename ) {
		$filename = $this->normalize_php_name( $filename );
		if ( is_wp_error( $filename ) ) {
			return $filename;
		}
		$path = $this->existing_path( $filename );
		if ( is_wp_error( $path ) ) {
			return $path;
		}
		$content = file_get_contents( $path );
		if ( false === $content ) {
			return new WP_Error( 'rf_site_functionality_snippet_data_unavailable', 'Unable to read the snippet file.' );
		}
		require_once __DIR__ . '/class-rf-site-functionality-snippet-meta.php';
		return array(
			'filename' => $filename,
			'status'   => $this->is_enabled( $filename ) ? 'enabled' : 'disabled',
			'content'  => $content,
			'header'   => Rf_Site_Functionality_Snippet_Meta::parse( $content ),
		);
	}

	/**
	 * Write a new snippet as a disabled file.
	 *
	 * @since 1.2.0
	 * @param string $filename Basename, with or without .php.
	 * @param string $content  PHP source.
	 * @return array{filename: string, status: string}|WP_Error
	 */
	public function create( $filename, $content ) {
		$filename = $this->normalize_php_name( $filename );
		if ( is_wp_error( $filename ) ) {
			return $filename;
		}
		if ( ! $this->ensure_dir() ) {
			return new WP_Error( 'rf_site_functionality_not_initialized', 'Snippet directory is not available.' );
		}
		$paths    = $this->file_paths( $filename );
		$enabled  = $paths['enabled'];
		$disabled = $paths['disabled'];
		if ( is_file( $enabled ) || is_file( $disabled ) ) {
			return new WP_Error( 'rf_site_functionality_invalid_filename', 'A snippet with this filename already exists.' );
		}
		$content = $this->prepare_source( $content );
		if ( is_wp_error( $content ) ) {
			return $content;
		}
		if ( false === file_put_contents( $disabled, $content ) ) {
			return new WP_Error( 'rf_site_functionality_snippet_data_unavailable', 'Unable to write the snippet file.' );
		}
		if ( ! $this->is_inside( $disabled ) ) {
			unlink( $disabled );
			return new WP_Error( 'rf_site_functionality_invalid_filename', 'Filename must be a single snippet file name.' );
		}
		return array(
			'filename' => $filename,
			'status'   => 'disabled',
		);
	}

	/**
	 * Replace the source of a disabled snippet.
	 *
	 * @since 1.2.0
	 * @param string $filename Basename, with or without .php.
	 * @param string $content  PHP source.
	 * @return array{filename: string, status: string}|WP_Error
	 */
	public function update( $filename, $content ) {
		$filename = $this->normalize_php_name( $filename );
		if ( is_wp_error( $filename ) ) {
			return $filename;
		}
		if ( $this->is_enabled( $filename ) ) {
			return new WP_Error( 'rf_site_functionality_invalid_status', 'Disable the snippet before updating it.' );
		}
		$disabled = $this->file_paths( $filename )['disabled'];
		if ( ! is_file( $disabled ) || ! $this->is_inside( $disabled ) ) {
			return new WP_Error( 'rf_site_functionality_invalid_filename', 'Snippet file was not found.' );
		}
		$content = $this->prepare_source( $content );
		if ( is_wp_error( $content ) ) {
			return $content;
		}
		if ( false === file_put_contents( $disabled, $content ) ) {
			return new WP_Error( 'rf_site_functionality_snippet_data_unavailable', 'Unable to write the snippet file.' );
		}
		return array(
			'filename' => $filename,
			'status'   => 'disabled',
		);
	}

	/**
	 * Remove one snippet file and its error note.
	 *
	 * @since 1.2.0
	 * @param string $filename Basename, with or without .php.
	 * @return array{filename: string, status: string}|WP_Error
	 */
	public function delete( $filename ) {
		$filename = $this->normalize_php_name( $filename );
		if ( is_wp_error( $filename ) ) {
			return $filename;
		}
		$located = $this->file_paths( $filename );
		$paths   = array(
			$located['enabled'],
			$located['disabled'],
			$located['error'],
		);
		$removed = false;
		foreach ( $paths as $path ) {
			if ( ! is_file( $path ) ) {
				continue;
			}
			if ( ! $this->is_inside( $path ) ) {
				return new WP_Error( 'rf_site_functionality_invalid_filename', 'Filename must be a single snippet file name.' );
			}
			if ( ! unlink( $path ) ) {
				return new WP_Error( 'rf_site_functionality_snippet_data_unavailable', 'Unable to delete the snippet file.' );
			}
			$removed = true;
		}
		if ( ! $removed ) {
			return new WP_Error( 'rf_site_functionality_invalid_filename', 'Snippet file was not found.' );
		}
		return array(
			'filename' => $filename,
			'status'   => 'deleted',
		);
	}

	/**
	 * Map a logical snippet name onto its PHP-guarded on-disk file names.
	 *
	 * Every stored file ends in .php with an ABSPATH/exit guard so Nginx/PHP-FPM
	 * returns an empty response instead of serving raw source code.
	 *
	 * @since 1.2.1
	 * @param string $filename Logical or disabled file name.
	 * @return array{logical: string, disk: string, enabled: string, disabled: string, error: string}|WP_Error
	 */
	public function file_paths( $filename ) {
		$logical = $this->normalize_php_name( $filename );
		if ( is_object( $logical ) ) {
			return $logical;
		}
		$stem    = substr( $logical, 0, -4 );
		$enabled = $this->dir . '/' . $logical;
		return array(
			'logical'  => $logical,
			'disk'     => $logical,
			'enabled'  => $enabled,
			'disabled' => $this->dir . '/' . $stem . '.disabled.php',
			'error'    => $this->dir . '/' . $stem . '.error.php',
		);
	}

	/**
	 * Normalize legacy and logical snippet basenames onto PHP-guarded on-disk names.
	 *
	 * example.mcphp -> example.php
	 * example.php.disabled / example.mcphp.disabled -> example.disabled.php
	 * example.php.error / example.mcphp.error -> example.error.php
	 *
	 * @since 1.2.1
	 * @param string $name Basename.
	 * @return string
	 */
	public static function storage_basename( $name ) {
		if ( in_array( $name, self::INTERNAL_FILES, true ) ) {
			return $name;
		}
		if ( '.disabled.php' === substr( $name, -13 ) || '.error.php' === substr( $name, -10 ) ) {
			return $name;
		}
		$suffix = '';
		if ( '.disabled' === substr( $name, -9 ) ) {
			$name   = substr( $name, 0, -9 );
			$suffix = '.disabled.php';
		} elseif ( '.error' === substr( $name, -6 ) ) {
			$name   = substr( $name, 0, -6 );
			$suffix = '.error.php';
		}
		if ( '.mcphp' === substr( $name, -6 ) ) {
			$stem = substr( $name, 0, -6 );
			return '' !== $suffix ? $stem . $suffix : $stem . '.php';
		}
		if ( '.php' === substr( $name, -4 ) ) {
			$stem = substr( $name, 0, -4 );
			return '' !== $suffix ? $stem . $suffix : $stem . '.php';
		}
		return $name . ( '.disabled.php' === $suffix ? '.disabled' : ( '.error.php' === $suffix ? '.error' : '' ) );
	}

	/**
	 * Convert any on-disk snippet name back to logical example.php for the editor and abilities.
	 *
	 * @since 1.2.1
	 * @param string $name Disk basename.
	 * @return string
	 */
	public static function logical_basename( $name ) {
		if ( '.disabled.php' === substr( $name, -13 ) ) {
			return substr( $name, 0, -13 ) . '.php';
		}
		if ( '.error.php' === substr( $name, -10 ) ) {
			return substr( $name, 0, -10 ) . '.php';
		}
		if ( '.mcphp' === substr( $name, -6 ) ) {
			return substr( $name, 0, -6 ) . '.php';
		}
		return $name;
	}

	/**
	 * Basename ending in .php, or an error when the name can leave the directory.
	 *
	 * @since 1.2.0
	 * @param string $filename Requested file name.
	 * @return string|WP_Error
	 */
	private function normalize_php_name( $filename ) {
		if ( ! is_string( $filename ) || '' === trim( $filename ) ) {
			return new WP_Error( 'rf_site_functionality_missing_filename', 'A filename is required.' );
		}
		$filename = str_replace( '\\', '/', $filename );
		if ( basename( $filename ) !== $filename ) {
			return new WP_Error( 'rf_site_functionality_invalid_filename', 'Filename must be a single snippet file name.' );
		}
		if ( '.disabled.php' === substr( $filename, -13 ) ) {
			$filename = substr( $filename, 0, -13 ) . '.php';
		} elseif ( '.error.php' === substr( $filename, -10 ) ) {
			$filename = substr( $filename, 0, -10 ) . '.php';
		}
		if ( '.disabled' === substr( $filename, -9 ) ) {
			$filename = substr( $filename, 0, -9 );
		}
		if ( '.mcphp' === substr( $filename, -6 ) ) {
			$filename = substr( $filename, 0, -6 ) . '.php';
		}
		if ( '.php' !== substr( $filename, -4 ) ) {
			$filename .= '.php';
		}
		if ( ! $this->is_safe_filename( $filename ) ) {
			return new WP_Error( 'rf_site_functionality_invalid_filename', 'Filename must be a single snippet file name.' );
		}
		return $filename;
	}

	/**
	 * Absolute path of the enabled or disabled file.
	 *
	 * @since 1.2.0
	 * @param string $filename Basename ending in .php.
	 * @return string|WP_Error
	 */
	private function existing_path( $filename ) {
		$located  = $this->file_paths( $filename );
		$enabled  = $located['enabled'];
		$disabled = $located['disabled'];
		if ( is_file( $disabled ) && $this->is_inside( $disabled ) ) {
			return $disabled;
		}
		if ( is_file( $enabled ) && $this->is_inside( $enabled ) ) {
			return $enabled;
		}
		return new WP_Error( 'rf_site_functionality_invalid_filename', 'Snippet file was not found.' );
	}

	/**
	 * Guard, lint, and size-check source before it is written.
	 *
	 * @since 1.2.0
	 * @param string $content PHP source.
	 * @return string|WP_Error
	 */
	private function prepare_source( $content ) {
		if ( ! is_string( $content ) ) {
			return new WP_Error( 'rf_site_functionality_missing_content', 'Snippet content is required.' );
		}
		if ( strlen( $content ) > self::MAX_SOURCE_BYTES ) {
			return new WP_Error( 'rf_site_functionality_invalid_content', 'Snippet content must be 256 KB or smaller.' );
		}
		require_once __DIR__ . '/class-rf-site-functionality-snippet-meta.php';
		require_once __DIR__ . '/class-rf-site-functionality-php-lint.php';
		$content = Rf_Site_Functionality_Snippet_Meta::ensure_guard( $content );
		$lint    = Rf_Site_Functionality_Php_Lint::check( $content );
		if ( is_wp_error( $lint ) ) {
			return $lint;
		}
		return $content;
	}

	/**
	 * CSS and JS files that are not disabled.
	 *
	 * @return string[]
	 */
	public function asset_files() {
		if ( ! is_dir( $this->dir ) ) {
			return array();
		}
		$files = array();
		foreach ( array( 'css', 'js' ) as $ext ) {
			$paths = glob( $this->dir . DIRECTORY_SEPARATOR . '*.' . $ext );
			if ( ! is_array( $paths ) ) {
				continue;
			}
			foreach ( $paths as $path ) {
				if ( is_file( $path . '.disabled' ) || ! $this->is_inside( $path ) ) {
					continue;
				}
				$files[] = $path;
			}
		}
		return $files;
	}
}
