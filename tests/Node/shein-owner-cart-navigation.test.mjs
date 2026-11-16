import test from 'node:test';
import assert from 'node:assert/strict';
import { ownerCartUrl } from '../../scripts/shein-owner-cart-navigation.mjs';
import { ownerCartReadiness } from '../../scripts/shein-owner-cart-evidence.mjs';

test('only accepts same-www account cart links, not shared carts or third parties', () => {
  assert.equal(ownerCartUrl('/cart?from=header'), 'https://www.shein.com/cart');
  assert.equal(ownerCartUrl('/ar/cart.html'), 'https://www.shein.com/ar/cart.html');
  for (const url of ['/cart/share/landing?group_id=123456789', 'https://m.shein.com/cart', 'https://www.shein.com.evil.test/cart', 'https://user:password@www.shein.com/cart', '/checkout', '/cart/delete', 'javascript:alert(1)']) {
    assert.equal(ownerCartUrl(url), '');
  }
});
test('recognizes locale cart paths but does not mistake OOPS or login for cart contents', () => {
  const base = { finalUrl:'https://www.shein.com/ar/cart.html', sessionSupplied:true, httpStatus:200 };
  assert.equal(ownerCartReadiness({...base, visibleText:'Cart(32) OOPS... Back to home'}).status, 'cart_page_unavailable');
  assert.equal(ownerCartReadiness({...base, visibleText:'Your cart is empty. Sign in to view your cart'}).status, 'login_required');
  assert.equal(ownerCartReadiness({...base, visibleText:'Your cart is empty'}).status, 'empty_cart_observed');
});
