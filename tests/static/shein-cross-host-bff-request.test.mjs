import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const worker = fs.readFileSync('scripts/shein-shared-page-import.mjs', 'utf8');
const diagnostics = fs.readFileSync('app/Services/CartImport/Browser/SheinImportDiagnostics.php', 'utf8');

test('SHEIN www request is not attempted by cross-origin fetch inside redirected m page', () => {
  assert.match(worker, /context\.request\.post\(endpoint,/);
  assert.doesNotMatch(worker, /page\.evaluate\(async \(\{ endpoint, body \}\)/);
  assert.match(worker, /maxRedirects:\s*0/);
  assert.match(worker, /data:\s*body/);
  assert.match(worker, /credentials|context\.request shares the Chromium context's cookies/);
  assert.match(worker, /uaeShareRequest\(finalUrl, targetShare\.groupId, shareHost\)/);
  assert.match(worker, /expectedGroupId:\s*targetShare\.groupId/);
  assert.match(worker, /expectedCountry:\s*PRICING_COUNTRY/);
  assert.match(worker, /captureBoundBff\(parsed, response\.url\(\)\)/);
});

test('primary purchasing website uses desktop browser profile; mobile fallback remains explicitly mobile', () => {
  assert.match(worker, /shareHost === 'www\.shein\.com' \? 1320 : 430/);
  assert.match(worker, /shareHost === 'www\.shein\.com' \? 900 : 932/);
  assert.match(worker, /shareHost === 'www\.shein\.com'\s*\?\s*'Mozilla\/5\.0 \(Windows/);
  assert.match(worker, /Mozilla\/5\.0 \(iPhone/);
});

test('diagnostic explains absent share identity or access limits without leaking secret share tokens', () => {
  assert.match(worker, /share_group_id_present:\s*Boolean\(targetShare\.groupId\)/);
  assert.match(worker, /direct_bff_attempted:\s*directBffAttempted/);
  assert.match(worker, /direct_bff_http_status:\s*directBffHttpStatus/);
  assert.match(worker, /direct_bff_outcome:\s*directBffOutcome/);
  assert.match(worker, /'missing_group_id'/);
  assert.match(worker, /\[403,\s*429\]\.includes\(directBffHttpStatus\)/);
  assert.match(diagnostics, /'shared_www', 'shared_mobile'/);
  assert.match(diagnostics, /'share_group_id_present'/);
  assert.match(diagnostics, /'direct_bff_http_status'/);
  assert.match(diagnostics, /'bound_share_response'/);
  assert.doesNotMatch(diagnostics, /'share_token'\s*=>|'group_id'\s*=>|'raw_url'\s*=>/);
});
