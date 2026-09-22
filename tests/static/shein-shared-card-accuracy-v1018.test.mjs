import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const worker = fs.readFileSync('scripts/shein-shared-page-import.mjs', 'utf8');
const importer = fs.readFileSync('app/Services/CartImport/Browser/SheinBrowserImporter.php', 'utf8');
const adapter = fs.readFileSync('app/Services/CartImport/Adapters/SheinShareAdapter.php', 'utf8');

test('shared cart importer builds product rows from one image-root per real product', () => {
  assert.match(worker, /const roots = pruneNestedProductRoots\(\[\.\.\.imageCandidateRoots\]\)/);
  assert.doesNotMatch(worker, /const roots = pruneNestedProductRoots\(\[\.\.\.linkRoots, \.\.\.imageCandidateRoots\]\)/);
  assert.match(worker, /seenImages/);
});

test('shared cart importer sanitizes markup-only names before accepting a product', () => {
  assert.match(worker, /cleanVisibleProductName\s*=|function cleanVisibleProductName/);
  assert.match(worker, /const name = cleanVisibleProductName\(/);
  assert.match(worker, /if \(isGenericSharedProductName\(name\)\) continue;/);
});

test('shared cart importer accepts local-currency price text as product-card evidence only', () => {
  assert.match(worker, /hasVisiblePriceEvidence\s*=|function hasVisiblePriceEvidence/);
  assert.match(worker, /AED\|SAR/);
  assert.match(worker, /د\.إ\|ر\.س/);
  assert.match(worker, /productEvidence[\s\S]*hasVisiblePriceEvidence\(text\)/);
  assert.match(worker, /if \(!text \|\| text\.length < 8 \|\| text\.length > 1800 \|\| !hasVisiblePriceEvidence\(text\)\) continue;/);
  assert.match(worker, /const anyPriceLine =/);
  assert.match(worker, /!anyPriceLine\.test\(line\)/);
});

test('shared cart importer still requires a real USD value before saving a price', () => {
  assert.match(worker, /usdFromText\s*=|function usdFromText/);
  assert.match(worker, /visibleUsdPriceFromRoot\s*=|function visibleUsdPriceFromRoot/);
  assert.match(worker, /let price = Number\(product\.visible_usd_price \|\| 0\)/);
  assert.match(worker, /status:'missing_usd_prices'/);
});

test('shared cart importer never promotes broad network scan rows when DOM cards were unreadable', () => {
  assert.doesNotMatch(worker, /if \(sharedPageEvidence && visibleProducts\.length === 0 && maps\.networkProducts\.size > 0\)/);
  assert.match(worker, /status:'shared_page_unreadable'/);
});

test('known shared-page diagnostic is returned instead of legacy worker data', () => {
  assert.match(importer, /if \(in_array\(\(string\) \(\$shared\['status'\] \?\? ''\), \['missing_usd_prices', 'shared_page_unreadable'\], true\)\) \{\s*return \$shared;/s);
});

test('adapter shows the precise shared-page diagnostic instead of a generic empty-cart message', () => {
  assert.match(adapter, /in_array\(\$browserStatus, \['missing_usd_prices', 'shared_page_unreadable'\], true\)/);
  assert.match(adapter, /\(string\) \(\$browser\['message'\] \?\?/);
});
