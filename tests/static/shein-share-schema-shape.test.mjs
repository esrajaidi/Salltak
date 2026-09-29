import test from 'node:test';
import assert from 'node:assert/strict';
import { inspectShareShape } from '../../scripts/shein-share-schema-shape.mjs';

const context = { groupId: 'TEST_GROUP_001', shareToken: 'SECRET_TEST_TOKEN' };
const payload = {
  response: {
    cartShareData: {
      group_id: 'TEST_GROUP_001',
      goods_list: [
        { goods_id: '100', sku_id: 'a', goods_name: 'Private Item', salePrice: { usdAmount: '10.62', amount: '38.2', currency: 'AED' }, quantity: 2, image: 'private.jpg' },
        { goods_id: '100', sku_id: 'b', goods_name: 'Private Item', salePrice: { usdAmount: '9.59' }, quantity: 1 },
      ],
    },
    recommendations: { goods_list: Array.from({ length: 82 }, () => ({ goods_id: 'unsafe', usdAmount: '1.00' })) },
  },
};

test('reports only bounded structural evidence for the matching share container', () => {
  const output = inspectShareShape(payload, context);
  assert.equal(output.length, 1);
  assert.equal(output[0].boundToShare, true);
  assert.equal(output[0].itemCount, 2);
  assert.equal(output[0].listPath, 'response.cartShareData.goods_list');
  assert.equal(output[0].itemFields.goods_id, true);
  assert.equal(output[0].itemFields.sku_id, true);
  assert.equal(output[0].itemFields.quantity, true);
  assert.equal(output[0].itemFields.salePrice_usdAmount, true);
  const serialized = JSON.stringify(output);
  for (const secret of ['SECRET_TEST_TOKEN', 'TEST_GROUP_001', 'Private Item', '10.62', '9.59', 'private.jpg']) {
    assert.equal(serialized.includes(secret), false, `private field leaked: ${secret}`);
  }
});

test('a mismatched share group is diagnostic only, never trusted membership', () => {
  const other = structuredClone(payload);
  other.response.cartShareData.group_id = 'OTHER_GROUP';
  const output = inspectShareShape(other, context);
  assert.equal(output[0].boundToShare, false);
  assert.equal(output[0].itemCount, 2);
});

test('a share-shaped payload without identity proof remains untrusted', () => {
  const output = inspectShareShape({ cartShareData: { goods_list: [{ goods_id: '1' }] } }, context);
  assert.equal(output[0].boundToShare, false);
});

test('attacker-controlled object keys and values never appear in the summary', () => {
  const output = inspectShareShape({ SECRET_TEST_TOKEN: { cartShareData: { group_id: 'TEST_GROUP_001', goods_list: [{ PRIVATE_PRODUCT: 'private customer name' }] } } }, context);
  assert.equal(JSON.stringify(output).includes('SECRET_TEST_TOKEN'), false);
  assert.equal(JSON.stringify(output).includes('PRIVATE_PRODUCT'), false);
  assert.equal(JSON.stringify(output).includes('private customer name'), false);
});
