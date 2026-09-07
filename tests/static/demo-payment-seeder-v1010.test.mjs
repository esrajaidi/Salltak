import fs from 'node:fs';
import assert from 'node:assert/strict';
assert.ok(fs.existsSync('database/seeders/DemoPaymentMethodsSeeder.php'), 'A dedicated demo payment seeder must exist');
const db = fs.readFileSync('database/seeders/DatabaseSeeder.php','utf8');
const demo = fs.readFileSync('database/seeders/DemoPaymentMethodsSeeder.php','utf8');
assert.match(db, /\$this->call\(DemoPaymentMethodsSeeder::class\)/, 'DatabaseSeeder must invoke the demo payment seeder');
assert.match(demo, /LY00DEMO/, 'Dedicated seeder must contain clearly fake LYPay data');
assert.match(demo, /PaymentMethod::where\(['"]code['"],\s*['"]cash['"]\)/, 'Dedicated seeder must activate cash');
console.log('demo-payment-seeder-v1010: PASS');
