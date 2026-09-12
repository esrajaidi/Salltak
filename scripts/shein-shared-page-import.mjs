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
    return /^(https?:)?\/\//i.test(s) || s.startsWith('/') ? s : '';
  }
  if (Array.isArray(value)) {
    for (const child of value) {
      const image = objectImage(child, depth + 1);
      if (image) return image;
    }
    return '';
  }
  if (typeof value === 'object') {
    for (const key of ['goods_img', 'goodsImg', 'goods_image', 'goodsImage', 'product_img', 'productImage', 'image_url', 'imageUrl', 'image', 'thumbnail', 'thumb', 'main_image', 'mainImage', 'url', 'src']) {
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
      locale: 'ar-AE',
      viewport: { width: 430, height: 932 },
      userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 Version/18.7 Mobile/15E148 Safari/604.1',
      args: ['--disable-dev-shm-usage'],
    });

    const page = context.pages()[0] || await context.newPage();
    const maps = {
      networkUsdById: new Map(),
      networkUsdByName: new Map(),
      networkUsdByImage: new Map(),
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
      const excluded = /(recommend|suggest|similar|related|guess|you.?may.?like|wishlist|favorite|favourite|recent|viewed|history|search|trend|campaign|marketing)/i;
      const pricePattern = /(?:USD|AED|SAR|KWD|QAR|BHD|OMR|\$|د\.?إ|ر\.?س)\s*[0-9]|[0-9]+(?:\.[0-9]+)?\s*(?:USD|AED|SAR|KWD|QAR|BHD|OMR)/i;
      const linkRoots = new Set();
      const imageCandidateRoots = new Set();
      const productLinks = [...document.querySelectorAll('a[href*="-p-"], a[href*="/product"], a[href*="goods"]')];

      for (const link of productLinks) {
        const root = link.closest('[data-goods-id], [data-product-id], [data-sku-id], article, li, [class*="item"], [class*="goods"], [class*="product"]') || link.parentElement;
        if (root) linkRoots.add(root);
      }

      for (const img of document.querySelectorAll('img')) {
        const src = abs(img.currentSrc || img.getAttribute('src') || img.getAttribute('data-src') || img.getAttribute('data-original') || '');
        if (!src || /logo|icon|avatar|flag|badge/i.test(src)) continue;
        let parent = img.parentElement;
        for (let depth = 0; parent && depth < 7; depth++, parent = parent.parentElement) {
          const text = String(parent.innerText || '').trim();
          if (!text || text.length < 8 || text.length > 2200) continue;
          if (!pricePattern.test(text)) continue;
          imageCandidateRoots.add(parent);
          break;
        }
      }

      const roots = [...new Set([...linkRoots, ...imageCandidateRoots])];
      const result = [];
      const seen = new Set();

      for (const root of roots) {
        const lineage = [];
        let parent = root;
        for (let i = 0; parent && i < 7; i++, parent = parent.parentElement) {
          lineage.push(`${parent.id || ''} ${parent.className || ''} ${parent.getAttribute?.('data-testid') || ''}`);
        }
        if (excluded.test(lineage.join(' '))) continue;

        const rect = root.getBoundingClientRect?.();
        if (rect && rect.width === 0 && rect.height === 0) continue;

        const img = root.querySelector('img');
        const image = abs(img?.currentSrc || img?.getAttribute('src') || img?.getAttribute('data-src') || img?.getAttribute('data-original') || '');
        if (!image || /logo|icon|avatar|flag|badge/i.test(image)) continue;

        const link = root.querySelector('a[href]');
        const href = abs(link?.getAttribute('href') || '');
        const idFromHref = href.match(/(?:-p-|goods[_/-]?)(\d{5,})/i)?.[1] || '';
        const ids = [
          root.getAttribute('data-goods-id'),
          root.getAttribute('data-product-id'),
          root.getAttribute('data-sku-id'),
          idFromHref,
        ].filter(Boolean).map(String);
        const externalId = ids[0] || '';

        const text = String(root.innerText || '').trim();
        if (!text || text.length > 2200) continue;
        const lines = text.split(/\n+/).map(x => x.trim()).filter(Boolean);
        const nameNode = root.querySelector('[class*="name"], [class*="title"], [data-testid*="name"]');
        const ignoredLine = /sold|bought|save|coupon|lowest|eligible|add all|add to cart|%|أضف|إضافة|خصم|اشترى|تم البيع/i;
        const priceLine = /(?:USD|AED|SAR|KWD|QAR|BHD|OMR|\$|د\.?إ|ر\.?س)\s*[0-9]|[0-9]+(?:\.[0-9]+)?\s*(?:USD|AED|SAR|KWD|QAR|BHD|OMR)/i;
        const name = String(
          link?.getAttribute('aria-label') ||
          link?.getAttribute('title') ||
          nameNode?.textContent ||
          lines.find(line => !priceLine.test(line) && !ignoredLine.test(line) && line.length > 4) ||
          ''
        ).trim().replace(/\s+/g, ' ').slice(0, 500);
        if (!name) continue;

        const usdMatch = text.match(/(?:USD|\$)\s*([0-9]+(?:\.[0-9]{1,4})?)/i) || text.match(/([0-9]+(?:\.[0-9]{1,4})?)\s*USD/i);
        const explicitUsdPrice = Number(usdMatch?.[1] || 0) || 0;

        const variantLine = lines.find(line => /\//.test(line) && !/^https?:/i.test(line) && line.length < 180 && !priceLine.test(line)) || '';
        const parts = variantLine.split('/').map(x => x.trim()).filter(Boolean);
        const color = parts[0] || '';
        const size = parts.length > 1 ? parts.slice(1).join(' / ') : '';

        const key = externalId || href || `${name}|${image}`;
        if (!key || seen.has(key)) continue;
        seen.add(key);

        result.push({
          external_id: externalId,
          lookup_ids: [...new Set(ids)],
          name,
          product_url: href,
          image_url: image,
          variant: variantLine,
          color,
          size,
          explicit_usd_price: explicitUsdPrice,
        });
        if (result.length >= 60) break;
      }

      return {
        products: result,
        candidate_root_count: roots.length,
        image_candidate_count: imageCandidateRoots.size,
        product_link_count: productLinks.length,
      };
    }).catch(() => ({ products:[], candidate_root_count:0, image_candidate_count:0, product_link_count:0 }));

    let visibleProducts = Array.isArray(domSnapshot.products) ? domSnapshot.products : [];
    const visibleProductCountBeforeNetworkFallback = visibleProducts.length;
    const urlEvidence = /cart\/share|cart_share|share\/landing|group_id=|[?&]shc=|onelink/i.test(`${targetUrl} ${finalUrl}`);
    const textEvidence = /items shared by|add all to cart|shared items|shared by|مشاركة|السلة|عناصر مشتركة|إضافة الكل|اضافة الكل/i.test(bodyText);
    const sharedPageEvidence = urlEvidence || textEvidence || (/share/i.test(finalUrl) && visibleProducts.length > 0);

    if (sharedPageEvidence && visibleProducts.length === 0 && maps.networkProducts.size > 0) {
      const bodyNormalized = normalizeName(bodyText);
      const fallback = [];
      const seen = new Set();

      for (const candidate of maps.networkProducts.values()) {
        const nameKey = normalizeName(candidate.name);
        const visibleByName = nameKey.length >= 10 && bodyNormalized.includes(nameKey);
        if (!candidate.trusted && !visibleByName) continue;
        const key = candidate.external_id || nameKey || normalizeImageKey(candidate.image_url);
        if (!key || seen.has(key)) continue;
        seen.add(key);
        fallback.push({
          external_id: candidate.external_id || '',
          lookup_ids: candidate.lookup_ids || [],
          name: candidate.name || 'منتج SHEIN',
          product_url: '',
          image_url: candidate.image_url || '',
          variant: '',
          color: '',
          size: '',
          explicit_usd_price: Number(candidate.unit_price_original || 0) || 0,
        });
        if (fallback.length >= MAX_ITEMS) break;
      }

      if (fallback.length) visibleProducts = fallback;
    }

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
        let price = Number(product.explicit_usd_price || 0) || 0;

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
          const imageKey = normalizeImageKey(product.image_url);
          price = Number(maps.networkUsdByImage.get(imageKey) || 0) || 0;
        }

        if (!(price > 0)) {
          missingUsdPriceCount++;
          continue;
        }

        items.push({
          external_id: String(product.external_id || ''),
          name: product.name,
          product_url: product.product_url,
          image_url: product.image_url,
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
