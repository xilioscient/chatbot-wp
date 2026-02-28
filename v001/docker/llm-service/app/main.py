# app/main.py
from fastapi import FastAPI, Request, HTTPException, Header
from pydantic import BaseModel
from fastapi.responses import JSONResponse, StreamingResponse
from .embeddings import compute_embedding
from .retrieval import Retriever
from .model_inference import ModelWrapper
from jose import jwt
import os

app = FastAPI(title="XilioScient LLM Service")

JWT_SECRET = os.environ.get('JWT_SECRET', 'changeme')
LLM_MODEL_PATH = os.environ.get('MODEL_PATH', '/models/model.bin')
VECTOR_DB_PATH = os.environ.get('VECTOR_DB_PATH', '/data/faiss_index')
MAX_CONTEXT_TOKENS = int(os.environ.get('MAX_CONTEXT_TOKENS', '2048'))

retriever = Retriever(VECTOR_DB_PATH)
model = ModelWrapper(LLM_MODEL_PATH)

class ChatRequest(BaseModel):
    message: str
    session_id: str = None
    metadata: dict = None
    top_k: int = 5

@app.get("/api/health")
async def health():
    return {"status": "ok", "model_loaded": model.is_ready()}

def verify_jwt(auth_header: str):
    if not auth_header:
        raise HTTPException(status_code=401, detail="Missing Authorization")
    try:
        token = auth_header.split(" ")[1]
        payload = jwt.decode(token, JWT_SECRET, algorithms=['HS256'])
        return payload
    except Exception as e:
        raise HTTPException(status_code=401, detail="Invalid token")

@app.post("/api/embeddings")
async def embeddings(req: dict, authorization: str = Header(None)):
    verify_jwt(authorization)
    text = req.get('text') or req.get('texts')
    if not text:
        raise HTTPException(status_code=400, detail="Missing text")
    emb = compute_embedding(text)
    return {"embedding": emb}

@app.post("/api/chat")
async def chat_endpoint(chat: ChatRequest, authorization: str = Header(None)):
    verify_jwt(authorization)
    # Compute query embedding
    q_emb = compute_embedding(chat.message)
    # Retrieve top-k
    contexts = retriever.search(q_emb, top_k=chat.top_k)
    # Build prompt
    system_prompt = (
        "Sei un assistente utile. Usa i contesti forniti per rispondere. "
        "Se la risposta non Ã¨ sicura, ammetti incertezza e suggerisci chiarimenti."
    )
    prompt = system_prompt + "\n\n"
    for i, c in enumerate(contexts):
        prompt += f"Contesto {i+1} (source: {c['source']}):\n{c['text']}\n\n"
    prompt += f"Domanda: {chat.message}\nRispondi in modo conciso."
    # Call model
    try:
        reply = model.generate(prompt, max_tokens=512)
    except Exception as e:
        # fallback to cloud if configured
        fallback_url = os.environ.get('FALLBACK_API_URL')
        fallback_key = os.environ.get('FALLBACK_API_KEY')
        if fallback_url and fallback_key:
            # simple fallback
            import requests
            r = requests.post(fallback_url, json={"prompt": prompt, "max_tokens":512}, headers={"Authorization": f"Bearer {fallback_key}"}, timeout=30)
            if r.status_code == 200:
                return {"reply": r.json().get('reply', r.text)}
        raise HTTPException(status_code=500, detail="Model inference failed")
    return {"reply": reply, "contexts": contexts}
