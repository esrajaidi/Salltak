import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const worker = fs.readFileSync('scripts/shein-shared-page-import.mjs', 'utf8');
const preview = fs.readFileSync('resources/views/carts/preview.blade.php', 'utf8');

test('shared SHEIN worker only keeps image-like URLs and maps network images by product id', () => {
  assert.match(worker, /function isLikelyImageUrl/);
  assert.match(worker, /networkImageById/);
  assert.match(worker, /maps\.networkImageById\.set/);
  assert.match(worker, /resolvedImage/);
  assert.match(worker, /image_url:\s*resolvedImage/);
});

test('cart preview replaces a browser-broken product image with the normal placeholder', () => {
  assert.match(preview, /data-product-image/);
  assert.match(preview, /data-product-image-fallback/);
  assert.match(preview, /addEventListener\('error'/);
});
