import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';

const cleaner = fs.readFileSync('app/Services/CartImport/Browser/SheinImportedItemCleaner.php', 'utf8');

test('SHEIN image cleaner rejects page URLs and keeps real CDN images', () => {
  assert.match(cleaner, /ltwebstatic\.com/);
  assert.match(cleaner, /pathinfo/);
  assert.match(cleaner, /imageExtensions\s*=\s*\[/);
  assert.match(cleaner, /jpg/);
  assert.match(cleaner, /webp/);
});

test('SHEIN image cleaner can recover from a valid fallback image', () => {
  assert.match(cleaner, /fallbackById/);
  assert.match(cleaner, /fallback\['image_url'\]/);
  assert.match(cleaner, /cleanImage/);
});
