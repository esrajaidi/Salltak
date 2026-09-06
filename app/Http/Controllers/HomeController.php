<?php

namespace App\Http\Controllers;

use App\Models\Store;

class HomeController extends Controller
{
    public function __invoke()
    {
        $stores = Store::query()->where('is_active', true)->orderBy('name')->get();
        return view('home', compact('stores'));
    }
}
