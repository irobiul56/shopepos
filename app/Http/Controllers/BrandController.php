<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BrandController extends Controller
{
    // Display brands list
    public function index()
    {
        $brands = Brand::where('shop_id', auth()->user()->shop_id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Brand/Index', [
            'brands' => $brands,
        ]);
    }

    // Store brand manually
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:brands,name,NULL,id,shop_id,' . auth()->user()->shop_id,
            'description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $data = $request->all();
        $data['shop_id'] = auth()->user()->shop_id;
        $data['slug'] = Str::slug($request->name);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('brands', 'public');
            $data['logo'] = $path;
        }

        $brand = Brand::create($data);

        // Return the created brand with full data
        return redirect()->route('brands.index')
            ->with('success', 'Brand created successfully!')
            ->with('created_brand', $brand);
    }

    // Update brand
    public function update(Request $request, Brand $brand)
    {
        // Validate the request
        $rules = [
            'name' => 'required|string|max:255|unique:brands,name,' . $brand->id . ',id,shop_id,' . auth()->user()->shop_id,
            'description' => 'nullable|string',
        ];
        
        // Only validate logo if a file is being uploaded
        if ($request->hasFile('logo')) {
            $rules['logo'] = 'image|mimes:jpeg,png,jpg,gif|max:2048';
        }
        
        $request->validate($rules);
        
        // Prepare data for update
        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'slug' => Str::slug($request->name),
            'is_active' => $request->is_active ?? $brand->is_active,
        ];
        
        $newLogoPath = null;
        
        // Handle logo
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($brand->logo) {
                Storage::disk('public')->delete($brand->logo);
            }
            $path = $request->file('logo')->store('brands', 'public');
            $data['logo'] = $path;
            $newLogoPath = $path;
        } 
        // Check if logo should be removed
        elseif ($request->input('remove_logo') == '1') {
            if ($brand->logo) {
                Storage::disk('public')->delete($brand->logo);
                $data['logo'] = null;
                $newLogoPath = null;
            }
        }
        
        $brand->update($data);
        
        // Return JSON response for Inertia
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Brand updated successfully!',
                'brand' => $brand->fresh(),
                'new_logo' => $newLogoPath ? Storage::url($newLogoPath) : null
            ]);
        }
        
        return redirect()->route('brands.index')
            ->with('success', 'Brand updated successfully!');
    }

    // Delete brand
    public function destroy(Brand $brand)
    {
        // Check if brand has products
        if ($brand->products()->count() > 0) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete brand with associated products!'
                ], 422);
            }
            return redirect()->route('brands.index')
                ->with('error', 'Cannot delete brand with associated products!');
        }
        
        // Delete logo
        if ($brand->logo) {
            Storage::disk('public')->delete($brand->logo);
        }
        
        $brand->delete();
        
        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Brand deleted successfully!'
            ]);
        }
        
        return redirect()->route('brands.index')
            ->with('success', 'Brand deleted successfully!');
    }

    // Toggle brand status
    public function toggleStatus(Brand $brand)
    {
        $brand->update(['is_active' => !$brand->is_active]);
        
        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Brand status updated successfully!',
                'brand' => $brand->fresh()
            ]);
        }
        
        return redirect()->route('brands.index')
            ->with('success', 'Brand status updated successfully!');
    }
}