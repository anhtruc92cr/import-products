<?php
require __DIR__ . '/../vendor/autoload.php';

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://soxes.ch/
 * @since      1.0.0
 *
 * @package    Sx_Import_Product
 * @subpackage Sx_Import_Product/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Sx_Import_Product
 * @subpackage Sx_Import_Product/admin
 * @author     Truc Nguyen <truc.nguyen@soxes.ch>
 */
class Sx_Import_Product_Admin
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
     * The import process error or not
     *
     * @since    1.0.0
     * @access   private
     * @var      boolean $is_error Import get error or not
     */
    private $is_error = false;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version The version of this plugin.
     * @since    1.0.0
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
        add_action('admin_menu', array($this, 'sx_menu_pages'));
    }

    /**
     * Only if the class has no instance
     *
     * @since    1.0.0
     */
    public static function get_instance($plugin_name, $version)
    {
        if (null === self::$instance) {
            self::$instance = new Sx_Import_Product_Admin($plugin_name, $version);
        }

        return self::$instance;
    }

    /**
     * Register the stylesheets for the admin area.
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

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/sx-import-product-admin.css', array(), $this->version, 'all');

    }

    /**
     * Register the JavaScript for the admin area.
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

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/sx-import-product-admin.js', array('jquery'), $this->version, false);

    }

    /**
     * Register menu pages for the admin area.
     *
     * @since    1.0.0
     */
    function sx_menu_pages()
    {
        add_menu_page('Import Products', 'Import Products', 'manage_options', 'sx-import-products', array($this, 'sx_menu_callback'));
    }

    /**
     * Admin content page
     *
     * @since    1.0.0
     */
    function sx_menu_callback()
    {
        $cron_url = $this->get_cron_url_running();
        require_once(SX_PATH . 'admin/partials/sx-import-product-admin-display.php');
    }

    protected function get_cron_url_running(): string
    {
        $crons = get_option('cron');
        $return = '';
        try {
            foreach ($crons as $time => $cron) {
                foreach ($cron as $hook => $dings) {
                    if ($hook == 'import_every_5min' || $hook == 'sx_import_data_to_table') {
                        $name = ($hook == 'sx_import_data_to_table') ? 'Import XML to DB' : 'Import Product';
                        foreach ($dings as $sig => $data) {
                            $link = array(
                                'page' => 'crontrol_admin_manage_page',
                                'crontrol_action' => 'run-cron',
                                'crontrol_id' => rawurlencode($hook),
                                'crontrol_sig' => rawurlencode($sig),
                                'crontrol_next_run_utc' => rawurlencode($time),
                            );
                            $link = add_query_arg($link, admin_url('tools.php'));
                            $link = wp_nonce_url($link, "crontrol-run-cron_{$hook}_{$sig}");

                            $return .= "<a target=\"_blank\" href='" . esc_url($link) . "'>" . esc_html__('Run ' . $name . ' Now', 'wp-crontrol') . '</a>&nbsp;&nbsp;&nbsp;';
                        }
                    }
                }
            }
            return $return;
        } catch (Exception $e) {
            Sx_Write_Log::write_log('Error: Cannot start import all: ' . $e->getMessage());
        }
    }

    /**
     * Cronjob to run import XML data to database
     *
     * @since    1.0.0
     */
    function import_data_to_table()
    {
        try {
            $reader = $this->read_file();
            $catalog = new XMLElementIterator($reader, SX_CAT_TAG);
            $this->import_to_custom_table($catalog, 'category');
            $reader->close();
            $reader = $this->read_file();
            $products = new XMLElementIterator($reader, SX_PRODUCT_TAG);
            $this->import_to_custom_table($products, 'product');
            $reader->close();
            $reader = $this->read_file();
            $maps = new XMLElementIterator($reader, SX_MAP_TAG);
            $this->import_to_custom_table($maps, 'map');
            $reader->close();
            Sx_Write_Log::write_log('Successful: Finish import XML to database!');
        } catch (Exception $e) {
            Sx_Write_Log::write_log('Error: Cannot start import data to table: ' . $e->getMessage());
        }
    }

    function import_to_custom_table($catalog, $type)
    {
        global $wpdb;
        foreach ($catalog as $key => $category) {
            $cat = $category->getSimpleXMLElement();
            $prepare_sql = json_encode($cat);
            $wpdb->insert(
                SX_TABLE,
                array('data' => $prepare_sql, 'type' => $type),
                array('%s', '%s'),
            );
        }
    }

    /**
     * import categories, products and map category to product
     *
     * @since    1.0.0
     */
    function import_all()
    {
        try {
            $data = $this->read_db();
            foreach ($data as $d) {
                $row_id = $d->id;
                switch ($d->type) {
                    case 'category':
                        $this->import_categories($d->data, $row_id);
                        break;
                    case 'product':
                        $this->import_products($d->data, $row_id);
                        break;
                    case 'map':
                        $this->map_category_to_product($d->data, $row_id);
                        break;
                    default:
                        break;
                }
            }
            //Save error to DB if there is any error per import
            if ($this->is_error) {
                $this->save_to_db('sx-import-error', 1);
            }
            //Send error email when finish
            if ($this->get_from_db('sx-import-error') == 1 && $this->check_table_null()) {
                Sx_Send_Email::send_email();
                $this->save_to_db('sx-import-error', '0');
            }
            //Send finish email
            if ($this->check_table_null()) {
                Sx_Send_Email::send_email('successful');
            }
        } catch (Exception $e) {
            Sx_Write_Log::write_log('Error: Cannot start import all: ' . $e->getMessage());
        }
    }

    protected function save_to_db($key, $error)
    {
        update_option($key, $error);
    }

    protected function get_from_db($key)
    {
        return get_option($key);
    }

    /**
     * Check data in custom table exist or not
     *
     * @return boolean
     * @since    1.0.0
     */
    function check_table_null(): bool
    {
        global $wpdb;
        $table = SX_TABLE;
        $result = $wpdb->get_results("SELECT id from $table WHERE id IS NOT NULL");
        if (count($result) == 0) {
            return false;
        }
        return true;
    }

    /**
     * Read XML Object
     *
     * @since    1.0.0
     */
    function read_file(): bool|XMLReader
    {
        try {
            $path = SX_IMPORT_PATH;
            $files = array_diff(scandir($path), array('.', '..'));
            //Only read the first file in xml-import folder
            $file = reset($files);
            if (!empty($file)) {
                $reader = new XMLReader();
                $reader->open($path . '/' . $file);
                return $reader;
            } else {
                Sx_Write_Log::write_log('Warning: Empty XML file in folder ' . $path . '/' . $file);
            }
        } catch (Exception $e) {
            Sx_Write_Log::write_log('Error: Cannot read XML file ' . $path . '/' . $file . ': ' . $e->getMessage());
            $this->is_error = true;
        }
        return false;
    }

    /**
     * Read Database data
     *
     * @since    1.0.0
     */
    function read_db(): array|object|bool|null
    {
        global $wpdb;
        try {
            $table = SX_TABLE;
            $number = get_option('sx-import-number') ?: 200;
            return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table LIMIT 0,%d", $number));
        } catch (Exception $e) {
            Sx_Write_Log::write_log('Error: Cannot read data from database' . $e->getMessage());
            $this->is_error = true;
        }
        return false;
    }

    /**
     * Import categories from XML file
     *
     * $categories array product categories get from XML file
     * @since    1.0.0
     */
    protected function import_categories($category, $row_id)
    {
        global $wpdb;
        if (!empty($category)) {
            $cat = json_decode($category);
            $type = $cat->{'@attributes'}?->type;
            $gr_id = (string)$cat->GROUP_ID;
            $parent_id = (string)$cat->PARENT_ID;
            $name = (string)$cat->GROUP_NAME;
            $number_order = (string)$cat->GROUP_ORDER;
            $img = $cat?->MIME_INFO?->MIME?->MIME_SOURCE;
            $thumb_id = null;
            if ($img) {
                $media_class = Sx_Import_Media::get_instance();
                $thumb_id = $media_class->download_img($gr_id, SX_CLIENT_URL . $img, (string)$img);
            }
            try {
                if (in_array($type, array('node', 'leaf')) && (!empty($gr_id) && $gr_id != 0)) {
                    $current_cat = $this->get_category($gr_id);
                    $parent_cat = $this->get_category($parent_id);
                    if ($current_cat != 0) {
                        wp_update_term(
                            $current_cat,
                            'product_cat',
                            array(
                                'parent' => $parent_cat,
                                'name' => $name
                            )
                        );
                        update_term_meta($current_cat, 'order', absint($number_order));
                        update_term_meta($current_cat, 'group_id', absint($gr_id));
                        update_term_meta($current_cat, 'thumbnail_id', absint($thumb_id));
                        Sx_Write_Log::write_log('Successful: Category ' . $gr_id . ' was updated');
                    } else {

                        $term = wp_insert_term(
                            $name,
                            'product_cat',
                            array(
                                'parent' => $parent_cat
                            )
                        );
                        if (!is_wp_error($term)) {
                            $cat_id = $term['term_id'] ?? 0;
                            update_term_meta($cat_id, 'thumbnail_id', absint($thumb_id));
                            update_term_meta($cat_id, 'order', absint($number_order));
                            update_term_meta($cat_id, 'group_id', absint($gr_id));
                            Sx_Write_Log::write_log('Successful: Category ' . $gr_id . ' was imported');
                        } else {
                            Sx_Write_Log::write_log('Error: issue when import category ' . $term->get_error_message());
                            $this->is_error = true;
                        }
                    }
                }
            } catch (Exception $e) {
                Sx_Write_Log::write_log('Error: import category with group ID ' . $gr_id . ': ' . $e->getMessage());
                $this->is_error = true;
            }
            $wpdb->delete(SX_TABLE, array('id' => $row_id));
        }
    }

    /**
     * get category information
     *
     * $group_id int category meta
     * @since    1.0.0
     */
    protected function get_category($group_id): int
    {
        $args = array(
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => 'group_id',
                    'value' => $group_id,
                    'compare' => '='
                )
            ),
            'taxonomy' => 'product_cat',
        );
        $terms = get_terms($args);

        return !(empty($terms[0]->term_id)) ? $terms[0]->term_id : 0;
    }

    /**
     * Return product ID based on SKU.
     *
     * @param string $sku Product SKU.
     * @return int
     * @since 3.0.0
     */
    protected function get_product_by_sku($sku): int
    {
        global $wpdb;

        $product_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku));

        if ($product_id) {
            return $product_id;
        }

        return 0;
    }

    /**
     * import products process
     *
     * $products array product information from XML file
     * @since    1.0.0
     */
    protected function import_products($product, $row_id)
    {
        global $wpdb;
        if (!empty($product)) {
            try {
                $prod = json_decode($product);
                //product sku
                $sku = (string)$prod->SUPPLIER_AID;
                $product_id = $this->get_product_by_sku($sku);
                //product title
                $title = (string)$prod->ARTICLE_DETAILS->DESCRIPTION_SHORT;
                //product description
                $des = (string)$prod->ARTICLE_DETAILS->DESCRIPTION_LONG;
                //product meta
                $ean = (string)$prod->ARTICLE_DETAILS->EAN;
                //product meta
                $manu_aid = (string)$prod->ARTICLE_DETAILS->MANUFACTURER_AID;
                //product meta
                $manu_name = (string)$prod->ARTICLE_DETAILS->MANUFACTURER_NAME;
                //product meta
                $deli_time = (string)$prod->ARTICLE_DETAILS->DELIVERY_TIME;
                //product tags
                $tags = (array)$prod->ARTICLE_DETAILS->KEYWORD;
                //product meta
                $status = (string)$prod->ARTICLE_DETAILS->ARTICLE_STATUS;
                //Product attributes
                $attributes = (array)$prod->ARTICLE_FEATURES;
                //product price
                $prices = (array)$prod->ARTICLE_PRICE_DETAILS;
                //product stock quantity, length, height, width, shipping class
                $prod_data = (array)$prod->USER_DEFINED_EXTENSIONS;
                $imgs = (array)$prod->MIME_INFO;
                //min/max quantity if needed
                $order = (array)$prod->ARTICLE_ORDER_DETAILS;

                //update old product
                if ($product_id != 0) {
                    $objProduct = new WC_Product_Simple($product_id);
                } //new product
                else {
                    $objProduct = new WC_Product_Simple();
                    $objProduct->set_sku($sku);
                }

                $objProduct->set_name($title);
                $objProduct->set_status("publish");
                $objProduct->set_catalog_visibility('visible');
                $objProduct->set_description($des);
                $objProduct->set_stock_status('instock');
                $objProduct->set_manage_stock(true);
                $objProduct->set_backorders('no');
                $objProduct->set_reviews_allowed('no');
                $objProduct->set_sold_individually(false);
                //Save product meta data after created
                $attribs = $this->save_product_attributes($product_id, $attributes);
                $objProduct->set_props(array(
                    'attributes' => $attribs,
                ));
                $post_id = $objProduct->save();
                if ($post_id <= 0) {
                    Sx_Write_Log::write_log("Error: Cannot create/update product with SKU: " . $sku);
                    $this->is_error = true;
                } else {
                    $this->save_product_data($post_id, $prod_data);
                    $this->save_prices($post_id, $prices);
                    $this->save_image($post_id, $imgs);
                    //Attribute Terms: These need to be set otherwise the attributes dont show on the admin backend:
                    foreach ($attribs as $attrib) {
                        /** @var WC_Product_Attribute $attrib */
                        $tax = $attrib->get_name();
                        $vals = $attrib->get_options();
                        $termsToAdd = array();
                        if (is_array($vals) && count($vals) > 0) {
                            foreach ($vals as $val) {
                                //Get or create the term if it doesnt exist:
                                $term = $this->get_attribute_term($val, $tax);

                                if ($term['id']) {
                                    $termsToAdd[] = $term['id'];
                                }
                            }
                        }
                        if (!empty($termsToAdd) && count($termsToAdd) > 0) {
                            wp_set_object_terms($post_id, $termsToAdd, $tax, true);
                        }
                    }
                    wp_set_object_terms($post_id, $tags, 'product_tag');
                    update_post_meta($post_id, '_ean', $ean);
                    update_post_meta($post_id, '_manufacturer_aid', $manu_aid);
                    update_post_meta($post_id, '_manufacturer_name', $manu_name);
                    update_post_meta($post_id, '_delivery_time', $deli_time);
                    update_post_meta($post_id, '_article_status', $status);
                    $this->save_order($post_id, $order);
                }
                Sx_Write_Log::write_log('Successful: Product ' . $post_id . ' SKU ' . $sku . ' was imported');
            } catch (Exception $e) {
                Sx_Write_Log::write_log('Error: Cannot import product ' . $post_id . ' SKU ' . $sku . ': ' . $e->getMessage());
            }
        }
        $wpdb->delete(SX_TABLE, array('id' => $row_id));
    }

    /**
     * save the order information in XML
     *
     * $product_ID int product ID
     * $order array product order information
     * @since    1.0.0
     */
    protected function save_order($product_id, $order)
    {
        try {
            update_post_meta($product_id, '_order_pce', $order['ORDER_UNIT']);
            update_post_meta($product_id, '_order_unit', $order['CONTENT_UNIT']);
            update_post_meta($product_id, '_order_no_cu', $order['NO_CU_PER_OU']);
            update_post_meta($product_id, '_order_min', $order['QUANTITY_MIN']);
            update_post_meta($product_id, '_order_interval', $order['QUANTITY_INTERVAL']);
        } catch (Exception $e) {
            Sx_Write_Log::write_log('Error: Cannot save order PCE information for product ' . $product_id . ': ' . $e->getMessage());
            $this->is_error = true;
        }
    }

    /**
     * save product prices
     *
     * $product_ID int product ID
     * $prices array product prices
     * @since    1.0.0
     */
    protected function save_prices($product_id, $prices)
    {
        try {
            if (!empty($prices['ARTICLE_PRICE'])) {
                foreach ($prices['ARTICLE_PRICE'] as $price) {
                    switch ($price->{'@attributes'}?->price_type) {
                        case 'net_customer':
                            empty($price->PRICE_AMOUNT) ?? update_post_meta($product_id, '_price', $price->PRICE_AMOUNT);
                            empty($price->TAX) ?? update_post_meta($product_id, '_tax_price', $price->TAX);
                            break;
                        case 'nrp':
                            empty($price->PRICE_AMOUNT) ?? update_post_meta($product_id, '_price_nrp', $price->PRICE_AMOUNT);
                            empty($price->TAX) ?? update_post_meta($product_id, '_tax_price_nrp', $price->TAX);
                            break;
                        case 'udp_price_1_excl_charge':
                            empty($price->PRICE_AMOUNT) ?? update_post_meta($product_id, '_price_udp', $price->PRICE_AMOUNT);
                            empty($price->TAX) ?? update_post_meta($product_id, '_tax_price_udp', $price->TAX);
                            break;
                        default:
                            break;
                    }
                }
            }
        } catch (Exception $e) {
            Sx_Write_Log::write_log('Error: Cannot save price for product ' . $product_id . ': ' . $e->getMessage());
            $this->is_error = true;
        }
    }

    /**
     * Prepare data product attributes
     *
     * $product_ID int product ID
     * $attr array product attributes
     * @since    1.0.0
     */
    protected function save_product_attributes($product_ID, $attr): bool|array
    {
        try {
            if (!empty($attr['FEATURE']) && is_array($attr['FEATURE'])) {
                $pos = 0;
                foreach ($attr['FEATURE'] as $at) {
                    $name = (string)$at->FNAME;
                    $string_val = (string)$at->FVALUE;
                    $suffix = !empty($at->FVALUE_DETAILS) ? ' ' . $at->FVALUE_DETAILS : '';
                    $values = $string_val . $suffix;
                    if (empty($name) || empty($values)) {
                        Sx_Write_Log::write_log("Warning: Empty attribute " . $product_ID . ': ' . $name . ' ' . $values);
                        continue;
                    }
                    if (!is_array($values)) {
                        $values = array($values);
                    }

                    $attribute = new WC_Product_Attribute();
                    $attribute->set_id(0);
                    $attribute->set_position($pos);
                    $attribute->set_visible(true);
                    $attribute->set_variation(true);
                    $pos++;
                    //Look for existing attribute:
                    $existingTaxes = wc_get_attribute_taxonomies();

                    //attribute_labels is in the format: array("slug" => "label / name")
                    $attribute_labels = wp_list_pluck($existingTaxes, 'attribute_label', 'attribute_name');
                    $slug = array_search($name, $attribute_labels, true);

                    if (!$slug) {
                        //Not found, so create it:
                        $slug = wc_sanitize_taxonomy_name($name);
                        $attribute_id = $this->create_global_attribute($name, $slug);
                    } else {
                        //Otherwise find it's ID
                        //Taxonomies are in the format: array("slug" => 12, "slug" => 14)
                        $taxonomies = wp_list_pluck($existingTaxes, 'attribute_id', 'attribute_name');

                        if (!isset($taxonomies[$slug])) {
                            Sx_Write_Log::write_log("Warning: Could not get wc attribute ID for attribute " . $name . " (slug: " . $slug . ") which should have existed!");
                            continue;
                        }

                        $attribute_id = (int)$taxonomies[$slug];
                    }

                    $taxonomy_name = wc_attribute_taxonomy_name($slug);

                    $attribute->set_id($attribute_id);
                    $attribute->set_name($taxonomy_name);
                    $attribute->set_options($values);

                    $attributes[] = $attribute;
                }
                return $attributes;
            }
        } catch (Exception $e) {
            Sx_Write_Log::write_log('Error: Cannot create attributes for product ' . $product_ID . ': ' . $e->getMessage());
            $this->is_error = true;
        }
        return false;
    }

    /**
     * save product tags
     *
     * @since    1.0.0
     */
    protected function save_product_tags($product, $tags)
    {
        try {
            wp_set_object_terms($product, $tags, 'product_tag');
        } catch (Exception $e) {
            Sx_Write_Log::write_log('Error: Cannot add product tag ' . $product . ': ' . $e->getMessage());
            $this->is_error = true;
        }
        return $product;
    }

    /**
     * Save meta data for product, include: width, length, height, quantity, class shipping (0 = package by delevery service, 1 = you will get it from a carrier on a pallet)
     *
     * @since    1.0.0
     */
    protected function save_product_data($product_ID, $data)
    {
        try {
            foreach ($data as $key => $d) {
                switch ($key) {
                    case 'UDX.SECOMP.STOCK_QUANTITY':
                        update_post_meta($product_ID, '_stock', $d);
                        if ($d > 0) {
                            update_post_meta($product_ID, '_stock_status', 'instock');
                        }
                        break;
                    case 'UDX.SECOMP.LENGTH':
                        $d = 0 ?: update_post_meta($product_ID, '_length', $d);
                        break;
                    case 'UDX.SECOMP.WIDTH':
                        $d = 0 ?: update_post_meta($product_ID, '_width', $d);
                        break;
                    case 'UDX.SECOMP.HEIGHT':
                        $d = 0 ?: update_post_meta($product_ID, '_height', $d);
                        break;
                    //<UDX.SECOMP.FREIGHT> = 0 means, that the product will send as package by delivery service. <UDX.SECOMP.FREIGHT> = 1 means that the product is very big or heavy and you will get it from a carrier on a pallet.
                    case 'UDX.SECOMP.FREIGHT':
                        update_post_meta($product_ID, '_freight', $d);
                        break;
                    case 'UDX.SECOMP.TRANSLATION_LANGUAGE':
                        update_post_meta($product_ID, '_translation_language', $d);
                        break;
                    default;
                        break;
                }
            }
        } catch (Exception $e) {
            Sx_Write_Log::write_log('Error: Cannot save product meta data ' . $product_ID . ': ' . $e->getMessage());
            $this->is_error = true;
        }
    }

    /**
     * create attribute terms
     *
     * @since    1.0.0
     */
    protected function create_global_attribute($name, $slug): WP_Error|int
    {

        $taxonomy_name = wc_attribute_taxonomy_name($slug);

        if (taxonomy_exists($taxonomy_name)) {
            return wc_attribute_taxonomy_id_by_name($slug);
        }

        $attribute_id = wc_create_attribute(array(
            'name' => $name,
            'slug' => $slug,
            'type' => 'select',
            'order_by' => 'menu_order',
            'has_archives' => false,
        ));

        //Register it as a wordpress taxonomy for just this session. Later on this will be loaded from the woocommerce taxonomy table.
        register_taxonomy(
            $taxonomy_name,
            apply_filters('woocommerce_taxonomy_objects_' . $taxonomy_name, array('product')),
            apply_filters('woocommerce_taxonomy_args_' . $taxonomy_name, array(
                'labels' => array(
                    'name' => $name,
                ),
                'hierarchical' => true,
                'show_ui' => false,
                'query_var' => true,
                'rewrite' => false,
            ))
        );

        //Clear caches
        delete_transient('wc_attribute_taxonomies');

        return $attribute_id;
    }

    /**
     * get attribute term
     *
     * @since    1.0.0
     */
    protected function get_attribute_term($value, $taxonomy)
    {
        //Look if there is already a term for this attribute?
        $term = get_term_by('name', $value, $taxonomy);

        if (!$term) {
            //No, create new term.
            $term = wp_insert_term($value, $taxonomy);
            if (is_wp_error($term)) {
                Sx_Write_Log::write_log("Unable to create new attribute term for " . $value . " in tax " . $taxonomy . "! " . $term->get_error_message());
                $this->is_error = true;
                return array('id' => false, 'slug' => false);
            }
            $termId = $term['term_id'];
            $term_slug = get_term($termId, $taxonomy)->slug; // Get the term slug
        } else {
            //Yes, grab it's id and slug
            $termId = $term->term_id;
            $term_slug = $term->slug;
        }

        return array('id' => $termId, 'slug' => $term_slug);
    }


    /**
     * save product featured image and gallery
     *
     * @since    1.0.0
     */
    protected function save_image($product_id, $imgs)
    {
        try {
            $thumbnail = false;
            $gallery = array();
            foreach ($imgs['MIME'] as $key => $img) {
                $img = (array)$img;
                if (!empty($img['MIME_SOURCE'])) {
                    $img_url = SX_CLIENT_URL . $img['MIME_SOURCE'];
                    $media_class = Sx_Import_Media::get_instance();
                    $thumb_id = $media_class->download_img($product_id, $img_url, $img['MIME_SOURCE']);
                    switch ($img['MIME_PURPOSE']) {
                        case 'logo';
                            update_post_meta($product_id, '_supplier_logo_' . $key, $thumb_id);
                            break;
                        case 'normal';
                            if (!$thumbnail) {
                                set_post_thumbnail($product_id, $thumb_id);
                                $thumbnail = true;
                            } else {
                                array_push($gallery, $thumb_id);
                            }
                            break;
                        default:
                            break;
                    }
                }
            }
            if (!empty($gallery)) {
                update_post_meta($product_id, '_product_image_gallery', implode(',', array_values($gallery)));
            }

        } catch (Exception $e) {
            Sx_Write_Log::write_log('Cannot save image to product ' . $product_id . ': ' . $e->getMessage());
            $this->is_error = true;
        }
    }

    /**
     * map category to product based on XML tag
     *
     * @since    1.0.0
     */
    protected function map_category_to_product($maps, $row_id)
    {
        global $wpdb;
        if (!empty($maps)) {
            $info = json_decode($maps);
            $sku = (string)$info->ART_ID;
            $cat = (string)$info->CATALOG_GROUP_ID;
            try {
                $product_id = $this->get_product_by_sku($sku);
                $cat_id = $this->get_category($cat);
                if ($product_id != 0 && $cat_id != 0) {
                    wp_set_object_terms($product_id, $cat_id, 'product_cat');
                } else {
                    Sx_Write_Log::write_log('Warning: Empty category ' . $cat . ' or product ' . $sku . ' ' . ($product_id != 0 && $cat_id != 0));
                }
            } catch (Exception $e) {
                Sx_Write_Log::write_log('Error: Cannot map category ' . $cat . ' to product ' . $sku . ': ' . $e->getMessage());
                $this->is_error = true;
            }
        }
        $wpdb->delete(SX_TABLE, array('id' => $row_id));
    }

    /**
     * AJAX action in admin page
     * 1: import categories, products and map category to product
     * 2: import categories only
     * 3: import products and map category to product
     * 4: map category to product
     * 5: fire cronjob action
     *
     * @since    1.0.0
     */
    function ajax_import_products()
    {
        $type = $_POST['import_type'];
        do_action('sx_before_importing');
        switch ($type) {
            case 1:
                $this->import_all();
                break;
            case 2:
                $this->import_data_to_table();
                break;
            default:
                break;
        }
        if ($this->is_error) {
            Sx_Send_Email::send_email();
        }
        do_action('sx_after_importing');
        wp_die();
    }
}
