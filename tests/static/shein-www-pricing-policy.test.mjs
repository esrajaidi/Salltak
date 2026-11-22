import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const importer = fs.readFileSync('app/Services/CartImport/Browser/SheinBrowserImporter.php', 'utf8');
const adapter = fs.readFileSync('app/Services/CartImport/Adapters/SheinShareAdapter.php', 'utf8');
const config = fs.readFileSync('config/services.php', 'utf8');
const admin = fs.readFileSync('resources/views/admin/shein-session/index.blade.php', 'utf8');
const diag = fs.readFileSync('app/Services/CartImport/Browser/SheinImportDiagnostics.php', 'utf8');

test('shared-cart estimates are default and strict account pricing is optional', () => {
  assert.match(config, /SHEIN_WWW_OWNER_PRICES_ONLY', false/);
  assert.match(importer, /\$withOwnerSession = \$useOwnerSession \?\? \$wwwOnly/);
  assert.match(importer, /'status' => 'account_session_required'/);
  assert.match(importer, /if \(\$wwwOnly && \$this->isSharedCartUrl\(\$url\) && \$accountState === null\)/);
  assert.match(importer, /'shared_www', \$traceId, \$accountState, 'www\.shein\.com'/);
  assert.match(importer, /requireWwwPriceEvidence\(\$shared\)/);
  assert.match(importer, /requireWwwPriceEvidence\(\$sharedRetry\)/);
  assert.match(importer, /! \$wwwOnly && \$this->isSharedCartUrl\(\$url\)/);
  assert.match(importer, /! \$wwwOnly && \$accountState !== null/);
});

test('a mobile BFF even under www document is refused, and no coupon price is claimed', () => {
  assert.match(importer, /\$bffHost === 'www\.shein\.com'/);
  assert.match(importer, /\$pageHost === 'www\.shein\.com'/);
  assert.match(importer, /'status'\] = 'www_price_source_unavailable'/);
  assert.match(importer, /'account_price_verified' => false/);
  assert.match(diag, /'www_price_source_unavailable'/);
  assert.match(diag, /'purchaser_login_missing'/);
  assert.match(diag, /'www_price_evidence_missing'/);
  assert.match(adapter, /'account_session_required', 'www_price_source_unavailable'/);
  assert.match(adapter, /'account_price_verified' => false/);
  assert.match(admin, /www\.shein\.com فقط/);
  assert.match(admin, /مش بالضرورة تشمل كوبون التطبيق/);
});
