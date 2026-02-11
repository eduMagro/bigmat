#!/bin/bash

set -e

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." >/dev/null 2>&1 && pwd)"
cd "${PROJECT_ROOT}"

export SAIL_FILES=compose.yaml

echo "=== Verificando Instalacion BIGMAT ==="

echo -n "Check Docker containers... "
if docker ps | grep -q "bigmat-laravel.test-1"; then
    echo "OK (Running)"
else
    echo "FAIL (Not running)"
    docker ps
fi

echo -n "Check Database... "
if ./vendor/bin/sail artisan db:show >/dev/null 2>&1; then
    echo "OK (Connected)"
else
    echo "FAIL (Cannot connect)"
fi

echo -n "Check bigmat.test resolution... "
if grep -q "bigmat.test" /etc/hosts; then
    echo "OK (Found in /etc/hosts)"
else
    echo "FAIL (Missing in /etc/hosts)"
fi

echo -n "Check Frontend Build... "
if [ -d "public/build" ]; then
    echo "OK (Build folder exists)"
else
    echo "FAIL (Missing public/build)"
fi

echo "=== Verificacion completada ==="
