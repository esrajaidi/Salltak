import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const worker = fs.readFileSync('scripts/shein-shared-page-import.mjs', 'utf8');
const safeLog = fs.readFileSync('app/Services/CartImport/Browser/SheinImportDiagnostics.php', 'utf8');

test('strict shared page worker records only anchored share response evidence', () => {
  assert.match(worker, /import \{ summarizeShareEvidence \} from '\.\/shein-share-evidence\.mjs';/);
  assert.match(worker, /summarizeShareEvidence\(\{[\s\S]*networkResponses: \[\{ url: responseUrl, payload: decoded \}\]/);
  assert.match(worker, /expectedShareContext: shareContextFor\(targetUrl, page\.url\(\)\)/);
  assert.match(worker, /share_response_seen:/);
  assert.match(worker, /share_bound_candidate_count:/);
  assert.match(worker, /response_class_counts:/);
});

test('Railway logging exposes safe counts only, never share tokens or response payload', () => {
  assert.match(safeLog, /'share_response_seen'\s*=>/);
  assert.match(safeLog, /'share_bound_candidate_count'\s*=>/);
  assert.match(safeLog, /'share_response_count'\s*=>/);
  assert.match(safeLog, /'other_response_count'\s*=>/);
  assert.doesNotMatch(safeLog, /'share_token'\s*=>|'response_url'\s*=>|'raw_payload'\s*=>|'group_id'\s*=>/);
});
