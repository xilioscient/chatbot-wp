/**
 * XilioScient Bot - Frontend JavaScript (Stile Elementor)
 */

(function($) {
    'use strict';

    // Fallback sicuro se xsbotData non è iniettato da WordPress
    const botConfig = typeof xsbotData !== 'undefined' ? xsbotData : {
        autoLoad: true,
        position: 'bottom-right',
        theme: 'light',
        welcomeMessage: "Ciao! Come posso aiutarti oggi?",
        placeholderText: "Scrivi la tua domanda...",
        restUrl: '/wp-json/mlc/v1/',
        nonce: '',
        enableFeedback: false,
        strings: { newChat: "Nuova Chat", close: "Chiudi", send: "Invia", error: "Errore imprevisto", networkError: "Errore di rete" },
        // Le 3 domande preimpostate
        quickReplies: [
            "Quali sono i vostri servizi?",
            "Come posso contattare il supporto?",
            "Voglio richiedere un preventivo"
        ]
    };

    class XSBot {
        constructor() {
            this.sessionId = this.getSessionId();
            this.isOpen = false;
            this.messages = [];
            this.isTyping = false;
            
            this.init();
        }

        init() {
            if ($('#xsbot-widget').length === 0 && botConfig.autoLoad !== false) {
                this.createFloatingWidget();
            }
            this.bindEvents();
            this.loadHistory();
            
            if (this.messages.length === 0) {
                this.addMessage(botConfig.welcomeMessage, 'bot');
                this.renderQuickReplies(); // Mostra le opzioni veloci all'inizio
            }
        }

        createFloatingWidget() {
            const html = `
                <div class="xsbot-floating-container xsbot-theme-${botConfig.theme}" data-position="${botConfig.position}">
                    <button class="xsbot-toggle-btn" id="xsbot-toggle" aria-label="${botConfig.strings.newChat}">
                        <svg class="xsbot-icon-chat" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <svg class="xsbot-icon-close" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                    <div class="xsbot-widget" id="xsbot-widget">
                        <div class="xsbot-header-container">
                            <div class="xsbot-header-top">
                                <div class="xsbot-header-content">
                                    <div class="xsbot-avatar">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
                                        </svg>
                                    </div>
                                    <div class="xsbot-header-text">
                                        <h3>Team di Supporto</h3>
                                        <span class="xsbot-status">Risponde subito</span>
                                    </div>
                                </div>
                                <div class="xsbot-header-actions">
                                    <button class="xsbot-action-btn" id="xsbot-new-chat" title="${botConfig.strings.newChat}">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                    </button>
                                    <button class="xsbot-action-btn" id="xsbot-close" title="${botConfig.strings.close}">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                    </button>
                                </div>
                            </div>
                            <div class="xsbot-banner-intro">
                                <h2>Ciao, sono Xilio 👋</h2>
                                <p>Troviamo insieme la soluzione migliore per le tue esigenze.</p>
                            </div>
                        </div>

                        <div class="xsbot-messages" id="xsbot-messages"></div>
                        
                        <div class="xsbot-quick-replies" id="xsbot-quick-replies"></div>

                        <div class="xsbot-input-area">
                            <textarea class="xsbot-input" id="xsbot-input" placeholder="${botConfig.placeholderText}" rows="1"></textarea>
                            <button class="xsbot-send-btn" id="xsbot-send" aria-label="${botConfig.strings.send}">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            $('body').append(html);
        }

        renderQuickReplies() {
            const $container = $('#xsbot-quick-replies');
            $container.empty();
            
            if (botConfig.quickReplies && botConfig.quickReplies.length > 0) {
                botConfig.quickReplies.forEach(reply => {
                    $container.append(`<button class="xsbot-quick-reply-btn">${reply}</button>`);
                });
                $container.show();
            }
        }

        bindEvents() {
            const self = this;
            
            $(document).on('click', '#xsbot-toggle', () => self.toggleWidget());
            $(document).on('click', '#xsbot-close', () => self.closeWidget());
            $(document).on('click', '#xsbot-new-chat', () => self.newChat());
            $(document).on('click', '#xsbot-send', () => self.processInput());
            
            $(document).on('keydown', '#xsbot-input', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    self.processInput();
                }
            });

            // Gestione click sulle quick replies
            $(document).on('click', '.xsbot-quick-reply-btn', function() {
                const text = $(this).text();
                self.sendMessage(text);
                $('#xsbot-quick-replies').hide(); // Nascondi dopo l'uso
            });
        }

        toggleWidget() { this.isOpen ? this.closeWidget() : this.openWidget(); }
        openWidget() { 
            $('#xsbot-widget').addClass('xsbot-open'); 
            $('#xsbot-toggle').addClass('xsbot-active'); 
            this.isOpen = true; 
            setTimeout(() => $('#xsbot-input').focus(), 300);
            this.scrollToBottom();
        }
        closeWidget() { 
            $('#xsbot-widget').removeClass('xsbot-open'); 
            $('#xsbot-toggle').removeClass('xsbot-active'); 
            this.isOpen = false; 
        }

        newChat() {
            if (confirm('Iniziare una nuova conversazione?')) {
                this.sessionId = this.generateSessionId();
                this.saveSessionId();
                this.messages = [];
                $('#xsbot-messages').empty();
                this.addMessage(botConfig.welcomeMessage, 'bot');
                this.renderQuickReplies(); // Riporta le quick replies
            }
        }

        processInput() {
            const $input = $('#xsbot-input');
            const message = $input.val().trim();
            if (!message || this.isTyping) return;
            
            $input.val('').css('height', 'auto');
            $('#xsbot-quick-replies').hide(); // Rimuovi suggerimenti se l'utente scrive
            this.sendMessage(message);
        }

        async sendMessage(message) {
            this.addMessage(message, 'user');
            this.showTyping();
            
            try {
                // Mock o chiamata reale API
                const response = await this.callAPI(message);
                this.hideTyping();
                if (response.success || response.reply) {
                    this.addMessage(response.reply || response.response, 'bot');
                } else {
                    this.addMessage(response.error || botConfig.strings.error, 'error');
                }
            } catch (error) {
                this.hideTyping();
                this.addMessage(botConfig.strings.networkError, 'error');
            }
        }

        async callAPI(message) {
            // Se non hai ancora un backend, scommenta questa riga per simulare la risposta:
            // return new Promise(resolve => setTimeout(() => resolve({success: true, reply: "Questa è una risposta di test SaaS."}), 1000));

            const response = await fetch(botConfig.restUrl + 'message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': botConfig.nonce },
                body: JSON.stringify({ message: message, session_id: this.sessionId })
            });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return await response.json();
        }

        addMessage(text, type) {
            const $messages = $('#xsbot-messages');
            const time = new Date().toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
            
            const html = `
                <div class="xsbot-message xsbot-message-${type}">
                    <div class="xsbot-message-content">
                        <div class="xsbot-message-text">${text}</div>
                        <div class="xsbot-message-time">${time}</div>
                    </div>
                </div>
            `;
            $messages.append(html);
            this.messages.push({ text, type });
            this.saveHistory();
            this.scrollToBottom();
        }

        showTyping() {
            this.isTyping = true;
            $('#xsbot-messages').append(`
                <div class="xsbot-message xsbot-message-bot xsbot-typing" id="xsbot-typing">
                    <div class="xsbot-message-content">
                        <div class="xsbot-typing-indicator"><span></span><span></span><span></span></div>
                    </div>
                </div>
            `);
            this.scrollToBottom();
        }

        hideTyping() { this.isTyping = false; $('#xsbot-typing').remove(); }
        scrollToBottom() { const $m = $('#xsbot-messages'); $m.animate({ scrollTop: $m[0].scrollHeight }, 300); }
        
        getSessionId() {
            let sid = localStorage.getItem('xsbot_session_id');
            if (!sid) { sid = this.generateSessionId(); this.saveSessionId(); }
            return sid;
        }
        generateSessionId() { return 'xsbot_' + Date.now(); }
        saveSessionId() { localStorage.setItem('xsbot_session_id', this.sessionId); }
        
        loadHistory() {
            const history = localStorage.getItem('xsbot_history_' + this.sessionId);
            if (history) {
                this.messages = JSON.parse(history);
                $('#xsbot-messages').empty();
                this.messages.forEach(m => this.addMessage(m.text, m.type));
                $('#xsbot-quick-replies').hide(); // Se c'è cronologia, nascondi i bottoni iniziali
            }
        }
        saveHistory() { localStorage.setItem('xsbot_history_' + this.sessionId, JSON.stringify(this.messages.slice(-50))); }
    }

    $(document).ready(function() { window.xsbot = new XSBot(); });
})(jQuery);