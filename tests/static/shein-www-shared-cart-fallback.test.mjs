import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { uaeSharedLandingUrl, uaeShareRequest } from '../../scripts/shein-ae-pricing.mjs';

const browser = fs.readFileSync('app/Services/CartImport/Browser/SheinBrowserImporter.php', 'utf8');
const worker = fs.readFileSync('scripts/shein-shared-page-import.mjs', 'utf8');
const adapter = fs.readFileSync('app/Services/CartImport/Adapters/SheinShareAdapter.php', 'utf8');
const share = 'https://onelink.shein.com/56/64kcvbmdsiau?shc=2_RkKGSegsOE6';
const resolved = 'https://m.shein.com/ar/cart/share/landing?group_id=851956795&local_country=SA';

test('sharecart is normalized first to purchasing site and preserves original link identity', () => {
  const www = new URL(uaeSharedLandingUrl(share, resolved));
  assert.equal(www.hostname, 'www.shein.com');
  assert.equal(www.searchParams.get('group_id'), '851956795');
  assert.equal(www.searchParams.get('shc'), '2_RkKGSegsOE6');
  assert.equal(www.searchParams.get('local_country'), 'AE');
  const req = uaeShareRequest(www.href, '851956795');
  assert.equal(new URL(req.endpoint).hostname, 'www.shein.com');
});

test('mobile fallback is explicitly marked unverified and does not reuse an unrelated link', () => {
  const www = new URL(uaeSharedLandingUrl(share, resolved));
  const mobile = new URL(uaeSharedLandingUrl(share, resolved, 'm.shein.com'));
  assert.equal(mobile.searchParams.get('group_id'), www.searchParams.get('group_id'));
  assert.equal(mobile.searchParams.get('shc'), www.searchParams.get('shc'));
  assert.match(browser, /'shared_www', \$traceId, \$accountState, 'www\.shein\.com'/);
  assert.match(browser, /'shared_mobile_fallback', \$traceId, \$accountState, 'm\.shein\.com'/);
  assert.match(browser, /'mobile_share_price_needs_review'/);
  assert.match(browser, /'account_price_unverified'/);
  assert.match(browser, /'shared_guest_fallback', \$traceId, null, 'm\.shein\.com'/);
  assert.match(worker, /uaeSharedLandingUrl\(targetUrl, '', shareHost\)/);
  assert.match(worker, /uaeShareRequest\(finalUrl, targetShare\.groupId, shareHost\)/);
  assert.match(adapter, /'mobile_share_price_needs_review', 'account_price_unverified'/);
  assert.match(adapter, /ImportResult::needsReview\(/);
});
