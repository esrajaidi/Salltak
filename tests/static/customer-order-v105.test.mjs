import fs from 'node:fs';
const view=fs.readFileSync('resources/views/orders/show.blade.php','utf8');
let failures=[];
for (const token of ['order-progress','customer-activity-timeline','unit_price_original','السعر بالدولار','readonly-price','visibility']) if (!view.includes(token)) failures.push(`customer order missing ${token}`);
if (/name=["'](?:unit_price_original|unit_price_lyd|reviewed_unit_price_lyd)["']/.test(view)) failures.push('customer view exposes editable price field');
if (!view.includes("event_type==='note'") && !view.includes("event_type === 'note'")) failures.push('customer timeline does not distinguish notes');
if (!view.includes("visibility==='customer'") && !view.includes("visibility === 'customer'")) failures.push('customer timeline missing visibility guard');
if (failures.length){console.error('FAIL premium customer order:', failures.join('; ')); process.exit(1)}
console.log('PASS premium customer order static checks');
