import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const retry = fs.readFileSync('scripts/shein-shared-page-retry.mjs', 'utf8');

test('retry uses one strict worker launch without a browser preflight', () => {
  assert.doesNotMatch(retry, /launchPersistentContext/);
  assert.doesNotMatch(retry, /playwright-chromium/);
  assert.match(retry, /spawnSync\(process\.execPath, \[strictWorker\]/);
});

test('retry identifies as desktop Safari and keeps the original direct share URL', () => {
  assert.match(retry, /Macintosh; Intel Mac OS X/);
  assert.match(retry, /Safari\/605\.1\.15/);
  assert.match(retry, /url: targetUrl/);
  assert.doesNotMatch(retry, /url: finalUrl/);
});
