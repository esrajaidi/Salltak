// UAE is the merchant's purchasing region, regardless of the customer's share country.
// A different country in a share URL is input context, not a verified UAE price.
export const PRICING_COUNTRY = 'AE';

function safeShein(url) {
  try {
    const parsed = new URL(String(url || ''));
    if (parsed.protocol !== 'https:') return null;
    const host = parsed.hostname.toLowerCase();
    return (host === 'shein.com' || host.endsWith('.shein.com')) ? parsed : null;
  } catch { return null; }
}

export function uaeSharedLandingUrl(originalUrl, redirectedUrl = '') {
  // Use the original share identity when both sources provide one.
  const original = safeShein(originalUrl);
  const redirected = safeShein(redirectedUrl);
  const groupId = original?.searchParams.get('group_id') || redirected?.searchParams.get('group_id');
  if (!groupId || !/^[a-zA-Z0-9_-]{6,100}$/.test(groupId)) return '';
  const result = new URL('https://m.shein.com/ar/cart/share/landing');
  result.searchParams.set('group_id', groupId);
  const shc = original?.searchParams.get('shc') || redirected?.searchParams.get('shc');
  if (shc) result.searchParams.set('shc', shc);
  const urlFrom = original?.searchParams.get('url_from') || redirected?.searchParams.get('url_from');
  if (urlFrom) result.searchParams.set('url_from', urlFrom);
  result.searchParams.set('local_country', PRICING_COUNTRY);
  result.searchParams.set('cart_share', '1');
  return result.href;
}

export function uaeShareRequest(url, expectedGroupId = '') {
  const u = safeShein(url);
  const groupId = String(expectedGroupId || '').trim();
  if (!u || !/^[a-zA-Z0-9_-]{6,100}$/.test(groupId)) return null;
  return {
    endpoint: 'https://m.shein.com/ar/bff-api/order/cart/share/landing?_ver=1.1.8&_lang=ar',
    body: { groupId, localCountry: PRICING_COUNTRY, userLocalSizeCountry: '' },
    currency: 'AED',
  };
}

export function isAedPrice(price) {
  if (!price || typeof price !== 'object' || Array.isArray(price)) return false;
  const declared = String(price.currency || price.currencyCode || price.currency_code || '').trim().toUpperCase();
  if (declared) return declared === 'AED';
  const symbol = String(price.amountWithSymbol || '').trim();
  return /^(?:AED\b|د\.?\s*إ|د\.?إ|د\.?أ)/i.test(symbol);
}
