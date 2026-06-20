<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy as TightenZiggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'shop_id' => $user->shop_id,
                    'role' => $user->role,
                    // Load shop relationship
                    'shop' => $user->shop ? [
                        'id' => $user->shop->id,
                        'name' => $user->shop->name,
                        'owner_name' => $user->shop->owner_name,
                        'logo' => $user->shop->logo,
                        'address' => $user->shop->address,
                        'phone' => $user->shop->phone,
                        'email' => $user->shop->email,
                    ] : null,
                ] : null,
            ],
            'ziggy' => function () use ($request) {
                return array_merge((new TightenZiggy())->toArray(), [
                    'location' => $request->url(),
                ]);
            },
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'sale_id' => fn () => $request->session()->get('sale_id'),
                'invoice_data' => fn () => $request->session()->get('invoice_data'),
                'updated_products' => fn () => $request->session()->get('updated_products'),
            ],
        ]);
    }
}