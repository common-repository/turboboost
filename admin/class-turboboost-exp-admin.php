<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://turbo-boost.io/
 * @since      1.0.0
 *
 * @package    Turboboost_Exp
 * @subpackage Turboboost_Exp/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Turboboost_Exp
 * @subpackage Turboboost_Exp/admin
 * @author     Turboboost <sushant@eunix.tech>
 */

if (!defined('ABSPATH')) die('No direct access allowed');

/**
 * Directory that stores the cache, including gzipped files and mobile specific cache
 */
if (!defined('TURBOBOOST_CACHE_FILES_DIR')) define('TURBOBOOST_CACHE_FILES_DIR', untrailingslashit(WP_CONTENT_DIR) . '/turboboost_cache');

if (!class_exists('Turboboost_Exp_Admin')) :
	class Turboboost_Exp_Admin {

		/**
		 * The ID of this plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 * @var      string    $plugin_name    The ID of this plugin.
		 */
		private $plugin_name;

		/**
		 * The version of this plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 * @var      string    $version    The current version of this plugin.
		 */
		private $version;
		public static $toggleOptions = [
			'enable_defer', 
			'enable_cdn', 
			'enable_js_minification', 
			'convert_webp', 'compress_all_images',
			 'resize_image', 'enable_cache', 'font_preload','dns_prefetch'
		];
		/**
		 * Initialize the class and set its properties.
		 *
		 * @since    1.0.0
		 * @param      string    $plugin_name       The name of this plugin.
		 * @param      string    $version    The version of this plugin.
		 */
		public function __construct()
		{ 
			$this->plugin_name = 'turboboost-exp';
			$this->version = '1.0.0';

			$this->turbo_plan = turboboost_get_plan();
			
			$this->turbo_plan = $this->turbo_plan == '' ? 'Basic' : $this->turbo_plan;
			
			// Add the admin menu page
			add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
			if(turboboost_is_user_subscribed()){
				$this->admin_init_act();
			}
			// Add a link to clear the cache in the WordPress admin top bar menu
			add_action('admin_bar_menu', array($this, 'add_clear_cache_link'), 999);

			add_action('admin_post_turboboost_clear_cache', [$this, 'handle_clear_cache_request']);
			
			add_action( 'updated_option', [$this,'turboboost_updated_option'], 10, 3 );
		}

		private $turbo_plan ;

		/**
		 * Register the stylesheets for the admin area.
		 *
		 * @since    1.0.0
		 */
		public function enqueue_styles()
		{

			/**
			 * This function is provided for demonstration purposes only.
			 *
			 * An instance of this class should be passed to the run() function
			 * defined in Turboboost_Exp_Loader as all of the hooks are defined
			 * in that particular class.
			 *
			 * The Turboboost_Exp_Loader will then create the relationship
			 * between the defined hooks and the functions defined in this
			 * class.
			 */

			wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/turboboost-exp-admin.css', array(), $this->version, 'all');

		}

		/**
		 * Register the JavaScript for the admin area.
		 *
		 * @since    1.0.0
		 */
		public function enqueue_scripts()
		{

			/**
			 * This function is provided for demonstration purposes only.
			 *
			 * An instance of this class should be passed to the run() function
			 * defined in Turboboost_Exp_Loader as all of the hooks are defined
			 * in that particular class.
			 *
			 * The Turboboost_Exp_Loader will then create the relationship
			 * between the defined hooks and the functions defined in this
			 * class.
			 */

			wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/turboboost-exp-admin.js', array('jquery'), $this->version, false);

		}
		public function add_plugin_admin_menu()
		{
			$turbooboost_subscribed = turboboost_is_user_subscribed();
			// Add a top-level menu page
			add_menu_page(
				'My Turboboost Dashboard',
				'Turboboost',
				'manage_options',
				'my_turboboost_dashboard',
				array($this, 'display_plugin_dashboard'),
				'dashicons-admin-generic',
				// Icon for the menu item (you can change this)
				25 // Position of the menu item in the dashboard
			);
			if($turbooboost_subscribed){

				// Add a sub-menu Frontend page
				add_submenu_page(
					'my_turboboost_dashboard',
					'Frontend',
					'Frontend',
					'manage_options',
					'turboboost_frontend',
					array($this, 'display_frontend'),
				);
	
				// Add a sub-menu Media page
				add_submenu_page(
					'my_turboboost_dashboard',
					'Media',
					'Media',
					'manage_options',
					'turboboost_media',
					array($this, 'display_media'),
				);
	
				// Add a sub-menu Media page
				add_submenu_page(
					'my_turboboost_dashboard',
					'Cache',
					'Cache',
					'manage_options',
					'turboboost_Cache',
					array($this, 'display_cache_settings'),
				);
			}
		}
		function display_frontend()
		{
?>
			<div class="wrap turbo-ui turbo-frontend">
				<!-- <h2>Frontend Settings</h2> -->
				<form method="post" action="options.php">
					<?php
					settings_fields('turboboost_frontend'); // Use the settings group defined below
					do_settings_sections('turboboost_frontend'); // Use the section slug defined below
					submit_button('Save Settings');
					?>
				</form>
			</div>
		<?php
		}
		function display_media()
		{
		?>
			<div class="wrap turbo-ui">
				<!-- <h2>Media</h2> -->
				<form method="post" action="options.php">
					<?php
					settings_fields('turboboost_media_setting'); // Use the settings group defined below
					do_settings_sections('turboboost_media'); // Use the section slug defined below
					submit_button('Save Settings');
					?>
				</form>
			</div>
		<?php
		}

		function display_cache_settings()
		{
		?>
			<div class="wrap turbo-ui">
				<!-- <h2>Media</h2> -->
				<form method="post" action="options.php">
					<?php
					settings_fields('turboboost_cache'); // Use the settings group defined below
					do_settings_sections('turboboost_cache'); // Use the section slug defined below
					submit_button('Save Settings');
					?>
				</form>
			</div>
			<?php
		}

		function turboboost_plugin_settings()
		{
			//dashboard section
			add_settings_section(
				'custom_plugin_section',
				'Turboboost Dashboard',
				'',
				'my_turboboost_dashboard'
			);
		}

		function turboboost_frontend_settings() {
			$selected_plan = $this->turbo_plan;
			//setting section 
			$option_group = 'turboboost_frontend';
			$media_section = 'turboboost_frontend_section';
			$turbo_dns_prefertch_enable = get_option(TURBOBOOST_OPTIONS['dns_prefetch'], false);
			$turbo_enable_font_preloading = get_option(TURBOBOOST_OPTIONS['font_preload'], false);

			register_setting(
				$option_group,
				TURBOBOOST_OPTIONS['font_preload'],
				array($this, 'turbo_sanitize_checkbox')
			);
			register_setting(
				$option_group,
				TURBOBOOST_OPTIONS['preload_urls'],
				array(
					'type' => 'string',
					'santize_callback' => 'sanitize_text_field',
				)
			);
			register_setting(
				$option_group,
				TURBOBOOST_OPTIONS['dns_prefetch'],
				array($this, 'turbo_sanitize_checkbox')
			);
			register_setting(
				$option_group,
				TURBOBOOST_OPTIONS['prefetch_urls'],
			);
			register_setting(
				$option_group,
				TURBOBOOST_OPTIONS['defer'],
				array($this, 'turbo_sanitize_checkbox')
			);
			register_setting(
				$option_group,
				TURBOBOOST_OPTIONS['defer_urls'],
				array(
					'type' => 'string',
					'santize_callback' => 'sanitize_text_field',
				)
			);
			register_setting(
				$option_group,
				TURBOBOOST_OPTIONS['cdn'],
				array($this, 'turbo_sanitize_checkbox')
			);
			register_setting(
				$option_group,
				TURBOBOOST_OPTIONS['js_minify'],
				array($this, 'turbo_sanitize_checkbox')
			);
			add_settings_section(
				$media_section,
				'Frontend Settings',
				'',
				$option_group
			);
			if( isset(TURBOBOOST_PLAN[$selected_plan]['font_preload']) ){
				add_settings_field(
					TURBOBOOST_OPTIONS['font_preload'],
					__('Enable Fonts Preloading'),
					array($this, 'render_font_preloading_option'),
					$option_group,
					$media_section
				);
			}

			if($turbo_enable_font_preloading){

				add_settings_field(
					TURBOBOOST_OPTIONS['preload_urls'],
					__('Select font url for preloading'),
					array($this, 'render_field_callback'),
					$option_group,
					$media_section
				);
			}
			if(isset(TURBOBOOST_PLAN[$selected_plan]['dns_prefetch'])){
				add_settings_field(
					TURBOBOOST_OPTIONS['dns_prefetch'],
					__('Enable DNS Url for DNS Prefetch'),
					array($this, 'render_DNS_option'),
					$option_group,
					$media_section
				);
			}
			if($turbo_dns_prefertch_enable) {
				add_settings_field(
					TURBOBOOST_OPTIONS['prefetch_urls'],
					__('Enable DNS Url for DNS Prefetch'),
					array($this, 'render_dns_prefetch_callback'),
					$option_group,
					$media_section
				);
			}

			if(isset(TURBOBOOST_PLAN[$selected_plan]['defer'])){
				add_settings_field(
					TURBOBOOST_OPTIONS['defer'],
					_('Enable Urls for deffering'),
					array($this, 'render_defer_urls_callback'),
					$option_group,
					$media_section
				);
			}

			if(isset(TURBOBOOST_PLAN[$selected_plan]['cdn'])){
				add_settings_field(
					TURBOBOOST_OPTIONS['cdn'],
					__('Enable CDN'),
					array($this, 'render_cdn_option'),
					$option_group,
					$media_section
				);
			}

			if(isset(TURBOBOOST_PLAN[$selected_plan]['minified_js'])){
				add_settings_field(
					TURBOBOOST_OPTIONS['js_minify'],
					__('Enable JS minification'),
					array($this, 'render_js_minification_option'),
					$option_group,
					$media_section
				);
			}

		}
		function turboboost_cache_settings()
		{
			//setting section
			$plan = $this->turbo_plan;
			$option_group = 'turboboost_cache';
			$media_section = 'turboboost_cache_section';
			$page_slug = 'turboboost_cache';
			register_setting(
				$option_group,
				TURBOBOOST_OPTIONS['cache'],
				array($this, 'turbo_sanitize_checkbox')
			);
			register_setting(
				$option_group,
				TURBOBOOST_OPTIONS['cache_page_list'],
			);
			add_settings_section(
				$media_section,
				'Cache Settings',
				'',
				$option_group
			);
			if(isset(TURBOBOOST_PLAN[$plan]['cache'])) {
				add_settings_field(
					TURBOBOOST_OPTIONS['cache'],
					__('Enable Cache'),
					array($this, 'render_cache_option'),
					$option_group,
					$media_section
				);
				if(get_option( TURBOBOOST_OPTIONS['cache'], false)){
					add_settings_field(
						TURBOBOOST_OPTIONS['cache_page_list'],
						__('Select Page for Cache'),
						array($this, 'render_page_cache_option'),
						$option_group,
						$media_section
					);
				}
			}
		}
		function turboboost_media_settings()
		{
			$plan = $this->turbo_plan;
			$option_group = 'turboboost_media_setting';
			$media_section = 'turbo_media_section';
			$page_slug = 'turboboost_media';
			//setting section 
			register_setting(
				$option_group,
				TURBOBOOST_OPTIONS['webp'],
				array($this, 'turbo_sanitize_checkbox')
			);
			register_setting(
				$option_group,
				TURBOBOOST_OPTIONS['compression'],
				array($this, 'turbo_sanitize_checkbox')
			);
			register_setting(
				$option_group,
				TURBOBOOST_OPTIONS['resize'],
				array($this, 'turbo_sanitize_checkbox')
			);
			add_settings_section(
				$media_section,
				'Media Settings',
				'',
				$page_slug,
			);

			if(isset(TURBOBOOST_PLAN[$plan]['webp'])) {
				add_settings_field(
					TURBOBOOST_OPTIONS['webp'],
					__('Convert all images to WebP'),
					array($this, 'render_convert_image_option_webp'),
					$page_slug,
					$media_section
				);
			}
			if(isset(TURBOBOOST_PLAN[$plan]['compression'])) {
				add_settings_field(
					TURBOBOOST_OPTIONS['compression'],
					__('Compress all images'),
					array($this, 'render_compress_image_option'),
					$page_slug,
					$media_section
				);
			}
			
			if(isset(TURBOBOOST_PLAN[$plan]['resize'])) {
				add_settings_field(
					TURBOBOOST_OPTIONS['resize'],
					__('Resize all images'),
					array($this, 'render_resize_image_option'),
					$page_slug,
					$media_section
				);
			}
		}
		function admin_init_act()
		{
			add_action('admin_init', [$this,'my_plugin_settings_init']);
			add_action('admin_init', array($this, 'turboboost_plugin_settings'));
			add_action('admin_init', array($this, 'turboboost_frontend_settings'));
			add_action('admin_init', array($this, 'turboboost_cache_settings'));
			add_action('admin_init', array($this, 'turboboost_media_settings'));
		}

		function my_plugin_settings_init() {
			$api_url = TURBOBOOST_API_URL .'dashboard/fetch-dashboard-settings-data';

			$api_token = get_option('turboboost_webhook_token', false);
	
			if ( !$api_token	 ) {
				return new WP_Error('token_verification_failed', 'No token available', array('status' => 403));
			}

			$dashboard_response = wp_remote_get( $api_url,
				[
					'headers' => array(
						'Content-Type' => 'application/json',
						'Authorization' => 'Bearer '. $api_token,
					)
				]);

			// Return if the request fails.
			if ( 200 !== wp_remote_retrieve_response_code( $dashboard_response ) ) {
				return false;
			}
				
			if ( ! is_wp_error( $dashboard_response ) ) {
				$dashboard_data = (array) json_decode( wp_remote_retrieve_body( $dashboard_response ), true );

				if($dashboard_data['status'] !== 200) {
					return ;
				}
			}

			$settings_array = isset($dashboard_data['settingHandler']) ? $dashboard_data['settingHandler'] : [];
			if( is_array($settings_array) && count($settings_array) >  0 ) {
				foreach (self::$toggleOptions as $key) {
					// Check if the key exists in the first array
					if (isset($settings_array[$key])) {			
						$value = $settings_array[$key]['value'] ?? false;
						update_option('turboboost_'.$key, $value);				
					}
				}
			}
			return;
		}

		function sanitize_string_for_array($input)
		{
			return array_map('sanitize_text_field', (array) $input);
		}
		function render_section_callback()
		{
			echo 'This is the section description.';
		}
		function render_field_callback()
		{
			$turbo_preload_urls = get_option(TURBOBOOST_OPTIONS['preload_urls'], []);

		 if (!empty($turbo_preload_urls)) {
			foreach ($turbo_preload_urls as $font => $details) {
				?>
				<div>
					<label for="domain_<?php echo esc_attr($font); ?>">
						<input type="checkbox"  name="turboboost_preload_urls[<?php echo esc_attr($font); ?>][active]"
							id="domain_<?php echo esc_attr($font); ?>"
							value="1" <?php checked($details['active'], 1); ?> />
						<?php 
							  echo  '(' . esc_html($font) . ') ';
							  if(isset($details['srcUrl'])){
						      echo esc_html($details['srcUrl']); 
							  }
						?>
					</label>
				</div>
				<?php
			}
		}
	}

	function render_font_preloading_option() {
		$in_plan = TURBOBOOST_PLAN[$this->turbo_plan]['font_preload'];
        $font_preload = get_option( TURBOBOOST_OPTIONS['font_preload'], false );
        ?>
		<div class ="tb-align-settings">
			<label for="font_preload">
			<input type="checkbox" id="font_preload" <?php echo ($in_plan ? '' :  esc_attr('disabled'));  ?> name="turboboost_font_preload"
			<?php checked( $font_preload, true ) ?>  /> Yes
			</label> <br>
			<?php echo( $in_plan ? '' :  '<p class="turbo-premium">Pro</p>');?>

		</div>
        <?php
    }

	function render_compress_image_option()
	{
		$in_plan = TURBOBOOST_PLAN[$this->turbo_plan]['compression'];
		$compress_all_images = get_option( TURBOBOOST_OPTIONS['compression'] );
		?>
		<div class ="tb-align-settings"> 
			<label for="compress_all_images"> 
			<input type="checkbox" id="compress_all_images" <?php echo ($in_plan ? '' :  esc_attr('disabled'));  ?> name="turboboost_compress_all_images"
			<?php checked( $compress_all_images, true ) ?>  /> Yes
			</label> 
			<?php echo( $in_plan ? '' :  '<p class="turbo-premium">Pro</p>');?>
		</div>
		<?php
	}
	function render_cdn_option()
	{
		$in_plan = TURBOBOOST_PLAN[$this->turbo_plan]['cdn'];
		$enable_cdn = get_option( TURBOBOOST_OPTIONS['cdn'] );
		?>
		<div class ="tb-align-settings">
		<label for="enable_cdn"> 
		<input type="checkbox" id="enable_cdn" <?php echo ($in_plan ? '' :  esc_attr('disabled'));  ?> name="turboboost_enable_cdn"
		<?php checked( $enable_cdn, true ) ?>  /> Yes
		</label> 
		<?php echo( $in_plan ? '' :  '<p class="turbo-premium">Pro</p>');?>
		<?php
	}
	function render_js_minification_option()
	{
		$in_plan = TURBOBOOST_PLAN[$this->turbo_plan]['minified_js'];
		$enable_js_minification = get_option(TURBOBOOST_OPTIONS['js_minify']);
		?>
		<div class ="tb-align-settings">
		<label for="enable_js_minification"> 
		<input type="checkbox" id="enable_js_minification" <?php echo ($in_plan ? '' :  esc_attr('disabled'));  ?> name="turboboost_enable_js_minification"
		<?php checked( $enable_js_minification, true ) ?>  /> Yes
		</label> 
		<?php echo( $in_plan ? '' :  '<p class="turbo-premium">Pro</p>');?>
		<?php
	}
	function render_cache_option()
	{
		$in_plan = TURBOBOOST_PLAN[$this->turbo_plan]['cache'];
		$enable_cache = get_option( TURBOBOOST_OPTIONS['cache'] );
		?>
		<label for="enable_cache"> 
		<input type="checkbox" id="enable_cache" <?php echo ($in_plan ? '' :  esc_attr('disabled'));  ?> name="turboboost_enable_cache"
		<?php checked( $enable_cache, true ) ?>  /> Yes
		</label>
		<?php echo( $in_plan ? '' :  '<p class="turbo-premium">Pro</p>');?>
		<?php
	}
	function render_page_cache_option()
	{
		$turbo_cache_enabled = get_option( TURBOBOOST_OPTIONS['cache'] );
		$turbo_cache_page_list = get_option( TURBOBOOST_OPTIONS['cache_page_list'], [] );
		$turbo_cache_page_list =is_array($turbo_cache_page_list) && count($turbo_cache_page_list) > 0 ? $turbo_cache_page_list : [];
		// Loop through each page
		if (!empty($turbo_cache_page_list)) {
			foreach ($turbo_cache_page_list as $page => $cache_enable) {
				
				?>
				<div>
					<label for="page_<?php echo esc_attr($page); ?>">
						<input type="hidden" name="turboboost_cache_page_list[<?php echo esc_attr($page); ?>]" value="0">
						<input class="nytest" <?php echo ($turbo_cache_enabled ? '' :  esc_attr('disabled'));  ?> type="checkbox" name="turboboost_cache_page_list[<?php echo esc_attr($page); ?>]"
							id="page_<?php echo esc_attr($page); ?>"
							value="1" <?php checked($cache_enable); ?> />
							<!-- Add a hidden input field to send 0 if checkbox is not checked -->
						<?php echo esc_html($page); ?>
					</label>
				</div>
				<?php
			}
		}else{
			echo 'No pages yet';
		}
	}
	function render_resize_image_option()
	{
		$in_plan = TURBOBOOST_PLAN[$this->turbo_plan]['resize'];
		$resize_image = get_option( TURBOBOOST_OPTIONS['resize'] );
		?>
		<div class ="tb-align-settings">
			<label for="resize_image"> 
			<input type="checkbox" id="resize_image" <?php echo ($in_plan ? '' :  esc_attr('disabled'));  ?> name="turboboost_resize_image"
			<?php checked( $resize_image, true ) ?>  /> Yes
			</label>
			<?php echo( $in_plan ? '' :  '<p class="turbo-premium">Pro</p>');?>
		</div>
		<?php
	}
	function render_convert_image_option_webp()
	{ 
		$in_plan = TURBOBOOST_PLAN[$this->turbo_plan]['webp'];
		$convert_webp =  get_option(TURBOBOOST_OPTIONS['webp']);
		?>
		<div class ="tb-align-settings">
		<label for="convert_webp"> 
		<input type="checkbox" id="convert_webp" <?php echo ($in_plan ? '' :  esc_attr('disabled'));  ?> name= "turboboost_convert_webp"
		<?php checked( $convert_webp, true ) ?>  /> Yes
		</label>
		<?php echo( $in_plan ? '' :  '<p class="turbo-premium">Pro</p>');?>
		<?php
	}
	function render_DNS_option()
	{
		$in_plan = TURBOBOOST_PLAN[$this->turbo_plan]['dns_prefetch'];
		$dns_prefetch = get_option( TURBOBOOST_OPTIONS['dns_prefetch'] );
		?>
		<div class ="tb-align-settings">
		<label for="dns_prefetch"> 
		<input type="checkbox" id="dns_prefetch" <?php echo ($in_plan ? '' :  esc_attr('disabled'));  ?> name="turboboost_dns_prefetch"
		<?php checked( $dns_prefetch, true ) ?>  /> Yes
		</label> <br>
		<?php echo( $in_plan ? '' :  '<p class="turbo-premium">Pro</p>');?>
		<?php
	}
	function render_dns_prefetch_callback()
	{
		$turbo_dns_prefertch_url_field = get_option( TURBOBOOST_OPTIONS['prefetch_urls'], '' );
		// Loop through each domain
		if (!empty($turbo_dns_prefertch_url_field)) {
			foreach ($turbo_dns_prefertch_url_field as $domain => $details) {
				
				?>
				<div>
					<label for="domain_<?php echo esc_attr($domain); ?>">
						<input class="nytest" type="checkbox" name="turboboost_dns_prefetch_urls[<?php echo esc_attr($domain); ?>][active]"
							id="domain_<?php echo esc_attr($domain); ?>"
							value="1" <?php checked($details['active'], 1); ?> />
						<?php echo esc_html($domain); ?>
					</label>
				</div>
				<?php
			}
		}else{
			echo 'Searching for DNS Prefetch URLs...';
		}
	}
	function render_defer_urls_callback()
	{
		$in_plan = TURBOBOOST_PLAN[$this->turbo_plan]['defer'];
		$enable_defer = get_option(TURBOBOOST_OPTIONS['defer'], '');
		?>
		<div class="tb-align-settings">
		<label for="enable_defer">
			<input type="checkbox" id="enable_defer"<?php echo ($in_plan ? '' :  esc_attr('disabled'));  ?>  name="turboboost_enable_defer" <?php checked($enable_defer, true) ?> /> Yes
		</label>
		<?php echo( $in_plan ? '' :  '<p class="turbo-premium">Pro</p>');?>
		<?php

	}

	public function display_plugin_dashboard(){
	$data = isAuthorised();

	// Get the error message
	if(is_wp_error( $data )){
		$error_message = $data->get_error_message();
		error_log('Token Error Message'. $error_message);

		$error_data = $data->get_error_data();

		if ($error_data) {
			// Check if status exists in error data
			if (isset($error_data['status'])) {
				include plugin_dir_path(__FILE__) . 'views/' . 'connect.php';
			}
		}
	}
		
		if(! is_wp_error( $data )):
	?>
		<!--dashboard HTML here -->
		<div class="wrap turbo-ui turbo-dashboard ">
			<div class="form">
				<h2 class="main-title">Welcome to Turboboost</h2>
				<a href="<?php echo TURBOBOOST_DASHBOARD_URL ?>" target="_blank"style="float:right;">Go To Dashboard</a>	
				<div class="turbo-info">
					<?php
						$cache_enable = get_option(TURBOBOOST_OPTIONS['cache']);
						$hit_count = get_transient(TURBOBOOST_OPTIONS['cache_page_hits']);
						$miss_count = get_transient(TURBOBOOST_OPTIONS['cache_page_misses']);
						$total_attempts = $hit_count + $miss_count;
						?>
					<div class="info-block">
						<?php
						if ($total_attempts > 0 && $hit_count > 0) {
							$hit_ratio = round($hit_count / $total_attempts * 100, 2);
							?>
		
							<div id="el" data-value=" <?php echo $hit_ratio ?>">
								<span id="needle"></span>
							</div>
							<style>
								span#needle {
									transform: rotate(<?php echo (($hit_ratio / 100) * 180 . 'deg') ?>);
								}
		
								@keyframes rotateBackground {
									from {
										transform: rotate(0deg);
									}
		
									to {
										transform: rotate(<?php echo ($hit_ratio / 100) * 180 ?>deg);
									}
								}
		
								#el:before {
									animation: rotateBackground 3s linear forwards;
									background: linear-gradient(0deg, #38f8ac 50%, #fff 10%);
								}
		
								#el.animate:before {
									background: linear-gradient(<?php echo ($hit_ratio + 80) . 'deg'; ?>, #38f8ac 50%, #fff 10%);
								}
							</style>
							<h3>Cache Hit Ratio</h3>
							<?php
						} else if (!$cache_enable) {
							echo "<h3> Cache is not enabled</h3>";
						} else {
							echo "<h4>Not enough data to calculate cache hit rate.</h4>";
						}
						$cache_size = $this->get_cache_size();
						?>
					</div>
					<div class="info-block">
						<h2 class="focus-data">
							<?php echo turboboost_format_size($cache_size['size']) ?>
						</h2>
						<h3> Cache Size </h3>
					</div>
					<?php
					$bounce_data = turboboost_calculate_bounce_data();
					$bounce_rate = 0;
					if ($bounce_data > 0) {
						$bounce_rate = ($bounce_data / 100) * 180;
					} else {
						echo "<i class='fa-solid fa-chart-simple'></i>";
						echo "<h4>Not enough data to calculate bounce rate.</h4>";
					}
					?>
					<div class="bounce-rate info-block">
						<div class="circle-wrap">
							<div class="circle">
								<div class="mask full">
									<div class="fill" data-angle="45"></div>
								</div>
								<style>
									.mask.full,
									.circle .fill {
										animation: fill ease-in-out 3s;
										transform: rotate(<?php echo ($bounce_rate . 'deg') ?>);
									}
		
									@keyframes fill {
										0% {
											transform: rotate(0deg);
										}
		
										100% {
											transform: rotate(<?php echo ($bounce_rate . 'deg') ?>);
										}
									}
								</style>
								<div class="mask half">
									<div class="fill"></div>
								</div>
								<div class="inside-circle">
									<?php echo ($bounce_data . '%') ?>
								</div>
							</div>
						</div>
						<h3> Bounce Rate </h3>
					</div>
					<div class="bounce-rate info-block">
						<h2 class="focus-data">
							<?php echo turboboost_calculate_page_views() ?>
						</h2>
						<h3> Page Views </h3>
					</div>
					<div class="bounce-rate info-block">
						<?php
						$cdn_data = turboboost_get_cdn_distribution();

						if (!empty ($cdn_data)) {
							$total = $cdn_data['js'] + $cdn_data['css'] + $cdn_data['other'];
							$js_percent = (($cdn_data['js'] / $total) * 100) > 15 ? ($cdn_data['js'] / $total) * 100 : 15;
							$css_percent = (($cdn_data['css'] / $total) * 100 )> 15 ? ($cdn_data['css'] / $total) * 100  : 15 ;
							$others_percent = ($cdn_data['other'] / $total) * 100 > 15 ? ($cdn_data['other'] / $total) * 100 : 15;
							?>
							
							<div class="bar-block">
								<div class="cdn-bars" style="display: flex; justify-content: space-between; width: 100%;">
										<div style="background: greenyellow; width: <?php echo $js_percent; ?>%;"><?php echo $cdn_data['js']; ?></div>
										<div  style="background: #35e4de; width: <?php echo $css_percent; ?>%;" ><?php echo $cdn_data['css']; ?></div>
										<div  style="background: lightgray; width: <?php echo $others_percent;?>%;" ><?php echo $cdn_data['other']; ?></div>
								</div>
								<div class="cdn-bar-info">
									<div style="background: greenyellow;">JS</div>
									<div  style="background: #35e4de;" >CSS</div>
									<div  style="background: lightgray; " >Other</div>
								</div>
							</div>
							<?php
							error_log("Fetching CDN distribution data..." . print_r($cdn_data, true));
							
						}
						?>
						<h3> CDN Distribution </h3>
					</div>
					<!-- <div class="bounce-rate info-block">
														<h2 class="focus-data"> 2700 Mib </h2>
														<h3> CDN Bandwidth </h3>
													</div> -->
				</div>
			</div>
							
		</div>
		
	<?php
		endif;
						}

						/**
						 * Get current cache size.
						 *
						 * @return array
						 */
						public function get_cache_size() {
							$cache_size = get_transient(TURBOBOOST_OPTIONS['get_cache_size']);

							// if (empty($cache_size)) return $cache_size;

							$infos = $this->get_dir_infos(TURBOBOOST_CACHE_FILES_DIR);
							$cache_size = array(
								'size' => $infos['size'],
								'file_count' => $infos['file_count']
							);

							set_transient(TURBOBOOST_OPTIONS['get_cache_size'], $cache_size, 24 * HOUR_IN_SECONDS);

							return $cache_size;
						}

						/**
						 * Fetch directory information.
						 *
						 * @param string $dir
						 * @return array
						 */
						private function get_dir_infos($dir) {
							$dir_size = 0;
							$file_count = 0;

							$handle = is_dir($dir) ? opendir($dir) : false;

							if (false === $handle) {
								return array('size' => 0, 'file_count' => 0);
							}

							$file = readdir($handle);

							while (false !== $file) {

								if ('.' != $file && '..' != $file) {
									$current_file = $dir . '/' . $file;

									if (is_dir($current_file)) {
										$sub_dir_infos = $this->get_dir_infos($current_file);
										$dir_size += $sub_dir_infos['size'];
										$file_count += $sub_dir_infos['file_count'];
									} elseif (is_file($current_file)) {
										$dir_size += filesize($current_file);
										$file_count++;
									}
								}

								$file = readdir($handle);

							}

							return array('size' => $dir_size, 'file_count' => $file_count);
						}

						function turbo_sanitize_checkbox( $value ) {
							return 'on' == $value ? true : false;
						}

						/**
						 * Add a link to clear thecache in the WordPress admin top bar menu
						 */
						public function add_clear_cache_link($admin_bar)
						{
							// Check if the user has permission to clear the cache
							if (!current_user_can('manage_options')) {
								return;
							}

							// Add a link to clear the cache in the WordPress admin top bar menu
							$admin_bar->add_menu(array(
								'id' => 'turboboost-clear-cache',
								'title' => __('Clear Cache', 'my-caching-plugin'),
								'href' => wp_nonce_url(admin_url('admin-post.php?action=turboboost_clear_cache'), 'turboboost_clear_cache')
							));
						}

						public function handle_clear_cache_request(){

							// Verify that the user has permission to clear the cache
							if (!current_user_can('manage_options')) {
								wp_die(esc_html(__('You do not have permission to clear the cache.', 'my-caching-plugin')));
							}

							// Clear the cache directory 
							$cache_dir = WP_CONTENT_DIR . '/turboboost_cache';
							$this->delete_directory($cache_dir);
							$this->delete_cache_transients();

							turboboost_save_cache_deletion_time();

							wp_redirect(wp_get_referer());
							exit;
						}

						private function delete_directory($dir) {
							// Check if WP_Filesystem is loaded
							if (!function_exists('WP_Filesystem')) {
								require_once ABSPATH . 'wp-admin/includes/file.php';
							}
							// Initialize WP_Filesystem
							if (!WP_Filesystem()) {
								// Failed to initialize, handle error here
								return;
							}
							global $wp_filesystem;

							if (!is_dir($dir)) {
								return true;
							}

							$files = array_diff(scandir($dir), array('.', '..'));

							foreach ($files as $file) {
								$path = $dir . '/' . $file;

								if (is_dir($path)) {
									$this->delete_directory($path);
								} else {
									wp_delete_file($path);
								}
							}
							return  $wp_filesystem->delete($dir, true);
						}
						/**
						 * Delete Transients on clear cache
						 * 
						 */
						public function delete_cache_transients() {
							$turboboost_transients = ['turboboost_cache_page_hits', 'turboboost_cache_page_misses', 'turboboost_get_cache_size'];
							foreach ($turboboost_transients as $turboboost_transient) {
								delete_transient($turboboost_transient);
							}
							error_log('deleted transients');
						}

	public function turboboost_updated_option($option, $old_value, $value) {
		$prefix = "turboboost_";
        // Remove the prefix from the variable
        $option_without_prefix = str_replace($prefix, '', $option);

   		if (!in_array($option_without_prefix, self::$toggleOptions)) return;
		// API URL
		$api_url = TURBOBOOST_API_URL;
		
		$api_token = get_option('turboboost_webhook_token', false);
	
		if ( !$api_token ) {
			return new WP_Error('token_verification_failed', 'No token available', array('status' => 403));
		}

		// Prepare the resposne.
		$response = wp_remote_post(
			$api_url . 'toggle-handler/toggle-settings-update',
			array(
				'timeout' => 10,
				'headers' => array(
					'Content-Type' => 'application/json',
					'Authorization' => 'Bearer '. $api_token,
				),
				'sslverify' => false,
				'body'      => json_encode(
					array(
						'option' => $option_without_prefix,
						'value'  => $value ,
						'siteUrl' => TURBOBOOST_HOME_URL ,
					)
				)
			)
		);
		return;	
	}	
}
endif;