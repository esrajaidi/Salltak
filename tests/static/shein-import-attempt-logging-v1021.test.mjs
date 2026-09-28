import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const importer = fs.readFileSync('app/Services/CartImport/Browser/SheinBrowserImporter.php', 'utf8');
const helper = fs.readFileSync('app/Services/CartImport/Browser/SheinImportDiagnostics.php', 'utf8');

test('each SHEIN browser worker attempt emits safe allowlisted stderr telemetry', () => {
  assert.match(importer, /use Illuminate\\Support\\Facades\\Log;/);
  assert.match(importer, /Log::channel\('stderr'\)->info\('SHEIN import attempt', SheinImportDiagnostics::attempt\(/);
  assert.match(importer, /microtime\(true\)/);
  assert.match(importer, /\$traceId\s*=\s*\(string\) Str::uuid\(\)/);
  assert.match(importer, /'shared_mobile', \$traceId/);
  assert.match(importer, /'shared_desktop', \$traceId/);
  assert.match(importer, /'shared_enrichment', \$traceId/);
  assert.match(importer, /'legacy_primary', \$traceId/);
  assert.match(importer, /'legacy_retry', \$traceId/);
});

test('SHEIN diagnostics emits no raw URL, error body, product or secrets', () => {
  assert.doesNotMatch(helper, /'full_url'\s*=>|'message'\s*=>|'stderr'\s*=>|'payloads'\s*=>|'products'\s*=>|'query'\s*=>/);
  assert.match(helper, /'final_host'\s*=>/);
  assert.match(helper, /'final_page'\s*=>/);
  assert.match(helper, /'failure_reason'\s*=>/);
});
