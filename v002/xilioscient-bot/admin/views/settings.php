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
?>

<div class="wrap xsbot-admin">
    <h1><?php _e('Impostazioni XilioScient Bot', 'xilioscient-bot'); ?></h1>
    
    <?php settings_errors('xsbot_messages'); ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('xsbot_settings_nonce'); ?>
        <input type="hidden" name="xsbot_save_settings" value="1">
        
        <div class="xsbot-admin-grid">
            
            <!-- Configurazione LLM -->
            <div class="xsbot-card">
                <h2><?php _e('Configurazione Servizio LLM', 'xilioscient-bot'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="xsbot_llm_endpoint"><?php _e('Endpoint LLM', 'xilioscient-bot'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="xsbot_llm_endpoint" name="xsbot_llm_endpoint" 
                                   value="<?php echo esc_attr($llm_endpoint); ?>" class="regular-text">
                            <p class="description"><?php _e('URL del servizio LLM locale (es. http://127.0.0.1:5000)', 'xilioscient-bot'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="xsbot_jwt_secret"><?php _e('JWT Secret', 'xilioscient-bot'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="xsbot_jwt_secret" name="xsbot_jwt_secret" 
                                   value="<?php echo esc_attr($jwt_secret); ?>" class="regular-text">
                            <button type="button" class="button" onclick="this.previousElementSibling.type=this.previousElementSibling.type==='password'?'text':'password'">
                                <?php _e('Mostra/Nascondi', 'xilioscient-bot'); ?>
                            </button>
                            <p class="description"><?php _e('Chiave segreta per autenticazione JWT (minimo 32 caratteri)', 'xilioscient-bot'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Test Connessione', 'xilioscient-bot'); ?></th>
                        <td>
                            <button type="button" id="xsbot-test-connection" class="button button-secondary">
                                <?php _e('Testa Connessione LLM', 'xilioscient-bot'); ?>
                            </button>
                            <span id="xsbot-connection-status"></span>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Configurazione RAG -->
            <div class="xsbot-card">
                <h2><?php _e('Configurazione RAG', 'xilioscient-bot'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="xsbot_top_k_retrieval"><?php _e('Top K Retrieval', 'xilioscient-bot'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="xsbot_top_k_retrieval" name="xsbot_top_k_retrieval" 
                                   value="<?php echo esc_attr($top_k); ?>" min="1" max="20" class="small-text">
                            <p class="description"><?php _e('Numero di documenti rilevanti da recuperare', 'xilioscient-bot'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="xsbot_chunk_size"><?php _e('Dimensione Chunk', 'xilioscient-bot'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="xsbot_chunk_size" name="xsbot_chunk_size" 
                                   value="<?php echo esc_attr($chunk_size); ?>" min="100" max="2000" class="small-text">
                            <p class="description"><?php _e('Dimensione chunks in caratteri', 'xilioscient-bot'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="xsbot_chunk_overlap"><?php _e('Overlap Chunk', 'xilioscient-bot'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="xsbot_chunk_overlap" name="xsbot_chunk_overlap" 
                                   value="<?php echo esc_attr($chunk_overlap); ?>" min="0" max="500" class="small-text">
                            <p class="description"><?php _e('Overlap tra chunks consecutivi', 'xilioscient-bot'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="xsbot_max_context_tokens"><?php _e('Max Context Tokens', 'xilioscient-bot'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="xsbot_max_context_tokens" name="xsbot_max_context_tokens" 
                                   value="<?php echo esc_attr($max_tokens); ?>" min="500" max="8000" class="small-text">
                            <p class="description"><?php _e('Massimo numero di token per contesto', 'xilioscient-bot'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Fallback Cloud -->
            <div class="xsbot-card">
                <h2><?php _e('Fallback API Cloud', 'xilioscient-bot'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="xsbot_fallback_api_provider"><?php _e('Provider', 'xilioscient-bot'); ?></label>
                        </th>
                        <td>
                            <select id="xsbot_fallback_api_provider" name="xsbot_fallback_api_provider">
                                <option value="none" <?php selected($fallback_provider, 'none'); ?>><?php _e('Nessuno', 'xilioscient-bot'); ?></option>
                                <option value="openai" <?php selected($fallback_provider, 'openai'); ?>>OpenAI</option>
                                <option value="anthropic" <?php selected($fallback_provider, 'anthropic'); ?>>Anthropic</option>
                            </select>
                            <p class="description"><?php _e('API cloud da usare se LLM locale non disponibile', 'xilioscient-bot'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="xsbot_fallback_api_key"><?php _e('API Key', 'xilioscient-bot'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="xsbot_fallback_api_key" name="xsbot_fallback_api_key" 
                                   value="<?php echo esc_attr($fallback_key); ?>" class="regular-text">
                            <p class="description"><?php _e('Chiave API per servizio fallback', 'xilioscient-bot'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Personalizzazione Widget -->
            <div class="xsbot-card">
                <h2><?php _e('Personalizzazione Widget', 'xilioscient-bot'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="xsbot_widget_position"><?php _e('Posizione Widget', 'xilioscient-bot'); ?></label>
                        </th>
                        <td>
                            <select id="xsbot_widget_position" name="xsbot_widget_position">
                                <option value="bottom-right" <?php selected($widget_position, 'bottom-right'); ?>><?php _e('Basso Destra', 'xilioscient-bot'); ?></option>
                                <option value="bottom-left" <?php selected($widget_position, 'bottom-left'); ?>><?php _e('Basso Sinistra', 'xilioscient-bot'); ?></option>
                                <option value="top-right" <?php selected($widget_position, 'top-right'); ?>><?php _e('Alto Destra', 'xilioscient-bot'); ?></option>
                                <option value="top-left" <?php selected($widget_position, 'top-left'); ?>><?php _e('Alto Sinistra', 'xilioscient-bot'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="xsbot_widget_theme"><?php _e('Tema Widget', 'xilioscient-bot'); ?></label>
                        </th>
                        <td>
                            <select id="xsbot_widget_theme" name="xsbot_widget_theme">
                                <option value="light" <?php selected($widget_theme, 'light'); ?>><?php _e('Chiaro', 'xilioscient-bot'); ?></option>
                                <option value="dark" <?php selected($widget_theme, 'dark'); ?>><?php _e('Scuro', 'xilioscient-bot'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="xsbot_welcome_message"><?php _e('Messaggio Benvenuto', 'xilioscient-bot'); ?></label>
                        </th>
                        <td>
                            <textarea id="xsbot_welcome_message" name="xsbot_welcome_message" rows="3" class="large-text"><?php echo esc_textarea($welcome_message); ?></textarea>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="xsbot_placeholder_text"><?php _e('Testo Placeholder', 'xilioscient-bot'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="xsbot_placeholder_text" name="xsbot_placeholder_text" 
                                   value="<?php echo esc_attr($placeholder_text); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Privacy e Logging -->
            <div class="xsbot-card">
                <h2><?php _e('Privacy e Logging', 'xilioscient-bot'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Log Conversazioni', 'xilioscient-bot'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="xsbot_log_conversations" value="1" <?php checked($log_conversations); ?>>
                                <?php _e('Salva conversazioni nel database', 'xilioscient-bot'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Anonimizza IP', 'xilioscient-bot'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="xsbot_anonymize_ip" value="1" <?php checked($anonymize_ip); ?>>
                                <?php _e('Anonimizza indirizzi IP (GDPR)', 'xilioscient-bot'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="xsbot_retention_days"><?php _e('Giorni Retention', 'xilioscient-bot'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="xsbot_retention_days" name="xsbot_retention_days" 
                                   value="<?php echo esc_attr($retention_days); ?>" min="1" max="365" class="small-text">
                            <p class="description"><?php _e('Giorni prima di eliminare conversazioni vecchie', 'xilioscient-bot'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Rate Limiting -->
            <div class="xsbot-card">
                <h2><?php _e('Rate Limiting', 'xilioscient-bot'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="xsbot_rate_limit_requests"><?php _e('Max Richieste', 'xilioscient-bot'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="xsbot_rate_limit_requests" name="xsbot_rate_limit_requests" 
                                   value="<?php echo esc_attr($rate_limit_requests); ?>" min="1" max="1000" class="small-text">
                            <p class="description"><?php _e('Numero massimo di richieste', 'xilioscient-bot'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="xsbot_rate_limit_window"><?php _e('Finestra Temporale (secondi)', 'xilioscient-bot'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="xsbot_rate_limit_window" name="xsbot_rate_limit_window" 
                                   value="<?php echo esc_attr($rate_limit_window); ?>" min="60" max="86400" class="small-text">
                            <p class="description"><?php _e('Periodo per il rate limiting (default: 3600 = 1 ora)', 'xilioscient-bot'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Funzionalità -->
            <div class="xsbot-card">
                <h2><?php _e('Funzionalità', 'xilioscient-bot'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Feedback Utenti', 'xilioscient-bot'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="xsbot_enable_feedback" value="1" <?php checked($enable_feedback); ?>>
                                <?php _e('Abilita pulsanti thumbs up/down', 'xilioscient-bot'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Caricamento File', 'xilioscient-bot'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="xsbot_enable_file_upload" value="1" <?php checked($enable_file_upload); ?>>
                                <?php _e('Permetti caricamento file nella chat', 'xilioscient-bot'); ?>
                            </label>
                        </td>
                    </tr>
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
