import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const importer = fs.readFileSync('app/Services/CartImport/Browser/SheinBrowserImporter.php', 'utf8');
const retryPath = 'scripts/shein-shared-page-retry.mjs';
const retryExists = fs.existsSync(retryPath);
const retryWorker = retryExists ? fs.readFileSync(retryPath, 'utf8') : '';

test('shared cart importer retries the strict parser with a fresh alternate environment', () => {
  assert.match(importer, /shein-shared-page-retry\.mjs/);
  assert.match(importer, /shein-shared-page-retry\//);
  assert.match(importer, /shared_page_retry/);
});

test('alternate retry worker preflights SHEIN in Arabic desktop mode then runs the strict worker', () => {
  assert.equal(retryExists, true, 'alternate retry worker must exist');
  assert.match(retryWorker, /locale:\s*'ar-AE'/);
  assert.match(retryWorker, /viewport:\s*\{\s*width:\s*1280,\s*height:\s*900\s*\}/);
  assert.match(retryWorker, /Mozilla\/5\.0 \(X11; Linux x86_64\)/);
  assert.match(retryWorker, /shein-shared-page-import\.mjs/);
  assert.match(retryWorker, /profileDir/);
});

test('share-looking URLs do not fall through to the legacy importer after strict retries fail', () => {
  assert.match(importer, /private function isSharedCartUrl\(string \$url\): bool/);
  assert.match(importer, /if \(\$this->isSharedCartUrl\(\$url\)\)\s*\{\s*return \$sharedRetry;/s);
});
