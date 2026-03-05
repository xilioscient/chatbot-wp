<?php
if (!defined('ABSPATH')) exit;

class XSBot_Knowledge_Handler {

    // Carica il file su Google File API
    public static function upload_to_google($path, $mime, $api_key) {
        $boundary = wp_generate_password(24);
        $filename = basename($path);
        $file_data = file_get_contents($path);

        $payload = "--$boundary\r\n";
        $payload .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
        $payload .= wp_json_encode(['file' => ['display_name' => $filename]]) . "\r\n";
        $payload .= "--$boundary\r\n";
        $payload .= "Content-Type: $mime\r\n\r\n";
        $payload .= $file_data . "\r\n";
        $payload .= "--$boundary--";

        $response = wp_remote_post("https://generativelanguage.googleapis.com/upload/v1beta/files?key=$api_key", [
            'headers' => ['Content-Type' => "multipart/related; boundary=$boundary"],
            'body'    => $payload,
            'timeout' => 60
        ]);

        if (is_wp_error($response)) return false;
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['file'] ?? false;
    }

    // Handler AJAX: Caricamento
    public static function handle_upload() {
        check_ajax_referer('xsbot_upload_nonce', '_ajax_nonce');
        if (empty($_FILES['file'])) wp_send_json_error('File mancante');

        $api_key = get_option('xsbot_fallback_api_key');
        if (!$api_key) wp_send_json_error('Configura prima la API Key nelle impostazioni.');

        // 1. Salva localmente
        $upload = wp_handle_upload($_FILES['file'], ['test_form' => false]);
        if (isset($upload['error'])) wp_send_json_error($upload['error']);

        // 2. Invia a Google
        $google_file = self::upload_to_google($upload['file'], $upload['type'], $api_key);
        
        if ($google_file) {
            $kb = get_option('xsbot_knowledge_base', []);
            $kb[] = [
                'id'         => uniqid(),
                'name'       => $_FILES['file']['name'],
                'local_path' => $upload['file'],
                'uri'        => $google_file['uri'],
                'mime'       => $upload['type'],
                'expires'    => time() + (47 * 3600), // 47 ore
                'date'       => current_time('mysql')
            ];
            update_option('xsbot_knowledge_base', $kb);
            wp_send_json_success('File pronto.');
        }
        wp_send_json_error('Errore durante l\'invio a Google.');
    }

    // Handler AJAX: Cancellazione
    public static function handle_delete() {
        check_ajax_referer('xsbot_delete_nonce', '_ajax_nonce');
        $id = $_POST['id'];
        $kb = get_option('xsbot_knowledge_base', []);

        foreach ($kb as $key => $doc) {
            if ($doc['id'] === $id) {
                if (file_exists($doc['local_path'])) unlink($doc['local_path']);
                unset($kb[$key]);
                update_option('xsbot_knowledge_base', array_values($kb));
                wp_send_json_success();
            }
        }
        wp_send_json_error('File non trovato.');
    }
}

// Hook per WordPress
add_action('wp_ajax_xsbot_upload_knowledge_file', ['XSBot_Knowledge_Handler', 'handle_upload']);
add_action('wp_ajax_xsbot_delete_knowledge_file', ['XSBot_Knowledge_Handler', 'handle_delete']);