# 🎉 XilioScient Bot - Plugin WordPress Completo Generato!

## ✅ Cosa è Stato Generato

### 📦 Plugin WordPress (`xilioscient-bot/`)
- ✅ **xilioscient-bot.php** - File principale plugin con bootstrap
- ✅ **includes/rest-endpoints.php** - REST API per chat e admin
- ✅ **includes/llm-proxy.php** - Proxy comunicazione con LLM
- ✅ **includes/admin-settings.php** - Gestione pagine admin
- ✅ **includes/shortcodes.php** - Shortcode per widget
- ✅ **public/app.js** - JavaScript widget chat (7.8KB)
- ✅ **public/style.css** - CSS professionale responsive (6.2KB)
- ✅ **admin/admin.js** - JavaScript amministrazione
- ✅ **admin/admin.css** - CSS pannello admin
- ✅ **admin/views/settings.php** - Pagina impostazioni completa
- ✅ **languages/xilioscient-bot.pot** - File traduzioni i18n
- ✅ **LICENSE** - Licenza MIT
- ✅ **CHANGELOG.md** - Registro versioni

### 🐳 Servizio LLM Docker (`docker/`)
- ✅ **Dockerfile** - Container Python FastAPI
- ✅ **docker-compose.yml** - Orchestrazione servizi
- ✅ **requirements.txt** - Dipendenze Python
- ✅ **app/main.py** - FastAPI application con RAG
- ✅ **app/embeddings.py** - Servizio embeddings
- ✅ **app/retrieval.py** - FAISS + SQLite retrieval
- ✅ **app/model_inference.py** - LLM inference
- ✅ **app/document_processor.py** - Estrazione e chunking
- ✅ **app/__init__.py** - Package Python

### 📚 Documentazione (`docs/`)
- ✅ **PROMPTS.md** - 5+ template prompt professionali

### 🛠️ Script (`scripts/`)
- ✅ **install.sh** - Script installazione automatica

### 📖 Altro
- ✅ **README.md** - Documentazione completa (14KB)

---

## 🚀 Installazione Rapida (3 Comandi)

```bash
# 1. Rendi eseguibile lo script
chmod +x scripts/install.sh

# 2. Esegui installazione
./scripts/install.sh

# 3. Copia plugin in WordPress
cp -r xilioscient-bot /path/to/wordpress/wp-content/plugins/
```

---

## 📋 Setup Passo-Passo

### 1️⃣ Avvia Servizio LLM

```bash
cd docker/

# Scarica modello (esempio Mistral 7B, 4GB)
mkdir -p data/models
cd data/models
wget https://huggingface.co/TheBloke/Mistral-7B-Instruct-v0.2-GGUF/resolve/main/mistral-7b-instruct-v0.2.Q4_K_M.gguf -O model.gguf
cd ../..

# Avvia container
docker-compose up -d

# Verifica
curl http://localhost:5000/api/health
```

### 2️⃣ Installa Plugin WordPress

```bash
# Copia nella directory plugins
cp -r xilioscient-bot /path/to/wordpress/wp-content/plugins/

# Oppure comprimi e carica via admin
cd xilioscient-bot/
zip -r ../xilioscient-bot.zip .
# Carica ZIP da WordPress Admin → Plugin → Aggiungi nuovo
```

### 3️⃣ Configura Plugin

1. Attiva plugin: **WordPress Admin → Plugin → XilioScient Bot → Attiva**

2. Vai a: **XilioScient Bot → Impostazioni**

3. Configura:
   - **Endpoint LLM**: `http://127.0.0.1:5000`
   - **JWT Secret**: genera con `openssl rand -base64 32`
   - **Top K**: `5`
   - **Chunk Size**: `500`
   - **Max Tokens**: `2000`

4. Clicca **"Testa Connessione LLM"** - deve essere ✅

5. Salva impostazioni

### 4️⃣ Carica Documenti

1. **XilioScient Bot → Knowledge Base**
2. Carica PDF, HTML, MD o DOCX
3. Attendi indicizzazione
4. I documenti saranno usati per rispondere

### 5️⃣ Verifica Widget

Il widget appare automaticamente su tutte le pagine!

O usa shortcode:
```html
[xsbot_chat]
```

---

## 🎨 Caratteristiche Implementate

### ✅ Frontend
- Widget chat floating con animazioni fluide
- Design mobile-first responsive
- Tema chiaro/scuro
- Typing indicators
- Sessioni persistenti (localStorage)
- Feedback thumbs up/down
- Citations con sources
- Auto-resize textarea
- Smooth scrolling

### ✅ Backend WordPress
- REST API con namespace `mlc/v1`
- JWT authentication
- Rate limiting (transients)
- Input sanitization
- Anti-prompt-injection filters
- CSRF protection
- Logging conversazioni
- Anonimizzazione IP (GDPR)
- Database tables automatiche

### ✅ Servizio LLM
- FastAPI con async
- RAG con FAISS + SQLite
- Embeddings multilingue (Sentence Transformers)
- Chunking semantico con overlap
- Top-K retrieval
- Confidence scoring
- Fallback cloud (OpenAI/Anthropic)
- Health check endpoint
- JWT verification
- Document processing (PDF/HTML/MD/DOCX)

### ✅ Admin Dashboard
- Statistiche real-time
- Gestione documenti
- Visualizzazione conversazioni
- Health monitoring
- Test connessione LLM
- Export/import dati

---

## 🔧 Struttura File Completa

```
xilioscient-bot/
├── xilioscient-bot.php          # Main plugin file
├── includes/
│   ├── rest-endpoints.php       # REST API
│   ├── llm-proxy.php            # LLM communication
│   ├── admin-settings.php       # Admin pages
│   └── shortcodes.php           # Shortcodes
├── public/
│   ├── app.js                   # Frontend JS
│   └── style.css                # Frontend CSS
├── admin/
│   ├── admin.js                 # Admin JS
│   ├── admin.css                # Admin CSS
│   └── views/
│       └── settings.php         # Settings page
├── languages/
│   └── xilioscient-bot.pot      # Translations
├── LICENSE
└── CHANGELOG.md

docker/
├── Dockerfile
├── docker-compose.yml
├── requirements.txt
├── .env                         # Create this
└── app/
    ├── __init__.py
    ├── main.py                  # FastAPI app
    ├── embeddings.py            # Embeddings service
    ├── retrieval.py             # FAISS+SQLite
    ├── model_inference.py       # LLM inference
    └── document_processor.py    # Document processing

docs/
└── PROMPTS.md                   # Prompt templates

scripts/
└── install.sh                   # Install script

README.md                        # Full documentation
```

---

## 🧪 Testing

### Test Plugin
```bash
# In WordPress
1. Attiva plugin
2. Controlla errori: WordPress Admin → Strumenti → Salute sito
3. Verifica endpoint: /wp-json/mlc/v1/health
```

### Test LLM Service
```bash
# Health check
curl http://localhost:5000/api/health

# Test chat (sostituisci JWT)
curl -X POST http://localhost:5000/api/chat \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "message": "Ciao, come funziona il sistema?",
    "session_id": "test123",
    "top_k": 5
  }'
```

### Test Widget
1. Apri homepage sito
2. Clicca pulsante chat (basso-destra)
3. Invia messaggio "Test"
4. Verifica risposta entro 5-10 secondi

---

## ⚠️ Note Importanti

### Modello LLM
Il sistema è pronto ma **RICHIEDE UN MODELLO**:

**Opzione 1 - Mistral 7B (Raccomandato)**:
```bash
cd docker/data/models/
wget https://huggingface.co/TheBloke/Mistral-7B-Instruct-v0.2-GGUF/resolve/main/mistral-7b-instruct-v0.2.Q4_K_M.gguf -O model.gguf
```

**Opzione 2 - Usa Fallback Cloud**:
- Configura OpenAI/Anthropic API key in impostazioni
- Sistema userà cloud quando locale non disponibile

### Requisiti Hardware

**Minimo** (con Q4 quantized):
- 2 CPU cores
- 4GB RAM
- 10GB storage

**Raccomandato** (performance ottimale):
- 4+ CPU cores
- 8GB+ RAM
- GPU con 8GB+ VRAM (opzionale ma velocizza 10-100x)

### Sicurezza

🔒 **IMPORTANTE**:
1. Genera JWT Secret forte: `openssl rand -base64 32`
2. Usa HTTPS in produzione
3. Blocca porta 5000 dal web (firewall)
4. Aggiorna regolarmente: `git pull && docker-compose pull`

---

## 📚 Documentazione

### README Principale
Leggi `README.md` per:
- Guida installazione dettagliata
- Configurazione avanzata
- Troubleshooting
- Best practices sicurezza
- Deployment produzione

### Prompt Templates
Leggi `docs/PROMPTS.md` per:
- 5+ template prompt professionali
- Best practices RAG
- Esempi ottimizzazione risposte
- Testing prompts

---

## 🐛 Troubleshooting Veloce

### Widget non appare
```bash
# Verifica JavaScript caricato
# Browser → Ispeziona → Console → cerca errori

# Verifica nonce
# Dovrebbe essere in window.xsbotData

# Disabilita cache plugin temporaneamente
```

### LLM non risponde
```bash
# Verifica container running
docker-compose ps

# Verifica logs
docker-compose logs -f llm-service

# Riavvia
docker-compose restart llm-service
```

### "Rate limit exceeded"
```bash
# Aumenta limiti in WordPress Admin → Impostazioni
# O attendi 1 ora per reset automatico
```

---

## 🎯 Prossimi Passi

1. ✅ Installa usando `./scripts/install.sh`
2. ✅ Verifica health checks
3. ✅ Carica 2-3 documenti di test
4. ✅ Testa widget sul sito
5. ✅ Personalizza messaggi/colori
6. ✅ Configura backup (opzionale)
7. ✅ Metti in produzione!

---

## 💡 Tips Utili

### Performance
- Usa modelli Q4 quantized per bilanciare qualità/velocità
- Abilita GPU se disponibile
- Chunk size ottimale: 400-600 caratteri
- Top-K retrieval: 3-7 documenti

### UX
- Messaggio benvenuto chiaro e amichevole
- Testa widget su mobile
- Configura tema che matcha il tuo sito
- Abilita feedback per migliorare risposte

### Contenuti
- Carica FAQ e documentazione comune
- Struttura documenti con headers chiari
- Aggiorna knowledge base regolarmente
- Monitora conversazioni per gap

---

## 📞 Supporto

- **Issues**: https://github.com/yourorg/xilioscient-bot/issues
- **Docs**: Leggi README.md completo
- **Email**: support@xilioscient.com

---

## 📄 Licenza

MIT License - Usa liberamente per progetti personali e commerciali!

---

## 🎉 Congratulazioni!

Hai un chatbot AI professionale completo pronto all'uso!

**Prossimo comando**:
```bash
./scripts/install.sh
```

Buon divertimento! 🚀
