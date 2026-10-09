import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { uaeSharedLandingUrl, uaeShareRequest, isAedPrice } from '../../scripts/shein-ae-pricing.mjs';
import { parseSheinShareBff } from '../../scripts/shein-share-bff-items.mjs';

const saUrl = 'https://m.shein.com/ar/cart/share/landing?group_id=851956795&shc=2_RwCnM9DrOvA&local_country=SA&cart_share=1';
const payload = {
  code: '0',
  info: {
    normalProducts: [
      { goods_id: '10011', goods_name: 'Verified bag', goodsAttr: 'أسود / XL',
        salePrice: { usdAmount: '21.73', amount: '79.80', amountWithSymbol: 'AED79.80' } },
    ],
    outStock: [],
    unavailable: [],
    recommendations: [{ goods_id: '9999999', salePrice: { usdAmount: 1, amountWithSymbol: 'AED1' } }],
  },
};

test('converts a foreign shared cart into the UAE landing without altering the shared group or token', () => {
  const normalized = new URL(uaeSharedLandingUrl(saUrl));
  assert.equal(normalized.hostname, 'm.shein.com');
  assert.equal(normalized.pathname, '/ar/cart/share/landing');
  assert.equal(normalized.searchParams.get('group_id'), '851956795');
  assert.equal(normalized.searchParams.get('shc'), '2_RwCnM9DrOvA');
  assert.equal(normalized.searchParams.get('local_country'), 'AE');
  assert.equal(normalized.searchParams.get('cart_share'), '1');
});

test('refuses external links and keeps resolved onelink identity', () => {
  assert.equal(uaeSharedLandingUrl('https://evil.example/?group_id=851956795'), '');
  assert.equal(uaeSharedLandingUrl('https://m.shein.com/ar/cart/share/landing?group_id=invalid'), '');
  const fromRedirect = uaeSharedLandingUrl(
    'https://onelink.shein.com/56/64j799em6uyv?shc=2_RkZ6Aa6GUrb',
    'https://m.shein.com/ar/cart/share/landing?group_id=851956795&local_country=SA'
  );
  const u = new URL(fromRedirect);
  assert.equal(u.searchParams.get('group_id'), '851956795');
  assert.equal(u.searchParams.get('shc'), '2_RkZ6Aa6GUrb');
  assert.equal(u.searchParams.get('local_country'), 'AE');
});

test('requests prices from UAE without modifying the group ID', () => {
  const req = uaeShareRequest(saUrl, '851956795');
  assert.equal(req.body.localCountry, 'AE');
  assert.equal(req.body.groupId, '851956795');
  assert.equal(req.currency, 'AED');
  assert.equal(uaeShareRequest('https://example.org', '851956795'), null);
});

test('confirms UAE pricing only if requested country is AE and SHEIN marks every product AED', () => {
  const req = uaeShareRequest(saUrl, '851956795');
  const context = { url: req.endpoint, method: 'POST', requestBody: req.body,
    expectedGroupId: '851956795', expectedCountry: 'AE' };
  const confirmed = parseSheinShareBff(payload, context);
  assert.equal(confirmed.bound, true);
  assert.equal(confirmed.aePriceVerified, true);
  assert.equal(confirmed.items.length, 1);
  assert.equal(confirmed.items[0].unit_price_original, 21.73);
  assert.equal(confirmed.items[0].currency, 'USD');
  assert.equal(parseSheinShareBff(payload, { ...context,
    requestBody: { ...context.requestBody, localCountry: 'SA' } }).bound, false);
  assert.equal(parseSheinShareBff(payload, { ...context,
    requestBody: { ...context.requestBody, groupId: 'OTHER' } }).bound, false);
  assert.equal(isAedPrice({ amountWithSymbol: '$21.73' }), false);
  assert.equal(isAedPrice({ amountWithSymbol: 'AED79.80' }), true);
});

test('does not claim UAE pricing when the source gives only USD or SAR evidence', () => {
  const foreign = structuredClone(payload);
  foreign.info.normalProducts[0].salePrice.amountWithSymbol = 'SAR81.50';
  const req = uaeShareRequest(saUrl, '851956795');
  const parsed = parseSheinShareBff(foreign, {
    url: req.endpoint, method: 'POST', requestBody: req.body,
    expectedGroupId: '851956795', expectedCountry: 'AE',
  });
  assert.equal(parsed.bound, true);
  assert.equal(parsed.aePriceVerified, false);
  assert.equal(parsed.items.length, 1);
});

test('importer never treats unverified region as a successfully confirmed quote', () => {
  const worker = fs.readFileSync('scripts/shein-shared-page-import.mjs','utf8');
  const adapter = fs.readFileSync('app/Services/CartImport/Adapters/SheinShareAdapter.php','utf8');
  assert.match(worker, /uaeSharedLandingUrl\(targetUrl/);
  assert.match(worker, /expectedCountry: PRICING_COUNTRY/);
  assert.match(worker, /appcurrency: 'AED'/);
  assert.match(worker, /bffAePriceVerified \? 'loaded' : 'ae_price_unverified'/);
  assert.match(adapter, /'ae_price_unverified'/);
  assert.match(adapter, /if \(! \$isSharedCart\)/);
});
