<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST endpoints namespace mlc/v1
 * - POST /mlc/v1/message
 * - Admin routes under /mlc/v1/admin/*
 */

add_action( 'rest_api_init', function () {
    register_rest_route( 'mlc/v1', '/message', array(
        'methods'             => 'POST',
        'callback'            => 'xilio_handle_message',
        'permission_callback' => '__return_true',
        'args'                => array(
            'message' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'session_id' => array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'metadata' => array(
                'required' => false,
                'sanitize_callback' => 'wp_kses_post',
            ),
        ),
    ) );

    // Admin-only: manage KB (protected by capability)
    register_rest_route( 'mlc/v1', '/admin/upload', array(
        'methods' => 'POST',
        'callback' => 'xilio_admin_upload',
        'permission_callback' => function() {
            return current_user_can( 'manage_options' );
        },
    ) );
} );

/* Simple rate limit using transients */
function xilio_rate_limited( $key, $limit = 20, $period = 60 ) {
    $trans_key = 'xilio_rl_' . md5( $key );
    $data = get_transient( $trans_key );
    if ( ! $data ) {
        set_transient( $trans_key, 1, $period );
        return false;
    }
    if ( $data >= $limit ) {
        return true;
    }
    set_transient( $trans_key, $data + 1, $period );
    return false;
}

/* Main message handler */
function xilio_handle_message( WP_REST_Request $request ) {
    $params = $request->get_json_params();
    $message = isset( $params['message'] ) ? sanitize_text_field( $params['message'] ) : '';
    $session_id = isset( $params['session_id'] ) ? sanitize_text_field( $params['session_id'] ) : wp_generate_uuid4();
    $metadata = isset( $params['metadata'] ) ? wp_kses_post( $params['metadata'] ) : '';

    if ( empty( $message ) ) {
        return new WP_REST_Response( array( 'error' => __( 'Messaggio vuoto', 'xilioscient-bot' ) ), 400 );
    }

    $client_ip = xilio_get_client_ip();
    if ( xilio_rate_limited( $client_ip, 30, 60 ) ) {
        return new WP_REST_Response( array( 'error' => __( 'Limite richieste superato', 'xilioscient-bot' ) ), 429 );
    }

    // Build payload to LLM service
    $options = get_option( 'xilio_options', array() );
    $llm_endpoint = rtrim( $options['llm_endpoint'] ?? 'http://127.0.0.1:5000', '/' );
    $jwt = xilio_generate_jwt( $options['jwt_secret'] ?? '' );

    $body = array(
        'message' => $message,
        'session_id' => $session_id,
        'metadata' => $metadata,
        'top_k' => intval( $options['top_k'] ?? 5 ),
    );

    $args = array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $jwt,
        ),
        'body' => wp_json_encode( $body ),
        'timeout' => 30,
    );

    $response = wp_remote_post( $llm_endpoint . '/api/chat', $args );

    if ( is_wp_error( $response ) ) {
        // fallback message
        return new WP_REST_Response( array( 'error' => __( 'Errore di comunicazione con il servizio LLM', 'xilioscient-bot' ) ), 502 );
    }

    $code = wp_remote_retrieve_response_code( $response );
    $resp_body = wp_remote_retrieve_body( $response );
    $data = json_decode( $resp_body, true );

    if ( $code >= 400 ) {
        return new WP_REST_Response( array( 'error' => $data['error'] ?? __( 'Errore dal servizio LLM', 'xilioscient-bot' ) ), $code );
    }

    // Optionally log conversation (respect GDPR settings)
    if ( ! empty( $options['log_conversations'] ) ) {
        xilio_log_conversation( $session_id, $message, $data );
    }

    return new WP_REST_Response( $data, 200 );
}

/* Admin upload handler (simple) */
function xilio_admin_upload( WP_REST_Request $request ) {
    if ( ! function_exists( 'wp_handle_upload' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    $files = $request->get_file_params();
    if ( empty( $files ) ) {
        return new WP_REST_Response( array( 'error' => __( 'Nessun file inviato', 'xilioscient-bot' ) ), 400 );
    }
    $uploaded = array();
    foreach ( $files as $file ) {
        $move = wp_handle_upload( $file, array( 'test_form' => false ) );
        if ( isset( $move['error'] ) ) {
            return new WP_REST_Response( array( 'error' => $move['error'] ), 500 );
        }
        $uploaded[] = $move['url'];
        // Optionally trigger indexing script (async)
        // wp_remote_post( admin_url('admin-ajax.php') . '?action=xilio_index', array('body'=>array('file'=>$move['file'])) );
    }
    return new WP_REST_Response( array( 'uploaded' => $uploaded ), 200 );
}

/* Helper: get client IP */
function xilio_get_client_ip() {
    if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        return sanitize_text_field( $_SERVER['HTTP_CLIENT_IP'] );
    } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        $ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
        return sanitize_text_field( trim( $ips[0] ) );
    } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
        return sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
    }
    return '0.0.0.0';
}

/* Simple logger */
function xilio_log_conversation( $session_id, $user_message, $response ) {
    global $wpdb;
    $table = $wpdb->prefix . 'xilio_conversations';
    $wpdb->insert( $table, array(
        'session_id' => $session_id,
        'user_message' => wp_json_encode( $user_message ),
        'response' => wp_json_encode( $response ),
        'created_at' => current_time( 'mysql', 1 ),
    ) );
}

/* Create DB table on activation (if not exists) */
function xilio_create_tables() {
    global $wpdb;
    $table = $wpdb->prefix . 'xilio_conversations';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        session_id VARCHAR(191) NOT NULL,
        user_message LONGTEXT,
        response LONGTEXT,
        created_at DATETIME,
        PRIMARY KEY  (id),
        KEY session_id (session_id)
    ) $charset_collate;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
register_activation_hook( __FILE__, 'xilio_create_tables' );
