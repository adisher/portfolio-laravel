#!/usr/bin/env node
/**
 * Product screenshot capture for the article screenshot library.
 *
 * Public pages are captured headless and automatically. Admin pages open a real
 * browser and wait for YOU to log in (this script never reads or stores any
 * password), then capture. Output goes to scripts/screenshots/out/<slug>.png,
 * ready to import into the Media library.
 *
 * Setup (once):
 *   npm install
 *   npx playwright install chromium
 *
 * Run:
 *   node scripts/screenshots/capture.mjs                 # default: targets.biolink.json
 *   node scripts/screenshots/capture.mjs targets.foo.json
 *
 * Each target in the JSON config:
 *   { slug, url, viewport?, selector?, fullPage?, auth?, loginUrl?, waitFor?, settleMs? }
 *   - selector: capture just that element (a tight crop). Omit/null to capture the viewport.
 *   - auth: true means the page is behind login (handled in a headed browser).
 */
import { chromium } from 'playwright';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';
import { mkdirSync, readFileSync } from 'node:fs';
import { createInterface } from 'node:readline';

const __dirname = dirname(fileURLToPath(import.meta.url));
const configFile = process.argv[2] || 'targets.biolink.json';
const targets = JSON.parse(readFileSync(join(__dirname, configFile), 'utf8'));
const OUT = join(__dirname, 'out');
mkdirSync(OUT, { recursive: true });

const ask = (q) =>
  new Promise((res) => {
    const rl = createInterface({ input: process.stdin, output: process.stdout });
    rl.question(q, (a) => {
      rl.close();
      res(a);
    });
  });

async function capture(page, t) {
  await page.setViewportSize(t.viewport ?? { width: 1280, height: 900 });
  await page.goto(t.url, { waitUntil: 'networkidle', timeout: 60000 });
  if (t.waitFor) {
    try {
      await page.waitForSelector(t.waitFor, { timeout: 15000 });
    } catch {
      console.warn(`  ! waitFor selector missing for ${t.slug}, continuing`);
    }
  }
  await page.waitForTimeout(t.settleMs ?? 1200);

  const file = join(OUT, `${t.slug}.png`);
  if (t.selector) {
    const el = await page.$(t.selector);
    if (el) {
      await el.screenshot({ path: file });
    } else {
      console.warn(`  ! selector "${t.selector}" not found for ${t.slug}, capturing viewport`);
      await page.screenshot({ path: file });
    }
  } else {
    await page.screenshot({ path: file, fullPage: !!t.fullPage });
  }
  console.log(`  ok  ${t.slug} -> ${file}`);
}

const publicTargets = targets.filter((t) => !t.auth);
const authTargets = targets.filter((t) => t.auth);

// Headed only when we need you to log in for admin pages.
const browser = await chromium.launch({ headless: authTargets.length === 0 });

try {
  if (publicTargets.length) {
    console.log('Capturing public pages...');
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    for (const t of publicTargets) await capture(page, t);
    await ctx.close();
  }

  if (authTargets.length) {
    console.log('\nAdmin pages need a login.');
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    const first = authTargets[0];
    await page.goto(first.loginUrl ?? first.url, { waitUntil: 'networkidle' }).catch(() => {});
    await ask('  Log in in the opened browser, then press Enter here to continue... ');
    for (const t of authTargets) await capture(page, t);
    await ctx.close();
  }
} finally {
  await browser.close();
}

console.log(`\nDone. Files in ${OUT}. Import them into the Media library (filename = slug).`);
