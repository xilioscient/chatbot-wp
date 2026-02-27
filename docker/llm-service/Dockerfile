FROM python:3.10-slim

WORKDIR /app

# Install system deps for FAISS and PDF parsing
RUN apt-get update && apt-get install -y build-essential git libsndfile1 libgl1 libglib2.0-0 poppler-utils

# Copy app
COPY app/requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY app/ /app/

EXPOSE 5000

ENV MODEL_PATH=/models/model.bin
ENV VECTOR_DB_PATH=/data/faiss_index
ENV JWT_SECRET=changeme
ENV MAX_CONTEXT_TOKENS=2048
ENV FALLBACK_API_URL=
ENV FALLBACK_API_KEY=

CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "5000", "--workers", "1"]
