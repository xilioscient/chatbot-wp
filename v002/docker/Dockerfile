FROM python:3.11-slim

# Installa dipendenze di sistema
RUN apt-get update && apt-get install -y \
    gcc \
    g++ \
    build-essential \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Crea directory di lavoro
WORKDIR /app

# Copia requirements
COPY requirements.txt .

# Installa dipendenze Python
RUN pip install --no-cache-dir -r requirements.txt

# Copia codice applicazione
COPY app/ ./app/

# Crea directory per dati
RUN mkdir -p /data/vector_db /data/models /data/documents

# Esponi porta
EXPOSE 5000

# Variabili d'ambiente di default
ENV MODEL_PATH=/data/models/model.gguf \
    VECTOR_DB_PATH=/data/vector_db \
    JWT_SECRET=your-secret-key-here \
    MAX_CONTEXT_TOKENS=2000 \
    FALLBACK_API_URL="" \
    FALLBACK_API_KEY="" \
    LOG_LEVEL=INFO

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD curl -f http://localhost:5000/api/health || exit 1

# Avvia applicazione
CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "5000", "--workers", "1"]
