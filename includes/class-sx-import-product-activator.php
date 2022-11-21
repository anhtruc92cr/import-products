<?php

/**
 * Fired during plugin activation
 *
 * @link       https://soxes.ch/
 * @since      1.0.0
 *
 * @package    Sx_Import_Product
 * @subpackage Sx_Import_Product/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Sx_Import_Product
 * @subpackage Sx_Import_Product/includes
 * @author     Truc Nguyen <truc.nguyen@soxes.ch>
 */
class Sx_Import_Product_Activator
{

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate()
    {
        if (!is_dir(SX_IMPORT_PATH)) {
            mkdir(SX_IMPORT_PATH);
        }
        if (!is_dir(SX_IMPORT_PATH_BK)) {
            mkdir(SX_IMPORT_PATH_BK);
        }

        //Create table to save data
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = SX_TABLE;
        $sql = "CREATE TABLE {$table_name} (
			  id int(10) NOT NULL AUTO_INCREMENT,
			  data longtext DEFAULT '',
			  type varchar(20) DEFAULT '',
			  PRIMARY KEY  (id)
			) $charset_collate;";
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

}
