import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync(new URL('../../scripts/shein-browser-import.mjs', import.meta.url), 'utf8');
const start = source.indexOf('function shareApiRequestFromUrl(');
const end = source.indexOf('function scalar(');

assert.ok(start >= 0, 'worker must define shareApiRequestFromUrl');
assert.ok(end > start, 'share API helper must be declared before parser helpers');

const context = { URL, isSheinUrl(raw) { try { const host = new URL(raw).hostname.toLowerCase(); return host === 'shein.com' || host.endsWith('.shein.com'); } catch { return false; } } };
vm.createContext(context);
vm.runInContext(`${source.slice(start, end)}\nthis.shareApiRequestFromUrl = shareApiRequestFromUrl;`, context);

test('builds the authoritative SHEIN shared-cart BFF request from the public share URL', () => {
  const request = context.shareApiRequestFromUrl('https://m.shein.com/ar/cart/share/landing?shc=2_RwCnM9DrOvA&group_id=851956795&local_country=AE&url_from=GM71035002695&cart_share=1');

  assert.equal(request.endpoint, 'https://m.shein.com/ar/bff-api/order/cart/share/landing?_ver=1.1.8&_lang=ar');
  assert.deepEqual(JSON.parse(JSON.stringify(request.body)), {
    groupId: '851956795',
    localCountry: 'AE',
    userLocalSizeCountry: '',
  });
});

test('worker actively calls the shared-cart BFF request instead of only waiting for page hydration', () => {
  assert.match(source, /shareApiRequestFromUrl\(targetUrl\)/);
  assert.match(source, /fetch\(request\.endpoint/);
});
