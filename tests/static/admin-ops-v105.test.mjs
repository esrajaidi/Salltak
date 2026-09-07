import fs from 'node:fs';
let failures=[];
const order=fs.readFileSync('resources/views/admin/orders/show.blade.php','utf8');
for (const token of ['ops-workspace','orders.notes.store','visibility','ملاحظة داخلية','تغيير الحالة','data-confirm','unit_price_original']) if (!order.includes(token)) failures.push(`admin order missing ${token}`);
const dash=fs.readFileSync('resources/views/admin/dashboard.blade.php','utf8');
for (const token of ['monitoring-grid','طلبات تحتاج تدخل','دفعات تنتظر التحقق','آخر نشاط بالنظام','audit']) if (!dash.includes(token)) failures.push(`dashboard missing ${token}`);
const controller=fs.readFileSync('app/Http/Controllers/Admin/DashboardController.php','utf8');
for (const token of ['AuditLog','recentActivity','agingOrders','needsAction','Schema::hasTable']) if (!controller.includes(token)) failures.push(`dashboard controller missing ${token}`);
if (failures.length){console.error('FAIL admin operations:', failures.join('; ')); process.exit(1)}
console.log('PASS premium admin operations checks');
