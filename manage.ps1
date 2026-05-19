<#
.SYNOPSIS
    WhatsTrigger — gerenciamento do ambiente Docker
.DESCRIPTION
    Comandos disponíveis:
      start        Sobe todos os containers
      stop         Para todos os containers (preserva volumes)
      restart      Reinicia todos os containers
      status       Exibe status dos containers
      logs         Segue os logs de app + queue em tempo real
      test         Roda a suite de testes PHPUnit
      shell        Abre shell interativo no container app
      migrate      Executa php artisan migrate
      fresh        migrate:fresh --seed (apaga e recria o banco)
      tinker       Abre php artisan tinker
      maintenance  Modo manutenção: para app/nginx/queue/scheduler (DB+Redis ficam no ar)
      resume       Sai do modo manutenção: reinicia app/nginx/queue/scheduler
      build        Rebuild da imagem PHP (usar após composer require/update)
      cache-clear  Limpa e reconstrói config/route/view cache do Laravel
      backup       Backup manual do MySQL (dump + gpg)
      restore      Restaurar backup MySQL: .\manage.ps1 restore .\backups\arquivo.sql
.EXAMPLE
    .\manage.ps1 start
    .\manage.ps1 test
    .\manage.ps1 maintenance
#>

param(
    [Parameter(Position=0, Mandatory=$true)]
    [ValidateSet("start","stop","restart","status","logs","test","shell","migrate","fresh","tinker","maintenance","resume","build","cache-clear","backup","restore")]
    [string]$Command
)

Set-Location $PSScriptRoot

$APP_SERVICES  = "app","nginx","queue","scheduler"
$COMPOSE       = "docker"
$COMPOSE_ARGS  = @("compose")

# Credenciais padrão criadas pelo seeder (DatabaseSeeder.php)
$DEFAULT_EMAIL    = "admin@whatstrigger.test"
$DEFAULT_PASSWORD = "password"

function Show-Credentials {
    Write-Host ""
    Write-Host "--- Acesso padrao ---" -ForegroundColor DarkCyan
    Write-Host "  URL:   http://localhost/whatstrigger/login" -ForegroundColor White
    Write-Host "  Email: $DEFAULT_EMAIL" -ForegroundColor White
    Write-Host "  Senha: $DEFAULT_PASSWORD" -ForegroundColor White
    Write-Host "(use 'fresh' para recriar o usuario padrao se necessario)" -ForegroundColor DarkGray
    Write-Host ""
}

function Invoke-Compose {
    param([string[]]$Args)
    & $COMPOSE @COMPOSE_ARGS @Args
}

function Show-Status {
    Write-Host ""
    Write-Host "=== Status dos Containers ===" -ForegroundColor Cyan
    docker ps -a --format "table {{.Names}}`t{{.Status}}`t{{.Ports}}" |
        Select-String "whatsapp|NAME"
    Write-Host ""
}

switch ($Command) {

    "start" {
        Write-Host "Subindo todos os containers..." -ForegroundColor Green
        Invoke-Compose @("up", "-d")
        Start-Sleep -Seconds 3
        Show-Status
        Write-Host "Executando migrations e seed..." -ForegroundColor Cyan
        Invoke-Compose @("exec", "-T", "app", "php", "artisan", "migrate", "--force")
        Invoke-Compose @("exec", "-T", "app", "php", "artisan", "db:seed", "--force")
        Write-Host "Aplicacao disponivel em: http://localhost" -ForegroundColor Yellow
        Write-Host "Evolution API:           http://localhost:8080" -ForegroundColor Yellow
        Show-Credentials
    }

    "stop" {
        Write-Host "Parando todos os containers..." -ForegroundColor Yellow
        Invoke-Compose @("down")
        Show-Status
    }

    "restart" {
        Write-Host "Reiniciando todos os containers..." -ForegroundColor Yellow
        Invoke-Compose @("restart")
        Start-Sleep -Seconds 3
        Show-Status
    }

    "status" {
        Show-Status
        Show-Credentials
    }

    "logs" {
        Write-Host "Seguindo logs de app + queue (Ctrl+C para sair)..." -ForegroundColor Cyan
        Invoke-Compose @("logs", "-f", "app", "queue")
    }

    "test" {
        Write-Host "Executando testes PHPUnit..." -ForegroundColor Cyan
        Invoke-Compose @("exec", "app", "php", "-d", "memory_limit=512M", "artisan", "test")
    }

    "shell" {
        Write-Host "Abrindo shell no container app..." -ForegroundColor Cyan
        Invoke-Compose @("exec", "app", "sh")
    }

    "migrate" {
        Write-Host "Executando migrations..." -ForegroundColor Cyan
        Invoke-Compose @("exec", "app", "php", "artisan", "migrate")
    }

    "fresh" {
        Write-Host "ATENCAO: isso apaga e recria todo o banco de dados!" -ForegroundColor Red
        $confirm = Read-Host "Confirmar? (s/N)"
        if ($confirm -match "^[sS]$") {
            Invoke-Compose @("exec", "app", "php", "artisan", "migrate:fresh", "--seed")
            Write-Host ""
            Write-Host "Banco recriado com usuario padrao:" -ForegroundColor Green
            Show-Credentials
        } else {
            Write-Host "Operacao cancelada." -ForegroundColor Yellow
        }
    }

    "tinker" {
        Write-Host "Abrindo tinker..." -ForegroundColor Cyan
        Invoke-Compose @("exec", "app", "php", "artisan", "tinker")
    }

    "maintenance" {
        Write-Host "Ativando modo manutencao..." -ForegroundColor Yellow
        Write-Host "Parando: $($APP_SERVICES -join ', ')" -ForegroundColor Yellow
        Invoke-Compose @(@("stop") + $APP_SERVICES)
        Write-Host ""
        Write-Host "MySQL e Redis continuam no ar. Para retomar, execute:" -ForegroundColor Green
        Write-Host "  .\manage.ps1 resume" -ForegroundColor Cyan
        Show-Status
    }

    "build" {
        Write-Host "Rebuilding imagem PHP (vendor baked in)..." -ForegroundColor Cyan
        Write-Host "Use apos: composer require/update, mudancas no Dockerfile" -ForegroundColor DarkGray
        Invoke-Compose @("build", "app")
        Write-Host "Recriando containers com nova imagem..." -ForegroundColor Cyan
        Invoke-Compose @(@("up", "-d", "--force-recreate") + $APP_SERVICES)
        Start-Sleep -Seconds 8
        Show-Status
        Show-Credentials
    }

    "cache-clear" {
        Write-Host "Limpando e reconstruindo caches do Laravel..." -ForegroundColor Cyan
        Invoke-Compose @("exec", "app", "php", "artisan", "config:clear")
        Invoke-Compose @("exec", "app", "php", "artisan", "route:clear")
        Invoke-Compose @("exec", "app", "php", "artisan", "view:clear")
        Invoke-Compose @("exec", "app", "php", "artisan", "config:cache")
        Invoke-Compose @("exec", "app", "php", "artisan", "route:cache")
        Invoke-Compose @("exec", "app", "php", "artisan", "view:cache")
        Write-Host "Caches reconstruidos." -ForegroundColor Green
    }

    "resume" {
        Write-Host "Saindo do modo manutencao..." -ForegroundColor Green
        Write-Host "Subindo: $($APP_SERVICES -join ', ')" -ForegroundColor Green
        Invoke-Compose @(@("up", "-d") + $APP_SERVICES)
        Start-Sleep -Seconds 3
        Show-Status
        Write-Host "Aplicacao disponivel em: http://localhost" -ForegroundColor Yellow
        Show-Credentials
    }

    "backup" {
        $backupDir = "$PSScriptRoot\backups"
        New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
        $timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
        $containerDump = "/tmp/whatstrigger_$timestamp.sql"
        $localFile = "$backupDir\whatstrigger_$timestamp.sql"

        Write-Host "Gerando dump dentro do container mysql..." -ForegroundColor Cyan
        & docker compose exec -T mysql mysqldump -uwhatstrigger -psecret --single-transaction --routines --triggers --result-file="$containerDump" whatstrigger 2>&1 | Out-Null

        Write-Host "Copiando para o host..." -ForegroundColor Cyan
        & docker compose cp "mysql:$containerDump" "$localFile" 2>&1 | Out-Null
        & docker compose exec -T mysql rm -f "$containerDump" 2>&1 | Out-Null

        if ((Test-Path $localFile) -and ((Get-Item $localFile).Length -gt 0)) {
            $size = [math]::Round((Get-Item $localFile).Length / 1MB, 2)
            Write-Host "Backup concluido: $localFile ($size MB)" -ForegroundColor Green
        } else {
            Write-Host "ERRO: Falha ao gerar backup." -ForegroundColor Red
        }
    }

    "restore" {
        $path = $args[0]
        if (-not $path -or -not (Test-Path $path)) {
            Write-Host "Uso: .\manage.ps1 restore <caminho\arquivo.sql>" -ForegroundColor Yellow
            return
        }
        $filename = Split-Path $path -Leaf
        $containerPath = "/tmp/$filename"

        Write-Host "Copiando dump para o container mysql..." -ForegroundColor Cyan
        & docker compose cp "$path" "mysql:$containerPath" 2>&1 | Out-Null

        Write-Host "Restaurando banco..." -ForegroundColor Cyan
        & docker compose exec -T mysql sh -c "mysql -uwhatstrigger -psecret whatstrigger < $containerPath" 2>&1
        & docker compose exec -T mysql rm -f "$containerPath" 2>&1 | Out-Null

        if ($LASTEXITCODE -eq 0) {
            Write-Host "Banco restaurado com sucesso." -ForegroundColor Green
        } else {
            Write-Host "ERRO: Falha ao restaurar." -ForegroundColor Red
        }
    }
}
