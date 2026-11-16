import test from 'node:test';
import assert from 'node:assert/strict';
import { landingFromOnelinkHtml } from '../../scripts/shein-onelink-landing.mjs';

const fixtures = [
  ['689tle2p88yz', '2_RdIkirc3yg6', '941085055'],
  ['68ahyp6f78xx', '2_RdxEms4tjLR', '941308490'],
];
function html(group, token) {
  const data = encodeURIComponent(JSON.stringify({ group_id: group, shc: token, local_country: 'AE', cart_share: '1' }));
  return `<input id="deeplink" value="sheinlink://applink/share_receiver?data=${data}" style="display: none"/>`;
}
for (const [slug, token, group] of fixtures) {
  test(`extracts the original shared cart ${group} before browser redirects home`, () => {
    const original = `https://onelink.shein.com/60/${slug}?shc=${token}`;
    const result = new URL(landingFromOnelinkHtml(html(group, token), original));
    assert.equal(result.origin, 'https://www.shein.com');
    assert.equal(result.pathname, '/ar/cart/share/landing');
    assert.equal(result.searchParams.get('group_id'), group);
    assert.equal(result.searchParams.get('shc'), token);
    assert.equal(result.searchParams.get('local_country'), 'AE');
  });
}
test('rejects mismatched token, invalid identity, and non-onelink sources', () => {
  const original = 'https://onelink.shein.com/60/example?shc=2_originalToken';
  assert.equal(landingFromOnelinkHtml(html('941308490', '2_otherToken'), original), '');
  assert.equal(landingFromOnelinkHtml(html('invalid', '2_originalToken'), original), '');
  assert.equal(landingFromOnelinkHtml(html('941308490', '2_originalToken'), 'https://evil.test/'), '');
  assert.equal(landingFromOnelinkHtml('<script>group_id=941308490</script>', original), '');
});
