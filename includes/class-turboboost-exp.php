<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://turbo-boost.io/
 * @since      1.0.0
 *
 * @package    Turboboost_Exp
 * @subpackage Turboboost_Exp/includes
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
 * @package    Turboboost_Exp
 * @subpackage Turboboost_Exp/includes
 * @author     Turboboost <sushant@eunix.tech>
 */
if (!defined('ABSPATH')) die('No direct access allowed!');

if (!class_exists('Turboboost_Exp')) :
class Turboboost_Exp {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Turboboost_Exp_Loader    $loader    Maintains and registers all hooks for the plugin.
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
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'TURBOBOOST_EXP_VERSION' ) ) {
			$this->version = TURBOBOOST_EXP_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'turboboost-exp';
		add_action('wp_enqueue_scripts', array($this,'enqueue_lazy_loading_checker'));
		add_action('wp_enqueue_scripts', array($this,'enqueue_html_updater_file'));
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->add_rest_hooks();
		add_action( 'init', array($this, 'cache_init')  );
		
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Turboboost_Exp_Loader. Orchestrates the hooks of the plugin.
	 * - Turboboost_Exp_i18n. Defines internationalization functionality.
	 * - Turboboost_Exp_Admin. Defines all hooks for the admin area.
	 * - Turboboost_Exp_Public. Defines all hooks for the public side of the site.
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
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-turboboost-exp-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-turboboost-exp-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-turboboost-exp-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-turboboost-exp-public.php';
		
		/**
		 * The class responsible for defining all actions that occur in the cachce related
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-turboboost-exp-cache.php';

		/**
		 * The class responsible for defining all actions that occur in the cachce related
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-turboboost-exp-cdn.php';
		
		/**
		 * The class responsible for defining all actions that occur in the image conversion to webp related
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/images-opt/class-turboboost-exp-webp.php';
		
		/**
		 * The class responsible for defining all actions that occur in the font optimization to font related
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/font-opt/class-turboboost-exp-font-opt.php';
		
		/**
		 * The class responsible for defining all actions that occur in the font optimization to DNS related
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/dns-opt/class-turboboost-exp-dns-opt.php';

		$this->loader = new Turboboost_Exp_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Turboboost_Exp_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Turboboost_Exp_i18n();

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

		$plugin_admin = new Turboboost_Exp_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Turboboost_Exp_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/** 
	 * Run Image Lazy Loading 
	 */
	function enqueue_lazy_loading_checker() {
		wp_enqueue_script('lazy-loading-checker', plugin_dir_url(__FILE__) . 'images-opt/lazyloading.js', array('jquery'), '1.0', true);
	}
	/** 
	 * Run HTML attribute updater
	 */
	function enqueue_html_updater_file() {
		wp_enqueue_script('html_updater_file', plugin_dir_url(__FILE__) . 'html-opt/htmlAttributeUpdation.js', array('jquery'), '1.0', true);
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
	 * @return    Turboboost_Exp_Loader    Orchestrates the hooks of the plugin.
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

	
    function cache_init( ){
    $turboboost_cache = new Turboboost_Exp_Cache();
	}

	public function add_rest_hooks() {
		add_action('rest_api_init', [$this, 'register_rest_routes']); 
	}

	public function register_rest_routes () {
		register_rest_route('turboboost/v1', '/subscription-callback', array(
			'methods' => 'GET',
			'callback' => array($this,'handle_subscription_callback'),
		));
	}

	// Define the callback handler function
	public function handle_subscription_callback( $request ) {
		// Retrieve the token parameter from the callback URL
		$token = $request->get_param('token');

		if ( empty( $token )) {
			return new WP_Error( 'Token can not be empty',  array( 'status' => 404 ) );
		}

		$token = trim(esc_attr($token));

		if ($this->validate_token( $token )) {
			update_option('turboboost_webhook_token', $token);

			$redirect_url = admin_url('admin.php?page=turboboost_frontend');
       		wp_redirect($redirect_url);

			exit;
			// return rest_ensure_response('Subscription callback handled successfully', 200);
		} else {
			// Token validation failed, handle error
			return new WP_REST_Response('Invalid token',  array( 'status' => 404 ));
		}

	}

	public function validate_token( $token = NULL) {
		if( !$token ) {
			return false;
		}

		$api_url = TURBOBOOST_API_URL . 'auth/check-user-account';

		$auth_response = wp_remote_get( $api_url,
			[
				'headers' => array(
					'Content-Type' => 'application/json',
					'Authorization' => 'Bearer '. $token,
				)
			] );

		// Return if the request fails.
		if ( 200 !== wp_remote_retrieve_response_code( $auth_response ) ) {
			return false;
		}	
			
		if ( ! is_wp_error( $auth_response ) ) {
			$user_data = (array) json_decode( wp_remote_retrieve_body( $auth_response ), true );

			if($user_data['status'] !== 200) {
				return false;
			}
		}

		if ( $user_data['status'] == 200 ) {
			return true;
		}
	}
}
endif;
