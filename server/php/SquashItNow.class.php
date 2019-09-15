<?php
/**
 * Main Squash It! Now control class
 */
class wbfy_si_SquashItNow
{
    /**
     * Set up Squash It! Now options and AJAX endpoints
     */
    public function init()
    {
        add_action('wp_ajax_wbfy_si_files', array($this, 'getFiles'));
        add_action('wp_ajax_wbfy_si_file', array($this, 'processFile'));
    }

    /**
     * Get list of files that are candidates for being processed
     * ie: bigger than the dimensions configured in options
     * Called via AJAX from Javascript client
     *
     * @return json Output is echoed as JSON data
     */
    public function getFiles()
    {
        global $wpdb;

        // Validate nonce
        check_ajax_referer('wbfy-squash-it-verify-secure', 'verify');

        // Initialise Images class and make sure there is a
        // image processing library available
        $image_lib = new wbfy_si_Libs_Images;
        if (!$image_lib->loaded()) {
            wp_send_json_success(['status' => $image_lib->error()]);
            return;
        }

        // In effect, set batch limit only if max_execution_time can't be set to zero
        ini_set('max_execution_time', 0);
        $batch_limit = ini_get('max_execution_time') * 10;

        // Testing
        // $batch_limit = 5;

        // Used for timing how long getFiles() takes to complete
        $start   = microtime(true);
        $options = wbfy_si_Options::getInstance();

        $scan_dir = wp_upload_dir();
        $scanner  = new RecursiveDirectoryIterator(trailingslashit($scan_dir['basedir']));

        // Initialise results structure
        $files = array(
            'count' => 0,
            'list'  => array(),
        );
        $meta_ids = array();

        $sql    = "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'";
        $images = $wpdb->get_col($sql);

        // Get list of meta ID's for images uploaded through WordPress
        foreach ($images as $id) {
            $file = get_attached_file($id);
            if ($file !== false) {
                $meta_ids[dirname($file)][basename($file)] = $id;
            }
        }

        // Recusive scan of uploads folder
        foreach (new RecursiveIteratorIterator($scanner) as $file) {
            // Check filename extension matches
            if (preg_match('/.*\.(jpeg|jpg|png|bmp)$/i', $file)) {
                $image_lib->setSource($file);
                $size = $image_lib->getExifSize();
                // Check if file is candidate for resizing
                if ($this->isCandidate($size)) {
                    // Don't squash if filename contains 'nosquash'
                    if (strpos(strtolower($file), 'nosquash') === false) {
                        $dir      = dirname($file);
                        $filename = basename($file);

                        $files['count']++;
                        $files['list'][$dir][] = [
                            'name' => $filename,
                            'id'   => (isset($meta_ids[$dir][$filename])) ? $meta_ids[$dir][$filename] : 0,
                            'size' => $size,
                        ];
                        // Terminate if batch limit is reached
                        if ($batch_limit > 0 && $files['count'] >= $batch_limit) {
                            break;
                        }
                    }
                }
            }
        }

        // return JSON results to caller
        wp_send_json_success(
            array(
                'status'     => 'OK',
                'secs'       => microtime(true) - $start,
                'files'      => $files,
                'batch_mode' => array(
                    'on'    => ($batch_limit > 0) ? true : false,
                    'limit' => $batch_limit,
                ),
            )
        );
    }

    /**
     * Process and resize single file
     * Called via AJAX from Javascript client
     *
     * @return json Output is echoed as JSON data
     */
    public function processFile()
    {
        // Validate nonce
        check_ajax_referer('wbfy-squash-it-verify-secure', 'verify');

        // Initialise Images class and make sure there is a
        // image processing library available
        $image_lib = new wbfy_si_Libs_Images;
        if (!$image_lib->loaded()) {
            wp_send_json_success(json_encode(['status' => $image_lib->error()]));
            return;
        }

        // Retrieve and sanitize input data
        $path     = sanitize_text_field($_GET['path']);
        $filename = sanitize_text_field($_GET['filename']);
        $id       = intval(sanitize_text_field($_GET['id']));
        $dry_run  = ($_GET['dry_run'] == 'yes') ? true : false;

        // Do some checks to mark sure filename and path are set
        // Checking at this stage allows for more meaningful error description
        // The actual file is checked for in the Images class
        if (empty($filename)) {
            wp_send_json_success(['status' => __('Invalid filename', 'wbfy-update-it')]);
            return;
        }

        if (empty($path)) {
            wp_send_json_success(['status' => __('Invalid path', 'wbfy-update-it')]);
            return;
        }

        // Combine path and filename to make fully qualified image source
        $source = trailingslashit($path) . $filename;

        // Get resize options from settings
        // Set to zero if not enabled
        $settings   = wbfy_si_Options::getInstance()->settings;
        $max_width  = ($settings['resize']['max_width']['enabled']) ? $settings['resize']['max_width']['value'] : 0;
        $max_height = ($settings['resize']['max_height']['enabled']) ? $settings['resize']['max_height']['value'] : 0;
        $quality    = ($settings['resize']['quality']['enabled']) ? $settings['resize']['quality']['value'] : -1;

        // Set image source
        $image_lib->setSource($source);

        // Resize image
        $result = $image_lib->resize($max_width, $max_height, $quality, $dry_run);

        if (is_array($result)) {
            // Update WordPress image meta data sizes if it exists
            if ($id > 0 && !$dry_run) {
                $meta           = wp_get_attachment_metadata($id);
                $meta['width']  = $result['width'];
                $meta['height'] = $result['height'];
                wp_update_attachment_metadata($id, $meta);
            }

            // Return JSON results to caller
            wp_send_json_success(
                array(
                    'status' => 'OK',
                    'sizes'  => $result,
                )
            );
        } else {
            // Return JSON error to caller
            wp_send_json_success(
                array(
                    'status' => $image_lib->error(),
                )
            );
        }
    }

    /**
     * Add Squash It! Now option to Tools menus in WP Admin
     */
    public function addToMenu()
    {
        add_management_page(
            __('Squash It Now!', 'wbfy-update-it'),
            __('Squash It Now!', 'wbfy-update-it'),
            'edit_files',
            'wbfy-squash-it-now',
            array($this, 'render')
        );
    }

    /**
     * Check whether file size matches set options
     *
     * @param array $size Array as returned by getSize or getExifSize
     * @param boolean True if file is candidate
     */
    private function isCandidate($size)
    {
        $options = wbfy_si_Options::getInstance();
        $is      = false;
        if ($options->settings['resize']['max_width']['enabled'] && $size['width'] > $options->settings['resize']['max_width']['value']) {
            $is = true;
        }
        if ($options->settings['resize']['max_height']['enabled'] && $size['height'] > $options->settings['resize']['max_height']['value']) {
            $is = true;
        }
        return $is;
    }

    /**
     * Render and echo Squash It! Now WP Admin template
     */
    public function render()
    {
        // Check user access rights
        if (!current_user_can('edit_files')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wbfy-update-it'));
        }

        wbfy_si_Libs_WordPress_Functions::enqueueJQuery();
        wp_enqueue_script('wbfy-squash-it-js', plugins_url('/wbfy-squash-it/resources/js/wbfy-squash-it.min.js'), false, WBFY_SI_VERSION);
        wp_enqueue_style('wbfy-squash-it-css', plugins_url('/wbfy-squash-it/resources/css/wbfy-squash-it.min.css'), false, WBFY_SI_VERSION);

        // Output rendered template
        echo wbfy_si_Libs_WordPress_Functions::render(
            'server/skin/squash-it-now.php',
            wbfy_si_Options::getInstance()->settings
        );
    }
}
