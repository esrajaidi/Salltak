import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const layout = fs.readFileSync('resources/views/layouts/app.blade.php', 'utf8');
const css = fs.readFileSync('public/css/mobile-bottom-nav.css', 'utf8');
const importer = fs.readFileSync('app/Services/CartImport/CartImportService.php', 'utf8');
const resolver = fs.readFileSync('app/Services/CartImport/SheinUrlResolver.php', 'utf8');

test('customer mobile layout exposes a fixed SHEIN-style bottom navigation', () => {
  assert.match(layout, /mobile-bottom-nav/);
  assert.match(layout, /سلة جديدة/);
  assert.match(layout, /سلاتي/);
  assert.match(layout, /طلباتي/);
  assert.match(layout, /حسابي/);
  assert.match(layout, /mobileAccountModal/);
  assert.match(css, /\.mobile-bottom-nav\s*\{/);
  assert.match(css, /position:\s*fixed/);
  assert.match(css, /env\(safe-area-inset-bottom/);
});

test('SHEIN short links are resolved before importing and original URLs remain available', () => {
  assert.match(importer, /SheinUrlResolver/);
  assert.match(importer, /resolve\(\$sourceUrl\)/);
  assert.match(importer, /->import\(\$resolvedUrl,/);
  assert.match(importer, /'source_url'\s*=>\s*\$sourceUrl/);
  assert.match(importer, /'resolved_url'\s*=>\s*\$resolvedUrl/);
  assert.match(resolver, /onelink\.shein\.com/);
  assert.match(resolver, /withoutRedirecting\(\)/);
  assert.match(resolver, /Location/);
});
