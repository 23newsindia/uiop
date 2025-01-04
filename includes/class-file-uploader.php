<?php
class SFD_File_Uploader {
    public static function init() {
        add_filter('upload_mimes', [self::class, 'add_allowed_mime_types']);
    }
    
    public static function add_allowed_mime_types($mimes) {
        $mimes['json'] = 'application/json';
        $mimes['safetensors'] = 'application/octet-stream';
        return $mimes;
    }
    
    public static function handle_file_upload($file, $post_id) {
        if (empty($file['name'])) {
            return false;
        }

        // Only allow specific file types
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['json', 'safetensors'])) {
            return false;
        }

        // Handle the upload
        $upload = wp_handle_upload($file, [
            'test_form' => false,
            'unique_filename_callback' => function($dir, $name, $ext) {
                return wp_unique_filename($dir, $name);
            }
        ]);

        if (!empty($upload['error'])) {
            error_log('SFD Upload Error: ' . $upload['error']);
            return false;
        }

        if (!empty($upload['url'])) {
            // Store the file URL
            update_post_meta($post_id, '_json_file_url', $upload['url']);
            return true;
        }

        return false;
    }
}