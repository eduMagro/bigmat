#!/bin/bash

set -e

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"
cd "${SCRIPT_DIR}"

read_env_var() {
    local key="$1"
    local default_value="$2"
    local value
    value="$(grep -E "^${key}=" .env 2>/dev/null | tail -n1 | cut -d'=' -f2-)"
    if [ -z "${value}" ]; then
        echo "${default_value}"
    else
        echo "${value}"
    fi
}

upsert_env_var() {
    local key="$1"
    local value="$2"
    if grep -q "^${key}=" .env 2>/dev/null; then
        sed -i "s|^${key}=.*|${key}=${value}|" .env
    else
        echo "${key}=${value}" >> .env
    fi
}

is_numeric_port() {
    [[ "$1" =~ ^[0-9]+$ ]] && [ "$1" -ge 1 ] && [ "$1" -le 65535 ]
}

port_in_use() {
    local port="$1"

    if command -v ss >/dev/null 2>&1; then
        ss -ltnH "( sport = :${port} )" 2>/dev/null | grep -q .
        return $?
    fi

    if command -v lsof >/dev/null 2>&1; then
        lsof -iTCP:"${port}" -sTCP:LISTEN -Pn >/dev/null 2>&1
        return $?
    fi

    return 1
}

PORT_OVERRIDES=()
RESERVED_PORTS=()
RESOLVED_PORT=""

is_reserved_port() {
    local candidate="$1"
    local reserved
    for reserved in "${RESERVED_PORTS[@]}"; do
        if [ "$reserved" = "$candidate" ]; then
            return 0
        fi
    done
    return 1
}

resolve_port() {
    local var_name="$1"
    local configured="$2"
    local fallback="$3"
    local scan_end="$4"
    local desired="$configured"

    if ! is_numeric_port "$desired" || [ "$desired" -lt 1024 ]; then
        if [ "$configured" != "$fallback" ]; then
            PORT_OVERRIDES+=("${var_name}: ${configured} -> ${fallback}")
        fi
        desired="$fallback"
    fi

    local chosen="$desired"
    if port_in_use "$chosen" || is_reserved_port "$chosen"; then
        local free_port=""
        local candidate
        for ((candidate = fallback; candidate <= scan_end; candidate++)); do
            if ! port_in_use "$candidate" && ! is_reserved_port "$candidate"; then
                free_port="$candidate"
                break
            fi
        done
        if [ -z "$free_port" ]; then
            echo "No se pudo encontrar puerto libre para ${var_name}." >&2
            exit 1
        fi
        PORT_OVERRIDES+=("${var_name}: ${configured} -> ${free_port}")
        chosen="$free_port"
    fi

    RESERVED_PORTS+=("$chosen")
    RESOLVED_PORT="$chosen"
}

APP_PORT_VALUE="$(read_env_var APP_PORT 8082)"
VITE_PORT_VALUE="$(read_env_var VITE_PORT "$(read_env_var VITE_DEV_SERVER_PORT 5175)")"
DB_PORT_VALUE="$(read_env_var FORWARD_DB_PORT 3307)"
REDIS_PORT_VALUE="$(read_env_var FORWARD_REDIS_PORT 6380)"
PMA_PORT_VALUE="$(read_env_var FORWARD_PHPMYADMIN_PORT 8081)"

resolve_port APP_PORT "$APP_PORT_VALUE" 8082 8999
APP_PORT_EFFECTIVE="$RESOLVED_PORT"
resolve_port VITE_PORT "$VITE_PORT_VALUE" 5175 5999
VITE_PORT_EFFECTIVE="$RESOLVED_PORT"
resolve_port FORWARD_DB_PORT "$DB_PORT_VALUE" 3307 3399
DB_PORT_EFFECTIVE="$RESOLVED_PORT"
resolve_port FORWARD_REDIS_PORT "$REDIS_PORT_VALUE" 6380 6499
REDIS_PORT_EFFECTIVE="$RESOLVED_PORT"
resolve_port FORWARD_PHPMYADMIN_PORT "$PMA_PORT_VALUE" 8081 8199
PMA_PORT_EFFECTIVE="$RESOLVED_PORT"

export APP_PORT="$APP_PORT_EFFECTIVE"
export VITE_PORT="$VITE_PORT_EFFECTIVE"
export VITE_HMR_PORT="$VITE_PORT_EFFECTIVE"
export VITE_DEV_SERVER_PORT="$VITE_PORT_EFFECTIVE"
export FORWARD_DB_PORT="$DB_PORT_EFFECTIVE"
export FORWARD_REDIS_PORT="$REDIS_PORT_EFFECTIVE"
export FORWARD_PHPMYADMIN_PORT="$PMA_PORT_EFFECTIVE"

upsert_env_var "APP_PORT" "$APP_PORT_EFFECTIVE"
upsert_env_var "VITE_PORT" "$VITE_PORT_EFFECTIVE"
upsert_env_var "VITE_HMR_PORT" "$VITE_PORT_EFFECTIVE"
upsert_env_var "VITE_DEV_SERVER_PORT" "$VITE_PORT_EFFECTIVE"
upsert_env_var "FORWARD_DB_PORT" "$DB_PORT_EFFECTIVE"
upsert_env_var "FORWARD_REDIS_PORT" "$REDIS_PORT_EFFECTIVE"
upsert_env_var "FORWARD_PHPMYADMIN_PORT" "$PMA_PORT_EFFECTIVE"

if [ -f compose.yaml ]; then
    export SAIL_FILES=compose.yaml
fi

echo "====================================="
echo "   Iniciando Entorno BIGMAT Project  "
echo "====================================="

run_sail() {
    if docker info >/dev/null 2>&1; then
        ./vendor/bin/sail "$@"
    else
        echo "Permisos Docker no activos en esta sesion. Intentando con sudo..."
        sudo ./vendor/bin/sail "$@"
    fi
}

echo "Levantando contenedores Docker..."
run_sail up -d

if [ -f public/hot ]; then
    echo "Eliminando public/hot para evitar HMR stale..."
    rm -f public/hot
fi

if [ "${#PORT_OVERRIDES[@]}" -gt 0 ]; then
    echo "Puertos ocupados detectados. Se aplicaron ajustes y se guardaron en .env:"
    for note in "${PORT_OVERRIDES[@]}"; do
        echo "  - ${note}"
    done
fi

echo "Compilando frontend..."
npm run build

echo "====================================="
echo "PROYECTO BIGMAT INICIADO"
echo "====================================="
echo "Web:         http://bigmat.test"
echo "Fallback:    http://localhost:${APP_PORT_EFFECTIVE}"
echo "Vite:        http://localhost:${VITE_PORT_EFFECTIVE}"
echo "phpMyAdmin:  http://localhost:${PMA_PORT_EFFECTIVE}"
echo "====================================="
