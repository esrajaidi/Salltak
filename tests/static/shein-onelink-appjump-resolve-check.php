<?php

require __DIR__.'/../../app/Services/CartImport/SheinUrlResolver.php';

use App\Services\CartImport\SheinUrlResolver;

// Offline, synthetic fixtures. No SHEIN visits, session files or raw tokens
// appear in logs. Verify the exact trusted mobile app jump sequence.
$reflect = new ReflectionClass(SheinUrlResolver::class);
$resolver = $reflect->newInstanceWithoutConstructor();
$deeplink = $reflect->getMethod('shareLandingFromDeeplinkHtml');
$jumpLink = $reflect->getMethod('trustedAppjumpFromHtml');
$jumpPage = $reflect->getMethod('shareLandingFromAppjumpHtml');
$short = 'https://onelink.shein.com/58/6663yrqirv65?shc=2_RNBOXsQCBJ4';

$encoded = rawurlencode(json_encode([
    'group_id' => '876543210',
    'shc' => '2_RNBOXsQCBJ4',
    'local_country' => 'SA',
]));
$deeplinkHtml = '<input type="hidden" id="deeplink" value="sheinlink://applink/share_receiver?data='.$encoded.'">';
$fromDeeplink = $deeplink->invoke($resolver, $deeplinkHtml, $short);
if (! is_string($fromDeeplink)) {
    throw new RuntimeException('Expected direct deeplink group resolution.');
}
parse_str((string) parse_url($fromDeeplink, PHP_URL_QUERY), $query);
if (parse_url($fromDeeplink, PHP_URL_HOST) !== 'www.shein.com'
    || ($query['group_id'] ?? '') !== '876543210'
    || ($query['local_country'] ?? '') !== 'AE'
    || ($query['shc'] ?? '') !== '2_RNBOXsQCBJ4') {
    throw new RuntimeException('Deeplink was not correctly converted to a canonical share landing.');
}

$jump = 'https://api-shein.shein.com/h5/sharejump/appjump?source=onelink&shc=2_RNBOXsQCBJ4';
$onelinkHtml = '<input id="url" value="'.htmlspecialchars($jump, ENT_QUOTES).'">';
if ($jumpLink->invoke($resolver, $onelinkHtml) !== $jump) {
    throw new RuntimeException('Trusted appjump URL was not decoded correctly.');
}
$jumpHtml = '<script>var shareInfo={"id":"876543210","share_type":"cart","status":"1"};'."\n".'</script>';
$fromJump = $jumpPage->invoke($resolver, $jumpHtml, $short);
if ($fromJump !== $fromDeeplink) {
    throw new RuntimeException('Appjump group resolution does not match direct deeplink.');
}

$invalid = [
    '<input id="url" value="https://example.com/h5/sharejump/appjump">',
    '<input id="url" value="https://api-shein.shein.com.evil.example/h5/sharejump/appjump">',
    '<input id="url" value="http://api-shein.shein.com/h5/sharejump/appjump">',
    '<input id="url" value="https://api-shein.shein.com:8443/h5/sharejump/appjump">',
];
foreach ($invalid as $body) {
    if ($jumpLink->invoke($resolver, $body) !== null) {
        throw new RuntimeException('Unsafe HTML redirect was trusted.');
    }
}
if ($jumpPage->invoke($resolver, "<script>shareInfo={\"id\":\"876543210\",\"share_type\":\"product\"};\n</script>", $short) !== null
    || $jumpPage->invoke($resolver, "<script>shareInfo={\"id\":\"876543210\",\"status\":-1};\n</script>", $short) !== null) {
    throw new RuntimeException('Rejected or expired share must not be treated as a cart.');
}
echo "SHEIN onelink appjump resolution checks passed.\n";
