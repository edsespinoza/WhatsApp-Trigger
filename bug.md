# WhatsTrigger — Registro de Trabalho e Instruções de Retomada

**Última sessão:** 09/05/2026  
**Motivo da pausa:** Problema de espaço no SSD — necessário liberar espaço antes de instalar Docker Desktop (~600 MB instalador + ~2–3 GB de imagens Docker)

---

## Estado atual do projeto

### O que já está pronto (código)

- `docker-compose.yml` — stack completa: nginx, app (PHP-FPM), queue worker, scheduler, mysql, redis, evolution-api
- `docker/php/Dockerfile` — imagem PHP 8.2 Alpine com extensões pdo_mysql, mbstring, bcmath, gd, redis
- `docker/php/entrypoint.sh` — bootstrap automático do Laravel na 1ª execução
- `docker/nginx/default.conf` — configuração NGINX apontando para Laravel
- `Makefile` — todos os comandos encapsulados
- `.env.example` — variáveis de ambiente com valores padrão para desenvolvimento
- Migrations (5 tabelas): contacts, campaigns, whatsapp_messages, logs, subscriptions
- Models: Contact, Campaign, WhatsAppMessage, MessageLog, Subscription
- Services: EvolutionApiService
- Jobs: DispatchCampaign, SendWhatsAppMessage, ProcessEvolutionWebhook
- Controllers API (Sanctum): Auth, Contact, Campaign, Report, WhatsApp, Subscription, Webhook
- Controller Web (Blade): WebController
- Middleware: VerifyEvolutionWebhook
- Artisan command: `whatstrigger:dispatch-due`
- Views Blade: login, register, dashboard, contacts, campaigns, whatsapp/connect, subscription
- Rotas API: `routes/whatstrigger.php`
- Rotas Web: `routes/web.php`
- Config: `config/whatstrigger.php`

### O que NÃO foi feito ainda

- [ ] Docker Desktop não está instalado (bloqueado por falta de espaço no SSD)
- [ ] `.env` não foi criado (copiar de `.env.example`)
- [ ] Containers nunca foram iniciados
- [ ] Migrations nunca rodaram
- [ ] `WHATSAPP_INSTANCE_ID` não foi definido (definir após subir a Evolution API)
- [ ] Middleware `VerifyEvolutionWebhook` não foi registrado no `bootstrap/app.php`
- [ ] `routes/whatstrigger.php` não foi incluído em `routes/api.php`

---

## Pendências técnicas do código

### 1. Registrar middleware no bootstrap/app.php (Laravel 11+)

Adicionar em `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'verify.evolution' => \App\Http\Middleware\VerifyEvolutionWebhook::class,
    ]);
})
```

### 2. Incluir rotas API em routes/api.php

```php
require __DIR__ . '/whatstrigger.php';
```

### 3. Registrar comando no scheduler (routes/console.php)

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('whatstrigger:dispatch-due')->everyMinute();
```

---

## Plano de retomada — passo a passo

### Passo 1 — Liberar espaço no SSD (antes de tudo)

Espaço necessário estimado:
- Docker Desktop instalador: ~600 MB
- Imagens Docker baixadas: ~2–3 GB
- Volume MySQL + dados: ~500 MB
- **Total recomendado livre: 5 GB**

Sugestões para liberar espaço:
- Esvaziar Lixeira
- Limpar pasta `%TEMP%` (`Win+R` → `%temp%` → selecionar tudo e deletar)
- Usar "Limpeza de Disco" do Windows (procurar no menu Iniciar)
- Desinstalar programas não utilizados

### Passo 2 — Instalar Docker Desktop

1. Baixar em: https://www.docker.com/products/docker-desktop/ (versão Windows)
2. Executar `Docker Desktop Installer.exe` como administrador
3. Durante instalação: marcar **"Use WSL 2 instead of Hyper-V"**
4. Reiniciar o computador
5. Abrir Docker Desktop e aguardar ícone verde na bandeja do sistema

### Passo 3 — Instalar o WhatsTrigger (Claude faz automaticamente)

Quando o Docker estiver rodando, dizer ao Claude "Docker instalado, pode continuar" e ele executará:

```bash
# 1. Copiar .env
cp .env.example .env

# 2. Subir tudo + migrations
make bootstrap
```

O `make bootstrap` faz automaticamente:
- `docker compose up -d` (sobe todos os 7 containers)
- Aguarda MySQL inicializar
- `php artisan key:generate`
- `php artisan migrate` (cria as 5 tabelas)

### Passo 4 — Configurar instância WhatsApp

Após os containers subirem:

1. Acessar Evolution API: http://localhost:8080
2. Criar uma instância com o nome desejado
3. Copiar o nome da instância para o `.env`:
   ```
   WHATSAPP_INSTANCE_ID=nome-da-instancia
   ```
4. Reiniciar o container app: `docker compose restart app`
5. Acessar http://localhost/whatstrigger/whatsapp para escanear o QR Code

### Passo 5 — Implementar pendências técnicas do código

As pendências listadas acima (bootstrap/app.php, routes/api.php, routes/console.php) precisam ser feitas após o Laravel ser instalado pelo entrypoint.

---

## Referência rápida de comandos

```bash
make bootstrap       # primeira vez
make up              # iniciar containers
make down            # parar containers
make logs            # ver logs em tempo real
make shell           # entrar no container
make migrate         # rodar migrations
make fresh           # apagar e recriar banco
make test            # rodar testes
make tinker          # console Laravel

php artisan whatstrigger:dispatch-due --dry-run   # checar campanhas pendentes sem disparar
```

## Portas dos serviços

| Serviço | URL |
|---------|-----|
| App (Laravel) | http://localhost |
| Evolution API | http://localhost:8080 |
| MySQL | localhost:3306 |
| Redis | localhost:6379 |

---

## Arquivos importantes do projeto

| Arquivo | Função |
|---------|--------|
| `CLAUDE.md` | Guia completo da arquitetura para o Claude |
| `.env.example` | Template de variáveis de ambiente |
| `docker-compose.yml` | Definição de todos os containers |
| `Makefile` | Atalhos de comandos |
| `apres_.md` | Plano de negócios completo (fonte da verdade) |
