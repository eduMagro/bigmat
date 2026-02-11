#!/bin/bash

# BIGMAT Project Installation Script
# Usage: sudo ./installation/setup.sh

set -e
export DEBIAN_FRONTEND=noninteractive

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." >/dev/null 2>&1 && pwd)"

if [ "$EUID" -ne 0 ]; then
    echo "Por favor ejecuta este script como root (sudo ./installation/setup.sh)"
    exit 1
fi

TARGET_USER="${SUDO_USER:-$(logname 2>/dev/null || true)}"
if [ -z "${TARGET_USER}" ] || [ "${TARGET_USER}" = "root" ]; then
    echo "No se pudo detectar un usuario no-root para finalizar la instalacion."
    echo "Ejecuta el script como: sudo ./installation/setup.sh"
    exit 1
fi

cd "${PROJECT_ROOT}"

echo "=== Inicio de la instalacion de BIGMAT ==="

echo "[1/7] Actualizando sistema..."
apt-get update -qq

echo "[2/7] Instalando dependencias base..."
apt-get install -y curl git unzip zip software-properties-common ca-certificates gnupg

echo "[3/7] Instalando PHP 8.2 y extensiones..."
add-apt-repository ppa:ondrej/php -y
apt-get update -qq
apt-get install -y php8.2 php8.2-cli php8.2-fpm php8.2-curl php8.2-mbstring php8.2-xml \
    php8.2-zip php8.2-mysql php8.2-bcmath php8.2-intl php8.2-gd php8.2-sqlite3

echo "[4/7] Instalando Composer..."
if ! command -v composer >/dev/null 2>&1; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    echo "Composer instalado."
else
    echo "Composer ya existe."
fi

echo "[5/7] Instalando Node.js..."
if ! command -v node >/dev/null 2>&1; then
    mkdir -p /etc/apt/keyrings
    curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg
    echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_20.x nodistro main" | tee /etc/apt/sources.list.d/nodesource.list
    apt-get update -qq
    apt-get install -y nodejs
    echo "Node.js instalado."
else
    echo "Node.js ya existe."
fi

echo "[6/7] Instalando Docker..."
if ! command -v docker >/dev/null 2>&1; then
    install -m 0755 -d /etc/apt/keyrings
    for pkg in docker.io docker-doc docker-compose docker-compose-v2 podman-docker containerd runc; do
        apt-get remove -y "$pkg" || true
    done

    curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
    chmod a+r /etc/apt/keyrings/docker.asc

    echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" \
        | tee /etc/apt/sources.list.d/docker.list >/dev/null

    apt-get update -qq
    apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
    echo "Docker instalado."
else
    echo "Docker ya existe."
fi

if [ -n "${TARGET_USER}" ]; then
    usermod -aG docker "${TARGET_USER}"
fi

echo "[7/7] Configurando dominio local bigmat.test..."
if grep -q "bigmat.test" /etc/hosts; then
    echo "bigmat.test ya existe en /etc/hosts."
else
    echo "127.0.0.1 bigmat.test" >> /etc/hosts
    echo "Anadido bigmat.test a /etc/hosts."
fi

echo "[Finalizando] Configurando proyecto..."
chown -R "${TARGET_USER}:${TARGET_USER}" "${PROJECT_ROOT}"

sudo -u "${TARGET_USER}" PROJECT_ROOT="${PROJECT_ROOT}" bash << 'EOF'
set -e
cd "${PROJECT_ROOT}"

if [ ! -f composer.json ]; then
    echo "No se encontro composer.json en ${PROJECT_ROOT}. Aborta."
    exit 1
fi

if [ ! -f compose.yaml ]; then
    echo "No se encontro compose.yaml en ${PROJECT_ROOT}. Aborta."
    exit 1
fi

if [ ! -f .env ]; then
    echo "Creando .env desde .env.example..."
    cp .env.example .env
fi

upsert_env() {
    local key="$1"
    local value="$2"
    if grep -q "^${key}=" .env; then
        sed -i "s|^${key}=.*|${key}=${value}|" .env
    else
        echo "${key}=${value}" >> .env
    fi
}

upsert_env "APP_NAME" "BIGMAT"
upsert_env "APP_URL" "http://bigmat.test"
upsert_env "DB_CONNECTION" "mysql"
upsert_env "DB_HOST" "mysql"
upsert_env "DB_PORT" "3306"
upsert_env "DB_DATABASE" "bigmat"
upsert_env "DB_USERNAME" "root"
upsert_env "DB_PASSWORD" ""
upsert_env "SESSION_DRIVER" "file"
upsert_env "VITE_HMR_HOST" "localhost"
upsert_env "VITE_PORT" "5175"
upsert_env "VITE_HMR_PORT" "5175"
upsert_env "VITE_DEV_SERVER_PORT" "5175"
upsert_env "APP_PORT" "8082"
upsert_env "FORWARD_DB_PORT" "3307"
upsert_env "FORWARD_REDIS_PORT" "6380"
upsert_env "FORWARD_PHPMYADMIN_PORT" "8081"
upsert_env "MYSQL_EXTRA_OPTIONS" ""

export SAIL_FILES=compose.yaml

ensure_laravel_runtime_paths() {
    mkdir -p bootstrap/cache
    mkdir -p storage/framework/cache/data
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/testing
    mkdir -p storage/framework/views
    mkdir -p storage/logs

    if [ ! -f config/view.php ]; then
        echo "config/view.php no existe. Creando config minima para views..."
        cat > config/view.php <<'PHP'
<?php

return [
    'paths' => [
        resource_path('views'),
    ],
    'compiled' => env(
        'VIEW_COMPILED_PATH',
        realpath(storage_path('framework/views'))
    ),
];
PHP
    fi
}

ensure_laravel_runtime_paths

echo "Ejecutando composer install..."
if ! composer install --no-interaction --prefer-dist; then
    echo "Composer fallo. Aplicando autocorreccion y reintentando..."
    ensure_laravel_runtime_paths
    composer install --no-interaction --prefer-dist
fi

if [ ! -f vendor/autoload.php ]; then
    echo "vendor/autoload.php no encontrado; regenerando autoload..."
    composer dump-autoload -o --no-interaction
fi

if [ ! -x ./vendor/bin/sail ]; then
    echo "No se encontro ./vendor/bin/sail tras composer install."
    exit 1
fi

echo "Ejecutando npm install..."
npm install
npm run build

echo "Construyendo contenedores Docker..."
./vendor/bin/sail build

echo "Iniciando Docker Sail..."
./vendor/bin/sail up -d

echo "Esperando a la base de datos..."
DB_READY=0
for i in {1..30}; do
    if ./vendor/bin/sail exec -T mysql mysql -uroot -e "SELECT 1" >/dev/null 2>&1; then
        DB_READY=1
        break
    fi
    sleep 2
done
if [ "${DB_READY}" -ne 1 ]; then
    echo "Aviso: la base de datos no respondio a tiempo; se continua con la configuracion."
fi

echo "Configurando Laravel..."
./vendor/bin/sail artisan key:generate --force
./vendor/bin/sail artisan migrate --force
./vendor/bin/sail artisan storage:link || true

echo "Ajustando permisos de storage..."
chmod -R ug+rwX storage bootstrap/cache
EOF

echo "=== Instalacion completada exitosamente ==="
echo ""
echo "Aplicacion disponible en: http://bigmat.test (o http://localhost:8082)"
echo "Base de datos (phpMyAdmin): http://localhost:8081"
