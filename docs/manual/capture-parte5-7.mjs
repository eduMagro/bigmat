// Capturas para las Partes 5-7 del manual: dashboard, ajustes, simulador y recepción de compras.
// Difumina datos personales antes de capturar.
// Requiere la app levantada y sesión de ACCESO TOTAL (Administración / Administrador / Programador).
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

  await page.goto(BASE + '/dashboard', { waitUntil: 'networkidle2' });
  if (page.url().includes('/login')) {
    console.log('\n>>> Inicia sesión en la ventana de Chrome (ACCESO TOTAL). Esperando...\n');
    for (let i = 0; i < 150; i++) { await sleep(2000); if (!page.url().includes('/login')) break; }
    await page.goto(BASE + '/dashboard', { waitUntil: 'networkidle2' });
  }
  if (page.url().includes('/login')) {
    console.error('\n❌ Sigues sin sesión iniciada. Inicia sesión como ACCESO TOTAL y vuelve a ejecutar.\n');
    await browser.close();
    process.exit(1);
  }
  console.log('Sesión OK.\n');

  // --- 20 Panel principal (/dashboard) ---
  await sleep(700);
  await blurOperator(page);
  await shot(page, '20-dashboard.png');

  // --- 21 Ajustes (/ajustes) ---
  await page.goto(BASE + '/ajustes', { waitUntil: 'networkidle2' });
  await sleep(800);
  await blurMatching(page, PII);
  await blurOperator(page);
  await shot(page, '21-ajustes.png');

  // --- 22 Simulador de nóminas (/simulacion-irpf) ---
  await page.goto(BASE + '/simulacion-irpf', { waitUntil: 'networkidle2' });
  await sleep(700);
  await blurOperator(page);
  await shot(page, '22-simulador.png');

  // --- 23 Recepción de solicitudes de compra (/recepcion-solicitudes) ---
  await page.goto(BASE + '/recepcion-solicitudes', { waitUntil: 'networkidle2' });
  await sleep(900);
  await blurMatching(page, PII);
  await blurOperator(page);
  await shot(page, '23-recepcion.png');

  console.log('\n✅ Capturas de las Partes 5-7 terminadas en /images');
  await browser.close();
})();
