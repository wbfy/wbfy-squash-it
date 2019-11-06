<?php
/**
 * Squash It! Image Resizer
 *
 * Set up autoload and instantiate the main Squash It! class and constants
 *
 * @link    https://websitesbuiltforyou.com/wordpress/squash-it-image-resizer
 * @package Squash It! Image Resizer
 */

/**
 * Plugin Name: Squash It!
 * Plugin URI: https://websitesbuiltforyou.com/wordpress/squash-it-image-resizer
 * Description: Resize and optimise images already uploaded to WordPress
 * Author: Websites Built For You
 * Author URI: https://websitesbuiltforyou.com
 * Version: 1.2.0
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses
 *
 * Copyright (c) 2019 Steve Perkins, Websites Built For You
 *
 * Squash It! is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free Software
 * Foundation, either version 2 of the License, or any later version.
 *
 * Squash It! is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * Squash It! If not, see https://www.gnu.org/licenses.
 */

if (!defined('ABSPATH')) {
    exit;
}

include 'server/src/Libs/Polyfills.php';

if (!class_exists('wbfy_si_Main')) {
    define('WBFY_SI_VERSION', '1.2.0');
    define('WBFY_SI_PLUGIN_DIR', plugin_dir_path(__FILE__));

    include 'server/src/Autoloader.class.php';
    wbfy_si_Autoloader::register();

    $wbfy_si = new wbfy_si_Main;
}
