# E2E (Playwright)

## Локальный прогон с docker-compose и Vite

1. Поднять бэкенд:

   ```bash
   docker compose -f infra/docker-compose.app.yml up -d
   ```

   Дождаться готовности сервисов (Postgres healthchecks, BFF на `http://localhost:8080`).

2. Запустить фронт (host и при необходимости remotes на 5174–5176):

   ```bash
   cd frontend
   npm run dev --workspace=@meal/host
   ```

   Убедитесь, что в BFF задан `BFF_CORS_ALLOW_ORIGIN` с origin вашего host (по умолчанию в `backend/bff-web/.env` уже есть `localhost` и `127.0.0.1` для портов 5173–5176).

3. Переменные окружения (опционально):

   - `PLAYWRIGHT_BASE_URL` — URL host (по умолчанию `http://localhost:5173`)
   - `E2E_BFF_BASE_URL` — база BFF для проверки перед тестами (по умолчанию `http://localhost:8080/bff/v1`)

4. Запуск:

   ```bash
   cd e2e
   npm ci
   npx playwright install chromium
   npm test
   ```

Если BFF не запущен, сценарии из `tests/auth.spec.ts` будут **пропущены** (skip), чтобы CI без стека не падал.

## CI

Job `e2e` в `.github/workflows/ci.yml` выполняет тесты; при отсутствии полного стека ожидаются пропуски auth-сценариев. Для жёсткой проверки поднимайте compose локально или добавьте отдельный workflow с `services`/`docker compose`.
