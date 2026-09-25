import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const importer = fs.readFileSync('app/Services/CartImport/Browser/SheinBrowserImporter.php', 'utf8');
const helper = fs.existsSync('app/Services/CartImport/Browser/SheinImportDiagnostics.php')
  ? fs.readFileSync('app/Services/CartImport/Browser/SheinImportDiagnostics.php', 'utf8') : '';

test('each SHEIN browser worker attempt emits a structured stderr diagnostic', () => {
  assert.match(importer, /use Illuminate\\Support\\Facades\\Log;/);
  assert.match(importer, /Log::channel\('stderr'\)->log\(/);
  assert.match(importer, /SheinImportDiagnostics::context\(/);
  assert.match(importer, /shein\.import\.attempt/);
});

test('structured diagnostic exposes counts and safe URL categories without raw URLs', () => {
  assert.match(helper, /final_host/);
  assert.match(helper, /final_page_type/);
  assert.match(helper, /visible_product_count/);
  assert.match(helper, /candidate_root_count/);
  assert.match(helper, /missing_usd_price_count/);
  assert.doesNotMatch(helper, /'source_url'\s*=>|'final_url'\s*=>|'stderr'\s*=>|'message'\s*=>/);
});
