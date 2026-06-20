<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        // Get shop info if user is authenticated
        $shop = null;
        if (auth()->check()) {
            $shop = Shop::find(auth()->user()->shop_id ?? 1);
        }

        return Inertia::render('Welcome', [
            'shop' => $shop,
        ]);
    }
}