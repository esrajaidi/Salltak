import fs from 'node:fs';

const migration = 'database/migrations/2026_09_07_133000_add_activity_notifications_and_audit.php';
const requiredFiles = ['app/Models/AppNotification.php','app/Models/AuditLog.php'];
let failures=[];
if (!fs.existsSync(migration)) failures.push('missing activity migration');
else {
  const text=fs.readFileSync(migration,'utf8');
  for (const token of ["Schema::table('order_status_histories'", "event_type", "visibility", "metadata", "Schema::create('app_notifications'", "Schema::create('audit_logs'"]) {
    if (!text.includes(token)) failures.push(`migration missing ${token}`);
  }
}
for (const file of requiredFiles) if (!fs.existsSync(file)) failures.push(`missing ${file}`);
const history=fs.readFileSync('app/Models/OrderStatusHistory.php','utf8');
for (const token of ['event_type','visibility','metadata']) if (!history.includes(token)) failures.push(`history model missing ${token}`);
const user=fs.readFileSync('app/Models/User.php','utf8');
if (!user.includes('appNotifications')) failures.push('User missing appNotifications relation');
if (failures.length){console.error('FAIL activity schema:', failures.join('; ')); process.exit(1)}
console.log('PASS activity schema/model checks');
