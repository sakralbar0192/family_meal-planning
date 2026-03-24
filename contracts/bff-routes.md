# BFF: маршруты для веб-клиента и микрофронтов

Браузер (Vue **host** и remotes) обращается **только** к BFF по префиксу `/bff/v1`. Запросы выполняются с **`credentials: 'include'`** (cookie сессии).

Внутренние вызовы BFF → API Gateway → микросервисы не видны клиенту.

**ADR:** [docs/adr/0001-microservice-boundaries-bff-session-auth.md](../docs/adr/0001-microservice-boundaries-bff-session-auth.md)

---

## Соглашение по именованию

| Префикс BFF | Микрофронт (зона) | Назначение |
|-------------|-------------------|------------|
| `/bff/v1/auth/*` | host | Регистрация, вход, выход, смена пароля |
| `/bff/v1/recipes/*` | mf-recipes | Библиотека, карточка, редактор, связанные read-модели |
| `/bff/v1/import/*` | mf-recipes | Импорт по URL (прокси к `recipe-import`, ответ черновика) |
| `/bff/v1/plan/*` | mf-planner | Неделя, дни, слоты, назначения (meal-planning + при необходимости агрегация с catalog) |
| `/bff/v1/shopping/*` | mf-shopping | Формирование списка, строки, отметки, экспорт |

Точные пути и тела — в `contracts/openapi/bff.openapi.yaml`.

---

## Примеры эндпоинтов (MVP, черновик)

### Auth (`mf` → host)

| Метод | Путь | Описание |
|-------|------|----------|
| POST | `/bff/v1/auth/register` | Регистрация email/пароль |
| POST | `/bff/v1/auth/login` | Вход, установка session cookie |
| POST | `/bff/v1/auth/logout` | Инвалидация сессии |
| POST | `/bff/v1/auth/password` | Смена пароля |

### Recipes (`mf-recipes`)

| Метод | Путь | Описание |
|-------|------|----------|
| GET | `/bff/v1/recipes` | Список с фильтрами (query) |
| GET | `/bff/v1/recipes/{id}` | Детали рецепта |
| POST | `/bff/v1/recipes` | Создание |
| PATCH | `/bff/v1/recipes/{id}` | Обновление |
| DELETE | `/bff/v1/recipes/{id}` | Удаление |

### Import (`mf-recipes`)

| Метод | Путь | Описание |
|-------|------|----------|
| POST | `/bff/v1/import/url` | Тело `{ "url": "..." }` → черновик от `recipe-import` |

### Plan (`mf-planner`)

| Метод | Путь | Описание |
|-------|------|----------|
| GET | `/bff/v1/plan/week` | План на неделю (query `anchorDate` + опционально `focusDate`, `recipeSearch`) |
| PATCH | `/bff/v1/plan/slots/{slotId}` | Обновление назначений (DnD); при передаче `expectedVersion` — optimistic lock; при рассинхроне **400** и `code: VERSION_CONFLICT` в теле (прокси к `meal-planning`) |

**Маппинг на `meal-planning`:** `GET /bff/v1/plan/week` → `GET /api/planning/v1/week-plans/current` (с теми же query).

### Shopping (`mf-shopping`)

| Метод | Путь | Описание |
|-------|------|----------|
| POST | `/bff/v1/shopping/build` | Тело `{ "from": "date", "to": "date" }` → формирование списка |
| GET | `/bff/v1/shopping/lists/{id}` | Получить список со строками и флагом `empty` (нет строк снимка) |
| POST | `/bff/v1/shopping/lists/{id}/lines` | Добавить ручную позицию (UC-3) |
| PATCH | `/bff/v1/shopping/lists/{id}/lines/{lineId}` | Отметка «куплено», правки |
| DELETE | `/bff/v1/shopping/lists/{id}/lines/{lineId}` | Удалить строку |

---

## Заголовки

| Заголовок | Кто задаёт | Назначение |
|-----------|------------|------------|
| `Cookie: session_id=...` | Браузер | Сессия после логина |
| `X-Correlation-Id` | Клиент (опционально) или BFF | Сквозная трассировка |
| `X-User-Id` | Только BFF → сервисы | Не принимать с публичного интернета |

---

## Связь с UX из design-plan

- **Период для списка покупок** задаётся в планировщике; BFF принимает даты в `shopping/build` согласно [design-plan.md](../docs/design-plan.md).
- **«В план» с экрана рецепта:** query или state для предзаполненного поиска обрабатывает **mf-planner** + маршрутизация host; BFF может отдать `GET /bff/v1/plan/week?focusDate=&recipeSearch=`.
