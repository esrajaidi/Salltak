// Executed inside the storefront page. Browser cookies/TLS/proxy settings are
// used only for the exact same origin; never fetch www from a mobile document.
export async function storefrontShareRequest({ endpoint, body, timeoutMs, maxBytes }) {
  const url = new URL(endpoint);
  if (url.protocol !== 'https:' || url.origin !== location.origin
      || !['www.shein.com', 'm.shein.com'].includes(url.hostname)
      || url.pathname !== '/ar/bff-api/order/cart/share/landing'
      || url.username || url.password || url.port) return { error: 'wrong_origin' };
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), timeoutMs);
  try {
    const response = await fetch(url.href, {
      method: 'POST', credentials: 'same-origin', redirect: 'error',
      headers: { accept: 'application/json', 'content-type': 'application/json', appcurrency: 'AED', 'x-requested-with': 'XMLHttpRequest' },
      body: JSON.stringify(body), signal: controller.signal,
    });
    const reader = response.body?.getReader();
    if (!reader) return { status: response.status, text: '', url: response.url };
    const chunks = [];
    let bytes = 0;
    while (true) {
      const part = await reader.read();
      if (part.done) break;
      bytes += part.value.byteLength;
      if (bytes > maxBytes) {
        await reader.cancel();
        return { status: response.status, oversized: true, url: response.url };
      }
      chunks.push(part.value);
    }
    const merged = new Uint8Array(bytes);
    let offset = 0;
    for (const chunk of chunks) { merged.set(chunk, offset); offset += chunk.byteLength; }
    return { status: response.status, text: new TextDecoder().decode(merged), url: response.url };
  } catch {
    return { error: controller.signal.aborted ? 'timeout' : 'browser_network' };
  } finally { clearTimeout(timer); }
}
