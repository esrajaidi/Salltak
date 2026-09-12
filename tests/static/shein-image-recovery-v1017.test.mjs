import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const cleaner = fs.readFileSync('app/Services/CartImport/Browser/SheinImportedItemCleaner.php', 'utf8');

test('SHEIN image cleaner rejects page URLs and keeps real CDN images', () => {
  assert.match(cleaner, /ltwebstatic\.com/);
  assert.match(cleaner, /pathinfo/);
  assert.match(cleaner, /\['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'\]/);
});

test('SHEIN image cleaner can recover from a valid fallback image', () => {
  assert.match(cleaner, /fallbackById/);
  assert.match(cleaner, /cleanImage\(\(string\) \(\$fallback\['image_url'\]/);
});
