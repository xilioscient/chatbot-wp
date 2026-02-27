# Installazione dettagliata

1. Requisiti server (Linux):
   - PHP 8.0+, MySQL, Apache/Nginx
   - Docker e docker-compose
2. Copia plugin:
   - `cp -r xilioscient-bot /var/www/html/wp-content/plugins/`
3. Attiva plugin dalla dashboard.
4. Avvia servizio LLM:
   - `cd docker && docker-compose up --build -d`
5. Configura JWT Secret in admin e in variabile d'ambiente del container.
6. Carica documenti e indicizzali.
