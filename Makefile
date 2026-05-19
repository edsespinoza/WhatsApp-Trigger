COMPOSE = docker compose
EXEC    = $(COMPOSE) exec app

.PHONY: up down restart logs shell tinker migrate fresh test bootstrap

# ── Ambiente ──────────────────────────────────────────────────────────────────
up:
	$(COMPOSE) up -d

down:
	$(COMPOSE) down

restart:
	$(COMPOSE) restart

logs:
	$(COMPOSE) logs -f app queue

# ── Acesso ao container ───────────────────────────────────────────────────────
shell:
	$(EXEC) sh

tinker:
	$(EXEC) php artisan tinker

# ── Banco de dados ────────────────────────────────────────────────────────────
migrate:
	$(EXEC) php artisan migrate

fresh:
	$(EXEC) php artisan migrate:fresh --seed

# ── Testes ────────────────────────────────────────────────────────────────────
test:
	$(EXEC) php -d memory_limit=512M artisan test

test-filter:
	$(EXEC) php -d memory_limit=512M artisan test --filter $(filter)

# ── Primeira vez ──────────────────────────────────────────────────────────────
bootstrap:
	@test -f .env || cp .env.example .env
	$(COMPOSE) up -d
	@echo "Aguardando MySQL ficar pronto..."
	@sleep 15
	$(EXEC) php artisan key:generate --force
	$(EXEC) php artisan migrate --force
	@echo ""
	@echo "Pronto! Acesse http://localhost"
	@echo "Evolution API: http://localhost:8080"
