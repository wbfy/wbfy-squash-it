<?php
/**
 * Main plugin loader
 */
class wbfy_si_Main
{
    /**
     * Initialise plugin and menus
     */
    public function __construct()
    {
        register_activation_hook(WBFY_SI_PLUGIN_DIR . '/wbfy-squash-it.php', array($this, 'activate'));
        register_deactivation_hook(WBFY_SI_PLUGIN_DIR . '/wbfy-squash-it.php', array($this, 'deactivate'));

        // Init admin page
        add_action('admin_init', array(new wbfy_si_Admin, 'init'));
        add_action('admin_menu', array(new wbfy_si_Admin, 'addToMenu'));

        // Init Squash It! Now page
        add_action('admin_init', array(new wbfy_si_SquashItNow, 'init'));
        add_action('admin_menu', array(new wbfy_si_SquashItNow, 'addToMenu'));

        // Init Squash It! On Upload
        add_action('init', array(new wbfy_si_SquashItOnUpload, 'init'));

        // Translations
        add_action('plugins_loaded', array($this, 'loadI18N'));
    }

    /**
     * Load I18N data
     */
    public function loadI18N()
    {
        load_plugin_textdomain('wbfy-squash-it', false, dirname(plugin_basename(__FILE__)) . '/resources/languages/');
    }

    /**
     * Initialise plugin and options
     */
    public function activate()
    {
        wbfy_si_Options::getInstance()->init();
    }

    /**
     * Deactivate plugin and remove options if required
     */
    public function deactivate()
    {
        $options = wbfy_si_Options::getInstance();
        if (!isset($options->settings['config_data']['on_deactivate']) || $options->settings['config_data']['on_deactivate']) {
            $options->drop();
        }
    }
}
