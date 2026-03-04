<?php
/**
 * Admin Settings
 * Pagina amministrazione plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class XSBOT_Admin_Settings {
    
    /**
     * Aggiungi pagina menu
     */
    public static function add_menu_page() {
        add_menu_page(
            __('XilioScient Bot', 'xilioscient-bot'),
            __('XilioScient Bot', 'xilioscient-bot'),
            'manage_options',
            'xsbot-settings',
            array(__CLASS__, 'render_settings_page'),
            'dashicons-format-chat',
            30
        );
        
        add_submenu_page(
            'xsbot-settings',
            __('Impostazioni', 'xilioscient-bot'),
            __('Impostazioni', 'xilioscient-bot'),
            'manage_options',
            'xsbot-settings',
            array(__CLASS__, 'render_settings_page')
        );
        
        add_submenu_page(
            'xsbot-settings',
            __('Knowledge Base', 'xilioscient-bot'),
            __('Knowledge Base', 'xilioscient-bot'),
            'manage_options',
            'xsbot-knowledge-base',
            array(__CLASS__, 'render_kb_page')
        );
        
        add_submenu_page(
            'xsbot-settings',
            __('Conversazioni', 'xilioscient-bot'),
            __('Conversazioni', 'xilioscient-bot'),
            'manage_options',
            'xsbot-conversations',
            array(__CLASS__, 'render_conversations_page')
        );
        
        add_submenu_page(
            'xsbot-settings',
            __('Statistiche', 'xilioscient-bot'),
            __('Statistiche', 'xilioscient-bot'),
            'manage_options',
            'xsbot-stats',
            array(__CLASS__, 'render_stats_page')
        );
    }
    
    /**
     * Render pagina impostazioni
     */
    public static function render_settings_page() {
        if (isset($_POST['xsbot_save_settings'])) {
            check_admin_referer('xsbot_settings_nonce');
            self::save_settings();
        }
        
        include XSBOT_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    /**
     * Render pagina knowledge base
     */
    public static function render_kb_page() {
        include XSBOT_PLUGIN_DIR . 'admin/views/knowledge-base.php';
    }
    
    /**
     * Render pagina conversazioni
     */
    public static function render_conversations_page() {
        include XSBOT_PLUGIN_DIR . 'admin/views/conversations.php';
    }
    
    /**
     * Render pagina statistiche
     */
    public static function render_stats_page() {
        include XSBOT_PLUGIN_DIR . 'admin/views/stats.php';
    }
    
    /**
     * Salva impostazioni
     */
    private static function save_settings() {
        $settings = array(
            'llm_endpoint',
            'jwt_secret',
            'top_k_retrieval',
            'chunk_size',
            'chunk_overlap',
            'max_context_tokens',
            'log_conversations',
            'retention_days',
            'rate_limit_requests',
            'rate_limit_window',
            'enable_feedback',
            'enable_file_upload',
            'fallback_api_provider',
            'fallback_api_key',
            'fallback_api_url',
            'widget_position',
            'widget_theme',
            'welcome_message',
            'placeholder_text',
            'anonymize_ip',
        );
        
        foreach ($settings as $setting) {
            if (isset($_POST["xsbot_$setting"])) {
                $value = $_POST["xsbot_$setting"];
                
                // Sanitizzazione
                if (in_array($setting, array('llm_endpoint', 'fallback_api_url'))) {
                    $value = esc_url_raw($value);
                } elseif (in_array($setting, array('jwt_secret', 'fallback_api_key'))) {
                    $value = sanitize_text_field($value);
                } elseif (in_array($setting, array('welcome_message', 'placeholder_text'))) {
                    $value = sanitize_textarea_field($value);
                } elseif (in_array($setting, array('log_conversations', 'enable_feedback', 'enable_file_upload', 'anonymize_ip'))) {
                    $value = (bool) $value;
                } else {
                    $value = intval($value);
                }
                
                update_option("xsbot_$setting", $value);
            }
        }
        
        add_settings_error('xsbot_messages', 'xsbot_message', __('Impostazioni salvate', 'xilioscient-bot'), 'success');
    }
}
