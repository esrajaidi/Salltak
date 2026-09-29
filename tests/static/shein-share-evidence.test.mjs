import test from 'node:test';
import assert from 'node:assert/strict';
import { summarizeShareEvidence } from '../../scripts/shein-share-evidence.mjs';

const shareContext = { groupId: '851956795', shc: 'SECRET_SHARE_TOKEN' };
const sample = [
  {
    url: 'https://m.shein.com/api/cart/share/items?shc=SECRET_SHARE_TOKEN',
    payload: {
      cartShareData: {
        group_id: '851956795',
        goods_list: [
          { goods_id: '10001', goods_name: 'Private item A', usdAmount: 10.62 },
          { goods_id: '10002', goods_name: 'Private item B', usdAmount: 9.59 },
        ],
      },
    },
  },
  {
    url: 'https://m.shein.com/api/recommend/list?secret=SECRET_SHARE_TOKEN',
    payload: { data: { goods_list: Array.from({ length: 82 }, (_, i) => ({ goods_id: String(i + 1000), usdAmount: 11 })) } },
  },
];

test('counts only explicitly bound shared-cart items, not network recommendations', () => {
  const summary = summarizeShareEvidence({ networkResponses: sample, pageSignals: { domCandidateCount: 0 }, expectedShareContext: shareContext });
  assert.equal(summary.shareResponseSeen, true);
  assert.equal(summary.shareBoundCandidateCount, 2);
  assert.equal(summary.domCandidateCount, 0);
  assert.deepEqual(summary.responseClassCounts, { share: 1, cart: 0, product: 0, other: 1 });
  const json = JSON.stringify(summary);
  for (const secret of ['SECRET_SHARE_TOKEN', '851956795', 'Private item A', 'usdAmount', 'goods_list', 'm.shein.com/api', 'secret=']) {
    assert.equal(json.includes(secret), false, `private field must not leak: ${secret}`);
  }
});

test('rejects a share-shaped payload whose group identity does not match', () => {
  const mismatched = structuredClone(sample[0]);
  mismatched.payload.cartShareData.group_id = 'other-group';
  const summary = summarizeShareEvidence({ networkResponses: [mismatched], expectedShareContext: shareContext });
  assert.equal(summary.shareResponseSeen, false);
  assert.equal(summary.shareBoundCandidateCount, 0);
  assert.equal(summary.responseClassCounts.share, 1);
});

test('does not infer shared items from currency data or endpoint name alone', () => {
  const summary = summarizeShareEvidence({ networkResponses: [sample[0]], expectedShareContext: {} });
  assert.equal(summary.shareResponseSeen, false);
  assert.equal(summary.shareBoundCandidateCount, 0);
});

test('never follows external URLs or includes untrusted response paths in diagnostics', () => {
  const summary = summarizeShareEvidence({ networkResponses: [
    { url: 'https://evil.example/cart/share?shc=SECRET_SHARE_TOKEN', payload: sample[0].payload },
    { url: 'not-a-url', payload: { cartShareData: sample[0].payload.cartShareData } },
  ], expectedShareContext: shareContext, pageSignals: { domCandidateCount: -12 } });
  assert.equal(summary.shareResponseSeen, false);
  assert.equal(summary.shareBoundCandidateCount, 0);
  assert.equal(summary.domCandidateCount, 0);
  assert.equal(summary.responseClassCounts.other, 2);
  assert.equal(JSON.stringify(summary).includes('evil.example'), false);
});
