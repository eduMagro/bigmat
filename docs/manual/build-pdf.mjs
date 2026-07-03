// Generador de PDF del manual de Bigmat.
// Markdown -> HTML (marked) -> PDF (Chrome headless --print-to-pdf).
// Uso: node build-pdf.mjs
//
// Las imágenes que aún no existan en /images se sustituyen automáticamente
// por un recuadro "Captura pendiente", para poder generar borradores antes
// de tener todas las capturas.

import { readFileSync, writeFileSync, existsSync, mkdirSync } from 'node:fs';
import { spawnSync } from 'node:child_process';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { dirname, join, resolve } from 'node:path';
import { marked } from 'marked';

const __dirname = dirname(fileURLToPath(import.meta.url));
const SRC = join(__dirname, 'manual-registro-usuarios.md');
const OUT_HTML = join(__dirname, 'build', 'manual.html');
const OUT_PDF = join(__dirname, 'manual-de-usuario.pdf');

const CHROME_CANDIDATES = [
  'C:/Program Files/Google/Chrome/Application/chrome.exe',
  'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
  'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
  'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
];

function findChrome() {
  for (const p of CHROME_CANDIDATES) if (existsSync(p)) return p;
  return null;
}

// --- Plantilla de estilos --------------------------------------------------
const CSS = `
:root {
  --rojo: #c1121f;
  --rojo-osc: #8d0d17;
  --tinta: #1f2430;
  --gris: #5b6472;
  --gris-cl: #e7eaef;
  --azul: #1d4ed8;
}
* { box-sizing: border-box; }
html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
body {
  font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
  color: var(--tinta);
  font-size: 11.5pt;
  line-height: 1.55;
  margin: 0;
}
h1, h2, h3 { line-height: 1.2; color: var(--tinta); }
h1 { font-size: 22pt; border-bottom: 3px solid var(--rojo); padding-bottom: 6px; margin-top: 0; }
h2 { font-size: 16pt; color: var(--rojo-osc); margin-top: 1.6em; border-bottom: 1px solid var(--gris-cl); padding-bottom: 4px; }
h3 { font-size: 12.5pt; color: var(--tinta); margin-top: 1.3em; }
p { margin: 0.5em 0; }
a { color: var(--azul); text-decoration: none; }
strong { color: var(--tinta); }
blockquote {
  margin: 1em 0; padding: 0.6em 1em;
  background: #fff5f5; border-left: 4px solid var(--rojo);
  color: var(--tinta); border-radius: 0 6px 6px 0;
}
blockquote p { margin: 0.2em 0; }
ul, ol { margin: 0.5em 0 0.5em 0; padding-left: 1.4em; }
li { margin: 0.25em 0; }
table { border-collapse: collapse; width: 100%; margin: 1em 0; font-size: 10.5pt; }
th, td { border: 1px solid var(--gris-cl); padding: 7px 10px; text-align: left; vertical-align: top; }
th { background: var(--rojo); color: #fff; font-weight: 600; }
tr:nth-child(even) td { background: #f7f8fa; }
code { background: #f0f2f5; padding: 1px 5px; border-radius: 4px; font-size: 0.92em; }
img { max-width: 100%; border: 1px solid var(--gris-cl); border-radius: 6px; margin: 0.6em 0; display: block; }

/* Portada */
.cover {
  height: 247mm; display: flex; flex-direction: column;
  align-items: center; justify-content: center; text-align: center;
}
.cover img { width: 230px; border: none; margin-bottom: 24px; }
.cover h1 { border: none; font-size: 30pt; margin: 0.2em 0; color: var(--rojo); }
.cover h2 { border: none; color: var(--tinta); font-size: 16pt; font-weight: 500; margin: 0.4em 2cm; }
.cover p { color: var(--gris); margin: 0.3em 0; }

.page-break { page-break-after: always; }

/* Recuadro de captura pendiente */
.captura-pendiente {
  border: 2px dashed var(--rojo);
  background: repeating-linear-gradient(45deg, #fff, #fff 10px, #fff5f5 10px, #fff5f5 20px);
  color: var(--rojo-osc);
  text-align: center;
  padding: 34px 16px;
  border-radius: 8px;
  margin: 0.8em 0;
  font-size: 10pt;
  font-weight: 600;
}
.captura-pendiente span { display: block; font-weight: 400; color: var(--gris); margin-top: 6px; font-size: 9pt; }
`;

// --- Conversión ------------------------------------------------------------
let md = readFileSync(SRC, 'utf8');
let html = marked.parse(md, { gfm: true, breaks: false });

// Sustituir imágenes inexistentes por un recuadro "pendiente".
let pendientes = 0;
html = html.replace(/<img src="([^"]+)" alt="([^"]*)"[^>]*>/g, (m, src, alt) => {
  // Las imágenes de assets (logo) se dejan pasar siempre.
  const abs = resolve(__dirname, src);
  if (existsSync(abs)) return m;
  if (src.startsWith('assets/')) return m; // si falta el logo, que se vea el fallo
  pendientes++;
  return `<div class="captura-pendiente">📸 Captura pendiente<span>${alt || src}</span></div>`;
});

// <base> con la ruta absoluta del manual para que las imágenes (images/, assets/)
// se resuelvan aunque el HTML se escriba en la subcarpeta build/.
const baseHref = pathToFileURL(__dirname + '/').href;
const doc = `<!doctype html>
<html lang="es"><head><meta charset="utf-8">
<base href="${baseHref}">
<title>Manual de registro de usuarios — Bigmat</title>
<style>${CSS}</style>
</head><body>${html}</body></html>`;

mkdirSync(dirname(OUT_HTML), { recursive: true });
writeFileSync(OUT_HTML, doc, 'utf8');
console.log(`HTML generado: ${OUT_HTML}`);
if (pendientes > 0) console.log(`⚠️  ${pendientes} captura(s) pendiente(s) (recuadros de aviso en el PDF).`);

// --- PDF con Chrome headless ----------------------------------------------
const chrome = findChrome();
if (!chrome) {
  console.error('No se encontró Chrome ni Edge. Instala Chrome o ajusta CHROME_CANDIDATES.');
  process.exit(1);
}

const args = [
  '--headless=new',
  '--disable-gpu',
  '--no-pdf-header-footer',
  `--print-to-pdf=${OUT_PDF}`,
  pathToFileURL(OUT_HTML).href,
];
const r = spawnSync(chrome, args, { stdio: 'inherit' });
if (r.status === 0 && existsSync(OUT_PDF)) {
  console.log(`✅ PDF generado: ${OUT_PDF}`);
} else {
  console.error('Error al generar el PDF con Chrome.', r.error || `código ${r.status}`);
  process.exit(1);
}
