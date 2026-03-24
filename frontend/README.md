# Frontend: Vue 3 + Module Federation

Монорепозиторий **npm workspaces**: пакеты `@meal/ui-tokens`, `@meal/bff-client` и приложения в `apps/`.

| Приложение | Порт dev | Роль |
|------------|----------|------|
| `host` | 5173 | Shell, загрузка remotes |
| `mf-recipes` | 5174 | Библиотека рецептов (remote) |
| `mf-planner` | 5175 | Планировщик (remote) |
| `mf-shopping` | 5176 | Список покупок (remote) |

## Установка и сборка

```bash
cd frontend
npm install
npm run build
```

`postinstall` собирает `packages/ui-tokens` (копирует `tokens.css` в `dist/`).

## Локальная разработка с микрофронтами

Remotes должны отдавать `remoteEntry.js`. Варианты:

1. **Три терминала + host:** в каждом запустите `npm run dev` в `apps/mf-recipes`, `apps/mf-planner`, `apps/mf-shopping`, затем `npm run dev` в `apps/host`.
2. **Preview после сборки:** `npm run build`, затем в каждом remote `npm run preview` на своём порту, затем `npm run dev` в host.

Переменные окружения для URL `remoteEntry.js` (если порты другие):

- `VITE_MF_RECIPES_URL`
- `VITE_MF_PLANNER_URL`
- `VITE_MF_SHOPPING_URL`

## Продакшен

Задайте переменные сборки host на URL размещённых `remoteEntry.js` (CDN или тот же origin). Браузер пользователя загружает remotes в рантайме.

## BFF

Запросы к API — только на BFF с `credentials: 'include'`. Маршруты и схемы: [contracts/bff-routes.md](../contracts/bff-routes.md), [bff.openapi.yaml](../contracts/openapi/bff.openapi.yaml).

Общий клиент: пакет **`@meal/bff-client`** (`createBffClient`, `resolveBffBaseUrl`). Базовый URL по умолчанию `http://localhost:8080/bff/v1`; переопределение:

- переменная **`VITE_BFF_BASE_URL`** (например в `.env.local` приложения).

При разработке с Vite на портах 5173–5176 браузер ходит на другой origin (BFF на 8080): на **bff-web** задайте **`BFF_CORS_ALLOW_ORIGIN`** (CSV origin’ов, см. `backend/bff-web/.env` и `infra/docker-compose.app.yml`) — включаются `Access-Control-Allow-Origin` (echo), `Access-Control-Allow-Credentials: true` и ответ на `OPTIONS` preflight. Альтернатива — прокси Vite на BFF.
