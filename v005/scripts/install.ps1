# ============================================================
# XilioScient Bot - Script Installazione Windows Ottimizzato
# ============================================================
# 
# ESEGUI DALLA DIRECTORY PRINCIPALE: C:\chatbot-wp\v003\
# Comando: powershell -ExecutionPolicy Bypass -File scripts\install.ps1
#
# ============================================================

Write-Host ""
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "  XilioScient Bot - Installazione Automatica Windows" -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

# Verifica di essere nella directory corretta
if (-not (Test-Path ".\xilioscient-bot\xilioscient-bot.php")) {
    Write-Host "[ERRORE] Script eseguito dalla directory sbagliata!" -ForegroundColor Red
    Write-Host "Devi essere in: C:\chatbot-wp\v003\" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Comando corretto:" -ForegroundColor Yellow
    Write-Host "  cd C:\chatbot-wp\v003\" -ForegroundColor White
    Write-Host "  powershell -ExecutionPolicy Bypass -File scripts\install.ps1" -ForegroundColor White
    Write-Host ""
    Read-Host "Premi INVIO per uscire"
    exit 1
}

Write-Host "[OK] Directory corretta" -ForegroundColor Green
Write-Host ""

# ============================================================
# STEP 1: Verifica Docker
# ============================================================
Write-Host "STEP 1/6: Verifica Docker Desktop" -ForegroundColor Cyan
Write-Host "------------------------------------------------------------" -ForegroundColor DarkGray

if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    Write-Host "[ERRORE] Docker non installato!" -ForegroundColor Red
    Write-Host "Scarica: https://www.docker.com/products/docker-desktop" -ForegroundColor Yellow
    Read-Host "Premi INVIO per uscire"
    exit 1
}

$dockerTest = docker ps 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERRORE] Docker Desktop non in esecuzione!" -ForegroundColor Red
    Write-Host "Avvia Docker Desktop e riprova" -ForegroundColor Yellow
    Read-Host "Premi INVIO per uscire"
    exit 1
}

Write-Host "[OK] Docker operativo (versione $(docker --version))" -ForegroundColor Green
Write-Host ""

# ============================================================
# STEP 2: Riorganizza Struttura File
# ============================================================
Write-Host "STEP 2/6: Riorganizzazione struttura file" -ForegroundColor Cyan
Write-Host "------------------------------------------------------------" -ForegroundColor DarkGray

# Crea directory llm-service
Write-Host "Creazione docker/llm-service/..." -ForegroundColor Yellow
New-Item -ItemType Directory -Force -Path "docker\llm-service\app" | Out-Null

# Sposta (non copia) i file
Write-Host "Spostamento Dockerfile..." -ForegroundColor Yellow
Move-Item -Path "docker\Dockerfile" -Destination "docker\llm-service\" -Force -ErrorAction SilentlyContinue

Write-Host "Spostamento requirements.txt..." -ForegroundColor Yellow
Move-Item -Path "docker\requirements.txt" -Destination "docker\llm-service\" -Force -ErrorAction SilentlyContinue

Write-Host "Spostamento app/*.py..." -ForegroundColor Yellow
Move-Item -Path "docker\app\*" -Destination "docker\llm-service\app\" -Force -ErrorAction SilentlyContinue

# Crea __init__.py se mancante
if (-not (Test-Path "docker\llm-service\app\__init__.py")) {
    New-Item -ItemType File -Path "docker\llm-service\app\__init__.py" -Force | Out-Null
}

# Rimuovi directory app vuota
Remove-Item -Path "docker\app" -Force -ErrorAction SilentlyContinue

Write-Host "[OK] Struttura riorganizzata" -ForegroundColor Green
Write-Host ""

# ============================================================
# STEP 3: Crea Directory Dati
# ============================================================
Write-Host "STEP 3/6: Creazione directory dati" -ForegroundColor Cyan
Write-Host "------------------------------------------------------------" -ForegroundColor DarkGray

New-Item -ItemType Directory -Force -Path "docker\data\vector_db" | Out-Null
New-Item -ItemType Directory -Force -Path "docker\data\models" | Out-Null
New-Item -ItemType Directory -Force -Path "docker\data\documents" | Out-Null

Write-Host "[OK] Directory create" -ForegroundColor Green
Write-Host ""

# ============================================================
# STEP 4: Configura Modello LLM
# ============================================================
Write-Host "STEP 4/6: Configurazione modello LLM" -ForegroundColor Cyan
Write-Host "------------------------------------------------------------" -ForegroundColor DarkGray

if (Test-Path "docker\data\models\model.gguf") {
    Write-Host "[OK] Modello gia' presente" -ForegroundColor Green
} else {
    Write-Host "Inserisci il path completo del tuo modello GGUF:" -ForegroundColor Yellow
    $modelPath = Read-Host "Path"
    
    if (Test-Path $modelPath) {
        Write-Host "Spostamento modello (puo' richiedere qualche secondo)..." -ForegroundColor Yellow
        Move-Item -Path $modelPath -Destination "docker\data\models\model.gguf" -Force
        Write-Host "[OK] Modello spostato" -ForegroundColor Green
    } else {
        Write-Host "[ERRORE] File non trovato: $modelPath" -ForegroundColor Red
        Read-Host "Premi INVIO per uscire"
        exit 1
    }
}
Write-Host ""

# ============================================================
# STEP 5: Genera JWT Secret e File .env
# ============================================================
Write-Host "STEP 5/6: Generazione JWT Secret" -ForegroundColor Cyan
Write-Host "------------------------------------------------------------" -ForegroundColor DarkGray

$jwtSecret = ""

if (Test-Path "docker\.env") {
    Write-Host "File .env gia' esistente, leggo JWT Secret..." -ForegroundColor Yellow
    $envContent = Get-Content "docker\.env" -Raw
    if ($envContent -match 'JWT_SECRET=([^\r\n]+)') {
        $jwtSecret = $matches[1]
        Write-Host "[OK] JWT Secret trovato" -ForegroundColor Green
    }
} 

if (-not $jwtSecret) {
    Write-Host "Generazione nuovo JWT Secret..." -ForegroundColor Yellow
    $bytes = New-Object byte[] 32
    [Security.Cryptography.RNGCryptoServiceProvider]::Create().GetBytes($bytes)
    $jwtSecret = [Convert]::ToBase64String($bytes).Replace("=","").Replace("+","").Replace("/","").Substring(0,32)
    
    $envContent = @"
JWT_SECRET=$jwtSecret
FALLBACK_API_PROVIDER=none
FALLBACK_API_KEY=
FALLBACK_API_URL=
LOG_LEVEL=INFO
"@
    
    Set-Content -Path "docker\.env" -Value $envContent -NoNewline
    Write-Host "[OK] File .env creato" -ForegroundColor Green
}

Write-Host ""
Write-Host "============================================================" -ForegroundColor Yellow
Write-Host "  JWT SECRET (salvalo per WordPress):" -ForegroundColor Yellow
Write-Host "  $jwtSecret" -ForegroundColor White
Write-Host "============================================================" -ForegroundColor Yellow
Write-Host ""

# ============================================================
# STEP 6: Build e Avvio Docker
# ============================================================
Write-Host "STEP 6/6: Build e avvio Docker" -ForegroundColor Cyan
Write-Host "------------------------------------------------------------" -ForegroundColor DarkGray

Set-Location docker

Write-Host "Build immagine Docker (richiede 2-5 minuti)..." -ForegroundColor Yellow
docker compose build 2>&1 | Out-Null

if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERRORE] Build fallito!" -ForegroundColor Red
    Write-Host "Esegui manualmente per vedere l'errore:" -ForegroundColor Yellow
    Write-Host "  cd docker" -ForegroundColor White
    Write-Host "  docker compose build" -ForegroundColor White
    Set-Location ..
    Read-Host "Premi INVIO per uscire"
    exit 1
}

Write-Host "[OK] Build completato" -ForegroundColor Green
Write-Host ""
Write-Host "Avvio servizi..." -ForegroundColor Yellow
docker compose up -d

Write-Host "[OK] Servizi avviati" -ForegroundColor Green
Write-Host ""
Write-Host "Attendo inizializzazione (30 secondi)..." -ForegroundColor Yellow
Start-Sleep -Seconds 30

# Health check
Write-Host "Verifica servizio LLM..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://localhost:5000/api/health" -UseBasicParsing -TimeoutSec 5
    if ($response.StatusCode -eq 200) {
        Write-Host "[OK] Servizio LLM operativo!" -ForegroundColor Green
    }
} catch {
    Write-Host "[AVVISO] Servizio non risponde ancora" -ForegroundColor Yellow
    Write-Host "Verifica con: docker compose logs llm-service" -ForegroundColor Cyan
}

Set-Location ..
Write-Host ""

# ============================================================
# COMPLETATO!
# ============================================================
Write-Host ""
Write-Host "============================================================" -ForegroundColor Green
Write-Host "  INSTALLAZIONE COMPLETATA!" -ForegroundColor Green
Write-Host "============================================================" -ForegroundColor Green
Write-Host ""

Write-Host "PROSSIMI PASSI:" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. COPIA PLUGIN IN WORDPRESS:" -ForegroundColor Yellow
Write-Host "   xcopy /E /I xilioscient-bot C:\Users\sasha\Local Sites\chatbot-xilio\app\public\wp-content\plugins\xilioscient-bot" -ForegroundColor White
Write-Host ""
Write-Host "2. ATTIVA PLUGIN:" -ForegroundColor Yellow
Write-Host "   WordPress Admin -> Plugin -> XilioScient Bot -> Attiva" -ForegroundColor White
Write-Host ""
Write-Host "3. CONFIGURA PLUGIN:" -ForegroundColor Yellow
Write-Host "   XilioScient Bot -> Impostazioni" -ForegroundColor White
Write-Host "   - Endpoint LLM: http://host.docker.internal:5000" -ForegroundColor White
Write-Host "   - JWT Secret: $jwtSecret" -ForegroundColor White
Write-Host "   - Top K: 5" -ForegroundColor White
Write-Host "   - Chunk Size: 500" -ForegroundColor White
Write-Host "   - Clicca 'Testa Connessione' -> deve essere OK" -ForegroundColor White
Write-Host "   - Salva Impostazioni" -ForegroundColor White
Write-Host ""
Write-Host "4. CARICA DOCUMENTI:" -ForegroundColor Yellow
Write-Host "   XilioScient Bot -> Knowledge Base -> Carica file" -ForegroundColor White
Write-Host ""
Write-Host "5. TESTA IL WIDGET:" -ForegroundColor Yellow
Write-Host "   Apri homepage -> Clicca pulsante chat -> Invia messaggio" -ForegroundColor White
Write-Host ""

Write-Host "COMANDI UTILI:" -ForegroundColor Cyan
Write-Host "  cd docker" -ForegroundColor White
Write-Host "  docker compose ps          # Stato servizi" -ForegroundColor White
Write-Host "  docker compose logs -f     # Visualizza logs" -ForegroundColor White
Write-Host "  docker compose restart     # Riavvia" -ForegroundColor White
Write-Host "  docker compose down        # Ferma servizi" -ForegroundColor White
Write-Host ""

Read-Host "Premi INVIO per chiudere"
