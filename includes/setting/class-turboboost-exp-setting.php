<?php

if (!defined('ABSPATH')) die('No direct access allowed');

if (!class_exists( 'Turboboost_Exp_Setting' )) :
class Turboboost_Exp_Setting {
    /**
	 * The array containing all settings that can be imported/exported.
	 *
	 * @var array
	 */
    public $options = array(
        'turboboost_dns_prefetch',
        'turboboost_dns_prefetch_urls',
        'turboboost_enable_cache',
        'turboboost_convert_webp',
        'turboboost_compress_all_images',
        'turboboost_resize_image',
        'turboboost_font_preload',
        'turboboost_preload_urls',
        'turboboost_add_defer_urls',
        'turboboost_enable_defer',
        'turboboost_enable_cdn',
        'turboboost_enable_js_minification',
        'turboboost_cdn_links',
        'turboboost_all_urls',
        'turboboost_prohibited_cache',
        'turboboost_cache_page_list',
        'turboboost_cache_page_hits',
        'turboboost_cache_page_misses',
        'turboboost_get_cache_size',
        'turboboost_minified_js',
        'turboboost_font_preload',
        'turboboost_webhook_token',
    ) ;

    public function removeOptions() {
        foreach( $this->options as $option) {
            delete_option($option);
        }
        error_log('deleted turboboost options');
    }

    public function getOptions() {
        return $this->options;
    }
}
endif;