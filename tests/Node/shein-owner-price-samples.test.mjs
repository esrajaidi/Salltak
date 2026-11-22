import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import { collectOwnerPriceSamples, sanitizeOwnerPriceSamples } from '../../scripts/shein-owner-price-samples.mjs';

test('only bounded product evidence leaves the account browser', () => {
  const raw = { product_id:'123456789', sku:'SKU-XL', option_text:'Black / XL', prices:[{ marker:'sale-price', text:'US$17.60' }], cookies:'SECRET', address:'PRIVATE', html:'<html>private</html>' };
  const samples = sanitizeOwnerPriceSamples(Array(6).fill(raw));
  assert.equal(samples.length, 3);
  assert.equal(samples[0].product_id, '123456789');
  assert.equal(samples[0].prices[0].text, 'US$17.60');
  assert.equal(samples[0].verified, false);
  assert.ok(!JSON.stringify(samples).includes('SECRET'));
  assert.ok(!JSON.stringify(samples).includes('PRIVATE'));
});
test('invalid product identities and non-price values are not emitted as product prices', () => {
  const samples = sanitizeOwnerPriceSamples([{product_id:'not-an-id', prices:[]}, {product_id:'123456789', prices:[{text:'email@example.com'}, {text:'$20.80'}]}]);
  assert.equal(samples.length, 1);
  assert.equal(samples[0].prices.length, 1);
  assert.equal(samples[0].prices[0].text, '$20.80');
});

test('unrelated product data attributes do not make a recommendation into an account-cart row', () => {
  const card = (className, productId) => ({
    className, closest:()=>null,
    getBoundingClientRect:()=>({ width:100, height:100 }),
    getAttribute:()=>'', querySelector:()=>null,
    querySelectorAll:selector => selector === 'a[href]'
      ? [{href:`https://www.shein.com/product-p-${productId}.html`}]
      : [{className:'sale-price', innerText:'$17.60'}],
  });
  const document = { querySelectorAll:()=>[card('product-card', '987654321'), card('cart-item', '123456789')] };
  const samples = vm.runInNewContext(`(${collectOwnerPriceSamples.toString()})()`, {document, URL});
  assert.equal(samples.length, 1);
  assert.equal(samples[0].product_id, '123456789');
});
