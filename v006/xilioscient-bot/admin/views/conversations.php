<?php
/**
 * Vista Conversazioni - Log delle chat
 */
if (!defined('ABSPATH')) exit;
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Log Conversazioni', 'xilioscient-bot'); ?></h1>
    <hr class="wp-header-end">

    <p><?php _e('Qui puoi monitorare le interazioni in tempo reale tra gli utenti e il bot.', 'xilioscient-bot'); ?></p>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 15%;"><?php _e('Data/Ora', 'xilioscient-bot'); ?></th>
                <th style="width: 15%;"><?php _e('Utente (IP)', 'xilioscient-bot'); ?></th>
                <th><?php _e('Messaggio Utente', 'xilioscient-bot'); ?></th>
                <th><?php _e('Risposta AI', 'xilioscient-bot'); ?></th>
                <th style="width: 10%;"><?php _e('Provider', 'xilioscient-bot'); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="5" style="text-align: center; padding: 20px;">
                    <?php _e('Nessuna conversazione registrata al momento.', 'xilioscient-bot'); ?>
                </td>
            </tr>
        </tbody>
    </table>
</div>