<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'brand', 'unit'])
            ->where('shop_id', auth()->user()->shop_id);
        
        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('barcode', 'like', "%{$search}%");
            });
        }
        
        // Category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        // Brand filter
        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }
        
        // Stock status filter
        if ($request->filled('stock_status')) {
            if ($request->stock_status === 'low') {
                $query->whereRaw('stock_quantity <= min_stock_alert')
                    ->where('stock_quantity', '>', 0);
            } elseif ($request->stock_status === 'out') {
                $query->where('stock_quantity', 0);
            } elseif ($request->stock_status === 'in') {
                $query->whereRaw('stock_quantity > min_stock_alert');
            }
        }
        
        $products = $query->orderBy('created_at', 'desc')->paginate(10);
        
        // Get categories and brands for filters
        $categories = Category::where('shop_id', auth()->user()->shop_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $brands = Brand::where('shop_id', auth()->user()->shop_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return Inertia::render('Product/Index', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
        ]);
    }

    // Show create product form
    public function create()
    {
        $categories = Category::where('shop_id', auth()->user()->shop_id)
            ->where('is_active', true)
            ->get();
        
        $brands = Brand::where('shop_id', auth()->user()->shop_id)
            ->where('is_active', true)
            ->get();
        
        $units = Unit::where('shop_id', auth()->user()->shop_id)
            ->where('is_active', true)
            ->get();

        return Inertia::render('Product/Create', [
            'categories' => $categories,
            'brands' => $brands,
            'units' => $units,
        ]);
    }

    // Store product
    public function store(Request $request)
    {

        // Validation
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku,NULL,id,shop_id,' . auth()->user()->shop_id,
            'barcode' => 'nullable|string|max:100',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit_id' => 'required|exists:units,id',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock_alert' => 'nullable|integer|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_taxable' => 'boolean',
            'product_image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120', // 5MB
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|mimes:jpeg,png,jpg|max:5120'
            ]);
            
        // Prepare data
        $data = $request->all();
        $data['shop_id'] = auth()->user()->shop_id;
        $data['slug'] = Str::slug($request->name);
        

        // Handle main image
        if ($request->hasFile('product_image')) {
            $path = $request->file('product_image')->store('products/image', 'public');
            $data['product_image'] = $path;
        }

        // Create product
        $product = Product::create($data);

        // Handle gallery images
        if ($request->hasFile('gallery_images')) {
            $galleryPaths = [];
            foreach ($request->file('gallery_images') as $image) {
                $path = $image->store('products/gallery', 'public');
                $galleryPaths[] = $path;
            }
            
            // Save gallery images as JSON
            $product->gallery_images = json_encode($galleryPaths);
            $product->save();
        }

        return redirect()->route('products.index')
            ->with('success', 'Product created successfully!');
    }

    // Show edit form
    public function edit(Product $product)
{
    $this->authorizeShop($product);
    
    $categories = Category::where('shop_id', auth()->user()->shop_id)
        ->where('is_active', true)
        ->orderBy('name')
        ->get();
    
    $brands = Brand::where('shop_id', auth()->user()->shop_id)
        ->where('is_active', true)
        ->orderBy('name')
        ->get();
    
    $units = Unit::where('shop_id', auth()->user()->shop_id)
        ->where('is_active', true)
        ->orderBy('name')
        ->get();
    
    // Decode gallery images if exists
    if ($product->gallery_images && is_string($product->gallery_images)) {
        $product->gallery_images = json_decode($product->gallery_images, true);
    }
    
    return Inertia::render('Product/Edit', [
        'product' => $product,
        'categories' => $categories,
        'brands' => $brands,
        'units' => $units,
    ]);
}

// Update product
public function update(Request $request, Product $product)
{
    $this->authorizeShop($product);

    $request->validate([
        'name' => 'required|string|max:255',
        'sku' => 'required|string|max:100|unique:products,sku,' . $product->id . ',id,shop_id,' . auth()->user()->shop_id,
        'barcode' => 'nullable|string|max:100',
        'category_id' => 'required|exists:categories,id',
        'brand_id' => 'nullable|exists:brands,id',
        'unit_id' => 'required|exists:units,id',
        'purchase_price' => 'required|numeric|min:0',
        'selling_price' => 'required|numeric|min:0',
        'wholesale_price' => 'nullable|numeric|min:0',
        'stock_quantity' => 'required|integer|min:0',
        'min_stock_alert' => 'nullable|integer|min:0',
        'tax_rate' => 'nullable|numeric|min:0|max:100',
        'description' => 'nullable|string',
        'is_active' => 'boolean',
        'is_taxable' => 'boolean',
        'product_image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
        'gallery_images' => 'nullable|array',
        'gallery_images.*' => 'image|mimes:jpeg,png,jpg|max:5120',
        'images_to_delete' => 'nullable|string'
    ]);

    $data = $request->except(['product_image', 'gallery_images', 'images_to_delete']);
    $data['slug'] = Str::slug($request->name);
    
    

    // Handle main image
    if ($request->hasFile('product_image')) {
        // Delete old image
        if ($product->product_image) {
            Storage::disk('public')->delete($product->product_image);
        }
        $path = $request->file('product_image')->store('products/image', 'public');
        $data['product_image'] = $path;
    }

    // Handle gallery images
    $existingGallery = $product->gallery_images ? json_decode($product->gallery_images, true) : [];
    
    // Handle images to delete
    $imagesToDelete = [];
    if ($request->filled('images_to_delete')) {
        $imagesToDelete = json_decode($request->images_to_delete, true);
        foreach ($imagesToDelete as $image) {
            Storage::disk('public')->delete($image);
        }
        // Remove deleted images from existing gallery
        $existingGallery = array_values(array_diff($existingGallery, $imagesToDelete));
    }
    
    // Handle new gallery images
    $newGalleryImages = [];
    if ($request->hasFile('gallery_images')) {
        foreach ($request->file('gallery_images') as $image) {
            $path = $image->store('products/gallery', 'public');
            $newGalleryImages[] = $path;
        }
    }
    
    // Merge existing and new images
    $allGalleryImages = array_merge($existingGallery, $newGalleryImages);
    $data['gallery_images'] = json_encode($allGalleryImages);

    $product->update($data);

    return redirect()->route('products.index')
        ->with('success', 'Product updated successfully!');
}


    // Delete product
    public function destroy(Product $product)
    {
        $this->authorizeShop($product);
        
        // Delete main image
        if ($product->product_image) {
            Storage::disk('public')->delete($product->product_image);
        }
        
        // Delete gallery images
        if ($product->gallery_images) {
            $galleryImages = json_decode($product->gallery_images, true);
            foreach ($galleryImages as $image) {
                Storage::disk('public')->delete($image);
            }
        }
        
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully!');
    }

    // Toggle product status
    public function toggleStatus(Product $product)
    {
        $this->authorizeShop($product);
        
        $product->update(['is_active' => !$product->is_active]);

        return redirect()->route('products.index')
            ->with('success', 'Product status updated successfully!');
    }

    // Authorize shop
    private function authorizeShop($product)
    {
        if ($product->shop_id !== auth()->user()->shop_id) {
            abort(403, 'Unauthorized action.');
        }
    }
}