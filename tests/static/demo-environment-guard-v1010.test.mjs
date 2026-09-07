import fs from 'node:fs';
import assert from 'node:assert/strict';
const seeder = fs.readFileSync('database/seeders/DemoPaymentMethodsSeeder.php','utf8');
assert.match(seeder, /environment\(['"]local['"],\s*['"]testing['"]\)/, 'Demo payment seeding must use Laravel variadic environment matching correctly');
console.log('demo-environment-guard-v1010: PASS');
