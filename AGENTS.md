# AGENTS.md — WhatsTrigger

- **Git remote:** `https://github.com/edsespinoza/WhatsApp-Trigger.git`
- **Branch:** `master`

Referência compacta para sessões OpenCode. Leia `CLAUDE.md` para contexto de negócio, arquitetura completa e stack.

---

## Setup & dev

- **Docker** (recomendado): `make bootstrap` (primeira vez) ou `.\manage.ps1 start` (Windows).
- **Local**: `composer setup` seguido de `composer dev` (server + queue + logs + vite concorrentes).
- Windows Docker: `vendor/` e `bootstrap/cache/` são volumes nomeados (não bind mounts) no docker-compose — não editá-los do host.
- Seeder cria usuário `admin@whatstrigger.test` / `password` com plano **Starter** (2000 msg).

## Comandos-chave

| Comando | O que faz |
|---|---|
| `make test` / `composer test` | Roda PHPUnit (sqlite :memory: + sync queue) |
| `make test-filter filter=Nome` | Teste específico |
| `composer lint` | Verifica estilo (Pint — Laravel preset) |
| `composer format` | Corrige estilo automaticamente |
| `make test-filter filter=Nome` | Teste específico |
| `php artisan whatstrigger:dispatch-due` | Dispara campanhas agendadas vencidas |
| `php artisan whatstrigger:dispatch-due --dry-run` | Lista sem disparar |
| `npm run dev` | Vite HMR |
| `.\manage.ps1 cache-clear` | Reconstrói config/route/view cache |
| `.\manage.ps1 build` | Rebuild da imagem PHP (após composer require/update) |

## Testes

- **DB_CONNECTION=sqlite** + **DB_DATABASE=:memory:** — não precisa de MySQL
- **QUEUE_CONNECTION=sync** — jobs executam síncronos, sem Redis
- Testes Feature usam `RefreshDatabase`
- Mockar `EvolutionApiService` antes de testar jobs que enviam mensagens reais

## Arquitetura (só o essencial)

- **Nunca** enviar síncrono: toda mensagem passa pela fila Redis.
- API routes: `/api/whatstrigger/*` (Sanctum, nomeadas `wt.api.*`) — em `routes/whatstrigger.php`, incluído por `routes/api.php`.
- Web routes: `/whatstrigger/*` (sessão, nomeadas `wt.*`) — em `routes/web.php`.
- Queue monitor: `/whatstrigger/queue/monitor` — fila pendente (Redis) + jobs falhos com retry.
- Webhook logs viewer: `/whatstrigger/webhooks/logs` — filtro por provedor/status + payload JSON no modal.
- Webhook Evolution: `POST /api/whatstrigger/webhooks/evolution` — autenticado por header `apikey` (middleware `VerifyEvolutionWebhook`).
- Webhook Stripe: `POST /api/whatstrigger/webhooks/stripe` — autenticado por assinatura Stripe.
- Webhook logging: middleware `LogWebhook` registra todo webhook recebido em `webhook_logs`.
- Webhook Stripe: `POST /api/whatstrigger/webhooks/stripe` — autenticado por assinatura Stripe.
- Queue monitor: `/whatstrigger/queue/monitor` — fila pendente (Redis) + jobs falhos com retry.
- Webhook logs viewer: `/whatstrigger/webhooks/logs` — filtro por provedor/status + payload JSON no modal.
- Scheduler roda `whatstrigger:dispatch-due` a cada 60s (`routes/console.php`).
- Rate limiting: `⌊index / WT_MESSAGES_PER_MINUTE⌋ × 60s` (padrão 10 msg/min).
- Anti-duplo-despacho: `lockForUpdate()` + transaction em `DispatchDueCampaigns`.
- Auth API: `throttle:5,1` no login, `throttle:3,60` no register. Web: `throttle:10,1` login, `throttle:3,60` register. Tokens Sanctum expiram em 30 dias.
- Security headers: `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: same-origin`, `Permissions-Policy` restrita (middleware `SecurityHeaders` global).

## Models & banco

- `Contact.tags` e `Campaign.target_tags` são **JSON cast** (`array`).
- `Contact.phone` tem unique constraint composta `(user_id, phone)` no banco.
- `User` hasOne `Subscription`; `Subscription.messages_limit = -1` = Enterprise (ilimitado).
- `Campaign.status` e `WhatsAppMessage.status` usam constantes de classe (não enum nativo do PHP).
- `DispatchCampaign` faz `insert()` em lote (não create individual) para grandes listas.
- Import de contatos usa `DB::transaction` + catch `UniqueConstraintViolationException`.

## Serviços

- `EvolutionApiService` — wrapper HTTP para Evolution API (sendText, status, QR Code, disconnect). Normaliza números BR (prefixo 55).
- `StripeService` — checkout session + webhook handler. Desabilitado se `STRIPE_SECRET_KEY` vazio.

## CI/CD

- GitHub Actions em `.github/workflows/ci.yml` — roda testes com MySQL + Redis em containers.
- Trigger: push/PR na branch `main`.

## Convenções de código

- PSR-4: `App\` em `app/`, `Tests\` em `tests/`.
- 4 spaces (`.editorconfig`).
- Sem comentários em código novo (seguir estilo existente).
- `Hash::make()` em vez de `bcrypt()` para password hashing.
