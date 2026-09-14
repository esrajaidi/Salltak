import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const importer = fs.readFileSync('app/Services/CartImport/Browser/SheinBrowserImporter.php', 'utf8');
const worker = fs.readFileSync('scripts/shein-shared-page-import.mjs', 'utf8');

test('shared cart importer retries the strict parser with a fresh alternate environment', () => {
  assert.match(importer, /shein-shared-page-retry/);
  assert.match(importer, /'locale'\s*=>\s*'ar-AE'/);
  assert.match(importer, /'viewport'\s*=>\s*\['width'\s*=>\s*1280,\s*'height'\s*=>\s*900\]/);
  assert.match(importer, /shared_page_retry/);
});

test('strict shared worker accepts locale viewport and user agent from its input', () => {
  assert.match(worker, /locale:\s*String\(cfg\.locale\s*\|\|\s*'en-AE'\)/);
  assert.match(worker, /viewport:\s*\{\s*width:\s*Number\(cfg\.viewport\?\.width\s*\|\|\s*430\),\s*height:\s*Number\(cfg\.viewport\?\.height\s*\|\|\s*932\)\s*\}/s);
  assert.match(worker, /userAgent:\s*String\(cfg\.userAgent\s*\|\|/);
});

test('share-looking URLs do not fall through to the legacy importer after strict retries fail', () => {
  assert.match(importer, /private function isSharedCartUrl\(string \$url\): bool/);
  assert.match(importer, /if \(\$this->isSharedCartUrl\(\$url\)\)\s*\{\s*return \$sharedRetry;/s);
});
