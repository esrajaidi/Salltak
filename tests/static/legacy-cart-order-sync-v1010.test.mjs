import fs from 'node:fs';
import assert from 'node:assert/strict';
const c = fs.readFileSync('app/Http/Controllers/OrderController.php','utf8');
assert.match(c, /if \(\$existing\) \{[\s\S]*?\$lockedCart->update\(\['status' => 'submitted'\]\);[\s\S]*?return \['order' => \$existing, 'created' => false\];/, 'Legacy carts that already have an order must be synchronized to submitted status');
console.log('legacy-cart-order-sync-v1010: PASS');
