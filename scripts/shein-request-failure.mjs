// Only a fixed category leaves this function, never URLs or bearer material.
export function requestFailureCategory(error) {
  const message = String(error?.message || '').slice(0, 8000);
  if (/timeout|timed out|ETIMEDOUT/i.test(message)) return 'timeout';
  if (/ENOTFOUND|EAI_AGAIN|getaddrinfo/i.test(message)) return 'dns';
  if (/certificate|SSL|TLS|CERT_/i.test(message)) return 'tls';
  if (/ECONNRESET|socket hang up/i.test(message)) return 'connection_reset';
  if (/ECONNREFUSED/i.test(message)) return 'connection_refused';
  if (/context.*closed|browser.*closed|target.*closed/i.test(message)) return 'context_closed';
  return 'unknown';
}
