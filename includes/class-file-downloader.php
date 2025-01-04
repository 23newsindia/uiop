<?php
class SFD_File_Downloader {
    public static function init() {
        add_action('init', [self::class, 'handle_download_request']);
        add_filter('the_content', [self::class, 'add_download_buttons']);
    }
    
    public static function handle_download_request() {
        if (!isset($_GET['sfd_legacy']) || !isset($_GET['file']) || !isset($_GET['post']) || !isset($_GET['nonce'])) {
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_GET['nonce'], 'sfd_legacy_download')) {
            wp_die('Security check failed');
        }

        $post_id = absint($_GET['post']);
        $file_url = get_post_meta($post_id, '_json_file_url', true);
        
        if (!$file_url) {
            wp_die('File not found');
        }

        $upload_dir = wp_upload_dir();
        $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $file_url);
        
        if (!file_exists($file_path)) {
            error_log('SFD: File not found at path: ' . $file_path);
            wp_die('File not found on server');
        }

        $mime_type = wp_check_filetype($file_path)['type'] ?: 'application/octet-stream';
        $filename = basename($file_path);

        // Clear output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Force download
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        readfile($file_path);
        exit;
    }
    
    public static function add_download_buttons($content) {
        global $post;
        
        if (!is_singular() || !is_main_query()) {
            return $content;
        }

        $file_url = get_post_meta($post->ID, '_json_file_url', true);
        if (!$file_url) {
            return $content;
        }

        $download_type = (pathinfo($file_url, PATHINFO_EXTENSION) === 'json') ? 'Workflows' : 'LoRA';
        
        $download_url = add_query_arg([
            'sfd_legacy' => '1',
            'file' => '1',
            'post' => $post->ID,
            'nonce' => wp_create_nonce('sfd_legacy_download')
        ], site_url());

        $button = sprintf(
            '<div class="sfd-download-button">
                <a href="%s" class="button" onclick="handleDownloadClick(event, this)">
                    Download %s
                </a>
                <div class="robot-message">🤖 Hey, welcome! Thanks for visiting comfyuiblog.com</div>
                <div class="firework"></div>
            </div>',
            esc_url($download_url),
            esc_html($download_type)
        );

        return $content . $button;
    }
}