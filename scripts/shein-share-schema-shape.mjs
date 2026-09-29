/**
 * Inspect structural evidence without ever returning product, URL, token,
 * cookie, identifier, customer or price values. A synthetic/diagnostic tool;
 * it does not itself attest the current live SHEIN API schema.
 */
const SHARE_ROOTS = new Set(['cartShareData', 'shareCartData', 'cartshare', 'cart_share', 'shareCartInfo', 'cartShareInfo']);
const LIST_KEYS = new Set(['goods_list', 'goodsList', 'items', 'cart_list', 'cartList']);
const SAFE_PATH_PARTS = new Set([...SHARE_ROOTS, ...LIST_KEYS, 'data', 'response', 'result', 'payload', 'obj', 'info']);
const FIELDS = [
  'goods_id', 'goodsId', 'goods_sn', 'sku_id', 'skuId', 'skc_id', 'skcId',
  'spu_id', 'spuId', 'color', 'color_name', 'colorName', 'size', 'size_name',
  'sizeName', 'variant', 'quantity', 'qty', 'goods_num', 'goods_quantity',
  'salePrice_usdAmount', 'sale_price_usdAmount', 'discountPrice_usdAmount',
  'discount_price_usdAmount', 'usdAmount', 'usd_amount',
];

function isRecord(value) {
  return value !== null && typeof value === 'object' && !Array.isArray(value);
}

function itemShape(items) {
  const records = items.filter(isRecord).slice(0, 8);
  const fields = Object.fromEntries(FIELDS.map(field => [field, false]));
  for (const item of records) {
    for (const field of FIELDS) {
      if (field.includes('_usdAmount')) {
        const [priceField] = field.split('_usdAmount');
        fields[field] ||= isRecord(item[priceField]) && (Object.hasOwn(item[priceField], 'usdAmount') || Object.hasOwn(item[priceField], 'usd_amount'));
      } else {
        fields[field] ||= Object.hasOwn(item, field);
      }
    }
  }
  return fields;
}

export function inspectShareShape(payload, { groupId = '', shareToken = '' } = {}) {
  if (!isRecord(payload)) return [];
  const output = [];
  const visited = new WeakSet();
  let visitedCount = 0;
  function visit(node, path, shareScope, depth) {
    if (!isRecord(node) || visited.has(node) || depth > 11 || ++visitedCount > 2500 || output.length >= 12) return;
    visited.add(node);
    for (const [key, value] of Object.entries(node)) {
      const safeKey = SAFE_PATH_PARTS.has(key) ? key : 'other';
      const nextPath = path ? `${path}.${safeKey}` : safeKey;
      const nextScope = SHARE_ROOTS.has(key) && isRecord(value) ? value : shareScope;
      if (LIST_KEYS.has(key) && Array.isArray(value) && shareScope) {
        const actualGroup = String(shareScope.group_id ?? shareScope.groupId ?? '').trim();
        const actualToken = String(shareScope.shc ?? shareScope.share_token ?? '').trim();
        const requestedGroup = String(groupId).trim();
        const requestedToken = String(shareToken).trim();
        const explicitConflict = Boolean(requestedGroup && actualGroup && requestedGroup !== actualGroup);
        const boundToShare = !explicitConflict && (
          Boolean(requestedGroup && actualGroup && requestedGroup === actualGroup) ||
          Boolean(requestedToken && actualToken && requestedToken === actualToken)
        );
        output.push({
          listPath: nextPath,
          boundToShare,
          itemCount: Math.min(1000, value.filter(isRecord).length),
          itemFields: itemShape(value),
        });
      } else if (isRecord(value)) {
        visit(value, nextPath, nextScope, depth + 1);
      }
    }
  }
  visit(payload, '', null, 0);
  return output;
}
