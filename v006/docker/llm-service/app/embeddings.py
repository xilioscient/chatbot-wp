"""
Embedding Service
Gestisce il calcolo degli embeddings per testi
"""

import os
import numpy as np
from typing import List, Union
from sentence_transformers import SentenceTransformer
import logging

logger = logging.getLogger(__name__)

class EmbeddingService:
    """Servizio per generare embeddings di testi"""
    
    def __init__(self):
        self.model_name = os.getenv('EMBEDDING_MODEL', 'sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2')
        self.model = None
        self.dimension = 384  # Dimensione default per MiniLM
        
    async def initialize(self):
        """Inizializza il modello di embedding"""
        try:
            logger.info(f"Caricamento modello embedding: {self.model_name}")
            self.model = SentenceTransformer(self.model_name)
            self.dimension = self.model.get_sentence_embedding_dimension()
            logger.info(f"Modello caricato. Dimensione embeddings: {self.dimension}")
        except Exception as e:
            logger.error(f"Errore caricamento modello embedding: {e}")
            raise
    
    async def embed_text(self, text: str) -> np.ndarray:
        """
        Genera embedding per un singolo testo
        
        Args:
            text: Testo da convertire in embedding
            
        Returns:
            np.ndarray: Vettore embedding
        """
        if self.model is None:
            raise RuntimeError("Modello non inizializzato")
        
        try:
            # Normalizza testo
            text = text.strip()
            if not text:
                return np.zeros(self.dimension)
            
            # Genera embedding
            embedding = self.model.encode(text, convert_to_numpy=True)
            
            # Normalizza vettore
            embedding = embedding / np.linalg.norm(embedding)
            
            return embedding
            
        except Exception as e:
            logger.error(f"Errore generazione embedding: {e}")
            raise
    
    async def embed_texts(self, texts: List[str], batch_size: int = 32) -> np.ndarray:
        """
        Genera embeddings per più testi in batch
        
        Args:
            texts: Lista di testi
            batch_size: Dimensione batch
            
        Returns:
            np.ndarray: Array di embeddings
        """
        if self.model is None:
            raise RuntimeError("Modello non inizializzato")
        
        try:
            # Normalizza testi
            texts = [t.strip() for t in texts]
            
            # Genera embeddings in batch
            embeddings = self.model.encode(
                texts,
                batch_size=batch_size,
                convert_to_numpy=True,
                show_progress_bar=len(texts) > 100
            )
            
            # Normalizza vettori
            norms = np.linalg.norm(embeddings, axis=1, keepdims=True)
            embeddings = embeddings / norms
            
            return embeddings
            
        except Exception as e:
            logger.error(f"Errore generazione embeddings batch: {e}")
            raise
    
    def get_dimension(self) -> int:
        """Ritorna la dimensione degli embeddings"""
        return self.dimension
    
    def is_ready(self) -> bool:
        """Verifica se il servizio è pronto"""
        return self.model is not None
