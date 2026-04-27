# Financial Analyzer

Analisador de saúde financeira pessoal com IA. O usuário informa renda e despesas por categoria e recebe um diagnóstico gerado pela Claude API com métricas e melhorias priorizadas.

## Stack

- **Backend**: PHP 8.3 + Slim Framework 4 + SQLite + cURL
- **Frontend**: React 18 + TypeScript + Vite
- **IA**: Claude API (Anthropic)
- **Infra**: Docker Compose

## Estrutura

```
financial-analyzer/
├── backend/
│   ├── public/index.php          # Entry point
│   ├── src/
│   │   ├── Controllers/          # AnalysisController
│   │   ├── Services/             # ClaudeService, AnalysisService
│   │   ├── Infrastructure/       # Database (SQLite)
│   │   ├── dependencies.php
│   │   └── routes.php
│   ├── composer.json
│   └── Dockerfile
├── financial-analyzer-frontend/
│   ├── src/
│   │   ├── components/           # Dashboard, AnalysisForm, AnalysisResult
│   │   ├── services/             # api.ts, auth.ts
│   │   ├── lib/utils.ts
│   │   └── App.tsx
│   ├── package.json
│   └── Dockerfile
└── docker-compose.yml
```

## Frontend (React + Vite)

Interface moderna para análise financeira com foco em usabilidade:

- **Dashboard completo** com métricas, gráficos (donut) e saúde financeira.
- **Simuladores** de cenário ("what if") e projeção de poupança.
- **Histórico de análises** e comparação por período.
- **UI consistente** com componentes reutilizáveis e layout responsivo.

## API Endpoints

| Método | Rota                     | Descrição                  |
|--------|--------------------------|----------------------------|
| GET    | /health                  | Health check               |
| POST   | /api/analysis            | Cria uma nova análise      |
| GET    | /api/analysis/history    | Lista análises anteriores  |
| GET    | /api/analysis/{id}       | Detalhe de uma análise     |

### POST /api/analysis

**Request**
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
    "dividas": 500,
    "outros": 200
  }
}
```

**Response 201**
```json
{
  "id": 1,
  "metrics": {
    "income": 5000,
    "total_expenses": 4050,
    "balance": 950,
    "savings_rate": 19.0,
    "health_score": 72,
    "expense_ratios": { "moradia": 30.0, ... },
    "rule_50_30_20": { "needs_pct": 58.0, "wants_pct": 13.0, "debt_pct": 10.0 }
  },
  "ai": {
    "diagnostico": "...",
    "melhorias": ["...", "...", "..."],
    "score_label": "Regular"
  }
}
```

## Como rodar

```bash
# 1. Clone e configure
cp .env.example .env
# Edite .env com sua ANTHROPIC_API_KEY

# 2. Suba os containers
docker compose up --build

# Frontend: http://localhost:5173
# Backend:  http://localhost:8080
```

## Decisões técnicas

- **SQLite** em vez de PostgreSQL — portfólio local não precisa de um servidor de banco separado; facilita setup e demonstração
- **Slim 4** em vez de Laravel — overhead zero para uma API REST simples, mostra domínio do PHP sem depender de magia de framework
- **cURL nativo** para chamar a Claude API — sem dependência extra de HTTP client, demonstra conhecimento de PHP puro
- **React + Vite** separado da API — arquitetura real de produto, não monolito PHP com templates
