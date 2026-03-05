<?php
/**
 * Plugin Name: XilioScient Bot
 * Description: Chatbot AI con RAG, supporto OpenAI/Anthropic/Gemini e gestione immagini personalizzabili
 * Version: 1.0.0
 * Author: XilioScient Team
 */

if (!defined('ABSPATH')) exit;

define('XSBOT_VERSION', '1.0.0');
define('XSBOT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('XSBOT_PLUGIN_URL', plugin_dir_url(__FILE__));

class XilioScient_Bot {
    private static $instance = null;
    public static function get_instance() { if (null === self::$instance) self::$instance = new self(); return self::$instance; }
    private function __construct() { $this->load_dependencies(); $this->init_hooks(); }
    private function load_dependencies() {
        require_once XSBOT_PLUGIN_DIR . 'includes/rest-endpoints.php';
        require_once XSBOT_PLUGIN_DIR . 'includes/admin-settings.php';
        require_once XSBOT_PLUGIN_DIR . 'includes/llm-proxy.php';
        require_once XSBOT_PLUGIN_DIR . 'includes/shortcodes.php';
    }
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin'));
        add_action('rest_api_init', array('XSBOT_REST_Endpoints', 'register_routes'));
        add_action('admin_menu', array('XSBOT_Admin_Settings', 'add_menu_page'));
        add_action('init', array('XSBOT_Shortcodes', 'register'));
        add_action('wp_footer', function() { echo '<div id="xsbot-container"></div>'; });
    }
    public function activate() {
        global $wpdb; $charset = $wpdb->get_charset_collate();
        dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}xsbot_conversations (id bigint(20) NOT NULL AUTO_INCREMENT, session_id varchar(255), user_message text, bot_response text, created_at datetime DEFAULT CURRENT_TIMESTAMP, ip_address varchar(45), PRIMARY KEY (id), KEY session_id (session_id)) $charset;");
        $defaults = array('llm_endpoint' => 'http://127.0.0.1:5000', 'jwt_secret' => wp_generate_password(32, true, true), 'top_k_retrieval' => 5, 'chunk_size' => 500, 'welcome_message' => 'Ciao! Come posso aiutarti?', 'bot_name' => 'Assistente', 'fallback_api_provider' => 'none', 'widget_position' => 'bottom-right');
        foreach ($defaults as $key => $value) if (false === get_option('xsbot_' . $key)) add_option('xsbot_' . $key, $value);
        flush_rewrite_rules();
    }
    public function enqueue_public() {
        wp_enqueue_style('xsbot', XSBOT_PLUGIN_URL . 'public/style.css', array(), XSBOT_VERSION);
        wp_enqueue_script('xsbot', XSBOT_PLUGIN_URL . 'public/app.js', array('jquery'), XSBOT_VERSION, true);
        wp_localize_script('xsbot', 'xsbotData', array(
            'restUrl' => rest_url('mlc/v1/'), 'nonce' => wp_create_nonce('wp_rest'),
            'welcomeMessage' => get_option('xsbot_welcome_message'), 'placeholderText' => 'Scrivi...',
            'botName' => get_option('xsbot_bot_name'), 'position' => get_option('xsbot_widget_position'),
            'profileImage' => get_option('xsbot_profile_image'), 'bannerImage' => get_option('xsbot_banner_image'),
            'strings' => array('error' => 'Errore', 'send' => 'Invia', 'newChat' => 'Nuova Chat', 'close' => 'Chiudi')
        ));
    }
    public function enqueue_admin($hook) {
        if (strpos($hook, 'xsbot') === false) return;
        wp_enqueue_media();
        wp_enqueue_style('xsbot-admin', XSBOT_PLUGIN_URL . 'admin/admin.css', array(), XSBOT_VERSION);
        wp_enqueue_script('xsbot-admin', XSBOT_PLUGIN_URL . 'admin/admin.js', array('jquery'), XSBOT_VERSION, true);
        wp_localize_script('xsbot-admin', 'xsbotAdmin', array('restUrl' => rest_url('mlc/v1/'), 'nonce' => wp_create_nonce('wp_rest')));
    }
}
add_action('plugins_loaded', function() { XilioScient_Bot::get_instance(); });
