<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://soxes.ch/
 * @since      1.0.0
 *
 * @package    Sx_Import_Product
 * @subpackage Sx_Import_Product/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Sx_Import_Product
 * @subpackage Sx_Import_Product/includes
 * @author     Truc Nguyen <truc.nguyen@soxes.ch>
 */
class Sx_Import_Product_Deactivator
{

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate()
    {
        $timestamp = wp_next_scheduled('sx_clean_up_logs');
        wp_unschedule_event($timestamp, 'sx_clean_up_logs');
        $timestamp = wp_next_scheduled('sx_import_product_daily');
        wp_unschedule_event($timestamp, 'sx_import_product_daily');
        global $wpdb;
        $table_name = SX_TABLE;
        $sql = "DROP TABLE IF EXISTS $table_name";
        $wpdb->query($sql);
        delete_option("sx-import-number");
        delete_option("sx-import-emails");
    }

}
