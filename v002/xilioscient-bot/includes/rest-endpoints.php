<?php
/**
 * REST API Endpoints
 * Gestisce le chiamate REST API per il chatbot
 */

if (!defined('ABSPATH')) {
    exit;
}

class XSBOT_REST_Endpoints {
    
    /**
     * Registra le route REST API
     */
    public static function register_routes() {
        // Endpoint principale per messaggi
        register_rest_route('mlc/v1', '/message', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'handle_message'),
            'permission_callback' => '__return_true',
            'args' => array(
                'message' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                    'validate_callback' => array(__CLASS__, 'validate_message'),
                ),
                'session_id' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'metadata' => array(
                    'required' => false,
                    'type' => 'object',
                    'default' => array(),
                ),
            ),
        ));
        
        // Endpoint per feedback
        register_rest_route('mlc/v1', '/feedback', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'handle_feedback'),
            'permission_callback' => '__return_true',
            'args' => array(
                'conversation_id' => array(
                    'required' => true,
                    'type' => 'integer',
                ),
                'feedback_type' => array(
                    'required' => true,
                    'type' => 'string',
                    'enum' => array('positive', 'negative'),
                ),
                'comment' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field',
                ),
            ),
        ));
        
        // Endpoint admin per caricamento documenti
        register_rest_route('mlc/v1', '/admin/upload', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'handle_document_upload'),
            'permission_callback' => array(__CLASS__, 'check_admin_permission'),
        ));
        
        // Endpoint admin per lista documenti
        register_rest_route('mlc/v1', '/admin/documents', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_documents_list'),
            'permission_callback' => array(__CLASS__, 'check_admin_permission'),
        ));
        
        // Endpoint admin per eliminazione documenti
        register_rest_route('mlc/v1', '/admin/documents/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array(__CLASS__, 'delete_document'),
            'permission_callback' => array(__CLASS__, 'check_admin_permission'),
        ));
        
        // Endpoint admin per statistiche
        register_rest_route('mlc/v1', '/admin/stats', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_statistics'),
            'permission_callback' => array(__CLASS__, 'check_admin_permission'),
        ));
        
        // Endpoint per health check
        register_rest_route('mlc/v1', '/health', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'health_check'),
            'permission_callback' => '__return_true',
        ));
    }
    
    /**
     * Valida il messaggio
     */
    public static function validate_message($value, $request, $param) {
        if (empty(trim($value))) {
            return new WP_Error('empty_message', __('Il messaggio non può essere vuoto', 'xilioscient-bot'));
        }
        
        if (strlen($value) > 5000) {
            return new WP_Error('message_too_long', __('Il messaggio è troppo lungo', 'xilioscient-bot'));
        }
        
        return true;
    }
    
    /**
     * Verifica permessi admin
     */
    public static function check_admin_permission() {
        return current_user_can('manage_options');
    }
    
    /**
     * Gestisce un messaggio chat
     */
    public static function handle_message($request) {
        $message = $request->get_param('message');
        $session_id = $request->get_param('session_id');
        $metadata = $request->get_param('metadata');
        
        // Verifica rate limiting
        $rate_limit_check = self::check_rate_limit($session_id);
        if (is_wp_error($rate_limit_check)) {
            return $rate_limit_check;
        }
        
        // Filtro anti-prompt-injection di base
        $filtered_message = self::filter_prompt_injection($message);
        
        // Inoltra al servizio LLM
        $llm_response = XSBOT_LLM_Proxy::send_message($filtered_message, $session_id, $metadata);
        
        if (is_wp_error($llm_response)) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => $llm_response->get_error_message(),
                'code' => $llm_response->get_error_code(),
            ), 500);
        }
        
        // Salva conversazione se logging abilitato
        $conversation_id = null;
        if (get_option('xsbot_log_conversations', true)) {
            $conversation_id = self::save_conversation($session_id, $message, $llm_response);
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'response' => $llm_response['response'],
            'conversation_id' => $conversation_id,
            'sources' => $llm_response['sources'] ?? array(),
            'confidence' => $llm_response['confidence'] ?? null,
        ), 200);
    }
    
    /**
     * Verifica rate limiting
     */
    private static function check_rate_limit($session_id) {
        $max_requests = get_option('xsbot_rate_limit_requests', 20);
        $window = get_option('xsbot_rate_limit_window', 3600);
        
        $transient_key = 'xsbot_rate_limit_' . md5($session_id);
        $requests = get_transient($transient_key);
        
        if (false === $requests) {
            $requests = array();
        }
        
        // Pulisci richieste vecchie
        $now = time();
        $requests = array_filter($requests, function($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        });
        
        if (count($requests) >= $max_requests) {
            return new WP_Error(
                'rate_limit_exceeded',
                __('Troppe richieste. Riprova tra qualche minuto.', 'xilioscient-bot'),
                array('status' => 429)
            );
        }
        
        $requests[] = $now;
        set_transient($transient_key, $requests, $window);
        
        return true;
    }
    
    /**
     * Filtro di base anti-prompt-injection
     */
    private static function filter_prompt_injection($message) {
        // Rimuovi tentativi comuni di prompt injection
        $patterns = array(
            '/ignore\s+(previous|all|above|prior)\s+instructions?/i',
            '/forget\s+(everything|all|previous)/i',
            '/you\s+are\s+now/i',
            '/system\s*:/i',
            '/\[INST\]/i',
            '/\[\/INST\]/i',
            '/<\|im_start\|>/i',
            '/<\|im_end\|>/i',
        );
        
        foreach ($patterns as $pattern) {
            $message = preg_replace($pattern, '[REMOVED]', $message);
        }
        
        return $message;
    }
    
    /**
     * Salva conversazione nel database
     */
    private static function save_conversation($session_id, $user_message, $llm_response) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'xsbot_conversations';
        
        // Anonimizza IP se necessario
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        if (get_option('xsbot_anonymize_ip', false)) {
            $ip_address = self::anonymize_ip($ip_address);
        }
        
        $data = array(
            'session_id' => $session_id,
            'user_id' => get_current_user_id() ?: null,
            'user_message' => $user_message,
            'bot_response' => $llm_response['response'] ?? '',
            'context_used' => isset($llm_response['context']) ? json_encode($llm_response['context']) : null,
            'metadata' => isset($llm_response['metadata']) ? json_encode($llm_response['metadata']) : null,
            'ip_address' => $ip_address,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'created_at' => current_time('mysql'),
        );
        
        $wpdb->insert($table_name, $data);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Anonimizza indirizzo IP
     */
    private static function anonymize_ip($ip) {
        if (empty($ip)) {
            return null;
        }
        
        // IPv4: rimuovi ultimo ottetto
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.0', $ip);
        }
        
        // IPv6: mantieni solo primi 4 blocchi
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            return implode(':', array_slice($parts, 0, 4)) . '::';
        }
        
        return null;
    }
    
    /**
     * Gestisce feedback
     */
    public static function handle_feedback($request) {
        global $wpdb;
        
        $conversation_id = $request->get_param('conversation_id');
        $feedback_type = $request->get_param('feedback_type');
        $comment = $request->get_param('comment');
        
        $table_name = $wpdb->prefix . 'xsbot_feedback';
        
        $data = array(
            'conversation_id' => $conversation_id,
            'feedback_type' => $feedback_type,
            'comment' => $comment,
            'created_at' => current_time('mysql'),
        );
        
        $result = $wpdb->insert($table_name, $data);
        
        if ($result) {
            return new WP_REST_Response(array(
                'success' => true,
                'message' => __('Grazie per il tuo feedback!', 'xilioscient-bot'),
            ), 200);
        }
        
        return new WP_Error('feedback_failed', __('Impossibile salvare il feedback', 'xilioscient-bot'));
    }
    
    /**
     * Gestisce caricamento documenti
     */
    public static function handle_document_upload($request) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $files = $request->get_file_params();
        
        if (empty($files['file'])) {
            return new WP_Error('no_file', __('Nessun file fornito', 'xilioscient-bot'));
        }
        
        // Verifica tipo file
        $allowed_types = array('application/pdf', 'text/html', 'text/markdown', 'text/plain');
        $file_type = $files['file']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            return new WP_Error('invalid_file_type', __('Tipo di file non supportato', 'xilioscient-bot'));
        }
        
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($files['file'], $upload_overrides);
        
        if ($movefile && !isset($movefile['error'])) {
            // Invia al servizio LLM per indicizzazione
            $indexing_result = XSBOT_LLM_Proxy::index_document($movefile['file'], $movefile['url']);
            
            if (is_wp_error($indexing_result)) {
                // Elimina file se indicizzazione fallisce
                wp_delete_file($movefile['file']);
                return $indexing_result;
            }
            
            return new WP_REST_Response(array(
                'success' => true,
                'file' => $movefile,
                'indexed' => $indexing_result,
            ), 200);
        }
        
        return new WP_Error('upload_error', $movefile['error']);
    }
    
    /**
     * Ottieni lista documenti
     */
    public static function get_documents_list($request) {
        $result = XSBOT_LLM_Proxy::get_documents();
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return new WP_REST_Response($result, 200);
    }
    
    /**
     * Elimina documento
     */
    public static function delete_document($request) {
        $doc_id = $request->get_param('id');
        
        $result = XSBOT_LLM_Proxy::delete_document($doc_id);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return new WP_REST_Response(array(
            'success' => true,
            'message' => __('Documento eliminato', 'xilioscient-bot'),
        ), 200);
    }
    
    /**
     * Ottieni statistiche
     */
    public static function get_statistics($request) {
        global $wpdb;
        
        $table_conversations = $wpdb->prefix . 'xsbot_conversations';
        $table_feedback = $wpdb->prefix . 'xsbot_feedback';
        
        // Statistiche conversazioni
        $total_conversations = $wpdb->get_var("SELECT COUNT(*) FROM $table_conversations");
        
        $today_conversations = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_conversations WHERE DATE(created_at) = %s",
            current_time('Y-m-d')
        ));
        
        $week_conversations = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_conversations WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        ));
        
        // Statistiche feedback
        $positive_feedback = $wpdb->get_var("SELECT COUNT(*) FROM $table_feedback WHERE feedback_type = 'positive'");
        $negative_feedback = $wpdb->get_var("SELECT COUNT(*) FROM $table_feedback WHERE feedback_type = 'negative'");
        
        // Sessioni uniche
        $unique_sessions = $wpdb->get_var("SELECT COUNT(DISTINCT session_id) FROM $table_conversations");
        
        // Conversazioni per giorno (ultimi 7 giorni)
        $daily_stats = $wpdb->get_results(
            "SELECT DATE(created_at) as date, COUNT(*) as count 
            FROM $table_conversations 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC"
        );
        
        return new WP_REST_Response(array(
            'total_conversations' => (int) $total_conversations,
            'today_conversations' => (int) $today_conversations,
            'week_conversations' => (int) $week_conversations,
            'unique_sessions' => (int) $unique_sessions,
            'positive_feedback' => (int) $positive_feedback,
            'negative_feedback' => (int) $negative_feedback,
            'daily_stats' => $daily_stats,
        ), 200);
    }
    
    /**
     * Health check
     */
    public static function health_check($request) {
        $llm_health = XSBOT_LLM_Proxy::check_health();
        
        return new WP_REST_Response(array(
            'wordpress' => array(
                'status' => 'ok',
                'version' => get_bloginfo('version'),
            ),
            'plugin' => array(
                'status' => 'ok',
                'version' => XSBOT_VERSION,
            ),
            'llm_service' => $llm_health,
        ), 200);
    }
}
