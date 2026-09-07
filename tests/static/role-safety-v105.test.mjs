import fs from 'node:fs';

const files = [
  'app/Http/Middleware/AdminMiddleware.php',
  'app/Http/Middleware/BackofficeMiddleware.php',
  'app/Http/Controllers/AuthController.php',
  'resources/views/layouts/app.blade.php',
  'resources/views/layouts/admin.blade.php',
  'resources/views/home.blade.php',
  'resources/views/site/sections/hero.blade.php',
  'resources/views/site/sections/cta.blade.php',
  'resources/views/admin/dashboard.blade.php',
];
let failures = [];
for (const file of files) {
  const text = fs.readFileSync(file, 'utf8');
  if (/->isBackoffice\s*\(/.test(text) || /->isAdmin\s*\(/.test(text)) failures.push(file);
}
if (failures.length) {
  console.error('FAIL custom role methods still required at runtime:', failures.join(', '));
  process.exit(1);
}
const home=fs.readFileSync('resources/views/home.blade.php','utf8') + fs.readFileSync('resources/views/site/sections/hero.blade.php','utf8') + fs.readFileSync('resources/views/site/sections/cta.blade.php','utf8');
if (!home.includes("['admin','order_manager']")) { console.error('FAIL home CTA does not treat order_manager as backoffice'); process.exit(1); }
console.log(`PASS role-safe runtime checks (${files.length} files)`);
