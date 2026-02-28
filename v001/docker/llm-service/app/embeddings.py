# app/embeddings.py
from sentence_transformers import SentenceTransformer
import numpy as np

# Carica modello di embedding (può essere locale)
EMBED_MODEL = SentenceTransformer('all-MiniLM-L6-v2')

def compute_embedding(text):
    if isinstance(text, list):
        emb = EMBED_MODEL.encode(text, convert_to_numpy=True).tolist()
        return emb
    emb = EMBED_MODEL.encode([text], convert_to_numpy=True)[0].tolist()
    return emb
