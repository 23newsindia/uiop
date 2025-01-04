<?php
class SFD_File_Handler {
    public static function init() {
        add_filter('upload_dir', [self::class, 'modify_upload_dir']);
    }

    public static function modify_upload_dir($uploads) {
        if (isset($_POST['sfd_secure_upload'])) {
            $uploads['subdir'] = '/secure-downloads' . $uploads['subdir'];
            $uploads['path'] = $uploads['basedir'] . $uploads['subdir'];
            $uploads['url'] = $uploads['baseurl'] . $uploads['subdir'];
        }
        return $uploads;
    }

    public static function get_file_path($file_url) {
        $upload_dir = wp_upload_dir();
        $file_path = str_replace(
            [$upload_dir['baseurl'], site_url()],
            [$upload_dir['basedir'], ABSPATH],
            $file_url
        );
        
        // Ensure the file is within the uploads directory
        if (strpos($file_path, $upload_dir['basedir']) !== 0) {
            return false;
        }
        
        return $file_path;
    }
}