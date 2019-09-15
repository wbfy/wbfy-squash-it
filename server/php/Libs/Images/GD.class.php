<?php
/**
 * Image manipulation PHP-GD wrapper
 */
class wbfy_si_Libs_Images_GD
{
    private $image_info   = [];
    private $source       = [];
    private $file_size    = 0;
    private $image_loaded = false;
    private $width        = 0;
    private $height       = 0;
    private $exif         = false;

    /**
     * Check if image file information has been successfully loaded
     *
     * @return boolean True if loaded
     */
    public function isLoaded()
    {
        return $this->image_loaded;
    }

    /**
     * Set the image source file and load the fileinformation about it
     *
     * @param string $source The image filename with path
     * @return mixed True if successfully loaded, error string if not
     */
    public function setSource($source)
    {
        $this->image_loaded = false;
        $this->source       = $source;
        $this->image_info   = getimagesize($source);
        if ($this->image_info === false) {
            return (string) __('The image file could not be opened', 'wbfy-squash-it');
        }
        $this->width  = $this->image_info[0];
        $this->height = $this->image_info[1];

        // Try to stop exif_read_data logging annoying warnings
        // May not work depending on ISP settings
        $error_level = error_reporting(E_ERROR);
        try {
            $this->exif = exif_read_data($source);
        } catch (Exception $e) {
            $this->exif = false;
        }
        // Reset error level to previous level
        $error_level = error_reporting($error_level);

        $this->file_size = filesize($source);
        if ($this->file_size === false) {
            return (string) __('The image file could not be statted', 'wbfy-squash-it');
        }

        $this->image_loaded = true;
        return true;
    }

    /**
     * @inheritDoc wbfy_si_Libs_Images
     */
    public function resize($max_width, $max_height, $quality, $dry_run, $destination)
    {
        // Overwrite source if no destination supplied
        if (is_null($destination)) {
            $destination = $this->source;
        }

        $old_image = $this->loadImage();
        if (is_string($old_image)) {
            return $old_image;
        }
        $old_image = $this->fixRotation($old_image);

        $fixed_width  = imagesx($old_image);
        $fixed_height = imagesy($old_image);

        // Size after rotation so max_width is correct
        // after orientation is corrected
        $new_width  = $fixed_width;
        $new_height = $fixed_height;

        // Resize to max_width if its larger
        if ($max_width > 0 && $new_width > $max_width) {
            $new_width  = $max_width;
            $new_height = floor(($max_width / $fixed_width) * $fixed_height);
        }

        // If still taller than max_height then further resize to max height
        if ($max_height > 0 && $new_height > $max_height) {
            $new_width  = floor(($max_height / $this->height) * $this->width);
            $new_height = $max_height;
        }

        // Should possibly also add transparent pad to max_width / max height?

        $new_image = imagecreatetruecolor($new_width, $new_height);
        // Preserve transparency on PNG images
        if ($this->getMimeType() == 'image/png') {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
        }
        imagecopyresampled($new_image, $old_image, 0, 0, 0, 0, $new_width, $new_height, $fixed_width, $fixed_height);

        return $this->saveImage($destination, $new_image, $quality, $dry_run);
    }

    /**
     * Fix image rotation if exif Orientation information is available
     * This is necessary because the exif data is dropped from the new
     * image created during the resize operation
     *
     * @param object $image GD image stream to be fixed
     * @return object Fixed image strean
     */
    private function fixRotation($image)
    {
        $orientation = $this->getOrientation();
        switch ($orientation) {
            case 3:
                return imagerotate($image, 180, 0);
            case 6:
                return imagerotate($image, -90, 0);
            case 8:
                return imagerotate($image, 90, 0);
        }
        return $image;
    }

    /**
     * @inheritDoc wbfy_si_Libs_Images
     */
    public function getExifSize()
    {
        $width       = $this->width;
        $height      = $this->height;
        $orientation = $this->getOrientation();
        switch ($orientation) {
            case 6:
            case 8:
                $height = $this->width;
                $width  = $this->height;
                break;
        }
        return array(
            'bytes'  => $this->file_size,
            'width'  => $width,
            'height' => $height,
        );
    }

    /**
     * @inheritDoc wbfy_si_Libs_Images
     */
    public function getSize()
    {
        return array(
            'bytes'  => $this->file_size,
            'width'  => $this->width,
            'height' => $this->height,
        );
    }

    /**
     * Load image from file based on mime type
     *
     * @return mixed Image stream object containing the image, error string on failure
     */
    private function loadImage()
    {
        $mime_type = $this->getMimeType();

        if ($mime_type == 'image/jpeg') {
            try {
                $image = imagecreatefromjpeg($this->source);
            } catch (Exception $e) {
                $image = false;
            }
        } elseif ($mime_type == 'image/gif') {
            try {
                $image = imagecreatefromgif($this->source);
            } catch (Exception $e) {
                $image = false;
            }
        } elseif ($mime_type == 'image/png') {
            try {
                $image = imagecreatefrompng($this->source);
            } catch (Exception $e) {
                $image = false;
            }
        } elseif ($mime_type == 'image/bmp') {
            try {
                $image = imagebmp($this->source, $destination, false);
            } catch (Exception $e) {
                $image = false;
            }
        } else {
            return (string) sprintf(__('Image type %s is not supported', 'wbfy-squash-it'), $mime_type);
        }

        if ($image === false) {
            return (string) sprintf(__('The %s image could not be imported', 'wbfy-squash-it'), $mime_type);
        }

        return $image;
    }

    /**
     * Save image stream to file
     *
     * @param string $destination Destination filename including any path
     * @param object $image Image stream object
     * @param int    $quality Image quality 1 - 100% to be used for saving, use -1 for GD default
     * @return mixed Array of results on save, error string on fail
     */
    private function saveImage($destination, $image, $quality = -1, $dry_run = true)
    {
        $mime_type     = $this->getMimeType();
        $squashed_size = $this->file_size;
        $result        = true;

        if (is_file($destination)) {
            if (!is_writeable($destination)) {
                return __('Existing image file cannot be overwritten', 'wbfy-squash-it');
            }
        }

        if ($mime_type == 'image/jpeg') {
            try {
                if (!$dry_run) {
                    $result = imagejpeg($image, $destination, $quality);
                }
                // Get size of squashed image
                // filesize doesn't always update with new details
                ob_start();
                imagejpeg($image, null, $quality);
                $squashed_size = strlen(ob_get_contents());
                ob_end_clean();
            } catch (Exception $e) {
                $result = false;
            }
        } elseif ($mime_type == 'image/gif') {
            try {
                if (!$dry_run) {
                    $result = imagegif($image, $destination);
                }
                // Get size of squashed image
                // filesize doesn't always update with new details
                ob_start();
                imagegif($image);
                $squashed_size = strlen(ob_get_contents());
                ob_end_clean();
            } catch (Exception $e) {
                $result = false;
            }
        } elseif ($mime_type == 'image/png') {
            if ($quality != -1) {
                $quality = (int) abs(10 - floor(($quality / 10)));
            }
            try {
                if (!$dry_run) {
                    $result = imagepng($image, $destination, $quality);
                }
                // Get size of squashed image
                // filesize doesn't always update with new details
                ob_start();
                imagepng($image, null, $quality);
                $squashed_size = strlen(ob_get_contents());
                ob_end_clean();
            } catch (Exception $e) {
                $result = false;
            }
        } elseif ($mime_type == 'image/bmp') {
            try {
                if (!$dry_run) {
                    $result = imagebmp($image, $destination, false);
                }
                // Get size of squashed image
                // filesize doesn't always update with new details
                ob_start();
                imagebmp($image, null, $quality);
                $squashed_size = strlen(ob_get_contents());
                ob_end_clean();
            } catch (Exception $e) {
                $result = false;
            }
        } else {
            return (string) sprintf(__('Image type %s is not supported', 'wbfy-squash-it'), $mime_type);
        }

        if ($result === false) {
            return (string) sprintf(__('The resized %s image could not be saved', 'wbfy-squash-it'), $mime_type);
        }

        return array(
            'original' => $this->file_size,
            'squashed' => $squashed_size,
            'width'    => imagesx($image),
            'height'   => imagesy($image),
        );
    }

    /**
     * Get mime type of current file
     * Will try and use file extension if it can't be determined from exif data
     * If all fails, returns mime type of 'image/unknown'
     *
     * @return string Mime type
     */
    private function getMimeType()
    {
        $mime_type = '';
        if (is_array($this->exif) && $this->exif['mime']) {
            $mime_type = $this->exif['mime'];
        } else {
            $extension = $this->getExtension();
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $mime_type = 'image/jpeg';
                    break;
                case 'bmp':
                case 'gif':
                case 'png':
                    $mime_type = 'image/' . $extension;
                    break;
                default:
                    $mime_type = 'image/unknown';
                    break;
            }
        }
        return $mime_type;
    }

    /**
     * Get file extension from current file if it matches one of the valid image types
     * Empty string if not
     *
     * @return string Extension or empty string
     */
    private function getExtension()
    {
        if (preg_match('/.*\.(jpeg|jpg|png|bmp)$/i', $this->source, $extension)) {
            return strtolower($extension[1]);
        }
        return '';
    }

    /**
     * Gets Orientation from current image mime data
     *
     * @return int Orientation or zero if not available
     */
    private function getOrientation()
    {
        $orientation = 0;
        if (is_array($this->exif) && isset($this->exif['Orientation'])) {
            $orientation = $this->exif['Orientation'];
        }
        return $orientation;
    }
}
