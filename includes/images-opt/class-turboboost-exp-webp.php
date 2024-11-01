<?php

if (!defined('ABSPATH')) die('No direct access allowed');

use WebPConvert\WebPConvert;

if (!class_exists('Turboboost_Exp_Webp')) :

    // Load action scheduler.
    $action_scheduler = require_once dirname(dirname(dirname( __FILE__ ) )). '/vendor/woocommerce/action-scheduler/action-scheduler.php';

    define('TURBOBOOST_PLUGIN_GET_MAIN_PATH', dirname(dirname(plugin_dir_path(__FILE__))));
    class Turboboost_Exp_Webp {
        /**
         * The type of cron we want to fire.
         *
         * @var string
         */
        public $cron_type = 'turboboost_convert_image_to_webp_cron';

        /**
         * The batch limit.
         *
         * @var int The batch limit.
         */
        const BATCH_LIMIT = 50;

        const IMAGETYPE_JPEG = 'image/jpeg';

        const IMAGETYPE_JPG = 'image/jpg';

        const IMAGETYPE_PNG = 'image/png';

        const WEBP_OPTION = 'turboboost_convert_webp';

        const COMPRESSION_OPTION = 'turboboost_compress_all_images';

        public $image_version = 'turboboost_webp_version';

        /**
         * Initialize, add actions and filters
         *
         * @return void
         */
        public function __construct()
        {
            require_once TURBOBOOST_PLUGIN_GET_MAIN_PATH . '/' . 'constants.php';
            require_once TURBOBOOST_PLUGIN_GET_MAIN_PATH . '/' . 'helpers.php';
            
            $this->setup_cron_event();
            add_action('turboboost_convert_image_to_webp_cron', array($this, 'turboboost_convert_image_to_webp_exec'));

            add_action('updated_option', array($this, 'options_changed'), 10, 3);
            if(turbo_check_woocommerce_active()){

                add_filter('wp_get_attachment_image_src', [$this, 'custom_modify_image_src'], 10, 4);
            }
            if ( get_option(TURBOBOOST_OPTIONS['webp'], false) ) {
                add_action('init', array($this, 'action_scheduler_load'));
            }
        }

        public function action_scheduler_load(){
            add_action('one_time_action_asap', array($this, 'turboboost_convert_image_to_webp_exec'));
        
            if (isset($_GET['one_time_action'])) {
                as_enqueue_async_action('one_time_action_asap');
            }
        }

        function custom_modify_image_src($image, $attachment_id, $size, $icon){
            if (is_product()) {
                $image = $this->serve_webp_image($image);
            }
            return $image;
        }
        
        /**
         * Check at rendering image on webpage if webp format exist then render webp
         */
        public function check_webp_format() {
            if ( get_option( TURBOBOOST_OPTIONS['webp'], false) ) {
                add_filter('wp_generate_attachment_metadata', array($this, 'convert_image_to_webp_on_upload'), 10, 2);
            } else {
                if ( get_option( TURBOBOOST_OPTIONS['compression'] ) ) {
                    add_filter( 'wp_handle_upload', array($this, 'compress_image_on_upload') );
                }
            }

            add_action( 'delete_attachment', array( $this, 'remove_backup_on_media_delete' ) );
        }

         /**
         * Trigger Webp Conversion or Compress Image action when enable plugin settings
         *
         * @param array      $option_name,$old_value, $new_value
         * @return void
         */
        public function options_changed( $option, $old_value, $new_value ) {
            if ( $option == TURBOBOOST_OPTIONS['webp'] ) {
                if ( get_option(TURBOBOOST_OPTIONS['webp'], false) ) {
                    // $this->turboboost_convert_image_to_webp_exec();
                }
            }

            if ( $option == self::COMPRESSION_OPTION ) {
                if ( get_option(self::COMPRESSION_OPTION, false) ) {
                    if ( !get_option(TURBOBOOST_OPTIONS['webp'], false) ) {
                        $this->compress_all_images();
                    }
                }
            }
        }

        /***************** WEBP FUNCTIONALITY START *********************************/

        /**
         * Setup cron event to convert images to webp once in a day
         */
        private function setup_cron_event() {
            if ( !wp_next_scheduled('turboboost_convert_image_to_webp_cron') ) {
                wp_schedule_event(time(), 'daily', 'turboboost_convert_image_to_webp_cron');
            }
        }

        /**
         * Cron Scheduled execution to convert images to webp once in a day
         *
         * @return void
         */
        public function turboboost_convert_image_to_webp_exec() {

            if ( get_option( TURBOBOOST_OPTIONS['webp'], false) ) {
                // Initialize batch size
                $batch_size = self::BATCH_LIMIT;
                $offset = 0;
             
                // $image_ids = $this->get_images_batch();
                do {
                    // Custom SQL query to retrieve a batch of data
                    $image_ids = get_posts(
                        array(
                            'post_type'      => 'attachment',
                            'post_mime_type' => 'image',
                            'numberposts'    => $batch_size,
                            'offset'         => $offset,
                            'fields'         => 'ids',
                            'meta_query'     => array(
                                array(
                                    'key'     => $this->image_version,
                                    'compare' => 'NOT EXISTS',
                                ),
                            )
                        )
                    );
                    // Return if no image data presents
                    if ( empty($image_ids) ) {
                        return;
                    }
                    // Loop through all images and optimize them.
                    foreach ( $image_ids as $image_id ) {
                        
                        $file_path = get_attached_file( $image_id );
                        if ( !file_exists($file_path) ) {
                            continue;
                        }

                        $file_type = wp_check_filetype($file_path);
                        $metadata = wp_get_attachment_metadata($image_id);

                        if ($file_type['ext'] === 'webp') {
                            update_post_meta($image_id, $this->image_version, 'yes');
                            continue;
                        }

                        if ($metadata && isset($metadata['file'])) {
                            if ($file_type['ext'] === 'jpg' || $file_type['ext'] === 'jpeg' || $file_type['ext'] === 'png') {
                                
                                $backup_filepath = preg_replace( '~.(png|jpg|jpeg|gif)$~', '.turbo_bak.$1', $file_path );

                                $file_path = file_exists( $backup_filepath ) ? $backup_filepath : $file_path;
            
                                $webp_file_path = preg_replace( '~(\.turbo_bak)?\.(png|jpg|jpeg|gif)$~', '.webp', $file_path );
                            
                                if (file_exists($webp_file_path)) {
                                    update_post_meta($image_id, $this->image_version, 'yes');
                                    continue;
                                }

                                WebPConvert::convert($file_path, $webp_file_path);
                                // Check webp version successfully created
                                if (file_exists($webp_file_path)) {
                                    list($width, $height) = getimagesize($webp_file_path);

                                    $metadata['sizes']['webp'] = array(
                                        'file' => pathinfo($metadata['file'], PATHINFO_FILENAME) . '.webp',
                                        'width' => $width,
                                        'height' => $height,
                                        'mime-type' => 'image/webp',
                                        'filesize' => filesize($webp_file_path)
                                    );
                                    // Update the attachment metadata.
                                    wp_update_attachment_metadata($image_id, $metadata);

                                    update_post_meta($image_id, $this->image_version, 'yes');
                                }
                            }
                        }
                    }
                    // Increment the offset for the next batch
                    $offset += $batch_size;
                } while (count($image_ids) > 0);

            }  
            error_log("turboboost webp conversion cron event executed:" . gmdate('d/m/Y h:i:s A'));
        }

        /**
         * Create webp version of uploaded image like jpg,png etc.
         * @param $file to be upload
         * 
         * @return $file- converted file
         * 
         * @since    1.0.0
         */
        public function convert_image_to_webp_on_upload( $metadata, $attachment_id ) {

            $file_path = get_attached_file($attachment_id);

            // Check if it's an image file
            $file_type = wp_check_filetype($file_path);

            if ($file_type['type'] === self::IMAGETYPE_JPG || $file_type['type'] === self::IMAGETYPE_JPEG || $file_type['type'] === self::IMAGETYPE_PNG) {

                $new_file_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $file_path);
                try {
                    // Convert image to webp
                    WebPConvert::convert($file_path, $new_file_path);
                } catch (\WebPConvert\Convert\Exceptions\ConversionFailedException $e) {
                    //Handle the exception
                    $this->handle_exception($e);
                    // Return original file
                    return $metadata;
                }
                // Update the file path in the metadata array
                $metadata['file'] = $new_file_path;
            }
            return $metadata;
        }

        /**
         * Error log exception if conversion to webp fails 
         */
        private function handle_exception( $exception ) {
            // Your custom exception handling logic
            error_log('Failed Webp Converter Exception: ' . $exception->getMessage());
        }

        /**
         * Delete webp version status from DB on 
         *
         * @return void
         */
        public function delete_metadata() {
            delete_post_meta_by_key( $this->image_version );
        }

        /**
         * Check webp version on page load.
         * 
         * @since    1.0.0
         */
        public function check_webp_version() {
            if ( get_option( TURBOBOOST_OPTIONS['webp'], false) ) {
                add_filter( 'wp_content_img_tag', [$this,'myplugin_modify_content_image_html'], 10, 3 );
            }
        }

        /**
         * Replace url if webp version exists
         * 
         * @since    1.0.0
         */
        public function myplugin_modify_content_image_html( $filtered_image, $context, $attachment_id ) {
            $image_src = wp_get_attachment_image_src($attachment_id, 'full');
            
            if ( isset($image_src) && is_array($image_src) ) {
                $image_url = $image_src[0];
                $updated_image = $this->serve_webp_image($image_src);
                $webp_url = $updated_image[0];

                if($image_url !== $webp_url){
                    // Replace the original image URL with the WebP version
                    $filtered_image = str_replace($image_url, $webp_url, $filtered_image);
                }
            }

            return $filtered_image;
        }

        public function serve_webp_image($image){
            if(is_array($image)){
                $image_url = $image[0];
                $webp_url = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $image_url);
                $response = wp_remote_head($webp_url);
    
                if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200){
                    $image[0] = $webp_url;
                }
            }
            
            return $image;
        }

        /***************** WEBP FUNCTIONALITY END *********************************/

        /***************** COMPRESSION FUNCTIONALITY START *********************************/

        /**
         * Compress image on upload.
         * @param $file to be upload
         * 
         * @return $file-path of compressed file
         * 
         * @since    1.0.0
         */
        public function compress_image_on_upload( $file ) {
            $image_path = $file['file'];

            $backup_filepath = preg_replace('~.(png|jpg|jpeg|gif)$~', '.turbo_bak.$1', $image_path);
            
            if ( file_exists($backup_filepath) ) {
                return $file;
            }

            copy($image_path, $backup_filepath); // Create a backup before compression

            $file_type = wp_check_filetype($image_path);

            if ($file_type['type'] == self::IMAGETYPE_JPEG) {
                $source_image = imagecreatefromjpeg($image_path);
                imagejpeg($source_image, $image_path, 80); // lossless compression
            } elseif ($file_type['type'] == self::IMAGETYPE_PNG) {
                $source_image = imagecreatefrompng($image_path);
                imagepng($source_image, $image_path, 8);
            } else {
                return $file;
            }

            if ( isset($source_image) && file_exists($source_image) ) {
                wp_delete_file($source_image);
            }
            return $file;
        }

        /**
         * Compress jpeg,png with GD image quality 80.
         * @param $file to be compress
         * 
         * @return $file- converted file
         * 
         * @since    1.0.0
         */
        public function compress_images_with_gd( $attachment_id ) {
            $image_path = get_attached_file($attachment_id);

            if ( !file_exists($image_path) ) {
                return;
            }
            // Create backup file
            $backup_filepath = preg_replace('~.(png|jpg|jpeg|gif)$~', '.turbo_bak.$1', $image_path);

            if ( file_exists($backup_filepath) ) {
                return;
            }

            if (wp_attachment_is_image($attachment_id) && file_exists($image_path)) {
                copy($image_path, $backup_filepath); // Create a backup before compression

                $file_type = wp_check_filetype($image_path);

                if ($file_type['type'] == self::IMAGETYPE_JPEG) {
                    $source_image = imagecreatefromjpeg($image_path);
                    imagejpeg($source_image, $image_path, 80); // lossless compression
                } elseif ($file_type['type'] == self::IMAGETYPE_PNG) {
                    $source_image = imagecreatefrompng($image_path);    
                    imagepng($source_image, $image_path, 8);
                } else {
                    return;
                }
                // Free up memory associated with the GD image resource
                if ( isset($source_image) && file_exists($source_image) ) {
                    wp_delete_file($source_image);
                }
            }
        }

        /**
         * Compress images.
         * 
         * @since    1.0.0
         */
        public function compress_all_images() {
          
            if ( did_action('compress_all_images' > 1) ) {
                return;
            }
           // Initialize batch size
           $batch_size = self::BATCH_LIMIT;
           $offset = 0;
            // Get images Id from database
            // $attachments = $this->get_images_data_from_db();
            do {
                $attachments = get_posts(
                    array(
                        'post_type' => 'attachment',
                        'numberposts'    => $batch_size,
                        'offset'         => $offset,
                        'post_mime_type' => 'image',
                        'fields'         => 'ids',
                    )
                );
                
                if ( empty($attachments) ) {
                    return;
                }
                
                foreach ( $attachments as $attachment_id ) {
                    $this->compress_images_with_gd( $attachment_id );
                }
                // Increment the offset for the next batch
                $offset += $batch_size;

            } while( count($attachments)>0 );
        }

          /**
         * Restore images on plugin deactivate
         * Delete backup image files
         * @since    1.0.0
         */
        public function restore_original_images() {
            // Get images Id from database
            $attachments = $this->get_images_data_from_db();

            if (empty($attachments)) {
                return;
            }

            foreach ($attachments as $attachment_id) {
                $image_path = get_attached_file($attachment_id);

                if( !file_exists($image_path) ) {
                    continue;
                }
                // Find backup image path.
                $backup_filepath = preg_replace('~.(png|jpg|jpeg|gif)$~', '.turbo_bak.$1', $image_path);

                // Check if backup file exists, if so, replace the file with the original one.
                if (file_exists($backup_filepath)) {
                    copy($backup_filepath, $image_path);
                }
            }
        }
        /***************** COMPRESSION FUNCTIONALITY END *********************************/

        /***************** RESIZE FUNCTIONALITY START *********************************/
        
        /**
         * On page load check for resize image
         * 
         * @since    1.0.0
         */
        public function initial_load() {
            add_action('init', array($this, 'optimize_existing_images'));
        }

        /**
         * To Resize Image get attachments from database
         * @param $file to be resized
         * 
         * @since    1.0.0
         */
        public function optimize_existing_images() {  
            // Get all attachments from the media library
            $resize_image = get_option( TURBOBOOST_OPTIONS['resize'], false );

            if ( !$resize_image ) {
                return;
            }

            // Get images Id from database
            $attachments = $this->get_images_data_from_db();
            // Initialize batch size
           $batch_size = self::BATCH_LIMIT;
           $offset = 0;
           
            do {
                $attachments = get_posts(
                    array(
                        'post_type' => 'attachment',
                        'numberposts'    => $batch_size,
                        'offset'         => $offset,
                        'post_mime_type' => 'image',
                        'fields'         => 'ids',
                    )
                );

                if ( empty($attachments) ) {
                    return;
                }

                $order = 0;
                foreach ($attachments as $attachment_id) {
                    $order += 1;
                    $file_path = get_attached_file($attachment_id);
                
                    // Define the maximum image dimensions (in pixels)
                    if ( $resize_image && !get_option( 'turboboost_webp_optimized', false ) ) {
                        $this->resize_images( $file_path );
                    }
                }
                 // Increment the offset for the next batch
                 $offset += $batch_size;
            } while( count($attachments)>0 ); 
            update_option('turboboost_webp_optimized', true);
        }


        /**
         * Resize Image.
         * @param $file to be resized
         * 
         * @since    1.0.0
         */
        public function resize_images( $file_path ) {
            // Check if the file is an image
            $file_type = wp_check_filetype( $file_path );
            if ( strpos($file_type['type'], 'image') !== 0 ) {
                // Not an image
                return;
            }

            $max_width = 1200;
            $max_height = 1200;
            $jpeg_quality = 90;

            if ( file_exists( $file_path ) ) {

                $webp_filepath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $file_path);
                    
                if ( file_exists($webp_filepath) ) {
                   return; 
                }  

                $backup_filepath = preg_replace('~.(png|jpg|jpeg|gif)$~', '.turbo_bak.$1', $file_path);

                if ( !file_exists($backup_filepath) ) {
                    copy($file_path, $backup_filepath); // Create a backup before resize
                }

                // Get the original image dimensions
                list($original_width, $original_height) = getimagesize( $file_path );

                // Check if the image dimensions exceed the maximum
                if ($original_width > $max_width || $original_height > $max_height) {
                    // Calculate the aspect ratio
                    $aspect_ratio = $original_width / $original_height;

                    // Calculate new dimensions based on the aspect ratio
                    if ($original_width > $original_height) {
                        $new_width = $max_width;
                        $new_height = $max_width / $aspect_ratio;
                    } else {
                        $new_height = $max_height;
                        $new_width = $max_height * $aspect_ratio;
                    }

                     // Load the image using WordPress functions
                    $editor = wp_get_image_editor( $file_path );

                    if (!is_wp_error($editor)) {
                        $editor->set_quality( $jpeg_quality );
                        // Resize the image to fit within the maximum dimensions while maintaining aspect ratio
                        $resized =  $editor->resize($new_width, $new_height, false);

                        // Save the optimized image over the original
                        $saved = $editor->save($file_path);

                        if ( is_wp_error( $saved ) )
                            error_log("Turboboost Error during file resize :" . $saved->get_error_message());
                    }
                }
            }
                    }

        /***************** RESIZE FUNCTIONALITY END *********************************/

        public function init() {
            if ( class_exists('Turboboost_Exp_Webp') ) {
                $this->check_webp_format();
            }
        }

        /**
         * Get Image ids from database
         * 
         * @return array $images 
         */
        public function get_images_batch() {
            // Flush the cache before prepare a new batch.
            // wp_cache_flush();
            // Get images in batch of 200.

            $images = get_posts(
                array(
                    'post_type'      => 'attachment',
                    'post_mime_type' => 'image',
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
                    'meta_query'     => array(
                        array(
                            'key'     => $this->image_version,
                            'compare' => 'NOT EXISTS',
                        ),
                    )
                )
            );
            return $images;
        }

        /**
         * Get Images data from database
         * 
         * @return array $images_data
         */
        public function get_images_data_from_db() {

            $images_data = get_posts(
                array(
                    'post_type' => 'attachment',
                    'numberposts' => -1,
                    'post_status' => null,
                    'post_mime_type' => 'image',
                    'fields'         => 'ids',
                )
            );
            return $images_data;
        }

        /**
         * On plugin Uninstall, Delete webp and backup files from uploads folder
         *
         * @return void
         */
        public function delete_backup_images() {
            // Get images Id from database
            $attachments = $this->get_images_data_from_db();

            if (empty($attachments)) {
                return;
            }

            foreach ($attachments as $attachment_id) {
                $image_path = get_attached_file( $attachment_id );

                if( !file_exists($image_path) ) {
                    continue;
                }
                // Find backup image path.
                $backup_filepath = preg_replace('~.(png|jpg|jpeg|gif)$~', '.turbo_bak.$1', $image_path);
                 // Find webp image path.
                $webp_file_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $image_path);

                // Check if backup file exists, if so, replace the file with the original one.
                if ( file_exists($backup_filepath) ) {
                    wp_delete_file( $backup_filepath );
                }

                if ( file_exists($webp_file_path) ) {
                    wp_delete_file( $webp_file_path );
                }
                error_log('deleted backup');
            }
        }

        /**
         * On delete media, Delete the backup, webp image.
         *
         * @since  1.0.0
         *
         * @param  int $id The attachment ID.
         */
        public function remove_backup_on_media_delete( $id ) {

            $image_path = get_attached_file( $id );
                   
            // Find backup image path.
            $backup_filepath = preg_replace('~.(png|jpg|jpeg|gif)$~', '.turbo_bak.$1', $image_path);
                // Find webp image path.
            $webp_file_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $image_path);

            // Check if backup file exists, if so, replace the file with the original one.
            if ( file_exists($backup_filepath) ) {
                wp_delete_file( $backup_filepath );
            }

            if ( file_exists($webp_file_path) ) {
                wp_delete_file( $webp_file_path );
            }
        }

        function webp_image_converter($file_path, $attachment_id, $order)
        {  
            // check the file type
            $file_type = wp_check_filetype($file_path);
            $upload_dir = wp_upload_dir();
            $metadata = wp_get_attachment_metadata($attachment_id);
            if ($file_type['ext'] === 'jpg' || $file_type['ext'] === 'jpeg' || $file_type['ext'] === 'png') {
                $webp_file_path = $upload_dir['path'] . '/' . pathinfo($metadata['file'], PATHINFO_FILENAME) . '.webp';
                if (file_exists($webp_file_path)) {
                    return;
                }
                $image = wp_get_image_editor($file_path);
                $image->save($webp_file_path, 'image/webp');
                $metadata['sizes']['webp'] = array(
                    'file' => pathinfo($metadata['file'], PATHINFO_FILENAME) . '.webp',
                    'width' => $image->get_size()['width'],
                    'height' => $image->get_size()['height'],
                    'mime-type' => 'image/webp'
                );
                update_post_meta($attachment_id, '_wp_attachment_metadata', $metadata);
            }
        }
    }
endif;

    if ( class_exists( 'Turboboost_Exp_Webp' ) ) {
        $turboboost_webp = new Turboboost_Exp_Webp();
        $turboboost_webp->initial_load();
        $turboboost_webp->check_webp_version();
    }
    add_action('init', array($turboboost_webp, 'init'));
