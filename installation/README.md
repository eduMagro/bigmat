# Guia de Instalacion del Proyecto BIGMAT

Esta carpeta contiene scripts para desplegar el proyecto desde cero en Ubuntu/Linux.

## Archivos
- `setup.sh`: instalacion automatizada (Docker, PHP, Node, dependencias y Sail).
- `verify.sh`: verificacion rapida del estado de la instalacion.

## Uso rapido
Desde la raiz del proyecto:

```bash
sudo chmod +x installation/setup.sh installation/verify.sh
sudo ./installation/setup.sh
```

Luego puedes validar con:

```bash
./installation/verify.sh
```

## Que hace `setup.sh`
1. Instala dependencias base del sistema.
2. Instala PHP 8.2 + extensiones.
3. Instala Composer.
4. Instala Node.js 20.
5. Instala Docker y Docker Compose plugin.
6. Configura `bigmat.test` en `/etc/hosts`.
7. Prepara `.env` para Sail.
8. Ejecuta `composer install`, `npm install`, `npm run build`.
9. Si falta `config/view.php` o `storage/framework/views`, lo corrige automaticamente.
10. Construye y levanta contenedores.
11. Ejecuta `key:generate`, `migrate` y `storage:link`.

## URLs por defecto
- Web: `http://bigmat.test` (fallback `http://localhost:8082`)
- phpMyAdmin: `http://localhost:8081`
