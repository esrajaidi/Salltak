<?php

require __DIR__.'/../../app/Services/CartImport/SheinSharedCartInput.php';

use App\Services\CartImport\SheinSharedCartInput;

function expectShare(string $input, ?string $expected): void
{
    try {
        $actual = SheinSharedCartInput::extract($input);
        if ($actual !== $expected) {
            throw new RuntimeException("Expected ".var_export($expected, true).", got ".var_export($actual, true));
        }
    } catch (InvalidArgumentException $e) {
        if ($expected !== null) throw $e;
    }
}

$short = 'https://onelink.shein.com/56/64kcvbmdsiau?shc=2_RkKGSegsOE6';
expectShare($short, $short);
expectShare("I found some great items at SHEIN!\nThese items in my shopping cart are great.\n".$short, $short);
expectShare("مرحبا، هذا الرابط:\n".$short."\u{00A0}", $short);
expectShare("Read more at https://example.com/first\n".$short, $short);
$long = 'https://m.shein.com/ar/cart/share/landing?shc=2_RwCnM9DrOvA&group_id=851956795&local_country=AE&cart_share=1';
expectShare($long, $long);
expectShare(str_replace('&', '&amp;', $long), $long);
expectShare('https://evil.example/cart/share/landing?group_id=851956795', null);
expectShare('https://onelink.shein.com.evil.example/56/64kcvbmdsiau?shc=abc', null);
expectShare('https://m.shein.com/ar/cart/share/landing?group_id=not-a-number', null);
expectShare('https://m.shein.com/ar/cart/share/landing?group_id=851956795@evil.example', null);
expectShare('test without links', null);
echo "SHEIN share input cases passed.\n";
