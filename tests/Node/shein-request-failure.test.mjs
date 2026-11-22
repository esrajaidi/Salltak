import test from 'node:test';
import assert from 'node:assert/strict';
import { requestFailureCategory } from '../../scripts/shein-request-failure.mjs';

test('classifies transport failures without returning remote error content', () => {
  for (const [message, expected] of [
    ['apiRequestContext.post: Timeout 10000ms exceeded', 'timeout'],
    ['getaddrinfo ENOTFOUND www.shein.com', 'dns'],
    ['unable to verify the first certificate', 'tls'],
    ['socket hang up ECONNRESET', 'connection_reset'],
    ['connect ECONNREFUSED', 'connection_refused'],
    ['Target page, context or browser has been closed', 'context_closed'],
    ['private unknown error https://example.test/?secret=value', 'unknown'],
  ]) assert.equal(requestFailureCategory(new Error(message)), expected);
});
