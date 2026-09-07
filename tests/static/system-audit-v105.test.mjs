import fs from 'node:fs';
const files = [
 'app/Http/Controllers/Admin/PaymentMethodController.php',
 'app/Http/Controllers/Admin/UserController.php',
 'app/Http/Controllers/Admin/SettingController.php',
 'app/Http/Controllers/Admin/DepositRuleController.php',
 'app/Http/Controllers/Admin/ExchangeRateController.php',
 'app/Http/Controllers/Admin/StoreController.php',
];
let failures=[];
for (const file of files){
 const text=fs.readFileSync(file,'utf8');
 if (!text.includes('AuditLogger')) failures.push(`${file} missing AuditLogger`);
 if (!text.includes('$this->audit->log(')) failures.push(`${file} missing audit log call`);
}
if (failures.length){console.error('FAIL system audit coverage:', failures.join('; ')); process.exit(1)}
console.log(`PASS system audit coverage (${files.length} controllers)`);
