import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const importer = fs.readFileSync('app/Services/CartImport/Browser/SheinBrowserImporter.php', 'utf8');
const sharedWorker = fs.readFileSync('scripts/shein-shared-page-import.mjs', 'utf8');
const ui = fs.readFileSync('public/js/app-ui.js', 'utf8');

test('browser importer prioritizes the visible SHEIN shared-items landing page', () => {
  assert.match(importer, /shein-shared-page-import\.mjs/);
  assert.match(importer, /shein_shared_items_page/);
  assert.match(sharedWorker, /items shared by\|add all to cart\|shared items\|shared by/i);
  assert.match(sharedWorker, /quantity:\s*1/);
  assert.match(sharedWorker, /currency:\s*'USD'/);
});

test('shared-items worker keeps the final URL after onelink navigation', () => {
  assert.match(sharedWorker, /final_url:page\.url\(\)/);
  assert.match(sharedWorker, /page\.goto\(targetUrl/);
});

test('cart loading overlay paints before Safari submits the request', () => {
  assert.match(ui, /event\.preventDefault\(\)/);
  assert.match(ui, /requestAnimationFrame/);
  assert.match(ui, /form\.submit\(\)/);
});
