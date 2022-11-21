<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://soxes.ch/
 * @since      1.0.0
 *
 * @package    Sx_Import_Product
 * @subpackage Sx_Import_Product/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Sx_Import_Product
 * @subpackage Sx_Import_Product/includes
 * @author     Truc Nguyen <truc.nguyen@soxes.ch>
 */
class Sx_Import_Product_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'sx-import-product',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
