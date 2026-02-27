# app/scripts/index_documents.py
"""
Script CLI per estrarre testo da PDF/HTML/MD, chunking, calcolo embedding e upsert in FAISS+SQLite.
Uso: python index_documents.py /path/to/file1.pdf /path/to/file2.md
"""
import sys
import os
from pypdf import PdfReader
from sentence_transformers import SentenceTransformer
from ..retrieval import Retriever
from ..embeddings import compute_embedding
import math

def extract_text_from_pdf(path):
    text = []
    reader = PdfReader(path)
    for p in reader.pages:
        text.append(p.extract_text() or '')
    return '\n'.join(text)

def extract_text_from_md(path):
    with open(path, 'r', encoding='utf-8') as f:
        return f.read()

def chunk_text(text, chunk_size=500, overlap=50):
    tokens = text.split()
    chunks = []
    i = 0
    while i < len(tokens):
        chunk = tokens[i:i+chunk_size]
        chunks.append(' '.join(chunk))
        i += chunk_size - overlap
    return chunks

def main():
    files = sys.argv[1:]
    if not files:
        print("Nessun file specificato.")
        return
    db_path = os.environ.get('VECTOR_DB_PATH', '/data/faiss_index')
    retriever = Retriever(db_path)
    all_embeddings = []
    metadatas = []
    for f in files:
        if f.lower().endswith('.pdf'):
            text = extract_text_from_pdf(f)
        elif f.lower().endswith('.md') or f.lower().endswith('.markdown'):
            text = extract_text_from_md(f)
        else:
            # try reading as text/html
            text = extract_text_from_md(f)
        chunks = chunk_text(text, chunk_size=int(os.environ.get('CHUNK_SIZE',500)), overlap=int(os.environ.get('CHUNK_OVERLAP',50)))
        for c in chunks:
            emb = compute_embedding(c)
            all_embeddings.append(emb)
            metadatas.append({'source': os.path.basename(f), 'text': c})
    retriever.upsert(all_embeddings, metadatas)
    print("Indicizzazione completata.")

if __name__ == '__main__':
    main()
