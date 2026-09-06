import test from 'node:test';
import assert from 'node:assert/strict';
import { parseSheinGoodsAttr } from '../../scripts/shein-goods-attr.mjs';

test('parses Arabic color and alpha size from goodsAttr', () => {
  assert.deepEqual(parseSheinGoodsAttr('احمر وردي اللون / XXL'), {
    color: 'احمر وردي اللون',
    size: 'XXL',
  });
});

test('parses plus size and one-size labels', () => {
  assert.deepEqual(parseSheinGoodsAttr('رمادي / 1XL'), { color: 'رمادي', size: '1XL' });
  assert.deepEqual(parseSheinGoodsAttr('وردي / مقاس واحد'), { color: 'وردي', size: 'مقاس واحد' });
});

test('uses last size-like segment when goodsAttr has a middle option', () => {
  assert.deepEqual(parseSheinGoodsAttr('متعدد الألوان / 5 أزواج عشوائية / 40-43'), {
    color: 'متعدد الألوان',
    size: '40-43',
  });
});

test('does not invent a size when the final segment is not size-like', () => {
  assert.deepEqual(parseSheinGoodsAttr('طوق مزيف مصنوع يدوياً / 4 قطع (أزرق فاتح، رمادي، أبيض، وأزرق داكن)'), {
    color: 'طوق مزيف مصنوع يدوياً',
    size: '',
  });
});

test('returns empty values for missing goodsAttr', () => {
  assert.deepEqual(parseSheinGoodsAttr(''), { color: '', size: '' });
});
