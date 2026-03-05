



(function($) {
    'use strict';

    // Configurazione iniziale
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
        quickReplies: [
            "Quali sono i vostri servizi?",
            "Come posso contattare il supporto?",
            "Voglio richiedere un preventivo"
        ]
    };

    const bannerImg = typeof window.bannerImg !== 'undefined' ? window.bannerImg : '';
    const profileImg = typeof window.profileImg !== 'undefined' ? window.profileImg : '';

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
                this.renderQuickReplies();
            }
        }

                createFloatingWidget() {
               
                const bannerUrl = '/../xilioscient-bot/xsbot-assets/banner.jpg';
                const profileUrl = '/../xsbot-assets/propic.png';

                const html = `
                    <div class="xsbot-floating-container xsbot-theme-${botConfig.theme}" data-position="${botConfig.position}">
                        <button class="xsbot-toggle-btn" id="xsbot-toggle" aria-label="${botConfig.strings.newChat}">
                            <svg class="xsbot-icon-chat" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                            <svg class="xsbot-icon-close" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                        <div class="xsbot-widget" id="xsbot-widget">
                            <div class="xsbot-header-container" style="background-image: url('${bannerUrl}'); background-size: cover; background-position: center;">
                                <div class="xsbot-header-top">
                                    <div class="xsbot-header-content">
                                        <div class="xsbot-avatar"><img src="${profileUrl}" alt="Supporto" style="width:36px; height:36px; border-radius:50%; object-fit:cover;"></div>
                                        <div class="xsbot-header-text"><h3>Team di Supporto</h3><span class="xsbot-status">Risponde subito</span></div>
                                    </div>
                                    <div class="xsbot-header-actions">
                                        <button class="xsbot-action-btn" id="xsbot-new-chat" title="${botConfig.strings.newChat}"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg></button>
                                        <button class="xsbot-action-btn" id="xsbot-close" title="${botConfig.strings.close}"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button>
                                    </div>
                                </div>
                                <div class="xsbot-banner-intro"><h2>Ciao, sono Xilio 👋</h2><p>Troviamo insieme la soluzione migliore.</p></div>
                            </div>
                            <div class="xsbot-messages" id="xsbot-messages"></div>
                            <div class="xsbot-quick-replies" id="xsbot-quick-replies"></div>
                            <div class="xsbot-input-area">
                                <textarea class="xsbot-input" id="xsbot-input" placeholder="${botConfig.placeholderText}" rows="1"></textarea>
                                <button class="xsbot-send-btn" id="xsbot-send" aria-label="${botConfig.strings.send}"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg></button>
                            </div>
                            <div class="xsbot-footer" style="text-align: center; font-size: 10px; margin-top: 5px; padding-bottom: 5px;">
                                Made by <a href="https://gonet.it" target="_blank" style="color: purple; text-decoration: none;">goNet.it</a>
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
                if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); self.processInput(); }
            });
            $(document).on('click', '.xsbot-quick-reply-btn', function() {
                const text = $(this).text();
                self.sendMessage(text);
                $('#xsbot-quick-replies').hide();
            });
        }

        toggleWidget() { this.isOpen ? this.closeWidget() : this.openWidget(); }
        openWidget() { $('#xsbot-widget').addClass('xsbot-open'); $('#xsbot-toggle').addClass('xsbot-active'); this.isOpen = true; setTimeout(() => $('#xsbot-input').focus(), 300); this.scrollToBottom(); }
        closeWidget() { $('#xsbot-widget').removeClass('xsbot-open'); $('#xsbot-toggle').removeClass('xsbot-active'); this.isOpen = false; }

        newChat() {
            if (confirm('Iniziare una nuova conversazione?')) {
                this.sessionId = this.generateSessionId();
                this.saveSessionId();
                this.messages = [];
                $('#xsbot-messages').empty();
                this.addMessage(botConfig.welcomeMessage, 'bot');
                this.renderQuickReplies();
            }
        }

        processInput() {
            const $input = $('#xsbot-input');
            const message = $input.val().trim();
            if (!message || this.isTyping) return;
            $input.val('').css('height', 'auto');
            $('#xsbot-quick-replies').hide();
            this.sendMessage(message);
        }

        async sendMessage(message) {
            this.addMessage(message, 'user');
            this.showTyping();
            try {
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
            const response = await fetch(botConfig.restUrl + 'message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': botConfig.nonce },
                body: JSON.stringify({ message: message, session_id: this.sessionId })
            });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return await response.json();
        }

        addMessage(text, type, save = true) {
            const $messages = $('#xsbot-messages');
            const time = new Date().toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
            
            const $msg = $('<div>', { class: `xsbot-message xsbot-message-${type}` });
            const $content = $('<div>', { class: 'xsbot-message-content' });
            $content.append($('<div>', { class: 'xsbot-message-text' }).text(text)); // Sicuro da XSS
            $content.append($('<div>', { class: 'xsbot-message-time' }).text(time));
            $msg.append($content);
            $messages.append($msg);
            
            if (save) {
                this.messages.push({ text, type });
                this.saveHistory();
            }
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
                try {
                    const parsed = JSON.parse(history);
                    this.messages = parsed;
                    $('#xsbot-messages').empty();
                    this.messages.forEach(m => this.addMessage(m.text, m.type, false)); // false = non risalvare
                    $('#xsbot-quick-replies').hide();
                } catch (e) { console.error("Error loading history", e); }
            }
        }
        saveHistory() { localStorage.setItem('xsbot_history_' + this.sessionId, JSON.stringify(this.messages.slice(-50))); }
    }

    // Caricamento sicuro dell'istanza
    $(window).on('load', function() {
        window.xsbot = new XSBot();
    });

})(jQuery);