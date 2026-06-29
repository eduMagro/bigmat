// Captura de pantallas del manual de Bigmat con Puppeteer (Chrome instalado).
// - Usa un perfil persistente (.pptr-profile): inicias sesión UNA vez y se recuerda.
// - Difumina datos personales por CSS antes de capturar.
// - Crea una incorporación de PRUEBA (Juan Ejemplo) para las capturas de ficha y
//   formulario público, y la ELIMINA al terminar.
//
// Uso: node capture.mjs

import puppeteer from 'puppeteer-core';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import { existsSync } from 'node:fs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const IMAGES = join(__dirname, 'images');
const PROFILE = join(__dirname, '.pptr-profile');
const BASE = 'http://localhost/bigmat/public';

const CHROME = [
  'C:/Program Files/Google/Chrome/Application/chrome.exe',
  'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
].find(existsSync);

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

// Difumina las hojas (elementos sin hijos) cuyo texto coincide con algún regex.
async function blurMatching(page, patterns) {
  await page.evaluate((pats) => {
    const regs = pats.map((p) => new RegExp(p));
    document.querySelectorAll('body *').forEach((el) => {
      if (el.children.length === 0) {
        const t = (el.textContent || '').trim();
        if (t && regs.some((r) => r.test(t))) {
          el.style.filter = 'blur(5px)';
        }
      }
    });
  }, patterns);
}

// Difumina la columna "Candidato" del listado y el nombre del operario en la cabecera.
async function blurCommon(page) {
  await page.evaluate(() => {
    document.querySelectorAll('table tbody tr td:first-child').forEach((el) => {
      el.style.filter = 'blur(5px)';
    });
    // Nombre del operario en la cabecera (span.hidden.sm:block dentro del menú de usuario)
    const span = document.querySelector('header span.sm\\:block, nav span.sm\\:block');
    if (span) span.style.filter = 'blur(5px)';
  });
}

async function shot(page, name) {
  await sleep(400);
  const path = join(IMAGES, name);
  await page.screenshot({ path });
  console.log('  ✓ ' + name);
}

(async () => {
  if (!CHROME) { console.error('No se encontró Chrome.'); process.exit(1); }

  const browser = await puppeteer.launch({
    executablePath: CHROME,
    headless: false,
    defaultViewport: { width: 1440, height: 900, deviceScaleFactor: 2 },
    userDataDir: PROFILE,
    args: ['--start-maximized'],
  });
  const page = (await browser.pages())[0] || (await browser.newPage());
  await page.setViewport({ width: 1440, height: 900, deviceScaleFactor: 2 });

  // --- Asegurar sesión ----------------------------------------------------
  await page.goto(BASE + '/incorporaciones', { waitUntil: 'networkidle2' });
  if (page.url().includes('/login')) {
    console.log('\n>>> INICIA SESIÓN en la ventana de Chrome que se ha abierto.');
    console.log('>>> (Para la pantalla de "Registrar Usuario" usa un usuario del departamento Programador.)');
    console.log('>>> Esperando hasta 5 minutos...\n');
    for (let i = 0; i < 150; i++) {
      await sleep(2000);
      if (!page.url().includes('/login')) break;
    }
    if (page.url().includes('/login')) { console.error('No se detectó inicio de sesión.'); await browser.close(); process.exit(1); }
    await page.goto(BASE + '/incorporaciones', { waitUntil: 'networkidle2' });
  }
  console.log('Sesión OK. Capturando...\n');

  // --- 01 Listado de incorporaciones -------------------------------------
  await blurCommon(page);
  await shot(page, '01-incorporaciones-index.png');

  // --- 02 Formulario nueva incorporación (vacío) -------------------------
  await page.goto(BASE + '/incorporaciones/create', { waitUntil: 'networkidle2' });
  await blurCommon(page);
  await shot(page, '02-incorporacion-create.png');

  // --- Crear incorporación de PRUEBA (Juan Ejemplo) ----------------------
  console.log('Creando incorporación de prueba...');
  await page.select('select[name="empresa_destino"]', await page.$eval('select[name="empresa_destino"] option:nth-child(2)', o => o.value));
  await page.type('input[name="name"]', 'Juan');
  await page.type('input[name="primer_apellido"]', 'Ejemplo');
  await page.type('input[name="segundo_apellido"]', 'Demostración');
  await page.type('input[name="telefono_provisional"]', '612345678');
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle2' }),
    page.evaluate(() => {
      const btn = [...document.querySelectorAll('button[type="submit"]')]
        .find((b) => /Crear Incorporaci/i.test(b.textContent));
      (btn || document.querySelector('form[action*="incorporaciones"] button[type="submit"]')).click();
    }),
  ]);
  const showUrl = page.url();
  console.log('  Ficha de prueba: ' + showUrl);

  // --- 03 Ficha con el enlace (datos de prueba, sin difuminar) -----------
  await blurCommon(page); // por si acaso, difumina operario
  await shot(page, '03-incorporacion-show-enlace.png');

  // --- 06 Documentos post-incorporación (scroll a la sección) ------------
  await page.evaluate(() => {
    const h = [...document.querySelectorAll('h2,h3')].find((e) => /Documentos Post/i.test(e.textContent));
    if (h) h.scrollIntoView({ block: 'start' });
  });
  await sleep(500);
  await shot(page, '06-documentos-post.png');

  // --- Leer el token del formulario público ------------------------------
  const tokenUrl = await page.evaluate(() => {
    let u = null;
    document.querySelectorAll('input').forEach((i) => { if (i.value && i.value.includes('/incorporacion/')) u = i.value; });
    if (!u) { const m = document.body.innerText.match(/https?:\/\/\S+\/incorporacion\/[A-Za-z0-9]+/); if (m) u = m[0]; }
    return u;
  });
  console.log('  Token público: ' + tokenUrl);

  // --- 04 Formulario público vacío (datos de prueba) ---------------------
  if (tokenUrl) {
    await page.goto(tokenUrl, { waitUntil: 'networkidle2' });
    await shot(page, '04-formulario-publico.png');
  } else {
    console.log('  ⚠️ No se pudo leer el token; se omite 04.');
  }

  // --- 05 Página de confirmación (de una incorporación REAL completada) ---
  // Buscar en el listado una incorporación completada y abrir su enlace público.
  await page.goto(BASE + '/incorporaciones', { waitUntil: 'networkidle2' });
  const firstShow = await page.$eval('table tbody tr a[href*="/incorporaciones/"]', (a) => a.href);
  await page.goto(firstShow, { waitUntil: 'networkidle2' });
  const realToken = await page.evaluate(() => {
    let u = null;
    document.querySelectorAll('input').forEach((i) => { if (i.value && i.value.includes('/incorporacion/')) u = i.value; });
    return u;
  });
  if (realToken) {
    await page.goto(realToken, { waitUntil: 'networkidle2' });
    // Difuminar DNI, email y teléfono del resumen
    await blurMatching(page, ['@', '\\b\\d{8}[A-Za-z]\\b', '\\b[XYZ]\\d{7}[A-Za-z]\\b', '^\\d{9}$']);
    await shot(page, '05-formulario-completado.png');
  } else {
    console.log('  ⚠️ No se pudo obtener token de una incorporación completada; se omite 05.');
  }

  // --- 07 Registrar usuario ---------------------------------------------
  await page.goto(BASE + '/register', { waitUntil: 'networkidle2' });
  if (page.url().includes('/register')) {
    await blurCommon(page);
    await shot(page, '07-registro-usuario.png');
  } else {
    console.log('  ⚠️ Sin permiso para /register (no es Programador). Redirigido a: ' + page.url());
    console.log('     Inicia sesión con un usuario Programador y vuelve a ejecutar para esta captura.');
  }

  // --- Limpieza: eliminar la incorporación de prueba ---------------------
  console.log('\nEliminando incorporación de prueba...');
  page.on('dialog', async (d) => { try { await d.accept(); } catch {} });
  try {
    await page.goto(showUrl, { waitUntil: 'networkidle2' });
    const delBtn = await page.evaluateHandle(() => {
      return [...document.querySelectorAll('button, a')].find((b) => /Eliminar incorporaci/i.test(b.textContent));
    });
    if (delBtn && delBtn.asElement()) {
      await delBtn.asElement().click();
      await sleep(800);
      // Confirmar en posible modal
      const confirm = await page.evaluateHandle(() => {
        return [...document.querySelectorAll('button, a')].find((b) => /^(Eliminar|Confirmar|Sí|Si)/i.test(b.textContent.trim()));
      });
      if (confirm && confirm.asElement()) { await confirm.asElement().click(); await sleep(1000); }
      console.log('  ✓ Incorporación de prueba eliminada (verifica en el listado).');
    } else {
      console.log('  ⚠️ No se encontró el botón de eliminar; borra la incorporación de prueba a mano: ' + showUrl);
    }
  } catch (e) {
    console.log('  ⚠️ No se pudo eliminar automáticamente. Bórrala a mano: ' + showUrl, e.message);
  }

  console.log('\n✅ Capturas terminadas en /images');
  await browser.close();
})();
