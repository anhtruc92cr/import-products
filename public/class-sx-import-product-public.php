<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://soxes.ch/
 * @since      1.0.0
 *
 * @package    Sx_Import_Product
 * @subpackage Sx_Import_Product/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Sx_Import_Product
 * @subpackage Sx_Import_Product/public
 * @author     Truc Nguyen <truc.nguyen@soxes.ch>
 */
class Sx_Import_Product_Public
{

    /**
     * The instance
     *
     * @since    1.0.0
     * @access   private
     * @var      Sx_Import_Product_Admin $instance Singleton class
     */
    private static $instance = null;

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of the plugin.
     * @param string $version The version of this plugin.
     * @since    1.0.0
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
        add_filter('cron_schedules', array($this, 'sx_cron_schedules'));
        add_action('import_every_5min', array($this, 'import_all'));
        if (!wp_next_scheduled('import_every_5min')) {
            wp_schedule_event(time(), '5min', 'import_every_5min');
        }

    }

    /**
     * Only if the class has no instance
     *
     * @since    1.0.0
     */
    public static function get_instance($plugin_name, $version)
    {
        if (null === self::$instance) {
            self::$instance = new Sx_Import_Product_Public($plugin_name, $version);
        }

        return self::$instance;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Sx_Import_Product_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Sx_Import_Product_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/sx-import-product-public.css', array(), $this->version, 'all');

    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Sx_Import_Product_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Sx_Import_Product_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/sx-import-product-public.js', array('jquery'), $this->version, false);

    }

    /**
     * Cleanup logs.
     */
    public function clean_up_logs()
    {
        $files = glob(SX_IMPORT_PATH_BK . "/logs/*");
        $now = time();
        foreach ($files as $file) {
            if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) == 'log') {
                // Cleanup logs >= 30 days
                if ($now - filectime($file) >= 60 * 60 * 24 * 30) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * move XML file to backup .
     */
    public function move_xml()
    {
        $files = array_diff(scandir(SX_IMPORT_PATH), array('.', '..'));
        //Only read the first file in xml-import folder
        $file = reset($files);
        $array = explode('.', $file);
        $new_name = $array[0] . '-' . date("d-m-Y") . $array[1];
        $oldplace = SX_IMPORT_PATH . "/{$file}";
        $newplace = SX_IMPORT_PATH_BK . "/{$new_name}";
        if (rename($oldplace, $newplace)) {
            return $newplace;
        }
        return null;
    }

    function sx_cron_schedules($schedules)
    {
        if (!isset($schedules["5min"])) {
            $schedules["5min"] = array(
                'interval' => 5 * 60,
                'display' => __('Once every 5 minutes'));
        }
        return $schedules;
    }

    function import_all() {
        do_action('sx_import_product_daily');
    }
}
