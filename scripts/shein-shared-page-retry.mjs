import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { spawnSync } from 'node:child_process';
import { chromium } from 'playwright-chromium';

async function readInput() {
  let raw = '';
  for await (const chunk of process.stdin) raw += chunk;
  try { return JSON.parse(raw || '{}'); } catch { return {}; }
}

function isSheinUrl(raw) {
  try {
    const url = new URL(String(raw || ''));
    const host = url.hostname.toLowerCase();
    return url.protocol === 'https:' && (host === 'shein.com' || host.endsWith('.shein.com'));
  } catch {
    return false;
  }
}

const cfg = await readInput();
const targetUrl = String(cfg.url || '');
const strictWorker = path.resolve(process.cwd(), 'scripts/shein-shared-page-import.mjs');
const profileDir = path.resolve(String(cfg.profileDir || path.join(process.cwd(), 'storage/app/shein-shared-page-retry')));
const timeoutMs = Math.min(90_000, Math.max(5_000, Number(cfg.timeoutMs || 35_000)));

if (!isSheinUrl(targetUrl) || !fs.existsSync(strictWorker)) {
  process.stdout.write(JSON.stringify({
    ok: false,
    status: !isSheinUrl(targetUrl) ? 'invalid_url' : 'unavailable',
    message: !isSheinUrl(targetUrl) ? 'Invalid SHEIN URL.' : 'Strict shared-page worker is missing.',
    items: [],
    payloads: [],
  }));
  process.exitCode = 2;
} else {
  fs.mkdirSync(profileDir, { recursive: true });
  let finalUrl = targetUrl;
  let context;

  try {
    context = await chromium.launchPersistentContext(profileDir, {
      headless: cfg.headless !== false,
      locale: 'ar-AE',
      viewport: { width: 1280, height: 900 },
      userAgent: 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36',
      args: ['--disable-dev-shm-usage'],
    });

    const page = context.pages()[0] || await context.newPage();
    await page.goto(targetUrl, { waitUntil: 'domcontentloaded', timeout: timeoutMs });
    try { await page.waitForLoadState('networkidle', { timeout: Math.min(10_000, timeoutMs) }); } catch {}
    await page.waitForTimeout(1800);

    if (isSheinUrl(page.url())) finalUrl = page.url();
  } catch {
    finalUrl = targetUrl;
  } finally {
    if (context) await context.close().catch(() => {});
  }

  const childInput = JSON.stringify({
    ...cfg,
    url: finalUrl,
    profileDir,
  });

  const child = spawnSync(process.execPath, [strictWorker], {
    cwd: process.cwd(),
    input: childInput,
    encoding: 'utf8',
    maxBuffer: 8 * 1024 * 1024,
    timeout: Math.max(20_000, timeoutMs + 20_000),
  });

  if (child.error) {
    process.stdout.write(JSON.stringify({
      ok: false,
      status: 'failed',
      message: child.error.message || 'Alternate shared-page retry failed.',
      items: [],
      payloads: [],
    }));
    process.exitCode = 1;
  } else if (!String(child.stdout || '').trim()) {
    process.stdout.write(JSON.stringify({
      ok: false,
      status: 'failed',
      message: String(child.stderr || '').trim() || 'Strict shared-page retry returned no result.',
      items: [],
      payloads: [],
    }));
    process.exitCode = Number(child.status || 1);
  } else {
    process.stdout.write(String(child.stdout));
    process.exitCode = Number(child.status || 0);
  }
}
