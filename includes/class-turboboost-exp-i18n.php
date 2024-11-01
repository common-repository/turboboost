<?php
 if ( ! defined( 'ABSPATH' ) ) exit; 
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://turbo-boost.io/
 * @since      1.0.0
 *
 * @package    Turboboost_Exp
 * @subpackage Turboboost_Exp/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Turboboost_Exp
 * @subpackage Turboboost_Exp/includes
 * @author     Turboboost <sushant@eunix.tech>
 */


if (!defined('TURBOBOOST_EXP_VERSION')) die('No direct access allowed!');

if (!class_exists('Turboboost_Exp_i18n')) :

class Turboboost_Exp_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'turboboost-exp',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}
}
endif;
