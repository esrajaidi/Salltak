import fs from 'node:fs';
import assert from 'node:assert/strict';

const model = fs.readFileSync('app/Models/Order.php', 'utf8');
const controller = fs.readFileSync('app/Http/Controllers/Admin/OrderController.php', 'utf8');
const workflow = fs.readFileSync('app/Services/OrderWorkflowService.php', 'utf8');
const view = fs.readFileSync('resources/views/admin/orders/show.blade.php', 'utf8');
const customerView = fs.readFileSync('resources/views/orders/show.blade.php', 'utf8');
const migration = fs.readFileSync('database/migrations/2026_09_08_120000_add_review_completed_at_to_orders_table.php', 'utf8');

assert.match(model, /review_completed_at/, 'Order must persist an explicit review completion timestamp');
assert.match(controller, /review_completed_at/, 'Finishing review must persist review completion');
assert.match(view, /تم إنهاء المراجعة/, 'Admin order view must show a completed-review state');
assert.match(view, /\$order->review_completed_at/, 'Review action must be driven by persisted completion state');

assert.match(view, /@selected\([^\n]*\$order->deposit_type/, 'Saved deposit mode must remain selected after reload');
assert.match(view, /old\('deposit_value'[^\n]*\$order->deposit_value/, 'Saved deposit value must remain populated after reload');
assert.match(view, /\$order->payment_terms_note/, 'Saved payment terms note must remain visible after reload');

assert.match(model, /ready_for_purchase/, 'Order workflow must include a ready-for-purchase status');
assert.match(workflow, /ready_for_purchase/, 'Payment workflow must transition fully-paid pre-purchase orders to ready-for-purchase');
assert.match(controller, /syncAfterVerifiedPayment/, 'Verified payments must synchronize the order workflow automatically');
assert.match(controller, /public function updatePaymentTerms[\s\S]*?syncAfterVerifiedPayment[\s\S]*?public function updateStatus/, 'Changing payment terms must not regress an already fully-paid order');
assert.match(customerView, /ready_for_purchase/, 'Customer tracking must display the ready-for-purchase state');
assert.match(migration, /payment_status[^\n]*paid[\s\S]*ready_for_purchase/, 'Migration must reconcile already fully-paid pre-purchase orders');

console.log('order-ops-persistence-v1013: PASS');
