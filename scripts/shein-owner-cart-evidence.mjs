/**
 * Pure, non-mutating SHEIN account-cart readiness classification.
 * Raw page text, cart IDs and account cookies must never be returned.
 * No amount visible on a cart page is automatically an authenticated,
 * coupon-adjusted per-SKU purchase price.
 */
import { ownerCartUrl } from './shein-owner-cart-navigation.mjs';

export function ownerCartReadiness({
  finalUrl = '', httpStatus = 0, visibleText = '',
  candidateProductCards = 0, sessionSupplied = false,
} = {}) {
  const text = String(visibleText || '').slice(0, 12000);
  let trustedHost = false;
  let cartPath = false;
  try {
    const url = new URL(String(finalUrl || ''));
    trustedHost = url.protocol === 'https:' && url.hostname === 'www.shein.com'
      && !url.username && !url.password && !url.port;
    cartPath = ownerCartUrl(url.href) !== '';
  } catch { /* invalid or redirected navigation */ }

  const statusCode = Number(httpStatus) || 0;
  const challenge = /(?:verify you are human|security verification|unusual traffic|captcha|access denied|robot check|تحقق أمني|أثبت أنك إنسان)/i.test(text);
  const loginGate = /(?:sign in to view your cart|please log in to continue|sign in to your account to continue|تسجيل الدخول لعرض سلة)/i.test(text);
  const pageUnavailable = /(?:\bOOPS\b|page not found|الصفحة غير موجودة)/i.test(text) || statusCode >= 400;
  const match = text.match(/(?:^|\n|\s)(?:Cart|All Items|Bag|سلة التسوق|السلة|كل المنتجات)\s*[\(\uFF08]\s*(\d{1,4})\s*[\)\uFF09]/i);
  const count = match ? Math.min(1000, Number(match[1])) : null;
  const emptyHint = /(?:your cart is empty|your bag is empty|your shopping bag is empty|no items in (?:your )?cart|سلتك فارغة|سلة التسوق فارغة|السلة فارغة)/i.test(text);
  const couponEvidence = /(?:after coupon|checkout with coupon|coupon applied|coupon discount|best coupon|بعد الكوبون|خصم الكوبون)/i.test(text);
  const countryAeEvidence = /(?:United Arab Emirates|الإمارات العربية المتحدة|shipping to UAE)/i.test(text);
  const usdEvidence = /(?:USD|US\$|\$\s*\d)/i.test(text);
  const cardCandidates = Math.max(0, Math.min(1000, Number(candidateProductCards) || 0));
  const cartEmptyProven = trustedHost && cartPath && emptyHint
    && !loginGate && !challenge && !pageUnavailable && statusCode >= 200 && statusCode < 300
    && cardCandidates === 0 && (count === null || count === 0);
  const hasItems = count !== null ? count > 0 : cardCandidates > 0;

  let status = 'cart_state_unclear';
  if (!sessionSupplied) status = 'no_owner_session';
  else if (!trustedHost || !cartPath) status = 'redirected_or_non_cart_page';
  else if (statusCode === 403 || statusCode === 429 || challenge) status = 'access_limited';
  else if (loginGate) status = 'login_required';
  else if (pageUnavailable) status = 'cart_page_unavailable';
  else if (cartEmptyProven) status = 'empty_cart_observed';
  else if (hasItems) status = 'existing_cart_must_be_preserved';

  return {
    status,
    session_supplied: Boolean(sessionSupplied),
    account_authenticated: false, // A saved cookie is NOT server-side proof of login.
    final_checkout_price_verified: false,
    account_cart_modified: false,
    coupon_label_seen: couponEvidence,
    cart_empty_proven: cartEmptyProven,
    cart_item_count_visible: count,
    product_card_candidates: cardCandidates,
    ae_region_visible: countryAeEvidence,
    usd_currency_visible: usdEvidence,
    http_status: Math.max(0, Math.min(599, statusCode)),
  };
}
