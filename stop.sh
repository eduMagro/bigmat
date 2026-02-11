#!/bin/bash

set -e

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"
cd "${SCRIPT_DIR}"

if [ -f compose.yaml ]; then
    export SAIL_FILES=compose.yaml
fi

echo "====================================="
echo "  Deteniendo Entorno BIGMAT Project  "
echo "====================================="

if docker info >/dev/null 2>&1; then
    ./vendor/bin/sail stop
else
    echo "Permisos Docker no activos en esta sesion. Intentando con sudo..."
    sudo ./vendor/bin/sail stop
fi

if [ -f public/hot ]; then
    rm -f public/hot
    echo "public/hot eliminado."
fi

echo "====================================="
echo "Procesos detenidos correctamente"
echo "====================================="
