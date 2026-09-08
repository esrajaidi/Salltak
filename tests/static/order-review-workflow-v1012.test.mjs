import fs from 'node:fs';

const failures = [];
const controller = fs.readFileSync('app/Http/Controllers/Admin/OrderController.php', 'utf8');
const view = fs.readFileSync('resources/views/admin/orders/show.blade.php', 'utf8');
const routes = fs.readFileSync('routes/web.php', 'utf8');

for (const token of [
  "Route::post('/orders/{order}/review/complete'",
  "->name('orders.review.complete')",
]) {
  if (!routes.includes(token)) failures.push(`routes missing ${token}`);
}

for (const token of [
  'public function completeReview(Request $request, Order $order)',
  "where('review_status','pending')->update(['review_status'=>'approved'])",
  "needs_customer_action",
  'تمت مراجعة جميع المنتجات',
  "'issue_type'=>['nullable',Rule::in(array_keys($issueMap))]",
  "'size_unavailable' => ['status' => 'option_issue'",
  "'color_unavailable' => ['status' => 'option_issue'",
  "'quantity_unavailable' => ['status' => 'option_issue'",
  "if ($data['issue_type'] === 'price_changed' && !isset($data['reviewed_unit_price_lyd']))",
]) {
  if (!controller.includes(token)) failures.push(`controller missing ${token}`);
}

for (const token of [
  'كل المنتجات سليمة افتراضيًا',
  'تحديد مشكلة',
  'إنهاء مراجعة الطلب',
  'غير متوفر',
  'المقاس غير متوفر',
  'اللون غير متوفر',
  'تغير السعر',
  'الكمية غير متوفرة',
  'مشكلة أخرى',
]) {
  if (!view.includes(token)) failures.push(`view missing ${token}`);
}

if (view.includes('name="review_status" required')) failures.push('legacy per-item review status dropdown is still present');
if (view.includes('>حفظ المراجعة</button>')) failures.push('legacy per-item save review button is still present');

if (failures.length) {
  console.error('FAIL V10.12 simplified order review:', failures.join('; '));
  process.exit(1);
}
console.log('PASS V10.12 simplified order review checks');
