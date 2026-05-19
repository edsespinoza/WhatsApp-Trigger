# WhatsTrigger — Registro de Trabalho

**Última atualização:** 19/05/2026 — 16:25

> **Problema crítico resolvido:** Login não funcionava porque o banco não tinha usuários — o seeder nunca foi executado. `manage.ps1 start` agora roda `migrate --force` + `db:seed --force` automaticamente na subida.

---

## Estado atual

Projeto funcional em Docker. **72 testes passando (130 asserções).**

### O que está pronto

- Docker stack completa: nginx, app (PHP 8.2 FPM), queue worker, scheduler, mysql, redis
- `make bootstrap` / `manage.ps1 start` / `composer dev`
- Migrations + Models + Factories + Seeders
- Auth: Register/Login (API Sanctum + Web session)
- Contatos: CRUD + import em lote (max 500)
- Campanhas: CRUD + agendamento + disparo via fila
- WhatsApp: Conexão Evolution API + QR Code + status polling
- Assinatura: Planos Free/Starter/Pro/Enterprise com quota
- Webhooks: Evolution API + Stripe, com logging estruturado
- Queue monitor: `/whatstrigger/queue/monitor` com retry de falhos
- Webhook logs viewer: `/whatstrigger/webhooks/logs`
- Security headers globais (X-Frame-Options, etc.)
- Rate limiting: web (10/1 login, 3/60 register), API (5/1 login, 3/60 register)
- Páginas de erro customizadas: 403, 404, 500
- CI: GitHub Actions (push/PR em `master`)
- Pint (Laravel preset) configurado

### Pendências / Melhorias futuras

- [ ] Notificações por e-mail ao concluir campanha
- [x] Exportar CSV de contatos (API + web, com BOM, filtros, 7 testes)
- [ ] Exportar CSV de mensagens
- [ ] Dashboard com gráficos (Chart.js)
- [ ] Histórico de alterações nas campanhas
- [ ] Cache Redis para consultas frequentes
- [x] Testes de unidade para os Services (EvolutionApiService — 10 testes)
- [ ] Testes de unidade para StripeService (mockar SDK Stripe)
- [x] Efeito visual SGW Pro na tela de login (ondas azuis animadas + barra de progresso no botão)
- [x] Fix login: `manage.ps1 start` agora migra+seeda automaticamente
- [ ] Swagger / OpenAPI docs dos endpoints
