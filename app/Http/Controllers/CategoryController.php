<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('parent')
            ->where('shop_id', auth()->user()->shop_id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $parentCategories = Category::where('shop_id', auth()->user()->shop_id)
            ->whereNull('parent_id')
            ->get();

        return inertia('Categories/Index', [
            'categories' => $categories,
            'parentCategories' => $parentCategories,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();
        $data['shop_id'] = auth()->user()->shop_id;
        $data['slug'] = Str::slug($request->name);

        Category::create($data);

        return redirect()->route('categories.index')
            ->with('success', 'Category created successfully!');
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();
        $data['slug'] = Str::slug($request->name);

        $category->update($data);

        return redirect()->route('categories.index')
            ->with('success', 'Category updated successfully!');
    }

    public function destroy(Category $category)
    {
        // Check if category has products
        if ($category->products()->count() > 0) {
            return redirect()->route('categories.index')
                ->with('error', 'Cannot delete category with products!');
        }

        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Category deleted successfully!');
    }

    public function toggleStatus(Category $category)
    {
        $category->update(['is_active' => !$category->is_active]);

        return redirect()->route('categories.index')
            ->with('success', 'Category status updated!');
    }
}