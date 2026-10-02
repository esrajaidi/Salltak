import test from 'node:test';
import assert from 'node:assert/strict';
import { classifyShareDestination as classify } from '../../scripts/shein-final-destination.mjs';
const oneLink = 'https://onelink.shein.com/55/sample?shc=sample-secret';
test('distinguishes OneLink browser fallback to storefront', () => {
  assert.equal(classify(oneLink, 'https://m.shein.com/ar/'), 'app_only_redirect');
});
test('share_landing with underscore is recognized as a share page', () => {
  assert.equal(classify(oneLink, 'https://m.shein.com/ar/share_landing?shc=sample-secret'), 'share_landing');
});
test('direct cart/share landing is recognized', () => {
  assert.equal(classify(oneLink, 'https://m.shein.com/ar/cart/share/landing?group_id=123'), 'share_landing');
});
test('unfetched original OneLink is not labeled as a redirect', () => {
  assert.equal(classify(oneLink, oneLink), 'unknown');
});
test('share network response prevents false app-only redirect', () => {
  assert.equal(classify(oneLink, 'https://m.shein.com/ar', { shareResponseSeen: true }), 'unknown');
});
test('visible products prevent false app-only redirect', () => {
  assert.equal(classify(oneLink, 'https://m.shein.com/ar', { visibleProductCount: 3 }), 'unknown');
});
test('external URL is never classified as valid SHEIN destination', () => {
  assert.equal(classify(oneLink, 'https://example.test/ar/share_landing'), 'unknown');
});
