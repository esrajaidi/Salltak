import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const worker = fs.readFileSync(path.resolve('scripts/shein-shared-page-import.mjs'), 'utf8');

test('shared-page worker does not assign USD by similar product name', () => {
  assert.doesNotMatch(worker, /price\s*=\s*fuzzyNamePrice\(/);
});
test('shared-page worker does not assign USD by shared product image', () => {
  assert.doesNotMatch(worker, /price\s*=\s*Number\(maps\.networkUsdByImage/);
});
test('shared-page worker does not discard distinct variants because of shared images', () => {
  assert.doesNotMatch(worker, /seenImages\.has\(imgKey\)/);
  assert.match(worker, /variant:\$\{variant\}/);
});
test('unknown prices survive in a read-only partial preview', () => {
  assert.match(worker, /price\s*=\s*null;/);
  assert.match(worker, /status:'missing_usd_prices'/);
  assert.match(worker, /items:items\.slice\(0, MAX_ITEMS\)/);
});
