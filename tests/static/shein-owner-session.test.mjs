import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const read = file => fs.readFileSync(file, 'utf8');

test('owner session is manually captured and excluded from git', () => {
  const local = read('scripts/shein-login-local.mjs');
  const ignore = read('.gitignore');
  assert.match(local, /headless: false/);
  assert.match(local, /page\.goto\('https:\/\/www\.shein\.com\/'/);
  assert.doesNotMatch(local, /page\.goto\('https:\/\/ar\.shein\.com\/'/);
  assert.match(local, /browser\.storageState\(\)/);
  assert.match(local, /mode: 0o600/);
  assert.match(ignore, /\/storage\/app\/shein-session\//);
  assert.doesNotMatch(local, /process\.argv\[\d+\].*password/);
});

test('protected admin vault has explicit enable/disable and no plaintext storage', () => {
  const routes = read('routes/web.php');
  const store = read('app/Services/CartImport/SheinSessionVault.php');
  const controller = read('app/Http/Controllers/Admin/SheinAccountSessionController.php');
  assert.match(routes, /middleware\('admin'\)->group/);
  assert.match(routes, /shein-session\.upload/);
  assert.match(routes, /shein-session\.disable/);
  assert.match(store, /Crypt::encryptString/);
  assert.match(store, /Crypt::decryptString/);
  assert.match(store, /'is_enabled' => false/);
  assert.match(controller, /Hash::check/);
  assert.match(controller, /'max:256'/);
  assert.match(routes, /throttle:3,5/);
});

test('account bearer tokens only go through worker stdin and are not returned', () => {
  const importer = read('app/Services/CartImport/Browser/SheinBrowserImporter.php');
  const worker = read('scripts/shein-shared-page-import.mjs');
  assert.match(importer, /'accountSession' => \$accountState/);
  assert.match(importer, /->input\(\$input/);
  assert.match(worker, /context\.addCookies\(safeCookies\)/);
  assert.match(worker, /account_session_applied:accountSessionApplied/);
  assert.doesNotMatch(worker, /console\.log\(.*accountSession/);
});
