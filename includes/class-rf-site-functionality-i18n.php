<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://www.mattcromwell.com
 * @since      1.0.0
 *
 * @package    Rf_Site_Functionality
 * @subpackage Rf_Site_Functionality/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Rf_Site_Functionality
 * @subpackage Rf_Site_Functionality/includes
 * @author     Matt Cromwell <info@mattcromwell.com>
 */
class Rf_Site_Functionality_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'rf-site-functionality',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
