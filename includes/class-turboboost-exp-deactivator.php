<?php
if (!defined('ABSPATH')) die('No direct access allowed!');
/**
 * Fired during plugin deactivation
 *
 * @link       https://turbo-boost.io/
 * @since      1.0.0
 *
 * @package    Turboboost_Exp
 * @subpackage Turboboost_Exp/includes
 */

/**
 * The class responsible for defining all actions that occur in the image conversion to webp related
 * side of the site.
 */
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/images-opt/class-turboboost-exp-webp.php';
require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-turboboost-exp-cache.php';
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Turboboost_Exp
 * @subpackage Turboboost_Exp/includes
 * @author     Turboboost <sushant@eunix.tech>
 */
if (!class_exists('Turboboost_Exp_Deactivator')) :

class Turboboost_Exp_Deactivator
{

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {	
		update_option('turboboost_webp_optimized', false);
		$imageOpt = new Turboboost_Exp_Webp();
		$imageOpt->restore_original_images();
				
		$webpCronDeactive = wp_next_scheduled( 'turboboost_convert_image_to_webp_cron' );
		if ( $webpCronDeactive ) {
			wp_unschedule_event( $webpCronDeactive, 'turboboost_convert_image_to_webp_cron' );
		}

		$cdnCronDeactive = wp_next_scheduled( 'cdn_link_cron_event' );
		if ( $cdnCronDeactive ) {
			wp_unschedule_event( $cdnCronDeactive, 'cdn_link_cron_event' );
		}

		$cacheObject = new Turboboost_Exp_Cache();
		$cacheObject->removeCacheDirectory(TURBOBOOST_CACHE_DIR);
	}
}
endif;