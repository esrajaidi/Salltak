# SHEIN share test fixture policy

This directory is reserved for synthetic samples or explicitly sanitized,
user-authorized observations of SHEIN shared carts. Do not commit real
`shc` tokens, `group_id` identifiers, cookies, customer details, full
request/response bodies, or private product lists.

The current `tests/static/shein-share-evidence.test.mjs` uses **synthetic**
in-memory samples: one explicitly share-bound group with two products and
a separate recommendations response with 82 price records. These are
regression cases, not a representation of SHEIN's current live API.

Do not write a live extractor from these synthetic shapes alone. First
observe a real permitted share response in an isolated environment and
record an anonymized structural fixture that proves membership in the
requested shared-cart group.
