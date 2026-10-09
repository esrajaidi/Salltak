import { parseSheinGoodsAttr } from './shein-goods-attr.mjs';

// This parser only trusts rows from the exact SHEIN shared-cart BFF POST,
// when the POST's groupId matches the group_id on the user's shared link.
// Cart-recommendation feeds and unrelated price responses are never imported.
function isSharedEndpoint(value) {
  try {
    const u = new URL(String(value || ''));
    return u.protocol === 'https:'
      && (u.hostname === 'shein.com' || u.hostname.endsWith('.shein.com'))
      && /(?:^|\/)bff-api\/order\/cart\/share\/landing\/?$/.test(u.pathname);
  } catch { return false; }
}

function validRequest(body, expectedGroupId) {
  if (!String(expectedGroupId || '').trim()) return false;
  let data = body;
  if (typeof data === 'string') {
    try { data = JSON.parse(data); } catch { return false; }
  }
  return Boolean(data && typeof data === 'object'
    && !Array.isArray(data)
    && String(data.groupId || '').trim() === String(expectedGroupId).trim());
}

function amount(value) {
  const s = String(value ?? '').trim();
  if (!/^\d+(?:\.\d{1,4})?$/.test(s)) return 0;
  const n = Number(s);
  return Number.isFinite(n) && n > 0 ? n : 0;
}

function img(value) {
  const source = String(value || '').trim();
  if (!source) return '';
  try {
    const parsed = new URL(source, 'https://m.shein.com/');
    if (parsed.protocol !== 'https:') return '';
    const host = parsed.hostname.toLowerCase();
    if (host === 'ltwebstatic.com' || host.endsWith('.ltwebstatic.com')
      || /\.(?:png|jpe?g|webp|avif)(?:$)/i.test(parsed.pathname)) return parsed.href;
  } catch {}
  return '';
}

function toItem(p) {
  if (!p || typeof p !== 'object' || Array.isArray(p)) return null;
  const externalId = String(p.goods_id || p.goodsId || '').trim();
  const name = String(p.goods_name || p.goodsName || '').replace(/\s+/g, ' ').trim().slice(0, 500);
  if (!externalId || !name || name.length > 500) return null;
  const price = amount(p.salePrice?.usdAmount ?? p.salePrice?.usd_amount ?? p.sale_price?.usdAmount);
  const attr = String(p.goodsAttr || p.goods_attr || '').trim().slice(0, 180);
  const option = parseSheinGoodsAttr(attr);
  return {
    external_id: externalId,
    name,
    product_url: 'https://ar.shein.com/product-p-' + encodeURIComponent(externalId) + '.html',
    image_url: img(p.goods_img || p.goodsImg),
    variant: String(p.sku_code || p.itemSku || attr).trim().slice(0, 180),
    color: option.color,
    size: option.size,
    // SHEIN's shared BFF does not disclose saved quantities; do not invent them.
    quantity: 1,
    unit_price_original: price,
    currency: 'USD',
  };
}

export function parseSheinShareBff(payload, {
  url = '', method = '', requestBody = null, expectedGroupId = '',
} = {}) {
  const empty = { bound: false, items: [], candidateCount: 0, missingUsdPriceCount: 0 };
  if (!isSharedEndpoint(url) || String(method).toUpperCase() !== 'POST'
    || !validRequest(requestBody, expectedGroupId)) return empty;
  if (!payload || typeof payload !== 'object' || Array.isArray(payload)
    || String(payload.code ?? '') !== '0') return empty;
  const info = payload.info;
  if (!info || typeof info !== 'object' || Array.isArray(info)) return empty;

  const listNames = ['normalProducts', 'outStock', 'unavailable'];
  // Only structurally identified shared-cart rows; never JSON-wide product scans.
  const arrays = listNames.map(k => Array.isArray(info[k]) ? info[k] : []);
  if (!listNames.some(k => Array.isArray(info[k]))) return empty;
  const candidateCount = arrays.reduce((sum, a) => sum + a.length, 0);
  if (candidateCount > 1000) return empty;

  const items = [];
  const seen = new Set();
  let missingUsdPriceCount = 0;
  for (const entry of arrays.flat()) {
    const item = toItem(entry);
    if (!item) continue;
    if (!(item.unit_price_original > 0)) { missingUsdPriceCount++; continue; }
    const key = [item.external_id, item.variant, item.color, item.size].join('|');
    if (seen.has(key)) continue;
    seen.add(key);
    items.push(item);
  }
  return {
    bound: true,
    items: items.slice(0, 60),
    candidateCount,
    missingUsdPriceCount,
  };
}
