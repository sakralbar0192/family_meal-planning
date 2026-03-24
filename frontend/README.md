# Frontend: Vue 3 + Module Federation

Монорепозиторий **npm workspaces**: общий пакет `@meal/ui-tokens` и приложения в `apps/`.

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

Запросы к API — только на BFF с `credentials: 'include'`. Маршруты: [contracts/bff-routes.md](../contracts/bff-routes.md).
