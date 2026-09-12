import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const worker = fs.readFileSync('scripts/shein-browser-import.mjs', 'utf8');
const ui = fs.readFileSync('public/js/app-ui.js', 'utf8');

test('browser worker uses the resolved SHEIN browser URL after onelink navigation', () => {
  assert.match(worker, /const resolvedBrowserUrl = page\.url\(\);/);
  assert.match(worker, /shareApiRequestFromUrl\(resolvedBrowserUrl\)/);
  assert.match(worker, /extractItemsFromPayload\(decoded, resolvedBrowserUrl,/);
});

test('shared-items landing page can treat visible shared product rows as quantity one', () => {
  assert.match(worker, /const sharedItemsLanding = .*items shared by.*add all to cart/is);
  assert.match(worker, /sharedItemsLanding \? 1 : 0/);
  assert.match(worker, /if \(!sharedItemsLanding && !\/(cart\|bag\|basket)\/i\.test\(contextText\)\) continue;/);
});

test('cart loading overlay paints before Safari submits the request', () => {
  assert.match(ui, /event\.preventDefault\(\)/);
  assert.match(ui, /requestAnimationFrame/);
  assert.match(ui, /form\.submit\(\)/);
});
