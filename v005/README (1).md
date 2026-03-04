# XilioScient Bot - Plugin WordPress con Chatbot AI Locale e RAG

Plugin WordPress professionale che fornisce un chatbot AI con Retrieval Augmented Generation (RAG) utilizzando un modello LLM locale.

## 🌟 Caratteristiche

- ✅ **Chatbot AI locale** con modelli Llama.cpp/GPT4All
- ✅ **RAG (Retrieval Augmented Generation)** con FAISS + SQLite
- ✅ **Widget chat professionale** con design moderno e responsive
- ✅ **Gestione documenti** - carica PDF, HTML, Markdown, DOCX
- ✅ **Privacy e GDPR** - dati locali, anonimizzazione opzionale
- ✅ **Fallback cloud** - supporto OpenAI/Anthropic come backup
- ✅ **Rate limiting** - protezione da abusi
- ✅ **Feedback utenti** - thumbs up/down per miglioramento
- ✅ **Statistiche dettagliate** - dashboard admin completa
- ✅ **Multi-sessione** - gestione conversazioni persistenti

## 📋 Requisiti

### Server
- PHP 8.0+
- WordPress 6.0+
- MySQL 5.7+ / MariaDB 10.3+
- 2GB RAM minimo (4GB raccomandato)

### Docker (per servizio LLM)
- Docker 20.10+
- Docker Compose 2.0+
- 4GB RAM disponibili per container
- 10GB spazio disco

### Opzionale
- GPU NVIDIA con CUDA (per inferenza veloce)
- Modello LLM locale (es. Llama 2, Mistral)

## 🚀 Installazione Rapida

### 1. Installa Plugin WordPress

```bash
# Scarica o clona repository
cd /path/to/wordpress/wp-content/plugins/
git clone https://github.com/yourorg/xilioscient-bot.git

# Oppure carica ZIP tramite WordPress admin
```

### 2. Attiva Plugin

- Vai su **WordPress Admin → Plugin**
- Trova "XilioScient Bot"
- Clicca **Attiva**

### 3. Configura Servizio LLM

```bash
# Naviga nella cartella docker
cd docker/

# Crea directory dati
mkdir -p data/{vector_db,models,documents}

# Scarica un modello (esempio: Mistral 7B)
cd data/models/
wget https://huggingface.co/TheBloke/Mistral-7B-Instruct-v0.2-GGUF/resolve/main/mistral-7b-instruct-v0.2.Q4_K_M.gguf -O model.gguf
cd ../..

# Configura variabili d'ambiente
cp .env.example .env
# Modifica .env con i tuoi valori

# Avvia servizi
docker-compose up -d

# Verifica stato
docker-compose ps
docker-compose logs -f llm-service
```

### 4. Configura Plugin WordPress

1. Vai su **WordPress Admin → XilioScient Bot → Impostazioni**
2. Configura:
   - **Endpoint LLM**: `http://127.0.0.1:5000` (o IP server Docker)
   - **JWT Secret**: genera una chiave sicura (32+ caratteri)
   - **Top K Retrieval**: `5` (numero documenti da recuperare)
   - **Chunk Size**: `500` (dimensione chunks per RAG)
   - **Max Context Tokens**: `2000`

3. Testa connessione con il pulsante **"Test Connessione"**

### 5. Carica Documenti Knowledge Base

1. Vai su **XilioScient Bot → Knowledge Base**
2. Carica i tuoi documenti (PDF, HTML, MD, DOCX)
3. Attendi l'indicizzazione
4. I documenti saranno usati per rispondere alle domande

### 6. Aggiungi Widget al Sito

**Metodo 1 - Widget Floating (raccomandato):**
```php
// Il widget appare automaticamente su tutte le pagine
// Configurabile da Impostazioni → Posizione widget
```

**Metodo 2 - Shortcode:**
```html
<!-- Widget inline -->
[xsbot_chat theme="light" height="600px"]

<!-- Solo pulsante -->
[xsbot_button position="bottom-right"]
```

**Metodo 3 - Template PHP:**
```php
<?php
if (function_exists('xsbot_init')) {
    echo do_shortcode('[xsbot_chat]');
}
?>
```

## ⚙️ Configurazione Avanzata

### Modelli LLM Supportati

Il plugin supporta qualsiasi modello compatibile con llama.cpp:

- **Llama 2** (7B, 13B, 70B)
- **Mistral** (7B)
- **Mixtral** (8x7B)
- **Vicuna**
- **Alpaca**
- Altri modelli GGUF

### Fallback Cloud

Configura un'API cloud come backup:

```bash
# In .env o WordPress admin
FALLBACK_API_PROVIDER=openai  # o 'anthropic'
FALLBACK_API_KEY=your-api-key-here
```

Quando il modello locale non è disponibile, il sistema usa automaticamente il fallback.

### Rate Limiting

Proteggi da abusi configurando:

- **Max richieste**: 20 (default)
- **Finestra temporale**: 3600 secondi (1 ora)
- Personalizzabile per utente/sessione

### Privacy e GDPR

- **Anonimizzazione IP**: Abilita in impostazioni
- **Log conversazioni**: Disabilita se richiesto
- **Retention**: Configurabile (default 30 giorni)
- **Eliminazione dati**: Script automatico disponibile

### Personalizzazione Widget

```css
/* CSS personalizzato in Aspetto → Personalizza → CSS Aggiuntivo */

/* Cambia colori gradient */
.xsbot-toggle-btn {
    background: linear-gradient(135deg, #your-color1 0%, #your-color2 100%);
}

/* Dimensioni widget */
.xsbot-widget {
    width: 450px;
    height: 700px;
}
```

## 📊 Funzionalità Admin

### Dashboard Statistiche

Accedi a metriche dettagliate:

- Conversazioni totali
- Utenti unici
- Feedback positivi/negativi
- Grafico conversazioni giornaliere
- Documenti indicizzati
- Salute servizio LLM

### Gestione Knowledge Base

- Carica documenti multipli
- Visualizza chunks estratti
- Elimina documenti
- Re-indicizza database

### Cronologia Conversazioni

- Visualizza tutte le conversazioni
- Filtra per data/utente/sessione
- Esporta in CSV
- Elimina conversazioni (GDPR)

## 🧪 Test

### Test PHP (Plugin)

```bash
cd wp-content/plugins/xilioscient-bot/
composer install
./vendor/bin/phpunit tests/
```

### Test Python (Servizio LLM)

```bash
cd docker/llm-service/
pip install pytest pytest-asyncio
pytest app/tests/ -v
```

## 🔧 Troubleshooting

### Plugin non appare

```bash
# Verifica permessi
chmod -R 755 xilioscient-bot/
chown -R www-data:www-data xilioscient-bot/

# Verifica errori PHP
tail -f /var/log/php-fpm/error.log
```

### Servizio LLM non risponde

```bash
# Verifica container
docker-compose ps
docker-compose logs llm-service

# Test diretto
curl http://localhost:5000/api/health

# Riavvia servizio
docker-compose restart llm-service
```

### Widget non appare

1. Verifica che JavaScript sia caricato: **Ispeziona → Console**
2. Controlla errori CORS
3. Verifica nonce WordPress
4. Disabilita plugin cache temporaneamente

### Errore "Rate limit exceeded"

- Aumenta limiti in **Impostazioni → Rate Limiting**
- O attendi 1 ora per reset automatico

### Modello troppo lento

Soluzioni:
1. Usa modello quantizzato più piccolo (Q4 invece di Q8)
2. Abilita GPU (se disponibile)
3. Configura fallback cloud
4. Aumenta risorse Docker

## 🔐 Sicurezza

### Best Practices

✅ **Genera JWT Secret forte**:
```bash
openssl rand -base64 32
```

✅ **HTTPS obbligatorio** in produzione

✅ **Firewall**: Blocca porta 5000 dall'esterno
```bash
ufw allow 80/tcp
ufw allow 443/tcp
ufw deny 5000/tcp
```

✅ **Aggiorna regolarmente**:
```bash
cd xilioscient-bot/
git pull
docker-compose pull
docker-compose up -d
```

✅ **Backup automatico**:
```bash
# Aggiungi a crontab
0 2 * * * /path/to/scripts/backup_db.sh
```

### Protezione Prompt Injection

Il plugin include filtri di base per prompt injection. Pattern bloccati:

- "ignore previous instructions"
- "you are now"
- Token di sistema (`<|im_start|>`, ecc.)

## 📦 Backup e Ripristino

### Backup Database

```bash
# Script incluso
./scripts/backup_db.sh

# Manuale
mysqldump -u user -p wordpress_db > backup.sql
```

### Backup Vector Database

```bash
# Backup FAISS + SQLite
tar -czf vector_db_backup.tar.gz docker/data/vector_db/
```

### Ripristino

```bash
# Database
mysql -u user -p wordpress_db < backup.sql

# Vector DB
tar -xzf vector_db_backup.tar.gz -C docker/data/
docker-compose restart llm-service
```

## 🚀 Deployment Produzione

### Requisiti Hardware

**Minimo:**
- 2 CPU cores
- 4GB RAM
- 20GB storage
- Modello Q4 quantizzato

**Raccomandato:**
- 4+ CPU cores (8+ ideale)
- 8GB+ RAM (16GB ideale)
- 50GB+ storage SSD
- GPU NVIDIA (RTX 3060+) con 12GB+ VRAM
- Modello Q5/Q8 per qualità superiore

### Ottimizzazioni

```yaml
# docker-compose.yml per produzione
services:
  llm-service:
    deploy:
      resources:
        limits:
          cpus: '4'
          memory: 8G
    # Se hai GPU
    runtime: nvidia
    environment:
      - CUDA_VISIBLE_DEVICES=0
```

### Monitoraggio

```bash
# Prometheus metrics (futuro)
# Grafana dashboard (futuro)

# Al momento: logs
docker-compose logs -f --tail=100 llm-service
```

## 📚 Documentazione Aggiuntiva

- [INSTALL.md](docs/INSTALL.md) - Guida installazione dettagliata
- [SECURITY.md](docs/SECURITY.md) - Linee guida sicurezza
- [PROMPTS.md](docs/PROMPTS.md) - Template prompt e best practices
- [GDPR.md](docs/GDPR.md) - Compliance GDPR
- [API.md](docs/API.md) - Documentazione API REST

## 🤝 Contribuire

Contributi benvenuti! Per favore:

1. Fork il repository
2. Crea branch feature (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add AmazingFeature'`)
4. Push al branch (`git push origin feature/AmazingFeature`)
5. Apri Pull Request

## 📄 Licenza

MIT License - vedi [LICENSE](LICENSE) per dettagli

## 🆘 Supporto

- **Issues**: https://github.com/yourorg/xilioscient-bot/issues
- **Discussions**: https://github.com/yourorg/xilioscient-bot/discussions
- **Email**: support@xilioscient.com

## ✅ Checklist Verifica Rapida

Usa questa checklist per confermare che tutto funziona:

- [ ] Plugin attivato in WordPress
- [ ] Container Docker LLM in esecuzione (`docker-compose ps`)
- [ ] Endpoint LLM raggiungibile (`curl http://localhost:5000/api/health`)
- [ ] JWT Secret configurato in plugin
- [ ] Connessione testata con successo (pulsante admin)
- [ ] Almeno 1 documento caricato e indicizzato
- [ ] Widget appare sul sito
- [ ] Test messaggio riceve risposta
- [ ] Statistiche visibili in admin
- [ ] Backup configurato (opzionale ma raccomandato)

Se tutti i punti sono ✅, il sistema è pronto! 🎉

## 🔄 Aggiornamenti

### v1.0.0 (2024-01-15)
- Release iniziale
- RAG con FAISS + SQLite
- Widget React responsive
- Admin dashboard completo
- Supporto fallback cloud

---

**Fatto con ❤️ da XilioScient Team**
