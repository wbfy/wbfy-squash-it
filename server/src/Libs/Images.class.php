<?php
/**
 * Image processing wrapper
 */
class wbfy_si_Libs_Images
{
    private $lib     = null; // Loaded image library or null
    private $error   = null; // Last error message or null
    private $options = array(
        'resize' => array(
            'max_width'  => array(
                'enabled' => true,
                'value'   => 1920,
            ),
            'max_height' => array(
                'enabled' => false,
                'value'   => 1146,
            ),
            'quality'    => array(
                'enabled' => false,
                'value'   => 82,
            ),
        ),
        'format' => array(
            'progressive' => 'leave',
        ),
    );

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
     * Check if file will be changed
     *
     * @return bool true if file will be changed, false if not
     */
    public function isCandidate()
    {
        // Check if file mime type is supported
        if (!$this->isSupportedMimeType()) {
            $this->setError(__('Mime type not supported', 'wbfy-squash-it'));
            return false;
        }

        if (strpos(strtolower($this->getSource()), 'nosquash') !== false) {
            $this->setError(__('Image source contains nosquash flag', 'wbfy-squash-it'));
            return false;
        }

        $will_change = false;

        // Check if resize needed
        $size = $this->getExifSize();
        if ($this->options['resize']['max_width']['enabled'] && $size['width'] > $this->options['resize']['max_width']['value']) {
            $will_change = true;
        }
        if ($this->options['resize']['max_height']['enabled'] && $size['height'] > $this->options['resize']['max_height']['value']) {
            $will_change = true;
        }

        if (!$will_change) {
            $this->setError(__('File will not be changed', 'wbfy-squash-it'));
            return false;
        }
        return true;
    }

    /**
     * Get image source file
     *
     * @return string image source with path
     */
    public function getSource()
    {
        return $this->lib->getSource();
    }

    /**
     * Check if image mime type is supported
     *
     * @return bool true if supported
     */
    public function isSupportedMimeType()
    {
        return $this->lib->isSupportedMimeType();
    }

    /**
     * Set image processing options
     *
     * @return void
     */
    public function setOptions($options)
    {
        $this->options = wbfy_si_Libs_Arrays::extend($this->options, $options);

        $this->options['resize']['max_width']['value'] = intval($this->options['resize']['max_width']['value']);
        if (!$this->options['resize']['max_width']['enabled']) {
            $this->options['resize']['max_width']['value'] = 0;
        } elseif ($this->options['resize']['max_width']['value'] > 0 && $this->options['resize']['max_width']['value'] < 300) {
            $this->options['resize']['max_width']['value'] = 300;
        }

        $this->options['resize']['max_height']['value'] = intval($this->options['resize']['max_height']['value']);
        if (!$this->options['resize']['max_height']['enabled']) {
            $this->options['resize']['max_height']['value'] = 0;
        } elseif ($this->options['resize']['max_height']['value'] > 0 && $this->options['resize']['max_height']['value'] < 300) {
            $this->options['resize']['max_height']['value'] = 300;
        }

        $this->options['resize']['quality']['value'] = intval($this->options['resize']['quality']['value']);
        if ($this->options['resize']['quality']['value'] > -1 && $this->options['resize']['quality']['value'] < 30) {
            $this->options['resize']['quality']['value'] = 30;
        }

        if ($this->options['resize']['quality']['value'] > 100) {
            $this->options['resize']['quality']['value'] = 100;
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
     * @param int    $progressive Interlace to make progressive loading if jpeg image
     * @param bool   $dry_run     If true, only run resize on temp file to get stats
     *                            If false, run and replace existing image
     * @param string $destination Where to save the resized image to
     *                            If omitted, will overwrite source
     * @return mixed              Sucess: array of before and after image sizes in bytes
     *                              [ original_size => <bytes>, squashed_size => <bytes> ]
     *                            Failure: set error() message and return false
     */
    public function resize($dry_run = true, $destination = null)
    {
        $this->clearError();

        if (!$this->lib->isLoaded()) {
            return $this->setError(__('The image file has not been set or could not be loaded', 'wbfy-squash-it'));
        }

        // Resize it!
        $result = $this->lib->resize(
            $this->options['resize']['max_width']['value'],
            $this->options['resize']['max_height']['value'],
            $this->options['resize']['quality']['value'],
            $this->options['format']['progressive'],
            $dry_run,
            $destination
        );
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
