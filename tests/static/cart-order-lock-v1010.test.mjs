import fs from 'node:fs';
import assert from 'node:assert/strict';

const orderController = fs.readFileSync('app/Http/Controllers/OrderController.php', 'utf8');
const cartController = fs.readFileSync('app/Http/Controllers/CartController.php', 'utf8');
const cartModel = fs.readFileSync('app/Models/Cart.php', 'utf8');
const showView = fs.readFileSync('resources/views/carts/show.blade.php', 'utf8');
const indexView = fs.readFileSync('resources/views/carts/index.blade.php', 'utf8');

assert.match(cartModel, /function order\s*\(\)/, 'Cart must expose a single linked order relation');
assert.match(orderController, /lockForUpdate\(\)/, 'Order creation must lock the cart row to prevent double-submit races');
assert.match(orderController, /where\(['"]cart_id['"],\s*\$lockedCart->id\)/, 'Order creation must re-check for any existing order while locked');
assert.match(orderController, /['"]status['"]\s*=>\s*['"]submitted['"]/, 'Submitting must mark the cart as submitted');
assert.match(orderController, /هذه السلة تم إرسالها مسبقًا كطلب/, 'Repeated submission must redirect to the existing order with a clear Arabic message');
assert.match(cartController, /لا يمكن إلغاء سلة تم إرسالها كطلب/, 'Submitted carts must not be cancellable');
assert.match(cartController, /لا يمكن حذف سلة تم إرسالها كطلب/, 'Submitted carts must not be deletable');
assert.match(showView, /تم إرسال هذه السلة كطلب/, 'Cart detail must show the linked order state');
assert.match(indexView, /تم إرسال الطلب/, 'Cart index must show submitted state');

console.log('cart-order-lock-v1010: PASS');
