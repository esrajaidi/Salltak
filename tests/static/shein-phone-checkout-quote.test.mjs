import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const cart = fs.readFileSync('app/Http/Controllers/CartController.php', 'utf8');
const preview = fs.readFileSync('resources/views/carts/preview.blade.php', 'utf8');
const order = fs.readFileSync('app/Http/Controllers/OrderController.php', 'utf8');
const saved = fs.readFileSync('resources/views/carts/show.blade.php', 'utf8');
const admin = fs.readFileSync('resources/views/admin/orders/show.blade.php', 'utf8');
const model = fs.readFileSync('app/Models/OrderItem.php', 'utf8');

test('imported SHEIN price is immutable and a phone quote has separate provenance', () => {
  assert.match(cart, /'items\.\*\.checkout_unit_price'/);
  assert.match(cart, /'checkout_total_from_app'/);
  assert.match(cart, /'phone_checkout_price_source' => 'customer_manually_entered'/);
  assert.match(cart, /'phone_checkout_verified' => false/);
  assert.match(cart, /'requires_manager_price_review' => true/);
  assert.match(cart, /'unit_price_original' => \$item\['unit_price_original'\]/);
  assert.match(cart, /'subtotal_original' => \$subtotal/);
  assert.match(cart, /'phone_checkout_comparison_total'/);
  assert.match(cart, /\$isSheinSource/);
  assert.match(order, /'total_lyd' => \$lockedCart->total_lyd/);
  assert.match(order, /'unit_price_original'=>\$item->unit_price_original/);
});

test('customer screen shows two distinct totals and warns account prices remain unverified', () => {
  assert.match(preview, /أسعار SHEIN تقديرية/);
  assert.match(preview, /سعر القطعة في تطبيق SHEIN بعد الكوبون/);
  assert.match(preview, /name="items\[\{\{ \$i \}\}\]\[checkout_unit_price\]"/);
  assert.match(preview, /name="checkout_total_from_app"/);
  assert.match(preview, /phoneQuoteSubtotal/);
  assert.match(preview, /phoneQuoteDifference/);
  assert.match(preview, /phoneEditedCount/);
  assert.match(preview, /' \(تقديري\)'/);
  assert.match(preview, /لا يغيّر إجمالي الطلب المعتمد/);
  assert.match(saved, /phone_checkout_total/);
  assert.match(admin, /phone_checkout_unit_price/);
  assert.match(model, /function cartItem\(/);
});
