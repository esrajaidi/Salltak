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

function explicitUsd(value, depth = 0) {
  if (depth > 5 || !value || typeof value !== 'object') return 0;
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

function collectNetworkUsd(node, networkUsdById, depth = 0, seen = new WeakSet()) {
  if (depth > 16 || !node || typeof node !== 'object' || seen.has(node)) return;
  seen.add(node);

  if (!Array.isArray(node)) {
    const ids = objectIds(node);
    if (ids.length) {
      const amount = explicitUsd(node);
      if (amount > 0) {
        for (const id of ids) if (!networkUsdById.has(id)) networkUsdById.set(id, amount);
      }
    }
  }

  for (const child of Object.values(node)) {
    if (child && typeof child === 'object') collectNetworkUsd(child, networkUsdById, depth + 1, seen);
  }
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
    const networkUsdById = new Map();
    const responseTasks = [];
    let inspectedResponseCount = 0;

    page.on('response', response => {
      const task = (async () => {
        try {
          if (!sheinUrl(response.url())) return;
          const contentType = String(response.headers()['content-type'] || '').toLowerCase();
          if (!contentType.includes('json') && !contentType.includes('text')) return;
          const text = await response.text();
          if (!text || text.length > MAX_RESPONSE_BYTES) return;
          let decoded;
          try { decoded = JSON.parse(text); } catch { return; }
          if (!decoded || typeof decoded !== 'object') return;
          inspectedResponseCount++;
          collectNetworkUsd(decoded, networkUsdById);
        } catch {}
      })();
      responseTasks.push(task);
    });

    await page.goto(targetUrl, { waitUntil:'domcontentloaded', timeout:timeoutMs });
    try { await page.waitForLoadState('networkidle', { timeout:Math.min(12_000, timeoutMs) }); } catch {}
    await page.waitForTimeout(2200);
    await Promise.allSettled(responseTasks);

    const bodyText = await page.locator('body').innerText().catch(() => '');
    const finalUrl = page.url();

    const visibleProducts = await page.evaluate(() => {
      const abs = value => {
        try { return new URL(String(value || ''), location.href).href; } catch { return String(value || ''); }
      };
      const excluded = /(recommend|suggest|similar|related|guess|you.?may.?like|wishlist|favorite|favourite|recent|viewed|history|search|trend|campaign|marketing)/i;
      const productLinks = [...document.querySelectorAll('a[href*="-p-"], a[href*="/product"], a[href*="goods"]')];
      const result = [];
      const seen = new Set();

      for (const link of productLinks) {
        const href = abs(link.getAttribute('href') || '');
        const idFromHref = href.match(/(?:-p-|goods[_/-]?)(\d{5,})/i)?.[1] || '';
        const root = link.closest('[data-goods-id], [data-product-id], [data-sku-id], article, li, [class*="item"], [class*="goods"], [class*="product"]') || link.parentElement;
        if (!root) continue;

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
        if (!image) continue;

        const ids = [
          root.getAttribute('data-goods-id'),
          root.getAttribute('data-product-id'),
          root.getAttribute('data-sku-id'),
          idFromHref,
        ].filter(Boolean).map(String);
        const externalId = ids[0] || '';
        if (!externalId && !href) continue;

        const text = String(root.innerText || '').trim();
        if (!text || text.length > 2200) continue;
        const lines = text.split(/\n+/).map(x => x.trim()).filter(Boolean);
        const nameNode = root.querySelector('[class*="name"], [class*="title"], [data-testid*="name"]');
        const name = String(
          link.getAttribute('aria-label') ||
          link.getAttribute('title') ||
          nameNode?.textContent ||
          lines.find(line => !/(?:AED|SAR|USD|د\.?إ|ر\.?س|\$)\s*[0-9]/i.test(line) && !/sold|bought|save|coupon|lowest|%|أضف|إضافة/i.test(line)) ||
          ''
        ).trim().replace(/\s+/g, ' ').slice(0, 500);
        if (!name) continue;

        const usdMatch = text.match(/(?:USD|\$)\s*([0-9]+(?:\.[0-9]{1,4})?)/i) || text.match(/([0-9]+(?:\.[0-9]{1,4})?)\s*USD/i);
        const explicitUsdPrice = Number(usdMatch?.[1] || 0) || 0;

        const variantLine = lines.find(line => /\//.test(line) && !/^https?:/i.test(line) && line.length < 160) || '';
        const parts = variantLine.split('/').map(x => x.trim()).filter(Boolean);
        const color = parts[0] || '';
        const size = parts.length > 1 ? parts.slice(1).join(' / ') : '';

        const key = externalId || href;
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
      return result;
    }).catch(() => []);

    const visibleProductCount = visibleProducts.length;
    const urlEvidence = /cart\/share|cart_share|share\/landing|group_id=|[?&]shc=|onelink/i.test(`${targetUrl} ${finalUrl}`);
    const textEvidence = /items shared by|add all to cart|shared items|shared by|مشاركة|السلة|عناصر مشتركة|إضافة الكل|اضافة الكل/i.test(bodyText);
    const sharedPageEvidence = urlEvidence || textEvidence || (/share/i.test(finalUrl) && visibleProductCount > 0);

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
          network_usd_price_count:networkUsdById.size,
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
            const matched = Number(networkUsdById.get(String(id)) || 0) || 0;
            if (matched > 0) {
              price = matched;
              break;
            }
          }
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
        final_item_count:items.length,
        dom_item_count:visibleProductCount,
        missing_usd_price_count:missingUsdPriceCount,
        network_usd_price_count:networkUsdById.size,
        inspected_response_count:inspectedResponseCount,
      };

      if (visibleProductCount > 0 && missingUsdPriceCount > 0) {
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
