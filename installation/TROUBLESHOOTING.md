# Solucion de Problemas (Troubleshooting)

## Error: `failed to resolve reference "sail-8.4/app:latest"`
Si al ejecutar `sail up` aparece este error, Docker intenta descargar la imagen en lugar de construirla.

Solucion:
```bash
SAIL_FILES=compose.yaml ./vendor/bin/sail build
SAIL_FILES=compose.yaml ./vendor/bin/sail up -d
```

## Permisos de Docker
Si aparece `permission denied` al conectar con `docker.sock`:
1. Ejecuta instalacion con `sudo`.
2. Verifica que tu usuario este en grupo `docker`.
3. Cierra sesion y vuelve a entrar.

Comando manual:
```bash
sudo usermod -aG docker $USER
newgrp docker
```

## Puertos ocupados
Si hay conflicto de puertos:
- Web: cambia `APP_PORT` en `.env`
- BD: cambia `FORWARD_DB_PORT` en `.env`
- phpMyAdmin: cambia `FORWARD_PHPMYADMIN_PORT` en `.env`

Reinicia contenedores:
```bash
SAIL_FILES=compose.yaml ./vendor/bin/sail down
SAIL_FILES=compose.yaml ./vendor/bin/sail up -d
```

## Base de datos no conecta al inicio
Esperar unos segundos y ejecutar:
```bash
SAIL_FILES=compose.yaml ./vendor/bin/sail artisan db:show
SAIL_FILES=compose.yaml ./vendor/bin/sail artisan migrate --force
```
