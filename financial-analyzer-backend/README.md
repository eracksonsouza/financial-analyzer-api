# Financial Analyzer API

<p align="left">
   <img alt="PHP" src="https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white" />
   <img alt="Slim" src="https://img.shields.io/badge/Slim-4-3F3F3F?logo=slim&logoColor=white" />
   <img alt="SQLite" src="https://img.shields.io/badge/SQLite-003B57?logo=sqlite&logoColor=white" />
   <img alt="Docker" src="https://img.shields.io/badge/Docker-2496ED?logo=docker&logoColor=white" />
   <img alt="Claude" src="https://img.shields.io/badge/Anthropic-Claude-101010" />
</p>

API em PHP (Slim 4) para registrar transações, consolidar métricas financeiras e gerar diagnósticos personalizados com apoio da Claude. O fluxo foi pensado para ser simples de integrar com um front-end: você envia renda e despesas, recebe métricas prontas e insights acionáveis.

## ✨ O que esta API entrega

- Cadastro e listagem de transações financeiras.
- Cálculo de métricas (saldo, taxa de poupança, regra 50/30/20, score de saúde financeira).
- Diagnóstico e sugestões com IA (Claude) baseadas nos dados reais do usuário.
- Histórico completo das análises geradas.

## 🧰 Requisitos

- PHP 8.3+
- Composer
- SQLite (local)

## ⚙️ Configuração

1. Instale dependências:
   - `composer install`
2. Configure variáveis de ambiente:
   - copie `.env.example` para `.env`
   - preencha `ANTHROPIC_API_KEY`
   - opcionalmente ajuste `DB_PATH` (padrão: `/data/financial.db`)

> Dica: o banco SQLite é criado automaticamente no primeiro uso.

## ▶️ Rodar localmente

- `php -S localhost:8080 -t public public/index.php`

## 🐳 Rodar com Docker

- `docker build -t financial-analyzer-backend .`
- `docker run --rm -p 8080:8080 -e ANTHROPIC_API_KEY=... -v $(pwd)/data:/data financial-analyzer-backend`

## 🧭 Endpoints

- `GET /health`
- `POST /api/analysis`
- `GET /api/analysis/history`
- `GET /api/analysis/{id}`
- `GET /api/transactions?month=YYYY-MM`
- `POST /api/transactions`

## 🔎 Observações

- CORS está liberado para `*`.
- Para produção, ajuste o middleware de erro e as permissões de CORS.
- O banco SQLite é criado automaticamente no primeiro uso.

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
