// Diagnostic observations, not a quote parser. No observed amount is approved
// as a unit price or coupon price until real row semantics are validated.
export function sanitizeOwnerPriceSamples(rows) {
  if (!Array.isArray(rows)) return [];
  return rows.filter(row => row && /^\d{4,20}$/.test(String(row.product_id || ''))).slice(0, 3).map(row => ({
    product_id: String(row.product_id),
    sku: /^[A-Za-z0-9_-]{1,120}$/.test(String(row.sku || '')) ? String(row.sku) : '',
    option_text: String(row.option_text || '').replace(/\s+/g, ' ').slice(0, 80),
    prices: (Array.isArray(row.prices) ? row.prices : []).filter(price =>
      price && typeof price.text === 'string' && price.text.length <= 100
      && !/[<>@]/.test(price.text) && /(?:US\$|USD|AED|\$|د\.إ)\s*\d/i.test(price.text)
    ).slice(0, 6).map(price => ({
      marker: String(price.marker || '').replace(/[^A-Za-z0-9_ -]/g, '').slice(0, 120),
      text: price.text.replace(/\s+/g, ' ').trim(),
    })),
    verified: false,
  }));
}

// Runs inside the owner's account-cart document. Captures three small row
// samples only: no HTML, storage, account text, address or payment information.
export function collectOwnerPriceSamples() {
  const rows = [];
  const roots = Array.from(document.querySelectorAll(
    '[data-goods-id], [data-sku-id], [class*="cart-item"], [class*="cartItem"], [class*="cart_item"]'
  )).slice(0, 300);
  const seen = new Set();
  for (const root of roots) {
    if (rows.length >= 3) break;
    // A data-goods-id anywhere in the page is insufficient membership proof.
    // Unknown markup is left unsampled rather than labeled as an account row.
    const rowClass = String(root.className || '').split(/\s+/).some(name =>
      /^(?:cart-item|cartItem|cart_item)(?:$|__|-)/.test(name));
    const cartRegion = root.closest('[class*="cart-list"], [class*="cartList"], [class*="cart_items"], [data-testid="cart-items"]');
    if (!rowClass && !cartRegion) continue;
    if (root.closest('[class*="recommend"], [class*="suggest"], [class*="similar"]')) continue;
    const rect = root.getBoundingClientRect();
    if (rect.width <= 0 || rect.height <= 0) continue;
    const ids = new Set();
    for (const anchor of root.querySelectorAll('a[href]')) {
      try {
        const url = new URL(anchor.href);
        const match = url.pathname.match(/-p-(\d{4,20})\.html$/i);
        if (url.protocol === 'https:' && url.hostname === 'www.shein.com' && match) ids.add(match[1]);
      } catch {}
    }
    if (ids.size !== 1) continue;
    const productId = [...ids][0];
    const prices = Array.from(root.querySelectorAll('[class*="price"], [data-price]')).slice(0, 20).map(node => ({
      marker: String(node.className || '').slice(0, 120),
      text: String(node.innerText || '').replace(/\s+/g, ' ').trim().slice(0, 100),
    })).filter(price => /(?:US\$|USD|AED|\$|د\.إ)\s*\d/i.test(price.text)).slice(0, 6);
    if (!prices.length) continue;
    const sku = root.getAttribute('data-sku-id') || root.getAttribute('data-sku') || '';
    const key = productId + '|' + sku;
    if (seen.has(key)) continue;
    seen.add(key);
    const option = root.querySelector('[class*="goods-attr"], [class*="goodsAttr"], [class*="sku-attr"], [class*="item-attr"]');
    rows.push({ product_id: productId, sku, option_text: String(option?.innerText || '').slice(0, 80), prices });
  }
  return rows;
}
