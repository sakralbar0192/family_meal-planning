# Family meal planning

Веб-приложение: рецепты, планировщик питания, список покупок. Документация продукта и домена — в [docs/README.md](docs/README.md).

## Архитектура

- **ADR:** [docs/adr/README.md](docs/adr/README.md)
- **Solution architecture:** [docs/solution-architecture.md](docs/solution-architecture.md)
- **BFF-маршруты:** [contracts/bff-routes.md](contracts/bff-routes.md)
- **OpenAPI:** [contracts/openapi/](contracts/openapi/)

## Репозиторий

| Каталог | Назначение |
|---------|------------|
| `docs/` | Продукт, домен, архитектурный контекст |
| `contracts/` | OpenAPI-шаблоны и соглашения BFF |
| `frontend/` | Vue 3, Module Federation (host + remotes), `@meal/ui-tokens` |
| `e2e/` | Playwright (заготовка под полный стек) |
| `infra/` | Docker Compose: Prometheus, Grafana, Loki, Tempo, OTel Collector |

## Быстрый старт

```bash
# Фронтенд
cd frontend && npm install && npm run build

# Наблюдаемость (опционально)
docker compose -f infra/docker-compose.observability.yml up -d
```

CI: [.github/workflows/ci.yml](.github/workflows/ci.yml) (OpenAPI, сборка фронта, Playwright smoke).
