<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://www.mattcromwell.com
 * @since             1.0.0
 * @package           Rf_Site_Functionality
 *
 * @wordpress-plugin
 * Plugin Name:       Site Functionality
 * Plugin URI:        https://github.com/Roots-and-Fruit/rf-site-functionality
 * Description:       A file-based code snippet system for WordPress. Load and execute PHP files from the /code-snippets/ directory with better performance and security than database-stored snippets.
 * Version:           2.0.0
 * Author:            Matt Cromwell
 * Author URI:        https://www.mattcromwell.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       rf-site-functionality
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'RF_SITE_FUNCTIONALITY_VERSION', '2.0.0' );

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
 * @return string
 */
function rf_site_functionality_snippet_folder_name() {
	$root = strtolower( rtrim( str_replace( '\\', '/', ABSPATH ), '/' ) );
	return 'mc-snippets-' . substr( md5( $root ), 0, 12 );
}

/**
 * Preferred snippet directory, next to WordPress and outside the web root.
 *
 * @return string
 */
function rf_site_functionality_sibling_snippets_dir() {
	$root = strtolower( rtrim( str_replace( '\\', '/', ABSPATH ), '/' ) );
	return dirname( str_replace( '/', DIRECTORY_SEPARATOR, $root ) ) . DIRECTORY_SEPARATOR . rf_site_functionality_snippet_folder_name();
}

/**
 * Temp fallback used when open_basedir blocks the sibling directory.
 *
 * @return string
 */
function rf_site_functionality_temp_snippets_dir() {
	return rtrim( sys_get_temp_dir(), '/\\' ) . DIRECTORY_SEPARATOR . rf_site_functionality_snippet_folder_name();
}

/**
 * Snippet directory outside the web root.
 *
 * A normal host gets a folder next to WordPress. If open_basedir blocks that
 * path, snippets stay in the system temp directory until the sibling path is
 * writable. The temp copy is moved and then removed.
 *
 * @return string
 */
function rf_site_functionality_private_snippets_dir() {
	$sibling = rf_site_functionality_sibling_snippets_dir();
	$temp    = rf_site_functionality_temp_snippets_dir();
	$basedir = (string) ini_get( 'open_basedir' );
	if ( ! rf_site_functionality_path_allowed( $sibling, $basedir ) ) {
		return $temp;
	}
	if ( is_dir( $temp ) ) {
		require_once plugin_dir_path( __FILE__ ) . 'includes/class-rf-site-functionality-snippet-migration.php';
		if ( ! Rf_Site_Functionality_Snippet_Migration::adopt_store( $temp, $sibling ) ) {
			return $temp;
		}
	}
	if ( is_dir( $sibling ) ) {
		$real = realpath( $sibling );
		if ( is_string( $real ) && '' !== $real ) {
			return $real;
		}
	}
	return $sibling;
}

/**
 * Plugin snippets directory path.
 * This is where PHP code snippets are stored and loaded from.
 */
if ( ! defined( 'RF_SITE_FUNCTIONALITY_SNIPPETS_DIR' ) ) {
	define( 'RF_SITE_FUNCTIONALITY_SNIPPETS_DIR', rf_site_functionality_private_snippets_dir() );
}

/**
 * Old public folder. Files here are moved out and then deleted.
 *
 * @return string
 */
function rf_site_functionality_public_snippets_dir() {
	return WP_CONTENT_DIR . '/mc-snippets';
}

/**
 * Legacy folder. Another process may still add files here. Copy them forward. Do not delete them.
 *
 * @return string
 */
function rf_site_functionality_legacy_snippets_dir() {
	return plugin_dir_path( __FILE__ ) . 'code-snippets';
}

/**
 * Load code snippets early in the WordPress lifecycle.
 * This ensures snippets are available before other plugins and themes load.
 */
function load_rf_site_functionality_snippets() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rf-site-functionality-snippet-store.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rf-site-functionality-snippet-migration.php';
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rf-site-functionality-snippet-loader.php';

	$store = new Rf_Site_Functionality_Snippet_Store();
	Rf_Site_Functionality_Snippet_Migration::relocate_public_snippets( rf_site_functionality_public_snippets_dir(), $store->get_dir() );
	$store->ensure_dir();
	$store->ensure_key();
	Rf_Site_Functionality_Snippet_Migration::copy_once( rf_site_functionality_legacy_snippets_dir(), $store->get_dir() );

	$snippet_loader = new Rf_Site_Functionality_Snippet_Loader();
	if ( isset( $_GET['mc-snippets-safe-mode'] ) ) {
		$snippet_loader->store()->maybe_disable( wp_unslash( $_GET['mc-snippets-safe-mode'] ) );
	}
	$snippet_loader->load_snippets();
}

// Hook to load snippets early in the WordPress lifecycle
add_action( 'plugins_loaded', 'load_rf_site_functionality_snippets', 5 );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-rf-site-functionality-activator.php
 */
function activate_rf_site_functionality() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rf-site-functionality-activator.php';
	Rf_Site_Functionality_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-rf-site-functionality-deactivator.php
 */
function deactivate_rf_site_functionality() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rf-site-functionality-deactivator.php';
	Rf_Site_Functionality_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_rf_site_functionality' );
register_deactivation_hook( __FILE__, 'deactivate_rf_site_functionality' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-rf-site-functionality.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_rf_site_functionality() {

	$plugin = new Rf_Site_Functionality();
	$plugin->run();
}
run_rf_site_functionality();
