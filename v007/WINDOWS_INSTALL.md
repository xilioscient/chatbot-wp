# 🪟 Guida Installazione XilioScient Bot per Windows

## ⚠️ Prerequisiti Windows

### 1. **Docker Desktop**
Scarica e installa: https://www.docker.com/products/docker-desktop

**Importante**:
- Dopo l'installazione, **riavvia il PC**
- Avvia Docker Desktop prima di procedere
- Verifica che sia in esecuzione (icona balena nella system tray)

### 2. **WordPress Locale**
Scegli uno:
- **XAMPP**: https://www.apachefriends.org/
- **WAMP**: https://www.wampserver.com/
- **Local by Flywheel**: https://localwp.com/ (Raccomandato)

---

## 🚀 Installazione Rapida con PowerShell

### Passo 1: Apri PowerShell come Amministratore

1. Premi `Win + X`
2. Seleziona "Windows PowerShell (Admin)"
3. Naviga alla cartella del plugin:
   ```powershell
   cd C:\Users\TuoNome\Downloads\xilioscient-bot
   ```

### Passo 2: Esegui Script Installazione

```powershell
powershell -ExecutionPolicy Bypass -File scripts\install.ps1
```

Lo script:
- ✅ Verifica Docker
- ✅ Crea directory necessarie
- ✅ Opzionalmente scarica modello LLM
- ✅ Genera JWT Secret
- ✅ Avvia servizi Docker

---

## 📦 Installazione Manuale (Passo-Passo)

### Parte 1: Servizio LLM Docker

#### 1. Crea Directory Dati

```powershell
cd docker
New-Item -ItemType Directory -Force -Path data\vector_db
New-Item -ItemType Directory -Force -Path data\models
New-Item -ItemType Directory -Force -Path data\documents
```

#### 2. Scarica Modello LLM

**Opzione A - Mistral 7B (Raccomandato)**:
```powershell
# Usando PowerShell
cd data\models
Invoke-WebRequest -Uri "https://huggingface.co/TheBloke/Mistral-7B-Instruct-v0.2-GGUF/resolve/main/mistral-7b-instruct-v0.2.Q4_K_M.gguf" -OutFile "model.gguf"
cd ..\..
```

**Opzione B - Browser**:
1. Vai su: https://huggingface.co/TheBloke/Mistral-7B-Instruct-v0.2-GGUF
2. Scarica: `mistral-7b-instruct-v0.2.Q4_K_M.gguf`
3. Rinomina in `model.gguf`
4. Sposta in `docker\data\models\model.gguf`

#### 3. Crea File .env

```powershell
# Genera JWT Secret sicuro
$bytes = New-Object byte[] 32
[Security.Cryptography.RNGCryptoServiceProvider]::Create().GetBytes($bytes)
$jwtSecret = [Convert]::ToBase64String($bytes).Replace("=","").Replace("+","").Replace("/","").Substring(0,32)

# Mostra JWT Secret (SALVALO!)
Write-Host "JWT Secret: $jwtSecret"

# Crea file .env
@"
JWT_SECRET=$jwtSecret
FALLBACK_API_PROVIDER=none
FALLBACK_API_KEY=
FALLBACK_API_URL=
LOG_LEVEL=INFO
"@ | Out-File -FilePath .env -Encoding ASCII
```

#### 4. Avvia Docker

```powershell
docker-compose up -d
```

#### 5. Verifica Funzionamento

```powershell
# Attendi 30 secondi per l'avvio
Start-Sleep -Seconds 30

# Test health check
curl http://localhost:5000/api/health
```

Se vedi `"status":"ok"` → **Funziona!** ✅

---

### Parte 2: Plugin WordPress

#### 1. Identifica Percorso WordPress

**XAMPP**:
```
C:\xampp\htdocs\wordpress\wp-content\plugins\
```

**WAMP**:
```
C:\wamp64\www\wordpress\wp-content\plugins\
```

**Local by Flywheel**:
```
C:\Users\TuoNome\Local Sites\nome-sito\app\public\wp-content\plugins\
```

#### 2. Copia Plugin

```powershell
# Sostituisci con il TUO percorso
$wpPluginsPath = "C:\xampp\htdocs\wordpress\wp-content\plugins\"

# Copia plugin
xcopy /E /I xilioscient-bot "$wpPluginsPath\xilioscient-bot"
```

**Oppure** copia manualmente la cartella `xilioscient-bot` in `wp-content\plugins\`

#### 3. Attiva Plugin in WordPress

1. Apri **WordPress Admin** (`http://localhost/wordpress/wp-admin`)
2. Vai su **Plugin**
3. Trova **XilioScient Bot**
4. Clicca **Attiva**

#### 4. Configura Plugin

1. Nel menu laterale, vai su **XilioScient Bot → Impostazioni**

2. Configura questi parametri:

   **Configurazione Servizio LLM**:
   - Endpoint LLM: `http://host.docker.internal:5000` ⚠️
   - JWT Secret: (incolla quello generato prima)

   ⚠️ **IMPORTANTE**: Su Windows usa `host.docker.internal` invece di `127.0.0.1`

   **Configurazione RAG**:
   - Top K Retrieval: `5`
   - Dimensione Chunk: `500`
   - Overlap Chunk: `50`
   - Max Context Tokens: `2000`

3. Clicca **"Testa Connessione LLM"**
   - Deve mostrare: ✅ "Connessione riuscita!"

4. **Salva Impostazioni**

#### 5. Carica Documenti

1. Vai su **XilioScient Bot → Knowledge Base**
2. Clicca **"Scegli File"**
3. Seleziona PDF, DOCX, HTML o MD
4. Clicca **"Carica Documento"**
5. Attendi indicizzazione

#### 6. Testa Widget

1. Apri homepage del tuo sito
2. Dovresti vedere il pulsante chat in basso a destra
3. Cliccalo e invia un messaggio di test
4. Dovresti ricevere risposta entro 5-10 secondi

---

## 🐛 Troubleshooting Windows

### Problema: "Docker non trovato"

**Soluzione**:
1. Installa Docker Desktop
2. Riavvia PC
3. Avvia Docker Desktop
4. Attendi che appaia icona balena nella system tray

### Problema: "Connessione LLM fallita"

**Causa**: Path di rete errato

**Soluzione**:
```
Usa: http://host.docker.internal:5000
NON: http://127.0.0.1:5000
NON: http://localhost:5000
```

### Problema: "docker-compose non riconosciuto"

**Soluzione**:
```powershell
# Usa invece:
docker compose up -d
# (senza trattino)
```

### Problema: "Permessi negati" durante copia plugin

**Soluzione**:
1. Chiudi XAMPP/WAMP
2. Esegui PowerShell come Amministratore
3. Riprova copia
4. Riavvia XAMPP/WAMP

### Problema: Widget non appare

**Verifica**:
1. Plugin attivato? (WordPress Admin → Plugin)
2. JavaScript caricato? (F12 → Console → cerca errori)
3. Cache svuotata? (CTRL+F5)
4. Tema compatibile? (prova con tema default Twenty Twenty-Four)

### Problema: Modello LLM troppo lento

**Soluzioni**:
1. Usa modello più piccolo (Q4 invece di Q8)
2. Configura fallback cloud:
   - Provider: OpenAI
   - API Key: tua chiave OpenAI
3. Aumenta RAM allocata a Docker:
   - Docker Desktop → Settings → Resources → Memory: 4GB+

---

## 📊 Comandi Utili PowerShell

```powershell
# Naviga a cartella docker
cd docker

# Stato servizi
docker-compose ps

# Visualizza logs in tempo reale
docker-compose logs -f llm-service

# Riavvia servizi
docker-compose restart

# Ferma servizi
docker-compose down

# Elimina tutto e ricomincia
docker-compose down -v
docker-compose up -d --build
```

---

## 🔧 Configurazione Avanzata Windows

### Firewall Windows

Se il widget non si connette, aggiungi eccezione:

1. Windows Defender Firewall → Impostazioni avanzate
2. Regole in entrata → Nuova regola
3. Porta → TCP → 5000
4. Consenti connessione
5. Nome: "XilioScient Bot LLM"

### Startup Automatico Docker

Per avviare Docker all'avvio di Windows:

1. Docker Desktop → Settings
2. General → "Start Docker Desktop when you log in"
3. ✅ Attiva

### Backup Automatico

Script PowerShell per backup giornaliero:

```powershell
# backup.ps1
$date = Get-Date -Format "yyyyMMdd"
$backupPath = "C:\Backup\xsbot-$date"

# Backup database WordPress
mysqldump -u root -p wordpress > "$backupPath-db.sql"

# Backup vector database
Copy-Item docker\data\vector_db "$backupPath-vectordb" -Recurse
```

Pianifica con Task Scheduler.

---

## 📱 Test su Dispositivi Mobili

Per testare da smartphone sulla stessa rete:

1. Trova IP del PC Windows:
   ```powershell
   ipconfig
   # Cerca "IPv4 Address"
   ```

2. Apri da smartphone:
   ```
   http://192.168.1.XX/wordpress
   ```

3. Widget dovrebbe funzionare anche da mobile

---

## ✅ Checklist Verifica Installazione

- [ ] Docker Desktop installato e in esecuzione
- [ ] Servizio LLM Docker avviato (`docker-compose ps`)
- [ ] Health check OK (`curl http://localhost:5000/api/health`)
- [ ] Plugin copiato in wp-content/plugins
- [ ] Plugin attivato in WordPress
- [ ] Endpoint configurato: `http://host.docker.internal:5000`
- [ ] JWT Secret configurato
- [ ] Test connessione ✅ riuscito
- [ ] Almeno 1 documento caricato
- [ ] Widget appare sul sito
- [ ] Messaggio di test riceve risposta

Se tutti ✅ → **Installazione completata!** 🎉

---

## 🆘 Supporto

**Problemi comuni Windows**:
- Path con spazi → Usa virgolette: `"C:\Program Files\..."`
- Antivirus blocca → Aggiungi eccezione per Docker
- WSL2 non installato → Docker lo installa automaticamente

**Documentazione completa**: README.md

**Issues**: Apri issue su GitHub con:
- Output di `docker-compose logs`
- Screenshot errore
- Versione Windows
