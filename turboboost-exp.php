<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://turbo-boost.io/
 * @since             1.0.0
 * @package           Turboboost_Exp
 *
 * @wordpress-plugin
 * Plugin Name:       Turboboost
 * Plugin URI:        https://turbo-boost.io/
 * Description:       Speed Up website
 * Version:           1.0.0
 * Author:            Turboboost
 * Author URI:        https://turbo-boost.io
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       turboboost-exp
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die('No Direct Access!');
}

$turboboost_basePath = dirname(__FILE__) . '/';
require plugin_dir_path(__FILE__) . 'constants.php';
require_once $turboboost_basePath . 'helpers.php';
define( 'PLUGIN_IMAGE_URL', plugin_dir_url( __FILE__ ) . 'public/images/' );
$isProd = false;

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
if (!defined('TURBOBOOST_EXP_VERSION')) define('TURBOBOOST_EXP_VERSION', '1.0.0');
if (!defined('TURBOBOOST_PLUGIN_MAIN_PATH')) define('TURBOBOOST_PLUGIN_MAIN_PATH', plugin_dir_path(__FILE__));
if (!defined('TURBOBOOST_API_TOKEN')) define('TURBOBOOST_API_TOKEN', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VySWQiOiI2NjAzMmU4NzRmMWQ1NzNhNzM3MDAxMTAiLCJpYXQiOjE3MTE1MzYyMzksImV4cCI6MTcxNDEyODIzOX0.O-jMY4bW--hBAEFlCpYJMLKRStElbDNAcnZGzShLs6g');
if (!defined('IS_PROD_VAL')) define('IS_PROD_VAL',$isProd);

if( $isProd ) {
    if (!defined('TURBOBOOST_API_URL')) define('TURBOBOOST_API_URL', 'https://backend.turbo-boost.io/v1/api/wordpress/');
} else {
    if (!defined('TURBOBOOST_API_URL')) define('TURBOBOOST_API_URL', 'https://backend-dev.turbo-boost.io/v1/api/wordpress/');
}

if( $isProd ) {
    if (!defined('TURBOBOOST_DASHBOARD_URL')) define('TURBOBOOST_DASHBOARD_URL', 'https://dashboard.turbo-boost.io/');
} else {
    if (!defined('TURBOBOOST_DASHBOARD_URL')) define('TURBOBOOST_DASHBOARD_URL', 'https://dashboard-dev.turbo-boost.io/');
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-turboboost-exp-activator.php
 */
if (!function_exists('turboboost_activate')) :
    function turboboost_activate() {
        require_once plugin_dir_path(__FILE__) . 'includes/class-turboboost-exp-activator.php';
        Turboboost_Exp_Activator::activate();
    }
endif;

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-turboboost-exp-deactivator.php
 */
if (!function_exists('turboboost_deactivate')) :
    function turboboost_deactivate() {
        require_once plugin_dir_path(__FILE__) . 'includes/class-turboboost-exp-deactivator.php';
        Turboboost_Exp_Deactivator::deactivate();
    }
endif;

require_once TURBOBOOST_PLUGIN_MAIN_PATH . 'vendor/autoload.php';

register_activation_hook(__FILE__, 'turboboost_activate');
register_deactivation_hook(__FILE__, 'turboboost_deactivate');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-turboboost-exp.php';






/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */

// Hook into template_redirect to track user page access
add_action('template_redirect', 'turboboost_track_page_visit');


if (!function_exists('turboboost_html_minifier_plugin_minify_callback')) :
    function turboboost_html_minifier_plugin_minify_callback($buffer) {
        // Minification logic goes here
        // $buffer contains the HTML content

        // Example: Remove whitespace and comments
        $minified_buffer = preg_replace('/\s+/', ' ', $buffer);
        $minified_buffer = preg_replace('/<!--(.*?)-->/', '', $minified_buffer);

        return $minified_buffer;
    }
endif;


add_action('wp_enqueue_scripts', 'turboboost_html_minifier_plugin_minify_css');

if (!function_exists('turboboost_html_minifier_plugin_minify_css')) :
    function turboboost_html_minifier_plugin_minify_css() {

        // Check if the minified CSS is cached
        $minified_css = get_transient('minified_css');

        // If the cached minified CSS exists, enqueue it and return
        if ($minified_css !== false) {
            wp_enqueue_style('minified-style', get_stylesheet_directory_uri() . '/minified-style.css', array(), '1.0', 'all');
            wp_add_inline_style('minified-style', $minified_css);
            return;
        }

        // If the minified CSS is not cached, proceed with generating and caching it

        // Get the URL of the active theme's stylesheet
        $css_url = get_stylesheet_uri();

        // Fetch the CSS code from the URL or load it from a file
        $css_code = file_get_contents($css_url);

        // Minify CSS by removing whitespace and comments
        $minified_css = preg_replace('/\/\*[\s\S]*?\*\/|\t+/', '', $css_code);
        $minified_css = preg_replace('/\s+/', ' ', $minified_css);

        // Save minified CSS to a file in the active theme directory
        $theme_dir = get_stylesheet_directory();
        $minified_css_file = $theme_dir . '/minified-style.css';
        file_put_contents($minified_css_file, $minified_css);

        // Cache the minified CSS using a transient with an expiration time (e.g., 1 hour)
        set_transient('turboboost_minified_css', $minified_css, HOUR_IN_SECONDS);

        // Enqueue the minified CSS file
        wp_enqueue_style('minified-style', get_stylesheet_directory_uri() . '/minified-style.css', array(), '1.0', 'all');

        // Fetch custom additional CSS
        $custom_additional_css = wp_get_custom_css();

        // Minify custom additional CSS by removing whitespace and comments
        $minified_custom_css = preg_replace('/\/\*[\s\S]*?\*\/|\t+/', '', $custom_additional_css);
        $minified_custom_css = preg_replace('/\s+/', ' ', $minified_custom_css);

        // Enqueue the minified custom additional CSS as a separate stylesheet
        wp_add_inline_style('minified-custom-css', $minified_custom_css);
    }
endif;

if ( is_admin() ) {
    add_action( 'wp_ajax_update_token', 'update_token_callback' );
}
if (!function_exists('turboboost_get_active_theme_css_files')) :
    function turboboost_get_active_theme_css_files() {
        $theme = wp_get_theme();
        $theme_directory = get_stylesheet_directory();
        $css_files = array();

        foreach ($theme->get_files('css', -1) as $file_path) {
            // Check if the file is external (not a style block or inline CSS).
            if (strpos($file_path, 'http') === false) {
                $css_files[] = $file_path;
            }
        }

        return $css_files;
    }
endif;

if (!function_exists('turboboost_combine_css_files')) :
    function turboboost_combine_css_files($files) {
        $combined_css = '';

        foreach ($files as $file) {
            $combined_css .= file_get_contents($file);
        }

        return $combined_css;
    }
endif;

if (!function_exists('turboboost_minify_css')) :
    function turboboost_minify_css($css) {
        // Remove comments.
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);

        // Remove tabs, spaces, newlines, etc.
        $css = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $css);

        return $css;
    }
endif;

if (!function_exists('turboboost_get_minified_css')) :
    function turboboost_get_minified_css() {
        $cache_key = 'minified_css';
        $css_file_path = '';
        $minified_css = get_transient($cache_key);
        $css_files = turboboost_get_active_theme_css_files();
        $combined_css = turboboost_combine_css_files($css_files);
        $minified_css = turboboost_minify_css($combined_css);
        set_transient($cache_key, $minified_css, 24 * 60 * 60); // Cache for 24 hours.
        $upload_dir = __DIR__; 
        $css_file_path = $upload_dir . '/minified-hero-style.css';
        file_put_contents($css_file_path, $minified_css);

        $css_file_path = plugin_dir_url(__FILE__) . 'minified-hero-style.css';
        return $css_file_path;
    }
endif;

if (!function_exists('turboboost_add_inline_critical_css_first')) :
    function turboboost_add_inline_critical_css_first($html, $handle) {
        // Check if the handle matches your minified stylesheet
        if ($handle === 'my-minified-css') {
            // Get the critical CSS you want to add
            $critical_css = file_get_contents(turboboost_get_minified_css());
            // Prepend the critical CSS before the original stylesheet link
            $html = "<style id='my-hero-css' type='text/css'>{$critical_css}</style>" . $html;
        }
        return $html;
    }
endif;

$turbo_defer_ulrs = get_option( TURBOBOOST_OPTIONS['defer_urls'], array() );

if (!function_exists('turboboost_optimized_js')) :
    function turboboost_optimized_js($tag, $handle, $src) {
        global $turbo_defer_ulrs;
        $turbo_defer_ulrs = is_array($turbo_defer_ulrs) ? $turbo_defer_ulrs : [];

        if ((strpos($src, 'min.js') === false) && get_option( TURBOBOOST_OPTIONS['js_minify'], false )) {

            $minified_js = get_option( TURBOBOOST_OPTIONS['minified_js'], false) && is_array(get_option( TURBOBOOST_OPTIONS['minified_js'])) ? get_option( TURBOBOOST_OPTIONS['minified_js']) : [];
            
            if(!in_array($src, $minified_js)) {
                $minified_js[] = $src;
                update_option( TURBOBOOST_OPTIONS['minified_js'], $minified_js);
            }
            // Get script contents
            $script_content = file_get_contents($src);
        
            // Minify script (you can use any minification library or method here)
            $minified_script = turboboost_minify_js($script_content);
        
            // Update tag to include minified script content
            $tag = "<script>$minified_script</script>";
            // Check if script should be minified (modify condition as needed)
        }
        else{
            if(!in_array($src, $turbo_defer_ulrs)) {
            $turbo_defer_ulrs[] = $src;
            }
            if ( ! is_admin() ) {
                $tag = str_replace( '></script>', ' defer></script>', $tag );
            }
        }

        return $tag;
    }
endif;

if (!function_exists('turboboost_is_within_wp_content')) :
    function turboboost_is_within_wp_content($path) {
        $wp_content_path = get_site_url() .'/wp-content';
        return strpos($path, $wp_content_path) === 0;
    }
endif;

if (get_option( TURBOBOOST_OPTIONS['js_minify'], false ) || get_option( TURBOBOOST_OPTIONS['defer'], false )) {
    add_filter( 'script_loader_tag', 'turboboost_optimized_js', 10, 3 );
    add_action('wp_footer', 'turboboost_update_defer_js');
}

/**
 * Escape/Remove extra space, comments from js content
 */
if (!function_exists('turboboost_minify_js')) :
    function turboboost_minify_js($script_content) {
        
        $minified_script = preg_replace('/\/\*[\s\S]*?\*\/|\/\/.*/', '', $script_content); // Remove comments
        $minified_script = preg_replace('/\s+/', ' ', $minified_script); // Remove whitespace

        return $minified_script;
    }
endif;
/**
 * Update values of defered links
 */
if (!function_exists('turboboost_update_defer_js')) :
    function turboboost_update_defer_js($script_content) {
        
        global $turbo_defer_ulrs;

        if(is_array($turbo_defer_ulrs) && count($turbo_defer_ulrs) > 0) {
            update_option( TURBOBOOST_OPTIONS['defer_urls'], $turbo_defer_ulrs);
        }
        
    }
endif;


if (!function_exists('turboboost_start')) :
    function turboboost_start() {
        $plugin = new Turboboost_Exp();
        $plugin->run();
    }
endif;
turboboost_start();