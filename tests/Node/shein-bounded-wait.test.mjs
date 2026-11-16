import test from 'node:test';
import assert from 'node:assert/strict';
import { boundedWait } from '../../scripts/shein-bounded-wait.mjs';

test('a response that never finishes cannot block cart extraction', async () => {
  const start = Date.now();
  assert.equal(await boundedWait(new Promise(() => {}), 20, 'expired'), 'expired');
  assert.ok(Date.now() - start < 500);
});
test('completed responses are preserved and rejected responses return fallback', async () => {
  assert.equal(await boundedWait(Promise.resolve('cart'), 100, ''), 'cart');
  assert.equal(await boundedWait(Promise.reject(new Error('private')), 100, 'failed'), 'failed');
});
