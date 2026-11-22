import test from 'node:test';
import assert from 'node:assert/strict';
import { chromium } from 'playwright-chromium';
import { storefrontShareRequest } from '../../scripts/shein-storefront-request.mjs';

// Local synthetic HTTP responses; never contacts SHEIN or uses an account.
test('real browser sends same-origin cart request with its session and honors denial', async () => {
  const browser = await chromium.launch({ executablePath: process.env.SHEIN_TEST_CHROMIUM || undefined, headless: true, args: ['--no-sandbox'] });
  try {
    const context = await browser.newContext();
    await context.addCookies([{ name: 'synthetic_session', value: 'fixture', domain: 'www.shein.com', path: '/', secure: true }]);
    let seen;
    let status = 200;
    await context.route('https://www.shein.com/**', async route => {
      if (route.request().method() === 'POST') {
        seen = route.request();
        return route.fulfill({ status, contentType: 'application/json', body: JSON.stringify({ code: status, items: [] }) });
      }
      return route.fulfill({ contentType: 'text/html', body: '<main>OOPS — synthetic storefront</main>' });
    });
    const page = await context.newPage();
    await page.goto('https://www.shein.com/ar/cart/share/landing');
    const args = { endpoint: 'https://www.shein.com/ar/bff-api/order/cart/share/landing', body: { groupId: '123456789', localCountry: 'AE' }, timeoutMs: 1000, maxBytes: 1000 };
    const result = await page.evaluate(storefrontShareRequest, args);
    assert.equal(result.status, 200);
    assert.deepEqual(seen.postDataJSON(), args.body);
    assert.match(await seen.headerValue('cookie'), /synthetic_session=fixture/);
    status = 403;
    assert.equal((await page.evaluate(storefrontShareRequest, args)).status, 403);
    assert.equal((await page.evaluate(storefrontShareRequest, { ...args, endpoint: 'https://m.shein.com/ar/bff-api/order/cart/share/landing' })).error, 'wrong_origin');
    await context.route('https://www.shein.com/ar/bff-api/**', async route => { await route.abort(); });
    assert.equal((await page.evaluate(storefrontShareRequest, args)).error, 'browser_network');
  } finally { await browser.close(); }
});
