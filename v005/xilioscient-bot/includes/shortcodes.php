<?php
/**
 * Shortcodes
 * Gestisce gli shortcode per il widget chat
 */

if (!defined('ABSPATH')) {
    exit;
}

class XSBOT_Shortcodes {
    
    /**
     * Registra shortcodes
     */
    public static function register() {
        add_shortcode('xsbot_chat', array(__CLASS__, 'render_chat_widget'));
        add_shortcode('xsbot_button', array(__CLASS__, 'render_chat_button'));
    }
    
    /**
     * Render widget chat inline
     */
    public static function render_chat_widget($atts) {
        $atts = shortcode_atts(array(
            'theme' => get_option('xsbot_widget_theme', 'light'),
            'height' => '600px',
            'width' => '100%',
        ), $atts);
        
        $session_id = self::get_or_create_session();
        
        ob_start();
        ?>
        <div class="xsbot-inline-widget" 
             data-theme="<?php echo esc_attr($atts['theme']); ?>"
             data-session="<?php echo esc_attr($session_id); ?>"
             style="height: <?php echo esc_attr($atts['height']); ?>; width: <?php echo esc_attr($atts['width']); ?>;">
            <div class="xsbot-chat-container">
                <div class="xsbot-messages" id="xsbot-messages"></div>
                <div class="xsbot-input-container">
                    <textarea 
                        class="xsbot-input" 
                        id="xsbot-input"
                        placeholder="<?php echo esc_attr(get_option('xsbot_placeholder_text')); ?>"
                        rows="1"></textarea>
                    <button class="xsbot-send-btn" id="xsbot-send">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render pulsante chat floating
     */
    public static function render_chat_button($atts) {
        $atts = shortcode_atts(array(
            'position' => get_option('xsbot_widget_position', 'bottom-right'),
            'text' => __('Hai bisogno di aiuto?', 'xilioscient-bot'),
        ), $atts);
        
        $session_id = self::get_or_create_session();
        
        ob_start();
        ?>
        <div class="xsbot-floating-button" 
             data-position="<?php echo esc_attr($atts['position']); ?>"
             data-session="<?php echo esc_attr($session_id); ?>">
            <button class="xsbot-toggle-btn" id="xsbot-toggle">
                <svg class="xsbot-icon-chat" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <svg class="xsbot-icon-close" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            <div class="xsbot-widget" id="xsbot-widget">
                <div class="xsbot-header">
                    <h3><?php _e('Chat Assistente', 'xilioscient-bot'); ?></h3>
                    <button class="xsbot-minimize" id="xsbot-minimize">−</button>
                </div>
                <div class="xsbot-chat-container">
                    <div class="xsbot-messages" id="xsbot-messages-float"></div>
                    <div class="xsbot-input-container">
                        <textarea 
                            class="xsbot-input" 
                            id="xsbot-input-float"
                            placeholder="<?php echo esc_attr(get_option('xsbot_placeholder_text')); ?>"
                            rows="1"></textarea>
                        <button class="xsbot-send-btn" id="xsbot-send-float">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Ottieni o crea session ID
     */
    private static function get_or_create_session() {
        if (!session_id()) {
            session_start();
        }
        
        if (!isset($_SESSION['xsbot_session_id'])) {
            $_SESSION['xsbot_session_id'] = wp_generate_password(32, false);
        }
        
        return $_SESSION['xsbot_session_id'];
    }
}
