<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* Shortcode [xilio_chat] to render chat widget */
add_shortcode( 'xilio_chat', 'xilio_render_chat_widget' );

function xilio_render_chat_widget( $atts = array() ) {
    $atts = shortcode_atts( array(
        'title' => __( 'Chat con XilioScient', 'xilioscient-bot' ),
    ), $atts, 'xilio_chat' );

    ob_start();
    ?>
    <div id="xilio-chat-widget" class="xilio-widget">
        <div class="xilio-header">
            <strong><?php echo esc_html( $atts['title'] ); ?></strong>
            <button id="xilio-toggle" aria-label="<?php esc_attr_e( 'Apri chat', 'xilioscient-bot' ); ?>">âœ•</button>
        </div>
        <div class="xilio-messages" id="xilio-messages" role="log" aria-live="polite"></div>
        <div class="xilio-input">
            <textarea id="xilio-input" placeholder="<?php esc_attr_e( 'Scrivi un messaggio...', 'xilioscient-bot' ); ?>"></textarea>
            <button id="xilio-send" class="button"><?php esc_html_e( 'Invia', 'xilioscient-bot' ); ?></button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
