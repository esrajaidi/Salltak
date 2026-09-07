import fs from 'node:fs';
let failures=[];
for (const file of ['app/Services/NotificationService.php','app/Services/AuditLogger.php']) if (!fs.existsSync(file)) failures.push(`missing ${file}`);
const workflow=fs.readFileSync('app/Services/OrderWorkflowService.php','utf8');
for (const token of ['NotificationService','AuditLogger','visibility','eventType','metadata','addNote(']) if (!workflow.includes(token)) failures.push(`workflow missing ${token}`);
if (failures.length){console.error('FAIL activity services:', failures.join('; ')); process.exit(1)}
console.log('PASS activity service checks');
