/**
 * Only count products that belong to a structurally identified shared-cart
 * response. This module intentionally returns no URL, token, payload, price,
 * product name or product ID; it is safe to include its result in diagnostics.
 *
 * The accepted cartShareData shape is a synthetic compatibility probe, not
 * proof that the currently deployed SHEIN endpoint uses this schema.
 */
export function summarizeShareEvidence({ networkResponses = [], pageSignals = {}, expectedShareContext = {} } = {}) {
  const counts = { share: 0, cart: 0, product: 0, other: 0 };
  let shareResponseSeen = false;
  let shareBoundCandidateCount = 0;
  const groupId = String(expectedShareContext.groupId || '').trim();
  const shareToken = String(expectedShareContext.shc || '').trim();

  for (const response of Array.isArray(networkResponses) ? networkResponses : []) {
    const category = classifyResponse(response?.url);
    counts[category]++;
    if (category !== 'share') continue;

    const payload = response?.payload;
    const scope = isRecord(payload?.cartShareData)
      ? payload.cartShareData
      : isRecord(payload?.data?.cartShareData) ? payload.data.cartShareData : null;
    if (!scope || !Array.isArray(scope.goods_list)) continue;

    // A response whose group differs from the requested group can never be
    // promoted to cart evidence, even if another token happens to match.
    const actualGroup = String(scope.group_id || scope.groupId || '').trim();
    if (groupId && actualGroup && groupId !== actualGroup) continue;
    const groupMatches = Boolean(groupId && actualGroup === groupId);
    const actualToken = String(scope.shc || scope.share_token || '').trim();
    const tokenMatches = Boolean(shareToken && actualToken && shareToken === actualToken);
    if (!groupMatches && !tokenMatches) continue;

    shareResponseSeen = true;
    shareBoundCandidateCount += scope.goods_list.filter(isRecord).length;
  }

  return {
    shareResponseSeen,
    shareBoundCandidateCount: Math.min(1000, shareBoundCandidateCount),
    domCandidateCount: boundedCount(pageSignals?.domCandidateCount),
    responseClassCounts: counts,
  };
}

function isRecord(value) {
  return value !== null && typeof value === 'object' && !Array.isArray(value);
}

function boundedCount(value) {
  return Number.isFinite(value) ? Math.min(1000, Math.max(0, Math.trunc(value))) : 0;
}

function classifyResponse(rawUrl) {
  try {
    const url = new URL(rawUrl);
    if (url.protocol !== 'https:' || !/(^|\.)shein\.com$/i.test(url.hostname)) return 'other';
    const path = url.pathname.toLowerCase();
    if (/cart[^/]*\/share|share[^/]*\/cart|share\/landing|share[-_]items/.test(path)) return 'share';
    if (/(^|\/)(cart|basket|bag)(\/|$)/.test(path)) return 'cart';
    if (/(^|\/)(product|goods|detail)(\/|$)/.test(path)) return 'product';
    return 'other';
  } catch {
    return 'other';
  }
}
