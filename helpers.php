<?php
// Call method on frontend or during specific actions
if (!function_exists('turboboost_format_size')) :

    /**
	 * Format Bytes Into KB/MB
	 *
	 * @param  mixed   $bytes    Number of bytes to be converted.
	 * @param  integer $decimals the number of decimal digits
	 * @return integer        return the correct format size.
	 */
	function turboboost_format_size($bytes, $decimals = 2) {
		if (!is_numeric($bytes)) return __('N/A', 'turboboost-optimize');

		if (1073741824 <= $bytes) {
			$bytes = number_format($bytes / 1073741824, $decimals) . ' GB';
		} elseif (1048576 <= $bytes) {
			$bytes = number_format($bytes / 1048576, $decimals) . ' MB';
		} elseif (1024 <= $bytes) {
			$bytes = number_format($bytes / 1024, $decimals) . ' KB';
		} elseif (1 < $bytes) {
			$bytes = $bytes . ' bytes';
		} elseif (1 == $bytes) {
			$bytes = $bytes . ' byte';
		} else {
			$bytes = '0 bytes';
		}

		return $bytes;
	}
endif;

if (!function_exists('turbo_check_woocommerce_active')) :
	function turbo_check_woocommerce_active()
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
		endif;

/**
 * Get the file creation time
 */
function get_file_creation_time($key){
	$file = TURBOBOOST_CACHE_DIR . '/' . $key . '.meta';

	if (file_exists($file)) {
		error_log("file was last modified: " . date("F d Y H:i:s.", filemtime($file)));
		return filemtime($file);
	}
}


function get_api_endpoint() {
    return 'http://localhost:8000/v1/api/wordpress/';
}


function turboboost_track_page_visit() {
	$flag = 0;
	$pages_data = [];
	$data = [];

	// $pages_name = turboboost_get_pages_data();

	// if ( empty($pages_name) ) {
	// 	return;
	// }

	global $wpdb;
	// Get user ID (if logged in)
	$user_id = wp_get_current_user()->ID;

	// Get current page URL
	$current_page_url = get_permalink();
	$page_name = $_SERVER['REQUEST_URI'];
	
	$table_name = $wpdb->prefix . 'turboboost_tracking_data';
	// Check already table exists
	$table_exists = turboboost_check_table_exist();

	if (!$table_exists) {
		turboboost_create_table($table_name);
	}

	// Define the timeframe (e.g., last minute)
	$timeframe = strtotime('-1 minute');
	$timeData = date("Y-m-d h:i:s",$timeframe);

	if( !empty($current_page_url ))	{
		// Query to check existing records
		$query = $wpdb->prepare("
		SELECT COUNT(*)
		FROM $table_name
		WHERE user_id = %d
		AND page_url = %s
		AND updated > %s
		", $user_id, $current_page_url, $timeData);

		// Execute the query
		$existing_count = $wpdb->get_var($query);
	}
	// Check if a record already exists within the specified timeframe

	if( !empty($current_page_url ) && $existing_count == 0) {
		if( is_front_page() ) {
			$data = [ 
				'user_id'  => $user_id,
				'page_url' => $current_page_url,
				'page_name' => $page_name,
				'post_type' => "sessions",
				'value' => 1,
				'updated' => current_time('mysql'),
			];
		} else {
			$data = [
				'user_id' => $user_id,
				'page_url' => $current_page_url,
				'page_name' => $page_name,
				'post_type' => "bounces",
				'value' => 1,
				'updated' => current_time('mysql'),
			];
		}
		// Sanitize data (optional, consider using wp_sanitize_* functions)
		$sanitized_data = array_map( 'sanitize_text_field', $data );

		$wpdb->insert( $table_name, $sanitized_data );
	}

	// if( !empty($current_page_url ) && $existing_count == 0) {
	// 	foreach($pages_name as $page_name) {
	// 		if( strpos($current_page_url, $page_name) !== false || is_front_page() ) {
	// 			$data = [ 
	// 				'user_id'  => $user_id,
	// 				'page_url' => $current_page_url,
	// 				'page_name' => $page_name,
	// 				'post_type' => "sessions",
	// 				'value' => 1,
	// 				'updated' => current_time('mysql'),
	// 			];
	// 				break;
	// 		} else {
	// 			$data = [
	// 				'user_id' => $user_id,
	// 				'page_url' => $current_page_url,
	// 				'page_name' => $page_name,
	// 				'post_type' => "bounces",
	// 				'value' => 1,
	// 				'updated' => current_time('mysql'),
	// 			];

	// 		}
	// 	}
	// 	// Sanitize data (optional, consider using wp_sanitize_* functions)
	// 	$sanitized_data = array_map( 'sanitize_text_field', $data );

	// 	$wpdb->insert( $table_name, $sanitized_data );
	// }
}

function turboboost_check_table_exist() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'turboboost_tracking_data';
	return $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
}

function turboboost_create_table($table_name) {
	global $wpdb;
	if ( ! function_exists( 'dbDelta' ) ) {
		include_once ABSPATH . 'wp-admin/includes/upgrade.php';
	}

	$charset_collate = $wpdb->get_charset_collate();
	$table           = $table_name;
	$sql             = "CREATE TABLE $table (
		id MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
		user_id  BIGINT(10),
		page_url TEXT,
		page_name TEXT,
		post_type TEXT,
		value MEDIUMTEXT,
		updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		deletion_time TIMESTAMP DEFAULT '0000-00-00 00:00:00',";
			
	dbDelta( "$sql\nPRIMARY KEY  (id)\n) {$charset_collate};" );
}

/**
 * Get all the pages  
 * @since    1.0.0
 * @access   public
 **/
function turboboost_get_pages_data() {
	global $wpdb;
	$slug = [];
	// Custom SQL query to retrieve post_name (slug) from the posts table
	$query = "SELECT post_name FROM {$wpdb->posts} WHERE post_type = 'page' OR post_type = 'product' AND post_status = 'publish'";

	// Fetch post names from the database
	$post_names = $wpdb->get_results($query);

	// Check if there are any post names
	if ($post_names) {
		// Loop through each post name
		foreach ($post_names as $post_name) {
			// Access the post name
			$slug[] = $post_name->post_name;
		}
	}
	return $slug;
}

function turboboost_get_table_name() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'turboboost_tracking_data';

	return $table_name;
}

function turboboost_detect_device() {
	// Default device as Desktop
	$device = 1;
	// Detect device: 1=> Desktop,2=> Mobile 
	if ( wp_is_mobile() ) {
		// User is browsing from a mobile device
		$device = 2;
	} else {
		// User is browsing from a desktop/laptop
		$device = 1;
	}
	return $device;
}

/***************** CACHE STATUS DATA FOR API Dashboard *********************************/

function turboboost_send_data_to_cache_status_api() {
	
	$api_url = TURBOBOOST_API_URL;
	$api_token = get_option('turboboost_webhook_token', '');

	if( !$api_token &&  empty($api_token) ) {
		return new WP_Error('token_verification_failed', 'No token available', array('status' => 403));
	}
	// Detect device
	$device = turboboost_detect_device();
	
    // hit ratio
	$hit_ratio = 0;
	$hit_count = get_transient(TURBOBOOST_OPTIONS['cache_page_hits']);
	$miss_count = get_transient(TURBOBOOST_OPTIONS['cache_page_misses']);
	$total_attempts = $hit_count + $miss_count;
	if ($total_attempts > 0 && $hit_count > 0) {
		$hit_ratio = round($hit_count / $total_attempts * 100, 2);
	}

	// cache size
	$cache_size = 0;
	$format_cache_size = 0;
	$cache_size = get_transient(TURBOBOOST_OPTIONS['get_cache_size']);
	if($cache_size  && !empty($cache_size['size'])) {
		$format_cache_size = turboboost_format_size($cache_size['size']);
	}

	// Bounce rate
	$bounce_rate = 0;
	$bounce_rate = turboboost_calculate_bounce_data();
	
	// Prepare the resposne.
	$response = wp_remote_post(
		$api_url . 'cache/cache-status/create-new-record',
		array(
			'timeout' => 10,
			'headers' => array(
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer '. $api_token,
			),
			'sslverify' => false,
			'body'      => json_encode(
				array(
					'hitRatio' => $hit_ratio,
					'cacheSize' => $format_cache_size ,
					'bounceRate' => $bounce_rate,
					'url' => get_permalink()? get_permalink() :home_url(),
					'siteUrl' => TURBOBOOST_HOME_URL,
					'deviceType' => isset($device)? $device: 1,
					'status' => 1
				)
			),
		)
	);
	if (!is_wp_error($response)) {
		// Get the response code
		$response_code = wp_remote_retrieve_response_code($response);
		error_log('Response Code: '. $response_code);
		// Get the response body
		$response_body = (array)json_decode(wp_remote_retrieve_body($response), true);
		error_log('Response Body: '. print_r($response_body, true));
		
		// Process the response code and body
		echo 'Response Code: ' . $response_code . '<br>';
		// echo 'Response Body: ' . $response_body;
	} else {
		// If there was an error in the request
		echo 'Error: ' . $response->get_error_message();
	}
	return;
}

function turboboost_calculate_bounce_data() {
	global $wpdb;
	$session_count = 0;
	$bounce_count = 0;
	$bounce_rate = 0;

	// Table name
	$table_name = $wpdb->prefix . 'turboboost_tracking_data';

	// Calculate the date 7 days ago
	$seven_days_ago = date('Y-m-d H:i:s', strtotime('-7 days'));

	// Custom SQL query to retrieve data for the last 7 days where post_type is "sessions"
	$session_query = $wpdb->prepare("
		SELECT COUNT(*)
		FROM $table_name
		WHERE post_type = 'sessions'
		AND updated >= %s
	", $seven_days_ago);

	// Execute the query
	$session_count = $wpdb->get_var($session_query);

	$bounce_query = $wpdb->prepare("
		SELECT COUNT(*)
		FROM $table_name
		WHERE post_type = 'bounces'
		AND updated >= %s
	", $seven_days_ago);

	// Execute the query
	$bounce_count = $wpdb->get_var($bounce_query);

	if ($session_count > 0) {
		$bounce_rate = round( ($session_count / ($bounce_count + $session_count)) * 100, 2 );
	}
	return $bounce_rate;
}

/***************** CACHE WARMUP DATA FOR API Dashboard *********************************/

function turboboost_send_data_to_cache_warmup_api() {
	$api_url = TURBOBOOST_API_URL;
	$api_token = get_option('turboboost_webhook_token', false);
	
	if( !$api_token ) {
		return new WP_Error('token_verification_failed', 'No token available', array('status' => 403));
	}

	$cached_pages = 0;
	$cached_pages = get_transient(TURBOBOOST_OPTIONS['get_cache_size']);
	if($cached_pages  && !empty($cached_pages['file_count'])) {
		$cached_pages = intval($cached_pages['file_count']/2);
	}

	// Prepare the resposne.
	$response = wp_remote_post(
		$api_url . 'cache/cache-warmup/create-new-record',
		array(
			'timeout' => 10,
			'headers' => array(
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer '. $api_token,
			),
			'sslverify' => false,
			'body'      => json_encode(
				array(
					'estimatedTimeSaved' => '',
					'estimatedCostsSaved' => '',
					'noOfPages' => $cached_pages,
					'siteUrl' => TURBOBOOST_HOME_URL
				)
			),
		)
	);
	return;
}

function turboboost_calculate_page_views() {
	global $wpdb;
	$page_views_count = 0;
	// Table name
	$table_name = $wpdb->prefix . 'turboboost_tracking_data';

	$today = date('Y-m-d'); // Get today's date in YYYY-MM-DD format

	$page_view_query = "SELECT page_url, page_name
			FROM $table_name
			WHERE post_type LIKE '%sessions%' OR post_type LIKE '%bounces%' AND DATE(updated) = '$today'";
			
	// Page url and name array
	$page_view_results = $wpdb->get_results( $page_view_query );
	// Total page views count
	$page_views_count = count($page_view_results);
	return $page_views_count;
}

/**
 * Get cached page url to send in cdn bandwidth API
 */
function turboboost_get_cached_page_url() {
	global $wpdb;
	$cache_data = '';
	// Table name
	$table_name = turboboost_get_table_name();
	$today = date('Y-m-d'); // Get today's date in YYYY-MM-DD format
	
	$cache_data_query = $wpdb->prepare("
    SELECT page_url
    FROM $table_name
    WHERE post_type = 'cache_info'
    AND (DATE(updated) = %s OR updated = (SELECT MAX(updated) FROM $table_name WHERE post_type = 'cache_info'))
    ", $today );

	// Page url and name array
	$cache_data_results = $wpdb->get_results($cache_data_query);

	if(!empty($cache_data_results)) {
		$cache_data = end($cache_data_results);
		$cache_data = $cache_data->page_url;
	}
	return $cache_data;
}

/***************** CDN DATA FOR API Dashboard *********************************/

function turboboost_save_cache_data($page_url, $timestamp){
	$page_url = $page_url ? $page_url : '';
	global $wpdb;
	$page_url ?? get_permalink();
	
	turboboost_create_cache_creation_data($page_url);

	$table_name = $wpdb->prefix . 'turboboost_tracking_data';
	// Check table already exists
	$table_exists = turboboost_check_table_exist();
	$value_to_update = array(
		'post_type' => 'cache_info',
		'page_url' => $page_url,
	);
	if (!$table_exists) {
		turboboost_create_table($table_name);
	}
	$data = array(
	  'page_url' => $page_url,
	  'post_type' => 'cache_info',
	  'updated' => date("Y-m-d h:i:s", $timestamp),
	);
	$sql = $wpdb->prepare(
		"SELECT * FROM $table_name WHERE post_type = %s AND page_url = %s",
		$value_to_update['post_type'],
		$value_to_update['page_url']
	);
	$row_exists = $wpdb->get_row($sql);

	if( $row_exists ) {
		$wpdb->update( $table_name, $data, $value_to_update );
	} else {
		$wpdb->insert($table_name, $data);
	}
	return;
}

/**
 * Save deletion time of cache 
 */
function turboboost_save_cache_deletion_time() {
	global $wpdb;
	$timestamp = time();

	$table_name = $wpdb->prefix . 'turboboost_tracking_data';
	// Check already table exists
	$table_exists = turboboost_check_table_exist();
	$where = array(
		'post_type' => 'cache_info',
	);
	if ($table_exists) {
		$data = array(
			'deletion_time' => date("Y-m-d h:i:s", $timestamp),
		);
		$sql = $wpdb->prepare(
			"SELECT * FROM $table_name WHERE post_type = %s",
			$where['post_type'],
		);
		$row_exists = $wpdb->get_results($sql);
	
		if ($row_exists) {
			$result = $wpdb->update($table_name, $data, $where);
			// Check if the update was successful
			if ($result !== false) {
				// Update successful
				error_log("Updated $result rows. 🐛🔫"); 
			} else {
				// Update failed
				error_log( "Error updating rows: 🔫🐛 " . $wpdb->last_error) ;
			}
		} 
	}
	turboboost_create_cache_deletion_data();
	return;
}

function turboboost_save_cdn_distribution() {
	$cdn_urls = [];
	$js_urls = [];
	$css_urls = [];
	$other_urls = [];
	$cdn_urls_count = [];
	global $wpdb;

	if(get_option( TURBOBOOST_OPTIONS['cdn_links'], false )) {
		$cdn_urls = get_option( TURBOBOOST_OPTIONS['cdn_links'] , []);
		if(!empty($cdn_urls)) {
			foreach($cdn_urls as $key=>$cdn_url) {
				// Get the file extension using pathinfo()
				$extension = pathinfo($cdn_url, PATHINFO_EXTENSION);
				if ($extension === 'js') {
					$js_urls[] = $cdn_url;
				} elseif ($extension === 'css') {
					$css_urls[] = $cdn_url;
				} else {
					$other_urls[] = $cdn_url;
				}
			}
		}

		$cdn_urls_count['js'] = count($js_urls);
		$cdn_urls_count['css'] = count($css_urls);
		$cdn_urls_count['other'] = count($other_urls);

		$table_name = turboboost_get_table_name();
		// Check already table exists
		$table_exists = turboboost_check_table_exist();

		if (!$table_exists) {
			turboboost_create_table($table_name);
		}

		$cdn_data = [ 
			'post_type' => "cdn_count",
			'value' => serialize([
                'js' => !empty($cdn_urls_count['js'])?$cdn_urls_count['js']:'0',
                'css' => !empty($cdn_urls_count['css'])?$cdn_urls_count['css']:'0',
                'other' => !empty($cdn_urls_count['other'])?$cdn_urls_count['other']:'0',
            ]),
			'updated' =>current_time('mysql'),
		];

		// Sanitize data (optional, consider using wp_sanitize_* functions)
		$sanitized_data = array_map( 'sanitize_text_field', $cdn_data );

		$wpdb->insert( $table_name, $cdn_data );
	}
}

function turboboost_get_cdn_distribution() {
	global $wpdb;
	$cdn_data = [];
	// Table name
	$table_name = turboboost_get_table_name();

	$today = date('Y-m-d'); // Get today's date in YYYY-MM-DD format
	
	$cdn_data_query = $wpdb->prepare("
    SELECT value
    FROM $table_name
    WHERE post_type = 'cdn_count'
    AND (DATE(updated) = %s OR updated = (SELECT MAX(updated) FROM $table_name WHERE post_type = 'cdn_count'))
    ", $today);

	// Page url and name array
	$cdn_data_results = $wpdb->get_results($cdn_data_query);
	if(!empty($cdn_data_results)) {
		$cdn_data = end($cdn_data_results);
		
		// Unserialize the data
		if(isset($cdn_data->value)) {
		$cdn_array = unserialize($cdn_data->value);
		}
		// Check if unserialization was successful
		if (!empty($cdn_array) && $cdn_array !== false) {
			$cdn_data = $cdn_array;
		}
	}
	return $cdn_data;
}

function turboboost_create_cdnbandwidth_data_api() {
	$cdn_dist = turboboost_get_cdn_distribution();
	$page_views = turboboost_calculate_page_views();
	// Prepare data 
	$cdn_data = [
		'js'    => $cdn_dist['js'],
		'css'    => $cdn_dist['css'],
		'other' => $cdn_dist['other'],
		'pageViews' => $page_views,
		'siteUrl' => TURBOBOOST_HOME_URL,
	];
	//Send data to cdn bandwidth API for cache distribution logs
	turboboost_send_data_to_cdn_distribution_api($cdn_data);
}

function turboboost_create_cache_creation_data( $page_url = '' ) {
	// Detect device type
	$device = turboboost_detect_device();

	$cache_logs = [
		'eventType'  => 1,
		'url'        => $page_url,
		'deviceType' => $device,
		'siteUrl' => TURBOBOOST_HOME_URL,
		
	];
	//Send data to cdn bandwidth API for cache creation logs
	turboboost_send_data_to_cdn_distribution_api( $cache_logs );
	return;
}

function turboboost_create_cache_deletion_data(){
	global $wpdb;
	$cache_data = '';
	// Table name
	$table_name = turboboost_get_table_name();

	$today = date('Y-m-d'); // Get today's date in YYYY-MM-DD format
	
	$cache_data_query = $wpdb->prepare("
    SELECT page_url
    FROM $table_name
    WHERE post_type = 'cache_info'
    AND (DATE(deletion_time) = %s OR deletion_time = (SELECT MAX(deletion_time) FROM $table_name WHERE post_type = 'cache_info'))
    ", $today);

	// Page url and name array
	$cache_data_results = $wpdb->get_results($cache_data_query);
	
	if(!empty($cache_data_results)) {
		$cache_data = end($cache_data_results);
		$cache_data = $cache_data->page_url;
	}

    // Detect device type
	$device = turboboost_detect_device();

	// create deletion data to send in cdn bandwidth api for logs
	$cache_logs = [
		'eventType'  => 2,
		'url'        => $cache_data ?? '',
		'deviceType' => $device,
		'siteUrl' => TURBOBOOST_HOME_URL,
	];

	// send data to cdn bandwidth API call
	turboboost_send_data_to_cdn_distribution_api($cache_logs);
}	

function turboboost_send_data_to_cdn_distribution_api($cdn_data) {

	$api_url = TURBOBOOST_API_URL;
	$api_token = get_option('turboboost_webhook_token', false);

	if( !$api_token ) {
		return new WP_Error('token_verification_failed', 'No token available', array('status' => 403));
	}

	// Prepare the resposne.
	$response = wp_remote_post(
		$api_url . 'cdn-bandwidth/create-new-record',
		array(
			'timeout' => 10,
			'headers' => array(
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer '. $api_token,
			),
			'sslverify' => false,
			'body'      => json_encode(
				$cdn_data,
			)
		)
	);
	return;
}

function turboboost_check_user_exist() {
    $api_url = TURBOBOOST_API_URL . 'auth/check-user-account';
    $api_token = get_option('turboboost_webhook_token', false); // Fetch token dynamically if possible

    // Prepare the response.
    $response = wp_remote_get(
        $api_url,
        array(
            'headers'   => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_token,
            ),
            'sslverify' => false, // Enable SSL verification in production
        )
    );

    if (is_wp_error($response)) {
        // Handle WP_Error
        error_log('HTTP request error: ' . $response->get_error_message());
        return false;
    }

    $response_body = wp_remote_retrieve_body($response);
    $response_data = json_decode($response_body, true);

    if ($response_data === null) {
        // Handle JSON decoding error
        error_log('JSON decoding error: ');
        return false;
    }

    // Check if response contains expected data structure
    if (!isset($response_data['status'])) {
        // Handle unexpected response format
        error_log('Unexpected response format');
        return false;
    }

    // Log response
    error_log('Response: ' . print_r($response_data, true));

    return $response_data;
}

function turboboost_get_plan(){
	$user_data = turboboost_check_user_exist();

	$plan_in_db = get_option(TURBOBOOST_OPTIONS['plan'], false);
	$plan = $plan_in_db ?? "Basic"; //check plan in case false set basic plan
	
	if (isset($user_data['plan']['billingHistory']) && is_array($user_data['plan']['billingHistory']) && !empty($user_data['plan']['billingHistory'])) {
		$last_billing_record = end($user_data['plan']['billingHistory']);
		if (is_array($last_billing_record) && isset($last_billing_record['plan']) && !empty($last_billing_record['plan'])) {
			$plan = $last_billing_record['plan'];
		}
	}


	if(!$plan_in_db){
		update_option(TURBOBOOST_OPTIONS['plan'], $plan);
	}
	else{
		if($plan_in_db !== $plan){
			plan_on_change($plan);
		}
	}

	return $plan;
}

function plan_on_change($new_plan){
	$current_plan = TURBOBOOST_PLAN[$new_plan];
	foreach ($current_plan as $key => $value) {//resetting options false if plan change
		update_option(TURBOBOOST_OPTIONS[$key], false);
	}
}

function turboboost_is_user_subscribed(){
	$token = get_option('turboboost_webhook_token', false);

	if( $token ) {
		return true;
	} else {
		return false;
	}

}

function isAuthorised() {
	$token = get_option('turboboost_webhook_token', false);
	
	if( !$token ) {
		return new WP_Error('token_verification_failed', 'No token available', array('status' => 403));
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
			return new WP_Error('token_verification_failed', 'No token available', array('status' => 403));
		}
	}
	
	if ( $user_data['status'] == 200 ) {
		return true; 
	}
}

function turboboost_delete_data() {

	$token = get_option('turboboost_webhook_token', false);
	
	if( !$token ) {
		return new WP_Error('token_verification_failed', 'No token available', array('status' => 403));
	}

	$api_url = TURBOBOOST_API_URL . '/auth/removing-platform-data?websiteUrl='.TURBOBOOST_HOME_URL;

	$auth_response = wp_remote_get( $api_url,
		[
			'headers' => array(
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer '. $token,
			)
		] );

	
	// Return if the request fails.
	if ( 200 !== wp_remote_retrieve_response_code( $auth_response ) ) {
		error_log("Error in connecting removing-platform-data API ");
		return false;
	}	

	if ( ! is_wp_error( $auth_response ) ) {
		$user_data = (array) json_decode( wp_remote_retrieve_body( $auth_response ), true );

		if($user_data['status'] !== 200) {
			return new WP_Error('token_verification_failed', 'No token available', array('status' => 403));
		}
	}
	
	if ( $user_data['status'] == 200 ) {
		error_log( "Successfuly deleted data from Turboboost dashboard!!" );
		return true; 
	}
}

 function update_token_callback() {
    $token = $_POST['token'];
    // Update the option value
    update_option('turboboost_webhook_token', $token);

	wp_die();
}

function turboboost_delete_internal_dashboard_data() {
	global $wpdb;
		
	$table_name = $wpdb->prefix . 'turboboost_tracking_data';

	$wpdb->query("DROP TABLE IF EXISTS $table_name");
	
	// $delete_query = "DELETE FROM $table_name
    // WHERE (post_type LIKE '%sessions%' OR post_type LIKE '%bounces%' OR post_type LIKE '%cdn_count%')";
	
	// $wpdb->query($delete_query);
}

function turboboost_delete_transient_data() {
  global $wpdb;
  $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_turboboost_%'" );
  $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_turboboost_%'" );
}