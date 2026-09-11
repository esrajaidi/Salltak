import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const layout = fs.readFileSync('resources/views/layouts/app.blade.php', 'utf8');
const css = fs.readFileSync('public/css/app.css', 'utf8');
const importer = fs.readFileSync('app/Services/CartImport/CartImportService.php', 'utf8');

test('customer mobile layout exposes a fixed SHEIN-style bottom navigation', () => {
  assert.match(layout, /mobile-bottom-nav/);
  assert.match(layout, /سلة جديدة/);
  assert.match(layout, /سلاتي/);
  assert.match(layout, /طلباتي/);
  assert.match(layout, /حسابي/);
  assert.match(css, /\.mobile-bottom-nav\s*\{/);
  assert.match(css, /position:\s*fixed/);
  assert.match(css, /env\(safe-area-inset-bottom/);
});

test('SHEIN short links are resolved before importing while original links can still be stored by the controller', () => {
  assert.match(importer, /SheinUrlResolver/);
  assert.match(importer, /resolve\(\$url\)/);
  assert.match(importer, /\$sourceUrl/);
  assert.match(importer, /->import\(\$sourceUrl,/);
});
