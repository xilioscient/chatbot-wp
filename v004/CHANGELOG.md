# Changelog

Tutte le modifiche rilevanti al progetto XilioScient Bot saranno documentate in questo file.

Il formato è basato su [Keep a Changelog](https://keepachangelog.com/it/1.0.0/),
e questo progetto aderisce al [Semantic Versioning](https://semver.org/lang/it/).

## [1.0.0] - 2024-01-15

### Aggiunto
- Plugin WordPress completo con interfaccia admin
- Widget chat responsive con design moderno
- Servizio LLM locale con FastAPI e Python
- RAG implementation con FAISS + SQLite
- Supporto per modelli Llama.cpp/GPT4All
- Gestione documenti (PDF, HTML, MD, DOCX)
- Chunking intelligente con overlap
- Embeddings multilingue con Sentence Transformers
- Fallback cloud (OpenAI/Anthropic)
- Rate limiting per protezione da abusi
- Sistema feedback utenti (thumbs up/down)
- Anonimizzazione IP per GDPR
- Dashboard statistiche dettagliate
- Cronologia conversazioni
- Health check endpoints
- JWT authentication tra WordPress e LLM service
- Anti-prompt-injection filters
- Docker Compose orchestration
- Documentazione completa in italiano
- Unit tests per PHP e Python
- Script di backup e installazione

### Caratteristiche
- Privacy-first: tutti i dati restano locali
- GDPR compliant con opzioni di anonimizzazione
- Mobile-first responsive design
- Tema chiaro/scuro
- Posizionamento widget personalizzabile
- Sessioni persistenti con localStorage
- Streaming responses (se supportato dal modello)
- Indicatori typing
- Sources attribution
- Confidence scoring
- Multi-documento retrieval
- Semantic chunking
- Context window management

### Sicurezza
- JWT token authentication
- CSRF protection
- Input sanitization e validation
- Rate limiting
- Prompt injection filtering
- SQL injection prevention
- XSS protection
- HTTPS raccomandato

### Performance
- Embedding caching
- FAISS index optimization
- Connection pooling
- Async processing
- Resource limits in Docker
- Lazy loading components

## [Unreleased]

### Pianificato
- Supporto streaming SSE nel widget
- Integrazione con più vector databases (Weaviate, Milvus)
- Fine-tuning modelli personalizzati
- A/B testing risposte
- Analytics avanzati
- Export conversazioni in più formati
- Integrazione CRM
- Multi-lingua UI
- Voice input/output
- Image understanding
- Sentiment analysis
- Auto-categorizzazione conversazioni
- Scheduled reindexing
- Backup automatico cloud
- Load balancing per high traffic
- Kubernetes deployment
- Prometheus metrics
- Grafana dashboards
- CI/CD pipeline completa

### In Sviluppo
- WebSocket per streaming real-time
- Progressive Web App (PWA)
- Browser extension
- Mobile app nativa

## Note di Versioning

- **MAJOR**: Cambiamenti incompatibili con versioni precedenti
- **MINOR**: Nuove funzionalità backward compatible
- **PATCH**: Bug fixes backward compatible

---

Per dettagli su una versione specifica, consultare i tag Git corrispondenti.
