/**
 * Admin-only, read-only SHEIN product price diagnostic.
 *
 * Compares a single product page on www.shein.com with and without the
 * account owner's manually exported storageState. DOES NOT claim that cookie
 * injection proves login, or that any visible amount is the checkout price.
 * Never send cookies, localStorage, or full HTML to stdout/logs.
 */
import process from 'node:process';
import { chromium } from 'playwright-chromium';

async function readInput() {
  let raw = '';
  for await (const chunk of process.stdin) {
    raw += chunk;
    if (raw.length > 400_000) throw new Error('request_too_large');
  }
  return JSON.parse(raw || '{}');
}

function permittedProduct(raw) {
  try {
    const u = new URL(String(raw || ''));
    return u.protocol === 'https:' && u.hostname.toLowerCase() === 'www.shein.com'
      && !u.username && !u.password && !u.port
      && u.href.length <= 2000
      && /(?:^|[-/])p-\d{4,20}\.html$/i.test(u.pathname);
  } catch { return false; }
}

function sanitizedState(raw) {
  const isSheinDomain = domain => {
    const h = String(domain || '').replace(/^\./, '').toLowerCase();
    return h === 'shein.com' || h.endsWith('.shein.com');
  };
  if (!raw || !Array.isArray(raw.cookies)) return null;
  const cookies = raw.cookies.filter(c => c && c.secure === true
    && isSheinDomain(c.domain) && typeof c.name === 'string'
    && typeof c.value === 'string' && c.value.length <= 12000);
  if (!cookies.length) return null;
  const origins = (Array.isArray(raw.origins) ? raw.origins : []).filter(o => {
    try {
      const u = new URL(o.origin);
      return u.protocol === 'https:' && isSheinDomain(u.hostname);
    } catch { return false; }
  });
  return { cookies, origins };
}

const output = (status, extra = {}) => {
  process.stdout.write(JSON.stringify({ status, ...extra }));
};

async function observe(context, productUrl, timeoutMs) {
  const page = await context.newPage();
  try {
    // Bootstrap on the website on which the owner really logs in.
    await page.goto('https://www.shein.com/', {
      waitUntil: 'domcontentloaded', timeout: timeoutMs,
    });
    await page.goto(productUrl, { waitUntil: 'domcontentloaded', timeout: timeoutMs });
    await page.waitForTimeout(2200);

    const host = new URL(page.url()).hostname.toLowerCase();
    if (host !== 'www.shein.com') {
      return { status: 'redirected_from_www', finalHost: host };
    }

    const data = await page.evaluate(() => {
      const usd = /\$\s*(\d{1,5}(?:[.,]\d{2})?)/g;
      const dollarValues = text => {
        const values = [];
        for (const match of String(text || '').matchAll(usd)) {
          const n = Number(match[1].replace(',', ''));
          if (n > 0 && Number.isFinite(n) && n < 20000) values.push(n);
        }
        return values;
      };

      // Prioritize price DOM near the product header. Avoid collecting
      // recommended items elsewhere on a lengthy SHEIN product page.
      const priceNodes = Array.from(document.querySelectorAll(
        '[data-testid*="price" i], [class*="price" i], [id*="price" i]'
      )).slice(0, 180);
      const candidates = [];
      const seen = new Set();
      for (const el of priceNodes) {
        const rect = el.getBoundingClientRect();
        if (rect.width <= 0 || rect.height <= 0 || rect.top < -50 || rect.top > 1050) continue;
        const text = (el.innerText || el.textContent || '').trim().slice(0, 180);
        if (!text || text.length > 180) continue;
        for (const price of dollarValues(text)) {
          const key = price.toFixed(2);
          if (seen.has(key)) continue;
          seen.add(key);
          candidates.push({ value: price, top: Math.round(rect.top) });
          if (candidates.length >= 12) break;
        }
        if (candidates.length >= 12) break;
      }

      if (!candidates.length) {
        // This is a weak fallback and is shown as evidence, NEVER a confirmed
        // logged-in purchase price. It can include offers or recommendations.
        const body = (document.body?.innerText || '').slice(0, 5000);
        for (const n of dollarValues(body)) {
          const key = n.toFixed(2);
          if (seen.has(key)) continue;
          seen.add(key);
          candidates.push({ value: n, top: -1 });
          if (candidates.length >= 8) break;
        }
      }

      return {
        pageTitle: (document.title || '').slice(0, 110),
        visibleUsdCandidates: candidates,
        rendered: Boolean(document.body?.innerText?.trim()),
      };
    });

    return { status: data.rendered ? 'page_loaded' : 'empty_page',
      finalHost: host, pageTitle: data.pageTitle,
      visibleUsdCandidates: data.visibleUsdCandidates };
  } catch (e) {
    // No credentials, cookies, remote HTML or arbitrary error body in output.
    return { status: String(e?.name || '').includes('Timeout') ? 'timeout' : 'page_unavailable' };
  } finally {
    await page.close().catch(() => {});
  }
}

let browser;
try {
  const input = await readInput();
  if (!permittedProduct(input.url)) {
    output('invalid_url', { message: 'Provide a www.shein.com product detail link ending in -p-ID.html.' });
    process.exitCode = 2;
  } else {
    const storage = sanitizedState(input.accountSession);
    if (!storage) {
      output('no_session', { message: 'Upload an authenticated SHEIN browser state in Salltak admin first.' });
    } else {
      browser = await chromium.launch({ headless: true, args: ['--disable-dev-shm-usage'] });
      const options = { locale: 'en-AE', viewport: { width: 1320, height: 860 } };
      const account = await browser.newContext({ ...options, storageState: storage });
      const guest = await browser.newContext(options);
      const timeoutMs = 25_000;
      const loggedInAttempt = await observe(account, input.url, timeoutMs);
      const guestAttempt = await observe(guest, input.url, timeoutMs);
      output('probed', {
        accountSessionSupplied: true,
        accountAuthenticated: false, // Cannot prove server-side login from session injection.
        purchasePriceConfirmed: false,
        owner: loggedInAttempt,
        guest: guestAttempt,
        note: 'Visible amounts are unverified; compare exact same variant and shipping settings manually.',
      });
      await account.close();
      await guest.close();
    }
  }
} catch {
  output('failed', { message: 'The product price comparison could not be completed.' });
  process.exitCode = 1;
} finally {
  await browser?.close().catch(() => {});
}
