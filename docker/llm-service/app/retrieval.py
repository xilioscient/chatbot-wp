# app/retrieval.py
import faiss
import numpy as np
import sqlite3
import os
import json

class Retriever:
    def __init__(self, db_path):
        self.index_path = os.path.join(db_path, 'faiss.index')
        self.meta_db = os.path.join(db_path, 'metadata.sqlite')
        self.index = None
        self._load_index()

    def _load_index(self):
        if os.path.exists(self.index_path):
            self.index = faiss.read_index(self.index_path)
        else:
            # empty index
            self.index = None
        # ensure metadata DB exists
        if not os.path.exists(self.meta_db):
            conn = sqlite3.connect(self.meta_db)
            c = conn.cursor()
            c.execute('CREATE TABLE IF NOT EXISTS chunks (id INTEGER PRIMARY KEY, source TEXT, text TEXT)')
            conn.commit()
            conn.close()

    def search(self, query_embedding, top_k=5):
        if self.index is None:
            return []
        q = np.array(query_embedding).astype('float32').reshape(1, -1)
        D, I = self.index.search(q, top_k)
        results = []
        conn = sqlite3.connect(self.meta_db)
        c = conn.cursor()
        for idx in I[0]:
            if idx == -1:
                continue
            c.execute('SELECT source, text FROM chunks WHERE id=?', (int(idx),))
            row = c.fetchone()
            if row:
                results.append({'source': row[0], 'text': row[1]})
        conn.close()
        return results

    def upsert(self, embeddings, metadatas):
        # embeddings: list of vectors, metadatas: list of dicts with source,text
        import faiss
        import numpy as np
        if self.index is None:
            dim = len(embeddings[0])
            self.index = faiss.IndexFlatL2(dim)
        vecs = np.array(embeddings).astype('float32')
        start_id = 0
        # append to index
        self.index.add(vecs)
        # write index
        os.makedirs(os.path.dirname(self.index_path), exist_ok=True)
        faiss.write_index(self.index, self.index_path)
        # write metadata
        conn = sqlite3.connect(self.meta_db)
        c = conn.cursor()
        for m in metadatas:
            c.execute('INSERT INTO chunks (source, text) VALUES (?, ?)', (m.get('source',''), m.get('text','')))
        conn.commit()
        conn.close()
