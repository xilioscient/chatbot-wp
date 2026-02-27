# app/tests/test_api.py
from fastapi.testclient import TestClient
from main import app
import os

client = TestClient(app)

def test_health():
    r = client.get("/api/health")
    assert r.status_code == 200
    assert "status" in r.json()

def test_embeddings_no_auth():
    r = client.post("/api/embeddings", json={"text":"ciao"})
    assert r.status_code == 401
