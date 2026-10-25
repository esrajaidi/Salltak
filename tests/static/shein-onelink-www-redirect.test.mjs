import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const resolver = fs.readFileSync('app/Services/CartImport/SheinUrlResolver.php', 'utf8');
const service = fs.readFileSync('app/Services/CartImport/CartImportService.php', 'utf8');
const tests = fs.readFileSync('tests/Unit/SheinUrlResolverTest.php', 'utf8');

test('shortlink resolves through www.shein.com share-page normalization only', () => {
  assert.match(resolver, /private function shareLandingOnWww\(/);
  assert.match(resolver, /https:\/\/www\.shein\.com/);
  assert.match(resolver, /str_contains\(\$path, '\/cart\/share\/'\)/);
  assert.match(resolver, /str_contains\(\$path, '\/share\/landing'\)/);
  assert.match(resolver, /return \$this->shareLandingOnWww\(\$current, \$normalizedShortUrl\)/);
  assert.match(resolver, /return \$url;\s*\}/); // Direct URLs stay unchanged.
  assert.match(service, /\$resolvedUrl = \$this->sheinUrlResolver->resolve\(\$sourceUrl\)/);
  assert.match(service, /\$this->shein->canHandle\(\$resolvedUrl/);
});

test('malformed real onelink share URL is repaired without replacing its cart identity', () => {
  assert.match(resolver, /shc=\[A-Za-z0-9_-\]\+/);
  assert.match(resolver, /url_from/);
  assert.match(resolver, /foreach \(\['shc', 'url_from'\] as \$key\)/);
  assert.match(resolver, /!\s*isset\(\$query\[\$key\]\)/);
  assert.match(tests, /64kcvbmdsiau/);
  assert.match(tests, /www\.shein\.com/);
  assert.match(tests, /group_id/);
  assert.match(tests, /url_from/);
  assert.match(tests, /assertSentCount\(1\)/);
});

test('unsafe third party redirects remain rejected', () => {
  assert.match(resolver, /!\s*\$this->isAllowedSheinHttpsUrl\(\$next\)/);
  assert.match(tests, /test_onelink_rejects_redirects_outside_shein/);
});
