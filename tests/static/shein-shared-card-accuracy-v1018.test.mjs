import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const worker = fs.readFileSync('scripts/shein-shared-page-import.mjs', 'utf8');

test('shared cart importer prunes nested duplicate product roots before extraction', () => {
  assert.match(worker, /pruneNestedProductRoots\s*=|function pruneNestedProductRoots/);
  assert.match(worker, /const roots = pruneNestedProductRoots\(/);
  assert.match(worker, /canonicalProductKey/);
});

test('shared cart importer takes the USD price from the same visible product card first', () => {
  assert.match(worker, /visibleUsdPriceFromRoot\s*=|function visibleUsdPriceFromRoot/);
  assert.match(worker, /visible_usd_price:/);
  assert.match(worker, /let price = Number\(product\.visible_usd_price \|\| 0\)/);
});

test('shared cart importer rejects generic phantom product rows', () => {
  assert.match(worker, /isGenericSharedProductName\s*=|function isGenericSharedProductName/);
  assert.match(worker, /if \(isGenericSharedProductName\(name\)\) continue;/);
});
