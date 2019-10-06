<?php
/**
 * Image processing wrapper
 */
class wbfy_si_Libs_Images
{
    private $lib   = null; // Loaded image library or null
    private $error = null; // Last error message or null

    /**
     * Check if image processing library is available and loaded
     *
     * @return bool true if loaded, false if not
     */
    public function __construct()
    {
        if (function_exists('gd_info')) {
            $this->lib = new wbfy_si_Libs_Images_GD;
        }
    }

    /**
     * Check if image processing library is available and loaded
     *
     * @return bool true if loaded, false if not
     */
    public function loaded()
    {
        if (is_object($this->lib)) {
            return true;
        }
        return $this->setError(__('No server side image processing library was found. Please install the PHP-GD image extension and retry.', 'wbfy-squash-it'));
    }

    /**
     * Error getter
     *
     * @return string Last error message or null
     */
    public function error()
    {
        return $this->error;
    }

    /**
     * Initialise and set source image file
     * Can be called repeatedly on single class instance
     *
     * @param string Source image file including path
     * @return mixed True on success, error string on fail
     */
    public function setSource($source)
    {
        if (is_readable($source)) {
            $result = $this->lib->setSource($source);
            if ($result !== true) {
                return $this->setError($result);
            }
            return true;
        }
        return $this->setError(__('The image file could not be found', 'wbfy-squash-it'));
    }

    /**
     * Get 'raw' image size
     * Returns an array of the following structure:
     *  'bytes'  =>   size in bytes of the image file
     *  'width'  =>   width in pixels
     *  'height' =>   height in pixels
     * @return mixed        The image size or false if there was an error
     */
    public function getSize()
    {
        $this->clearError();

        if (!$this->lib->isLoaded()) {
            return $this->setError(__('The image file has not been set or could not be loaded', 'wbfy-squash-it'));
        }

        return $this->lib->getSize($image);
    }

    /**
     * Get image size allowing for any Orientation information in the image exif data
     * Returns an array of the following structure:
     *  'bytes'  =>   size in bytes of the image file
     *  'width'  =>   width in pixels
     *  'height' =>   height in pixels
     * @return mixed        The image size or false if there was an error
     */
    public function getExifSize()
    {
        $this->clearError();

        if (!$this->lib->isLoaded()) {
            return $this->setError(__('The image file has not been set or could not be loaded', 'wbfy-squash-it'));
        }

        return $this->lib->getExifSize($image);
    }

    /**
     * Resize image according to parameters
     *
     * @param string $source      Source image path and filename
     * @param int    $max_width   Maximum image width in pixels
     *                            Minimum 300, Zero to ignore
     * @param int    $max_height  Maximum image height in pixels
     *                            Minimum 300, Zero to ignore
     * @param int    $quality     Quality level to be applied to image (where possible)
     *                            Should be a percentage between 30 and 100
     * @param string $destination Where to save the resized image to
     *                            If omitted, will overwrite source
     * @return mixed              Sucess: array of before and after image sizes in bytes
     *                              [ original_size => <bytes>, squashed_size => <bytes> ]
     *                            Failure: set error() message and return false
     */
    public function resize($max_width, $max_height, $quality, $dry_run = true, $destination = null)
    {
        $this->clearError();

        if (!$this->lib->isLoaded()) {
            return $this->setError(__('The image file has not been set or could not be loaded', 'wbfy-squash-it'));
        }

        $max_width = intval($max_width);
        if ($max_width > 0 && $max_width < 300) {
            $max_width = 300;
        }

        $max_height = intval($max_height);
        if ($max_height > 0 && $max_height < 300) {
            $max_height = 300;
        }

        $quality = intval($quality);
        if ($quality > -1 && $quality < 30) {
            $quality = 30;
        }

        if ($quality > 100) {
            $quality = 100;
        }

        // Resize it!
        $result = $this->lib->resize($max_width, $max_height, $quality, $dry_run, $destination);
        if (!is_array($result)) {
            return $this->setError($result);
        }
        return $result;
    }

    /**
     * Clear error message
     */
    private function clearError()
    {
        $this->error = null;
    }

    /**
     * Set error message
     *
     * @return boolean Always returns false to allow "return $this->setError('xx')" in main code
     */
    private function setError($result)
    {
        $this->error = $error;
        return false;
    }
}
