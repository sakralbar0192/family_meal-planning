# OpenAPI: контракты микросервисов

Каждый сервис ведёт спецификацию **OpenAPI 3.x** в этом каталоге: `{{service-name}}.openapi.yaml` (например `recipe-catalog.openapi.yaml`).

## Правила

- Версия API в URL: `/v1/...`.
- Общие соглашения: JSON, UTF-8, даты в ISO 8601 (`date` / `date-time`).
- Ошибки: единый объект проблемы (поля `code`, `message`, опционально `details`), HTTP 4xx/5xx по смыслу.
- Заголовки трассировки: `X-Correlation-Id` (опционально от клиента; иначе генерирует BFF).
- Внутренние вызовы из BFF: `X-User-Id` (доверенный), `X-Internal-Auth` (секрет среды) — не документировать в публичной спеке браузера; описать во внутренней спеке или ADR [0001](../../docs/adr/0001-microservice-boundaries-bff-session-auth.md).

## Шаблон

Скопируйте [template-service.yaml](./template-service.yaml) и замените плейсхолдеры `SERVICE_NAME`, `TITLE`, `DESCRIPTION`.

## Валидация в CI

См. [.github/workflows/ci.yml](../../.github/workflows/ci.yml): шаг `spectral` или `swagger-cli validate` при появлении реальных спецификаций.
