<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.mattcromwell.com
 * @since      1.0.0
 *
 * @package    Rf_Site_Functionality
 * @subpackage Rf_Site_Functionality/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Rf_Site_Functionality
 * @subpackage Rf_Site_Functionality/includes
 * @author     Matt Cromwell <info@mattcromwell.com>
 */
class Rf_Site_Functionality {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Rf_Site_Functionality_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The admin class instance.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Rf_Site_Functionality_Admin    $plugin_admin    The admin class instance.
	 */
	protected $plugin_admin;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'RF_SITE_FUNCTIONALITY_VERSION' ) ) {
			$this->version = RF_SITE_FUNCTIONALITY_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'rf-site-functionality';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Rf_Site_Functionality_Loader. Orchestrates the hooks of the plugin.
	 * - Rf_Site_Functionality_i18n. Defines internationalization functionality.
	 * - Rf_Site_Functionality_Admin. Defines all hooks for the admin area.
	 * - Rf_Site_Functionality_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-rf-site-functionality-loader.php';
		require_once __DIR__ . '/class-rf-site-functionality-abilities.php';
		Rf_Site_Functionality_Abilities::register();

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-rf-site-functionality-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		if ( is_admin() ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-rf-site-functionality-admin.php';
		}

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-rf-site-functionality-public.php';

		$this->loader = new Rf_Site_Functionality_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Rf_Site_Functionality_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Rf_Site_Functionality_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		if ( ! is_admin() || ! class_exists( 'Rf_Site_Functionality_Admin' ) ) {
			return;
		}

		$this->plugin_admin = new Rf_Site_Functionality_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $this->plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $this->plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $this->plugin_admin, 'add_admin_menu' );
		$this->loader->add_action( 'init', $this->plugin_admin, 'register_ajax_handlers' );
		$this->loader->add_action( 'admin_init', $this->plugin_admin, 'register_settings' );
		$this->loader->add_filter( 'plugin_action_links_' . plugin_basename( plugin_dir_path( dirname( __FILE__ ) ) . 'rf-site-functionality.php' ), $this->plugin_admin, 'add_plugin_action_links' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Rf_Site_Functionality_Public( $this->get_plugin_name(), $this->get_version() );

		unset( $plugin_public );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Rf_Site_Functionality_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
