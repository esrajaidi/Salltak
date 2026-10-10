import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const worker = fs.readFileSync('scripts/shein-www-account-price-probe.mjs', 'utf8');
const controller = fs.readFileSync('app/Http/Controllers/Admin/SheinAccountSessionController.php', 'utf8');
const routes = fs.readFileSync('routes/web.php', 'utf8');
const view = fs.readFileSync('resources/views/admin/shein-session/index.blade.php', 'utf8');

test('price probe stays admin-only, non-mutating and uses the vault', () => {
  assert.match(routes, /middleware\('admin'\)->group/);
  assert.match(routes, /shein-session\/price-probe/);
  assert.match(routes, /throttle:shein-account-price-probe/);
  const provider = fs.readFileSync('app/Providers/AppServiceProvider.php', 'utf8');
  assert.match(provider, /RateLimiter::for\('shein-account-price-probe'/);
  assert.match(provider, /Limit::perMinutes\(5, 2\)/);
  assert.match(provider, /->withErrors\(/);
  assert.match(provider, /->withInput\(/);
  assert.match(controller, /function probeProductPrice\(/);
  assert.match(controller, /\$this->vault->read\(true\)/);
  assert.match(controller, /->input\(\$payload\)/);
  assert.doesNotMatch(controller, /Cart::update|CartItem::update|Order::update/);
  assert.match(view, /www\.shein\.com/);
  assert.match(view, /route\('admin\.shein-session\.price-probe'\)/);
});

test('one product is inspected on www as owner versus guest without claiming authentication', () => {
  assert.match(worker, /u\.hostname\.toLowerCase\(\) === 'www\.shein\.com'/);
  assert.match(worker, /p-\\d\{4,20\}/);
  assert.match(worker, /storageState: storage/);
  assert.match(worker, /await browser\.newContext\(options\)/);
  assert.match(worker, /await page\.goto\(productUrl/);
  assert.match(worker, /for \(let poll = 0; poll < 4; poll\+\+\)/);
  assert.match(worker, /product-intro__head-price/);
  assert.match(worker, /product_price_dom/);
  assert.match(worker, /public_structured_data/);
  assert.match(worker, /page_loaded_without_price/);
  assert.match(worker, /security_check_possible/);
  assert.match(worker, /USD/);
  assert.match(controller, /set_time_limit\(115\)/);
  assert.match(controller, /Process::timeout\(95\)/);
  assert.match(controller, /\$result\['indicators'\]/);
  assert.match(controller, /'http_status'/);
  assert.match(view, /قسم تفاصيل المنتج/);
  assert.match(view, /'price_candidates_found'/);
  assert.match(view, /لم يتم العثور على سعر موثوق للعرض/);
  assert.match(worker, /accountAuthenticated: false/);
  assert.match(worker, /purchasePriceConfirmed: false/);
  assert.doesNotMatch(worker, /console\.log/);
  assert.doesNotMatch(worker, /process\.stdout\.write\(.*accountSession/);
});
