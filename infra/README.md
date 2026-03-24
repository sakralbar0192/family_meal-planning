# Инфраструктура наблюдаемости

## Запуск стека

Из корня репозитория:

```bash
docker compose -f infra/docker-compose.observability.yml up -d
```

## Запуск app-стека (MVP backend)

```bash
docker compose -f infra/docker-compose.app.yml up -d --build
```

| Сервис | URL | Учётные данные |
|--------|-----|----------------|
| Grafana | http://localhost:3000 | `admin` / `admin` (сменить в проде) |
| Prometheus | http://localhost:9090 | — |
| Loki API | http://localhost:3100 | — |
| Tempo | http://localhost:3200 | — |
| OTLP (приложения → collector) | `localhost:14317` (gRPC), `localhost:14318` (HTTP) | — |

Приложения отправляют трейсы на **OTel Collector** (`14317`/`14318`), а не напрямую в Tempo; коллектор проксирует в Tempo.

## Prometheus

Добавьте job’ы в [prometheus/prometheus.yml](./prometheus/prometheus.yml), когда сервисы начнут отдавать `/metrics`.

## Loki и Promtail

В compose поднят **Loki**; поток логов из Docker можно подключить отдельным **Promtail** с `docker_sd_config` и монтированием `/var/run/docker.sock` (не включено по умолчанию, чтобы стек поднимался без root-доступа к сокету). Альтернатива — писать JSON-логи в файл и читать их Promtail’ом.

## Файлы

- `tempo/tempo.yaml` — OTLP и хранение трейсов локально.
- `otel-collector/config.yaml` — приём OTLP от приложений, экспорт в Tempo.
- `grafana/provisioning/datasources/datasources.yml` — Prometheus, Loki, Tempo.
