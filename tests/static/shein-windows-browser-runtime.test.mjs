import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const importer = fs.readFileSync('app/Services/CartImport/Browser/SheinBrowserImporter.php', 'utf8');
const adapter = fs.readFileSync('app/Services/CartImport/Adapters/SheinShareAdapter.php', 'utf8');
const pkg = JSON.parse(fs.readFileSync('package.json', 'utf8'));
const doctor = fs.readFileSync('scripts/shein-browser-doctor.mjs', 'utf8');

test('a Node runtime abort must not be interpreted as an empty SHEIN cart', () => {
  assert.match(importer, /!\s*is_array\(\$decoded\)\s*&&\s*\$result->exitCode\(\)\s*===\s*134/);
  assert.match(importer, /'status' => 'unavailable'/);
  assert.match(importer, /'worker_aborted' => true/);
  assert.match(importer, /if \(\(\$shared\['meta'\]\['worker_aborted'\] \?\? false\) === true\) \{\s*return \$shared;/);
  assert.match(adapter, /\$browser\['meta'\]\['worker_aborted'\]/);
  assert.match(adapter, /Node\.js.*Chromium.*Windows/);
});

test('Windows doctor checks Chromium without SHEIN or leaking browser profiles', () => {
  assert.equal(pkg.scripts['browser:doctor'], 'node scripts/shein-browser-doctor.mjs');
  assert.match(doctor, /await import\('playwright-chromium'\)/);
  assert.match(doctor, /chromium\.executablePath\(\)/);
  assert.match(doctor, /chromium\.launch\(\{ headless: true/);
  assert.match(doctor, /browser\.newPage\(\)/);
  assert.match(doctor, /SHEIN_BROWSER_NODE/);
  assert.doesNotMatch(doctor, /https:\/\/|shein\.com|storageState|addCookies|session\.json/);
});
