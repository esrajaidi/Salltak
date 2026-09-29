/**
 * Local read-only SHEIN share probe: prints only structural metadata.
 * Run on the user's machine, where the shared page opens normally.
 * No raw URL, token, cookie, response body, item, image, or price is saved.
 */
import process from 'node:process';
import readline from 'node:readline/promises';
import { chromium } from 'playwright-chromium';
import { inspectShareShape } from './shein-share-schema-shape.mjs';

const MAX_RESPONSE_BYTES = 2_500_000;
const MAX_RESPONSE_COUNT = 100;
const MAX_RESULTS = 12;

function sheinUrl(input) {
  try {
    const url = new URL(String(input).trim());
    return url.protocol === 'https:' && /(^|\.)shein\.com$/i.test(url.hostname);
  } catch { return false; }
}

function shareContext(input) {
  try {
    const url = new URL(input);
    if (!sheinUrl(input)) return { groupId: '', shareToken: '' };
    return { groupId: url.searchParams.get('group_id') || '', shareToken: url.searchParams.get('shc') || '' };
  } catch { return { groupId: '', shareToken: '' }; }
}

function finalPageKind(input) {
  try {
    const path = new URL(input).pathname.toLowerCase();
    if (path.includes('/cart/share') || path.includes('/share/landing')) return 'share_landing';
    if (path.includes('/cart')) return 'cart';
    return 'other';
  } catch { return 'unavailable'; }
}

const rl = readline.createInterface({ input: process.stdin, output: process.stdout });
const raw = await rl.question('الصقي رابط مشاركة SHEIN الذي يفتح عندك ثم Enter: ');
rl.close();
const target = String(raw || '').trim();
if (!sheinUrl(target)) {
  process.stdout.write('خطأ: يلزم رابط HTTPS تابع لـSHEIN. لم يُرسل أي طلب.\n');
  process.exitCode = 1;
} else {
  let browser;
  const responseTasks = [];
  const findings = [];
  let inspected = 0;
  let challenge = false;
  let finalPage = 'unavailable';
  try {
    // Headed by default so the user sees the normal landing page.
    // --headless is optional; no security challenge is automated.
    browser = await chromium.launch({ headless: process.argv.includes('--headless') });
    const context = await browser.newContext({ locale: 'ar-AE', viewport: { width: 430, height: 932 }, isMobile: true, hasTouch: true });
    const page = await context.newPage();
    page.on('response', response => {
      const task = (async () => {
        if (inspected >= MAX_RESPONSE_COUNT || !sheinUrl(response.url())) return;
        try {
          const mime = String(response.headers()['content-type'] || '').toLowerCase();
          if (!mime.includes('json') && !mime.includes('text')) return;
          const length = Number(response.headers()['content-length'] || 0);
          if (length > MAX_RESPONSE_BYTES) return;
          const rawBody = await response.text();
          if (!rawBody || rawBody.length > MAX_RESPONSE_BYTES) return;
          let json;
          try { json = JSON.parse(rawBody); } catch { return; }
          if (!json || typeof json !== 'object') return;
          inspected++;
          const original = shareContext(target);
          const current = shareContext(page.url());
          const contextIdentity = {
            groupId: original.groupId || current.groupId,
            shareToken: original.shareToken || current.shareToken,
          };
          const shapes = inspectShareShape(json, contextIdentity);
          for (const shape of shapes) {
            if (findings.length >= MAX_RESULTS) break;
            findings.push(shape);
          }
        } catch { /* Unreadable responses do not reveal raw data. */ }
      })();
      responseTasks.push(task);
    });

    try { await page.goto(target, { waitUntil: 'domcontentloaded', timeout: 18_000 }); }
    catch { /* Report observed page category and structures. */ }
    await page.waitForTimeout(7_000);
    await Promise.allSettled(responseTasks);
    finalPage = finalPageKind(page.url());
    const bodyText = await page.locator('body').innerText({ timeout: 1500 }).catch(() => '');
    challenge = /captcha|security check|verify you are human|أكمل التحقق|تحقق أمني/i.test(bodyText);
    const summary = {
      status: challenge ? 'security_challenge' : findings.some(f => f.boundToShare) ? 'share_structure_observed' : 'share_structure_unverified',
      finalPage,
      jsonResponsesInspected: inspected,
      shareListShapes: findings,
    };
    process.stdout.write(`\nنتيجة آمنة للفحص (انسخي هذا JSON فقط، ولا ترسلي ملفات الشبكة):\n${JSON.stringify(summary, null, 2)}\n`);
  } catch (error) {
    const code = error?.name === 'TimeoutError' ? 'browser_timeout' : 'browser_unavailable';
    process.stdout.write(`تعذّر إكمال الفحص: ${code}. لم تُحفظ بيانات السلة.\n`);
    process.exitCode = 1;
  } finally {
    await browser?.close().catch(() => {});
  }
}
