import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import { storefrontShareRequest } from '../../scripts/shein-storefront-request.mjs';

const args = { endpoint:'https://www.shein.com/ar/bff-api/order/cart/share/landing', body:{ groupId:'123456789', localCountry:'AE' }, timeoutMs:25, maxBytes:1000 };
function run(fetch, input = args, origin = 'https://www.shein.com') {
  const sandbox = { URL, location:{ origin }, AbortController, setTimeout, clearTimeout, Uint8Array, TextDecoder, fetch, args:input };
  // Same serialization boundary as Playwright page.evaluate: no module globals.
  return vm.runInNewContext(`(${storefrontShareRequest.toString()})(args)`, sandbox);
}
test('storefront request retains group identity and browser credentials, even on OOPS page', async () => {
  const result = await run(async (url, options) => {
    assert.equal(url, args.endpoint);
    assert.equal(options.credentials, 'same-origin');
    assert.equal(options.redirect, 'error');
    assert.deepEqual(JSON.parse(options.body), args.body);
    return new Response('{"code":0,"items":[]}', { status:200 });
  });
  assert.equal(result.status, 200);
  assert.equal(JSON.parse(result.text).code, 0);
});
test('storefront denial is returned faithfully, not retried as success', async () => {
  assert.equal((await run(async () => new Response('denied', { status:403 }))).status, 403);
});
test('cross-origin and arbitrary endpoint requests are rejected before fetch', async () => {
  const reject = () => { throw new Error('must not request'); };
  assert.equal((await run(reject, args, 'https://m.shein.com')).error, 'wrong_origin');
  assert.equal((await run(reject, {...args, endpoint:'https://www.shein.com/other'})).error, 'wrong_origin');
});
test('stalled network request aborts within its own budget', async () => {
  const result = await run((url, { signal }) => new Promise((resolve, reject) => {
    signal.addEventListener('abort', () => reject(new Error('aborted')));
  }));
  assert.equal(result.error, 'timeout');
});
test('oversized response cannot become a quote', async () => {
  const result = await run(async () => new Response('x'.repeat(1001)));
  assert.equal(result.oversized, true);
  assert.equal(result.text, undefined);
});
