"""
Retrieval Service
Gestisce il vector database con FAISS e metadati con SQLite
"""

import os
import json
import faiss
import numpy as np
import sqlite3
from typing import List, Dict, Optional, Any
from pathlib import Path
import logging

logger = logging.getLogger(__name__)

class RetrievalService:
    """Servizio per retrieval di documenti simili"""
    
    def __init__(self, embedding_service):
        self.embedding_service = embedding_service
        self.vector_db_path = Path(os.getenv('VECTOR_DB_PATH', '/data/vector_db'))
        self.vector_db_path.mkdir(parents=True, exist_ok=True)
        
        self.index_file = self.vector_db_path / 'faiss.index'
        self.metadata_db = self.vector_db_path / 'metadata.db'
        
        self.index = None
        self.conn = None
        
    async def initialize(self):
        """Inizializza il vector database"""
        try:
            # Inizializza SQLite per metadati
            self.conn = sqlite3.connect(str(self.metadata_db), check_same_thread=False)
            self.conn.row_factory = sqlite3.Row
            self._create_tables()
            
            # Carica o crea indice FAISS
            dimension = self.embedding_service.get_dimension()
            
            if self.index_file.exists():
                logger.info("Caricamento indice FAISS esistente")
                self.index = faiss.read_index(str(self.index_file))
            else:
                logger.info("Creazione nuovo indice FAISS")
                self.index = faiss.IndexFlatIP(dimension)  # Inner Product (cosine similarity)
                self._save_index()
            
            logger.info(f"Retrieval service pronto. Documenti indicizzati: {self.index.ntotal}")
            
        except Exception as e:
            logger.error(f"Errore inizializzazione retrieval: {e}")
            raise
    
    def _create_tables(self):
        """Crea tabelle SQLite"""
        cursor = self.conn.cursor()
        
        # Tabella documenti
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS documents (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                document_id TEXT UNIQUE NOT NULL,
                file_name TEXT NOT NULL,
                file_url TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)
        
        # Tabella chunks
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS chunks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                document_id TEXT NOT NULL,
                chunk_index INTEGER NOT NULL,
                text TEXT NOT NULL,
                vector_id INTEGER NOT NULL,
                metadata TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (document_id) REFERENCES documents(document_id)
            )
        """)
        
        self.conn.commit()
    
    async def add_documents(
        self,
        document_id: str,
        file_name: str,
        file_url: str,
        chunks: List[str],
        metadata: Optional[Dict[str, Any]] = None
    ) -> Dict[str, Any]:
        """
        Aggiunge documenti al database
        
        Args:
            document_id: ID univoco documento
            file_name: Nome file
            file_url: URL file
            chunks: Lista di testi chunked
            metadata: Metadati aggiuntivi
            
        Returns:
            Dict con info sull'operazione
        """
        try:
            cursor = self.conn.cursor()
            
            # Inserisci documento
            cursor.execute(
                "INSERT OR REPLACE INTO documents (document_id, file_name, file_url) VALUES (?, ?, ?)",
                (document_id, file_name, file_url)
            )
            
            # Genera embeddings per tutti i chunks
            logger.info(f"Generazione embeddings per {len(chunks)} chunks")
            embeddings = await self.embedding_service.embed_texts(chunks)
            
            # Aggiungi a FAISS
            start_id = self.index.ntotal
            self.index.add(embeddings)
            
            # Salva metadati chunks
            for i, (chunk, embedding) in enumerate(zip(chunks, embeddings)):
                vector_id = start_id + i
                chunk_metadata = json.dumps(metadata) if metadata else None
                
                cursor.execute(
                    """INSERT INTO chunks 
                       (document_id, chunk_index, text, vector_id, metadata) 
                       VALUES (?, ?, ?, ?, ?)""",
                    (document_id, i, chunk, vector_id, chunk_metadata)
                )
            
            self.conn.commit()
            self._save_index()
            
            logger.info(f"Documento {document_id} indicizzato con successo")
            
            return {
                'document_id': document_id,
                'chunks_added': len(chunks),
                'total_vectors': self.index.ntotal
            }
            
        except Exception as e:
            logger.error(f"Errore aggiunta documenti: {e}")
            self.conn.rollback()
            raise
    
    async def search(
        self,
        query_embedding: np.ndarray,
        top_k: int = 5,
        threshold: float = 0.3
    ) -> List[Dict[str, Any]]:
        """
        Cerca documenti simili
        
        Args:
            query_embedding: Embedding della query
            top_k: Numero risultati da ritornare
            threshold: Soglia di similarità minima
            
        Returns:
            Lista di documenti con score
        """
        try:
            if self.index.ntotal == 0:
                logger.warning("Nessun documento nel database")
                return []
            
            # Reshape per FAISS
            query_embedding = query_embedding.reshape(1, -1)
            
            # Search
            scores, indices = self.index.search(query_embedding, top_k)
            
            # Recupera metadati
            results = []
            cursor = self.conn.cursor()
            
            for score, idx in zip(scores[0], indices[0]):
                if idx == -1 or score < threshold:
                    continue
                
                # Recupera chunk
                cursor.execute(
                    "SELECT * FROM chunks WHERE vector_id = ?",
                    (int(idx),)
                )
                row = cursor.fetchone()
                
                if row:
                    # Recupera info documento
                    cursor.execute(
                        "SELECT * FROM documents WHERE document_id = ?",
                        (row['document_id'],)
                    )
                    doc_row = cursor.fetchone()
                    
                    results.append({
                        'text': row['text'],
                        'score': float(score),
                        'document_id': row['document_id'],
                        'chunk_index': row['chunk_index'],
                        'title': doc_row['file_name'] if doc_row else 'Unknown',
                        'url': doc_row['file_url'] if doc_row else '',
                        'metadata': json.loads(row['metadata']) if row['metadata'] else {}
                    })
            
            logger.info(f"Trovati {len(results)} documenti rilevanti")
            return results
            
        except Exception as e:
            logger.error(f"Errore ricerca: {e}")
            raise
    
    async def get_documents(self) -> List[Dict[str, Any]]:
        """Ottieni lista documenti"""
        try:
            cursor = self.conn.cursor()
            cursor.execute("""
                SELECT d.*, COUNT(c.id) as chunk_count
                FROM documents d
                LEFT JOIN chunks c ON d.document_id = c.document_id
                GROUP BY d.document_id
                ORDER BY d.created_at DESC
            """)
            
            documents = []
            for row in cursor.fetchall():
                documents.append({
                    'document_id': row['document_id'],
                    'file_name': row['file_name'],
                    'file_url': row['file_url'],
                    'chunk_count': row['chunk_count'],
                    'created_at': row['created_at']
                })
            
            return documents
            
        except Exception as e:
            logger.error(f"Errore recupero documenti: {e}")
            raise
    
    async def delete_document(self, document_id: str):
        """Elimina documento"""
        try:
            cursor = self.conn.cursor()
            
            # Ottieni vector IDs da eliminare
            cursor.execute(
                "SELECT vector_id FROM chunks WHERE document_id = ?",
                (document_id,)
            )
            vector_ids = [row['vector_id'] for row in cursor.fetchall()]
            
            # Elimina da database
            cursor.execute("DELETE FROM chunks WHERE document_id = ?", (document_id,))
            cursor.execute("DELETE FROM documents WHERE document_id = ?", (document_id,))
            self.conn.commit()
            
            # NOTA: FAISS non supporta eliminazione diretta
            # Bisognerebbe ricostruire l'indice, per ora lasciamo i vettori
            # In produzione: implementare pulizia periodica
            
            logger.info(f"Documento {document_id} eliminato")
            
        except Exception as e:
            logger.error(f"Errore eliminazione documento: {e}")
            self.conn.rollback()
            raise
    
    def _save_index(self):
        """Salva indice FAISS su disco"""
        try:
            faiss.write_index(self.index, str(self.index_file))
        except Exception as e:
            logger.error(f"Errore salvataggio indice: {e}")
    
    def is_ready(self) -> bool:
        """Verifica se il servizio è pronto"""
        return self.index is not None and self.conn is not None
    
    def __del__(self):
        """Chiudi connessione DB"""
        if self.conn:
            self.conn.close()
