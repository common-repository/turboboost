<?php

if (!defined('ABSPATH')) die('No direct access allowed!');

if (!class_exists('Turboboost_Exp_cdn')) :

class Turboboost_Exp_cdn
{   
    public  $cdn_links;
    public  $all_urls;
    public function __construct()
    {  
        if(get_option( TURBOBOOST_OPTIONS['cdn'], false )){
            $this->cdn_links = get_option( TURBOBOOST_OPTIONS['cdn_links'], array() );
            if(!is_array($this->cdn_links)){
                $this->cdn_links = [];
            }
            add_action('cdn_link_cron_event', array($this, 'cdn_link_cron_function'));
            add_action('wp', array($this, 'cdn_link_update_cron_activation'));
        }
        
        add_filter('sanitize_option_cdn_links', [$this, 'custom_sanitize_cdn_links'], 10, 3);
    }
    function custom_sanitize_cdn_links($value, $option, $original_value){
        $cdn_links = array();
        if( is_array($value) && count($value) > 0 ){
            $cdn_links = $value;
        }
        return $cdn_links;
    }
    public function filter_loader()
    {
        // add_filter('script_loader_src', array($this, 'replace_jquery_with_jsdelivr'), 10, 2);

    }
    public function replace_jquery_with_jsdelivr($src, $handle)
    {
        if ($src === 'http://13.48.148.96/wp-includes/js/jquery/jquery.min.js?ver=3.6.4') {
            // Replace the local jQuery file URL with jsDelivr CDN URL
            $src = 'https://cdn.jsdelivr.net/jquery/latest/jquery.min.js';
        }
        return $src;
    }
    function apply_filter_script(){
        if(get_option( TURBOBOOST_OPTIONS['cdn'], false )) {
            add_filter( 'script_loader_src', array($this, 'filter_script_loader_src'), 20, 2 );
            add_filter( 'style_loader_src', array($this, 'filter_style_loader_src'), 20, 2 );
            add_action( 'wp_footer', array($this, 'save_cdn_links_data'), 9999);
            add_action( 'wp_footer', array($this, 'update_external_domains'), 9999);
        }    
    }
    function filter_script_loader_src( $src, $handle ) {
        return $this->maybe_replace_src( 'js', $src, $handle );
    }

    function filter_style_loader_src( $src, $handle ) {
        return $this->maybe_replace_src( 'css', $src, $handle );
    }
    
    function maybe_replace_src( $ext, $src, $handle ) {
        $old_src = $src;
        $ran_already = false;
        if(!empty( $this->cdn_links && array_key_exists( $old_src, $this->cdn_links ) ) && get_option( TURBOBOOST_OPTIONS['cdn'], false) ){
            $src = $this->cdn_links[$old_src];
            return $src;
        }
        // We only run this once per page generation to avoid a bunch of API calls to slow the site down
        if ( !$ran_already ) {
            if(get_option( TURBOBOOST_OPTIONS['cdn'], false )){
                $src = $this->detect_by_hash( $ext, $src, $handle );
                $src = $this->detect_plugin_asset( $ext, $src, $handle );
            }
            $this->register_new_src($src);
        }
        if(get_option( TURBOBOOST_OPTIONS['cdn'], false )){
            $ran_already = true;
                        if($old_src !== $src){
                $this->cdn_links[$old_src] = $src;
            }
        }
        return $src;
    }
    
    function register_new_src($src){
        $this->all_urls[] = $src;
    }

    function get_plugin_dir_file( $plugin_slug ) {
    
        $active_plugins = get_option( 'active_plugins' );
    
        if ( in_array( "$plugin_slug/$plugin_slug.php", $active_plugins, true ) ) {
            return "$plugin_slug/$plugin_slug.php";
        }
    
        foreach ( $active_plugins as $key => $value ) {
    
            if ( $this->starts_with( $value, $plugin_slug ) ) {
                return $value;
            }
        }
        return false;
    }
    public function update_external_domains(){
        if ( get_option( TURBOBOOST_OPTIONS['all_urls'], false ) ) {
            update_option( TURBOBOOST_OPTIONS['all_urls'], $this->all_urls );
        }
        else{
            add_option( TURBOBOOST_OPTIONS['all_urls'], $this->all_urls );
        }
    }
    function save_cdn_links_data(){
        update_option( TURBOBOOST_OPTIONS['cdn_links'], $this->cdn_links );
        turboboost_save_cdn_distribution();
        turboboost_create_cdnbandwidth_data_api();
    }
    function detect_plugin_asset( $ext, $src, $handle ) {
    
        if ( $this->starts_with( $src, 'https://cdn.jsdelivr.net' ) ) {
            return $src;
        }
    
        preg_match( "#/plugins/(?<plugin_slug>[^/]+)/(?<path>.*\.$ext)#", $src, $matches );
    
        if ( ! empty( $matches['plugin_slug'] ) ) {
            $plugin_dir_file = $this->get_plugin_dir_file( $matches['plugin_slug'] );
        }
    
        if ( empty( $plugin_dir_file ) ) {
            return $src;
        }
    
        $plugin_ver     = $this->get_plugin_version( $plugin_dir_file );
        $cdn_file       = "https://cdn.jsdelivr.net/wp/{$matches['plugin_slug']}/tags/$plugin_ver/{$matches['path']}";
        $transient_name = "jsdelivr_this_{$cdn_file}_exists";
        $file_exists    = get_transient( $transient_name );
    
        if ( false === $file_exists ) {
    
            // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
            $file_headers = @get_headers( $cdn_file );
    
            if ( 'HTTP/1.1 404 Not Found' === $file_headers[0] ) {
                $file_exists = 'no';
            } else {
                $file_exists = 'yes';
            }
    
            // Random time between 24 and 48h to avoid calls getting made every pageload (if only one lonely visitor)
            set_transient( $transient_name, $file_exists, wp_rand( DAY_IN_SECONDS, DAY_IN_SECONDS * 2 ) );
        }
    
        if ( 'yes' === $file_exists ) {
            $src = $cdn_file;
        }
    
        return $src;
    }

    function get_plugin_version( $plugin_file ) {
        $plugin_data =$this->get_plugin_data( WP_PLUGIN_DIR . "/$plugin_file", false, false );
        return $plugin_data['Version'];
    }
    
    function detect_by_hash( $ext, $src, $handle ) {
        if ( $this->starts_with( $src, 'https://cdn.jsdelivr.net' ) ) {
            return $src;
        }
    
        $parsed_url = wp_parse_url( $src );
        $file       = rtrim( ABSPATH, '/' ) . $parsed_url['path'];
        $file_alt   = rtrim( dirname( ABSPATH ), '/' ) . $parsed_url['path'];
        if ( is_file( $file ) ) {
            $data = $this->get_jsdeliver_hash_api_data( $file );
        };
        if ( is_file( $file_alt ) ) {
            $data = $this->get_jsdeliver_hash_api_data( $file_alt );
        };
    
        if ( isset( $data['type'] ) && 'gh' === $data['type'] ) {
            $src = "https://cdn.jsdelivr.net/{$data['type']}/{$data['name']}@{$data['version']}{$data['file']}";
        }
        return $src;
    }
    
    function get_jsdeliver_hash_api_data( $file_path ) {
    
        $transient_name = "jsdelivr_this_hashapi_wp{$GLOBALS['wp_version']}_$file_path";
        $result         = get_transient( $transient_name );
    
        if ( true ) {
    
            // Local file, no need for wp_remote_get
            // phpcs:disable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
            $result       = array();
            $file_content = file_get_contents( $file_path );
            // phpcs:enable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
            if ( $file_content ) {
                $sha256 = hash( 'sha256', $file_content );
                // echo 'file_path '. $file_path . ' sha256 : '. $sha256;
                $data   = wp_safe_remote_get( "https://data.jsdelivr.com/v1/lookup/hash/$sha256", array() );
                if ( ! is_wp_error( $data ) ) {
                    $result = (array) json_decode( wp_remote_retrieve_body( $data ), true );
                    // print_r( $result );
                }
            }
            set_transient( $transient_name, $result, wp_rand( DAY_IN_SECONDS, DAY_IN_SECONDS * 2 ) );
        }
        return $result;
    }
    
    function contains( $haystack, $needle ) {
        return strpos( $haystack, $needle ) !== false;
    }
    
    function starts_with( $haystack, $needle ) {
        return $haystack[0] === $needle[0] ? strncmp( $haystack, $needle, strlen( $needle ) ) === 0 : false;
    }
    
    function get_plugin_data( $plugin_file, $markup = true, $translate = true ) {

        $default_headers = array(
            'Name'        => 'Plugin Name',
            'PluginURI'   => 'Plugin URI',
            'Version'     => 'Version',
            'Description' => 'Description',
            'Author'      => 'Author',
            'AuthorURI'   => 'Author URI',
            'TextDomain'  => 'Text Domain',
            'DomainPath'  => 'Domain Path',
            'Network'     => 'Network',
            // Site Wide Only is deprecated in favor of Network.
            '_sitewide'   => 'Site Wide Only',
        );
    
        $plugin_data = get_file_data( $plugin_file, $default_headers, 'plugin' );
    
        // Site Wide Only is the old header for Network
        if ( ! $plugin_data['Network'] && $plugin_data['_sitewide'] ) {
            // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
            // phpcs:disable WordPress.WP.I18n.MissingArgDomain
            /* translators: 1: Site Wide Only: true, 2: Network: true */
            _deprecated_argument( __FUNCTION__, '3.0.0', sprintf( __( 'The %1$s plugin header is deprecated. Use %2$s instead.' ), '<code>Site Wide Only: true</code>', '<code>Network: true</code>' ) );
            // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
            // phpcs:enable WordPress.WP.I18n.MissingArgDomain
            $plugin_data['Network'] = $plugin_data['_sitewide'];
        }
        // phpcs:disable WordPress.PHP.StrictComparisons.LooseComparison
        $plugin_data['Network'] = ( 'true' == strtolower( $plugin_data['Network'] ) );
        // phpcs:enable WordPress.PHP.StrictComparisons.LooseComparison
        unset( $plugin_data['_sitewide'] );
    
        // If no text domain is defined fall back to the plugin slug.
        if ( ! $plugin_data['TextDomain'] ) {
            $plugin_slug = dirname( plugin_basename( $plugin_file ) );
            if ( '.' !== $plugin_slug && false === strpos( $plugin_slug, '/' ) ) {
                $plugin_data['TextDomain'] = $plugin_slug;
            }
        }
    
        if ( $markup || $translate ) {
            $plugin_data = _get_plugin_data_markup_translate( $plugin_file, $plugin_data, $markup, $translate );
        } else {
            $plugin_data['Title']      = $plugin_data['Name'];
            $plugin_data['AuthorName'] = $plugin_data['Author'];
        }
        return $plugin_data;
    }
    function cdn_link_update_cron_activation() {
        // Schedule the event to run every hour
        if (!wp_next_scheduled('cdn_link_cron_event')) {
            wp_schedule_event(time(), 'daily', 'cdn_link_cron_event');
        }
    }

    public function cdn_link_cron_function() {

        $getData = get_option( TURBOBOOST_OPTIONS['cdn_links'], false );
        if(!empty($getData)){
            $getData = false;
            update_option( TURBOBOOST_OPTIONS['cdn'], $getData );
        }
    }
}

endif;
if (class_exists('Turboboost_Exp_cdn')) {
    $turboboost_cdn = new Turboboost_Exp_cdn();
        add_action('wp_enqueue_scripts', array($turboboost_cdn, 'apply_filter_script'));
}
