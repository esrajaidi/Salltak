import fs from 'node:fs';
import assert from 'node:assert/strict';

const catalogPath = 'config/libya_payment_methods.php';
assert.ok(fs.existsSync(catalogPath), 'Libya payment catalog config must exist');
const catalog = fs.readFileSync(catalogPath, 'utf8');
const requiredCodes = [
  'lypay','onepay','bank_transfer','numo_qr','local_cards','visa','mastercard','pos_softpos','cash',
  'almadar_wallet','alittihad_international_wallet','miza_wallet','daleel_libya_wallet','fawry_wallet',
  'albidaya_wallet','runpay_wallet','tadawul_cards','tafani_cards','obour_cards','masarat_mobile_cards',
  'ithmar_cards','moamalat_cards'
];
for (const code of requiredCodes) {
  assert.match(catalog, new RegExp(`['"]code['"]\\s*=>\\s*['"]${code}['"]`), `missing payment method ${code}`);
}
assert.match(catalog, /merchant_docs_required/, 'catalog must mark provider-private merchant docs honestly');
assert.match(catalog, /public_docs_partner_api_restricted/, 'LYPay must record public docs + restricted direct API access');

const controller = fs.readFileSync('app/Http/Controllers/Admin/PaymentMethodController.php', 'utf8');
assert.match(controller, /installLibyaCatalog/, 'admin must be able to install/refresh Libya catalog');
assert.match(controller, /activationIssues\(\)/, 'admin must block incomplete methods from activation');
assert.match(controller, /checkout_url/, 'admin config must support contracted checkout URLs');
assert.match(controller, /webhook_secret/, 'admin config must preserve secret webhook credentials');

const orderController = fs.readFileSync('app/Http/Controllers/OrderController.php', 'utf8');
assert.match(orderController, /canOfferForOrder/, 'customer payment methods must be filtered by amount, stage and configuration');

console.log('payment-catalog: PASS');
