import fs from 'node:fs';
import assert from 'node:assert/strict';

const layout = fs.readFileSync('resources/views/layouts/app.blade.php','utf8');
const admin = fs.readFileSync('resources/views/layouts/admin.blade.php','utf8');
const css = fs.readFileSync('public/css/app.css','utf8');
const js = fs.readFileSync('public/js/app-ui.js','utf8');

// Typography must actually load the chosen Arabic families, not only name fallbacks.
assert.match(layout, /fonts\.googleapis\.com\/css2\?family=Cairo/);
assert.match(layout, /IBM\+Plex\+Sans\+Arabic/);
assert.match(css, /body\s*\{[^}]*font-family:\s*"IBM Plex Sans Arabic"/s);
assert.match(css, /(?:h1,h2,h3,h4,h5,h6|\.page-heading)[^{]*\{[^}]*font-family:\s*"Cairo"/s);

// Admin has no phantom 74px navbar offset because public navbar is hidden on admin routes.
assert.match(css, /\.admin-layout\s*\{[^}]*min-height:100vh/s);
assert.match(css, /@media\s*\(min-width:992px\)[^{]*\{[^}]*\.admin-sidebar\s*\{[^}]*top:0[^}]*height:100vh/s);
assert.match(css, /\.admin-topbar\s*\{[^}]*top:0/s);

// Mobile admin tables become readable cards instead of forcing a desktop-width table.
assert.match(js, /responsiveTables/);
assert.match(js, /data-label/);
assert.match(css, /@media\s*\(max-width:767\.98px\)[\s\S]*\.admin-main \.table-modern thead\s*\{display:none/);
assert.match(css, /\.admin-main \.table-modern td::before/);

// Topbar and sidebar remain usable on narrow devices.
assert.match(admin, /admin-mobile-logout/);
assert.match(css, /@media\s*\(max-width:575\.98px\)[\s\S]*\.admin-topbar/);
assert.match(css, /@media\s*\(max-width:575\.98px\)[\s\S]*\.admin-content/);

// Motion is enhanced but respects user accessibility preferences.
assert.match(css, /\.marketing-hero[^}]*animation:/s);
assert.match(css, /@media\s*\(prefers-reduced-motion:reduce\)/);

console.log('ui-v108-responsive: PASS');
