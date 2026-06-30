// Capturas para la Parte 2 del manual: listado, ficha, calendario y mi-perfil.
// Difumina datos personales (columnas PII, nombre h1, email/DNI/telefono) por CSS.
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
// Datos personales típicos en fichas: email, DNI/NIE, teléfonos de 9 dígitos
const PII = ['@', '\\b\\d{8}[A-Za-z]\\b', '\\b[XYZ]\\d{7}[A-Za-z]\\b', '^\\d{9}$'];

async function shot(page, name) { await sleep(400); await page.screenshot({ path: join(IMAGES, name) }); console.log('  ✓ ' + name); }

(async () => {
  const browser = await puppeteer.launch({ executablePath: CHROME, headless: false, defaultViewport: { width: 1440, height: 900, deviceScaleFactor: 2 }, userDataDir: PROFILE });
  const page = (await browser.pages())[0] || (await browser.newPage());
  await page.setViewport({ width: 1440, height: 900, deviceScaleFactor: 2 });

  await page.goto(BASE + '/users', { waitUntil: 'networkidle2' });
  if (page.url().includes('/login')) {
    console.log('\n>>> Inicia sesión en la ventana de Chrome (usuario de OFICINA). Esperando...\n');
    for (let i = 0; i < 150; i++) { await sleep(2000); if (!page.url().includes('/login')) break; }
    await page.goto(BASE + '/users', { waitUntil: 'networkidle2' });
  }
  if (page.url().includes('/login')) {
    console.error('\n❌ Sigues sin sesión iniciada. Inicia sesión como OFICINA y vuelve a ejecutar. No se captura nada.\n');
    await browser.close();
    process.exit(1);
  }
  console.log('Sesión OK.\n');

  // --- 08 Listado de usuarios (difuminar columnas PII 2..9) ---
  await page.evaluate(() => {
    document.querySelectorAll('table tbody tr').forEach((tr) => {
      tr.querySelectorAll('td').forEach((td, i) => { if (i >= 1 && i <= 8) td.style.filter = 'blur(5px)'; });
    });
  });
  await blurOperator(page);
  await shot(page, '08-users-index.png');

  // --- Ficha de un trabajador (oficina viendo a otro: operario de ejemplo) ---
  await page.goto(BASE + '/users/10', { waitUntil: 'networkidle2' });
  await sleep(500);
  // Intentar expandir "Ver más" para mostrar las secciones
  await page.evaluate(() => {
    const btn = [...document.querySelectorAll('button, a, span')].find((b) => /ver m[áa]s/i.test((b.textContent || '').trim()));
    if (btn) btn.click();
  });
  await sleep(600);
  await page.evaluate(() => { document.querySelectorAll('h1').forEach((h) => h.style.filter = 'blur(6px)'); });
  await blurMatching(page, PII);
  await blurOperator(page);
  await shot(page, '09-ficha-trabajador.png');

  // --- 10 Calendario de la ficha ---
  await page.evaluate(() => { const fc = document.querySelector('.fc'); if (fc) fc.scrollIntoView({ block: 'center' }); });
  await sleep(700);
  await shot(page, '10-ficha-calendario.png');

  // --- 11 Mi perfil (del usuario logueado) ---
  await page.goto(BASE + '/users', { waitUntil: 'networkidle2' });
  const miPerfil = await page.evaluate(() => {
    const a = document.querySelector('a[href*="/mi-perfil/"]');
    return a ? a.href : null;
  });
  if (miPerfil) {
    await page.goto(miPerfil, { waitUntil: 'networkidle2' });
    await sleep(500);
    await page.evaluate(() => {
      const btn = [...document.querySelectorAll('button, a, span')].find((b) => /ver m[áa]s/i.test((b.textContent || '').trim()));
      if (btn) btn.click();
    });
    await sleep(600);
    await page.evaluate(() => { document.querySelectorAll('h1').forEach((h) => h.style.filter = 'blur(6px)'); });
    await blurMatching(page, PII);
    await blurOperator(page);
    await shot(page, '11-mi-perfil.png');
  } else {
    console.log('  ⚠️ No se encontró enlace a mi-perfil; se omite 11.');
  }

  // --- 12 Planificación de turnos (timeline de recursos) ---
  await page.goto(BASE + '/planificacion/trabajadores', { waitUntil: 'networkidle2' });
  // El calendario carga sus datos por AJAX tras render; esperar a que aparezcan eventos
  for (let i = 0; i < 20; i++) { await sleep(500); const ok = await page.evaluate(() => !!document.querySelector('.fc-event, .fc-timeline-event')); if (ok) break; }
  await sleep(800);
  // Difuminar nombres y fotos de los trabajadores en la columna de recursos
  await page.evaluate(() => {
    document.querySelectorAll('.fc-datagrid-cell .text-blue-700, .fc-datagrid-cell a > div > div').forEach((el) => { el.style.filter = 'blur(5px)'; });
    document.querySelectorAll('.fc-datagrid-cell img').forEach((img) => { img.style.filter = 'blur(6px)'; });
  });
  await blurOperator(page);
  await shot(page, '12-planificacion.png');

  console.log('\n✅ Capturas terminadas en /images');
  await browser.close();
})();
