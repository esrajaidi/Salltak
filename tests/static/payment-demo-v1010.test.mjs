import fs from 'node:fs';
import assert from 'node:assert/strict';

const seeder = fs.readFileSync('database/seeders/DemoPaymentMethodsSeeder.php', 'utf8');

assert.match(seeder, /PaymentMethod::where\(['"]code['"],\s*['"]lypay['"]\)/, 'Demo seeder must configure LYPay');
assert.match(seeder, /PaymentMethod::where\(['"]code['"],\s*['"]cash['"]\)/, 'Demo seeder must configure cash');
assert.match(seeder, /LY00DEMO/, 'LYPay demo data must use an obviously fake IBAN');
assert.match(seeder, /بيانات تجريبية|تجريبي/, 'LYPay demo configuration must be visibly marked as demo');
assert.match(seeder, /['"]is_active['"]\s*=>\s*true/, 'LYPay and cash must be activated in demo data');
assert.match(seeder, /['"]test_mode['"]\s*=>\s*['"]1['"]/, 'LYPay demo data must be marked test/demo mode');

console.log('payment-demo-v1010: PASS');
