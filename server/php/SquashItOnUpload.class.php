<?php
/**
 * Main Squash It! On Upload
 */
class wbfy_si_SquashItOnUpload
{
    /**
     * Set up Squash It! On Upload
     */
    public function init()
    {
        $options = wbfy_si_Options::getInstance();
        if ($options->settings['auto']['on_upload']) {
            add_filter('wp_handle_upload', array($this, 'handler'));
        }
    }

    /**
     * Handle uploaded file and resize if required
     *
     * @param  array $info Information about the upload provided by the filter hook
     * @return array $info Original file info, updated if required
     */
    public function handler($info)
    {
        // Ignore files marked as nosquash
        if (strpos($info['file'], 'nosquash') !== false) {
            return $info;
        }

        // Ignore none images
        if (preg_match('/.*\.(' . WBFY_SI_IMAGE_EXTENSIONS . ')$/i', $info['file'])) {
            return $info;
        }

        // Initialise Images class and make sure there is a
        // image processing library available
        $image_lib = new wbfy_si_Libs_Images;
        $image_lib->setSource($info['file']);

        if (!$image_lib->loaded()) {
            return wp_handle_upload_error(
                $info['file'],
                __($image_lib->error(), 'wbfy-squash-it')
            );
        }

        // Get resize options from settings
        // Set to zero if not enabled
        $settings   = wbfy_si_Options::getInstance()->settings;
        $max_width  = ($settings['resize']['max_width']['enabled']) ? $settings['resize']['max_width']['value'] : 0;
        $max_height = ($settings['resize']['max_height']['enabled']) ? $settings['resize']['max_height']['value'] : 0;
        $quality    = ($settings['resize']['quality']['enabled']) ? $settings['resize']['quality']['value'] : -1;
        $dry_run    = false;

        // TODO: could add option to convert oher formats to jpg
        //       not sure if this is really necessary though

        // Resize image
        $result = $image_lib->resize($max_width, $max_height, $quality, $dry_run);

        if (!is_array($result)) {
            return wp_handle_upload_error(
                $info['file'],
                __($image_lib->error(), 'wbfy-squash-it')
            );
        }

        return $info;
    }
}
