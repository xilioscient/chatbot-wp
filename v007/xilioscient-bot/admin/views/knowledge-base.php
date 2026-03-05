<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1><?php _e('Knowledge Base', 'xilioscient-bot'); ?></h1>

    <div class="card">
        <h2><?php _e('Carica Documento', 'xilioscient-bot'); ?></h2>
        <input type="file" id="xsbot-file-input" />
        <button id="xsbot-upload-doc" class="button button-primary"><?php _e('Carica Documento', 'xilioscient-bot'); ?></button>
        <button id="xsbot-reindex" class="button"><?php _e('Reindicizza Tutto', 'xilioscient-bot'); ?></button>
    </div>

    <hr>

    <h2><?php _e('Documenti Indicizzati', 'xilioscient-bot'); ?></h2>
    <div id="xsbot-documents-list">
        <p><?php _e('Caricamento documenti...', 'xilioscient-bot'); ?></p>
    </div>
</div>