FROM python:3.11-slim

# Installa dipendenze di sistema per compilazione
RUN apt-get update && apt-get install -y \
    gcc \
    g++ \
    make \
    cmake \
    build-essential \
    curl \
    wget \
    git \
    libopenblas-dev \
    && rm -rf /var/lib/apt/lists/*

# Crea directory di lavoro
WORKDIR /app

# Copia requirements base (senza llama-cpp-python)
COPY requirements.txt .

# Installa dipendenze base
RUN pip install --no-cache-dir --upgrade pip && \
    pip install --no-cache-dir -r requirements.txt

# Installa llama-cpp-python con supporto CPU ottimizzato per Intel
# CMAKE_ARGS forza la compilazione con supporto AVX2 per processori Intel moderni
RUN CMAKE_ARGS="-DLLAMA_BLAS=ON -DLLAMA_BLAS_VENDOR=OpenBLAS" \
    pip install --no-cache-dir llama-cpp-python==0.2.27 --force-reinstall --no-binary :all:

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
    LOG_LEVEL=INFO \
    OMP_NUM_THREADS=4

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD curl -f http://localhost:5000/api/health || exit 1

# Avvia applicazione con un solo worker (importante per modelli LLM)
CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "5000", "--workers", "1"]
