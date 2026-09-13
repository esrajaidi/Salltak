import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const worker = fs.readFileSync('scripts/shein-shared-page-import.mjs', 'utf8');
const importer = fs.readFileSync('app/Services/CartImport/Browser/SheinBrowserImporter.php', 'utf8');

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

test('shared cart importer takes the USD price from the same visible product card first', () => {
  assert.match(worker, /visibleUsdPriceFromRoot\s*=|function visibleUsdPriceFromRoot/);
  assert.match(worker, /visible_usd_price:/);
  assert.match(worker, /let price = Number\(product\.visible_usd_price \|\| 0\)/);
});

test('shared cart importer never promotes broad network scan rows when DOM cards were unreadable', () => {
  assert.doesNotMatch(worker, /if \(sharedPageEvidence && visibleProducts\.length === 0 && maps\.networkProducts\.size > 0\)/);
  assert.match(worker, /status:'shared_page_unreadable'/);
});

test('known shared-page diagnostic is returned instead of legacy worker data', () => {
  assert.match(importer, /if \(in_array\(\(string\) \(\$shared\['status'\] \?\? ''\), \['missing_usd_prices', 'shared_page_unreadable'\], true\)\) \{\s*return \$shared;/s);
});
