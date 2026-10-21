<?php
/**
 * Copy snippets into the storage directory once.
 *
 * @package    Rf_Site_Functionality
 * @subpackage Rf_Site_Functionality/includes
 */

/**
 * Copies legacy plugin snippets into storage without overwriting newer copies.
 *
 * @since 1.1.0
 */
class Rf_Site_Functionality_Snippet_Migration {

	/**
	 * Copy files that are not already in the destination.
	 *
	 * @param string $source Legacy directory.
	 * @param string $dest   Storage directory.
	 * @return string[] Basenames copied.
	 */
	public static function copy_once( $source, $dest ) {
		$copied = array();
		if ( ! is_dir( $source ) ) {
			return $copied;
		}
		if ( ! is_dir( $dest ) && ! mkdir( $dest, 0755, true ) ) {
			return $copied;
		}
		$paths = glob( rtrim( $source, '/\\' ) . '/*' );
		if ( ! is_array( $paths ) ) {
			return $copied;
		}
		foreach ( $paths as $path ) {
			if ( ! is_file( $path ) ) {
				continue;
			}
			$name = basename( $path );
			if ( 'index.php' === $name || 'safe-mode.key' === $name || '.htaccess' === $name ) {
				continue;
			}
			$target = rtrim( $dest, '/\\' ) . '/' . Rf_Site_Functionality_Snippet_Store::storage_basename( $name );
			if ( is_file( $target ) ) {
				continue;
			}
			if ( copy( $path, $target ) ) {
				$copied[] = $name;
			}
		}
		return $copied;
	}

	/**
	 * Remove source snippets whose basename is already in the destination.
	 *
	 * Leaves index.php alone. Live installs should not call this while another
	 * process is still adding files to the legacy folder.
	 *
	 * @param string $source Legacy directory.
	 * @param string $dest   Storage directory.
	 * @return string[] Basenames removed from the source.
	 */
	public static function delete_verified_sources( $source, $dest ) {
		$removed = array();
		if ( ! is_dir( $source ) || ! is_dir( $dest ) ) {
			return $removed;
		}
		$paths = glob( rtrim( $source, '/\\' ) . '/*' );
		if ( ! is_array( $paths ) ) {
			return $removed;
		}
		foreach ( $paths as $path ) {
			if ( ! is_file( $path ) ) {
				continue;
			}
			$name = basename( $path );
			if ( 'index.php' === $name || 'safe-mode.key' === $name || '.htaccess' === $name ) {
				continue;
			}
			$target = rtrim( $dest, '/\\' ) . '/' . Rf_Site_Functionality_Snippet_Store::storage_basename( $name );
			if ( ! is_file( $target ) ) {
				continue;
			}
			if ( unlink( $path ) ) {
				$removed[] = $name;
			}
		}
		return $removed;
	}

	/**
	 * Move the public wp-content/mc-snippets folder out of the web root.
	 *
	 * Copies snippet bodies to .mcphp names, checks the size, then deletes the
	 * public files. The safe-mode key is not copied.
	 *
	 * @since 1.2.1
	 * @param string $public  Old directory inside the web root.
	 * @param string $private New directory outside the web root.
	 * @return bool Whether the public directory is gone or was already absent.
	 */
	public static function relocate_public_snippets( $public, $private ) {
		$public  = rtrim( (string) $public, '/\\' );
		$private = rtrim( (string) $private, '/\\' );
		if ( '' === $public || ! is_dir( $public ) ) {
			return true;
		}
		$public_real = realpath( $public );
		if ( is_dir( $private ) ) {
			$private_real = realpath( $private );
			if ( false !== $public_real && false !== $private_real && $public_real === $private_real ) {
				return false;
			}
		} elseif ( ! mkdir( $private, 0755, true ) ) {
			return false;
		}
		$names = scandir( $public );
		if ( ! is_array( $names ) ) {
			return false;
		}
		$copied = array();
		foreach ( $names as $name ) {
			if ( '.' === $name || '..' === $name ) {
				continue;
			}
			$path = $public . '/' . $name;
			if ( ! is_file( $path ) ) {
				continue;
			}
			if ( in_array( $name, array( 'safe-mode.key', '.htaccess', 'index.php', 'README.md' ), true ) ) {
				continue;
			}
			$target = $private . '/' . Rf_Site_Functionality_Snippet_Store::storage_basename( $name );
			if ( is_file( $target ) && filesize( $target ) === filesize( $path ) ) {
				$copied[] = $name;
				continue;
			}
			if ( is_file( $target ) ) {
				return false;
			}
			if ( ! copy( $path, $target ) || ! is_file( $target ) || filesize( $target ) !== filesize( $path ) ) {
				if ( is_file( $target ) ) {
					unlink( $target );
				}
				return false;
			}
			$copied[] = $name;
		}
		foreach ( $names as $name ) {
			if ( '.' === $name || '..' === $name ) {
				continue;
			}
			$path = $public . '/' . $name;
			if ( ! is_file( $path ) ) {
				continue;
			}
			$drop = in_array( $name, array( 'safe-mode.key', '.htaccess', 'index.php', 'README.md' ), true );
			if ( ! $drop && ! in_array( $name, $copied, true ) ) {
				continue;
			}
			unlink( $path );
		}
		$left = scandir( $public );
		if ( is_array( $left ) && 2 === count( $left ) ) {
			rmdir( $public );
		}
		return ! is_dir( $public );
	}

	/**
	 * Move a previous snippet directory onto $dest and remove $source.
	 *
	 * Copies every file, including the safe-mode key, checks the size, then
	 * deletes the source. A size mismatch leaves both copies in place.
	 *
	 * @since 1.2.2
	 * @param string $source Previous directory.
	 * @param string $dest   Directory to keep.
	 * @return bool Whether $source is gone or was already absent.
	 */
	public static function adopt_store( $source, $dest ) {
		$source = rtrim( (string) $source, '/\\' );
		$dest   = rtrim( (string) $dest, '/\\' );
		if ( '' === $source || ! is_dir( $source ) ) {
			return true;
		}
		$source_real = realpath( $source );
		if ( is_dir( $dest ) ) {
			$dest_real = realpath( $dest );
			if ( false !== $source_real && false !== $dest_real && $source_real === $dest_real ) {
				return true;
			}
		} else {
			$parent = dirname( $dest );
			if ( is_dir( $parent ) && is_writable( $parent ) && rename( $source, $dest ) ) {
				return ! is_dir( $source );
			}
			if ( ! mkdir( $dest, 0755, true ) ) {
				return false;
			}
		}
		$names = scandir( $source );
		if ( ! is_array( $names ) ) {
			return false;
		}
		foreach ( $names as $name ) {
			if ( '.' === $name || '..' === $name ) {
				continue;
			}
			$path = $source . '/' . $name;
			if ( ! is_file( $path ) ) {
				return false;
			}
			$target = $dest . '/' . $name;
			if ( is_file( $target ) && filesize( $target ) === filesize( $path ) ) {
				continue;
			}
			if ( is_file( $target ) ) {
				return false;
			}
			if ( ! copy( $path, $target ) || ! is_file( $target ) || filesize( $target ) !== filesize( $path ) ) {
				if ( is_file( $target ) ) {
					unlink( $target );
				}
				return false;
			}
		}
		foreach ( $names as $name ) {
			if ( '.' === $name || '..' === $name ) {
				continue;
			}
			$path = $source . '/' . $name;
			if ( is_file( $path ) && ! unlink( $path ) ) {
				return false;
			}
		}
		$left = scandir( $source );
		if ( is_array( $left ) && 2 === count( $left ) ) {
			rmdir( $source );
		}
		return ! is_dir( $source );
	}
}
