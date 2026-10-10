/**
 * Read-only, local interactive diagnostic. Opens the existing Chromium profile
 * created by npm run shein:login, so an operator can visually confirm which
 * SHEIN page and product price are rendered by the actual logged-in browser.
 *
 * Never prints cookies, localStorage, full HTML, page text or screenshots.
 * Never saves or changes the SHEIN session file or customer prices.
 */
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import readline from 'node:readline/promises';
import { chromium } from 'playwright-chromium';

const DEFAULT_URL = 'https://www.shein.com/Women-s-New-Niche-Design-Shoulder-Large-Capacity-Underarm-Bag-Elegant-Long-Red-Baguette-Bag-p-61586172.html';
const profileDirectory = path.resolve('storage/app/shein-session/browser');
const sourceUrl = process.argv[2] || DEFAULT_URL;

function validProductUrl(raw) {
  try {
    const url = new URL(raw);
    return url.protocol === 'https:'
      && url.hostname === 'www.shein.com'
      && !url.username && !url.password && !url.port
      && url.href.length <= 2000
      && /(?:^|[-/])p-\d{4,20}\.html$/i.test(url.pathname);
  } catch {
    return false;
  }
}

const terminal = readline.createInterface({ input: process.stdin, output: process.stdout });
let browser;
try {
  if (!validProductUrl(sourceUrl)) {
    throw new Error('استعملي رابط منتج حقيقي من https://www.shein.com ينتهي بـ -p-رقم.html.');
  }
  if (!fs.existsSync(profileDirectory)) {
    throw new Error('ما لقيناش ملف المتصفح المحلي. شغّلي npm run shein:login أولًا وسجّلي دخولك.');
  }

  // Unlike the production probe, this uses the SAME interactive Chromium
  // profile in which the owner manually signed in. It is not headless.
  browser = await chromium.launchPersistentContext(profileDirectory, {
    headless: false,
    locale: 'en-AE',
    viewport: { width: 1320, height: 900 },
  });
  const page = browser.pages()[0] || await browser.newPage();
  const response = await page.goto(sourceUrl, {
    waitUntil: 'domcontentloaded',
    timeout: 45_000,
  }).catch(() => null);

  process.stdout.write('\nفتحنا صفحة المنتج في Chromium المحلي الخاص بحساب SHEIN.\n');
  process.stdout.write('1) تأكدي بعينك أن حساب الشراء مفتوح، وأنه نفس المنتج واللون Burgundy والمقاس Large.\n');
  process.stdout.write('2) راجعي بلد الشحن والعملة. إذا ظهرت شاشة تحقق أمني، كمّليها بنفسك فقط.\n');
  process.stdout.write('3) إذا ظهرت صفحة المنتج والسعر، اضغطي Enter هنا حتى نفحص العناصر المعروضة.\n');
  await terminal.question('اضغطي Enter بعد فحص صفحة SHEIN يدويًا (Ctrl+C للإلغاء)... ');

  if (page.isClosed()) throw new Error('نافذة Chromium اتقفلت قبل الفحص. افتحيها من جديد.');
  const evidence = await page.evaluate(() => {
    const productHeader = document.querySelector(
      '.product-intro, [class*="product-intro__head"], [data-testid="product-detail"]'
    );
    const selectors = [
      '[class*="product-intro__head-price"]',
      '[class*="product-intro__price"]',
      '[class*="product-intro"] [class*="price"]',
      '[data-testid*="product-price" i]',
      '[data-testid*="sale-price" i]',
      '[class*="goods-price"]',
      '[class*="sale-price"]',
      '[itemprop="price"]',
    ].join(',');
    const nodes = Array.from(document.querySelectorAll(selectors)).slice(0, 160);
    const candidates = [];
    const seen = new Set();

    for (const el of nodes) {
      const r = el.getBoundingClientRect();
      if (r.width <= 0 || r.height <= 0 || r.top < -40 || r.top > 1100) continue;
      if (el.closest('[class*="recommend"], [class*="similar"], del, s')) continue;
      const text = String(el.innerText || el.textContent || '').slice(0, 130);
      for (const m of text.matchAll(/(?:US\$|USD|\$)\s*(\d{1,5}(?:\.\d{2})?)/gi)) {
        const value = Number(m[1]);
        if (!(value > 0 && value < 20000)) continue;
        const key = value.toFixed(2);
        if (seen.has(key)) continue;
        seen.add(key);
        candidates.push({
          priceUsd: value,
          withinProductSection: Boolean(el.closest('[class*="product-intro"]')),
          top: Math.round(r.top),
        });
        if (candidates.length >= 8) break;
      }
      if (candidates.length >= 8) break;
    }

    const body = String(document.body?.innerText || '');
    return {
      productSectionPresent: Boolean(productHeader),
      priceNodeCount: nodes.length,
      usdSymbolInVisibleText: /\$\s*\d/.test(body.slice(0, 5000)),
      visibleCandidates: candidates,
      renderedTextLength: body.length,
    };
  });

  process.stdout.write('\nنتيجة الفحص المحلي (أرقام محتملة فقط، مش أسعار شراء معتمدة):\n');
  process.stdout.write(JSON.stringify({
    productId: new URL(sourceUrl).pathname.match(/p-(\d{4,20})\.html$/i)?.[1] || '',
    finalHost: new URL(page.url()).hostname,
    initialHttpStatus: response?.status() || null,
    ...evidence,
  }, null, 2) + '\n');
  process.stdout.write('\nلو المنتج ظاهر في Chromium لكن visibleCandidates فاضية، صوّري نافذة المنتج فقط بعد إخفاء بيانات الحساب؛ ما تبعتيش ملف session.json.\n');
} catch (error) {
  process.stderr.write(String(error?.message || 'تعذر الفحص المحلي') + '\n');
  process.exitCode = 1;
} finally {
  await browser?.close().catch(() => {});
  terminal.close();
}
