<?php

if (!defined('ABSPATH')) die('No direct access allowed');

if (!class_exists('Turboboost_Exp_Dns_Opt')) :

class Turboboost_Exp_Dns_Opt
{
    public $cdn_domain = 'cdn.jsdelivr.net';
    public $unique_domain_list;
    function prefetch_external_links()
    { 
        $external_domains = get_option( TURBOBOOST_OPTIONS['prefetch_urls'] );
        if (!empty($external_domains)) {
            foreach ($external_domains as $external_domain) {
                if ($external_domain['active'] && isset($external_domain['domain'])) {
                    echo '<link rel="dns-prefetch" href="' . esc_url($external_domain['domain']) . '">';
                }
            }
        }
    }
    public function __construct()
    {
        $turbo_dns_prefertch_enable = get_option(TURBOBOOST_OPTIONS['dns_prefetch'], false);
        
        if($turbo_dns_prefertch_enable)
        {
            // Add dns-prefetch tags to the <head> section
            add_action('wp_head', array($this, 'prefetch_external_links'));
            add_filter('sanitize_option_dns_prefetch_urls', [$this, 'custom_sanitize_dns_prefetch_urls'], 10, 3);
            $url_exist = get_option( TURBOBOOST_OPTIONS['all_urls'], false );
            if ($url_exist && count($url_exist) > 0 ) {
                add_action('init', [$this, 'get_unique_domain_list']);
            }
        }
    }

    // Custom callback to modify the dns_prefetch_urls option before saving
    function custom_sanitize_dns_prefetch_urls($value, $option, $original_value)
    {
        $dns_prefetch_urls = get_option( TURBOBOOST_OPTIONS['prefetch_urls'], array() );
        if (!is_array($dns_prefetch_urls)) {
            $dns_prefetch_urls = array();
        }
        // Loop through Array1 and update its elements
        if (is_array($value) && count($value) > 0) { // If new domain is not contain in the main array
            foreach ($value as $domain => $settings) {
                if (!isset($dns_prefetch_urls[$domain])) {
                    $dns_prefetch_urls[$domain] = $settings;
                }
            }
        }
        if (is_array($dns_prefetch_urls) && count($dns_prefetch_urls) > 0) { //Updating domain setting after save button
            foreach ($dns_prefetch_urls as $domain => &$settings) {
                // Check if the key exists in Array2
                if (isset($value[$domain])) {
                    // If yes, maintain the 'active' status from Array1
                    $settings['active'] = $value[$domain]['active'];
                } else {
                    // If not, set 'active' to 0
                    $settings['active'] = 0;
                }
            }
        } else { // If no domain is contain in the main array
            if (is_array($value) && count($value) > 0) {
                $dns_prefetch_urls = $value;
            }
        }
        // Return the modified value
        return $dns_prefetch_urls;
    }
    function update_main_array($main_array, $new_array)
    {

    }

    public function get_host_domain()
    {
        if ( ! isset( $_SERVER['HTTP_HOST'] ) ) {
			return '';
		}

        $host = filter_var($_SERVER['HTTP_HOST'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
        // Validate the HTTP host
        if ($host === false) {
            return '';
        }

        $host_parts = explode('.', $host);
        $tld = $host_parts[count($host_parts) - 1];
        $host_domain = $host;
        if ($tld == 'com' || $tld == 'net' || $tld == 'org' || $tld == 'co.uk' || $tld == 'co.jp' || $tld == 'de' || $tld == 'fr' || $tld == 'it' || $tld == 'es') {
            $host_domain = $host_parts[count($host_parts) - 2] . '.' . $tld;
        }
        return $host_domain;
    }

    public function get_unique_domain_list()
    {
        $urls = get_option(TURBOBOOST_OPTIONS['all_urls']);
        $unique_domains = [];
        $this->unique_domain_list = get_option( TURBOBOOST_OPTIONS['prefetch_urls'], array() );
        if (gettype($this->unique_domain_list) != 'array') {
            $this->unique_domain_list = array();
        }
        if(!empty($urls)) {
            foreach ($urls as $url) {
                $url_parts = wp_parse_url($url);
                $domain = $url_parts['host'];
                if (!in_array($domain, $unique_domains)) {
                    $unique_domains[$domain] = $domain;
                }
            }
        }    
        $unique_domains = array_filter($unique_domains, [$this, 'filter_out_domain']);
        $unique_domains = $this->cdn_checker($unique_domains);
        $unique_domains = array_map([$this, 'create_object_for_domain_list'], $unique_domains);
        $unique_domains = $this->get_array_difference_by_key($unique_domains, $this->unique_domain_list);
        if (count($unique_domains) > 0) {
            $this->unique_domain_list = array_merge($this->unique_domain_list, $unique_domains);
            if (get_option(TURBOBOOST_OPTIONS['prefetch_urls'], false)) {
                add_option(TURBOBOOST_OPTIONS['prefetch_urls'], $this->unique_domain_list);
            } else {
                update_option(TURBOBOOST_OPTIONS['prefetch_urls'], $this->unique_domain_list);
            }
        }
    }
    public function cdn_checker($domains_list)
    {
        if (get_option( TURBOBOOST_OPTIONS['cdn'] ) == true) {
            if (array_search($this->cdn_domain, $domains_list) === false) {
                $domains_list[$this->cdn_domain] = $this->cdn_domain;
            }
        }
        return $domains_list;
    }
    function get_array_difference_by_key($array1, $array2)
    {
        $difference = array_diff_key($array1, $array2);
        return $difference;
    }

    function filter_out_domain($url)
    {
        return strtolower($url !== $this->get_host_domain());
    }
    function create_object_for_domain_list($domain)
    {
        return (array) ['domain' => $domain, 'active' => true];
    }
    function check_if_domain_list_option_exits()
    {
        if (get_option( TURBOBOOST_OPTIONS['prefetch_urls'], false)) {
            return true;
        } else {
            add_option( TURBOBOOST_OPTIONS['prefetch_urls'], '');
        }
    }
}

endif;

if (class_exists('Turboboost_Exp_Dns_Opt')) {
    $turboboost_webp = new Turboboost_Exp_Dns_Opt();
}