# SHEIN cart extractor V5

This revision tightens shared-cart extraction after a real Windows debug run showed false positives from SHEIN page CSS/recommendation/product-feed content.

Changes:
- generic `count` / `num` keys are no longer treated as cart quantity;
- network JSON candidates must live in a cart/share branch (or an explicit cart endpoint);
- recommendation/feed/search/history/trend branches remain excluded;
- markup/CSS/source-map text is rejected as product identity;
- DOM extraction only scans cart/bag containers and requires a real quantity control/value;
- DOM price extraction prefers an actual price element with a currency token;
- normal CLI output is compact: payload bodies and full HTML are omitted unless `"debug": true` is passed;
- `response_meta` remains available so real SHEIN endpoints can be diagnosed without dumping megabytes of HTML/CSS.
