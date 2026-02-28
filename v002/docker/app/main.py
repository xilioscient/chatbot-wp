"""
XilioScient Bot - LLM Service
FastAPI application per RAG con modelli locali
"""

import os
import jwt
from datetime import datetime, timedelta
from typing import Optional, List, Dict, Any
from fastapi import FastAPI, HTTPException, Depends, Header, UploadFile, File
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
import logging

from app.embeddings import EmbeddingService
from app.retrieval import RetrievalService
from app.model_inference import ModelService
from app.document_processor import DocumentProcessor

# Configurazione logging
logging.basicConfig(
    level=os.getenv('LOG_LEVEL', 'INFO'),
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Inizializza app
app = FastAPI(
    title="XilioScient Bot LLM Service",
    description="Servizio LLM locale con RAG",
    version="1.0.0"
)

# CORS
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Configurare in produzione
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Modelli Pydantic
class ChatRequest(BaseModel):
    message: str
    session_id: str
    metadata: Optional[Dict[str, Any]] = {}
    top_k: int = 5
    max_tokens: int = 2000

class ChatResponse(BaseModel):
    response: str
    sources: List[Dict[str, str]] = []
    context: List[str] = []
    confidence: Optional[float] = None
    metadata: Optional[Dict[str, Any]] = {}

class IndexRequest(BaseModel):
    file_name: str
    file_content: str  # base64 encoded
    file_url: str
    chunk_size: int = 500
    chunk_overlap: int = 50

class HealthResponse(BaseModel):
    status: str
    model_loaded: bool
    vector_db_ready: bool
    timestamp: str

# Servizi globali
embedding_service: Optional[EmbeddingService] = None
retrieval_service: Optional[RetrievalService] = None
model_service: Optional[ModelService] = None
document_processor: Optional[DocumentProcessor] = None

# JWT Secret
JWT_SECRET = os.getenv('JWT_SECRET', 'your-secret-key-here')

# Verifica JWT
def verify_jwt(authorization: Optional[str] = Header(None)):
    if not authorization:
        raise HTTPException(status_code=401, detail="Authorization header mancante")
    
    try:
        scheme, token = authorization.split()
        if scheme.lower() != 'bearer':
            raise HTTPException(status_code=401, detail="Schema autorizzazione invalido")
        
        payload = jwt.decode(token, JWT_SECRET, algorithms=['HS256'])
        return payload
    except jwt.ExpiredSignatureError:
        raise HTTPException(status_code=401, detail="Token scaduto")
    except jwt.InvalidTokenError:
        raise HTTPException(status_code=401, detail="Token invalido")
    except Exception as e:
        raise HTTPException(status_code=401, detail=f"Errore verifica token: {str(e)}")

@app.on_event("startup")
async def startup_event():
    """Inizializza servizi all'avvio"""
    global embedding_service, retrieval_service, model_service, document_processor
    
    logger.info("Avvio servizi...")
    
    try:
        # Inizializza embedding service
        embedding_service = EmbeddingService()
        await embedding_service.initialize()
        logger.info("Embedding service inizializzato")
        
        # Inizializza retrieval service
        retrieval_service = RetrievalService(embedding_service)
        await retrieval_service.initialize()
        logger.info("Retrieval service inizializzato")
        
        # Inizializza model service
        model_service = ModelService()
        await model_service.initialize()
        logger.info("Model service inizializzato")
        
        # Inizializza document processor
        document_processor = DocumentProcessor(embedding_service, retrieval_service)
        logger.info("Document processor inizializzato")
        
        logger.info("Tutti i servizi avviati con successo")
    except Exception as e:
        logger.error(f"Errore durante l'avvio: {e}")
        raise

@app.get("/api/health", response_model=HealthResponse)
async def health_check():
    """Health check endpoint"""
    return HealthResponse(
        status="ok",
        model_loaded=model_service is not None and model_service.is_loaded(),
        vector_db_ready=retrieval_service is not None and retrieval_service.is_ready(),
        timestamp=datetime.now().isoformat()
    )

@app.post("/api/chat", response_model=ChatResponse)
async def chat(
    request: ChatRequest,
    auth: Dict = Depends(verify_jwt)
):
    """Endpoint principale per chat con RAG"""
    try:
        logger.info(f"Nuova richiesta chat da sessione {request.session_id}")
        
        # 1. Calcola embedding della query
        query_embedding = await embedding_service.embed_text(request.message)
        
        # 2. Retrieval dei documenti rilevanti
        relevant_docs = await retrieval_service.search(
            query_embedding,
            top_k=request.top_k
        )
        
        # 3. Costruisci contesto
        context_texts = [doc['text'] for doc in relevant_docs]
        
        # 4. Costruisci prompt con template
        system_prompt = build_system_prompt()
        user_prompt = build_user_prompt(request.message, context_texts)
        
        # 5. Genera risposta con modello
        response = await model_service.generate(
            system_prompt=system_prompt,
            user_prompt=user_prompt,
            max_tokens=request.max_tokens
        )
        
        # 6. Calcola confidence (se disponibile)
        confidence = calculate_confidence(relevant_docs)
        
        # 7. Prepara sources
        sources = [
            {
                'title': doc.get('title', 'Documento'),
                'url': doc.get('url', ''),
                'score': doc.get('score', 0.0)
            }
            for doc in relevant_docs[:3]
        ]
        
        logger.info(f"Risposta generata con successo per sessione {request.session_id}")
        
        return ChatResponse(
            response=response,
            sources=sources,
            context=context_texts,
            confidence=confidence,
            metadata={
                'model': model_service.model_name,
                'tokens_used': len(response.split()),
                'docs_retrieved': len(relevant_docs)
            }
        )
        
    except Exception as e:
        logger.error(f"Errore in chat endpoint: {e}")
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/api/index")
async def index_document(
    request: IndexRequest,
    auth: Dict = Depends(verify_jwt)
):
    """Indicizza un nuovo documento"""
    try:
        logger.info(f"Indicizzazione documento: {request.file_name}")
        
        # Decodifica contenuto
        import base64
        file_content = base64.b64decode(request.file_content)
        
        # Processa documento
        result = await document_processor.process_document(
            file_name=request.file_name,
            file_content=file_content,
            file_url=request.file_url,
            chunk_size=request.chunk_size,
            chunk_overlap=request.chunk_overlap
        )
        
        logger.info(f"Documento indicizzato: {result['chunks_created']} chunks creati")
        
        return {
            'success': True,
            'document_id': result['document_id'],
            'chunks_created': result['chunks_created'],
            'message': 'Documento indicizzato con successo'
        }
        
    except Exception as e:
        logger.error(f"Errore indicizzazione: {e}")
        raise HTTPException(status_code=500, detail=str(e))

@app.get("/api/documents")
async def get_documents(auth: Dict = Depends(verify_jwt)):
    """Ottieni lista documenti indicizzati"""
    try:
        documents = await retrieval_service.get_documents()
        return {'documents': documents}
    except Exception as e:
        logger.error(f"Errore recupero documenti: {e}")
        raise HTTPException(status_code=500, detail=str(e))

@app.delete("/api/documents/{document_id}")
async def delete_document(
    document_id: str,
    auth: Dict = Depends(verify_jwt)
):
    """Elimina un documento"""
    try:
        await retrieval_service.delete_document(document_id)
        return {'success': True, 'message': 'Documento eliminato'}
    except Exception as e:
        logger.error(f"Errore eliminazione documento: {e}")
        raise HTTPException(status_code=500, detail=str(e))

def build_system_prompt() -> str:
    """Costruisce il system prompt per il modello"""
    return """Sei un assistente AI utile, preciso e cortese. Il tuo compito è rispondere alle domande degli utenti utilizzando il contesto fornito.

REGOLE IMPORTANTI:
1. Basa le tue risposte PRINCIPALMENTE sul contesto fornito
2. Se il contesto non contiene informazioni sufficienti, dillo chiaramente
3. Non inventare informazioni non presenti nel contesto
4. Rispondi sempre in italiano in modo chiaro e conciso
5. Se la domanda non è chiara, chiedi chiarimenti
6. Mantieni un tono professionale ma amichevole
7. Se rilevi contenuti inappropriati, declina educatamente

FORMATO RISPOSTA:
- Risposte dirette e concise
- Usa punti elenco per liste
- Cita le fonti quando appropriato"""

def build_user_prompt(query: str, context: List[str]) -> str:
    """Costruisce lo user prompt con contesto"""
    context_str = "\n\n".join([f"[Fonte {i+1}]: {text}" for i, text in enumerate(context)])
    
    return f"""CONTESTO:
{context_str}

DOMANDA UTENTE:
{query}

Rispondi alla domanda basandoti sul contesto fornito. Se il contesto non contiene informazioni rilevanti, dillo chiaramente."""

def calculate_confidence(documents: List[Dict]) -> float:
    """Calcola confidence score basato sui documenti recuperati"""
    if not documents:
        return 0.0
    
    # Media dei similarity scores
    scores = [doc.get('score', 0.0) for doc in documents]
    return sum(scores) / len(scores) if scores else 0.0

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=5000)
