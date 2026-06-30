// Capturas para la Parte 3 del manual: vacaciones, justificante e incorporación.
// Difumina datos personales (nombres, email/DNI/teléfono) por CSS antes de capturar.
// Requiere la app levantada y sesión de OFICINA (para el panel de vacaciones).
import puppeteer from 'puppeteer-core';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import { existsSync } from 'node:fs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const IMAGES = join(__dirname, 'images');
const PROFILE = join(__dirname, '.pptr-profile');
const BASE = 'http://localhost/bigmat/public';
const CHROME = ['C:/Program Files/Google/Chrome/Application/chrome.exe','C:/Program Files (x86)/Google/Chrome/Application/chrome.exe'].find(existsSync);
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

  await page.goto(BASE + '/vacaciones', { waitUntil: 'networkidle2' });
  if (page.url().includes('/login')) {
    console.log('\n>>> Inicia sesión en la ventana de Chrome (usuario de OFICINA). Esperando...\n');
    for (let i = 0; i < 150; i++) { await sleep(2000); if (!page.url().includes('/login')) break; }
    await page.goto(BASE + '/vacaciones', { waitUntil: 'networkidle2' });
  }
  if (page.url().includes('/login')) {
    console.error('\n❌ Sigues sin sesión iniciada. Inicia sesión como OFICINA y vuelve a ejecutar.\n');
    await browser.close();
    process.exit(1);
  }
  console.log('Sesión OK.\n');

  // --- 13 Panel de vacaciones (tabla de solicitudes + calendario) ---
  // Esperar a que el calendario pinte sus eventos
  for (let i = 0; i < 20; i++) { await sleep(500); const ok = await page.evaluate(() => !!document.querySelector('.fc-event, .fc-daygrid-event, table tbody tr')); if (ok) break; }
  await sleep(600);
  await page.evaluate(() => {
    // Nombres de empleado en la tabla de solicitudes (1ª columna)
    document.querySelectorAll('table tbody tr td:first-child').forEach((td) => { td.style.filter = 'blur(5px)'; });
    // Títulos de eventos en el calendario (nombres)
    document.querySelectorAll('.fc-event-title, .fc-event').forEach((el) => { el.style.filter = 'blur(5px)'; });
  });
  await blurOperator(page);
  await shot(page, '13-vacaciones-panel.png');

  // --- 14 Subir justificante (ficha de un operario, sección desplegada) ---
  await page.goto(BASE + '/users/10', { waitUntil: 'networkidle2' });
  await sleep(500);
  // Desplegar la sección "Subir Justificante"
  await page.evaluate(() => {
    const el = [...document.querySelectorAll('button, a, h2, h3, summary, div, span')]
      .find((b) => /subir justificante|justificantes/i.test((b.textContent || '').trim()) && (b.textContent || '').trim().length < 40);
    if (el) el.click();
  });
  await sleep(700);
  await page.evaluate(() => {
    const el = [...document.querySelectorAll('h2, h3, summary, button, span')]
      .find((b) => /subir justificante|justificantes/i.test((b.textContent || '').trim()));
    if (el) el.scrollIntoView({ block: 'center' });
  });
  await sleep(400);
  await page.evaluate(() => { document.querySelectorAll('h1').forEach((h) => h.style.filter = 'blur(6px)'); });
  await blurMatching(page, PII);
  await blurOperator(page);
  await shot(page, '14-justificante.png');

  // --- 15 Ficha de incorporación (primera incorporación del listado) ---
  await page.goto(BASE + '/incorporaciones', { waitUntil: 'networkidle2' });
  await sleep(500);
  const showUrl = await page.evaluate(() => {
    const a = [...document.querySelectorAll('a[href*="/incorporaciones/"]')]
      .find((x) => /\/incorporaciones\/\d+(\?|$)/.test(x.getAttribute('href') || ''));
    return a ? a.href : null;
  });
  if (showUrl) {
    await page.goto(showUrl, { waitUntil: 'networkidle2' });
    await sleep(600);
    await page.evaluate(() => { document.querySelectorAll('h1, h2').forEach((h) => h.style.filter = 'blur(6px)'); });
    await blurMatching(page, PII);
    await blurOperator(page);
    await shot(page, '15-incorporacion-show.png');
  } else {
    console.log('  ⚠️ No se encontró ninguna incorporación en el listado; se omite 15.');
  }

  console.log('\n✅ Capturas de la Parte 3 terminadas en /images');
  await browser.close();
})();
