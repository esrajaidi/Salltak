export function ownerCartUrl(raw) {
  try {
    const url = new URL(String(raw || ''), 'https://www.shein.com/');
    if (url.protocol !== 'https:' || url.hostname !== 'www.shein.com'
        || url.username || url.password || url.port
        || !/^\/(?:[a-z]{2}(?:-[a-z]{2})?\/)?cart(?:\.html)?\/?$/i.test(url.pathname)) return '';
    // Navigation is read-only: never replay query actions from remote markup.
    url.search = '';
    url.hash = '';
    return url.href;
  } catch { return ''; }
}
