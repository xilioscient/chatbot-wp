<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
    <h1><span class="dashicons dashicons-database"></span> Knowledge Base</h1>
    <p>I file qui sotto sono salvati sul server e sincronizzati automaticamente.</p>

    <div class="card" style="padding: 20px; max-width: 600px;">
        <input type="file" id="xsbot-file-input" />
        <button id="xsbot-upload-btn" class="button button-primary">Carica Documento</button>
        <div id="xsbot-status" style="margin-top: 10px;"></div>
    </div>

    <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
        <thead>
            <tr>
                <th>Nome Documento</th>
                <th>Data</th>
                <th>Stato Google</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $docs = get_option('xsbot_knowledge_base', []);
            if (empty($docs)): ?>
                <tr><td colspan="4">Nessun documento caricato.</td></tr>
            <?php else: foreach (array_reverse($docs) as $doc): ?>
                <tr>
                    <td><strong><?php echo esc_html($doc['name']); ?></strong></td>
                    <td><?php echo esc_html($doc['date']); ?></td>
                    <td>
                        <?php if (time() < $doc['expires']): ?>
                            <span style="color: green;">● Attivo</span>
                        <?php else: ?>
                            <span style="color: orange;">● Scaduto (verrà rinnovato)</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="button xsbot-delete-file" data-id="<?php echo $doc['id']; ?>">Elimina</button>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($) {
    // Upload
    $('#xsbot-upload-btn').on('click', function() {
        const file = $('#xsbot-file-input')[0].files[0];
        if(!file) return alert('Seleziona un file');
        
        const fd = new FormData();
        fd.append('action', 'xsbot_upload_knowledge_file');
        fd.append('file', file);
        fd.append('_ajax_nonce', '<?php echo wp_create_nonce("xsbot_upload_nonce"); ?>');

        $('#xsbot-status').text('Caricamento...');
        $.ajax({
            url: ajaxurl, type: 'POST', data: fd, processData: false, contentType: false,
            success: (res) => { if(res.success) location.reload(); else alert(res.data); }
        });
    });

    // Delete
    $('.xsbot-delete-file').on('click', function() {
        if(!confirm('Eliminare definitivamente?')) return;
        $.post(ajaxurl, {
            action: 'xsbot_delete_knowledge_file',
            id: $(this).data('id'),
            _ajax_nonce: '<?php echo wp_create_nonce("xsbot_delete_nonce"); ?>'
        }, () => location.reload());
    });
});
</script>