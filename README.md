# Financial Analyzer API

<p align="left">
   <img alt="PHP" src="https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white" />
   <img alt="Slim" src="https://img.shields.io/badge/Slim-4-3F3F3F?logo=slim&logoColor=white" />
  <img alt="PostgreSQL" src="https://img.shields.io/badge/PostgreSQL-4169E1?logo=postgresql&logoColor=white" />
   <img alt="Docker" src="https://img.shields.io/badge/Docker-2496ED?logo=docker&logoColor=white" />
   <img alt="OpenAI Compatible" src="https://img.shields.io/badge/AI-OpenAI%20Compatible-10A37F?logo=openai&logoColor=white" />
</p>

API em PHP (Slim 4) para registrar transações, consolidar métricas financeiras e gerar diagnósticos personalizados com apoio de IA. O fluxo foi pensado para ser simples de integrar com um front-end: você envia renda e despesas, recebe métricas prontas e insights acionáveis.

## ✨ O que esta API entrega

- Cadastro e listagem de transações financeiras.
- Cálculo de métricas (saldo, taxa de poupança, regra 50/30/20, score de saúde financeira).
- Diagnóstico e sugestões com IA baseadas nos dados reais do usuário.
- Histórico completo das análises geradas.

## 🤖 Provedor de IA

A integração usa o formato **OpenAI Chat Completions**, então funciona com qualquer provedor compatível:

- OpenAI (`https://api.openai.com/v1/chat/completions`)
- OpenRouter (`https://openrouter.ai/api/v1/chat/completions`)
- Groq (`https://api.groq.com/openai/v1/chat/completions`)
- DeepSeek (`https://api.deepseek.com/v1/chat/completions`)
- Ollama local (`http://localhost:11434/v1/chat/completions`)
- Qualquer outro endpoint compatível

Basta apontar `AI_API_URL` para o provedor desejado.

## 🧰 Requisitos

- PHP 8.3+ e Composer (para rodar local), **ou**
- Docker + Docker Compose (recomendado).

## ⚙️ Configuração

1. Copie `.env.example` para `.env`:
   ```
   cp .env.example .env
   ```
2. Preencha as variáveis:
   - `AI_API_KEY` — chave do provedor de IA (obrigatório)
   - `AI_API_URL` — endpoint compatível com OpenAI (opcional, default: OpenAI)
   - `AI_MODEL` — modelo a ser usado (opcional, default: `gpt-4o-mini`)
  - `DATABASE_URL` — string de conexão do PostgreSQL (obrigatório)

> Dica: com Docker Compose, o Postgres sobe automaticamente.

## 🐳 Rodar com Docker (recomendado)

```bash
docker compose up --build -d
```

A API ficará disponível em `http://localhost:8080`.

Para parar:
```bash
docker compose down
```

## ▶️ Rodar localmente (sem Docker)

```bash
composer install
php -S localhost:8080 -t public public/index.php
```

## 🚀 Deploy no Railway

Este projeto já inclui `Dockerfile`, então o Railway consegue fazer deploy via Docker automaticamente.

### 1) Criar o serviço

- No Railway: **New Project** → **Deploy from GitHub repo** → selecione este repositório.
- O Railway deve detectar o `Dockerfile` e buildar a imagem.

### 2) Configurar variáveis (obrigatório)

No serviço criado, vá em **Variables** e configure:

- `AI_API_KEY` (obrigatório)
- `AI_API_URL` (opcional, default: `https://api.openai.com/v1/chat/completions`)
- `AI_MODEL` (opcional, default: `gpt-4o-mini`)
- `APP_DEBUG` (recomendado `false` em produção)
- `CORS_ALLOWED_ORIGINS` (recomendado setar seu domínio do front)

E configure também:

- `DATABASE_URL` (obrigatório)

> Se você adicionar um PostgreSQL no Railway (Add → Database → PostgreSQL), ele normalmente injeta `DATABASE_URL` automaticamente.

> O Railway injeta a variável `PORT` automaticamente. O container já faz bind em `0.0.0.0:$PORT`.

### 3) Verificar health check

- Após o deploy, abra a URL pública do Railway e teste:
  - `GET /health`


## 🧭 Endpoints

| Método | Rota                          | Descrição                                |
| ------ | ----------------------------- | ---------------------------------------- |
| GET    | `/health`                     | Health check                             |
| POST   | `/api/analysis`               | Cria análise financeira com IA           |
| GET    | `/api/analysis/history`       | Lista histórico de análises              |
| GET    | `/api/analysis/{id}`          | Detalha uma análise específica           |
| GET    | `/api/transactions?month=YYYY-MM` | Lista transações (filtro opcional por mês) |
| POST   | `/api/transactions`           | Cria uma transação                       |

### Exemplo: `POST /api/analysis`

```json
{
  "income": 5000,
  "expenses": {
    "moradia": 1500,
    "alimentacao": 800,
    "transporte": 400,
    "saude": 200,
    "lazer": 300,
    "educacao": 150,
    "dividas": 600,
    "outros": 250
  }
}
```

Resposta:
```json
{
  "id": 1,
  "metrics": {
    "income": 5000,
    "total_expenses": 4200,
    "balance": 800,
    "savings_rate": 16,
    "expense_ratios": { "moradia": 30, "alimentacao": 16, "...": "..." },
    "rule_50_30_20": { "needs_pct": 58, "wants_pct": 14, "debt_pct": 12 },
    "health_score": 85
  },
  "ai": {
    "diagnostico": "Sua saúde financeira é boa...",
    "melhorias": ["...", "...", "..."],
    "score_label": "Regular"
  }
}
```

## 🔎 Observações

- CORS está liberado para `*`. Para produção, restrinja a origem.
- Em produção, desligue o `displayErrorDetails` no `addErrorMiddleware`.
- Em produção, prefira PostgreSQL (persistência, concorrência e backups).

## 📦 Estrutura do projeto

```
public/                # Bootstrap do Slim e middlewares
src/Controllers/       # Endpoints da API
src/Services/          # Regras de negócio e integração com IA
src/Infrastructure/    # Persistência/DB
```

## 💡 Próximas ideias

- Autenticação JWT para multiusuário.
- Paginação e filtros avançados para histórico.
- Exportação de análises em PDF.
