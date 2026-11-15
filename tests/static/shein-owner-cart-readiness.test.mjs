import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import { ownerCartReadiness } from '../../scripts/shein-owner-cart-evidence.mjs';

const url = 'https://www.shein.com/cart';

test('existing purchaser cart must never be silently mixed with a customer cart', () => {
  const state = ownerCartReadiness({
    finalUrl: url, httpStatus: 200, sessionSupplied: true,
    visibleText: 'Cart(32) United Arab Emirates\nCheckout with Coupon(34)\n$327.77 After Coupon',
    candidateProductCards: 32,
  });
  assert.equal(state.status, 'existing_cart_must_be_preserved');
  assert.equal(state.cart_item_count_visible, 32);
  assert.equal(state.coupon_label_seen, true);
  assert.equal(state.ae_region_visible, true);
  assert.equal(state.usd_currency_visible, true);
  assert.equal(state.final_checkout_price_verified, false);
  assert.equal(state.account_authenticated, false);
  assert.equal(state.account_cart_modified, false);
  assert.ok(!JSON.stringify(state).includes('327.77'));
});

test('empty cart is safe to report but never claims coupon price', () => {
  const state = ownerCartReadiness({
    finalUrl: url, httpStatus: 200, sessionSupplied: true,
    visibleText: 'Your Cart Is Empty', candidateProductCards: 0,
  });
  assert.equal(state.status, 'empty_cart_observed');
  assert.equal(state.cart_empty_proven, true);
  assert.equal(state.final_checkout_price_verified, false);
});

test('never trusts unproven cart emptiness, login gate, redirect or a challenge', () => {
  const cases = [
    [{ finalUrl: url, visibleText: 'Cart(3) Your Cart Is Empty', candidateProductCards: 3 }, 'existing_cart_must_be_preserved'],
    [{ finalUrl: url, visibleText: 'Add to cart', candidateProductCards: 0 }, 'cart_state_unclear'],
    [{ finalUrl: 'https://m.shein.com/cart', visibleText: 'Cart(32)' }, 'redirected_or_non_cart_page'],
    [{ finalUrl: 'https://www.shein.com/user/login', visibleText: 'Please log in to continue' }, 'redirected_or_non_cart_page'],
    [{ finalUrl: url, httpStatus: 403, visibleText: 'Cart(32)' }, 'access_limited'],
    [{ finalUrl: url, visibleText: 'Security Verification' }, 'access_limited'],
  ];
  for (const [args, expected] of cases) {
    assert.equal(ownerCartReadiness({ sessionSupplied: true, ...args }).status, expected);
  }
  assert.equal(ownerCartReadiness({ finalUrl: url }).status, 'no_owner_session');
});

test('diagnostics are admin-only and cannot add items, checkout or leak secrets', () => {
  const worker = fs.readFileSync('scripts/shein-owner-cart-readiness.mjs', 'utf8');
  const controller = fs.readFileSync('app/Http/Controllers/Admin/SheinAccountSessionController.php', 'utf8');
  const routes = fs.readFileSync('routes/web.php', 'utf8');
  const ui = fs.readFileSync('resources/views/admin/shein-session/index.blade.php', 'utf8');

  assert.match(worker, /https:\/\/www\.shein\.com\/cart/);
  assert.match(worker, /storageState: state/);
  assert.match(worker, /ownerCartReadiness/);
  assert.doesNotMatch(worker, /page\.click\(|locator\([^)]*\)\.click\(|\.fill\(|\.press\(|\.post\(|addToCart|placeOrder|submitOrder/);
  assert.doesNotMatch(worker, /console\.log|storageState\(\)/);
  assert.match(controller, /public function probeCartReadiness\(\)/);
  assert.match(controller, /\$this->vault->read\(true\)/);
  assert.match(controller, /'account_cart_modified' => false/);
  assert.match(controller, /'final_checkout_price_verified' => false/);
  assert.match(routes, /middleware\('admin'\)->group/);
  assert.match(routes, /shein-session\/cart-readiness/);
  assert.match(routes, /throttle:shein-account-price-probe/);
  assert.match(ui, /admin\.shein-session\.cart-readiness/);
  assert.match(ui, /قراءة فقط/);
});
