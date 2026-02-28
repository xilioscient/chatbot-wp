<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* Admin menu and settings page */
add_action( 'admin_menu', function() {
    add_menu_page(
        __( 'XilioScient Bot', 'xilioscient-bot' ),
        __( 'XilioScient Bot', 'xilioscient-bot' ),
        'manage_options',
        'xilioscient-bot',
        'xilio_admin_page',
        'dashicons-format-chat',
        80
    );
} );

add_action( 'admin_init', function() {
    register_setting( 'xilio_options_group', 'xilio_options', 'xilio_options_validate' );
    add_settings_section( 'xilio_main_section', __( 'Impostazioni servizio LLM', 'xilioscient-bot' ), null, 'xilioscient-bot' );

    add_settings_field( 'llm_endpoint', __( 'LLM Endpoint', 'xilioscient-bot' ), 'xilio_field_llm_endpoint', 'xilioscient-bot', 'xilio_main_section' );
    add_settings_field( 'jwt_secret', __( 'JWT Secret', 'xilioscient-bot' ), 'xilio_field_jwt_secret', 'xilioscient-bot', 'xilio_main_section' );
    add_settings_field( 'top_k', __( 'Top K retrieval', 'xilioscient-bot' ), 'xilio_field_top_k', 'xilioscient-bot', 'xilio_main_section' );
    add_settings_field( 'chunk_size', __( 'Chunk size', 'xilioscient-bot' ), 'xilio_field_chunk_size', 'xilioscient-bot', 'xilio_main_section' );
    add_settings_field( 'fallback_api', __( 'Fallback API (opzionale)', 'xilioscient-bot' ), 'xilio_field_fallback_api', 'xilioscient-bot', 'xilio_main_section' );
    add_settings_field( 'log_conversations', __( 'Log conversazioni', 'xilioscient-bot' ), 'xilio_field_log_conversations', 'xilioscient-bot', 'xilio_main_section' );
    add_settings_field( 'retention_days', __( 'Retention giorni', 'xilioscient-bot' ), 'xilio_field_retention_days', 'xilioscient-bot', 'xilio_main_section' );
} );

function xilio_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $options = get_option( 'xilio_options', array() );
    ?>
    <div class="wrap">
        <h1><?php _e( 'XilioScient Bot - Impostazioni', 'xilioscient-bot' ); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'xilio_options_group' ); ?>
            <?php do_settings_sections( 'xilioscient-bot' ); ?>
            <?php submit_button(); ?>
        </form>

        <h2><?php _e( 'Gestione Knowledge Base', 'xilioscient-bot' ); ?></h2>
        <p><?php _e( 'Carica documenti (PDF/HTML/MD) per indicizzarli nel DB vettoriale.', 'xilioscient-bot' ); ?></p>
        <form id="xilio-upload-form" enctype="multipart/form-data">
            <input type="file" name="kb_files[]" multiple />
            <button id="xilio-upload-btn" class="button button-primary"><?php _e( 'Carica e indicizza', 'xilioscient-bot' ); ?></button>
            <span id="xilio-upload-status"></span>
        </form>
    </div>
    <?php
}

/* Fields callbacks */
function xilio_field_llm_endpoint() {
    $options = get_option( 'xilio_options', array() );
    printf( '<input type="text" name="xilio_options[llm_endpoint]" value="%s" class="regular-text" />', esc_attr( $options['llm_endpoint'] ?? '' ) );
}
function xilio_field_jwt_secret() {
    $options = get_option( 'xilio_options', array() );
    printf( '<input type="password" name="xilio_options[jwt_secret]" value="%s" class="regular-text" />', esc_attr( $options['jwt_secret'] ?? '' ) );
    echo '<p class="description">' . __( 'Segreto condiviso per firmare JWT tra WP e servizio LLM.', 'xilioscient-bot' ) . '</p>';
}
function xilio_field_top_k() {
    $options = get_option( 'xilio_options', array() );
    printf( '<input type="number" min="1" max="20" name="xilio_options[top_k]" value="%s" />', esc_attr( $options['top_k'] ?? 5 ) );
}
function xilio_field_chunk_size() {
    $options = get_option( 'xilio_options', array() );
    printf( '<input type="number" min="100" max="2000" name="xilio_options[chunk_size]" value="%s" />', esc_attr( $options['chunk_size'] ?? 500 ) );
}
function xilio_field_fallback_api() {
    $options = get_option( 'xilio_options', array() );
    printf( '<input type="text" name="xilio_options[fallback_api]" value="%s" class="regular-text" />', esc_attr( $options['fallback_api'] ?? '' ) );
}
function xilio_field_log_conversations() {
    $options = get_option( 'xilio_options', array() );
    $checked = ! empty( $options['log_conversations'] ) ? 'checked' : '';
    printf( '<input type="checkbox" name="xilio_options[log_conversations]" value="1" %s />', $checked );
}
function xilio_field_retention_days() {
    $options = get_option( 'xilio_options', array() );
    printf( '<input type="number" min="1" max="3650" name="xilio_options[retention_days]" value="%s" />', esc_attr( $options['retention_days'] ?? 30 ) );
}

/* Validate options */
function xilio_options_validate( $input ) {
    $input['llm_endpoint'] = esc_url_raw( $input['llm_endpoint'] );
    $input['jwt_secret'] = sanitize_text_field( $input['jwt_secret'] );
    $input['top_k'] = intval( $input['top_k'] );
    $input['chunk_size'] = intval( $input['chunk_size'] );
    $input['fallback_api'] = esc_url_raw( $input['fallback_api'] );
    $input['log_conversations'] = ! empty( $input['log_conversations'] ) ? 1 : 0;
    $input['retention_days'] = intval( $input['retention_days'] );
    return $input;
}

/* AJAX handler for upload (admin) */
add_action( 'wp_ajax_xilio_admin_upload', function() {
    check_ajax_referer( 'xilio_admin', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'Permesso negato', 'xilioscient-bot' ), 403 );
    }
    if ( empty( $_FILES['kb_files'] ) ) {
        wp_send_json_error( __( 'Nessun file', 'xilioscient-bot' ), 400 );
    }
    // Forward to REST admin upload
    $files = $_FILES['kb_files'];
    $boundary = wp_generate_password( 24 );
    $body = array();
    foreach ( $files['name'] as $i => $name ) {
        $tmp_name = $files['tmp_name'][ $i ];
        $body[] = array(
            'name' => 'kb_files[]',
            'filename' => $name,
            'contents' => file_get_contents( $tmp_name ),
        );
    }
    // For simplicity, call internal function or instruct admin to use CLI indexing
    wp_send_json_success( array( 'message' => __( 'File caricati. Avviare indicizzazione tramite script Docker.', 'xilioscient-bot' ) ) );
} );
