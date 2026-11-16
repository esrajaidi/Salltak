import { uaeSharedLandingUrl } from './shein-ae-pricing.mjs';

// Read only SHEIN's static cart deeplink. Never execute remote JavaScript or
// infer a cart from a homepage, recommendations, or an unrelated group ID.
export function landingFromOnelinkHtml(html, original) {
  try {
    const source = new URL(original);
    if (source.protocol !== 'https:' || source.hostname !== 'onelink.shein.com'
        || source.username || source.password || source.port
        || typeof html !== 'string' || html.length > 2_000_000) return '';
    const token = source.searchParams.get('shc');
    if (!/^2_[A-Za-z0-9_-]{4,120}$/.test(token || '')) return '';
    for (const match of html.matchAll(/<input\b[^>]{0,8192}>/gi)) {
      const tag = match[0];
      if (!/\bid\s*=\s*(["'])deeplink\1/i.test(tag)) continue;
      const raw = tag.match(/\bvalue\s*=\s*(["'])([^"']{1,8000})\1/i)?.[2];
      if (!raw) continue;
      const deep = new URL(raw.replace(/&amp;/g, '&').replace(/&#(?:0*38|x0*26);/gi, '&'));
      if (deep.protocol !== 'sheinlink:' || deep.hostname !== 'applink'
          || deep.pathname !== '/share_receiver') continue;
      const data = JSON.parse(deep.searchParams.get('data') || 'null');
      if (!data || typeof data !== 'object' || Array.isArray(data)
          || (data.share_type !== undefined && data.share_type !== 'cart')
          || data.shc !== token || !/^\d{6,20}$/.test(String(data.group_id || ''))) continue;
      const landing = new URL('https://www.shein.com/ar/cart/share/landing');
      landing.searchParams.set('group_id', String(data.group_id));
      landing.searchParams.set('shc', token);
      return uaeSharedLandingUrl(original, landing.href);
    }
  } catch {}
  return '';
}
