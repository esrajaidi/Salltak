<?php
require_once __DIR__.'/../../app/Services/CartImport/Browser/SheinImportedItemCleaner.php';
use App\Services\CartImport\Browser\SheinImportedItemCleaner;
$base=[['external_id'=>'100','variant'=>'M','name'=>'','image_url'=>'']];
$other=[['external_id'=>'100','variant'=>'L','name'=>'Wrong','image_url'=>'https://img.ltwebstatic.com/wrong.jpg']];
$exact=[['external_id'=>'100','variant'=>'M','name'=>'Right','image_url'=>'https://img.ltwebstatic.com/right.jpg']];
if (SheinImportedItemCleaner::clean($base,$other)[0]['image_url'] !== '') throw new RuntimeException('cross-variant image');
if (SheinImportedItemCleaner::clean($base,$exact)[0]['image_url'] !== 'https://img.ltwebstatic.com/right.jpg') throw new RuntimeException('exact SKU did not enrich');
echo "PASS: variant identity controls enrichment\n";
