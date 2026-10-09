// UAE is the merchant's purchasing region, regardless of the customer's share country.
// A different country in a share URL is input context, not a verified UAE price.
export const PRICING_COUNTRY = 'AE';

// Try the actual purchasing site first. Mobile shared-cart services remain an
// explicitly selectable last-resort fallback, not the default price source.
export const PRIMARY_SHEIN_HOST = 'www.shein.com';
export const FALLBACK_SHARE_HOST = 'm.shein.com';

export function trustedShareHost(host) {
  return host === FALLBACK_SHARE_HOST ? FALLBACK_SHARE_HOST : PRIMARY_SHEIN_HOST;
}

function safeShein(url) {
  try {
    const parsed = new URL(String(url || ''));
    if (parsed.protocol !== 'https:') return null;
    const host = parsed.hostname.toLowerCase();
    return (host === 'shein.com' || host.endsWith('.shein.com')) ? parsed : null;
  } catch { return null; }
}

export function uaeSharedLandingUrl(originalUrl, redirectedUrl = '', shareHost = PRIMARY_SHEIN_HOST) {
  // Use the original share identity when both sources provide one.
  const original = safeShein(originalUrl);
  const redirected = safeShein(redirectedUrl);
  const groupId = original?.searchParams.get('group_id') || redirected?.searchParams.get('group_id');
  if (!groupId || !/^\d{6,20}$/.test(groupId)) return '';
  const result = new URL('https://' + trustedShareHost(shareHost) + '/ar/cart/share/landing');
  result.searchParams.set('group_id', groupId);
  const shc = original?.searchParams.get('shc') || redirected?.searchParams.get('shc');
  if (shc) result.searchParams.set('shc', shc);
  const urlFrom = original?.searchParams.get('url_from') || redirected?.searchParams.get('url_from');
  if (urlFrom) result.searchParams.set('url_from', urlFrom);
  result.searchParams.set('local_country', PRICING_COUNTRY);
  result.searchParams.set('cart_share', '1');
  return result.href;
}

export function uaeShareRequest(url, expectedGroupId = '', shareHost = PRIMARY_SHEIN_HOST) {
  const u = safeShein(url);
  const groupId = String(expectedGroupId || '').trim();
  if (!u || !/^\d{6,20}$/.test(groupId)) return null;
  return {
    endpoint: 'https://' + trustedShareHost(shareHost) + '/ar/bff-api/order/cart/share/landing?_ver=1.1.8&_lang=ar',
    body: { groupId, localCountry: PRICING_COUNTRY, userLocalSizeCountry: '' },
    currency: 'AED',
  };
}

export function isAedPrice(price) {
  if (!price || typeof price !== 'object' || Array.isArray(price)) return false;
  const declared = String(price.currency || price.currencyCode || price.currency_code || '').trim().toUpperCase();
  if (declared) return declared === 'AED';
  const symbol = String(price.amountWithSymbol || '').trim();
  return /^(?:AED(?=\s|[0-9])|د\.?\s*إ|د\.?إ|د\.?أ)/i.test(symbol);
}
