import fs from 'node:fs';
let failures=[];
const routes=fs.readFileSync('routes/web.php','utf8');
for (const token of ["notifications.index","notifications.read","notifications.read-all","orders.notes.store"]) if (!routes.includes(token)) failures.push(`routes missing ${token}`);
if (!fs.existsSync('app/Http/Controllers/NotificationController.php')) failures.push('missing NotificationController');
const adminOrder=fs.readFileSync('app/Http/Controllers/Admin/OrderController.php','utf8');
for (const token of ['public function note(', 'visibility', 'AuditLogger', 'NotificationService']) if (!adminOrder.includes(token)) failures.push(`admin order missing ${token}`);
const customerOrder=fs.readFileSync('app/Http/Controllers/OrderController.php','utf8');
for (const token of ['NotificationService','AuditLogger']) if (!customerOrder.includes(token)) failures.push(`customer order missing ${token}`);
const payment=fs.readFileSync('app/Http/Controllers/PaymentController.php','utf8');
for (const token of ['NotificationService','AuditLogger']) if (!payment.includes(token)) failures.push(`payment controller missing ${token}`);
if (failures.length){console.error('FAIL controller event coverage:', failures.join('; ')); process.exit(1)}
console.log('PASS controller event coverage checks');
