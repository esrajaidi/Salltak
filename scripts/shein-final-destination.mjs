/** Only categorizes where a browser ended up; never asserts cart membership. */
export function classifyShareDestination(original, current, { visibleProductCount = 0, shareResponseSeen = false } = {}) {
  let start; let end;
  try { start = new URL(original); end = new URL(current); } catch { return 'unknown'; }
  const allowed = u => u.protocol === 'https:' && (u.hostname === 'shein.com' || u.hostname.endsWith('.shein.com'));
  if (!allowed(start) || !allowed(end)) return 'unknown';
  const path = end.pathname.toLowerCase();
  if (path.includes('/cart/share') || path.includes('/share/landing') || path.includes('/share_landing') || path.includes('/share-landing')) return 'share_landing';
  if (start.hostname === 'onelink.shein.com' && end.hostname !== 'onelink.shein.com' && !shareResponseSeen && visibleProductCount === 0) return 'app_only_redirect';
  return 'unknown';
}
