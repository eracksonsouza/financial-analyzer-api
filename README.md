# Financial Analyzer API

API em PHP para registrar transações, consolidar métricas financeiras e gerar diagnósticos personalizados com apoio da Claude.

## Stack

- **Backend**: PHP 8.3 + Slim Framework 4 + SQLite + cURL
- **IA**: Claude API (Anthropic)
- **Infra**: Docker Compose

## Estrutura

```
financial-analyzer-api/
├── financial-analyzer-backend/
│   ├── public/index.php
│   ├── src/
│   │   ├── Controllers/
│   │   ├── Services/
│   │   └── Infrastructure/
│   ├── composer.json
│   └── Dockerfile
└── docker-compose.yml
```

## Endpoints

| Método | Rota                  | Descrição                 |
|--------|-----------------------|---------------------------|
| GET    | /health               | Health check              |
| POST   | /api/analysis         | Cria uma nova análise     |
| GET    | /api/analysis/history | Lista análises anteriores |
| GET    | /api/analysis/{id}    | Detalhe de uma análise    |
| GET    | /api/transactions     | Lista transações          |
| POST   | /api/transactions     | Cria uma transação        |

## Como rodar

```bash
cp .env.example .env
# Edite .env com sua ANTHROPIC_API_KEY
docker compose up --build
```

Backend: http://localhost:8080

## Observações

- O banco SQLite é criado automaticamente no primeiro uso.
- O projeto foi mantido apenas como API; não há frontend versionado neste repositório.
