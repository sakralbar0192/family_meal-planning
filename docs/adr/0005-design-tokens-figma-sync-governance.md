# ADR 0005: Governance дизайн-токенов и синхронизация с Figma MCP

## Статус

Принято

## Контекст

В проекте уже есть `frontend/packages/ui-tokens`, а дизайн-спецификация ведётся в Figma и документируется в `docs/technical-spec-figma.md`. При этом:

- нет единого формального процесса изменения токенов;
- есть риск расхождения между Figma и кодовыми токенами;
- встречаются hardcoded style values, обходящие токены.

Нужен управляемый процесс синхронизации и единые правила для design/frontend команд.

## Решение

### 1. Источники правды и роли

- `docs/technical-spec-figma.md` + Figma MCP — каноничный дизайн-контекст по токенам и UI-мастерам.
- `frontend/packages/ui-tokens` — каноничная кодовая проекция токенов для runtime.
- `frontend/packages/ui-kit` и MFE используют только семантические токены из `ui-tokens`.

### 2. Change workflow для токенов

Каждое изменение токена проходит последовательность:

1. **Propose**: причина и ожидаемый эффект (UX/бренд/a11y/адаптив).
2. **Review**: согласование design + frontend.
3. **Sync**: обновление Figma и `ui-tokens` без расхождения имён/семантики.
4. **Verify**: проверка stories, screenshot и UI parity-checklist.

### 3. Правила использования токенов

- Shared компоненты не используют hardcoded цвета/spacing/радиусы/типографику, если для них есть семантический токен.
- Допустимые исключения документируются явно (например, third-party constraints или временный техдолг).
- Брейкпоинты и touch target следуют значениям, зафиксированным в токенах и документации.

### 4. CI/quality policy (целевая)

В качестве целевого governance-гейта фиксируются:

- token drift check между Figma-спекой и кодовыми токенами;
- Storybook/visual checks для `ui-kit`;
- тесты, подтверждающие отсутствие критичных визуальных регрессий при смене токенов.

Этот ADR фиксирует policy и не требует немедленного внедрения полной автоматизации в рамках текущей итерации документации.

## Последствия

### Положительные

- Снижается риск рассинхронизации Figma и кода.
- Появляется прозрачный процесс токен-изменений.
- Упрощается поддержка светлой/тёмной темы и адаптивных правил.

### Отрицательные

- Изменение токенов требует межкомандного review.
- На переходном этапе возможен инвентаризационный техдолг по hardcoded стилям.

## Связанные документы

- [0004-frontend-architecture-baseline-fsd-uikit-storybook-tests.md](./0004-frontend-architecture-baseline-fsd-uikit-storybook-tests.md)
- [technical-spec-figma.md](../technical-spec-figma.md)
- [ui-parity-checklist.md](../ui-parity-checklist.md)
- [frontend-architecture.md](../frontend-architecture.md)
