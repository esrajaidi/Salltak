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

const emit = result => process.stdout.write(JSON.stringify(result));

let browser;
try {
  const input = await readInput();
  const state = secureState(input.accountSession);
  if (!state) {
    emit(ownerCartReadiness({ sessionSupplied: false }));
  } else {
    browser = await chromium.launch({ headless: true, args: ['--disable-dev-shm-usage'] });
    const context = await browser.newContext({
      storageState: state,
      locale: 'en-AE',
      viewport: { width: 1320, height: 900 },
    });
    const page = await context.newPage();
    const response = await page.goto('https://www.shein.com/cart', {
      waitUntil: 'domcontentloaded', timeout: 25000,
    });
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
    emit(ownerCartReadiness({
      finalUrl: page.url(),
      httpStatus: response?.status() || 0,
      visibleText: evidence.text,
      candidateProductCards: evidence.candidates,
      sessionSupplied: true,
    }));
    await context.close();
  }
} catch {
  emit({
    status: 'probe_failed',
    session_supplied: false,
    account_authenticated: false,
    final_checkout_price_verified: false,
    account_cart_modified: false,
  });
} finally {
  await browser?.close().catch(() => {});
}
