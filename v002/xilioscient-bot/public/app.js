/**
 * XilioScient Bot - Frontend JavaScript
 * Gestisce l'interfaccia utente del chatbot
 */

(function($) {
    'use strict';

    class XSBot {
        constructor() {
            this.sessionId = this.getSessionId();
            this.isOpen = false;
            this.messages = [];
            this.isTyping = false;
            
            this.init();
        }

        init() {
            // Aggiungi widget se non esiste
            if ($('#xsbot-widget').length === 0 && xsbotData.autoLoad !== false) {
                this.createFloatingWidget();
            }
            
            this.bindEvents();
            this.loadHistory();
            
            // Mostra messaggio di benvenuto
            if (this.messages.length === 0) {
                this.addMessage(xsbotData.welcomeMessage, 'bot');
            }
        }

        createFloatingWidget() {
            const position = xsbotData.position || 'bottom-right';
            const theme = xsbotData.theme || 'light';
            
            const html = `
                <div class="xsbot-floating-container xsbot-theme-${theme}" data-position="${position}">
                    <button class="xsbot-toggle-btn" id="xsbot-toggle" aria-label="${xsbotData.strings.newChat}">
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
                            <div class="xsbot-header-content">
                                <div class="xsbot-avatar">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                                    </svg>
                                </div>
                                <div class="xsbot-header-text">
                                    <h3>Assistente AI</h3>
                                    <span class="xsbot-status">Online</span>
                                </div>
                            </div>
                            <div class="xsbot-header-actions">
                                <button class="xsbot-action-btn" id="xsbot-new-chat" title="${xsbotData.strings.newChat}">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                </button>
                                <button class="xsbot-action-btn" id="xsbot-close" title="${xsbotData.strings.close}">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="xsbot-messages" id="xsbot-messages"></div>
                        <div class="xsbot-input-area">
                            <textarea 
                                class="xsbot-input" 
                                id="xsbot-input"
                                placeholder="${xsbotData.placeholderText}"
                                rows="1"></textarea>
                            <button class="xsbot-send-btn" id="xsbot-send" aria-label="${xsbotData.strings.send}">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="22" y1="2" x2="11" y2="13"></line>
                                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(html);
        }

        bindEvents() {
            const self = this;
            
            // Toggle widget
            $(document).on('click', '#xsbot-toggle', function() {
                self.toggleWidget();
            });
            
            // Chiudi widget
            $(document).on('click', '#xsbot-close', function() {
                self.closeWidget();
            });
            
            // Nuova chat
            $(document).on('click', '#xsbot-new-chat', function() {
                self.newChat();
            });
            
            // Invia messaggio
            $(document).on('click', '#xsbot-send', function() {
                self.sendMessage();
            });
            
            // Invia con Enter (Shift+Enter per nuova riga)
            $(document).on('keydown', '#xsbot-input', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    self.sendMessage();
                }
            });
            
            // Auto-resize textarea
            $(document).on('input', '#xsbot-input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });
            
            // Feedback
            $(document).on('click', '.xsbot-feedback-btn', function() {
                const conversationId = $(this).data('conversation-id');
                const type = $(this).data('type');
                self.sendFeedback(conversationId, type);
            });
        }

        toggleWidget() {
            if (this.isOpen) {
                this.closeWidget();
            } else {
                this.openWidget();
            }
        }

        openWidget() {
            $('#xsbot-widget').addClass('xsbot-open');
            $('#xsbot-toggle').addClass('xsbot-active');
            this.isOpen = true;
            
            // Focus su input
            setTimeout(() => {
                $('#xsbot-input').focus();
            }, 300);
            
            this.scrollToBottom();
        }

        closeWidget() {
            $('#xsbot-widget').removeClass('xsbot-open');
            $('#xsbot-toggle').removeClass('xsbot-active');
            this.isOpen = false;
        }

        newChat() {
            if (confirm('Iniziare una nuova conversazione? La cronologia attuale sarà salvata.')) {
                this.sessionId = this.generateSessionId();
                this.saveSessionId();
                this.messages = [];
                $('#xsbot-messages').empty();
                this.addMessage(xsbotData.welcomeMessage, 'bot');
            }
        }

        async sendMessage() {
            const $input = $('#xsbot-input');
            const message = $input.val().trim();
            
            if (!message || this.isTyping) {
                return;
            }
            
            // Aggiungi messaggio utente
            this.addMessage(message, 'user');
            $input.val('').css('height', 'auto');
            
            // Mostra typing indicator
            this.showTyping();
            
            try {
                const response = await this.callAPI(message);
                
                this.hideTyping();
                
                if (response.success) {
                    this.addMessage(response.response, 'bot', {
                        conversationId: response.conversation_id,
                        sources: response.sources,
                        confidence: response.confidence
                    });
                } else {
                    this.addMessage(response.error || xsbotData.strings.error, 'error');
                }
            } catch (error) {
                this.hideTyping();
                console.error('Error:', error);
                this.addMessage(xsbotData.strings.networkError, 'error');
            }
        }

        async callAPI(message) {
            const response = await fetch(xsbotData.restUrl + 'message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': xsbotData.nonce
                },
                body: JSON.stringify({
                    message: message,
                    session_id: this.sessionId,
                    metadata: {
                        user_agent: navigator.userAgent,
                        page_url: window.location.href
                    }
                })
            });
            
            if (!response.ok) {
                if (response.status === 429) {
                    throw new Error(xsbotData.strings.rateLimitExceeded);
                }
                throw new Error('HTTP ' + response.status);
            }
            
            return await response.json();
        }

        addMessage(text, type, metadata = {}) {
            const $messages = $('#xsbot-messages');
            const timestamp = new Date().toLocaleTimeString('it-IT', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            let messageHtml = `
                <div class="xsbot-message xsbot-message-${type}">
                    <div class="xsbot-message-content">
                        <div class="xsbot-message-text">${this.formatMessage(text)}</div>
                        <div class="xsbot-message-time">${timestamp}</div>
            `;
            
            // Aggiungi sources se presenti
            if (metadata.sources && metadata.sources.length > 0) {
                messageHtml += '<div class="xsbot-sources"><strong>Fonti:</strong> ';
                metadata.sources.forEach((source, i) => {
                    if (i > 0) messageHtml += ', ';
                    messageHtml += `<a href="${source.url}" target="_blank">${source.title}</a>`;
                });
                messageHtml += '</div>';
            }
            
            // Aggiungi feedback buttons
            if (type === 'bot' && xsbotData.enableFeedback && metadata.conversationId) {
                messageHtml += `
                    <div class="xsbot-feedback">
                        <button class="xsbot-feedback-btn" data-conversation-id="${metadata.conversationId}" data-type="positive" title="Utile">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path>
                            </svg>
                        </button>
                        <button class="xsbot-feedback-btn" data-conversation-id="${metadata.conversationId}" data-type="negative" title="Non utile">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h2.67A2.31 2.31 0 0 1 22 4v7a2.31 2.31 0 0 1-2.33 2H17"></path>
                            </svg>
                        </button>
                    </div>
                `;
            }
            
            messageHtml += '</div></div>';
            
            $messages.append(messageHtml);
            this.messages.push({ text, type, timestamp: Date.now(), metadata });
            this.saveHistory();
            this.scrollToBottom();
        }

        formatMessage(text) {
            // Escape HTML
            text = $('<div>').text(text).html();
            
            // Converti link
            text = text.replace(
                /((https?:\/\/)?[\w-]+(\.[\w-]+)+\.?(:\d+)?(\/\S*)?)/gi,
                '<a href="$1" target="_blank" rel="noopener">$1</a>'
            );
            
            // Converti newlines
            text = text.replace(/\n/g, '<br>');
            
            // Markdown bold
            text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
            
            // Markdown italic
            text = text.replace(/\*(.+?)\*/g, '<em>$1</em>');
            
            return text;
        }

        showTyping() {
            this.isTyping = true;
            const $messages = $('#xsbot-messages');
            $messages.append(`
                <div class="xsbot-message xsbot-message-bot xsbot-typing" id="xsbot-typing">
                    <div class="xsbot-message-content">
                        <div class="xsbot-typing-indicator">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            `);
            this.scrollToBottom();
        }

        hideTyping() {
            this.isTyping = false;
            $('#xsbot-typing').remove();
        }

        async sendFeedback(conversationId, type) {
            try {
                const response = await fetch(xsbotData.restUrl + 'feedback', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': xsbotData.nonce
                    },
                    body: JSON.stringify({
                        conversation_id: conversationId,
                        feedback_type: type
                    })
                });
                
                if (response.ok) {
                    // Mostra conferma visiva
                    $(`.xsbot-feedback-btn[data-conversation-id="${conversationId}"]`)
                        .parent()
                        .html('<span class="xsbot-feedback-thanks">' + xsbotData.strings.feedbackThanks + '</span>');
                }
            } catch (error) {
                console.error('Feedback error:', error);
            }
        }

        scrollToBottom() {
            const $messages = $('#xsbot-messages');
            $messages.animate({
                scrollTop: $messages[0].scrollHeight
            }, 300);
        }

        getSessionId() {
            let sessionId = localStorage.getItem('xsbot_session_id');
            if (!sessionId) {
                sessionId = this.generateSessionId();
                this.saveSessionId();
            }
            return sessionId;
        }

        generateSessionId() {
            return 'xsbot_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        }

        saveSessionId() {
            localStorage.setItem('xsbot_session_id', this.sessionId);
        }

        loadHistory() {
            try {
                const history = localStorage.getItem('xsbot_history_' + this.sessionId);
                if (history) {
                    this.messages = JSON.parse(history);
                    this.renderMessages();
                }
            } catch (e) {
                console.error('Error loading history:', e);
            }
        }

        saveHistory() {
            try {
                // Mantieni solo ultimi 50 messaggi
                const recentMessages = this.messages.slice(-50);
                localStorage.setItem(
                    'xsbot_history_' + this.sessionId,
                    JSON.stringify(recentMessages)
                );
            } catch (e) {
                console.error('Error saving history:', e);
            }
        }

        renderMessages() {
            const $messages = $('#xsbot-messages');
            $messages.empty();
            
            this.messages.forEach(msg => {
                this.addMessage(msg.text, msg.type, msg.metadata || {});
            });
        }
    }

    // Inizializza quando il DOM è pronto
    $(document).ready(function() {
        window.xsbot = new XSBot();
    });

})(jQuery);
