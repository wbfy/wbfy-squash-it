<?php
class wbfy_si_Admin
{
    /**
     * Initialise Squash It! options page
     * Set up actions and filters for Admin functions
     */
    public function init()
    {
        register_setting(
            'wbfy_si_options', // Option group.
            'wbfy_si', // Option name (in wp_options)
            array($this, 'validate') // Sanitation callback
        );
        add_filter('plugin_action_links_wbfy-squash-it/wbfy-squash-it.php', array($this, 'pluginPageSettingsLink'));
        $this->registerForm();
    }

    /**
     * Add 'settings' option onto WP Plugins page
     *
     * @param array  $links links for WP Plugin page
     * @return array $links With new settings link added
     */
    public function pluginPageSettingsLink($links)
    {
        $url = esc_url(
            add_query_arg(
                'page',
                'wbfy-squash-it',
                get_admin_url() . 'admin.php'
            )
        );
        $settings_link = "<a href='$url'>" . __('Settings') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Add Squash It! to settings menu
     */
    public function addToMenu()
    {
        add_options_page(
            __('Squash It!', 'wbfy-squash-it'), // Page title
            __('Squash It!', 'wbfy-squash-it'), // Menu title
            'manage_options', // Capability/permission required
            'wbfy-squash-it', // Page slug (unique id)
            array($this, 'render') // Renderer callback
        );
    }

    public function registerForm()
    {
        // Image resize settings section
        add_settings_section(
            'wbfy_si_resize', // ID
            __('Resize Parameters', 'wbfy-squash-it'), // Section name
            array($this, 'sectionImageResize'), // Title HTML callback
            'wbfy_si_options' // register_setting::option group NOT page slug!
        );

        // Resize settings fields
        add_settings_field(
            'wbfy_si_resize_max_width_value', // ID
            __('Maximum Width (px)', 'wbfy-squash-it'), // Label
            array($this, 'fieldResizeMaxWidth'), // Field HTML callback
            'wbfy_si_options', // register_setting::option group NOT page slug!
            'wbfy_si_resize' // Section ID
        );

        add_settings_field(
            'wbfy_si_resize_max_height_value',
            __('Maximum Height (px)', 'wbfy-squash-it'),
            array($this, 'fieldResizeMaxHeight'),
            'wbfy_si_options',
            'wbfy_si_resize'
        );

        add_settings_field(
            'wbfy_si_resize_quality_value',
            __('Quality (%)', 'wbfy-squash-it'),
            array($this, 'fieldResizeQuality'),
            'wbfy_si_options',
            'wbfy_si_resize'
        );

        // Config automatic resize
        add_settings_section(
            'wbfy_si_auto',
            __('Automatic Resizing', 'wbfy-squash-it'),
            array($this, 'sectionAuto'),
            'wbfy_si_options'
        );

        add_settings_field(
            'wbfy_si_auto_on_upload',
            __('On upload', 'wbfy-squash-it'),
            array($this, 'fieldAutoOnUpload'),
            'wbfy_si_options',
            'wbfy_si_auto'
        );

        // Config data settings fields
        add_settings_section(
            'wbfy_si_config_data',
            __('Configuration Data', 'wbfy-squash-it'),
            array($this, 'sectionConfigData'),
            'wbfy_si_options'
        );

        add_settings_field(
            'wbfy_si_config_data_on_deactivate',
            __('Deactivated', 'wbfy-squash-it'),
            array($this, 'fieldConfigDataOnDeactivate'),
            'wbfy_si_options',
            'wbfy_si_config_data'
        );

        add_settings_field(
            'wbfy_si_config_data_on_delete',
            __('Deleted', 'wbfy-squash-it'),
            array($this, 'fieldConfigDataOnDelete'),
            'wbfy_si_options',
            'wbfy_si_config_data'
        );
    }

    /**
     * Render Resize options section header
     */
    public function sectionImageResize()
    {
        echo '<p>' . __('The parameters below will apply to both batch and automatic image resizing:', 'wbfy-squash-it') . '</p>';
    }

    /**
     * Render max_width field
     */
    public function fieldResizeMaxWidth()
    {
        $options = wbfy_si_Options::getInstance();

        echo '<div>';

        echo wbfy_si_Libs_Html_Inputs::inputCheck(
            array(
                'id'    => 'wbfy_si_resize_max_width_enabled',
                'name'  => 'wbfy_si[resize][max_width][enabled]',
                'value' => $options->settings['resize']['max_width']['enabled'],
                'label' => __('Enabled', 'wbfy-squash-it'),
            )
        );

        echo '</div><div>';

        echo wbfy_si_Libs_Html_Inputs::inputText(
            array(
                'id'        => 'wbfy_si_resize_max_width_value',
                'name'      => 'wbfy_si[resize][max_width][value]',
                'value'     => $options->settings['resize']['max_width']['value'],
                'maxlength' => 5,
            )
        );

        echo '<div><div>' . __('Minimum value: 300', 'wbfy-squash-it');
    }

    /**
     * Render resize max_height field
     */
    public function fieldResizeMaxHeight()
    {
        $options = wbfy_si_Options::getInstance();

        echo '<div>';

        echo wbfy_si_Libs_Html_Inputs::inputCheck(
            array(
                'id'    => 'wbfy_si_resize_max_height_enabled',
                'name'  => 'wbfy_si[resize][max_height][enabled]',
                'value' => $options->settings['resize']['max_height']['enabled'],
                'label' => __('Enabled', 'wbfy-squash-it'),
            )
        );

        echo '</div><div>';

        echo wbfy_si_Libs_Html_Inputs::inputText(
            array(
                'id'        => 'wbfy_si_resize_max_height_value',
                'name'      => 'wbfy_si[resize][max_height][value]',
                'value'     => $options->settings['resize']['max_height']['value'],
                'maxlength' => 5,
            )
        );

        echo '<div><div>Minimum value: 300';
    }

    /**
     * Render quality field
     */
    public function fieldResizeQuality()
    {
        $options = wbfy_si_Options::getInstance();

        echo '<div>';

        echo wbfy_si_Libs_Html_Inputs::inputCheck(
            array(
                'id'    => 'wbfy_si_resize_quality_enabled',
                'name'  => 'wbfy_si[resize][quality][enabled]',
                'value' => $options->settings['resize']['quality']['enabled'],
                'label' => __('Enabled', 'wbfy-squash-it'),
            )
        );

        echo '</div><div>';

        echo wbfy_si_Libs_Html_Inputs::selectRange(
            30, 100,
            array(
                'id'    => 'wbfy_si_resize_quality_value',
                'name'  => 'wbfy_si[resize][quality][value]',
                'value' => $options->settings['resize']['quality']['value'],
            )
        );

        echo '<div>';
    }

    /**
     * Render Auto options section header
     */
    public function sectionAuto()
    {
        echo '<p>' . __('Automatically resize images:', 'wbfy-squash-it') . '</p>';
    }

    /**
     * Render on_delete/uninstall field
     */
    public function fieldAutoOnUpload()
    {
        $options = wbfy_si_Options::getInstance();
        echo wbfy_si_Libs_Html_Inputs::inputCheck(
            array(
                'id'    => 'wbfy_si_auto_on_upload',
                'name'  => 'wbfy_si[auto][on_upload]',
                'value' => $options->settings['auto']['on_upload'],
            )
        );
    }

    /**
     * Render Config Data options header
     */
    public function sectionConfigData()
    {
        echo '<p>' . __('Remove all configuration data for this plugin when it is:', 'wbfy-squash-it') . '</p>';
    }

    /**
     * Render on_deactivate field
     */
    public function fieldConfigDataOnDeactivate()
    {
        $options = wbfy_si_Options::getInstance();
        echo wbfy_si_Libs_Html_Inputs::inputCheck(
            array(
                'id'    => 'wbfy_si_config_data_on_deactivate',
                'name'  => 'wbfy_si[config_data][on_deactivate]',
                'value' => $options->settings['config_data']['on_deactivate'],
            )
        );
    }

    /**
     * Render on_delete/uninstall field
     */
    public function fieldConfigDataOnDelete()
    {
        $options = wbfy_si_Options::getInstance();
        echo wbfy_si_Libs_Html_Inputs::inputCheck(
            array(
                'id'    => 'wbfy_si_config_data_on_delete',
                'name'  => 'wbfy_si[config_data][on_delete]',
                'value' => $options->settings['config_data']['on_delete'],
            )
        );
    }

    /**
     * Validate and sanitize inputs
     */
    public function validate($input)
    {
        $input['config_data']['on_delete']        = (isset($input['config_data']['on_delete'])) ? true : false;
        $input['config_data']['on_deactivate']    = (isset($input['config_data']['on_deactivate'])) ? true : false;
        $input['resize']['max_width']['enabled']  = (isset($input['resize']['max_width']['enabled'])) ? true : false;
        $input['resize']['max_height']['enabled'] = (isset($input['resize']['max_height']['enabled'])) ? true : false;
        $input['resize']['quality']['enabled']    = (isset($input['resize']['quality']['enabled'])) ? true : false;
        $input['auto']['on_upload']               = (isset($input['auto']['on_upload'])) ? true : false;

        $input['resize']['quality']['value'] = intval(sanitize_text_field($input['resize']['quality']['value']));
        if ($input['resize']['quality']['value'] < 10) {
            $input['resize']['quality']['value'] = 10;
        }
        if ($input['resize']['quality']['value'] > 100) {
            $input['resize']['quality']['value'] = 100;
        }

        $input['resize']['max_height']['value'] = intval(sanitize_text_field($input['resize']['max_height']['value']));
        if ($input['resize']['max_height']['value'] < 300) {
            $input['resize']['max_height']['value'] = 300;
        }

        $input['resize']['max_width']['value'] = intval(sanitize_text_field($input['resize']['max_width']['value']));
        if ($input['resize']['max_width']['value'] < 300) {
            $input['resize']['max_width']['value'] = 300;
        }

        return $input;
    }

    /**
     * Render Squash It! options page
     */
    public function render()
    {
        if (!current_user_can('edit_files')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wbfy-squash-it'));
        }

        wp_enqueue_style(
            'wbfy-squash-it-css',
            plugins_url('/wbfy-squash-it/resources/css/wbfy-squash-it.min.css'),
            false,
            WBFY_SI_VERSION
        );

        echo wbfy_si_Libs_WordPress_Functions::render(
            'server/skin/admin.php',
            wbfy_si_Options::getInstance()->settings
        );
    }
}
