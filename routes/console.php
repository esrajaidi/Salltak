<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('cartly:about', function () {
    $this->info('سلات ليبيا - Cart Import & LYD Conversion Platform');
})->purpose('Show platform information');
