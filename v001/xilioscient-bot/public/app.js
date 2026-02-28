/* Vanilla JS widget: gestione sessione, invio messaggi, streaming SSE fallback */
(function(){
  const restUrl = (typeof XILIO_CONFIG !== 'undefined') ? XILIO_CONFIG.rest_url : '/wp-json/mlc/v1/message';
  const nonce = (typeof XILIO_CONFIG !== 'undefined') ? XILIO_CONFIG.nonce : '';

  function $(id){ return document.getElementById(id); }

  function createMessageElement(text, cls){
    const div = document.createElement('div');
    div.className = 'xilio-msg ' + cls;
    const bubble = document.createElement('div');
    bubble.className = 'bubble';
    bubble.textContent = text;
    div.appendChild(bubble);
    return div;
  }

  function appendBotMessage(text){
    const container = $('xilio-messages');
    const el = createMessageElement(text, 'bot');
    container.appendChild(el);
    container.scrollTop = container.scrollHeight;
  }

  function appendUserMessage(text){
    const container = $('xilio-messages');
    const el = createMessageElement(text, 'user');
    container.appendChild(el);
    container.scrollTop = container.scrollHeight;
  }

  function showTyping(){
    const container = $('xilio-messages');
    const el = document.createElement('div');
    el.className = 'xilio-typing';
    el.id = 'xilio-typing';
    el.textContent = 'Xilio sta scrivendo...';
    container.appendChild(el);
    container.scrollTop = container.scrollHeight;
  }
  function hideTyping(){
    const el = $('xilio-typing');
    if (el) el.remove();
  }

  function getSessionId(){
    let sid = localStorage.getItem('xilio_session_id');
    if (!sid){
      sid = 'sess-' + Math.random().toString(36).substr(2,9);
      localStorage.setItem('xilio_session_id', sid);
    }
    return sid;
  }

  function sendMessage(message){
    const payload = {
      message: message,
      session_id: getSessionId(),
      metadata: { user_agent: navigator.userAgent }
    };
    showTyping();
    fetch(restUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce
      },
      body: JSON.stringify(payload)
    }).then(resp => resp.json()).then(data => {
      hideTyping();
      if (data.error){
        appendBotMessage('Errore: ' + data.error);
      } else if (data.reply){
        appendBotMessage(data.reply);
      } else if (data.stream_url){
        // handle streaming via SSE
        consumeStream(data.stream_url);
      } else {
        appendBotMessage(JSON.stringify(data));
      }
    }).catch(err => {
      hideTyping();
      appendBotMessage('Errore di rete. Riprova piÃ¹ tardi.');
      console.error(err);
    });
  }

  function consumeStream(url){
    const evtSource = new EventSource(url);
    let buffer = '';
    evtSource.onmessage = function(e){
      buffer += e.data;
      // update last bot bubble
      const container = $('xilio-messages');
      let last = container.querySelector('.xilio-msg.bot .bubble:last-child');
      if (!last){
        const el = createMessageElement('', 'bot');
        container.appendChild(el);
        last = el.querySelector('.bubble');
      }
      last.textContent = buffer;
      container.scrollTop = container.scrollHeight;
    };
    evtSource.onerror = function(){
      evtSource.close();
    };
  }

  document.addEventListener('DOMContentLoaded', function(){
    const sendBtn = $('xilio-send');
    const input = $('xilio-input');
    const toggle = $('xilio-toggle');
    const widget = document.getElementById('xilio-chat-widget');

    sendBtn.addEventListener('click', function(){
      const text = input.value.trim();
      if (!text) return;
      appendUserMessage(text);
      input.value = '';
      sendMessage(text);
    });

    input.addEventListener('keydown', function(e){
      if (e.key === 'Enter' && !e.shiftKey){
        e.preventDefault();
        sendBtn.click();
      }
    });

    toggle.addEventListener('click', function(){
      widget.style.display = (widget.style.display === 'none') ? 'flex' : 'none';
    });
  });
})();
