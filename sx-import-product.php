<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://soxes.ch/
 * @since             1.0.0
 * @package           Sx_Import_Product
 *
 * @wordpress-plugin
 * Plugin Name:       Import Products
 * Plugin URI:        https://soxes.ch/
 * Description:       Import products from XML file places in folder xml-import
 * Version:           1.0.0
 * Author:            Truc Nguyen
 * Author URI:        https://soxes.ch/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       sx-import-product
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('SX_IMPORT_PRODUCT_VERSION', '1.0.0');
define('SX_PATH', plugin_dir_path(__FILE__));
define('SX_URL', plugin_dir_url(__FILE__));
$upload_dir = wp_get_upload_dir();
define('SX_IMPORT_PATH', trailingslashit($upload_dir['basedir']) . 'xml-import');
define('SX_IMPORT_PATH_BK', trailingslashit($upload_dir['basedir']) . 'xml-import-bk');
define('SX_IMPORT_URL', trailingslashit($upload_dir['baseurl']) . 'xml-import');
define('SX_IMPORT_URL_BK', trailingslashit($upload_dir['baseurl']) . 'xml-import-bk');
define('SX_CLIENT_URL', 'https://img.roline.ch/');
define('SX_CAT_TAG', 'CATALOG_STRUCTURE');
define('SX_PRODUCT_TAG', 'ARTICLE');
define('SX_MAP_TAG', 'ARTICLE_TO_CATALOGGROUP_MAP');

global $wpdb;
define('SX_TABLE', $wpdb->prefix . 'sx_import');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-sx-import-product-activator.php
 */
function activate_sx_import_product()
{
    // Require ACF PRO
    if (!is_plugin_active('wp-crontrol/wp-crontrol.php') && current_user_can('activate_plugins')) {
        wp_die('Sorry, but this plugin requires the WP Cron to be installed and active. <br><a href="' . admin_url('plugins.php') . '">&laquo; Return to Plugins</a>');
    }
    require_once SX_PATH . 'includes/class-sx-import-product-activator.php';
    Sx_Import_Product_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-sx-import-product-deactivator.php
 */
function deactivate_sx_import_product()
{
    require_once SX_PATH . 'includes/class-sx-import-product-deactivator.php';
    Sx_Import_Product_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_sx_import_product');
register_deactivation_hook(__FILE__, 'deactivate_sx_import_product');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require SX_PATH . 'includes/class-sx-import-product.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_sx_import_product()
{

    $plugin = new Sx_Import_Product();
    $plugin->run();

}

run_sx_import_product();
