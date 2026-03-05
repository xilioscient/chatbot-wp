<?php
/**
 * Pagina Impostazioni Admin
 */

if (!defined('ABSPATH')) exit;

$llm_endpoint = get_option('xsbot_llm_endpoint', 'http://127.0.0.1:5000');
$jwt_secret = get_option('xsbot_jwt_secret');
$top_k = get_option('xsbot_top_k_retrieval', 5);
$chunk_size = get_option('xsbot_chunk_size', 500);
$chunk_overlap = get_option('xsbot_chunk_overlap', 50);
$max_tokens = get_option('xsbot_max_context_tokens', 2000);
$log_conversations = get_option('xsbot_log_conversations', true);
$retention_days = get_option('xsbot_retention_days', 30);
$rate_limit_requests = get_option('xsbot_rate_limit_requests', 20);
$rate_limit_window = get_option('xsbot_rate_limit_window', 3600);
$enable_feedback = get_option('xsbot_enable_feedback', true);
$enable_file_upload = get_option('xsbot_enable_file_upload', true);
$fallback_provider = get_option('xsbot_fallback_api_provider', 'none');
$fallback_key = get_option('xsbot_fallback_api_key', '');
$widget_position = get_option('xsbot_widget_position', 'bottom-right');
$widget_theme = get_option('xsbot_widget_theme', 'light');
$welcome_message = get_option('xsbot_welcome_message');
$placeholder_text = get_option('xsbot_placeholder_text');
$anonymize_ip = get_option('xsbot_anonymize_ip', false);

/**
 * Pagina Impostazioni Admin - XilioScient Bot
 */

if (!defined('ABSPATH')) exit;

// --- 1. LOGICA DI SALVATAGGIO ---
if (isset($_POST['xsbot_save_settings']) && check_admin_referer('xsbot_settings_nonce')) {
    
    // Array di tutte le opzioni testuali/numeriche
    $options = [
        'xsbot_llm_endpoint', 'xsbot_jwt_secret', 'xsbot_top_k_retrieval',
        'xsbot_chunk_size', 'xsbot_chunk_overlap', 'xsbot_max_context_tokens',
        'xsbot_fallback_api_provider', 'xsbot_fallback_api_key', 'xsbot_widget_position',
        'xsbot_widget_theme', 'xsbot_welcome_message', 'xsbot_placeholder_text',
        'xsbot_retention_days', 'xsbot_rate_limit_requests', 'xsbot_rate_limit_window'
    ];

    foreach ($options as $opt) {
        if (isset($_POST[$opt])) {
            update_option($opt, sanitize_text_field($_POST[$opt]));
        }
    }

    // Checkbox
    update_option('xsbot_log_conversations', isset($_POST['xsbot_log_conversations']) ? 1 : 0);
    update_option('xsbot_anonymize_ip', isset($_POST['xsbot_anonymize_ip']) ? 1 : 0);
    update_option('xsbot_enable_feedback', isset($_POST['xsbot_enable_feedback']) ? 1 : 0);
    update_option('xsbot_enable_file_upload', isset($_POST['xsbot_enable_file_upload']) ? 1 : 0);

    // Gestione Immagini
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    foreach (['xsbot_profile_image', 'xsbot_banner_image'] as $img_field) {
        if (!empty($_FILES[$img_field]['name'])) {
            $attachment_id = media_handle_upload($img_field, 0);
            if (!is_wp_error($attachment_id)) {
                update_option($img_field, wp_get_attachment_url($attachment_id));
            }
        }
    }
    
    echo '<div class="updated"><p><strong>Impostazioni salvate correttamente!</strong></p></div>';
}

// --- 2. RECUPERO VALORI ---
$val = [];
$keys = [
    'xsbot_llm_endpoint', 'xsbot_jwt_secret', 'xsbot_top_k_retrieval', 'xsbot_chunk_size', 
    'xsbot_chunk_overlap', 'xsbot_max_context_tokens', 'xsbot_log_conversations', 
    'xsbot_retention_days', 'xsbot_rate_limit_requests', 'xsbot_rate_limit_window', 
    'xsbot_enable_feedback', 'xsbot_enable_file_upload', 'xsbot_fallback_api_provider', 
    'xsbot_fallback_api_key', 'xsbot_widget_position', 'xsbot_widget_theme', 
    'xsbot_welcome_message', 'xsbot_placeholder_text', 'xsbot_anonymize_ip'
];
foreach($keys as $k) $val[$k] = get_option($k);
?>

<div class="wrap xsbot-admin">
    <h1><?php _e('Impostazioni XilioScient Bot', 'xilioscient-bot'); ?></h1>
    
    <form method="post" action="" enctype="multipart/form-data">
        <?php wp_nonce_field('xsbot_settings_nonce'); ?>
        <input type="hidden" name="xsbot_save_settings" value="1">
        
        <div class="xsbot-admin-grid">
            
            <div class="xsbot-card">
                <h2><?php _e('Configurazione Servizio LLM', 'xilioscient-bot'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="xsbot_llm_endpoint"><?php _e('Endpoint LLM', 'xilioscient-bot'); ?></label></th>
                        <td><input type="url" name="xsbot_llm_endpoint" value="<?php echo esc_attr($val['xsbot_llm_endpoint']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="xsbot_jwt_secret"><?php _e('JWT Secret', 'xilioscient-bot'); ?></label></th>
                        <td><input type="password" name="xsbot_jwt_secret" value="<?php echo esc_attr($val['xsbot_jwt_secret']); ?>" class="regular-text"></td>
                    </tr>
                </table>
            </div>
            
            <div class="xsbot-card">
                <h2><?php _e('Configurazione RAG', 'xilioscient-bot'); ?></h2>
                <table class="form-table">
                    <tr><th><label>Top K</label></th><td><input type="number" name="xsbot_top_k_retrieval" value="<?php echo esc_attr($val['xsbot_top_k_retrieval'] ?: 5); ?>" class="small-text"></td></tr>
                    <tr><th><label>Chunk Size</label></th><td><input type="number" name="xsbot_chunk_size" value="<?php echo esc_attr($val['xsbot_chunk_size'] ?: 500); ?>" class="small-text"></td></tr>
                    <tr><th><label>Overlap</label></th><td><input type="number" name="xsbot_chunk_overlap" value="<?php echo esc_attr($val['xsbot_chunk_overlap'] ?: 50); ?>" class="small-text"></td></tr>
                </table>
            </div>

            <div class="xsbot-card">
                <h2><?php _e('Fallback API Cloud', 'xilioscient-bot'); ?></h2>
                <table class="form-table">
                    <tr><th><label>Provider</label></th>
                    <td>
                        <select name="xsbot_fallback_api_provider">
                            <option value="none" <?php selected($val['xsbot_fallback_api_provider'], 'none'); ?>>Nessuno</option>
                            <option value="openai" <?php selected($val['xsbot_fallback_api_provider'], 'openai'); ?>>OpenAI</option>
                            <option value="gemini" <?php selected($val['xsbot_fallback_api_provider'], 'gemini'); ?>>Gemini</option>
                        </select>
                    </td></tr>
                    <tr><th><label>API Key</label></th><td><input type="password" name="xsbot_fallback_api_key" value="<?php echo esc_attr($val['xsbot_fallback_api_key']); ?>" class="regular-text"></td></tr>
                </table>
            </div>

            <div class="xsbot-card">
                <h2><?php _e('Personalizzazione Widget', 'xilioscient-bot'); ?></h2>
                <table class="form-table">
                    <tr><th><label>Posizione</label></th><td><select name="xsbot_widget_position"><option value="bottom-right" <?php selected($val['xsbot_widget_position'], 'bottom-right'); ?>>Basso Destra</option><option value="bottom-left" <?php selected($val['xsbot_widget_position'], 'bottom-left'); ?>>Basso Sinistra</option></select></td></tr>
                    <tr><th><label>Messaggio</label></th><td><textarea name="xsbot_welcome_message" class="large-text"><?php echo esc_textarea($val['xsbot_welcome_message']); ?></textarea></td></tr>
                </table>
            </div>

            <div class="xsbot-card">
                <h2><?php _e('Privacy e Limiti', 'xilioscient-bot'); ?></h2>
                <table class="form-table">
                    <tr><th>Log Conversazioni</th><td><input type="checkbox" name="xsbot_log_conversations" value="1" <?php checked($val['xsbot_log_conversations'], 1); ?>></td></tr>
                    <tr><th>Anonimizza IP</th><td><input type="checkbox" name="xsbot_anonymize_ip" value="1" <?php checked($val['xsbot_anonymize_ip'], 1); ?>></td></tr>
                    <tr><th>Max Richieste</th><td><input type="number" name="xsbot_rate_limit_requests" value="<?php echo esc_attr($val['xsbot_rate_limit_requests'] ?: 20); ?>" class="small-text"></td></tr>
                </table>
            </div>

            <div class="xsbot-card">
                <h2><?php _e('Immagini', 'xilioscient-bot'); ?></h2>
                <table class="form-table">
                    <tr><th>Profilo</th><td><input type="file" name="xsbot_profile_image"></td></tr>
                    <tr><th>Banner</th><td><input type="file" name="xsbot_banner_image"></td></tr>
                </table>
            </div>
        </div>
        
        <?php submit_button(__('Salva Impostazioni', 'xilioscient-bot')); ?>
    </form>
</div>

<style>
.xsbot-admin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.xsbot-card {
    background: white;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    padding: 20px;
}

.xsbot-card h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

#xsbot-connection-status {
    margin-left: 10px;
    font-weight: bold;
}

#xsbot-connection-status.success {
    color: #46b450;
}

#xsbot-connection-status.error {
    color: #dc3232;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#xsbot-test-connection').on('click', function() {
        var button = $(this);
        var status = $('#xsbot-connection-status');
        
        button.prop('disabled', true).text('<?php _e('Test in corso...', 'xilioscient-bot'); ?>');
        status.removeClass('success error').text('');
        
        $.ajax({
            url: xsbotAdmin.restUrl + 'health',
            type: 'GET',
            headers: {
                'X-WP-Nonce': xsbotAdmin.nonce
            },
            success: function(response) {
                if (response.llm_service && response.llm_service.status === 'ok') {
                    status.addClass('success').text('✓ ' + xsbotAdmin.strings.testConnectionSuccess);
                } else {
                    status.addClass('error').text('✗ ' + xsbotAdmin.strings.testConnectionFailed);
                }
            },
            error: function() {
                status.addClass('error').text('✗ ' + xsbotAdmin.strings.testConnectionFailed);
            },
            complete: function() {
                button.prop('disabled', false).text('<?php _e('Testa Connessione LLM', 'xilioscient-bot'); ?>');
            }
        });
    });
});
</script>
