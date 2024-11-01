<?php

if (!defined('ABSPATH')) die('No direct access allowed');

if (!class_exists('Turboboost_Exp_Font_Opt')) :

class Turboboost_Exp_Font_Opt
{
    public  $all_urls;

    const GOOGLE_FONT_DOMAIN = 'fonts.googleapis.com';
    
    const USER_AGENT = [
		'woff2' => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:109.0) Gecko/20100101 Firefox/115.0',
	];

    public function __construct() {
        if ( get_option( TURBOBOOST_OPTIONS['all_urls'], false) ) {
            $this->all_urls = get_option( TURBOBOOST_OPTIONS['all_urls'] );
       }

       add_filter('sanitize_option_turbo_preload_urls', [$this, 'custom_sanitize_turbo_preload_urls'], 10, 3);
    }

    /**
     * The function "preload_fonts" preloads font files from specified URLs and outputs a preload link
     * tag for each font.
     * 
     * @return array font data that is fetched from the filtered URLs.
     */
    public function preload_fonts()
    {

        if (!empty($this->all_urls)) {
            $filteredUrls = array_filter($this->all_urls, function ($url) {
                $parsedUrl = wp_parse_url($url);
                return isset($parsedUrl['host']) && $parsedUrl['host'] === self::GOOGLE_FONT_DOMAIN;
            });
        }
        if (isset($filteredUrls) && count($filteredUrls) > 0) {

            foreach ($filteredUrls as $filteredUrl) {
               $font_data = $this->getFontsData($filteredUrl);      
            }
        }
        if (isset($font_data) && is_array($font_data) && count($font_data) > 0) {     
            if (get_option(TURBOBOOST_OPTIONS['preload_urls'], false)) {
                add_option(TURBOBOOST_OPTIONS['preload_urls'], $font_data);
            } else {
                update_option(TURBOBOOST_OPTIONS['preload_urls'], $font_data);
            }
        }
     
        $font_urls = get_option( TURBOBOOST_OPTIONS['preload_urls'] );

        if(!empty($font_urls['srcUrl'])) {
            foreach($font_urls as $font_url) {
                if($font_url['active']) {
                    echo '<link rel="preload" href="' . esc_url($font_url['srcUrl']) . '" as="font" type="font/woff2" crossorigin="anonymous"  >' . "\n";
                }
            }
        
        }
    }


    function preload_head()
    {
        
        if(get_option(TURBOBOOST_OPTIONS['font_preload'], false)) {
            // Hook the preload_fonts function into the wp_head action
            add_action('wp_head', array($this, 'preload_fonts'));
        }
    }

    /**
     * The function `getFontsData` retrieves font data from a given URL and returns an array of unique
     * font families and their corresponding source URLs.
     * 
     * @param filteredUrl The filteredUrl parameter is the URL of the stylesheet that contains the font
     * data. This function retrieves the font data from the stylesheet and returns an array of unique
     * font families and their corresponding source URLs.
     * 
     * @return an array of unique font families and their corresponding source URLs.
     */
    private function getFontsData($filteredUrl) {

        $response = wp_remote_get(
            $filteredUrl,
            [
            'user-agent' => apply_filters( 'turboboost_optimize_user_agent', self::USER_AGENT['woff2'] ),
            ]
        );
        $code = wp_remote_retrieve_response_code( $response );

        if ( $code !== 200 ) {
			return [];
		}

        $stylesheet = wp_remote_retrieve_body( $response );
        $uniquePairs = array();
     
        preg_match_all('/font-family:\s\'(.*?)\';[^}]*src:\surl\((.*?)\)/', $stylesheet, $matches,PREG_SET_ORDER);

        foreach ($matches as $match) {
            $fontFamily = $match[1];
            $srcUrl = $match[2];

            // Check if the fontFamily already exists in uniquePairs
            $index = array_search($fontFamily, array_column($uniquePairs, 'fontFamily'));
        
            if ($index !== false) {
                // If fontFamily exists, update srcUrl if necessary
                if ($uniquePairs[$fontFamily]['srcUrl'] !== $srcUrl) {
                    $uniquePairs[$fontFamily]['srcUrl'] = $srcUrl;
                }
            } else {
                // If fontFamily doesn't exist, add a new object to uniquePairs
                $uniquePairs[$fontFamily] = array('fontFamily' => $fontFamily, 'srcUrl' => $srcUrl , 'active' =>true);
            }
        }
        return $uniquePairs ;
    }

    // Custom callback to modify the dns_prefetch_urls option before saving
    function custom_sanitize_turbo_preload_urls($value, $option, $original_value)
    {
        $turbo_preload_urls = get_option( TURBOBOOST_OPTIONS['preload_urls'], array() );
        // Loop through Array1 and update its elements
        if(is_array($value) && count($value) > 0){
            foreach ($value as $domain => $settings) {
                if (!isset($turbo_preload_urls[$domain])) {
                    $turbo_preload_urls[$domain] = $settings;
                }
            }
        }
        if(count($turbo_preload_urls) > 0){
            foreach ($turbo_preload_urls as $domain => &$settings) {
                // Check if the key exists in Array2
                if (isset($value[$domain])) {
                    // If yes, maintain the 'active' status from Array1
                    $settings['active'] = $value[$domain]['active'];
                } else {
                    // If not, set 'active' to 0
                    $settings['active'] = 0;
                }
            }
        }
        else{
            if (count($value) > 0 && is_array($value)) {
                $turbo_preload_urls = $value;
            }
        }
        // // Return the modified value
        return $turbo_preload_urls;
    }

    function font_swap(){
        ?>
        <script type="text\javascript">
            let bodyFontFamily = window.getComputedStyle(document.body).fontFamily
            bodyFontFamily += ', sans-serif'
            
        </script>
        <?php
    }
}

endif;

if (class_exists('Turboboost_Exp_Font_Opt')) {
    $turboboost_font_opt = new Turboboost_Exp_Font_Opt();
    $turboboost_font_opt->preload_head();
}

