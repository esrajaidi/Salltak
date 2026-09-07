# Salltak V10.3 Libya Payments Design

## Goal
Make the Libya payment subsystem truthful, configurable, customer-safe, and compact to manage. The administrator can configure and activate payment methods; customers only see methods that are active, complete enough to use, valid for the amount/order stage, and supported by the application.

## Source of truth
- Central Bank of Libya electronic-payment directory is the source for licensed company names and official activity categories.
- LYPay public site and CBL DevPortal are the source for LYPay IBAN, merchant QR/NUMO QR, Bearer-token/HMAC/webhook concepts. Direct CBL API access is not assumed for Salltak because the public DevPortal states direct access is for banks/licensed financial institutions; merchant integration must be through a participating bank/licensed partner unless the platform later receives approved credentials.
- OnePay is treated as an official instant-payment service because CBL publishes it alongside LYPay. No public Libya OnePay merchant API specification is assumed; merchant QR/beneficiary/reference configuration remains manual until merchant documentation is supplied.
- Licensed wallet/card processors without public merchant API docs are not given invented endpoints or credentials. Their config records merchant/acceptance details and an optional external checkout URL supplied by the contracted provider.

## Customer-facing methods/catalog
The system contains 22 entries: LYPay, OnePay, traditional bank transfer, NUMO/merchant QR, local bank cards, Visa, Mastercard, POS/SoftPOS, cash/on-delivery, seven CBL-listed wallet providers, and six CBL-listed card/mobile-banking processors.

## Integration modes
- `manual_verification`: customer transfers/pays externally and submits reference/receipt.
- `merchant_qr`: customer scans configured QR, then submits reference/receipt when required.
- `external_link`: customer opens a contracted provider/acquirer checkout URL, then returns to Salltak to submit reference/receipt until a callback connector is implemented.
- `in_person`: payment is accepted by staff/POS at delivery/office.
- `cash`: cash/on-delivery.
- `partner_api`: configuration can be stored for future bank/licensed-partner integration, but the method cannot be activated as an automated connector unless application code explicitly supports that connector.

## Activation safety
Turning a method ON validates provider-specific requirements. Examples: LYPay needs IBAN/account/merchant QR; bank transfer needs IBAN or account number; wallets need wallet/merchant/QR identification; external card checkout needs acquirer/provider merchant ID and checkout URL. Missing configuration blocks activation with an Arabic explanation.

## Customer filtering
A method is shown only when it is active, amount limits match, activation configuration remains valid, order stage matches its availability, and its integration mode is supported. Delivery-only methods such as cash and POS/SoftPOS are not offered for an early deposit.

## Security
`payment_methods.config` remains encrypted. Secret/API credentials are never rendered to customers. Empty secret inputs preserve stored values. No card PAN/CVV data is stored by Salltak.

## Admin UX
The long settings page becomes a compact responsive card grid with search/status/category filters. Each method shows name, activity, readiness, integration mode and ON/OFF action. Full provider-specific configuration opens inside a Bootstrap modal. Custom-method creation is collapsed by default.

## Testing
Add static/config regression checks for the 22-entry catalog, provider-specific schemas, activation guards, customer filtering hooks, compact admin UI, LYPay/OnePay presence, and no fake API behavior. Run existing V10.2, MySQL, responsive, SHEIN, reveal and PHP syntax checks before packaging.
