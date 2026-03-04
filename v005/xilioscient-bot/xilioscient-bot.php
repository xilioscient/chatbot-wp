<?php
/**
 * Plugin Name: XilioScient Bot
 * Plugin URI: https://github.com/xilioscient/chatbot-wp
 * Description: Chatbot AI locale con RAG (Retrieval Augmented Generation) per WordPress
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: XilioScient Team
 * Author URI: https://xilioscient.com
 * License: MIT
 * Text Domain: xilioscient-bot
 * Domain Path: /languages
 */

// Previeni accesso diretto
if (!defined('ABSPATH')) {
    exit;
}


$bot_config = json_decode(file_get_contents('xsbot-assets/config.json'), true);
echo "<script>var xsbotImages = " . json_encode($bot_config) . ";</script>";

// Definisci costanti del plugin
define('XSBOT_VERSION', '1.0.0');
define('XSBOT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('XSBOT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('XSBOT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Classe principale del plugin
 */
class XilioScient_Bot {
    
    private static $instance = null;
    
    /**
     * Singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Costruttore
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Carica i file necessari
     */
    private function load_dependencies() {
        require_once XSBOT_PLUGIN_DIR . 'includes/rest-endpoints.php';
        require_once XSBOT_PLUGIN_DIR . 'includes/admin-settings.php';
        require_once XSBOT_PLUGIN_DIR . 'includes/llm-proxy.php';
        require_once XSBOT_PLUGIN_DIR . 'includes/shortcodes.php';
    }
    
    /**
     * Inizializza gli hook di WordPress
     */
    private function init_hooks() {
        // Attivazione/disattivazione plugin
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Carica textdomain per traduzioni
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Enqueue scripts e styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Inizializza componenti
        add_action('rest_api_init', array('XSBOT_REST_Endpoints', 'register_routes'));
        add_action('admin_menu', array('XSBOT_Admin_Settings', 'add_menu_page'));
        add_action('init', array('XSBOT_Shortcodes', 'register'));

        add_action('wp_footer', array($this, 'render_widget_html'));
        
    }
    
    /**
     * Attivazione plugin
     */
    public function activate() {
        // Crea tabelle database se necessarie
        $this->create_tables();
        
        // Imposta opzioni default
        $defaults = array(
            'llm_endpoint' => 'http://127.0.0.1:5000',
            'jwt_secret' => wp_generate_password(32, true, true),
            'top_k_retrieval' => 5,
            'chunk_size' => 500,
            'chunk_overlap' => 50,
            'max_context_tokens' => 2000,
            'log_conversations' => true,
            'retention_days' => 30,
            'rate_limit_requests' => 20,
            'rate_limit_window' => 3600,
            'enable_feedback' => true,
            'enable_file_upload' => true,
            'fallback_api_provider' => 'none',
            'fallback_api_key' => '',
            'fallback_api_url' => '',
            'widget_position' => 'bottom-right',
            'widget_theme' => 'light',
            'welcome_message' => __('Ciao! Come posso aiutarti oggi?', 'xilioscient-bot'),
            'placeholder_text' => __('Scrivi un messaggio...', 'xilioscient-bot'),
        );
        
        foreach ($defaults as $key => $value) {
            if (false === get_option('xsbot_' . $key)) {
                add_option('xsbot_' . $key, $value);
            }
        }
        
        flush_rewrite_rules();
    }
    
    /**
     * Disattivazione plugin
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Crea tabelle database
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_conversations = $wpdb->prefix . 'xsbot_conversations';
        $table_feedback = $wpdb->prefix . 'xsbot_feedback';
        
        $sql_conversations = "CREATE TABLE IF NOT EXISTS $table_conversations (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            user_message text NOT NULL,
            bot_response text NOT NULL,
            context_used text DEFAULT NULL,
            metadata longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        $sql_feedback = "CREATE TABLE IF NOT EXISTS $table_feedback (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            conversation_id bigint(20) NOT NULL,
            feedback_type varchar(20) NOT NULL,
            comment text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY conversation_id (conversation_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_conversations);
        dbDelta($sql_feedback);
    }
    
    /**
     * Carica textdomain per traduzioni
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'xilioscient-bot',
            false,
            dirname(XSBOT_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Enqueue assets pubblici
     */
    public function enqueue_public_assets() {
        wp_enqueue_style(
            'xsbot-public',
            XSBOT_PLUGIN_URL . 'public/style.css',
            array(),
            XSBOT_VERSION
        );
        
        wp_enqueue_script(
            'xsbot-public',
            XSBOT_PLUGIN_URL . 'public/app.js',
            array('jquery'),
            XSBOT_VERSION,
            true
        );
        
        wp_localize_script('xsbot-public', 'xsbotData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('mlc/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'welcomeMessage' => get_option('xsbot_welcome_message'),
            'placeholderText' => get_option('xsbot_placeholder_text'),
            'position' => get_option('xsbot_widget_position'),
            'theme' => get_option('xsbot_widget_theme'),
            'enableFeedback' => get_option('xsbot_enable_feedback'),
            'enableFileUpload' => get_option('xsbot_enable_file_upload'),
            'strings' => array(
                'thinking' => __('Sto pensando...', 'xilioscient-bot'),
                'error' => __('Si è verificato un errore. Riprova.', 'xilioscient-bot'),
                'rateLimitExceeded' => __('Troppe richieste. Riprova tra qualche minuto.', 'xilioscient-bot'),
                'networkError' => __('Errore di connessione. Verifica la tua connessione internet.', 'xilioscient-bot'),
                'feedbackThanks' => __('Grazie per il tuo feedback!', 'xilioscient-bot'),
                'send' => __('Invia', 'xilioscient-bot'),
                'newChat' => __('Nuova chat', 'xilioscient-bot'),
                'close' => __('Chiudi', 'xilioscient-bot'),
            )
        ));
    }


    public function render_widget_html() {
        echo '<div id="xsbot-container"></div>';
    }


    /**
     * Enqueue assets admin
     */
    public function enqueue_admin_assets($hook) {
        // Carichiamo solo se siamo in una pagina del plugin
        if (strpos($hook, 'xsbot') === false) {
            return;
        }

        wp_enqueue_style(
            'xsbot-admin',
            XSBOT_PLUGIN_URL . 'admin/admin.css',
            array(),
            XSBOT_VERSION
        );

        wp_enqueue_script(
            'xsbot-admin',
            XSBOT_PLUGIN_URL . 'admin/admin.js',
            array('jquery'),
            XSBOT_VERSION,
            true
        );

        wp_localize_script('xsbot-admin', 'xsbotAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('mlc/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'strings' => array(
                'confirmDelete' => __('Sei sicuro di voler eliminare questi elementi?', 'xilioscient-bot'),
                'uploadSuccess' => __('File caricato con successo', 'xilioscient-bot'),
                'uploadError' => __('Errore durante il caricamento', 'xilioscient-bot'),
                'indexingStarted' => __('Indicizzazione avviata', 'xilioscient-bot'),
                'indexingComplete' => __('Indicizzazione completata', 'xilioscient-bot'),
                'testConnectionSuccess' => __('Connessione riuscita!', 'xilioscient-bot'),
                'testConnectionFailed' => __('Connessione fallita', 'xilioscient-bot'),
            )
        ));
    }
} 

/**
 * Inizializza il plugin
 */
function xsbot_init() {
    $file_path = get_stylesheet_directory() . 'xsbot-assets/config.json';
    if (file_exists($file_path)) {
        $bot_config = json_decode(file_get_contents($file_path), true);
        echo "<script>var xsbotImages = " . json_encode($bot_config) . ";</script>";
    }
    return XilioScient_Bot::get_instance();
}

// Avvia il plugin
add_action('plugins_loaded', 'xsbot_init');
