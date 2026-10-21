<?php

/**
 * Fired during plugin activation
 *
 * @link       https://www.mattcromwell.com
 * @since      1.0.0
 *
 * @package    Rf_Site_Functionality
 * @subpackage Rf_Site_Functionality/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Rf_Site_Functionality
 * @subpackage Rf_Site_Functionality/includes
 * @author     Matt Cromwell <info@mattcromwell.com>
 */
class Rf_Site_Functionality_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-rf-site-functionality-snippet-store.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-rf-site-functionality-snippet-migration.php';
		$store = new Rf_Site_Functionality_Snippet_Store();
		$store->ensure_dir();
		require_once __DIR__ . '/class-rf-site-functionality-abilities.php';
		Rf_Site_Functionality_Abilities::grant_caps();
		if ( function_exists( 'rf_site_functionality_legacy_snippets_dir' ) ) {
			Rf_Site_Functionality_Snippet_Migration::copy_once( rf_site_functionality_legacy_snippets_dir(), $store->get_dir() );
		}
	}

}
