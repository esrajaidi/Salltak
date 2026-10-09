import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { parseSheinShareBff } from '../../scripts/shein-share-bff-items.mjs';

const context = {
  url: 'https://m.shein.com/ar/bff-api/order/cart/share/landing?_ver=1.1.8',
  method: 'POST',
  requestBody: JSON.stringify({ groupId: '851956795', localCountry: 'AE' }),
  expectedGroupId: '851956795',
};

const payload = {
  code: '0',
  info: {
    normalProducts: [{
      goods_id: '10001', sku_code: 'SKU-BLACK-XL',
      goods_name: 'Shared cart dress', goodsAttr: 'الأسود / XL',
      goods_img: '//img.ltwebstatic.com/test/shared-product.jpg',
      salePrice: { amount: 48, amountWithSymbol: 'AED48', usdAmount: '13.07' },
    }],
    outStock: [{
      goods_id: '10002', sku_code: 'SKU-WHITE-M',
      goods_name: 'Out of stock top', goodsAttr: 'White / M',
      goods_img: '//img.ltwebstatic.com/test/other.jpg',
      salePrice: { amount: 29, amountWithSymbol: 'AED29', usdAmount: '7.90' },
    }],
    unavailable: [],
    recommendations: [{
      goods_id: 'NOT-IN-CART', goods_name: 'Unsafe suggested item',
      salePrice: { usdAmount: 11 },
    }],
  },
  recommendations: [{ goods_id: 'NOT-IN-CART', salePrice: { usdAmount: 11 } }],
};

test('accepts SHEIN BFF items only when endpoint, POST group and response schema all match', () => {
  const result = parseSheinShareBff(payload, context);
  assert.equal(result.bound, true);
  assert.equal(result.candidateCount, 2);
  assert.equal(result.items.length, 2);
  assert.deepEqual(result.items.map(x => x.external_id), ['10001', '10002']);
  assert.equal(result.items[0].unit_price_original, 13.07);
  assert.equal(result.items[0].currency, 'USD');
  assert.equal(result.items[0].quantity, 1);
  assert.equal(result.items[0].color, 'الأسود');
  assert.equal(result.items[0].size, 'XL');
  assert.ok(result.items[0].image_url.endsWith('/shared-product.jpg'));
  assert.equal(result.items.some(x => x.name.includes('suggested')), false);
});

test('rejects a different group, unrelated endpoint, GET or unproven share data', () => {
  const attempts = [
    { ...context, expectedGroupId: 'OTHER' },
    { ...context, requestBody: '{"groupId":"OTHER"}' },
    { ...context, url: 'https://m.shein.com/ar/search' },
    { ...context, method: 'GET' },
    { ...context, requestBody: null },
  ];
  for (const attempted of attempts) {
    const result = parseSheinShareBff(payload, attempted);
    assert.equal(result.bound, false);
    assert.deepEqual(result.items, []);
  }
});

test('never confuses local AED with USD or invents missing prices', () => {
  const localOnly = structuredClone(payload);
  delete localOnly.info.normalProducts[0].salePrice.usdAmount;
  const result = parseSheinShareBff(localOnly, context);
  assert.equal(result.bound, true);
  assert.equal(result.missingUsdPriceCount, 1);
  assert.equal(result.items.length, 1);
  assert.equal(result.items[0].external_id, '10002');
  assert.equal(parseSheinShareBff({ code: '836100', info: payload.info }, context).bound, false);
});

test('strict shared worker imports BFF rows and keeps general network scan untrusted', () => {
  const worker = fs.readFileSync('scripts/shein-shared-page-import.mjs', 'utf8');
  assert.match(worker, /parseSheinShareBff/);
  assert.match(worker, /expectedGroupId: targetShare.groupId/);
  assert.match(worker, /bffSharedRows.size > 0 && bffMissingUsdPriceCount === 0/);
  assert.doesNotMatch(worker, /if \(sharedPageEvidence && visibleProducts.length === 0 && maps.networkProducts.size > 0\)/);
});
