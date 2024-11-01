<?php
if (!defined('ABSPATH')) die('No direct access allowed.');

require_once( wp_normalize_path(ABSPATH).'wp-load.php');
require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-turboboost-exp-admin.php';

define( 'TURBOBOOST_CACHE_DIR', WP_CONTENT_DIR. '/turboboost_cache' );

if (!class_exists('Turboboost_Exp_Cache')) :

    class Turboboost_Exp_Cache {
        public $do_not_cache = ['cart', 'checkout', 'my-account', 'wp-login', 'wp-cron', 'wp-admin'];
        public $ob_callback;
        public $cache_timeout  = 180 * 60;
        public $ignore_cookies  = ['wordpress_test_cookie','woocommerce_cart_hash','woocommerce_items_in_cart','wp_woocommerce_session_','woocommerce_recently_viewed','store_notice[notice id]'];
        public $woocommerce_active;
        public $should_page_cache = false;

        public function __construct() {
            $cache_enabled = get_option(TURBOBOOST_OPTIONS['cache'], false);

            if (false === get_transient('turboboost_get_all_pages_has_run')) {
                $this->get_all_the_pages();
                set_transient('turboboost_get_all_pages_has_run', true, DAY_IN_SECONDS); // Set the transient to expire after 1 day
            }
            
            add_action('publish_page', [$this,'get_all_the_pages'], 10, 2);
            $this->woocommerce_active = $this->check_woocommerce_active();
            $this->should_page_cache = $this->check_page_cache_option();

            $this->ob_callback = function ($contents) {
                if ($this->should_page_cache) {
                    $key_data = $this->turboboost_get_req();
                    $unique_key = substr(md5(serialize($key_data[2])), 0, 8);
                    $cache = [
                        'contents' => $contents,
                        'created' => time(),
                        'expires' => time() + $this->cache_timeout,
                    ];
                    $this->store($unique_key, $cache);
                }

                header('X-Turboboost-Cache: miss');
                return $contents;
            };

            add_filter('sanitize_option_turboboost_cache_page_list', [$this, 'custom_sanitize_cache_page_list'], 10, 3);
            // $user_logged_in = is_user_logged_in();
            $user_logged_in = is_user_logged_in();

            if ($this->should_page_cache && !$user_logged_in && $cache_enabled) {
                
                $key = $this->turboboost_get_key();

                if ($key) {
                    $turboboost_cache = $this->turboboost_get_cache_data($key);

                    if ($turboboost_cache) {
                        header('X-Turboboost-Cache: hit');
                        readfile($turboboost_cache['content']);
                        $hit_count = get_transient(TURBOBOOST_OPTIONS['cache_page_hits']);
                        set_transient(TURBOBOOST_OPTIONS['cache_page_hits'], $hit_count + 1, 24 * HOUR_IN_SECONDS);
                        exit;

                    } else {
                        header('X-Turboboost-Cache: miss');
                        $miss_count = get_transient(TURBOBOOST_OPTIONS['cache_page_misses']);
                        set_transient(TURBOBOOST_OPTIONS['cache_page_misses'], $miss_count + 1, 24 * HOUR_IN_SECONDS);
                        unset($turboboost_cache);
                    }
                }
                if (!isset($turboboost_cache)) {
                    ob_start($this->ob_callback);
                }
            }
        }
        
        /**
         * Check if User is logged in 
         * @since    1.0.0
         * @access   public
         **/
        function check_if_user_logged_in()
        { 
            $logged_in_cookie_name = LOGGED_IN_COOKIE;
             
            if (!empty($_COOKIE[$logged_in_cookie_name])) {
                $sanitized_logged_in_cookie = sanitize_key($_COOKIE[$logged_in_cookie_name]);
                // Validate the auth cookie
                if (wp_validate_auth_cookie( $sanitized_logged_in_cookie, 'logged_in')) {
                    return true;
                } else {
                    return false;
                }
            }
        }
        /**
         * Get all the pages  
         * @since    1.0.0
         * @access   public
         **/
        function get_all_the_pages()
        {
            $page_args = array(
                'post_type' => 'page',
                'post_status' => 'publish'
            );
            $pages = get_pages($page_args);
            $page_list = array();
            if (is_array($pages) && count($pages) > 0) {

                foreach ($pages as $page) { // $pages is array of object

                    $page_list[$page->post_name] =  in_array($page->post_name, $this->do_not_cache) ? 0 : 1;
                }
            }

            if(isset($page_list) && is_array($page_list) && count($page_list) > 0) {  
                update_option(TURBOBOOST_OPTIONS['cache_page_list'], $page_list);
            }
        }
        /**
         * Check if given page cache is enabled
         * @since    1.0.0
         * @access   public
         **/
        function check_page_cache_option()
        {
            $page_uri =  filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);

            if ($page_uri === false) {
                return;
            }

            $prohibited_cache =  get_option(TURBOBOOST_OPTIONS['prohibited_cache'], false);
            $prohibited_cache = is_array($prohibited_cache) && count($prohibited_cache) >= 1 ? array_merge($prohibited_cache, $this->do_not_cache) : $this->do_not_cache;
            
            foreach ($prohibited_cache as $page) {
                if (strpos($page_uri, $page) !== false) {
                    return  false;
                }
            }

            return true;
        }
        /**
         * Check if WooCommerce is active
         * @since    1.0.0
         * @access   public
         **/
        function check_woocommerce_active()
        {
            if (
                in_array(
                    'woocommerce/woocommerce.php',
                    apply_filters('active_plugins', get_option('active_plugins'))
                )
            ) {
                return true;
            } else {
                return false;
            }
        }
        /** 
         * Get page cahce if available else return false
         * @since    1.0.0
         * @access   private
         */
        public function turboboost_get_cache_data($key)
        {
            $cache['file'] = TURBOBOOST_CACHE_DIR . '/' . $key . '.meta';

            if (file_exists($cache['file'])) {
                $cache['meta'] = json_decode(file_get_contents(TURBOBOOST_CACHE_DIR . '/' . $key . '.meta'), true);
                $cache['content'] = (TURBOBOOST_CACHE_DIR . '/' . $key . '.html');

                if ($cache['meta'] != false && $cache['content'] != false) {

                    if ($cache['meta']['expires'] <  time()) {
                        header('X-Turboboost-Cache: expired');
                        return false;
                    }
                    return $cache;
                }
            }
            return false;
        }
        function custom_sanitize_cache_page_list($value, $old_value, $option){
            if(!$value){
                $value = $old_value;
            }
            $prohibited_cache = array();
            if(is_array($value) && count($value) >= 1){

                foreach ($value as $key => $val) {
                    if ($val == 0) {
                        $prohibited_cache[] = $key;
                    }
                }
    
                if(count($prohibited_cache) >= 1 ){
                    get_option(TURBOBOOST_OPTIONS['prohibited_cache'], false) ? update_option(TURBOBOOST_OPTIONS['prohibited_cache'], $prohibited_cache) : add_option(TURBOBOOST_OPTIONS['prohibited_cache'], $prohibited_cache) ;
                }
            }

            return $value;
        }

        function turboboost_get_key(){
            $request_data = $this->turboboost_get_req();
            $unique_key = substr(md5(serialize($request_data[2])), 0, 8);

            return $unique_key;
        }

        public function turboboost_get_req()
        {
            $cookies = [];
            $headers = [];
            
            $request_uri = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
           // Validate
            if ($request_uri === false) {
                return;
            }

            $http_host = filter_var($_SERVER['HTTP_HOST'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
            // Validate the HTTP host
            if ($http_host === false) {
                $http_host = '';
            }
            
            if ( empty( $_SERVER['REQUEST_METHOD'] ) ) {
                return;
            }

            //Sanitize request method
            if ( ! empty( $_SERVER['REQUEST_METHOD'] ) ) {
                $request_method = $this->sanitize_request_method( $_SERVER['REQUEST_METHOD'] );
            }
            
            foreach ($_COOKIE as $name  => $value) {
                $sanitized_name = sanitize_key($name);
                if (in_array($sanitized_name, $this->ignore_cookies)) continue;
                
                if(isset($_COOKIE[$sanitized_name])){
                    $cookies[] = $sanitized_name;
                }
            }

            return [
                $request_method, 
                $http_host,
                $request_uri,
                $cookies,
                $headers,
            ];
        }

        /**
         * Sanitizes the Requires at least header to ensure that it's a valid method
         *
         * @param string $request_method
         * @return string The sanitized $request_method
         */
        public function sanitize_request_method( $request_method ) {
            $request_method = strtoupper( $request_method );
            if (! in_array( $request_method , [ 'GET', 'HEAD' ] ))  {
               $request_method = '';
            }
            return $request_method;
        }

        public function store($key, $cache)
        {
            function write_file($file, $content)
            {
                $fileHandle =  fopen($file, 'w');

                if ($fileHandle !== false && flock($fileHandle, LOCK_EX)) {
                    // Perform operations on the meta file while holding the lock
                    fwrite($fileHandle, $content);
                    // Release the lock
                    flock($fileHandle, LOCK_UN);
                    fclose($fileHandle);
                    return true;
                } else {
                    return false;
                }
            }
            $content = $cache['contents'];
            unset($cache['contents']);

            $meta = wp_json_encode($cache);
            $cache_dir = WP_CONTENT_DIR . '/turboboost_cache';
            if (!wp_mkdir_p($cache_dir)) {
                return false;
            }
            $meta_file = $cache_dir . '/' . $key . '.meta';
            $cache_file = $cache_dir . '/' . $key . '.html';
            $opr_status = write_file($meta_file, $meta);

            if ($opr_status) {
                $opr_status = write_file($cache_file, $content);
                $is_page = false;

                $creation_timestamp = get_file_creation_time($key);
                // $page = sanitize_text_field($_SERVER['REQUEST_URI']);
                $page = get_permalink();
                
                // Check whether key is a page or assets
                $is_page = $this->action_for_pages_only($page);
                
                // do actions only for pages
                if( $is_page ) {
                    turboboost_save_cache_data($page, $creation_timestamp);
                    turboboost_send_data_to_cache_status_api();
                    turboboost_send_data_to_cache_warmup_api();
                }       
                $this->calculate_cache_size();
            }

            return $opr_status;
        }

        /**
         * Check for pages and skip assets
         *
         * @param string $uri
         * @return bool
         */
        public function action_for_pages_only($uri) {
            $turbo_cache_page_lists = get_option( TURBOBOOST_OPTIONS['cache_page_list'], false );
            // calculate cache size for a particular page not for every assets
            if($turbo_cache_page_lists && !empty($turbo_cache_page_lists)) {
                
                foreach($turbo_cache_page_lists as $page=>$value) {
                    if (strpos($uri, $page) !== false) {
                       return true; 
                    }
                }
            }
            return false;
        }

        /**
         * calculate current cache size if cache allowed for page.
         *
         * @return array
         */
        public function calculate_cache_size() {
            // create admin menu object
            $admin_obj = new Turboboost_Exp_Admin();
            $uri =  $_SERVER['REQUEST_URI'];
            $turbo_cache_page_lists = get_option( TURBOBOOST_OPTIONS['cache_page_list'], false );
            // calculate cache size for a particular page not for every assets
            if($turbo_cache_page_lists && !empty($turbo_cache_page_lists)) {
                
                foreach($turbo_cache_page_lists as $page=>$value) {
                    if (strpos($uri, $page) !== false) {
                        $admin_obj->get_cache_size();
                    }
                }
            }
        }

        public function removeCacheDirectory($dir)
        {
            // Check if WP_Filesystem is loaded
		if ( ! function_exists( 'WP_Filesystem' ) ) {
		    require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		// Initialize WP_Filesystem
		if ( ! WP_Filesystem() ) {
		    // Failed to initialize, handle error here
		    return;
		}
		global $wp_filesystem;
            if (is_dir($dir)) {
                $objects = scandir($dir);

                foreach ($objects as $object) {

                    if ($object != "." && $object != "..") {

                        if (is_dir($dir . "/" . $object)) {
                            $this->removeCacheDirectory($dir . "/" . $object);
                        } else {
                            wp_delete_file($dir . "/" . $object);
                        }
                    }
                }
                $wp_filesystem->delete($dir, true);
            }
        }
    }
endif;