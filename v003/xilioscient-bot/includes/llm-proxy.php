<?php
/**
 * LLM Proxy
 * Gestisce la comunicazione con il servizio LLM locale
 */

if (!defined('ABSPATH')) {
    exit;
}

class XSBOT_LLM_Proxy {
    
    /**
     * Invia messaggio al servizio LLM
     */
    public static function send_message($message, $session_id, $metadata = array()) {
        $endpoint = get_option('xsbot_llm_endpoint', 'http://127.0.0.1:5000');
        $url = trailingslashit($endpoint) . 'api/chat';
        
        $jwt_token = self::generate_jwt();
        
        $body = array(
            'message' => $message,
            'session_id' => $session_id,
            'metadata' => $metadata,
            'top_k' => get_option('xsbot_top_k_retrieval', 5),
            'max_tokens' => get_option('xsbot_max_context_tokens', 2000),
        );
        
        $response = wp_remote_post($url, array(
            'timeout' => 60,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $jwt_token,
            ),
            'body' => json_encode($body),
        ));
        
        if (is_wp_error($response)) {
            // Prova fallback se configurato
            if (self::has_fallback()) {
                return self::send_to_fallback($message, $session_id);
            }
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            // Prova fallback
            if (self::has_fallback()) {
                return self::send_to_fallback($message, $session_id);
            }
            
            return new WP_Error(
                'llm_error',
                sprintf(__('Errore servizio LLM: %d', 'xilioscient-bot'), $status_code)
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return $data;
    }
    
    /**
     * Genera JWT token
     */
    private static function generate_jwt() {
        $secret = get_option('xsbot_jwt_secret');
        
        if (empty($secret)) {
            return '';
        }
        
        $header = array(
            'typ' => 'JWT',
            'alg' => 'HS256'
        );
        
        $payload = array(
            'iat' => time(),
            'exp' => time() + 3600,
            'iss' => get_site_url(),
        );
        
        $header_encoded = self::base64url_encode(json_encode($header));
        $payload_encoded = self::base64url_encode(json_encode($payload));
        
        $signature = hash_hmac('sha256', "$header_encoded.$payload_encoded", $secret, true);
        $signature_encoded = self::base64url_encode($signature);
        
        return "$header_encoded.$payload_encoded.$signature_encoded";
    }
    
    /**
     * Base64 URL-safe encoding
     */
    private static function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Verifica se è configurato un fallback
     */
    private static function has_fallback() {
        $provider = get_option('xsbot_fallback_api_provider', 'none');
        return $provider !== 'none' && !empty(get_option('xsbot_fallback_api_key'));
    }
    
    /**
     * Invia a servizio fallback (OpenAI, Anthropic, etc.)
     */
    private static function send_to_fallback($message, $session_id) {
        $provider = get_option('xsbot_fallback_api_provider');
        $api_key = get_option('xsbot_fallback_api_key');
        
        switch ($provider) {
            case 'openai':
                return self::send_to_openai($message, $api_key);
            case 'anthropic':
                return self::send_to_anthropic($message, $api_key);
            default:
                return new WP_Error('no_fallback', __('Servizio LLM non disponibile', 'xilioscient-bot'));
        }
    }
    
    /**
     * Invia a OpenAI
     */
    private static function send_to_openai($message, $api_key) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $body = array(
            'model' => 'gpt-3.5-turbo',
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'Sei un assistente AI utile e cortese. Rispondi in italiano.'
                ),
                array(
                    'role' => 'user',
                    'content' => $message
                )
            ),
            'max_tokens' => 500,
        );
        
        $response = wp_remote_post($url, array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'body' => json_encode($body),
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            return array(
                'response' => $data['choices'][0]['message']['content'],
                'sources' => array(),
                'fallback' => true,
            );
        }
        
        return new WP_Error('openai_error', __('Errore OpenAI', 'xilioscient-bot'));
    }
    
    /**
     * Invia a Anthropic
     */
    private static function send_to_anthropic($message, $api_key) {
        $url = 'https://api.anthropic.com/v1/messages';
        
        $body = array(
            'model' => 'claude-3-haiku-20240307',
            'max_tokens' => 500,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $message
                )
            ),
        );
        
        $response = wp_remote_post($url, array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'x-api-key' => $api_key,
                'anthropic-version' => '2023-06-01',
            ),
            'body' => json_encode($body),
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['content'][0]['text'])) {
            return array(
                'response' => $data['content'][0]['text'],
                'sources' => array(),
                'fallback' => true,
            );
        }
        
        return new WP_Error('anthropic_error', __('Errore Anthropic', 'xilioscient-bot'));
    }
    
    /**
     * Indicizza documento
     */
    public static function index_document($file_path, $file_url) {
        $endpoint = get_option('xsbot_llm_endpoint', 'http://127.0.0.1:5000');
        $url = trailingslashit($endpoint) . 'api/index';
        
        $jwt_token = self::generate_jwt();
        
        // Leggi contenuto file
        $file_content = file_get_contents($file_path);
        $file_name = basename($file_path);
        
        $body = array(
            'file_name' => $file_name,
            'file_content' => base64_encode($file_content),
            'file_url' => $file_url,
            'chunk_size' => get_option('xsbot_chunk_size', 500),
            'chunk_overlap' => get_option('xsbot_chunk_overlap', 50),
        );
        
        $response = wp_remote_post($url, array(
            'timeout' => 120,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $jwt_token,
            ),
            'body' => json_encode($body),
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            return new WP_Error(
                'indexing_error',
                sprintf(__('Errore indicizzazione: %d', 'xilioscient-bot'), $status_code)
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
    
    /**
     * Ottieni lista documenti
     */
    public static function get_documents() {
        $endpoint = get_option('xsbot_llm_endpoint', 'http://127.0.0.1:5000');
        $url = trailingslashit($endpoint) . 'api/documents';
        
        $jwt_token = self::generate_jwt();
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $jwt_token,
            ),
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
    
    /**
     * Elimina documento
     */
    public static function delete_document($doc_id) {
        $endpoint = get_option('xsbot_llm_endpoint', 'http://127.0.0.1:5000');
        $url = trailingslashit($endpoint) . 'api/documents/' . $doc_id;
        
        $jwt_token = self::generate_jwt();
        
        $response = wp_remote_request($url, array(
            'method' => 'DELETE',
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $jwt_token,
            ),
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
    
    /**
     * Verifica salute servizio LLM
     */
    public static function check_health() {
        $endpoint = get_option('xsbot_llm_endpoint', 'http://127.0.0.1:5000');
        $url = trailingslashit($endpoint) . 'api/health';
        
        $response = wp_remote_get($url, array(
            'timeout' => 5,
        ));
        
        if (is_wp_error($response)) {
            return array(
                'status' => 'error',
                'message' => $response->get_error_message(),
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            return array_merge(array('status' => 'ok'), $data);
        }
        
        return array(
            'status' => 'error',
            'code' => $status_code,
        );
    }
}
