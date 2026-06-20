<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ShopController extends Controller
{
    public function edit($id)
    {
        $shop = Shop::findOrFail($id);
        
        if (auth()->user()->shop_id !== $shop->id && auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }
        
        return Inertia::render('Shop/Edit', [
            'shop' => $shop,
        ]);
    }

    public function update(Request $request, $id)
    {
        $shop = Shop::findOrFail($id);
        
        if (auth()->user()->shop_id !== $shop->id && auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'email' => 'required|email|unique:shops,email,' . $shop->id,
            'phone' => 'required|string|max:20|unique:shops,phone,' . $shop->id,
            'address' => 'required|string',
            'shop_type' => 'required|in:grocery,clothing,pharmacy,tailor,electronics,restaurant,other',
            'tin_number' => 'nullable|string|max:50',
            'currency_symbol' => 'required|string|max:5',
            'currency_code' => 'required|string|max:5',
            'is_active' => 'boolean',
            'logo' => 'nullable|image|mimes:jpeg,png,gif,webp,svg|max:2048',
        ]);

        try {
            $data = $request->only([
                'name',
                'owner_name',
                'email',
                'phone',
                'address',
                'shop_type',
                'tin_number',
                'currency_symbol',
                'currency_code',
                'is_active',
            ]);

            // Handle logo upload
            if ($request->hasFile('logo')) {
                // Delete old logo if exists
                if ($shop->logo) {
                    Storage::disk('public')->delete($shop->logo);
                }
                
                $path = $request->file('logo')->store('shop_logos', 'public');
                $data['logo'] = $path;
            }

            $shop->update($data);

            return redirect()->back()->with('success', 'Shop information updated successfully!');
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update shop: ' . $e->getMessage());
        }
    }
}