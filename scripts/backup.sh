#!/bin/bash
# WhatsTrigger — Backup automatizado do MySQL com criptografia GPG
#
# Uso:
#   ./scripts/backup.sh                    # backup manual
#   ./scripts/backup.sh --restore arquivo  # restaurar backup
#
# Variáveis de ambiente (ou defaults):
#   DB_BACKUP_PASSPHRASE  — senha para criptografia GPG simétrica
#   DB_BACKUP_RETENTION   — dias para manter backups (padrão: 30)
#   DB_BACKUP_DIR         — diretório de destino (padrão: ./backups)

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
BACKUP_DIR="${DB_BACKUP_DIR:-$SCRIPT_DIR/backups}"
RETENTION_DAYS="${DB_BACKUP_RETENTION:-30}"
PASSPHRASE="${DB_BACKUP_PASSPHRASE:-}"
TIMESTAMP=$(date '+%Y-%m-%d_%H-%M-%S')
DUMP_FILE="${BACKUP_DIR}/whatstrigger_${TIMESTAMP}.sql"
ENCRYPTED_FILE="${DUMP_FILE}.gpg"

DB_HOST="${DB_HOST:-mysql}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-whatstrigger}"
DB_USERNAME="${DB_USERNAME:-whatstrigger}"
DB_PASSWORD="${DB_PASSWORD:-secret}"

mkdir -p "$BACKUP_DIR"

restore() {
    local input="$1"
    local decrypted

    if [[ "$input" == *.gpg ]]; then
        if [[ -z "$PASSPHRASE" ]]; then
            echo "ERRO: DB_BACKUP_PASSPHRASE necessária para descriptografar" >&2
            exit 1
        fi
        echo "Descriptografando $input ..."
        decrypted="${input%.gpg}"
        gpg --batch --yes --passphrase "$PASSPHRASE" \
            --decrypt --output "$decrypted" "$input"
        input="$decrypted"
    fi

    if [[ ! -f "$input" ]]; then
        echo "ERRO: Arquivo não encontrado: $input" >&2
        exit 1
    fi

    echo "Restaurando banco $DB_DATABASE a partir de $input ..."
    docker compose exec -T mysql mysql \
        -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < "$input"
    echo "Restauro concluído."

    if [[ -f "$decrypted" ]]; then
        rm -f "$decrypted"
    fi
}

backup() {
    echo "=== WhatsTrigger Backup ==="
    echo "Banco:  $DB_DATABASE"
    echo "Destino: $DUMP_FILE"
    echo ""

    docker compose exec -T mysql mysqldump \
        -u"$DB_USERNAME" -p"$DB_PASSWORD" \
        --single-transaction --routines --triggers \
        "$DB_DATABASE" > "$DUMP_FILE"

    local dump_size=$(wc -c < "$DUMP_FILE")
    echo "Dump gerado: $(numfmt --to=iec $dump_size)"

    if [[ -n "$PASSPHRASE" ]]; then
        echo "Criptografando com GPG (simétrico)..."
        gpg --batch --yes --passphrase "$PASSPHRASE" \
            --symmetric --cipher-algo AES256 \
            --output "$ENCRYPTED_FILE" "$DUMP_FILE"
        rm -f "$DUMP_FILE"
        echo "Arquivo criptografado: $ENCRYPTED_FILE"
        local encrypted_size=$(wc -c < "$ENCRYPTED_FILE")
        echo "Tamanho: $(numfmt --to=iec $encrypted_size)"
    else
        echo "AVISO: DB_BACKUP_PASSPHRASE não definida — backup sem criptografia!"
    fi

    echo ""
    echo "Limpando backups com mais de $RETENTION_DAYS dias..."
    find "$BACKUP_DIR" -name 'whatstrigger_*.sql*' -mtime +$RETENTION_DAYS \
        -exec echo "Removido: {}" \; -delete

    echo "Backup concluído em $(date '+%Y-%m-%d %H:%M:%S')"
}

case "${1:-}" in
    --restore)
        if [[ -z "${2:-}" ]]; then
            echo "Uso: $0 --restore <arquivo.sql[.gpg]>" >&2
            exit 1
        fi
        restore "$2"
        ;;
    *)
        backup
        ;;
esac
