import fs from 'node:fs';
import assert from 'node:assert/strict';

const read = p => fs.readFileSync(p, 'utf8');
const adminCarts = read('resources/views/admin/carts/index.blade.php');
const cartsIndex = read('resources/views/carts/index.blade.php');
const cartsShow = read('resources/views/carts/show.blade.php');
const adminCartShow = read('resources/views/admin/carts/show.blade.php');
const rates = read('resources/views/admin/exchange-rates/index.blade.php');
const stores = read('resources/views/admin/stores/index.blade.php');
const home = read('resources/views/home.blade.php');
const publicSections = fs.readdirSync('resources/views/site/sections').filter(f=>f.endsWith('.blade.php')).map(f=>read('resources/views/site/sections/'+f)).join('\n');

assert.match(adminCarts, /محفوظة/);
assert.match(adminCarts, /مؤكدة/);
assert.doesNotMatch(adminCarts, />\s*\{\{\s*\$cart->status\s*\}\}\s*</);
assert.match(cartsIndex, /\$statusLabels/);
assert.match(cartsIndex, /\$currencyLabels/);
assert.match(cartsShow, /\$currencyLabels/);
assert.match(adminCartShow, /\$currencyLabels/);
assert.match(rates, /دولار أمريكي/);
assert.match(stores, /دولار أمريكي/);
assert.match(home + publicSections, /دولار أمريكي/);
console.log('arabic-ui-v106: PASS');
