<?php

class Sx_Import_Media
{
    /**
     * The instance
     *
     * @since    1.0.0
     * @access   private
     * @var      Sx_Import_Media $instance Singleton class
     */
    private static $instance = null;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version The version of this plugin.
     * @since    1.0.0
     */
    public function __construct()
    {
    }

    /**
     * Only if the class has no instance
     *
     * @since    1.0.0
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new Sx_Import_Media();
        }

        return self::$instance;
    }

    protected function check_attachment_exists($name)
    {
        global $wpdb;
        $sql = $wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta 
         WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s",
            '%/' . $name
        );
        return $wpdb->get_var($sql);
    }

    public function download_img($gr_id, $url, $img = '')
    {
        $message = "Download image catalog $gr_id: ";

        // Gives us access to the download_url() and wp_handle_sideload() functions.
        require_once ABSPATH . 'wp-admin/includes/file.php';

        // Does the attachment already exist?
        $path_parts = pathinfo($url);
        $img_name = basename($path_parts['filename']);
        if ($this->check_attachment_exists(basename($path_parts['basename']))) {
            $attachment = get_page_by_title($img_name, OBJECT, 'attachment');
            if (!empty($attachment)) {
                $img_xml = get_post_meta($attachment->ID,'_img_xml_path', true);
                if ($img_xml == $img) {
                    Sx_Write_Log::write_log('Warning: Attachment already exist ID: ' . $attachment->ID . ' category/product ID ' . $gr_id);
                    return $attachment->ID;
                }
            }
        }

        // Download file to temp dir.
        $temp_file = download_url($url);

        if (is_wp_error($temp_file)) {
            $message .= ' ' . $url . ' ' . $temp_file->get_error_message();
            Sx_Write_Log::write_log($message);
            return false;
        }

        $mime_type = mime_content_type($temp_file);

        if (!$this->is_supported_image_type($mime_type)) {
            $message .= "not support image type";
            Sx_Write_Log::write_log($message);
            return false;
        }

        // An array similar to that of a PHP `$_FILES` POST array
        $file = array(
            'name' => basename($url),
            'type' => $mime_type,
            'tmp_name' => $temp_file,
            'error' => 0,
            'size' => filesize($temp_file),
        );

        $overrides = array(
            // This tells WordPress to not look for the POST form
            // fields that would normally be present. Default is true.
            // Since the file is being downloaded from a remote server,
            // there will be no form fields.
            'test_form' => false,

            // Setting this to false lets WordPress allow empty files – not recommended.
            'test_size' => true,

            // A properly uploaded file will pass this test.
            // There should be no reason to override this one.
            'test_upload' => true,
        );

        // Move the temporary file into the uploads directory.
        $file_attributes = wp_handle_sideload($file, $overrides);

        if ($this->did_a_sideloading_error_occur($file_attributes)) {
            $message .= "an error occur while sideloading the file";
            Sx_Write_Log::write_log($message);
            return false;
        }

        $attachment_id = $this->insert_attachment($file_attributes['file'], $file_attributes['type']);
        update_post_meta($attachment_id,'_img_xml_path', $img);
        $this->update_attachment_metadata($attachment_id);
        return $attachment_id;
    }

    /**
     * Is this image MIME type supported by the WordPress Media Libarary?
     *
     * @param string $mime_type The MIME type.
     *
     * @return bool
     */
    protected function is_supported_image_type($mime_type)
    {
        return in_array($mime_type, array('image/jpeg', 'image/gif', 'image/png', 'image/x-icon'), true);
    }

    /**
     * Did an error occur while sideloading the file?
     *
     * @param array $file_attributes The file attribues, or array containing an 'error' key on failure.
     *
     * @return bool
     */
    protected function did_a_sideloading_error_occur($file_attributes)
    {
        return isset($file_attributes['error']);
    }

    /**
     * Insert attachment into the WordPress Media Library.
     *
     * @param string $file_path The path to the media file.
     * @param string $mime_type The MIME type of the media file.
     */
    protected function insert_attachment($file_path, $mime_type)
    {

        // Get the path to the uploads directory.
        $wp_upload_dir = wp_upload_dir();

        // Prepare an array of post data for the attachment.
        $attachment_data = array(
            'guid' => $wp_upload_dir['url'] . '/' . basename($file_path),
            'post_mime_type' => $mime_type,
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($file_path)),
            'post_content' => '',
            'post_status' => 'inherit',
        );

        return wp_insert_attachment($attachment_data, $file_path);
    }

    /**
     * Update attachment metadata.
     */
    protected function update_attachment_metadata($attachment_id)
    {

        $file_path = get_attached_file($attachment_id);

        if (!$file_path) {
            return;
        }

        // Gives us access to the wp_generate_attachment_metadata() function.
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Generate metadata and image sizes for the attachment.
        $attach_data = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attach_data);
    }

}