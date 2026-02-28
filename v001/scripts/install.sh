#!/usr/bin/env bash
set -e
echo "Aggiornamento sistema e installazione dipendenze base..."
sudo apt-get update
sudo apt-get install -y python3 python3-venv python3-pip docker.io docker-compose git

echo "Creare cartella per plugin e copiare files in wp-content/plugins/xilioscient-bot"
echo "Assicurati di avere accesso al server WordPress e di attivare il plugin dalla dashboard."

echo "Per avviare il servizio LLM:"
echo "cd docker && docker-compose up --build -d"
