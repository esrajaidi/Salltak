import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const adapter = fs.readFileSync('app/Services/CartImport/Adapters/SheinShareAdapter.php', 'utf8');
const view = fs.readFileSync('resources/views/carts/preview.blade.php', 'utf8');
const cart = fs.readFileSync('app/Http/Controllers/CartController.php', 'utf8');

test('shared cart price cannot be labeled as authenticated purchasing-account price', () => {
  assert.match(adapter, /'account_price_verified' => false/);
  assert.match(adapter, /'price_source' => 'shein_shared_cart_estimate'/);
  assert.match(adapter, /if \(\$isSharedCart\) \{\s*return ImportResult::needsReview\(/);
  assert.match(adapter, /www\.shein\.com/);
  assert.match(adapter, /if \(\(\$browser\['status'\] \?\? ''\) === 'guest_price_needs_review'\)/);
  assert.match(adapter, /'mobile_share_price_needs_review', 'account_price_unverified'/);
});

test('customer preview distinguishes AED storefront evidence from logged-in account price', () => {
  assert.match(view, /\$accountPriceVerified = \(bool\)/);
  assert.match(view, /\$sheinPriceNeedsReview = \$isSheinShare && !\$accountPriceVerified/);
  // Some onelink BFF responses omit the numeric group ID. They must still
  // receive a SHEIN account-price warning based on the original source host.
  assert.match(view, /\$sourceHost = strtolower\(/);
  assert.match(view, /\$sourceHost === 'shein.com'/);
  assert.match(view, /str_ends_with\(\$sourceHost, '\.shein\.com'\)/);
  assert.match(view, /\$isSheinShare = \$isSheinSource/);
  assert.match(view, /\$result->meta\['price_source'\]/);
  assert.match(view, /أسعار SHEIN تقديرية/);
  assert.match(view, /مش سعر حسابك بعد Login/);
  assert.match(view, /لكن هذا لا يعني تطابق السعر مع حساب الشراء بعد Login/);
  assert.match(view, /أسعار SHEIN تقديرية — لم تُؤكّد من حساب الشراء/);
  assert.match(view, /حفظ السلة للمراجعة/);
  assert.doesNotMatch(view, /السعر المستورد قبل كوبونات الحساب/);
  assert.match(cart, /'import_status' => \$result->status/);
});
