<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://turbo-boost.io/
 * @since      1.0.0
 *
 * @package    Turboboost_Exp
 */

 if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
require_once dirname(__FILE__) . '/includes/setting/class-turboboost-exp-setting.php';
require_once dirname(__FILE__) . '/includes/images-opt/class-turboboost-exp-webp.php';
require_once dirname(__FILE__) . '/helpers.php';

turboboost_delete_data();

$settingObject = new Turboboost_Exp_Setting();
$settingObject->removeOptions();

turboboost_delete_internal_dashboard_data();
turboboost_delete_transient_data();
$imageOpt = new Turboboost_Exp_Webp();
$imageOpt->delete_metadata();
$imageOpt->delete_backup_images();
