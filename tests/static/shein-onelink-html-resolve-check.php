<?php

require __DIR__.'/../../app/Services/CartImport/SheinUrlResolver.php';

use App\Services\CartImport\SheinUrlResolver;

// Run the parser without HTTP or Laravel startup. Only synthetic onelink
// response bodies are used, and no real session, cookie, password or URL is logged.
$reflect = new ReflectionClass(SheinUrlResolver::class);
$resolver = $reflect->newInstanceWithoutConstructor();
$method = $reflect->getMethod('shareLandingFromHtml');

$original = 'https://onelink.shein.com/58/6663yrqirv65?shc=2_RNBOXsQCBJ4';
$fixtures = [
    '<script>var shareInfo={"group_id":"876543210","shc":"2_RNBOXsQCBJ4"}</script>',
    '<script>let deeplink = "/cart/share/landing?shc=2_RNBOXsQCBJ4&group_id=876543210";</script>',
    '<script>let deeplink = "/cart/share/landing?group_id%3D876543210%26shc%3D2_RNBOXsQCBJ4";</script>',
];

foreach ($fixtures as $fixture) {
    $landing = $method->invoke($resolver, $fixture, $original);
    if (! is_string($landing)) {
        throw new RuntimeException('Expected resolved share landing from synthetic fixture.');
    }

    $parts = parse_url($landing);
    parse_str((string) ($parts['query'] ?? ''), $query);
    if (($parts['host'] ?? null) !== 'www.shein.com'
        || ($query['group_id'] ?? null) !== '876543210'
        || ($query['shc'] ?? null) !== '2_RNBOXsQCBJ4'
        || ($query['local_country'] ?? null) !== 'AE'
        || ($query['cart_share'] ?? null) !== '1') {
        throw new RuntimeException('Resolved landing lost trusted share identity.');
    }
}

foreach ([
    '<html>ordinary homepage</html>',
    '<script>shareInfo={"group_id":"invalid"}</script>',
    '<script>shareInfo={"group_id":"876543210999999999999999999"}</script>',
    str_repeat('x', 2_000_001),
] as $notShared) {
    if ($method->invoke($resolver, $notShared, $original) !== null) {
        throw new RuntimeException('Incorrectly inferred a shared-cart group ID.');
    }
}

echo "SHEIN onelink HTML resolution checks passed.\n";
