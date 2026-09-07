import fs from 'node:fs';
import assert from 'node:assert/strict';

const js = fs.readFileSync('public/js/app-ui.js', 'utf8');
const preview = fs.readFileSync('resources/views/carts/preview.blade.php', 'utf8');
const show = fs.readFileSync('resources/views/carts/show.blade.php', 'utf8');

const thresholdMatch = js.match(/threshold:\s*([0-9.]+)/);
assert.ok(thresholdMatch, 'IntersectionObserver threshold must exist');
assert.ok(Number(thresholdMatch[1]) <= 0.01, `reveal threshold must be <= 0.01 for tall cart cards, got ${thresholdMatch[1]}`);
assert.match(preview, /surface-card-elevated\s+reveal\s+is-visible/, 'cart preview primary card must be visible immediately');
assert.match(show, /surface-card-elevated\s+reveal\s+is-visible/, 'saved cart primary card must be visible immediately');

console.log('reveal-guard: PASS');
