<p align="center">
    <img src="public/imagenes/logoBigmat.png" alt="BigMat Logo" width="420">
</p>

# BIGMAT

Sistema de gestion para BIGMAT (version independiente del proyecto).

## Guia Rapida

### Primer uso (instalacion desde cero)
Si acabas de clonar el repositorio en un equipo limpio:

```bash
sudo ./installation/setup.sh
```

### Uso diario (arrancar proyecto)

```bash
./start.sh
```

URLs:
- Web: [http://bigmat.test](http://bigmat.test)
- Fallback: [http://localhost:8082](http://localhost:8082)
- Base de datos (phpMyAdmin): [http://localhost:8081](http://localhost:8081)

### Parar proyecto

```bash
./stop.sh
```

## Desarrollo

Para desarrollo con recarga en caliente:

```bash
npm run dev
```

## Comandos Laravel (Artisan)

```bash
SAIL_FILES=compose.yaml ./vendor/bin/sail artisan [comando]
```

Ejemplo:

```bash
SAIL_FILES=compose.yaml ./vendor/bin/sail artisan migrate
```

## Documentacion de instalacion

- [Instalacion](installation/README.md)
- [Troubleshooting](installation/TROUBLESHOOTING.md)
