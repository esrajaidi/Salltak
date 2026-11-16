/**
 * Read-only owner-session cart preflight. This does NOT click any button,
 * change the SHEIN cart, apply coupons, navigate to payment, or place orders.
 *
 * Output is a strictly allowlisted diagnostics object; never print cookies,
 * storageState, page text, product URLs, addresses, payment details or HTML.
 */
import process from 'node:process';
import { chromium } from 'playwright-chromium';
import { ownerCartReadiness } from './shein-owner-cart-evidence.mjs';
import { ownerCartUrl } from './shein-owner-cart-navigation.mjs';
import { boundedWait } from './shein-bounded-wait.mjs';
import { collectOwnerPriceSamples, sanitizeOwnerPriceSamples } from './shein-owner-price-samples.mjs';

async function readInput() {
  let raw = '';
  for await (const chunk of process.stdin) {
    raw += chunk;
    if (raw.length > 400_000) throw new Error('request_too_large');
  }
  return JSON.parse(raw || '{}');
}

function secureState(raw) {
  if (!raw || !Array.isArray(raw.cookies)) return null;
  const sheinDomain = value => {
    const domain = String(value || '').replace(/^\./, '').toLowerCase();
    return domain === 'shein.com' || domain.endsWith('.shein.com');
  };
  const cookies = raw.cookies.filter(cookie => cookie && cookie.secure === true
    && sheinDomain(cookie.domain) && typeof cookie.name === 'string'
    && typeof cookie.value === 'string' && cookie.value.length <= 12000);
  if (!cookies.length) return null;
  const origins = (Array.isArray(raw.origins) ? raw.origins : []).filter(item => {
    try {
      const url = new URL(item.origin);
      return url.protocol === 'https:' && sheinDomain(url.hostname);
    } catch { return false; }
  });
  return { cookies, origins };
}

let emitted = false;
const emit = result => {
  if (emitted) return;
  emitted = true;
  process.stdout.write(JSON.stringify(result));
};

let browser;
let sessionSupplied = false;
let stage = 'input';
const watchdog = setTimeout(() => {
  emit({ ...ownerCartReadiness({ sessionSupplied }), status:'probe_timeout', navigation_stage:stage });
  boundedWait(browser?.close(), 1000, null).finally(() => process.exit(0));
}, 32000);
try {
  const input = await readInput();
  const state = secureState(input.accountSession);
  if (!state) {
    emit(ownerCartReadiness({ sessionSupplied: false }));
  } else {
    sessionSupplied = true;
    stage = 'launch';
    browser = await chromium.launch({ headless: input.headless !== false, timeout:10000, args: ['--disable-dev-shm-usage'] });
    const context = await browser.newContext({
      storageState: state,
      locale: 'en-AE',
      viewport: { width: 1320, height: 900 },
    });
    const page = await context.newPage();
    stage = 'storefront';
    // Read the site's own account-cart link without clicking or modifying it.
    await page.goto('https://www.shein.com/', {
      waitUntil:'domcontentloaded', timeout:10000,
    }).catch(() => null);
    const links = await boundedWait(page.evaluate(() =>
      Array.from(document.querySelectorAll('a[href]')).slice(0, 1500).map(a => a.href)
    ), 1500, []);
    const cartUrl = links.map(ownerCartUrl).find(Boolean) || 'https://www.shein.com/cart';
    stage = 'account_cart';
    const response = await page.goto(cartUrl, {
      waitUntil: 'domcontentloaded', timeout: 15000,
    });
    stage = 'cart_evidence';
    await page.waitForTimeout(1800);
    const evidence = await page.evaluate(() => {
      const text = String(document.body?.innerText || '').slice(0, 12000);
      // Candidates are NOT trusted product IDs or confirmed cart quantities.
      const visibleCartCards = Array.from(document.querySelectorAll(
        '[data-goods-id], [data-sku-id], [class*="cart-item"], [class*="cartItem"]'
      )).filter(el => {
        const rect = el.getBoundingClientRect();
        return rect.width > 0 && rect.height > 0 && rect.top < 2400;
      }).length;
      return { text, candidates: Math.min(1000, visibleCartCards) };
    });
    const readiness = ownerCartReadiness({
      finalUrl: page.url(),
      httpStatus: response?.status() || 0,
      visibleText: evidence.text,
      candidateProductCards: evidence.candidates,
      sessionSupplied: true,
    });
    const priceSamples = ['existing_cart_must_be_preserved', 'cart_state_unclear'].includes(readiness.status)
      ? sanitizeOwnerPriceSamples(await boundedWait(page.evaluate(collectOwnerPriceSamples), 2000, [])) : [];
    emit({ ...readiness, navigation_stage:stage, price_samples:priceSamples });
    await boundedWait(context.close(), 1000, null);
  }
} catch {
  emit({
    status: 'probe_failed',
    session_supplied: sessionSupplied,
    navigation_stage: stage,
    account_authenticated: false,
    final_checkout_price_verified: false,
    account_cart_modified: false,
  });
} finally {
  const closed = !browser || await boundedWait(browser.close().then(() => true), 1000, false);
  if (!closed) {
    await new Promise(resolve => process.stdout.write('', resolve));
    process.exit(0);
  }
  clearTimeout(watchdog);
}
