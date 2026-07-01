// Capturas para la Parte 4 del manual: mensajes, documentos a firmar, EPIs y nóminas.
// Difumina datos personales (nombres, email/DNI/teléfono) por CSS antes de capturar.
// Requiere la app levantada y sesión de OFICINA / ADMINISTRACIÓN (acceso total).
import puppeteer from 'puppeteer-core';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import { existsSync } from 'node:fs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const IMAGES = join(__dirname, 'images');
const PROFILE = join(__dirname, '.pptr-profile');
const BASE = 'http://localhost/bigmat/public';
const CHROME = ['C:/Program Files/Google/Chrome/Application/chrome.exe', 'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe'].find(existsSync);
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function blurMatching(page, patterns) {
  await page.evaluate((pats) => {
    const regs = pats.map((p) => new RegExp(p));
    document.querySelectorAll('body *').forEach((el) => {
      if (el.children.length === 0) {
        const t = (el.textContent || '').trim();
        if (t && regs.some((r) => r.test(t))) el.style.filter = 'blur(5px)';
      }
    });
  }, patterns);
}
async function blurOperator(page) {
  await page.evaluate(() => {
    const span = document.querySelector('header span.sm\\:block, nav span.sm\\:block');
    if (span) span.style.filter = 'blur(5px)';
  });
}
// Datos personales típicos: email, DNI/NIE, teléfonos de 9 dígitos
const PII = ['@', '\\b\\d{8}[A-Za-z]\\b', '\\b[XYZ]\\d{7}[A-Za-z]\\b', '^\\d{9}$'];

async function shot(page, name) { await sleep(400); await page.screenshot({ path: join(IMAGES, name) }); console.log('  ✓ ' + name); }

(async () => {
  const browser = await puppeteer.launch({ executablePath: CHROME, headless: false, defaultViewport: { width: 1440, height: 900, deviceScaleFactor: 2 }, userDataDir: PROFILE });
  const page = (await browser.pages())[0] || (await browser.newPage());
  await page.setViewport({ width: 1440, height: 900, deviceScaleFactor: 2 });

  await page.goto(BASE + '/alertas', { waitUntil: 'networkidle2' });
  if (page.url().includes('/login')) {
    console.log('\n>>> Inicia sesión en la ventana de Chrome (usuario de OFICINA / ADMINISTRACIÓN). Esperando...\n');
    for (let i = 0; i < 150; i++) { await sleep(2000); if (!page.url().includes('/login')) break; }
    await page.goto(BASE + '/alertas', { waitUntil: 'networkidle2' });
  }
  if (page.url().includes('/login')) {
    console.error('\n❌ Sigues sin sesión iniciada. Inicia sesión como OFICINA/ADMINISTRACIÓN y vuelve a ejecutar.\n');
    await browser.close();
    process.exit(1);
  }
  console.log('Sesión OK.\n');

  // --- 16 Bandeja de mensajes (/alertas) ---
  await sleep(700);
  await page.evaluate(() => {
    // Nombres de emisor en la tabla/tarjetas de mensajes
    document.querySelectorAll('table tbody tr td, .card, [class*="rounded"]').forEach((el) => {
      const t = (el.textContent || '');
      if (/@/.test(t) && el.children.length === 0) el.style.filter = 'blur(5px)';
    });
  });
  await blurMatching(page, PII);
  await blurOperator(page);
  await shot(page, '16-mensajes.png');

  // --- 17 Documentos a firmar (/documentos-alertas) ---
  await page.goto(BASE + '/documentos-alertas', { waitUntil: 'networkidle2' });
  await sleep(700);
  await blurMatching(page, PII);
  await blurOperator(page);
  await shot(page, '17-documentos-firma.png');

  // --- 18 EPIs por trabajador (/epis) ---
  await page.goto(BASE + '/epis', { waitUntil: 'networkidle2' });
  // Esperar a que cargue la agenda por AJAX
  for (let i = 0; i < 20; i++) { await sleep(500); const ok = await page.evaluate(() => !/Cargando/i.test(document.body.innerText)); if (ok) break; }
  await sleep(600);
  await blurMatching(page, PII);
  await blurOperator(page);
  await shot(page, '18-epis.png');

  // --- 19 Importar nóminas (/nominas) ---
  await page.goto(BASE + '/nominas', { waitUntil: 'networkidle2' });
  await sleep(600);
  await blurOperator(page);
  await shot(page, '19-nominas.png');

  console.log('\n✅ Capturas de la Parte 4 terminadas en /images');
  await browser.close();
})();
