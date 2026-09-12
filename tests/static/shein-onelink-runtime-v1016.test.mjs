import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const importer = fs.readFileSync('app/Services/CartImport/Browser/SheinBrowserImporter.php', 'utf8');
const sharedWorker = fs.readFileSync('scripts/shein-shared-page-import.mjs', 'utf8');
const adapter = fs.readFileSync('app/Services/CartImport/Adapters/SheinShareAdapter.php', 'utf8');
const ui = fs.readFileSync('public/js/app-ui.js', 'utf8');

test('browser importer prioritizes the visible SHEIN shared-items landing page', () => {
  assert.match(importer, /shein-shared-page-import\.mjs/);
  assert.match(importer, /shein_shared_items_page/);
});

test('shared-items worker detects shared lists without depending on English copy', () => {
  assert.match(sharedWorker, /visible_product_count/);
  assert.match(sharedWorker, /sharedPageEvidence/);
  assert.match(sharedWorker, /cart\/share|cart_share|group_id|shared by|مشاركة|السلة/i);
});

test('shared-items worker matches visible products to network USD prices', () => {
  assert.match(sharedWorker, /networkUsdById/);
  assert.match(sharedWorker, /usdAmount|usd_amount|usdPrice|usd_price/);
  assert.match(sharedWorker, /missing_usd_price_count/);
  assert.match(sharedWorker, /currency:\s*'USD'/);
});

test('shared-items worker reports visible products when USD prices are unavailable', () => {
  assert.match(sharedWorker, /status:'missing_usd_prices'/);
  assert.match(sharedWorker, /وجدنا منتجات في رابط SHEIN/);
  assert.match(sharedWorker, /final_url:page\.url\(\)/);
  assert.match(adapter, /missing_usd_prices/);
  assert.match(adapter, /وجدنا منتجات في رابط SHEIN/);
});

test('cart loading overlay paints before Safari submits the request', () => {
  assert.match(ui, /event\.preventDefault\(\)/);
  assert.match(ui, /requestAnimationFrame/);
  assert.match(ui, /form\.submit\(\)/);
});
