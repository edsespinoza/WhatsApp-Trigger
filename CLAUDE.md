# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

---

## Contexto do Projeto

**WhatsTrigger** é um SaaS de automação de mensagens WhatsApp embutido na infraestrutura de uma LandPage existente — compartilhando servidor, domínio e banco de dados (MySQL). Público-alvo: compradores de cursos online no Brasil que querem fazer follow-up automático via WhatsApp.

**Status atual:** MVP implementado — backend, fila assíncrona, views Blade e integração com Evolution API estão funcionais.

> Referências de negócio: `apres_.md` (plano completo v1.0) e `usuário_destinatário.txt` (perfil do usuário-alvo).

---

## Arquitetura

### Stack

| Camada | Decisão |
|--------|---------|
| Backend | Laravel 12 (PHP 8.2+) |
| Banco | MySQL — tabelas: `contacts`, `campaigns`, `whatsapp_messages`, `logs`, `subscriptions` |
| Fila assíncrona | Redis (`QUEUE_CONNECTION=redis`) |
| Scheduler | Container `scheduler` (loop `schedule:run` a cada 60s, definido em `routes/console.php`) |
| Conector WhatsApp | Evolution API (self-hosted, porta 8080) |
| Frontend | Blade + Bootstrap 5.3 (CDN) + TailwindCSS 4.0 (Vite) |
| Auth API | Laravel Sanctum — rotas em `/api/whatstrigger/...` (nomeadas `wt.api.*`) |
| Auth Web | Guard `web` (sessão) — rotas em `/whatstrigger/...` (nomeadas `wt.*`) |

### Fluxo de disparo de campanha

```
scheduler container
  └─ schedule:run (a cada minuto)
       └─ whatstrigger:dispatch-due
            └─ DispatchCampaign (job, fila Redis)
                 ├─ verifica quota da assinatura
                 ├─ insere whatsapp_messages em lote
                 └─ SendWhatsAppMessage::dispatch() × N mensagens
                      ├─ delay escalonado: ⌊index / rate_limit⌋ × 60s
                      └─ EvolutionApiService::sendText()
```

### Fluxo de webhook (confirmação de entrega)

```
Evolution API → POST /api/whatstrigger/webhooks/evolution
  └─ VerifyEvolutionWebhook (middleware — valida header 'apikey')
       └─ WebhookController::evolution()
            └─ ProcessEvolutionWebhook (job)
                 └─ atualiza status em WhatsAppMessage (delivered / read)
                      e registra em MessageLog
```

---

## Organização de código

```
app/
  Console/Commands/DispatchDueCampaigns.php  ← artisan whatstrigger:dispatch-due
  Http/Controllers/WhatsTrigger/             ← todos os controllers
    AuthController, ContactController, CampaignController,
    ReportController, WhatsAppController, SubscriptionController,
    WebhookController, WebController (views Blade)
  Http/Middleware/VerifyEvolutionWebhook.php
  Http/Requests/WhatsTrigger/               ← Form Requests
  Jobs/DispatchCampaign.php        ← enfileira mensagens individuais
  Jobs/SendWhatsAppMessage.php     ← envia via EvolutionApiService
  Jobs/ProcessEvolutionWebhook.php ← processa eventos de entrega
  Models/                          ← Contact (tags: JSON), Campaign (status: enum),
                                      WhatsAppMessage (status: enum), MessageLog,
                                      Subscription, User (HasOne Subscription)
  Services/EvolutionApiService.php ← wrapper HTTP para Evolution API;
                                      normaliza números BR antes de enviar
config/whatstrigger.php            ← evolution.url/key/instance_id, rate_limit
routes/whatstrigger.php            ← API routes (Sanctum) — incluídas em routes/api.php
routes/web.php                     ← Web routes (sessão/Blade) — prefixo /whatstrigger
routes/console.php                 ← definição do Schedule::command dispatch-due
database/migrations/               ← 5 migrations prefixadas com 2026_05_01_
resources/views/whatstrigger/      ← layouts/app, auth, dashboard, contacts, campaigns,
                                      whatsapp/connect, subscription
```

---

## MCP Skill Seekers

O servidor MCP Skill Seekers inicia automaticamente via **modo stdio** em cada sessão — nenhuma ação manual é necessária.

- Ferramentas disponíveis: `scrape_codebase`, `scrape_docs`, `create_skill`, `update_skill`, `delete_skill`, `list_skills`, e outras (~34 tools)
- Usar `scrape_codebase` para indexar código-fonte deste projeto
- Usar `scrape_docs` para indexar documentação HTML pública
- Hub de skills instalados: `C:\Users\Espinoza\.skills-hub\`

> Se os tools não aparecerem, reiniciar o Claude Code para reconectar o processo stdio.

---

## Comandos de Desenvolvimento

```bash
make bootstrap       # primeira vez: copia .env, sobe containers, gera key, roda migrations
make up              # docker compose up -d
make down            # parar
make restart         # reiniciar containers
make logs            # seguir logs dos containers app + queue
make shell           # sh dentro do container app
make migrate         # php artisan migrate
make fresh           # migrate:fresh --seed (apaga e recria)
make test            # php artisan test
make test-filter filter=NomeDoTeste   # roda testes filtrados por nome
make tinker          # php artisan tinker
```

Composer scripts (sem Docker — desenvolvimento local):
```bash
composer setup   # install + .env + key:generate + migrate + npm install + build
composer dev     # servidor local concorrente: artisan serve + queue:listen + pail + vite
composer test    # config:clear + php artisan test
```

Frontend:
```bash
npm run dev      # Vite dev server (HMR)
npm run build    # bundle de produção (Vite + TailwindCSS 4)
```

Comando Artisan do módulo:
```bash
php artisan whatstrigger:dispatch-due           # dispara campanhas vencidas
php artisan whatstrigger:dispatch-due --dry-run # lista sem disparar
```

---

## Testes

`phpunit.xml` configura o ambiente de teste com:
- **DB_CONNECTION=sqlite / DB_DATABASE=:memory:** — banco em memória, não precisa do MySQL
- **QUEUE_CONNECTION=sync** — jobs executam de forma síncrona (sem Redis)

Em testes Feature, `SendWhatsAppMessage` é executado imediatamente — mockar `EvolutionApiService` antes de disparar qualquer job que envolva envio real.

---

## Decisões Críticas (invariantes de arquitetura)

**Envio nunca síncrono:** Toda mensagem vai para a fila Redis. Nenhum envio acontece durante a requisição HTTP — sem exceções.

**Anti-duplo-despacho:** `DispatchDueCampaigns` usa `lockForUpdate()` + atualiza status para `sending` dentro de uma transaction antes de enfileirar o job, evitando corrida entre execuções sobrepostas do scheduler.

**Rate limiting por campanha:** `DispatchCampaign` espalha jobs no tempo: `⌊index / messages_per_minute⌋ × 60s`. Padrão 10 msg/min; ajustar via `WT_MESSAGES_PER_MINUTE` conforme aquecimento do número.

**Quota first:** `DispatchCampaign` incrementa `messages_sent` na assinatura antes do envio real para bloquear quota imediatamente e evitar estouro concorrente.

**Isolamento de carga:** As rotas do subsistema devem ter rate limits separados para não sobrecarregar o servidor da LandPage.

**Webhook autenticado sem Sanctum:** A rota `/api/whatstrigger/webhooks/evolution` usa `VerifyEvolutionWebhook` (compara header `apikey` com `WHATSAPP_API_KEY`) — alias registrado em `bootstrap/app.php`.

**Volumes Docker no Windows:** `vendor/` e `bootstrap/cache/` são volumes nomeados Docker (`vendor_data`, `bootstrap_cache`) — não bind mounts — para evitar a lentidão do filesystem Windows em hot paths de I/O.

---

## Registro de Middleware (Laravel 12)

`bootstrap/app.php` já configura:
```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->redirectGuestsTo(fn() => route('wt.login'));
    $middleware->alias([
        'verify.evolution' => \App\Http\Middleware\VerifyEvolutionWebhook::class,
    ]);
})
```

Inclusão das API routes em `routes/api.php`:
```php
require __DIR__ . '/whatstrigger.php';
```

---

## Variáveis de Ambiente

```env
# WhatsApp / Evolution API
WHATSAPP_API_TYPE=evolution
WHATSAPP_API_URL=http://evolution-api:8080
WHATSAPP_API_KEY=
WHATSAPP_INSTANCE_ID=

# Rate limit (padrão 10 msg/min por campanha)
WT_MESSAGES_PER_MINUTE=10

# Banco (compartilhado com LandPage)
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
DB_ROOT_PASSWORD=          # usado apenas no docker-compose para o MySQL

# Fila
REDIS_HOST=localhost
REDIS_PORT=6379
QUEUE_CONNECTION=redis

# Billing (se Stripe)
STRIPE_SECRET_KEY=
STRIPE_PUBLIC_KEY=
```

---

## Planos de Assinatura

| Plano | Preço | Limite | Diferencial |
|-------|-------|--------|-------------|
| Free | R$ 0 | 50 msg/mês | Marca d'água, 1 funil |
| Starter | R$ 29,90/mês | 2.000 msg/mês | Funis ilimitados, suporte e-mail |
| Pro | R$ 79,90/mês | 10.000 msg/mês | API webhooks, tags, relatórios |
| Enterprise | R$ 497/mês + 5% revenda | Sem limite | White-label, revenda para alunos |

Overage: R$ 0,02 por mensagem adicional ao plano.

---

## Portas expostas

| Serviço | Porta |
|---------|-------|
| NGINX (app) | 80 / 443 |
| Evolution API | 8080 |
| MySQL | 3306 |
| Redis | 6379 |
