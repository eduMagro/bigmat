# Manual de usuario — Bigmat

Manual en PDF sobre el alta de trabajadores (incorporaciones y registro de usuarios).

## Archivos

- `manual-registro-usuarios.md` — Fuente del manual (Markdown). **Edita aquí el contenido.**
- `manual-registro-usuarios.pdf` — PDF generado (resultado final).
- `build-pdf.mjs` — Conversor Markdown → PDF (usa `marked` + Chrome headless).
- `capture.mjs` — Script de Puppeteer para regenerar las capturas de la app.
- `images/` — Capturas de pantalla incrustadas en el manual.
- `assets/` — Logo y otros recursos.

## Requisitos

- Node.js y Google Chrome instalados.
- `npm install` (instala `marked` y `puppeteer-core`).

## Generar el PDF

```bash
npm run build
```

Genera `manual-registro-usuarios.pdf`. Si falta alguna imagen en `images/`, en el
PDF aparece un recuadro «Captura pendiente» en su lugar.

## Regenerar las capturas

Requiere la app levantada (XAMPP) en `http://localhost/bigmat/public`.

```bash
node capture.mjs
```

Abre una ventana de Chrome con un perfil propio (`.pptr-profile/`): inicia sesión
una vez (usa un usuario del departamento **Programador** para la pantalla de
«Registrar Usuario»). El script difumina los datos personales antes de capturar.
