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
    // Compare the same product with exactly the same browser locale and URL.
    // Do not infer that a stored cookie proves SHEIN authenticated the account.
    const response = await page.goto(productUrl, {
      waitUntil: 'domcontentloaded', timeout: timeoutMs,
    });
    const httpStatus = Number(response?.status() || 0);
    const host = new URL(page.url()).hostname.toLowerCase();
    if (host !== 'www.shein.com') {
      return { status: 'redirected_from_www', finalHost: host, httpStatus };
    }
    if (httpStatus === 403 || httpStatus === 429) {
      return { status: 'access_limited', finalHost: host, httpStatus };
    }

    const productId = new URL(productUrl).pathname.match(/p-(\d{4,20})\.html$/i)?.[1] || '';
    let evidence = null;

    // SHEIN hydrates the product price client-side and may not render it within
    // 2 seconds. Wait a short, bounded period for product evidence, not an
    // arbitrary network-idle state (which may never arrive on retail pages).
    for (let poll = 0; poll < 4; poll++) {
      evidence = await page.evaluate(({ productId }) => {
        const PRICE_RE = /(?:US\$|USD|\$)\s*([\d,]{1,9}(?:\.\d{2})?)/giu;
        const amounts = raw => {
          const values = [];
          for (const match of String(raw || '').matchAll(PRICE_RE)) {
            const n = Number(match[1].replaceAll(',', ''));
            if (n > 0 && Number.isFinite(n) && n <= 20000) values.push(n);
          }
          return values;
        };

        const body = String(document.body?.innerText || '');
        const challengeLikely = /(?:verify you are human|unusual traffic|security verification|access denied|captcha|تحقق أمني|أثبت أنك إنسان)/iu
          .test(body.slice(0, 2500));
        const productIntro = document.querySelector(
          '.product-intro, [class*="product-intro__head"], [class*="product-intro__price"], [data-testid="product-detail"]'
        );
        const selectors = [
          '[class*="product-intro__head-price"]',
          '[class*="product-intro__price"]',
          '[data-testid*="product-price" i]',
          '[data-testid*="sale-price" i]',
          '[class*="goods-price"]',
          '[class*="sale-price"]',
          '[itemprop="price"]',
          '[data-price]',
        ].join(',');
        const rawNodes = Array.from(document.querySelectorAll(selectors)).slice(0, 220);
        const candidates = [];
        const seen = new Set();

        const add = (value, source, top) => {
          const n = Number(value);
          if (!(n > 0 && n <= 20000) || !Number.isFinite(n)) return;
          const key = n.toFixed(2);
          if (seen.has(key) || candidates.length >= 10) return;
          seen.add(key);
          candidates.push({ value: n, source, top });
        };

        for (const el of rawNodes) {
          const rect = el.getBoundingClientRect();
          if (rect.width <= 0 || rect.height <= 0 || rect.top < -40 || rect.top > 1400) continue;
          const text = String(el.innerText || el.textContent || '').trim().slice(0, 320);
          const source = el.closest('[class*="product-intro"]')
            ? 'product_price_dom' : 'possible_price_dom';
          // Prefer product detail prices; exclude unrelated recommendation
          // cards, strikethrough prices and discount-only promotional banners.
          if (el.closest('[class*="recommend"], [class*="similar"], [class*="suggest"]')
              || el.closest('del, s, [class*="original-price"], [class*="retail-price"]')) continue;
          for (const value of amounts(text)) {
            add(value, source, Math.round(rect.top));
          }
          const priceAttr = el.getAttribute('content') || el.getAttribute('data-price');
          if (priceAttr && /^(?:\d{1,5})(?:\.\d{1,2})?$/.test(priceAttr)) {
            add(priceAttr, 'product_price_metadata', Math.round(rect.top));
          }
        }

        // Public page metadata can appear even while the personalized
        // product-price widget is still hydrating. It is never verified as an
        // account-specific price, and only used if USD is explicit.
        if (!candidates.length) {
          const priceMeta = document.querySelector(
            'meta[property="product:price:amount"], meta[itemprop="price"]'
          );
          const currencyMeta = document.querySelector(
            'meta[property="product:price:currency"], meta[itemprop="priceCurrency"]'
          );
          if (priceMeta && String(currencyMeta?.content || '').toUpperCase() === 'USD') {
            add(priceMeta.content, 'product_price_metadata', -1);
          }
        }

        // SHEIN sometimes emits a product offer as structured data before
        // rendering the DOM. Treat it as *weak evidence*, not an account price.
        const fromLd = raw => {
          const list = Array.isArray(raw) ? raw : [raw];
          for (const obj of list) {
            if (!obj || typeof obj !== 'object') continue;
            if (Array.isArray(obj['@graph'])) fromLd(obj['@graph']);
            const t = Array.isArray(obj['@type']) ? obj['@type'] : [obj['@type']];
            if (!t.some(x => String(x || '').toLowerCase() === 'product')) continue;
            const url = String(obj.url || '');
            if (url && productId && !url.includes('p-' + productId + '.html')) continue;
            const offers = Array.isArray(obj.offers) ? obj.offers : [obj.offers];
            for (const offer of offers) {
              if (!offer || typeof offer !== 'object') continue;
              if (String(offer.priceCurrency || '').toUpperCase() !== 'USD') continue;
              const amount = Number(offer.price || offer.lowPrice || offer.priceSpecification?.price);
              add(amount, 'public_structured_data', -1);
            }
          }
        };
        if (!candidates.length) {
          for (const script of Array.from(document.querySelectorAll('script[type="application/ld+json"]')).slice(0, 10)) {
            try { fromLd(JSON.parse(script.textContent || 'null')); } catch {}
          }
        }

        const bodyTop = body.slice(0, 6500);
        const hasUsdAnywhere = amounts(bodyTop).length > 0;
        const currencyNonUsd = /(?:\bAED\b|\bSAR\b|\bDZD\b|د\.إ|دج)/i.test(bodyTop);
        return {
          rendered: body.length > 40,
          challengeLikely,
          productIntroPresent: Boolean(productIntro),
          domPriceNodeCount: rawNodes.length,
          usdVisibleInPage: hasUsdAnywhere,
          nonUsdCurrencyVisible: currencyNonUsd,
          visibleUsdCandidates: candidates,
        };
      }, { productId });

      if (evidence.visibleUsdCandidates.length || evidence.challengeLikely) break;
      if (poll < 3) await page.waitForTimeout(poll === 0 ? 1800 : 2500);
    }

    const status = evidence?.challengeLikely ? 'security_check_possible'
      : !evidence?.rendered ? 'empty_page'
      : evidence?.visibleUsdCandidates.length ? 'price_candidates_found'
      : 'page_loaded_without_price';

    return {
      status, finalHost: host, httpStatus,
      visibleUsdCandidates: evidence?.visibleUsdCandidates || [],
      indicators: {
        productIntroPresent: Boolean(evidence?.productIntroPresent),
        domPriceNodeCount: evidence?.domPriceNodeCount || 0,
        usdVisibleInPage: Boolean(evidence?.usdVisibleInPage),
        nonUsdCurrencyVisible: Boolean(evidence?.nonUsdCurrencyVisible),
        challengeLikely: Boolean(evidence?.challengeLikely),
      },
    };
  } catch (e) {
    // Do not serialize URL, page HTML, cookie values or remote error bodies.
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
