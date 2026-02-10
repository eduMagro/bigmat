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

APP_PORT_VALUE="$(read_env_var APP_PORT 8082)"
PMA_PORT_VALUE="$(read_env_var FORWARD_PHPMYADMIN_PORT 8081)"

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

echo "Compilando frontend..."
npm run build

echo "====================================="
echo "PROYECTO BIGMAT INICIADO"
echo "====================================="
echo "Web:         http://bigmat.test"
echo "Fallback:    http://localhost:${APP_PORT_VALUE}"
echo "phpMyAdmin:  http://localhost:${PMA_PORT_VALUE}"
echo "====================================="
