"""
Document Processor
Estrae testo da vari formati e crea chunks
"""

import os
import re
import hashlib
import logging
from typing import List, Dict, Any, Optional
from io import BytesIO

logger = logging.getLogger(__name__)

class DocumentProcessor:
    """Processa documenti per indicizzazione"""
    
    def __init__(self, embedding_service, retrieval_service):
        self.embedding_service = embedding_service
        self.retrieval_service = retrieval_service
    
    async def process_document(
        self,
        file_name: str,
        file_content: bytes,
        file_url: str,
        chunk_size: int = 500,
        chunk_overlap: int = 50
    ) -> Dict[str, Any]:
        """
        Processa un documento completo
        
        Args:
            file_name: Nome file
            file_content: Contenuto binario
            file_url: URL file
            chunk_size: Dimensione chunk in caratteri
            chunk_overlap: Overlap tra chunks
            
        Returns:
            Dict con risultati processamento
        """
        try:
            # Genera ID univoco
            document_id = self._generate_document_id(file_name, file_content)
            
            # Estrai testo
            text = await self._extract_text(file_name, file_content)
            
            if not text.strip():
                raise ValueError("Nessun testo estratto dal documento")
            
            # Crea chunks
            chunks = self._create_chunks(text, chunk_size, chunk_overlap)
            
            logger.info(f"Documento {file_name}: {len(text)} caratteri, {len(chunks)} chunks")
            
            # Indicizza
            result = await self.retrieval_service.add_documents(
                document_id=document_id,
                file_name=file_name,
                file_url=file_url,
                chunks=chunks,
                metadata={
                    'file_size': len(file_content),
                    'chunk_size': chunk_size,
                    'chunk_overlap': chunk_overlap
                }
            )
            
            return {
                'document_id': document_id,
                'chunks_created': len(chunks),
                'text_length': len(text)
            }
            
        except Exception as e:
            logger.error(f"Errore processamento documento: {e}")
            raise
    
    async def _extract_text(self, file_name: str, file_content: bytes) -> str:
        """Estrae testo dal file"""
        extension = os.path.splitext(file_name)[1].lower()
        
        if extension == '.pdf':
            return await self._extract_from_pdf(file_content)
        elif extension in ['.txt', '.md', '.markdown']:
            return file_content.decode('utf-8', errors='ignore')
        elif extension in ['.html', '.htm']:
            return await self._extract_from_html(file_content)
        elif extension == '.docx':
            return await self._extract_from_docx(file_content)
        else:
            # Prova come testo
            try:
                return file_content.decode('utf-8', errors='ignore')
            except:
                raise ValueError(f"Formato file non supportato: {extension}")
    
    async def _extract_from_pdf(self, file_content: bytes) -> str:
        """Estrae testo da PDF"""
        try:
            from pypdf import PdfReader
            
            pdf = PdfReader(BytesIO(file_content))
            text = []
            
            for page in pdf.pages:
                page_text = page.extract_text()
                if page_text:
                    text.append(page_text)
            
            return '\n\n'.join(text)
            
        except Exception as e:
            logger.error(f"Errore estrazione PDF: {e}")
            raise
    
    async def _extract_from_html(self, file_content: bytes) -> str:
        """Estrae testo da HTML"""
        try:
            from bs4 import BeautifulSoup
            
            soup = BeautifulSoup(file_content, 'html.parser')
            
            # Rimuovi script e style
            for script in soup(["script", "style"]):
                script.decompose()
            
            # Estrai testo
            text = soup.get_text()
            
            # Pulisci
            lines = (line.strip() for line in text.splitlines())
            chunks = (phrase.strip() for line in lines for phrase in line.split("  "))
            text = '\n'.join(chunk for chunk in chunks if chunk)
            
            return text
            
        except Exception as e:
            logger.error(f"Errore estrazione HTML: {e}")
            raise
    
    async def _extract_from_docx(self, file_content: bytes) -> str:
        """Estrae testo da DOCX"""
        try:
            from docx import Document
            
            doc = Document(BytesIO(file_content))
            text = []
            
            for paragraph in doc.paragraphs:
                if paragraph.text.strip():
                    text.append(paragraph.text)
            
            return '\n\n'.join(text)
            
        except Exception as e:
            logger.error(f"Errore estrazione DOCX: {e}")
            raise
    
    def _create_chunks(
        self,
        text: str,
        chunk_size: int,
        chunk_overlap: int
    ) -> List[str]:
        """
        Crea chunks di testo con overlap
        
        Args:
            text: Testo da chunkare
            chunk_size: Dimensione target chunk
            chunk_overlap: Overlap tra chunks
            
        Returns:
            Lista di chunks
        """
        # Pulisci testo
        text = self._clean_text(text)
        
        # Split in sentences per chunking semantico
        sentences = self._split_sentences(text)
        
        chunks = []
        current_chunk = []
        current_length = 0
        
        for sentence in sentences:
            sentence_length = len(sentence)
            
            # Se la sentence da sola supera chunk_size, spezzala
            if sentence_length > chunk_size:
                # Salva chunk corrente se presente
                if current_chunk:
                    chunks.append(' '.join(current_chunk))
                    current_chunk = []
                    current_length = 0
                
                # Spezza sentence lunga
                words = sentence.split()
                temp_chunk = []
                temp_length = 0
                
                for word in words:
                    word_length = len(word) + 1  # +1 per spazio
                    if temp_length + word_length > chunk_size and temp_chunk:
                        chunks.append(' '.join(temp_chunk))
                        # Overlap: mantieni ultime parole
                        overlap_words = int(len(temp_chunk) * (chunk_overlap / chunk_size))
                        temp_chunk = temp_chunk[-overlap_words:] if overlap_words > 0 else []
                        temp_length = sum(len(w) + 1 for w in temp_chunk)
                    
                    temp_chunk.append(word)
                    temp_length += word_length
                
                if temp_chunk:
                    chunks.append(' '.join(temp_chunk))
                
                continue
            
            # Aggiungi sentence al chunk corrente
            if current_length + sentence_length <= chunk_size:
                current_chunk.append(sentence)
                current_length += sentence_length + 1  # +1 per spazio
            else:
                # Salva chunk corrente
                if current_chunk:
                    chunks.append(' '.join(current_chunk))
                
                # Inizia nuovo chunk con overlap
                overlap_sentences = []
                overlap_length = 0
                
                for sent in reversed(current_chunk):
                    sent_length = len(sent) + 1
                    if overlap_length + sent_length <= chunk_overlap:
                        overlap_sentences.insert(0, sent)
                        overlap_length += sent_length
                    else:
                        break
                
                current_chunk = overlap_sentences + [sentence]
                current_length = overlap_length + sentence_length + 1
        
        # Aggiungi ultimo chunk
        if current_chunk:
            chunks.append(' '.join(current_chunk))
        
        return chunks
    
    def _clean_text(self, text: str) -> str:
        """Pulisce il testo"""
        # Rimuovi whitespace multipli
        text = re.sub(r'\s+', ' ', text)
        
        # Rimuovi caratteri speciali problematici
        text = re.sub(r'[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F-\x9F]', '', text)
        
        return text.strip()
    
    def _split_sentences(self, text: str) -> List[str]:
        """Split testo in frasi"""
        # Pattern per split su punteggiatura
        pattern = r'(?<=[.!?])\s+(?=[A-Z])'
        sentences = re.split(pattern, text)
        
        # Filtra frasi vuote e troppo corte
        sentences = [s.strip() for s in sentences if len(s.strip()) > 10]
        
        return sentences
    
    def _generate_document_id(self, file_name: str, file_content: bytes) -> str:
        """Genera ID univoco per documento"""
        content_hash = hashlib.sha256(file_content).hexdigest()
        return f"{file_name}_{content_hash[:12]}"
