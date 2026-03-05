"""
Model Inference Service
Gestisce l'inferenza con modelli locali (Llama.cpp) e fallback cloud
"""

import os
import logging
from typing import Optional, Dict, Any
import requests

logger = logging.getLogger(__name__)

class ModelService:
    """Servizio per inferenza con LLM"""
    
    def __init__(self):
        self.model_path = os.getenv('MODEL_PATH', '/data/models/model.gguf')
        self.model_name = "local"
        self.model = None
        self.use_fallback = False
        
        # Configurazione fallback
        self.fallback_provider = os.getenv('FALLBACK_API_PROVIDER', 'none')
        self.fallback_url = os.getenv('FALLBACK_API_URL', '')
        self.fallback_key = os.getenv('FALLBACK_API_KEY', '')
        
    async def initialize(self):
        """Inizializza il modello"""
        try:
            # Tenta di caricare modello locale
            if os.path.exists(self.model_path):
                logger.info(f"Caricamento modello locale: {self.model_path}")
                from llama_cpp import Llama
                
                self.model = Llama(
                    model_path=self.model_path,
                    n_ctx=1024,
                    n_threads=os.cpu_count() or 2,
                    n_gpu_layers=0,  # Usa GPU se disponibile
                    verbose=False
                )
                self.model_name = os.path.basename(self.model_path)
                logger.info(f"Modello locale caricato: {self.model_name}")
            else:
                logger.warning(f"Modello locale non trovato: {self.model_path}")
                
                # Configura fallback se disponibile
                if self.fallback_provider != 'none' and self.fallback_key:
                    self.use_fallback = True
                    logger.info(f"Configurato fallback: {self.fallback_provider}")
                else:
                    raise FileNotFoundError("Nessun modello disponibile")
                    
        except Exception as e:
            logger.error(f"Errore inizializzazione modello: {e}")
            
            # Prova fallback
            if self.fallback_provider != 'none' and self.fallback_key:
                self.use_fallback = True
                logger.info("Uso fallback cloud")
            else:
                raise
    
    async def generate(
        self,
        system_prompt: str,
        user_prompt: str,
        max_tokens: int = 500,
        temperature: float = 0.7,
        top_p: float = 0.9
    ) -> str:
        """
        Genera risposta
        
        Args:
            system_prompt: Prompt di sistema
            user_prompt: Prompt utente
            max_tokens: Max token da generare
            temperature: Temperatura sampling
            top_p: Top-p sampling
            
        Returns:
            str: Risposta generata
        """
        try:
            if self.use_fallback:
                return await self._generate_fallback(system_prompt, user_prompt, max_tokens)
            else:
                return await self._generate_local(system_prompt, user_prompt, max_tokens, temperature, top_p)
                
        except Exception as e:
            logger.error(f"Errore generazione: {e}")
            
            # Prova fallback se disponibile
            if not self.use_fallback and self.fallback_key:
                logger.info("Tentativo fallback dopo errore locale")
                return await self._generate_fallback(system_prompt, user_prompt, max_tokens)
            
            raise
    
    async def _generate_local(
        self,
        system_prompt: str,
        user_prompt: str,
        max_tokens: int,
        temperature: float,
        top_p: float
    ) -> str:
        """Genera con modello locale"""
        if self.model is None:
            raise RuntimeError("Modello locale non caricato")
        
        # Costruisci prompt completo
        full_prompt = f"""<s>[INST] <<SYS>>
{system_prompt}
<</SYS>>

{user_prompt} [/INST]"""
        
        # Genera
        output = self.model(
            full_prompt,
            max_tokens=max_tokens,
            temperature=temperature,
            top_p=top_p,
            stop=["</s>", "[INST]"],
            echo=False
        )
        
        response = output['choices'][0]['text'].strip()
        return response
    
    async def _generate_fallback(
        self,
        system_prompt: str,
        user_prompt: str,
        max_tokens: int
    ) -> str:
        """Genera con API cloud"""
        if self.fallback_provider == 'openai':
            return await self._generate_openai(system_prompt, user_prompt, max_tokens)
        elif self.fallback_provider == 'anthropic':
            return await self._generate_anthropic(system_prompt, user_prompt, max_tokens)
        else:
            raise ValueError(f"Provider fallback non supportato: {self.fallback_provider}")
    
    async def _generate_openai(self, system_prompt: str, user_prompt: str, max_tokens: int) -> str:
        """Genera con OpenAI"""
        url = "https://api.openai.com/v1/chat/completions"
        
        headers = {
            'Content-Type': 'application/json',
            'Authorization': f'Bearer {self.fallback_key}'
        }
        
        data = {
            'model': 'gpt-3.5-turbo',
            'messages': [
                {'role': 'system', 'content': system_prompt},
                {'role': 'user', 'content': user_prompt}
            ],
            'max_tokens': max_tokens,
            'temperature': 0.7
        }
        
        response = requests.post(url, headers=headers, json=data, timeout=30)
        response.raise_for_status()
        
        result = response.json()
        return result['choices'][0]['message']['content']
    
    async def _generate_anthropic(self, system_prompt: str, user_prompt: str, max_tokens: int) -> str:
        """Genera con Anthropic"""
        url = "https://api.anthropic.com/v1/messages"
        
        headers = {
            'Content-Type': 'application/json',
            'x-api-key': self.fallback_key,
            'anthropic-version': '2023-06-01'
        }
        
        data = {
            'model': 'claude-3-haiku-20240307',
            'max_tokens': max_tokens,
            'system': system_prompt,
            'messages': [
                {'role': 'user', 'content': user_prompt}
            ]
        }
        
        response = requests.post(url, headers=headers, json=data, timeout=30)
        response.raise_for_status()
        
        result = response.json()
        return result['content'][0]['text']
    
    def is_loaded(self) -> bool:
        """Verifica se il modello è caricato"""
        return self.model is not None or self.use_fallback
    
    def get_model_info(self) -> Dict[str, Any]:
        """Ritorna info sul modello"""
        return {
            'model_name': self.model_name,
            'is_local': not self.use_fallback,
            'fallback_provider': self.fallback_provider if self.use_fallback else None
        }
