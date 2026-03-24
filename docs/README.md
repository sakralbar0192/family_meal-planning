# Документация проекта

Материалы для продукта, домена, архитектуры и дизайна веб-приложения планирования питания и рецептов.

## С чего начать

| Роль / задача | Порядок чтения |
|---------------|----------------|
| Продукт, scope, сценарии | [business-doc.md](./business-doc.md) |
| Ограниченные контексты, Event Storming, ubiquitous language | [domain-contexts-event-storming.md](./domain-contexts-event-storming.md) |
| Архитектура, NFR, трассировка к ADR | [architecture-decision-context.md](./architecture-decision-context.md) |
| UX-договорённости, этапы макета/прототипа | [design-plan.md](./design-plan.md) |
| Figma: токены, компоненты, фреймы, прототип, синхронизация | [technical-spec-figma.md](./technical-spec-figma.md) |

Расширенный **индекс документов** с пояснением «когда читать» — в [architecture-decision-context.md](./architecture-decision-context.md) §9.

## Источники правды (без дублирования смысла)

| Тема | Документ |
|------|----------|
| Функциональные и нефункциональные требования, MVP, use cases | [business-doc.md](./business-doc.md) |
| Доменные границы, события, единый язык (гипотезы до воркшопа) | [domain-contexts-event-storming.md](./domain-contexts-event-storming.md) |
| Вход для архитектурных решений и матрица трассировки → ADR | [architecture-decision-context.md](./architecture-decision-context.md) |
| Продуктовый UX и этапы дизайна (1–7) | [design-plan.md](./design-plan.md) |
| Аудит и спецификация макета в Figma (`node id`, чеклист расхождений) | [technical-spec-figma.md](./technical-spec-figma.md) |

Матрица **сценарии × контексты × события** ведётся в одном месте: [domain-contexts-event-storming.md](./domain-contexts-event-storming.md) §4. Архитектурный контекст на неё ссылается, не копируя таблицу.

**Правило сопровождения этапов 1–7:** сначала обновляется [design-plan.md](./design-plan.md) (продукт и UX), затем при изменениях в Figma — [technical-spec-figma.md](./technical-spec-figma.md) (факт файла и идентификаторы). Подробнее — абзац «Слои документации» в начале блока этапов в [technical-spec-figma.md](./technical-spec-figma.md).

Записи **ADR** при появлении можно хранить в `docs/adr/` (папка по необходимости); точка входа для решений — [architecture-decision-context.md](./architecture-decision-context.md).
