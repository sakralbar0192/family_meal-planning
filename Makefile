SHELL := /bin/bash

COMPOSE_FILE ?= infra/docker-compose.app.yml
DC := docker compose -f $(COMPOSE_FILE)

.PHONY: help build up start stop restart down down-v ps logs health frontend-install frontend-build frontend-clean frontend-dev-host frontend-dev-recipes frontend-dev-planner frontend-dev-shopping frontend-dev-all frontend-hybrid frontend-hybrid-bg dev e2e-local e2e-fullstack

help:
	@echo "Family meal planning stack"
	@echo ""
	@echo "Usage:"
	@echo "  make <target> [SERVICE=name] [COMPOSE_FILE=infra/docker-compose.app.yml]"
	@echo ""
	@echo "Targets:"
	@echo "  help      Show this help"
	@echo "  build     Build images from compose file"
	@echo "  up        Build and start services in background, wait for healthchecks"
	@echo "  start     Start existing containers"
	@echo "  stop      Stop running containers"
	@echo "  restart   Restart all or one service (use SERVICE=...)"
	@echo "  down      Stop and remove containers/network"
	@echo "  down-v    Stop and remove containers/network/volumes"
	@echo "  ps        Show compose services status"
	@echo "  logs      Tail logs (all services or SERVICE=...)"
	@echo "  health    Probe BFF health endpoint on localhost:8080"
	@echo "  frontend-install   Install frontend workspace deps"
	@echo "  frontend-build  Build all frontend workspaces"
	@echo "  frontend-clean         Stop all local Vite/preview processes"
	@echo "  frontend-dev-host      Run host dev server (5173)"
	@echo "  frontend-dev-recipes   Run mf-recipes dev server (5174)"
	@echo "  frontend-dev-planner   Run mf-planner dev server (5175)"
	@echo "  frontend-dev-shopping  Run mf-shopping dev server (5176)"
	@echo "  frontend-dev-all       Run all frontend dev servers together"
	@echo "  frontend-hybrid        One command: remotes preview + host dev"
	@echo "  frontend-hybrid-bg     Same as hybrid, but detached to background logs"
	@echo "  dev                    Start backend stack, remotes preview, host dev"
	@echo "  e2e-local       Run smoke/auth/uc e2e (BFF optional mode)"
	@echo "  e2e-fullstack   Run smoke/auth/uc e2e with E2E_FULL_STACK=1"

build:
	$(DC) build

up:
	$(DC) up -d --build --wait

start:
	$(DC) start

stop:
	$(DC) stop

restart:
ifdef SERVICE
	$(DC) restart $(SERVICE)
else
	$(DC) restart
endif

down:
	$(DC) down

down-v:
	$(DC) down -v

ps:
	$(DC) ps

logs:
ifdef SERVICE
	$(DC) logs -f --tail=200 $(SERVICE)
else
	$(DC) logs -f --tail=200
endif

health:
	curl -fsS http://127.0.0.1:8080/bff/v1/health

frontend-install:
	npm install --prefix frontend

frontend-build:
	npm run build --prefix frontend

frontend-clean:
	for p in 5173 5174 5175 5176; do \
		pids=$$(lsof -tiTCP:$$p -sTCP:LISTEN || true); \
		if [ -n "$$pids" ]; then kill $$pids || true; fi; \
	done

frontend-dev-host:
	VITE_BFF_BASE_URL=http://localhost:8080/bff/v1 \
	VITE_MF_RECIPES_URL=http://localhost:5174/assets/remoteEntry.js \
	VITE_MF_PLANNER_URL=http://localhost:5175/assets/remoteEntry.js \
	VITE_MF_SHOPPING_URL=http://localhost:5176/assets/remoteEntry.js \
	npm run dev:host --prefix frontend

frontend-hybrid: frontend-build frontend-clean
	npm run preview --workspace=@meal/mf-recipes --prefix frontend -- --host localhost --port 5174 & \
	npm run preview --workspace=@meal/mf-planner --prefix frontend -- --host localhost --port 5175 & \
	npm run preview --workspace=@meal/mf-shopping --prefix frontend -- --host localhost --port 5176 & \
	VITE_BFF_BASE_URL=http://localhost:8080/bff/v1 \
	VITE_MF_RECIPES_URL=http://localhost:5174/assets/remoteEntry.js \
	VITE_MF_PLANNER_URL=http://localhost:5175/assets/remoteEntry.js \
	VITE_MF_SHOPPING_URL=http://localhost:5176/assets/remoteEntry.js \
	npm run dev:host --prefix frontend & \
	wait

frontend-hybrid-bg: frontend-build frontend-clean
	nohup npm run preview --workspace=@meal/mf-recipes --prefix frontend -- --host localhost --port 5174 > /tmp/fmp-mf-recipes.log 2>&1 &
	nohup npm run preview --workspace=@meal/mf-planner --prefix frontend -- --host localhost --port 5175 > /tmp/fmp-mf-planner.log 2>&1 &
	nohup npm run preview --workspace=@meal/mf-shopping --prefix frontend -- --host localhost --port 5176 > /tmp/fmp-mf-shopping.log 2>&1 &
	nohup env VITE_BFF_BASE_URL=http://localhost:8080/bff/v1 VITE_MF_RECIPES_URL=http://localhost:5174/assets/remoteEntry.js VITE_MF_PLANNER_URL=http://localhost:5175/assets/remoteEntry.js VITE_MF_SHOPPING_URL=http://localhost:5176/assets/remoteEntry.js npm run dev:host --prefix frontend > /tmp/fmp-host.log 2>&1 &
	@echo "Started in background. Logs:"
	@echo "  /tmp/fmp-host.log"
	@echo "  /tmp/fmp-mf-recipes.log"
	@echo "  /tmp/fmp-mf-planner.log"
	@echo "  /tmp/fmp-mf-shopping.log"

frontend-dev-recipes:
	npm run dev:recipes --prefix frontend

frontend-dev-planner:
	npm run dev:planner --prefix frontend

frontend-dev-shopping:
	npm run dev:shopping --prefix frontend

frontend-dev-all:
	npm run dev:recipes --prefix frontend & \
	npm run dev:planner --prefix frontend & \
	npm run dev:shopping --prefix frontend & \
	VITE_BFF_BASE_URL=http://localhost:8080/bff/v1 \
	VITE_MF_RECIPES_URL=http://localhost:5174/assets/remoteEntry.js \
	VITE_MF_PLANNER_URL=http://localhost:5175/assets/remoteEntry.js \
	VITE_MF_SHOPPING_URL=http://localhost:5176/assets/remoteEntry.js \
	npm run dev:host --prefix frontend & \
	wait

dev: up frontend-hybrid

e2e-local:
	npm test --prefix e2e -- --reporter=list tests/smoke-stack.spec.ts tests/auth.spec.ts tests/uc-planner-shopping.spec.ts

e2e-fullstack:
	E2E_FULL_STACK=1 npm test --prefix e2e -- --reporter=list tests/smoke-stack.spec.ts tests/auth.spec.ts tests/uc-planner-shopping.spec.ts
