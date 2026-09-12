import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { chromium } from 'playwright-chromium';

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
      locale: 'en-AE',
      viewport: { width: 430, height: 932 },
      userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 Version/18.7 Mobile/15E148 Safari/604.1',
      args: ['--disable-dev-shm-usage'],
    });

    const page = context.pages()[0] || await context.newPage();
    await page.goto(targetUrl, { waitUntil:'domcontentloaded', timeout:timeoutMs });
    try { await page.waitForLoadState('networkidle', { timeout:Math.min(12_000, timeoutMs) }); } catch {}
    await page.waitForTimeout(1800);

    const bodyText = await page.locator('body').innerText().catch(() => '');
    const sharedItemsLanding = /items shared by|add all to cart|shared items|shared by/i.test(bodyText);
    if (!sharedItemsLanding) {
      out({ ok:true, status:'not_shared_page', final_url:page.url(), items:[], payloads:[], meta:{ shared_items_landing:false } });
    } else {
      const items = await page.evaluate(() => {
        const abs = value => {
          try { return new URL(String(value || ''), location.href).href; } catch { return String(value || ''); }
        };
        const roots = [...document.querySelectorAll('article, li, [class*="item"], [class*="goods"], [class*="product"]')];
        const result = [];
        const seen = new Set();

        for (const root of roots) {
          const text = String(root.innerText || '').trim();
          if (!text || text.length > 1800) continue;
          const priceMatch = text.match(/\$\s*([0-9]+(?:\.[0-9]{1,2})?)/);
          if (!priceMatch) continue;

          const img = root.querySelector('img');
          if (!img) continue;
          const image = abs(img.currentSrc || img.getAttribute('src') || img.getAttribute('data-src') || '');
          if (!image) continue;

          const link = root.querySelector('a[href]');
          const href = abs(link?.getAttribute('href') || '');
          const id = href.match(/(?:-p-|goods[_/-]?)(\d{5,})/i)?.[1] || root.getAttribute('data-goods-id') || root.getAttribute('data-product-id') || '';

          const lines = text.split(/\n+/).map(x => x.trim()).filter(Boolean);
          const name = String(link?.getAttribute('aria-label') || link?.getAttribute('title') || lines.find(line => !/^\$/.test(line) && !/sold|bought|save|coupon|lowest|%/i.test(line)) || '').slice(0, 500);
          if (!name) continue;

          const variantLine = lines.find(line => /\//.test(line) && !/^https?:/i.test(line)) || '';
          const parts = variantLine.split('/').map(x => x.trim()).filter(Boolean);
          const color = parts[0] || '';
          const size = parts.length > 1 ? parts.slice(1).join(' / ') : '';
          const price = Number(priceMatch[1]);
          if (!(price > 0)) continue;

          const key = `${id || href || name}|${variantLine}|${price}`;
          if (seen.has(key)) continue;
          seen.add(key);

          result.push({
            external_id: String(id),
            name,
            product_url: href,
            image_url: image,
            variant: variantLine,
            color,
            size,
            quantity: 1,
            unit_price_original: price,
            currency: 'USD',
          });

          if (result.length >= 60) break;
        }
        return result;
      }).catch(() => []);

      out({
        ok:true,
        status:'loaded',
        final_url:page.url(),
        items,
        payloads:[],
        meta:{ shared_items_landing:true, final_item_count:items.length, dom_item_count:items.length },
      });
    }
  } catch (error) {
    out({ ok:false, status:'failed', message:String(error?.message || error || 'Shared page import failed'), items:[], payloads:[] }, 1);
  } finally {
    if (context) await context.close().catch(() => {});
  }
}
