import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { spawnSync } from 'node:child_process';

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

  // The direct shared-cart URL is known before this retry begins. Running a
  // separate Chrome preflight only repeats the navigation and adds latency.
  // Retry the strict worker once with a macOS Safari identity because real
  // desktop Safari can receive a different SHEIN landing experience.
  const safariUserAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15';
  const childInput = JSON.stringify({
    ...cfg,
    url: targetUrl,
    profileDir,
    locale: 'ar-AE',
    viewport: { width: 1440, height: 960 },
    userAgent: safariUserAgent,
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
