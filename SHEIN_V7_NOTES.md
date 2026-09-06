# SHEIN V7 — Color & Size Extraction

This build extends the V6 shared-cart endpoint parser.

## Change
- Reads SHEIN `goodsAttr` values returned by `/bff-api/order/cart/share/landing`.
- Fills `color` and `size` when SHEIN returns combined selected attributes such as:
  - `احمر وردي اللون / XXL`
  - `رمادي / 1XL`
  - `وردي / مقاس واحد`
  - `متعدد الألوان / 5 أزواج عشوائية / 40-43`
- Keeps `sku_code` as the product variant/SKU.
- Does not invent a size when the final attribute does not look like a size.

## Tests
- Pure Node tests cover Arabic color/size parsing and multi-part attributes.
- Existing SHEIN smoke checks and Bootstrap/RTL UI checks remain green.
