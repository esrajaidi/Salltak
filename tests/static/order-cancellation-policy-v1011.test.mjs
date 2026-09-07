import fs from 'node:fs';
import assert from 'node:assert/strict';

const controller = fs.readFileSync('app/Http/Controllers/OrderController.php', 'utf8');
const view = fs.readFileSync('resources/views/orders/show.blade.php', 'utf8');

assert.match(controller, /cancellation_reason/, 'Cancellation must persist the customer reason in history metadata');
assert.match(controller, /forfeited_deposit_amount/, 'Cancellation must record the forfeited deposit amount');
assert.match(controller, /cancellation_policy_acknowledged/, 'Paid-deposit cancellation must require explicit policy acknowledgement');
assert.match(controller, /remaining_amount['"]?\s*=>\s*0/, 'Cancelled orders must not keep an outstanding balance');
assert.match(view, /سبب الإلغاء/, 'Customer cancellation UI must ask for a reason');
assert.match(view, /العربون غير قابل للاسترداد/, 'Customer cancellation UI must warn that a paid deposit is non-refundable');
assert.match(view, /أقر بأنني قرأت سياسة الإلغاء/, 'Customer must explicitly acknowledge the cancellation policy');
assert.match(view, /سيؤدي إلغاء الطلب إلى فقدان العربون المدفوع/, 'SweetAlert confirmation must clearly repeat the deposit consequence');

console.log('order-cancellation-policy-v1011: PASS');
