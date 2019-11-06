<?php
/**
 * Squash It! Admin options handler
 */
class wbfy_si_Options
{
    public $settings = null;

    /**
     * Singleton
     *
     * @return object Singleton instance
     */
    public function getInstance()
    {
        static $instance = null;
        if (is_null($instance)) {
            $me       = __CLASS__;
            $instance = new $me;
            return $instance;
        }
        return $instance;
    }

    /**
     * Load options from DB
     * Extend existing options to also include any new top level
     * options that may have been added during any plugin update
     */
    public function __construct()
    {
        $this->getDefaults();
        $settings = get_option('wbfy_si');
        if (is_array($settings)) {
            $this->settings = wbfy_si_Libs_Arrays::extend(
                $this->settings,
                $settings
            );
        }
    }

    /**
     * Delete options
     */
    public function drop()
    {
        delete_option('wbfy_si');
    }

    /**
     * Called on plugin initialisation
     */
    public function init()
    {
        $this->getDefaults();
        add_option('wbfy_si', $this->settings);
    }

    /**
     * Default options
     *
     * @return array Default options list
     */
    public function getDefaults()
    {
        $this->settings = array(
            'image'       => array(
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
            ),
            'auto'        => array(
                'on_upload' => false,
            ),
            'config_data' => array(
                'on_deactivate' => 0,
                'on_delete'     => 1,
            ),
        );
    }
}
