import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync(new URL('../../scripts/shein-browser-import.mjs', import.meta.url), 'utf8');
const start = source.indexOf('function scalar(');
const end = source.indexOf('function extractItemsFromPayload(');
assert.ok(start >= 0 && end > start, 'worker cart parser helpers must exist');

const context = {
  URL,
  parseSheinGoodsAttr(value) {
    const parts = String(value || '').split('/').map(x => x.trim()).filter(Boolean);
    return { color: parts[0] || '', size: parts[1] || '' };
  },
};
vm.createContext(context);
vm.runInContext(`${source.slice(start, end)}\nthis.candidateFromNode = candidateFromNode;`, context);

test('shared-cart product remains importable when SHEIN omits usdAmount', () => {
  const item = context.candidateFromNode({
    goods_id: '36412523',
    goods_name: 'منتج تجريبي من سلة SHEIN',
    goods_img: '//img.ltwebstatic.com/example.jpg',
    goodsAttr: 'وردي / XXL',
    sku_code: 'SKU-XXL',
    salePrice: {
      amount: '37.00',
      amountWithSymbol: 'AED 37.00',
      currency: 'AED',
    },
  }, ['cart_response', 'cart_share_landing', 'info', 'normalProducts'], 'https://m.shein.com/ar/cart/share/landing?group_id=851956795&local_country=AE', 'AED');

  assert.ok(item, 'product must not be discarded only because usdAmount is absent');
  assert.equal(item.unit_price_original, 37);
  assert.equal(item.currency, 'AED');
  assert.equal(item.size, 'XXL');
});
