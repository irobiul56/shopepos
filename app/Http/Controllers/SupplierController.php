<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupplierController extends Controller
{
    // Display suppliers list
    public function index(Request $request)
    {
        $query = Supplier::where('shop_id', auth()->user()->shop_id);
        
        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        
        $suppliers = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return Inertia::render('Supplier/Index', [
            'suppliers' => $suppliers,
        ]);
    }

    // Store new supplier
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20|unique:suppliers,phone,NULL,id,shop_id,' . auth()->user()->shop_id,
            'email' => 'nullable|email|max:255|unique:suppliers,email,NULL,id,shop_id,' . auth()->user()->shop_id,
            'address' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        Supplier::create([
            'shop_id' => auth()->user()->shop_id,
            'name' => $request->name,
            'company_name' => $request->company_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'is_active' => $request->is_active ?? true,
            'total_due' => 0,
        ]);

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier created successfully!');
    }


    // Update supplier
    public function update(Request $request, Supplier $supplier)
    {
        $this->authorizeShop($supplier);

        $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20|unique:suppliers,phone,' . $supplier->id . ',id,shop_id,' . auth()->user()->shop_id,
            'email' => 'nullable|email|max:255|unique:suppliers,email,' . $supplier->id . ',id,shop_id,' . auth()->user()->shop_id,
            'address' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $supplier->update($request->all());

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier updated successfully!');
    }

    // Delete supplier
    public function destroy(Supplier $supplier)
    {
        $this->authorizeShop($supplier);
        
        // Check if supplier has any purchases
        if ($supplier->purchases()->count() > 0) {
            return redirect()->route('suppliers.index')
                ->with('error', 'Cannot delete supplier with purchase history!');
        }
        
        // Check if supplier has any due amount
        if ($supplier->total_due > 0) {
            return redirect()->route('suppliers.index')
                ->with('error', 'Cannot delete supplier with due amount! Please clear the due first.');
        }
        
        $supplier->delete();
        
        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier deleted successfully!');
    }

    // Toggle supplier status
    public function toggleStatus(Supplier $supplier)
    {
        $this->authorizeShop($supplier);
        
        $supplier->update(['is_active' => !$supplier->is_active]);
        
        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier status updated successfully!');
    }

    // Authorize shop
    private function authorizeShop($supplier)
    {
        if ($supplier->shop_id !== auth()->user()->shop_id) {
            abort(403, 'Unauthorized action.');
        }
    }
}