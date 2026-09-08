import fs from 'node:fs';
import net from 'node:net';
import path from 'node:path';
import process from 'node:process';
import { chromium } from 'playwright-chromium';
import { parseSheinGoodsAttr } from './shein-goods-attr.mjs';

const MAX_PAYLOADS = 30;
const MAX_PAYLOAD_BYTES = 2_500_000;
const MAX_HTML_BYTES = 3_000_000;
const MAX_ITEMS = 60;

function output(data, exitCode = 0) {
  process.stdout.write(JSON.stringify(data));
  process.exitCode = exitCode;
}

function allowedMainUrl(raw) {
  try {
    const url = new URL(raw);
    if (url.protocol !== 'https:') return false;
    const host = url.hostname.toLowerCase();
    return host === 'shein.com' || host.endsWith('.shein.com');
  } catch {
    return false;
  }
}

function isSheinUrl(raw) {
  try {
    const host = new URL(raw).hostname.toLowerCase();
    return host === 'shein.com' || host.endsWith('.shein.com');
  } catch {
    return false;
  }
}

function isPrivateLiteralHost(hostname) {
  const host = hostname.toLowerCase();
  if (host === 'localhost' || host.endsWith('.localhost')) return true;
  const ipType = net.isIP(host);
  if (!ipType) return false;
  if (ipType === 4) {
    const p = host.split('.').map(Number);
    return p[0] === 10 || p[0] === 127 || p[0] === 0 ||
      (p[0] === 169 && p[1] === 254) ||
      (p[0] === 172 && p[1] >= 16 && p[1] <= 31) ||
      (p[0] === 192 && p[1] === 168);
  }
  return host === '::1' || host.startsWith('fc') || host.startsWith('fd') || host.startsWith('fe80:');
}

function looksLikeChallenge(text) {
  const hay = String(text || '').toLowerCase();
  return [
    'captcha', 'verify you are human', 'security verification', 'access denied',
    'too many requests', 'unusual traffic', 'robot check', 'please verify',
    'التحقق الأمني', 'التحقق من أنك', 'تحقق من أنك', 'طلب غير معتاد'
  ].some(token => hay.includes(token));
}

function currencyFromUrl(raw) {
  try {
    const country = new URL(raw).searchParams.get('local_country')?.toUpperCase();
    const map = { AE: 'AED', SA: 'SAR', KW: 'KWD', QA: 'QAR', BH: 'BHD', OM: 'OMR', US: 'USD', GB: 'GBP', UK: 'GBP', TR: 'TRY' };
    return map[country] || 'USD';
  } catch {
    return 'USD';
  }
}

function shareApiRequestFromUrl(raw) {
  try {
    const url = new URL(raw);
    const groupId = String(url.searchParams.get('group_id') || '').trim();
    if (!groupId || !isSheinUrl(url.href)) return null;

    const localCountry = String(url.searchParams.get('local_country') || '').trim().toUpperCase();
    const firstSegment = url.pathname.split('/').filter(Boolean)[0] || 'ar';
    const locale = /^[a-z]{2}(?:-[a-z]{2})?$/i.test(firstSegment) ? firstSegment : 'ar';
    const language = locale.split('-')[0].toLowerCase();

    return {
      endpoint: `${url.origin}/${locale}/bff-api/order/cart/share/landing?_ver=1.1.8&_lang=${encodeURIComponent(language)}`,
      body: {
        groupId,
        localCountry,
        userLocalSizeCountry: '',
      },
    };
  } catch {
    return null;
  }
}

function scalar(value) {
  if (value === null || value === undefined) return '';
  if (['string', 'number', 'boolean'].includes(typeof value)) return String(value).trim();
  return '';
}

function firstScalar(obj, keys, fallback = '') {
  for (const key of keys) {
    if (Object.prototype.hasOwnProperty.call(obj, key)) {
      const val = scalar(obj[key]);
      if (val !== '') return val;
    }
  }
  return fallback;
}

function findAmount(value, depth = 0) {
  if (depth > 4 || value === null || value === undefined) return 0;
  if (typeof value === 'number') return Number.isFinite(value) && value > 0 ? value : 0;
  if (typeof value === 'string') {
    const clean = value.replace(/,/g, ' ');
    const matches = [...clean.matchAll(/(?:AED|د\.?إ|SAR|ر\.?س|USD|\$|EUR|€|GBP|£|KWD|QAR|BHD|OMR)?\s*([0-9]+(?:\.[0-9]{1,4})?)/gi)];
    const nums = matches.map(m => Number(m[1])).filter(n => Number.isFinite(n) && n > 0);
    return nums[0] || 0;
  }
  if (typeof value === 'object') {
    const preferred = ['amount', 'price', 'salePrice', 'sale_price', 'value', 'unit_price', 'unitPrice', 'usdAmount', 'localAmount', 'retailPrice'];
    for (const key of preferred) {
      if (Object.prototype.hasOwnProperty.call(value, key)) {
        const amount = findAmount(value[key], depth + 1);
        if (amount > 0) return amount;
      }
    }
    for (const child of Object.values(value)) {
      const amount = findAmount(child, depth + 1);
      if (amount > 0) return amount;
    }
  }
  return 0;
}

function findUsdAmount(value, depth = 0) {
  if (depth > 5 || value === null || value === undefined) return 0;
  if (typeof value === 'object') {
    if (!Array.isArray(value)) {
      for (const key of ['usdAmount', 'usd_amount', 'usdPrice', 'usd_price']) {
        if (Object.prototype.hasOwnProperty.call(value, key)) {
          const amount = findAmount(value[key], depth + 1);
          if (amount > 0) return amount;
        }
      }
    }
    for (const child of Object.values(value)) {
      const amount = findUsdAmount(child, depth + 1);
      if (amount > 0) return amount;
    }
  }
  return 0;
}

function findCurrency(value, fallback = '') {
  const visit = (v, depth = 0) => {
    if (depth > 4 || v === null || v === undefined) return '';
    if (typeof v === 'string') {
      const m = v.toUpperCase().match(/\b(AED|SAR|USD|EUR|GBP|KWD|QAR|BHD|OMR|TRY)\b/);
      return m?.[1] || '';
    }
    if (typeof v === 'object') {
      for (const key of ['currency', 'currencyCode', 'currency_code', 'code']) {
        if (Object.prototype.hasOwnProperty.call(v, key)) {
          const c = String(v[key] ?? '').trim().toUpperCase();
          if (/^[A-Z]{3}$/.test(c)) return c;
        }
      }
      for (const child of Object.values(v)) {
        const c = visit(child, depth + 1);
        if (c) return c;
      }
    }
    return '';
  };
  return visit(value) || fallback;
}

function firstValue(obj, keys) {
  for (const key of keys) {
    if (Object.prototype.hasOwnProperty.call(obj, key) && obj[key] !== null && obj[key] !== undefined) return obj[key];
  }
  return undefined;
}

function imageFrom(value, depth = 0) {
  if (depth > 4 || value === null || value === undefined) return '';
  if (typeof value === 'string') {
    const s = value.trim();
    if (/^(https?:)?\/\//i.test(s) || s.startsWith('/')) return s;
    return '';
  }
  if (Array.isArray(value)) {
    for (const child of value) {
      const found = imageFrom(child, depth + 1);
      if (found) return found;
    }
    return '';
  }
  if (typeof value === 'object') {
    for (const key of ['url', 'src', 'origin_image', 'originImage', 'goods_img', 'goodsImg', 'image_url', 'imageUrl', 'thumbnail', 'thumb']) {
      if (Object.prototype.hasOwnProperty.call(value, key)) {
        const found = imageFrom(value[key], depth + 1);
        if (found) return found;
      }
    }
  }
  return '';
}

function absUrl(value, base) {
  if (!value) return '';
  try {
    if (String(value).startsWith('//')) return `https:${value}`;
    return new URL(String(value), base).href;
  } catch {
    return String(value);
  }
}

function mergeKnownContainers(node) {
  const merged = { ...node };
  const containers = [
    'goods', 'goodsInfo', 'goods_info', 'goodsDetail', 'goods_detail',
    'product', 'productInfo', 'product_info', 'productDetail', 'product_detail',
    'sku', 'skuInfo', 'sku_info', 'skc', 'skcInfo', 'skc_info',
    'item', 'itemInfo', 'item_info', 'businessInfo', 'business_info',
    'detail', 'detailInfo', 'detail_info'
  ];
  for (const key of containers) {
    const v = node[key];
    if (v && typeof v === 'object' && !Array.isArray(v)) Object.assign(merged, v, node);
  }
  return merged;
}

function variantAttrs(view) {
  const result = { color: '', size: '', variant: '' };
  const seen = new Set();
  const walk = (v, depth = 0) => {
    if (depth > 5 || !v || typeof v !== 'object' || seen.has(v)) return;
    seen.add(v);
    if (!Array.isArray(v)) {
      const label = firstScalar(v, ['attr_name', 'attrName', 'name', 'label', 'title', 'type']).toLowerCase();
      const val = firstScalar(v, ['attr_value', 'attrValue', 'value', 'name_value', 'nameValue', 'label_value', 'labelValue', 'attrValueName']);
      if (val) {
        if (!result.color && /(color|colour|لون)/i.test(label)) result.color = val;
        if (!result.size && /(size|مقاس)/i.test(label)) result.size = val;
        if (!result.variant) result.variant = val;
      }
    }
    for (const child of Object.values(v)) if (child && typeof child === 'object') walk(child, depth + 1);
  };
  for (const key of ['sku_sale_attr', 'skuSaleAttr', 'attributes', 'attrs', 'goods_attr_list', 'goodsAttrList', 'attr_list', 'attrList', 'saleAttrList', 'skuAttribute']) {
    if (view[key]) walk(view[key]);
  }
  return result;
}

function excludedProductContext(pathText) {
  return /(recommend|recommendation|suggest|suggestion|similar|related|guess|youmaylike|you_may_like|hot|feed|search|history|recent|viewed|wishlist|favorite|favourite|trend|flash|campaign|adlist|ad_list|marketing)/i.test(pathText);
}

function strongCartContext(pathText) {
  const text = String(pathText || '').toLowerCase();
  const explicitShare = /(cartshare|cart_share|sharecart|share_cart|sharedcart|shared_cart)/i.test(text);
  const explicitRows = /(cartlist|cart_list|cartitems|cart_items|cartgoods|cart_goods|baglist|bag_list|basketitems|basket_items|selectedgoods|selected_goods|selecteditems|selected_items)/i.test(text);
  const cartBranch = /(cart|bag|basket|checkout)/i.test(text) && /(goods_list|goodslist|item_list|items|products|skus|sku_list|list)/i.test(text);
  const responseHint = /(cart_response|shared_cart_response)/i.test(text);
  return explicitShare || explicitRows || cartBranch || responseHint;
}

function looksLikeMarkupNoise(value) {
  const s = String(value || '').trim();
  if (!s) return false;
  if (s.length > 700) return true;
  return /<style|<script|sourceMappingURL|sourceURL=webpack|webpack:\/\/|data:application\/json;base64|\{[^}]{0,120}:[^}]{0,120}\}|(?:^|;)\s*[a-z-]{2,30}\s*:/i.test(s);
}

function candidateFromNode(node, pathParts, baseUrl, fallbackCurrency) {
  if (!node || typeof node !== 'object' || Array.isArray(node)) return null;
  const view = mergeKnownContainers(node);
  const name = firstScalar(view, [
    'goods_name', 'goodsName', 'goods_title', 'goodsTitle', 'product_name', 'productName',
    'product_title', 'productTitle', 'mall_goods_name', 'mallGoodsName', 'name', 'title'
  ]);
  const externalId = firstScalar(view, [
    'goods_id', 'goodsId', 'product_id', 'productId', 'spu_id', 'spuId', 'goods_sn', 'goodsSn',
    'sku_id', 'skuId', 'sku_code', 'skuCode', 'skc_id', 'skcId', 'skc', 'sku'
  ]);
  const priceValue = firstValue(view, [
    'salePrice', 'sale_price', 'salePriceInfo', 'sale_price_info', 'discountPrice', 'discount_price',
    'productPromotionPrice', 'promotionPrice', 'unit_price', 'unitPrice', 'price', 'priceInfo', 'price_info',
    'retailPrice', 'retail_price', 'retailPriceInfo', 'mallPrice', 'mall_price', 'amount'
  ]);
  const usdPrice = findUsdAmount(priceValue);
  const explicitCurrency = findCurrency(priceValue, '').toUpperCase();
  const localPrice = findAmount(priceValue);
  const price = usdPrice > 0 ? usdPrice : localPrice;
  const priceCurrency = usdPrice > 0 ? 'USD' : (explicitCurrency || fallbackCurrency || 'USD').toUpperCase();
  if ((!name && !externalId) || price <= 0) return null;

  const pathText = pathParts.join('.').toLowerCase();
  const trustedShareEndpoint = pathText.includes('cart_share_landing');
  const quantityKeys = [
    'quantity', 'qty', 'goods_num', 'goodsNum', 'goods_quantity', 'goodsQuantity', 'cart_quantity', 'cartQuantity',
    'product_num', 'productNum', 'sku_num', 'skuNum', 'selected_num', 'selectedNum', 'buy_num', 'buyNum',
    ...(trustedShareEndpoint ? ['num', 'count', 'item_num', 'itemNum', 'purchase_num', 'purchaseNum'] : [])
  ];
  const qtyRaw = firstScalar(view, quantityKeys, '');
  const hasQty = qtyRaw !== '';
  const cartContext = strongCartContext(pathText) || trustedShareEndpoint;

  // A SHEIN cart page contains recommendation feeds, styles, banners and product
  // carousels. Only accept nodes that are actually inside a cart/share branch.
  // Generic `count`/`num` fields are deliberately NOT treated as quantity.
  if (excludedProductContext(pathText)) return null;
  if (!cartContext) return null;
  if (!hasQty && !/(cartshare|cart_share|sharecart|share_cart|cartitems|cart_items|cartgoods|cart_goods|selectedgoods|selected_goods|selecteditems|selected_items)/i.test(pathText)) return null;
  if (looksLikeMarkupNoise(name) || looksLikeMarkupNoise(externalId)) return null;

  const attrs = variantAttrs(view);
  const goodsAttr = parseSheinGoodsAttr(firstScalar(view, ['goodsAttr', 'goods_attr']));
  const color = firstScalar(view, ['color', 'color_name', 'colorName', 'goods_color', 'goodsColor']) || attrs.color || goodsAttr.color;
  const size = firstScalar(view, ['size', 'size_name', 'sizeName', 'goods_size', 'goodsSize']) || attrs.size || goodsAttr.size;
  const variant = firstScalar(view, ['variant', 'sku_name', 'skuName', 'attr_value', 'attrValue', 'skc_name', 'skcName', 'sku_code', 'skuCode', 'sku_id', 'skuId']) || attrs.variant;
  const imageValue = firstValue(view, ['goods_img', 'goodsImg', 'goods_image', 'goodsImage', 'product_img', 'productImage', 'image_url', 'imageUrl', 'image', 'thumbnail', 'thumb', 'main_image', 'mainImage', 'images']);
  const image = imageFrom(imageValue);
  let productUrl = firstScalar(view, ['product_url', 'productUrl', 'goods_url', 'goodsUrl', 'url', 'detail_url', 'detailUrl', 'goodsLink', 'productLink', 'link']);
  if (!productUrl && externalId) productUrl = `/ar/p-${externalId}.html`;

  if (!externalId && !image && !productUrl) return null;
  if (looksLikeMarkupNoise(productUrl) || looksLikeMarkupNoise(image)) return null;

  return {
    external_id: externalId,
    name,
    product_url: absUrl(productUrl, baseUrl),
    image_url: absUrl(image, baseUrl),
    variant,
    color,
    size,
    quantity: Math.max(1, Number(qtyRaw || 1) || 1),
    unit_price_original: price,
    currency: priceCurrency,
  };
}

function extractItemsFromPayload(payload, baseUrl, fallbackCurrency, rootContext = []) {
  const items = [];
  const seen = new WeakSet();
  const walk = (node, pathParts = [], depth = 0) => {
    if (depth > 18 || !node || typeof node !== 'object') return;
    if (seen.has(node)) return;
    seen.add(node);
    if (!Array.isArray(node)) {
      const candidate = candidateFromNode(node, pathParts, baseUrl, fallbackCurrency);
      if (candidate) items.push(candidate);
    }
    if (items.length >= MAX_ITEMS) return;
    if (Array.isArray(node)) {
      for (let i = 0; i < node.length; i++) walk(node[i], pathParts, depth + 1);
    } else {
      for (const [key, child] of Object.entries(node)) {
        if (child && typeof child === 'object') {
          walk(child, [...pathParts, key], depth + 1);
          continue;
        }
        // Some SHEIN BFF responses embed subtrees as JSON strings. Only parse
        // bounded JSON-looking values so normal labels / HTML are ignored.
        if (typeof child === 'string' && child.length >= 2 && child.length <= 500_000 && /^[\s]*[\[{]/.test(child)) {
          try {
            const parsed = JSON.parse(child);
            if (parsed && typeof parsed === 'object') walk(parsed, [...pathParts, key, 'json_string'], depth + 1);
          } catch {}
        }
      }
    }
  };
  walk(payload, rootContext);
  return items;
}

function normalizeItem(raw, fallbackCurrency) {
  if (!raw || typeof raw !== 'object') return null;
  const quantity = Math.max(1, Number(raw.quantity ?? raw.qty ?? raw.cart_quantity ?? raw.goods_num ?? 1) || 1);
  const price = findAmount(raw.unit_price_original ?? raw.price ?? raw.sale_price ?? raw.salePrice ?? 0);
  const name = String(raw.name ?? raw.goods_name ?? raw.title ?? '').trim();
  const productUrl = String(raw.product_url ?? raw.url ?? '').trim();
  const imageUrl = String(raw.image_url ?? raw.image ?? raw.goods_img ?? '').trim();
  const id = String(raw.external_id ?? raw.goods_id ?? raw.sku ?? '').trim();
  if ((!name && !id && !productUrl && !imageUrl) || price <= 0) return null;
  return {
    external_id: id,
    name,
    product_url: productUrl,
    image_url: imageUrl,
    variant: String(raw.variant ?? raw.sku_code ?? '').trim(),
    color: String(raw.color ?? '').trim(),
    size: String(raw.size ?? '').trim(),
    quantity,
    unit_price_original: price,
    currency: String(raw.currency ?? fallbackCurrency).toUpperCase(),
  };
}

function dedupe(items) {
  const seen = new Map();
  for (const item of items) {
    const normalized = normalizeItem(item, item.currency || 'USD');
    if (!normalized) continue;
    const key = normalized.external_id ? `${normalized.external_id}|${normalized.variant}|${normalized.color}|${normalized.size}` : (normalized.product_url || `${normalized.name}|${normalized.variant}|${normalized.color}|${normalized.size}|${normalized.unit_price_original}`);
    if (!key) continue;
    if (!seen.has(key)) {
      seen.set(key, normalized);
      continue;
    }
    const existing = seen.get(key);
    // Prefer the richer duplicate (name/image/variant) and preserve real cart quantity.
    const score = x => [x.name, x.image_url, x.product_url, x.variant, x.color, x.size].filter(Boolean).length;
    const richer = score(normalized) > score(existing) ? normalized : existing;
    richer.quantity = Math.max(existing.quantity || 1, normalized.quantity || 1);
    seen.set(key, richer);
  }
  return [...seen.values()];
}

async function readInput() {
  let raw = '';
  for await (const chunk of process.stdin) raw += chunk;
  try { return JSON.parse(raw || '{}'); } catch { return {}; }
}

const input = await readInput();
const targetUrl = String(input.url || '');
if (!allowedMainUrl(targetUrl)) {
  output({ ok: false, status: 'invalid_url', message: 'Only HTTPS SHEIN URLs are accepted.', items: [], payloads: [] }, 2);
} else {
  const timeoutMs = Math.min(90_000, Math.max(5_000, Number(input.timeoutMs || 35_000)));
  const headless = input.headless !== false;
  const manualChallengeWaitMs = Math.min(120_000, Math.max(0, Number(input.manualChallengeWaitMs || 0)));
  const debug = input.debug === true;
  const profileDir = path.resolve(String(input.profileDir || path.join(process.cwd(), 'storage/app/shein-browser-profile')));
  fs.mkdirSync(profileDir, { recursive: true });

  let context;
  let page;
  const payloads = [];
  const payloadKeys = new Set();
  const responseMeta = [];
  const networkItems = [];
  const fallbackCurrency = currencyFromUrl(targetUrl);

  try {
    context = await chromium.launchPersistentContext(profileDir, {
      headless,
      locale: 'ar-AE',
      viewport: { width: 1440, height: 1000 },
      userAgent: 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',
      args: ['--disable-dev-shm-usage'],
    });
    page = context.pages()[0] || await context.newPage();
    page.setDefaultTimeout(Math.min(timeoutMs, 20_000));

    await page.route('**/*', async route => {
      try {
        const u = new URL(route.request().url());
        if (!['http:', 'https:'].includes(u.protocol) || isPrivateLiteralHost(u.hostname)) return route.abort();
        if (route.request().isNavigationRequest() && route.request().frame() === page.mainFrame() && !allowedMainUrl(u.href)) return route.abort();
        return route.continue();
      } catch {
        return route.abort();
      }
    });

    page.on('response', async response => {
      try {
        const url = response.url();
        if (!isSheinUrl(url)) return;
        const contentType = String(response.headers()['content-type'] || '').toLowerCase();
        if (!contentType.includes('json') && !contentType.includes('text')) return;
        const text = await response.text();
        if (!text || text.length > MAX_PAYLOAD_BYTES) return;
        let decoded;
        try { decoded = JSON.parse(text); } catch { return; }
        if (!decoded || typeof decoded !== 'object') return;

        const explicitCartEndpoint = /(cart[^a-z0-9]*(?:share|list|items|goods)|(?:share|list|items|goods)[^a-z0-9]*cart|bag[^a-z0-9]*(?:list|items)|basket|checkout)/i.test(url);
        const isShareLandingEndpoint = /\/bff-api\/order\/cart\/share\/landing(?:\?|$)/i.test(url);
        const rootContext = isShareLandingEndpoint ? ['cart_response', 'cart_share_landing'] : (explicitCartEndpoint ? ['cart_response'] : []);
        const found = extractItemsFromPayload(decoded, targetUrl, fallbackCurrency, rootContext);
        if (found.length) networkItems.push(...found);

        const looksRelevant = found.length > 0 || explicitCartEndpoint || isShareLandingEndpoint || /cartshare|cart_share|sharecart|share_cart|cart_list|cart_items|cart_goods/i.test(text.slice(0, 500_000));
        if (!looksRelevant || payloads.length >= MAX_PAYLOADS) return;
        const key = `${url}|${text.slice(0, 220)}`;
        if (payloadKeys.has(key)) return;
        payloadKeys.add(key);
        // Always retain the exact shared-cart landing payload while debugging,
        // even when zero items matched. This is the authoritative response we
        // need to adapt to if SHEIN changes its schema.
        if (debug || isShareLandingEndpoint || found.length) payloads.push(decoded);
        const request = response.request();
        const topKeys = decoded && typeof decoded === 'object' && !Array.isArray(decoded) ? Object.keys(decoded).slice(0, 30) : [];
        responseMeta.push({
          url,
          status: response.status(),
          method: request.method(),
          post_data: isShareLandingEndpoint ? String(request.postData() || '').slice(0, 4000) : '',
          content_type: contentType,
          body_bytes: text.length,
          top_keys: topKeys,
          matched_items: found.length,
          is_share_landing: isShareLandingEndpoint,
        });
      } catch {
        // Streaming/binary/cancelled responses are expected on modern storefronts.
      }
    });

    const navigation = await page.goto(targetUrl, { waitUntil: 'domcontentloaded', timeout: timeoutMs });
    try { await page.waitForLoadState('networkidle', { timeout: Math.min(15_000, timeoutMs) }); } catch {}
    try { await page.waitForTimeout(2500); } catch {}

    let bodyText = '';
    try { bodyText = await page.locator('body').innerText({ timeout: 5_000 }); } catch {}
    let challenged = looksLikeChallenge(`${await page.title().catch(() => '')}\n${bodyText}`);

    if (challenged && !headless && manualChallengeWaitMs > 0) {
      const deadline = Date.now() + manualChallengeWaitMs;
      while (Date.now() < deadline) {
        await page.waitForTimeout(1500);
        try { bodyText = await page.locator('body').innerText({ timeout: 3_000 }); } catch {}
        challenged = looksLikeChallenge(`${await page.title().catch(() => '')}\n${bodyText}`);
        if (!challenged) {
          try { await page.waitForLoadState('networkidle', { timeout: 8_000 }); } catch {}
          try { await page.waitForTimeout(1800); } catch {}
          break;
        }
      }
    }

    // Ask the same shared-cart BFF endpoint explicitly from inside the live SHEIN
    // browser session. The storefront does not always hydrate/fire this request on its
    // own anymore, so waiting only for page network traffic can incorrectly return 0 items.
    const shareRequest = shareApiRequestFromUrl(targetUrl);
    let directBffStatus = null;
    let directBffMatchedItems = 0;
    if (shareRequest) {
      const direct = await page.evaluate(async (request) => {
        try {
          const response = await fetch(request.endpoint, {
            method: 'POST',
            credentials: 'include',
            headers: {
              'accept': 'application/json, text/plain, */*',
              'content-type': 'application/json;charset=UTF-8',
            },
            body: JSON.stringify(request.body),
          });
          return {
            status: response.status,
            contentType: response.headers.get('content-type') || '',
            text: await response.text(),
          };
        } catch (error) {
          return { status: 0, contentType: '', text: '', error: String(error?.message || error || '') };
        }
      }, shareRequest).catch(() => ({ status: 0, contentType: '', text: '' }));

      directBffStatus = Number(direct?.status || 0) || null;
      const directText = String(direct?.text || '');
      if (directText && directText.length <= MAX_PAYLOAD_BYTES) {
        try {
          const decoded = JSON.parse(directText);
          if (decoded && typeof decoded === 'object') {
            const found = extractItemsFromPayload(
              decoded,
              targetUrl,
              fallbackCurrency,
              ['cart_response', 'cart_share_landing'],
            );
            directBffMatchedItems = found.length;
            if (found.length) networkItems.push(...found);
            if ((debug || found.length) && payloads.length < MAX_PAYLOADS) payloads.push(decoded);
            responseMeta.push({
              url: shareRequest.endpoint,
              status: directBffStatus,
              method: 'POST',
              post_data: JSON.stringify(shareRequest.body),
              content_type: String(direct?.contentType || ''),
              body_bytes: directText.length,
              top_keys: !Array.isArray(decoded) ? Object.keys(decoded).slice(0, 30) : [],
              matched_items: found.length,
              is_share_landing: true,
              direct_bff: true,
            });
          }
        } catch {}
      }
    }

    // Pull hydrated state from common global objects. We serialize inside the page to avoid handles/cycles.
    const statePayloads = await page.evaluate(() => {
      const results = [];
      const candidates = ['__INITIAL_STATE__', '__NEXT_DATA__', '__NUXT__', '__APOLLO_STATE__', '__PRELOADED_STATE__', 'gbProductDetail', 'productIntroData', 'cartData', 'cartShareData'];
      for (const key of candidates) {
        try {
          const value = window[key];
          if (value && typeof value === 'object') {
            const raw = JSON.stringify(value);
            if (raw && raw.length <= 2_500_000) results.push(JSON.parse(raw));
          }
        } catch {}
      }
      // Also inspect JSON script tags after hydration.
      for (const script of document.querySelectorAll('script[type="application/json"], script#__NEXT_DATA__')) {
        try {
          const raw = script.textContent || '';
          if (raw.length > 20 && raw.length <= 2_500_000) results.push(JSON.parse(raw));
        } catch {}
      }
      return results.slice(0, 16);
    }).catch(() => []);

    const stateItems = [];
    for (const p of statePayloads) {
      const found = extractItemsFromPayload(p, targetUrl, fallbackCurrency);
      if (found.length) stateItems.push(...found);
      if (payloads.length < MAX_PAYLOADS && found.length) payloads.push(p);
    }

    const domItems = await page.evaluate((fallbackCurrency) => {
      const abs = value => {
        if (!value) return '';
        try { if (String(value).startsWith('//')) return `https:${value}`; return new URL(value, location.href).href; } catch { return String(value); }
      };
      const parsePrice = (text, fallback = '') => {
        const clean = String(text || '').replace(/,/g, ' ');
        const withCurrency = [
          /(?:USD|\$)\s*([0-9]+(?:\.[0-9]{1,4})?)/i,
          /([0-9]+(?:\.[0-9]{1,4})?)\s*(?:USD|\$)/i,
        ];
        for (const re of withCurrency) {
          const m = clean.match(re);
          const n = Number(m?.[1] || 0);
          if (Number.isFinite(n) && n > 0) return n;
        }
        return 0;
      };
      const attrValue = (text, labels) => {
        const lines = String(text || '').split(/\n|\||·/).map(x => x.trim()).filter(Boolean);
        for (const line of lines) for (const label of labels) {
          const safe = label.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
          const m = line.match(new RegExp(`^${safe}\\s*[:：-]?\\s*(.+)$`, 'i'));
          if (m) return m[1].trim();
        }
        return '';
      };
      const excludedContext = /(recommend|suggest|similar|related|guess|you-may-like|you_may_like|hot|feed|search|history|recent|viewed|wishlist|favorite|favourite|trend|flash|campaign|marketing)/i;
      const cartContainerSelector = '[class*="cart"], [id*="cart"], [data-testid*="cart"], [data-module*="cart"], [class*="bag"], [id*="bag"], [data-testid*="bag"]';
      const rowSelector = '[data-goods-id], [data-product-id], [data-sku-id], [class*="cart-item"], [class*="cartItem"], [class*="bag-item"], [class*="bagItem"], [class*="item"]';
      const containers = [...document.querySelectorAll(cartContainerSelector)].filter(el => !excludedContext.test(`${el.id || ''} ${el.className || ''} ${el.getAttribute?.('data-testid') || ''} ${el.getAttribute?.('data-module') || ''}`));
      const cards = [...new Set(containers.flatMap(container => [...container.querySelectorAll(rowSelector)]))];
      const items = [];
      for (const card of cards) {
        const lineage = [];
        let el = card;
        for (let i = 0; el && i < 8; i++, el = el.parentElement) lineage.push(`${el.id || ''} ${el.className || ''} ${el.getAttribute?.('data-testid') || ''} ${el.getAttribute?.('data-module') || ''}`);
        const contextText = lineage.join(' ');
        if (excludedContext.test(contextText) || !/(cart|bag|basket)/i.test(contextText)) continue;

        const text = (card.innerText || '').trim();
        if (!text || text.length > 3000) continue;
        const qtyInput = card.querySelector('input[type="number"], input[class*="qty"], input[class*="quantity"]');
        const qtyTextMatch = text.match(/(?:qty|quantity|الكمية)\s*[:：]?\s*(\d{1,3})/i) || text.match(/(?:^|\s)[×x]\s*(\d{1,3})(?:\s|$)/i);
        const quantity = Number(qtyInput?.value || qtyTextMatch?.[1] || 0);
        if (!Number.isFinite(quantity) || quantity < 1) continue;

        const link = card.querySelector('a[href*="-p-"], a[href*="/product"], a[href*="goods"]');
        const href = abs(link?.getAttribute('href') || '');
        const img = card.querySelector('img');
        const image = abs(img?.currentSrc || img?.getAttribute('src') || img?.getAttribute('data-src') || img?.getAttribute('data-original') || '');
        const nameNode = card.querySelector('[class*="name"], [class*="title"], [data-testid*="name"]');
        const name = (link?.getAttribute('aria-label') || link?.getAttribute('title') || nameNode?.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 500);
        const id = card.getAttribute('data-goods-id') || card.getAttribute('data-product-id') || card.getAttribute('data-sku-id') || href.match(/(?:-p-|goods[_/-]?)(\d{5,})/i)?.[1] || '';
        const priceNode = card.querySelector('[class*="price"], [data-testid*="price"], [data-price]');
        const price = parsePrice(priceNode?.textContent || '', priceNode?.getAttribute?.('data-price') || '');
        if ((!name && !id) || (!image && !href) || price <= 0) continue;
        const color = attrValue(text, ['Color', 'Colour', 'اللون']);
        const size = attrValue(text, ['Size', 'المقاس']);
        items.push({ external_id: id, name, product_url: href, image_url: image, variant: '', color, size, quantity, unit_price_original: price, currency: fallbackCurrency });
      }
      return items;
    }, fallbackCurrency).catch(() => []);

    let html = '';
    try { html = await page.content(); } catch {}
    if (html.length > MAX_HTML_BYTES) html = html.slice(0, MAX_HTML_BYTES);

    const items = dedupe([
      ...networkItems,
      ...stateItems,
      ...(Array.isArray(domItems) ? domItems : []),
    ]).slice(0, MAX_ITEMS);
    const finalUrl = page.url();
    const statusCode = navigation?.status?.() || null;

    output({
      ok: !challenged,
      status: challenged ? 'challenge' : 'loaded',
      message: challenged ? 'SHEIN verification/challenge is visible in Chromium.' : 'SHEIN page rendered in Chromium.',
      final_url: finalUrl,
      http_status: statusCode,
      title: await page.title().catch(() => ''),
      challenged,
      items,
      payloads: debug ? payloads : [],
      response_meta: responseMeta.slice(0, 40),
      html: debug ? html : '',
      meta: {
        headless,
        payload_count: payloads.length,
        network_item_count: dedupe(networkItems).length,
        state_item_count: dedupe(stateItems).length,
        dom_item_count: dedupe(domItems || []).length,
        final_item_count: items.length,
        direct_bff_status: directBffStatus,
        direct_bff_matched_items: directBffMatchedItems,
      }
    });
  } catch (error) {
    const message = String(error?.message || error || 'Unknown Playwright error');
    const missingBrowser = /Executable doesn't exist|browserType\.launch|playwright install/i.test(message);
    output({
      ok: false,
      status: missingBrowser ? 'unavailable' : 'failed',
      message,
      items: [],
      payloads: [],
      meta: { headless }
    }, 1);
  } finally {
    if (context) await context.close().catch(() => {});
  }
}
