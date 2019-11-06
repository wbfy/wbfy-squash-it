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
        // Initialise Images class and make sure there is a
        // image processing library available
        $image_lib = new wbfy_si_Libs_Images;
        if (!$image_lib->loaded()) {
            return wp_handle_upload_error(
                $info['file'],
                __($image_lib->error(), 'wbfy-squash-it')
            );
        }
        $image_lib->setOptions(wbfy_si_Options::getInstance()->settings['image']);
        $image_lib->setSource($info['file']);

        // Resize image
        if ($image_lib->isCandidate()) {
            $result = $image_lib->resize(false);
            if (!is_array($result)) {
                return wp_handle_upload_error(
                    $info['file'],
                    __($image_lib->error(), 'wbfy-squash-it')
                );
            }
        }

        return $info;
    }
}
