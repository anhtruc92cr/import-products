<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://soxes.ch/
 * @since      1.0.0
 *
 * @package    Sx_Import_Product
 * @subpackage Sx_Import_Product/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Sx_Import_Product
 * @subpackage Sx_Import_Product/includes
 * @author     Truc Nguyen <truc.nguyen@soxes.ch>
 */
class Sx_Import_Product
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Sx_Import_Product_Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_name The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        if (defined('SX_IMPORT_PRODUCT_VERSION')) {
            $this->version = SX_IMPORT_PRODUCT_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'sx-import-product';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();

    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Sx_Import_Product_Loader. Orchestrates the hooks of the plugin.
     * - Sx_Import_Product_i18n. Defines internationalization functionality.
     * - Sx_Import_Product_Admin. Defines all hooks for the admin area.
     * - Sx_Import_Product_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-sx-import-product-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-sx-import-product-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-sx-import-product-admin.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-sx-media-import.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-sx-write-log.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-sx-send-email.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-sx-import-product-public.php';

        $this->loader = new Sx_Import_Product_Loader();

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Sx_Import_Product_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {

        $plugin_i18n = new Sx_Import_Product_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {
        $plugin_admin = Sx_Import_Product_Admin::get_instance($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('wp_ajax_import_products', $plugin_admin, 'ajax_import_products');
        $this->loader->add_action('sx_import_product_daily', $plugin_admin, 'import_all');
        $this->loader->add_action('sx_import_data_to_table', $plugin_admin, 'import_data_to_table');

        $current_time = time();
        if (!wp_next_scheduled('sx_import_data_to_table')) {
            wp_schedule_event($current_time, 'daily', 'sx_import_data_to_table');
        }
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {

        $plugin_public = Sx_Import_Product_Public::get_instance($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('sx_clean_up_logs', $plugin_public, 'clean_up_logs');
        $this->loader->add_action('sx_after_importing', $plugin_public, 'move_xml');

        if (!wp_next_scheduled('sx_clean_up_logs')) {
            wp_schedule_event(time(), 'daily', 'sx_clean_up_logs');
        }
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return    string    The name of the plugin.
     * @since     1.0.0
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return    Sx_Import_Product_Loader    Orchestrates the hooks of the plugin.
     * @since     1.0.0
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return    string    The version number of the plugin.
     * @since     1.0.0
     */
    public function get_version()
    {
        return $this->version;
    }

}
