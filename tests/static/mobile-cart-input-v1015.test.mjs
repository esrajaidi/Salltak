import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const view = fs.readFileSync('resources/views/carts/create.blade.php', 'utf8');

test('SHEIN share URL input stays full width on mobile and becomes inline from sm up', () => {
  assert.doesNotMatch(view, /input-group input-group-lg flex-column flex-sm-row/);
  assert.match(view, /cart-link-entry d-grid d-sm-flex gap-2/);
  assert.match(view, /id="source_url" class="form-control form-control-lg ltr w-100 flex-grow-1/);
  assert.match(view, /icon-text-btn flex-shrink-0/);
});
