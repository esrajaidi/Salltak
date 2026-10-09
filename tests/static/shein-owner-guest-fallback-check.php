<?php
// Lightweight regression guard for failed owner session behavior. Full end-to-end
// verification requires a real SHEIN account and must never run in CI.
$importer = file_get_contents(__DIR__.'/../../app/Services/CartImport/Browser/SheinBrowserImporter.php');
$adapter = file_get_contents(__DIR__.'/../../app/Services/CartImport/Adapters/SheinShareAdapter.php');

function requireGuard(bool $condition, string $label): void
{
    if (! $condition) throw new RuntimeException($label);
}

requireGuard(str_contains($importer, 'if ($accountState !== null && $this->isSharedCartUrl($url) && ($shared[\'items\'] ?? []) === [])'), 'Guest retry must only follow an empty authenticated share');
requireGuard(str_contains($importer, "'shared_guest_fallback', $traceId);"), 'Guest retry must not receive account state');
requireGuard(str_contains($importer, "'status'] = 'guest_price_needs_review'"), 'Guest response must be marked unverified for account');
requireGuard(str_contains($importer, "'account_session_applied'] = false"), 'Guest quote must never look authenticated');
requireGuard(str_contains($importer, 'File::deleteDirectory($guestProfile)'), 'Temporary guest browser profile must be deleted');
requireGuard(str_contains($adapter, "('guest_price_needs_review')"), 'Adapter must handle guest quote');
requireGuard(str_contains($adapter, 'تم استيراد السلة بأسعار الزائر'), 'Customer must see accurate guest quote warning');
requireGuard(str_contains($adapter, 'ImportResult::needsReview('), 'Guest quote must require review');
echo "SHEIN owner-session fallback guards passed.\n";
