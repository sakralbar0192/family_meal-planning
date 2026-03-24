# ADR 0004: Baseline frontend architecture (FSD, shared UI kit, central Storybook, tests)

## Статус

Принято

## Контекст

Текущий frontend реализован как host + MFE, но без единого архитектурного baseline:

- структура приложений преимущественно page-oriented, без явных FSD-слоёв;
- общий UI kit отсутствует, что ведёт к повторению базовых компонентов;
- Storybook для shared UI отсутствует;
- unit/component и screenshot тесты на frontend-уровне не стандартизированы.

Это замедляет масштабирование MFE, увеличивает стоимость UI-поддержки и повышает риск визуальных и поведенческих регрессий.

## Решение

### 1. Зафиксировать frontend baseline

Ввести единые архитектурные правила для всех frontend-приложений:

- FSD-подмножество внутри каждого MFE: `app`, `pages`, `widgets`, `features`, `entities`, `shared`;
- явные границы ответственности между `host` и MFE;
- shared-first подход для переиспользуемого UI.

### 2. Ввести общий пакет `ui-kit`

- Создать/поддерживать единый пакет `frontend/packages/ui-kit`.
- Публичные shared-компоненты и базовые UI-паттерны размещаются в `ui-kit`.
- MFE и host используют `ui-kit`, не копируя базовые primitives локально.

### 3. Central Storybook как UI contract surface

- Ввести центральный Storybook в `frontend/packages/ui-kit`.
- Каждый публичный компонент `ui-kit` обязан иметь stories для основных и edge-case состояний.
- Storybook используется как точка обзора UI-контрактов между командами.

### 4. Test pyramid для frontend

- Unit/component tests обязательны для публичных компонентов `ui-kit`.
- Screenshot tests обязательны для ключевых stories `ui-kit` на mobile/tablet/desktop.
- E2E (Playwright) покрывают сквозные сценарии и интеграцию MFE через host.

### 5. Совместимость с текущими API-ограничениями

- Клиентский код сохраняет boundary: только `/bff/v1` с `credentials: 'include'`.
- Новые BFF endpoints добавляются только через OpenAPI и `contracts/bff-routes.md`.
- Этот ADR не меняет HTTP-контракты сам по себе.

## Последствия

### Положительные

- Единая архитектурная модель для frontend-модулей.
- Быстрее и безопаснее переиспользование UI между MFE.
- Более предсказуемое качество через единые visual и unit quality gates.

### Отрицательные

- Стоимость миграции существующих экранов и компонентов.
- Дополнительная дисциплина и эксплуатационные издержки на stories/tests.

## Связанные документы

- [0001-microservice-boundaries-bff-session-auth.md](./0001-microservice-boundaries-bff-session-auth.md)
- [0003-php-go-split-openapi.md](./0003-php-go-split-openapi.md)
- [frontend-architecture.md](../frontend-architecture.md)
- [design-plan.md](../design-plan.md)
