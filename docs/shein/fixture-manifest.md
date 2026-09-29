# SHEIN Shared Cart fixture and verification manifest

## Known production evidence (aggregate only)

On 2026-09-28, a request to `/my-carts/analyze` produced a
`shared_mobile: shared_page_unreadable` diagnostic with 0 visible DOM
products, 0 candidate roots, 82 network USD ID matches and 38 inspected
responses. A desktop retry reported `failed` after approximately 68 seconds.
The 82 network records are **not** verified shared-cart entries; the
existing worker scanned JSON from other SHEIN responses, potentially
including recommendations. No raw response, live share token, or
anonymized live schema has been obtained from that attempt.

## Synthetic test inputs

- `tests/static/shein-share-evidence.test.mjs`: a fabricated share
  payload containing a matching `group_id` and two `goods_list` entries;
  a separate fabricated recommendations response contains 82 records.
  Checks that only explicitly matching share evidence counts and that
  no raw product or URL data leaves the diagnostic summary.
- A fabricated mismatched group and an untrusted external URL demonstrate
  fail-closed source classification.

## Gate before implementing the actual live extractor

A browser observation on a user-authorized **working** share link must
identify where SHEIN provides the exact list of shared items. Capture
*only* sanitized structural keys/container boundaries and proof that the
observed list matches the intended group. Verify whether `goods_id`,
`sku_id`/`skc_id`, color, size, quantity, and USD price are co-located
or require exact-ID joining. If the site responds with a security
challenge or no share-bound list, mark this work blocked rather than
synthesizing products from unrelated network JSON.

Do not add a guessed endpoint or infer a cart count from prices received.
Production import behavior remains unchanged on the implementation
branch until membership and pricing can be independently verified.
