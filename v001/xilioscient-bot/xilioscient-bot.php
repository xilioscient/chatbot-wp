<?php
/**
 * Plugin Name: XilioScient Bot
 * Plugin URI:  https://example.com/xilioscient-bot
 * Description: Chatbot AI locale con RAG per WordPress. Widget front-end, pagina admin per gestione KB e proxy verso servizio LLM locale.
 * Version:     0.1.0
 * Author:      XilioScient
 * Author URI:  https://example.com
 * Text Domain: xilioscient-bot
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'XILIO_DIR', plugin_dir_path( __FILE__ ) );
define( 'XILIO_URL', plugin_dir_url( __FILE__ ) );
define( 'XILIO_VERSION', '0.1.0' );

/* Load translations */
function xilio_load_textdomain() {
    load_plugin_textdomain( 'xilioscient-bot', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'xilio_load_textdomain' );

/* Include files */
require_once XILIO_DIR . 'includes/rest-endpoints.php';
require_once XILIO_DIR . 'includes/admin-settings.php';
require_once XILIO_DIR . 'includes/llm-proxy.php';
require_once XILIO_DIR . 'includes/shortcodes.php';

/* Enqueue public assets */
function xilio_enqueue_assets() {
    wp_enqueue_style( 'xilio-style', XILIO_URL . 'public/style.css', array(), XILIO_VERSION );
    wp_enqueue_script( 'xilio-app', XILIO_URL . 'public/app.js', array(), XILIO_VERSION, true );
    wp_localize_script( 'xilio-app', 'XILIO_CONFIG', array(
        'rest_url' => esc_url_raw( rest_url( 'mlc/v1/message' ) ),
        'nonce'    => wp_create_nonce( 'wp_rest' ),
    ) );
}
add_action( 'wp_enqueue_scripts', 'xilio_enqueue_assets' );

/* Enqueue admin assets */
function xilio_admin_assets( $hook ) {
    if ( strpos( $hook, 'xilioscient-bot' ) === false ) {
        return;
    }
    wp_enqueue_style( 'xilio-admin-style', XILIO_URL . 'admin/admin.css', array(), XILIO_VERSION );
    wp_enqueue_script( 'xilio-admin-js', XILIO_URL . 'admin/admin.js', array('jquery'), XILIO_VERSION, true );
    wp_localize_script( 'xilio-admin-js', 'XILIO_ADMIN', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'xilio_admin' ),
    ) );
}
add_action( 'admin_enqueue_scripts', 'xilio_admin_assets' );

/* Activation / Deactivation hooks */
function xilio_activate() {
    // default options
    $defaults = array(
        'llm_endpoint' => 'http://127.0.0.1:5000',
        'jwt_secret' => '',
        'top_k' => 5,
        'chunk_size' => 500,
        'overlap' => 50,
        'fallback_api' => '',
        'log_conversations' => 0,
        'retention_days' => 30,
    );
    add_option( 'xilio_options', $defaults );
}
register_activation_hook( __FILE__, 'xilio_activate' );

function xilio_deactivate() {
    // keep data for GDPR; admin can delete
}
register_deactivation_hook( __FILE__, 'xilio_deactivate' );
