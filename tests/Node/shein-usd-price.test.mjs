import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync(new URL('../../scripts/shein-browser-import.mjs', import.meta.url), 'utf8');
const start = source.indexOf('function findAmount(');
const end = source.indexOf('function findCurrency(');
assert.ok(start >= 0 && end > start, 'worker price helpers must exist');

const context = {};
vm.createContext(context);
vm.runInContext(`${source.slice(start, end)}\nthis.findAmount = findAmount; this.findUsdAmount = findUsdAmount;`, context);

test('SHEIN worker prefers usdAmount from shared-cart sale price', () => {
  const salePrice = {
    amount: '37.00',
    amountWithSymbol: 'SR37.00',
    usdAmount: '9.85',
    usdAmountWithSymbol: '$9.85',
  };

  assert.equal(context.findUsdAmount(salePrice), 9.85);
  assert.equal(context.findAmount(salePrice), 37);
});

test('SHEIN worker finds nested usdAmount values', () => {
  assert.equal(context.findUsdAmount({ priceData: { unitPrice: { price: { usdAmount: '22.18' } } } }), 22.18);
});
