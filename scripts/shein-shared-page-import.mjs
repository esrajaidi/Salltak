import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { chromium } from 'playwright-chromium';

const MAX_RESPONSE_BYTES = 2_500_000;
const MAX_ITEMS = 60;

function out(payload, code = 0) {
  process.stdout.write(JSON.stringify(payload));
  process.exitCode = code;
}

function allowed(raw) {
  try {
    const u = new URL(raw);
    const h = u.hostname.toLowerCase();
    return u.protocol === 'https:' && (h === 'shein.com' || h.endsWith('.shein.com'));
  } catch {
    return false;
  }
}

function sheinUrl(raw) {
  try {
    const u = new URL(raw);
    const h = u.hostname.toLowerCase();
    return h === 'shein.com' || h.endsWith('.shein.com');
  } catch {
    return false;
  }
}

function scalar(value) {
  if (typeof value === 'number') return Number.isFinite(value) ? value : 0;
  if (typeof value !== 'string') return 0;
  const match = value.replace(/,/g, '').match(/([0-9]+(?:\.[0-9]{1,4})?)/);
  const n = Number(match?.[1] || 0);
  return Number.isFinite(n) && n > 0 ? n : 0;
}

function normalizeName(value) {
  return String(value || '')
    .normalize('NFKC')
    .toLowerCase()
    .replace(/[\u200b-\u200f\u202a-\u202e]/g, '')
    .replace(/[^\p{L}\p{N}]+/gu, ' ')
    .trim()
    .replace(/\s+/g, ' ');
}

function normalizeImageKey(value) {
  try {
    const u = new URL(String(value || ''), 'https://m.shein.com/');
    return u.pathname.split('/').filter(Boolean).pop()?.toLowerCase() || '';
  } catch {
    return '';
  }
}

function isLikelyImageUrl(value) {
  const raw = String(value || '').trim();
  if (!raw) return false;
  try {
    const u = new URL(raw, 'https://m.shein.com/');
    const host = u.hostname.toLowerCase();
    const pathname = u.pathname.toLowerCase();
    if (/placeholder|default[-_]?image|no[-_]?image|transparent|spacer|blank|logo|icon|avatar|badge/.test(pathname)) return false;
    if (host.endsWith('ltwebstatic.com')) return true;
    return /\.(?:jpe?g|png|webp|gif|avif)(?:$|[/?#])/i.test(`${pathname}${u.search}`);
  } catch {
    return false;
  }
}

function explicitUsd(value, depth = 0) {
  if (depth > 6 || !value || typeof value !== 'object') return 0;
  if (!Array.isArray(value)) {
    for (const key of ['usdAmount', 'usd_amount', 'usdPrice', 'usd_price']) {
      if (Object.prototype.hasOwnProperty.call(value, key)) {
        const amount = scalar(value[key]);
        if (amount > 0) return amount;
      }
    }
  }
  for (const child of Object.values(value)) {
    if (child && typeof child === 'object') {
      const amount = explicitUsd(child, depth + 1);
      if (amount > 0) return amount;
    }
  }
  return 0;
}

function objectIds(node) {
  if (!node || typeof node !== 'object' || Array.isArray(node)) return [];
  const ids = [];
  for (const key of ['goods_id', 'goodsId', 'product_id', 'productId', 'spu_id', 'spuId', 'sku_id', 'skuId', 'skc_id', 'skcId', 'goods_sn', 'goodsSn', 'sku']) {
    const value = node[key];
    if (typeof value === 'string' || typeof value === 'number') {
      const id = String(value).trim();
      if (id) ids.push(id);
    }
  }
  return [...new Set(ids)];
}

function objectName(node) {
  if (!node || typeof node !== 'object' || Array.isArray(node)) return '';
  for (const key of ['goods_name', 'goodsName', 'product_name', 'productName', 'goods_title', 'goodsTitle', 'product_title', 'productTitle', 'mall_goods_name', 'mallGoodsName', 'name', 'title']) {
    const value = node[key];
    if (typeof value === 'string' && value.trim().length > 2 && value.trim().length < 600) return value.trim();
  }
  return '';
}

function objectImage(value, depth = 0) {
  if (depth > 4 || value === null || value === undefined) return '';
  if (typeof value === 'string') {
    const s = value.trim();
    return isLikelyImageUrl(s) ? s : '';
  }
  if (Array.isArray(value)) {
    for (const child of value) {
      const image = objectImage(child, depth + 1);
      if (image) return image;
    }
    return '';
  }
  if (typeof value === 'object') {
    for (const key of ['goods_img', 'goodsImg', 'goods_image', 'goodsImage', 'product_img', 'productImage', 'image_url', 'imageUrl', 'image', 'thumbnail', 'thumb', 'main_image', 'mainImage']) {
      if (!Object.prototype.hasOwnProperty.call(value, key)) continue;
      const image = objectImage(value[key], depth + 1);
      if (image) return image;
    }
  }
  return '';
}

function collectNetworkUsd(node, maps, trusted = false, depth = 0, seen = new WeakSet()) {
  if (depth > 16 || !node || typeof node !== 'object' || seen.has(node)) return;
  seen.add(node);

  if (!Array.isArray(node)) {
    const ids = objectIds(node);
    const name = objectName(node);
    const image = objectImage(node);
    const amount = explicitUsd(node);

    if (image) {
      for (const id of ids) if (!maps.networkImageById.has(id)) maps.networkImageById.set(id, image);
    }

    if (amount > 0) {
      for (const id of ids) if (!maps.networkUsdById.has(id)) maps.networkUsdById.set(id, amount);

      const nameKey = normalizeName(name);
      if (nameKey && !maps.networkUsdByName.has(nameKey)) maps.networkUsdByName.set(nameKey, amount);

      const imageKey = normalizeImageKey(image);
      if (imageKey && !maps.networkUsdByImage.has(imageKey)) maps.networkUsdByImage.set(imageKey, amount);

      if ((ids.length || nameKey) && (name || image)) {
        const key = ids[0] || nameKey || imageKey;
        if (key && !maps.networkProducts.has(key)) {
          maps.networkProducts.set(key, {
            external_id: ids[0] || '',
            lookup_ids: ids,
            name,
            image_url: image,
            unit_price_original: amount,
            trusted,
          });
        } else if (key && trusted && maps.networkProducts.has(key)) {
          maps.networkProducts.get(key).trusted = true;
        }
      }
    }
  }

  for (const child of Object.values(node)) {
    if (child && typeof child === 'object') collectNetworkUsd(child, maps, trusted, depth + 1, seen);
  }
}

function fuzzyNamePrice(name, map) {
  const key = normalizeName(name);
  if (!key) return 0;
  const exact = Number(map.get(key) || 0) || 0;
  if (exact > 0) return exact;
  if (key.length < 12) return 0;

  for (const [candidate, price] of map.entries()) {
    if (candidate.length < 12) continue;
    if (candidate.includes(key) || key.includes(candidate)) return Number(price || 0) || 0;
  }
  return 0;
}

async function input() {
  let raw = '';
  for await (const chunk of process.stdin) raw += chunk;
  try { return JSON.parse(raw || '{}'); } catch { return {}; }
}

const cfg = await input();
const targetUrl = String(cfg.url || '');
if (!allowed(targetUrl)) {
  out({ ok:false, status:'invalid_url', items:[], payloads:[] }, 2);
} else {
  const timeoutMs = Math.min(90_000, Math.max(5_000, Number(cfg.timeoutMs || 35_000)));
  const profileDir = path.resolve(String(cfg.profileDir || path.join(process.cwd(), 'storage/app/shein-shared-page-profile')));
  fs.mkdirSync(profileDir, { recursive:true });

  let context;
  try {
    context = await chromium.launchPersistentContext(profileDir, {
      headless: cfg.headless !== false,
      locale: String(cfg.locale || 'en-AE'),
      viewport: { width: Number(cfg.viewport?.width || 430), height: Number(cfg.viewport?.height || 932) },
      userAgent: String(cfg.userAgent || 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 Version/18.7 Mobile/15E148 Safari/604.1'),
      args: ['--disable-dev-shm-usage'],
    });

    const page = context.pages()[0] || await context.newPage();
    const maps = {
      networkUsdById: new Map(),
      networkUsdByName: new Map(),
      networkUsdByImage: new Map(),
      networkImageById: new Map(),
      networkProducts: new Map(),
    };
    const responseTasks = [];
    let inspectedResponseCount = 0;

    page.on('response', response => {
      const task = (async () => {
        try {
          const responseUrl = response.url();
          if (!sheinUrl(responseUrl)) return;
          const contentType = String(response.headers()['content-type'] || '').toLowerCase();
          if (!contentType.includes('json') && !contentType.includes('text')) return;
          const text = await response.text();
          if (!text || text.length > MAX_RESPONSE_BYTES) return;
          let decoded;
          try { decoded = JSON.parse(text); } catch { return; }
          if (!decoded || typeof decoded !== 'object') return;
          inspectedResponseCount++;
          const trusted = /(cart|share|landing|bag|basket|checkout)/i.test(responseUrl);
          collectNetworkUsd(decoded, maps, trusted);
        } catch {}
      })();
      responseTasks.push(task);
    });

    await page.goto(targetUrl, { waitUntil:'domcontentloaded', timeout:timeoutMs });
    try { await page.waitForLoadState('networkidle', { timeout:Math.min(12_000, timeoutMs) }); } catch {}
    await page.waitForTimeout(2600);
    await Promise.allSettled(responseTasks);

    const bodyText = await page.locator('body').innerText().catch(() => '');
    const finalUrl = page.url();

    const domSnapshot = await page.evaluate(() => {
      const abs = value => {
        try { return new URL(String(value || ''), location.href).href; } catch { return String(value || ''); }
      };
      const normalizeName = value => String(value || '')
        .normalize('NFKC')
        .toLowerCase()
        .replace(/[\u200b-\u200f\u202a-\u202e]/g, '')
        .replace(/[^\p{L}\p{N}]+/gu, ' ')
        .trim()
        .replace(/\s+/g, ' ');
      const cleanVisibleProductName = value => String(value || '')
        .replace(/<[^>]*>/g, ' ')
        .replace(/&nbsp;|&#160;/gi, ' ')
        .replace(/\s+/g, ' ')
        .trim()
        .slice(0, 500);
      const imageKey = value => {
        try {
          const u = new URL(String(value || ''), location.href);
          return u.pathname.split('/').filter(Boolean).pop()?.toLowerCase() || '';
        } catch { return ''; }
      };
      const isLikelyImageUrl = value => {
        try {
          const u = new URL(String(value || ''), location.href);
          const host = u.hostname.toLowerCase();
          const pathname = u.pathname.toLowerCase();
          if (/placeholder|default[-_]?image|no[-_]?image|transparent|spacer|blank|logo|icon|avatar|badge/.test(pathname)) return false;
          if (host.endsWith('ltwebstatic.com')) return true;
          return /\.(?:jpe?g|png|webp|gif|avif)(?:$|[/?#])/i.test(`${pathname}${u.search}`);
        } catch { return false; }
      };
      const usdFromText = text => {
        const raw = String(text || '').replace(/,/g, '');
        const match = raw.match(/(?:USD|US\$|\$)\s*([0-9]+(?:\.[0-9]{1,4})?)/i)
          || raw.match(/([0-9]+(?:\.[0-9]{1,4})?)\s*(?:USD|US\$)/i);
        const value = Number(match?.[1] || 0);
        return Number.isFinite(value) && value > 0 ? value : 0;
      };
      const visibleUsdPriceFromRoot = root => {
        const nodes = [...root.querySelectorAll('[class*="price"], [class*="sale"], [class*="amount"], [data-testid*="price"], span, strong, b')];
        const candidates = [];
        for (const node of nodes) {
          const text = String(node.textContent || '').trim();
          if (!text || text.length > 80) continue;
          const price = usdFromText(text);
          if (!(price > 0)) continue;
          const style = getComputedStyle(node);
          const lineage = [];
          let p = node;
          for (let i = 0; p && i < 3; i++, p = p.parentElement) lineage.push(`${p.tagName || ''} ${p.className || ''}`);
          if (/line-through/i.test(style.textDecorationLine || style.textDecoration || '') || /\b(?:DEL|S)\b|origin|original|retail|market|old|cross|was-price/i.test(lineage.join(' '))) continue;
          const cls = `${node.className || ''} ${node.parentElement?.className || ''}`;
          let score = text.length;
          if (/sale|discount|current|final|special|price-now/i.test(cls)) score -= 50;
          if (node.children.length === 0) score -= 10;
          candidates.push({ price, score });
        }
        candidates.sort((a, b) => a.score - b.score);
        if (candidates[0]?.price > 0) return candidates[0].price;
        const lines = String(root.innerText || '').split(/\n+/).map(x => x.trim()).filter(Boolean);
        for (const line of lines) {
          const price = usdFromText(line);
          if (price > 0) return price;
        }
        return 0;
      };
      const isGenericSharedProductName = value => {
        const name = normalizeName(value);
        return !name
          || name === 'shein'
          || name === 'منتج shein'
          || name === 'منتج شي ان'
          || /^(items shared by|shared items|add all to cart|cart|share my cart)/i.test(name)
          || /^(العناصر التي تمت مشاركتها|العناصر المشتركة|إضافة الكل|اضافة الكل)/i.test(name);
      };
      const hasVisiblePriceEvidence = text => {
        const raw = String(text || '').replace(/,/g, '');
        return /(?:USD|US\$|\$|AED|SAR|د\.إ|ر\.س|SR)\s*[0-9]+(?:\.[0-9]{1,4})?/i.test(raw)
          || /[0-9]+(?:\.[0-9]{1,4})?\s*(?:USD|US\$|AED|SAR|د\.إ|ر\.س|SR)/i.test(raw);
      };
      const productEvidence = root => {
        if (!root?.querySelector) return false;
        const image = root.querySelector('img');
        if (!image) return false;
        const text = String(root.innerText || '').trim();
        return text.length >= 6 && text.length <= 1800 && hasVisiblePriceEvidence(text);
      };
      const pruneNestedProductRoots = roots => {
        const unique = [...new Set(roots)].filter(Boolean);
        return unique.filter(root => !unique.some(other => other !== root && root.contains(other) && productEvidence(other)));
      };
      const canonicalProductKey = product => {
        const img = imageKey(product.image_url);
        if (img) return `img:${img}`;
        const id = String(product.external_id || '').trim();
        if (id) return `id:${id}`;
        try {
          const u = new URL(String(product.product_url || ''), location.href);
          if (u.pathname) return `url:${u.pathname.toLowerCase()}`;
        } catch {}
        const name = normalizeName(product.name);
        return name ? `name:${name}` : '';
      };
      const excluded = /(recommend|suggest|similar|related|guess|you.?may.?like|wishlist|favorite|favourite|recent|viewed|history|search|trend|campaign|marketing)/i;
      const pricePattern = /(?:USD|US\$|\$)\s*[0-9]|[0-9]+(?:\.[0-9]+)?\s*(?:USD|US\$)/i;
      const anyPriceLine = /(?:USD|US\$|\$|AED|SAR|د\.إ|ر\.س|SR)\s*[0-9]|[0-9]+(?:\.[0-9]+)?\s*(?:USD|US\$|AED|SAR|د\.إ|ر\.س|SR)/i;
      const linkRoots = new Set();
      const imageCandidateRoots = new Set();
      const productLinks = [...document.querySelectorAll('a[href*="-p-"], a[href*="/product"], a[href*="goods"]')];

      for (const link of productLinks) {
        let root = link.closest('[data-goods-id], [data-product-id], [data-sku-id], article, li, [class*="goods-item"], [class*="product-item"], [class*="cart-item"]');
        if (!root) {
          let parent = link.parentElement;
          for (let depth = 0; parent && depth < 6; depth++, parent = parent.parentElement) {
            if (productEvidence(parent)) { root = parent; break; }
          }
        }
        if (root && productEvidence(root)) linkRoots.add(root);
      }

      for (const img of document.querySelectorAll('img')) {
        const src = abs(img.currentSrc || img.getAttribute('src') || img.getAttribute('data-src') || img.getAttribute('data-original') || '');
        if (!isLikelyImageUrl(src)) continue;
        let parent = img.parentElement;
        for (let depth = 0; parent && depth < 7; depth++, parent = parent.parentElement) {
          const text = String(parent.innerText || '').trim();
          if (!text || text.length < 8 || text.length > 1800 || !hasVisiblePriceEvidence(text)) continue;
          const substantialImages = [...parent.querySelectorAll('img')].filter(candidate => {
            const candidateSrc = abs(candidate.currentSrc || candidate.getAttribute('src') || candidate.getAttribute('data-src') || candidate.getAttribute('data-original') || '');
            return isLikelyImageUrl(candidateSrc);
          });
          if (substantialImages.length > 2) continue;
          imageCandidateRoots.add(parent);
          break;
        }
      }

      const roots = pruneNestedProductRoots([...imageCandidateRoots]);
      const result = [];
      const seenCanonical = new Set();
      const seenIds = new Set();
      const seenImages = new Set();

      for (const root of roots) {
        const lineage = [];
        let parent = root;
        for (let i = 0; parent && i < 7; i++, parent = parent.parentElement) {
          lineage.push(`${parent.id || ''} ${parent.className || ''} ${parent.getAttribute?.('data-testid') || ''}`);
        }
        if (excluded.test(lineage.join(' '))) continue;

        const rect = root.getBoundingClientRect?.();
        if (rect && rect.width === 0 && rect.height === 0) continue;

        const img = [...root.querySelectorAll('img')].find(candidate => {
          const src = abs(candidate.currentSrc || candidate.getAttribute('src') || candidate.getAttribute('data-src') || candidate.getAttribute('data-original') || '');
          return isLikelyImageUrl(src);
        });
        const image = abs(img?.currentSrc || img?.getAttribute('src') || img?.getAttribute('data-src') || img?.getAttribute('data-original') || '');
        if (!isLikelyImageUrl(image)) continue;

        const link = (root.matches?.('a[href*="-p-"], a[href*="/product"], a[href*="goods"]') ? root : null)
          || root.querySelector('a[href*="-p-"], a[href*="/product"], a[href*="goods"]');
        const href = abs(link?.getAttribute('href') || '');
        const idNodes = [root, ...root.querySelectorAll('[data-goods-id], [data-product-id], [data-sku-id]')];
        const ids = [];
        for (const node of idNodes) {
          for (const attr of ['data-goods-id', 'data-product-id', 'data-sku-id']) {
            const value = node.getAttribute?.(attr);
            if (value) ids.push(String(value));
          }
        }
        const idFromHref = href.match(/(?:-p-|goods[_/-]?)(\d{5,})/i)?.[1] || '';
        if (idFromHref) ids.push(idFromHref);
        const lookupIds = [...new Set(ids.filter(Boolean))];
        const externalId = lookupIds[0] || '';

        const text = String(root.innerText || '').trim();
        if (!text || text.length > 1800) continue;
        const lines = text.split(/\n+/).map(x => x.trim()).filter(Boolean);
        const nameNode = root.querySelector('[class*="goods-name"], [class*="product-name"], [class*="item-name"], [class*="title"], [data-testid*="name"]');
        const ignoredLine = /sold|bought|save|coupon|lowest|eligible|add all|add to cart|%|أضف|إضافة|خصم|اشترى|تم البيع/i;
        const priceLine = /(?:USD|US\$|\$)\s*[0-9]|[0-9]+(?:\.[0-9]+)?\s*(?:USD|US\$)/i;
        const rawName = link?.getAttribute('aria-label')
          || link?.getAttribute('title')
          || nameNode?.textContent
          || lines.find(line => !anyPriceLine.test(line) && !ignoredLine.test(line) && line.length > 4)
          || '';
        const name = cleanVisibleProductName(rawName);
        if (isGenericSharedProductName(name)) continue;

        const variantLine = lines.find(line => /\//.test(line) && !/^https?:/i.test(line) && line.length < 180 && !anyPriceLine.test(line)) || '';
        const parts = variantLine.split('/').map(x => x.trim()).filter(Boolean);
        const color = parts[0] || '';
        const size = parts.length > 1 ? parts.slice(1).join(' / ') : '';
        const visibleUsdPrice = visibleUsdPriceFromRoot(root);

        const product = {
          external_id: externalId,
          lookup_ids: lookupIds,
          name,
          product_url: href,
          image_url: image,
          variant: variantLine,
          color,
          size,
          visible_usd_price: visibleUsdPrice,
        };
        const key = canonicalProductKey(product);
        const imgKey = imageKey(image);
        if (!key || seenCanonical.has(key)) continue;
        if (externalId && seenIds.has(externalId)) continue;
        if (imgKey && seenImages.has(imgKey)) continue;
        seenCanonical.add(key);
        if (externalId) seenIds.add(externalId);
        if (imgKey) seenImages.add(imgKey);
        result.push(product);
        if (result.length >= MAX_ITEMS) break;
      }

      return {
        products: result,
        candidate_root_count: roots.length,
        image_candidate_count: imageCandidateRoots.size,
        product_link_count: productLinks.length,
      };
    }).catch(() => ({ products:[], candidate_root_count:0, image_candidate_count:0, product_link_count:0 }));

    const visibleProducts = Array.isArray(domSnapshot.products) ? domSnapshot.products : [];
    const visibleProductCountBeforeNetworkFallback = visibleProducts.length;
    const urlEvidence = /cart\/share|cart_share|share\/landing|group_id=|[?&]shc=|onelink/i.test(`${targetUrl} ${finalUrl}`);
    const textEvidence = /items shared by|add all to cart|shared items|shared by|مشاركة|السلة|عناصر مشتركة|إضافة الكل|اضافة الكل/i.test(bodyText);
    const sharedPageEvidence = urlEvidence || textEvidence || (/share/i.test(finalUrl) && visibleProducts.length > 0);
    const visibleProductCount = visibleProducts.length;

    if (!sharedPageEvidence) {
      out({
        ok:true,
        status:'not_shared_page',
        final_url:finalUrl,
        items:[],
        payloads:[],
        meta:{
          shared_items_landing:false,
          sharedPageEvidence:false,
          visible_product_count:visibleProductCount,
          candidate_root_count:Number(domSnapshot.candidate_root_count || 0),
          image_candidate_count:Number(domSnapshot.image_candidate_count || 0),
          product_link_count:Number(domSnapshot.product_link_count || 0),
          network_usd_price_count:maps.networkUsdById.size,
          network_product_count:maps.networkProducts.size,
          inspected_response_count:inspectedResponseCount,
        },
      });
    } else {
      const items = [];
      let missingUsdPriceCount = 0;

      for (const product of visibleProducts) {
        let price = Number(product.visible_usd_price || 0) || 0;

        if (!(price > 0)) {
          for (const id of product.lookup_ids || []) {
            const matched = Number(maps.networkUsdById.get(String(id)) || 0) || 0;
            if (matched > 0) {
              price = matched;
              break;
            }
          }
        }

        if (!(price > 0)) price = fuzzyNamePrice(product.name, maps.networkUsdByName);
        if (!(price > 0)) {
          const imageKeyValue = normalizeImageKey(product.image_url);
          price = Number(maps.networkUsdByImage.get(imageKeyValue) || 0) || 0;
        }

        if (!(price > 0)) {
          missingUsdPriceCount++;
          continue;
        }

        let resolvedImage = isLikelyImageUrl(product.image_url) ? product.image_url : '';
        if (!resolvedImage) {
          for (const id of product.lookup_ids || []) {
            const networkImage = maps.networkImageById.get(String(id));
            if (isLikelyImageUrl(networkImage)) {
              resolvedImage = networkImage;
              break;
            }
          }
        }

        items.push({
          external_id: String(product.external_id || ''),
          name: product.name,
          product_url: product.product_url,
          image_url: resolvedImage,
          variant: product.variant,
          color: product.color,
          size: product.size,
          quantity: 1,
          unit_price_original: price,
          currency: 'USD',
        });
      }

      const diagnosticMeta = {
        shared_items_landing:true,
        sharedPageEvidence:true,
        visible_product_count:visibleProductCount,
        visible_product_count_before_network_fallback:visibleProductCountBeforeNetworkFallback,
        final_item_count:items.length,
        dom_item_count:visibleProductCountBeforeNetworkFallback,
        candidate_root_count:Number(domSnapshot.candidate_root_count || 0),
        image_candidate_count:Number(domSnapshot.image_candidate_count || 0),
        product_link_count:Number(domSnapshot.product_link_count || 0),
        missing_usd_price_count:missingUsdPriceCount,
        network_usd_price_count:maps.networkUsdById.size,
        network_name_price_count:maps.networkUsdByName.size,
        network_product_count:maps.networkProducts.size,
        inspected_response_count:inspectedResponseCount,
      };

      if (visibleProductCount === 0) {
        out({
          ok:true,
          status:'shared_page_unreadable',
          message:'رابط مشاركة SHEIN صالح وفتح بنجاح، لكن SHEIN لم يعرّض عناصر القائمة للمتصفح بشكل يمكن قراءته في هذه المحاولة. أعد المحاولة مرة أخرى.',
          final_url:finalUrl,
          items:[],
          payloads:[],
          meta:diagnosticMeta,
        });
      } else if (missingUsdPriceCount > 0) {
        out({
          ok:true,
          status:'missing_usd_prices',
          message:`وجدنا منتجات في رابط SHEIN (${visibleProductCount}) لكن تعذر تأكيد السعر بالدولار لبعضها. لن نعتبر سعر AED/SAR سعرًا بالدولار.`,
          final_url:finalUrl,
          items:[],
          payloads:[],
          meta:diagnosticMeta,
        });
      } else {
        out({
          ok:true,
          status:'loaded',
          final_url:finalUrl,
          items:items.slice(0, MAX_ITEMS),
          payloads:[],
          meta:diagnosticMeta,
        });
      }
    }
  } catch (error) {
    out({ ok:false, status:'failed', message:String(error?.message || error || 'Shared page import failed'), items:[], payloads:[] }, 1);
  } finally {
    if (context) await context.close().catch(() => {});
  }
}
