<?php
$turboboost_options = [
    'preload_urls'          => 'turboboost_preload_urls',
    'font_preload'          => 'turboboost_font_preload',
    'prefetch_urls'         => 'turboboost_dns_prefetch_urls',
    'cdn'                   => 'turboboost_enable_cdn',
    'js_minify'             => 'turboboost_enable_js_minification',
    'webp'                  => 'turboboost_convert_webp',
    'compression'           => 'turboboost_compress_all_images',
    'resize'                => 'turboboost_resize_image',
    'dns_prefetch'          => 'turboboost_dns_prefetch',
    'cache'                 => 'turboboost_enable_cache',
    'defer_urls'            => 'turboboost_add_defer_urls',
    'defer'                 => 'turboboost_enable_defer',
    'cdn_links'             => 'turboboost_cdn_links',
    'all_urls'              => 'turboboost_all_urls',
    'prohibited_cache'      => 'turboboost_prohibited_cache',
    'cache_page_list'       => 'turboboost_cache_page_list',
    'cache_page_hits'       => 'turboboost_cache_page_hits',
    'cache_page_misses'     => 'turboboost_cache_page_misses',
    'get_cache_size'        => 'turboboost_get_cache_size',
    'minified_js'           => 'turboboost_minified_js',
    'plan'                  => 'turboboost_plan',
];
if (!defined('TURBOBOOST_OPTIONS')) define('TURBOBOOST_OPTIONS',$turboboost_options);

$turboboost_plans = [

    'Basic' => [

        'font_preload' => false,
        'dns_prefetch' => false,
        'defer' => false,
        'cdn' => true,
        'minified_js' => true,
        'webp' => true,
        'compression' => true,
        'resize' => true,
        'cache' => true,
        'css_minification' => false,
    ],

    'Starter' =>[

        'font_preload' => false,
        'dns_prefetch' => false,
        'defer' => false,
        'cdn' => true,
        'minified_js' => true,
        'webp' => true,
        'compression' => true,
        'resize' => true,
        'cache' => true,
        'css_minification' => false,
    ],

    'Growth' =>[

        'font_preload' => false,
        'dns_prefetch' => false,
        'defer' => false,
        'cdn' => true,
        'minified_js' => true,
        'webp' => true,
        'compression' => true,
        'resize' => true,
        'cache' => true,
        'css_minification' => true,
    ],

    'Pro' =>[

        'font_preload' => true,
        'dns_prefetch' => true,
        'defer' => true,
        'cdn' => true,
        'minified_js' => true,
        'webp' => true,
        'compression' => true,
        'resize' => true,
        'cache' => true,
        'css_minification' => true,
    ],
];

if (!defined('TURBOBOOST_PLAN')) define('TURBOBOOST_PLAN',$turboboost_plans);

$home_url_without_protocol = str_replace(array('http://', 'https://'), '', home_url());
if (!defined('TURBOBOOST_HOME_URL')) define('TURBOBOOST_HOME_URL',$home_url_without_protocol);

$turbo_plan = 'basic';