/**
 * XilioScient Bot - Admin JavaScript
 */

(function($) {
    'use strict';

    const XSBotAdmin = {
        
        init: function() {
            this.bindEvents();
            this.initComponents();
        },

        bindEvents: function() {
            // Upload documenti
            $('#xsbot-upload-doc').on('click', this.uploadDocument);
            
            // Elimina documento
            $(document).on('click', '.xsbot-delete-doc', this.deleteDocument);
            
            // Indicizza documenti
            $('#xsbot-reindex').on('click', this.reindexDocuments);
            
            // Esporta conversazioni
            $('#xsbot-export-conversations').on('click', this.exportConversations);
            
            // Pulisci conversazioni vecchie
            $('#xsbot-cleanup-conversations').on('click', this.cleanupConversations);
        },

        initComponents: function() {
            // Inizializza grafici se disponibili
            if (typeof Chart !== 'undefined') {
                this.initCharts();
            }
            
            // Carica statistiche
            this.loadStats();
            
            // Carica documenti
            if ($('#xsbot-documents-list').length) {
                this.loadDocuments();
            }
        },

        uploadDocument: function(e) {
            e.preventDefault();
            
            const fileInput = $('#xsbot-file-input')[0];
            const file = fileInput.files[0];
            
            if (!file) {
                alert('Seleziona un file');
                return;
            }
            
            // Verifica tipo file
            const allowedTypes = ['application/pdf', 'text/html', 'text/markdown', 'text/plain', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            if (!allowedTypes.includes(file.type)) {
                alert('Tipo file non supportato');
                return;
            }
            
            const formData = new FormData();
            formData.append('file', file);
            
            const button = $(this);
            button.prop('disabled', true).text('Caricamento...');
            
            $.ajax({
                url: xsbotAdmin.restUrl + 'admin/upload',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-WP-Nonce': xsbotAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(xsbotAdmin.strings.uploadSuccess);
                        fileInput.value = '';
                        XSBotAdmin.loadDocuments();
                    } else {
                        alert(xsbotAdmin.strings.uploadError);
                    }
                },
                error: function(xhr) {
                    console.error('Upload error:', xhr);
                    alert(xsbotAdmin.strings.uploadError);
                },
                complete: function() {
                    button.prop('disabled', false).text('Carica Documento');
                }
            });
        },

        deleteDocument: function(e) {
            e.preventDefault();
            
            if (!confirm(xsbotAdmin.strings.confirmDelete)) {
                return;
            }
            
            const docId = $(this).data('doc-id');
            
            $.ajax({
                url: xsbotAdmin.restUrl + 'admin/documents/' + docId,
                type: 'DELETE',
                headers: {
                    'X-WP-Nonce': xsbotAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        XSBotAdmin.loadDocuments();
                    }
                },
                error: function(xhr) {
                    console.error('Delete error:', xhr);
                    alert('Errore eliminazione documento');
                }
            });
        },

        loadDocuments: function() {
            const container = $('#xsbot-documents-list');
            container.html('<p>Caricamento...</p>');
            
            $.ajax({
                url: xsbotAdmin.restUrl + 'admin/documents',
                type: 'GET',
                headers: {
                    'X-WP-Nonce': xsbotAdmin.nonce
                },
                success: function(response) {
                    if (response.documents && response.documents.length > 0) {
                        let html = '<table class="wp-list-table widefat fixed striped">';
                        html += '<thead><tr>';
                        html += '<th>Nome File</th>';
                        html += '<th>Chunks</th>';
                        html += '<th>Data Caricamento</th>';
                        html += '<th>Azioni</th>';
                        html += '</tr></thead><tbody>';
                        
                        response.documents.forEach(function(doc) {
                            html += '<tr>';
                            html += '<td>' + doc.file_name + '</td>';
                            html += '<td>' + doc.chunk_count + '</td>';
                            html += '<td>' + new Date(doc.created_at).toLocaleString('it-IT') + '</td>';
                            html += '<td>';
                            html += '<button class="button xsbot-delete-doc" data-doc-id="' + doc.document_id + '">Elimina</button>';
                            html += '</td>';
                            html += '</tr>';
                        });
                        
                        html += '</tbody></table>';
                        container.html(html);
                    } else {
                        container.html('<p>Nessun documento indicizzato</p>');
                    }
                },
                error: function(xhr) {
                    console.error('Load documents error:', xhr);
                    container.html('<p>Errore caricamento documenti</p>');
                }
            });
        },

        loadStats: function() {
            $.ajax({
                url: xsbotAdmin.restUrl + 'admin/stats',
                type: 'GET',
                headers: {
                    'X-WP-Nonce': xsbotAdmin.nonce
                },
                success: function(response) {
                    // Aggiorna contatori
                    $('#xsbot-total-conversations').text(response.total_conversations || 0);
                    $('#xsbot-today-conversations').text(response.today_conversations || 0);
                    $('#xsbot-week-conversations').text(response.week_conversations || 0);
                    $('#xsbot-unique-sessions').text(response.unique_sessions || 0);
                    $('#xsbot-positive-feedback').text(response.positive_feedback || 0);
                    $('#xsbot-negative-feedback').text(response.negative_feedback || 0);
                    
                    // Calcola percentuale feedback positivo
                    const totalFeedback = (response.positive_feedback || 0) + (response.negative_feedback || 0);
                    if (totalFeedback > 0) {
                        const percentage = Math.round((response.positive_feedback / totalFeedback) * 100);
                        $('#xsbot-feedback-percentage').text(percentage + '%');
                    }
                },
                error: function(xhr) {
                    console.error('Load stats error:', xhr);
                }
            });
        },

        initCharts: function() {
            // Grafico conversazioni giornaliere
            const ctx = document.getElementById('xsbot-daily-chart');
            if (!ctx) return;
            
            $.ajax({
                url: xsbotAdmin.restUrl + 'admin/stats',
                type: 'GET',
                headers: {
                    'X-WP-Nonce': xsbotAdmin.nonce
                },
                success: function(response) {
                    const dailyStats = response.daily_stats || [];
                    
                    const labels = dailyStats.map(s => s.date).reverse();
                    const data = dailyStats.map(s => s.count).reverse();
                    
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Conversazioni',
                                data: data,
                                borderColor: 'rgb(102, 126, 234)',
                                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                                tension: 0.3,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });
                }
            });
        },

        exportConversations: function(e) {
            e.preventDefault();
            
            const startDate = $('#xsbot-export-start').val();
            const endDate = $('#xsbot-export-end').val();
            
            const url = xsbotAdmin.ajaxUrl + '?action=xsbot_export_conversations&start=' + startDate + '&end=' + endDate + '&nonce=' + xsbotAdmin.nonce;
            
            window.location.href = url;
        },

        cleanupConversations: function(e) {
            e.preventDefault();
            
            if (!confirm('Eliminare tutte le conversazioni più vecchie di ' + $('#xsbot-cleanup-days').val() + ' giorni?')) {
                return;
            }
            
            const days = $('#xsbot-cleanup-days').val();
            
            $.ajax({
                url: xsbotAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'xsbot_cleanup_conversations',
                    days: days,
                    nonce: xsbotAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Conversazioni eliminate: ' + response.data.deleted);
                        XSBotAdmin.loadStats();
                    }
                },
                error: function(xhr) {
                    console.error('Cleanup error:', xhr);
                    alert('Errore durante la pulizia');
                }
            });
        },

        reindexDocuments: function(e) {
            e.preventDefault();
            
            if (!confirm('Ricostruire l\'indice di tutti i documenti? Questa operazione può richiedere tempo.')) {
                return;
            }
            
            const button = $(this);
            button.prop('disabled', true).text('Indicizzazione in corso...');
            
            $.ajax({
                url: xsbotAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'xsbot_reindex_documents',
                    nonce: xsbotAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(xsbotAdmin.strings.indexingComplete);
                    } else {
                        alert('Errore durante l\'indicizzazione');
                    }
                },
                error: function(xhr) {
                    console.error('Reindex error:', xhr);
                    alert('Errore durante l\'indicizzazione');
                },
                complete: function() {
                    button.prop('disabled', false).text('Reindicizza Tutti i Documenti');
                }
            });
        }
    };

    // Inizializza quando il DOM è pronto
    $(document).ready(function() {
        XSBotAdmin.init();
    });

})(jQuery);
