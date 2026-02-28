# XilioScient Bot - Windows Installation Script
# Esegui con: powershell -ExecutionPolicy Bypass -File install.ps1

Write-Host "=== XilioScient Bot - Installazione Windows ===" -ForegroundColor Cyan
Write-Host ""

# Check Docker Desktop
Write-Host "Verifica Docker Desktop..." -ForegroundColor Yellow
if (Get-Command docker -ErrorAction SilentlyContinue) {
    Write-Host "[OK] Docker installato" -ForegroundColor Green
} else {
    Write-Host "[ERRORE] Docker non installato" -ForegroundColor Red
    Write-Host "Installa Docker Desktop: https://www.docker.com/products/docker-desktop" -ForegroundColor Yellow
    exit 1
}

# Verifica Docker running
$dockerRunning = docker ps 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERRORE] Docker Desktop non in esecuzione" -ForegroundColor Red
    Write-Host "Avvia Docker Desktop e riprova" -ForegroundColor Yellow
    exit 1
}
Write-Host "[OK] Docker Desktop attivo" -ForegroundColor Green

Write-Host ""
Write-Host "=== Configurazione Servizio LLM ===" -ForegroundColor Cyan

# Crea directory dati
Write-Host "Creazione directory dati..." -ForegroundColor Yellow
New-Item -ItemType Directory -Force -Path "docker\data\vector_db" | Out-Null
New-Item -ItemType Directory -Force -Path "docker\data\models" | Out-Null
New-Item -ItemType Directory -Force -Path "docker\data\documents" | Out-Null
Write-Host "[OK] Directory create" -ForegroundColor Green

# Verifica modello
Write-Host ""
Write-Host "Verifica modello LLM..." -ForegroundColor Yellow
if (-not (Test-Path "docker\data\models\model.gguf")) {
    Write-Host "Nessun modello trovato" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Opzioni:" -ForegroundColor Cyan
    Write-Host "1) Scarica Mistral 7B Q4 (4GB) [Raccomandato]"
    Write-Host "2) Specifica path modello esistente"
    Write-Host "3) Salta (configura dopo)"
    $choice = Read-Host "Scelta [1-3]"
    
    switch ($choice) {
        "1" {
            Write-Host "Download Mistral 7B Q4..." -ForegroundColor Yellow
            $url = "https://huggingface.co/TheBloke/Mistral-7B-Instruct-v0.2-GGUF/resolve/main/mistral-7b-instruct-v0.2.Q4_K_M.gguf"
            $output = "docker\data\models\model.gguf"
            
            # Download con progress bar
            $ProgressPreference = 'SilentlyContinue'
            Invoke-WebRequest -Uri $url -OutFile $output -UseBasicParsing
            $ProgressPreference = 'Continue'
            
            Write-Host "[OK] Modello scaricato" -ForegroundColor Green
        }
        "2" {
            $modelPath = Read-Host "Path modello"
            if (Test-Path $modelPath) {
                Copy-Item $modelPath "docker\data\models\model.gguf"
                Write-Host "[OK] Modello copiato" -ForegroundColor Green
            } else {
                Write-Host "[ERRORE] File non trovato" -ForegroundColor Red
                exit 1
            }
        }
        "3" {
            Write-Host "[AVVISO] Ricorda di aggiungere il modello prima di avviare" -ForegroundColor Yellow
        }
    }
}

# Configura .env
Write-Host ""
Write-Host "Configurazione variabili d'ambiente..." -ForegroundColor Yellow
if (-not (Test-Path "docker\.env")) {
    # Genera JWT Secret
    $bytes = New-Object byte[] 32
    [Security.Cryptography.RNGCryptoServiceProvider]::Create().GetBytes($bytes)
    $jwtSecret = [Convert]::ToBase64String($bytes).Replace("=","").Replace("+","").Replace("/","").Substring(0,32)
    
    $envContent = @"
# XilioScient Bot - Configuration
JWT_SECRET=$jwtSecret
FALLBACK_API_PROVIDER=none
FALLBACK_API_KEY=
FALLBACK_API_URL=
LOG_LEVEL=INFO
"@
    
    Set-Content -Path "docker\.env" -Value $envContent
    Write-Host "[OK] File .env creato" -ForegroundColor Green
    Write-Host "JWT Secret generato: $jwtSecret" -ForegroundColor Cyan
    Write-Host "[IMPORTANTE] Salva questo JWT Secret per WordPress" -ForegroundColor Yellow
} else {
    Write-Host "[OK] File .env già esistente" -ForegroundColor Green
}

# Build e avvio Docker
Write-Host ""
Write-Host "=== Build e Avvio Docker ===" -ForegroundColor Cyan
Set-Location docker

Write-Host "Build immagine Docker..." -ForegroundColor Yellow
docker-compose build
if ($LASTEXITCODE -ne 0) {
    Write-Host "[ERRORE] Build fallito" -ForegroundColor Red
    exit 1
}
Write-Host "[OK] Build completato" -ForegroundColor Green

Write-Host ""
$startNow = Read-Host "Avviare i servizi ora? [y/N]"
if ($startNow -eq "y" -or $startNow -eq "Y") {
    docker-compose up -d
    Write-Host "[OK] Servizi avviati" -ForegroundColor Green
    
    Write-Host ""
    Write-Host "Attendo avvio servizi..." -ForegroundColor Yellow
    Start-Sleep -Seconds 10
    
    # Health check
    try {
        $response = Invoke-WebRequest -Uri "http://localhost:5000/api/health" -UseBasicParsing -TimeoutSec 5
        if ($response.StatusCode -eq 200) {
            Write-Host "[OK] Servizio LLM operativo" -ForegroundColor Green
        }
    } catch {
        Write-Host "[AVVISO] Servizio non ancora pronto" -ForegroundColor Yellow
        Write-Host "Controlla logs: docker-compose logs -f llm-service" -ForegroundColor Cyan
    }
}

Set-Location ..

Write-Host ""
Write-Host "=== Installazione Plugin WordPress ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "PERCORSI COMUNI WINDOWS:" -ForegroundColor Yellow
Write-Host "XAMPP:    C:\xampp\htdocs\wordpress\wp-content\plugins\" -ForegroundColor Cyan
Write-Host "WAMP:     C:\wamp64\www\wordpress\wp-content\plugins\" -ForegroundColor Cyan
Write-Host "Local:    C:\Users\$env:USERNAME\Local Sites\[sito]\app\public\wp-content\plugins\" -ForegroundColor Cyan
Write-Host ""

$wpPath = Read-Host "Inserisci path completo directory plugins WordPress (o premi INVIO per saltare)"
if ($wpPath -and (Test-Path $wpPath)) {
    Write-Host "Copia plugin..." -ForegroundColor Yellow
    Copy-Item -Path "xilioscient-bot" -Destination "$wpPath\xilioscient-bot" -Recurse -Force
    Write-Host "[OK] Plugin copiato in WordPress" -ForegroundColor Green
    Write-Host ""
    Write-Host "PROSSIMI PASSI:" -ForegroundColor Cyan
    Write-Host "1. Vai su WordPress Admin -> Plugin" -ForegroundColor White
    Write-Host "2. Attiva 'XilioScient Bot'" -ForegroundColor White
    Write-Host "3. Vai su XilioScient Bot -> Impostazioni" -ForegroundColor White
    Write-Host "4. Configura Endpoint LLM: http://127.0.0.1:5000" -ForegroundColor White
    Write-Host "5. Inserisci JWT Secret (vedi sopra)" -ForegroundColor White
    Write-Host "6. Testa connessione" -ForegroundColor White
} else {
    Write-Host ""
    Write-Host "Copia manuale plugin:" -ForegroundColor Yellow
    Write-Host "xcopy /E /I xilioscient-bot C:\path\to\wordpress\wp-content\plugins\xilioscient-bot" -ForegroundColor Cyan
}

Write-Host ""
Write-Host "=== Installazione Completata ===" -ForegroundColor Green
Write-Host ""
Write-Host "COMANDI UTILI:" -ForegroundColor Cyan
Write-Host "cd docker" -ForegroundColor White
Write-Host "docker-compose ps                 # Stato servizi" -ForegroundColor White
Write-Host "docker-compose logs -f            # Visualizza logs" -ForegroundColor White
Write-Host "docker-compose restart            # Riavvia" -ForegroundColor White
Write-Host "docker-compose down               # Ferma servizi" -ForegroundColor White
Write-Host ""
Write-Host "Documentazione: README.md" -ForegroundColor Cyan
Write-Host "Quick Start: 00_START_HERE.md" -ForegroundColor Cyan
