function cleanPart(value) {
  return String(value ?? '').replace(/\s+/g, ' ').trim();
}

function isSizeLike(value) {
  const text = cleanPart(value);
  if (!text) return false;

  const compact = text.replace(/\s+/g, '').toUpperCase();
  if (/^(?:XXXS|XXS|XS|S|M|L|XL|XXL|XXXL|XXXXL|[0-9]+XL)$/.test(compact)) return true;
  if (/^(?:ONE[-_ ]?SIZE|ONESIZE)$/i.test(text)) return true;
  if (/^(?:مقاس\s*واحد|قياس\s*واحد)$/i.test(text)) return true;
  if (/^\d{1,3}(?:\.\d+)?(?:\s*[-–—]\s*\d{1,3}(?:\.\d+)?)?$/.test(text)) return true;
  if (/^(?:EU|US|UK)\s*\d{1,3}(?:\.\d+)?(?:\s*[-–—]\s*\d{1,3}(?:\.\d+)?)?$/i.test(text)) return true;

  return false;
}

/**
 * SHEIN shared-cart payloads often expose the selected options as a single
 * `goodsAttr` string such as "الأسود / XL" instead of named color/size fields.
 * The first segment is the selected color/style and the last segment is used
 * as size only when it actually looks like a size.
 */
export function parseSheinGoodsAttr(value) {
  const raw = cleanPart(value);
  if (!raw) return { color: '', size: '' };

  const parts = raw.split('/').map(cleanPart).filter(Boolean);
  if (!parts.length) return { color: '', size: '' };

  const color = parts[0] ?? '';
  const last = parts.at(-1) ?? '';
  const size = parts.length > 1 && isSizeLike(last) ? last : '';

  return { color, size };
}
