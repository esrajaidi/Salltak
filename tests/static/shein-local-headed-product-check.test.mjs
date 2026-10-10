import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const script = fs.readFileSync('scripts/shein-local-product-check.mjs', 'utf8');
const pkg = JSON.parse(fs.readFileSync('package.json', 'utf8'));

test('headful diagnostic uses exactly the manually signed-in Chromium profile', () => {
  assert.equal(pkg.scripts['shein:inspect'], 'node scripts/shein-local-product-check.mjs');
  assert.match(script, /launchPersistentContext\(profileDirectory/);
  assert.match(script, /headless: false/);
  assert.match(script, /storage\/app\/shein-session\/browser/);
  assert.match(script, /https:\/\/www\.shein\.com/);
  assert.match(script, /await terminal\.question\(/);
});

test('headful diagnostic is read-only, not a fake authenticated quote', () => {
  assert.match(script, /same product and color|نفس المنتج واللون/);
  assert.match(script, /productSectionPresent/);
  assert.match(script, /priceNodeCount/);
  assert.match(script, /visibleCandidates/);
  assert.match(script, /withinProductSection/);
  assert.doesNotMatch(script, /storageState\s*\(\s*\)/);
  assert.doesNotMatch(script, /writeFileSync|fs\.writeFile|setExtraHTTPHeaders|route\(|fetch\(/);
  assert.doesNotMatch(script, /screenshot\(|localStorage\.|getCookies\(/);
  assert.doesNotMatch(script, /console\.log/);
});
