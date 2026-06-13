<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Customer::where('shop_id', auth()->user()->shop_id);
        
        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('loyalty_card_number', 'like', "%{$search}%");
            });
        }
        
        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        
        // Loyalty level filter
        if ($request->filled('loyalty')) {
            switch ($request->loyalty) {
                case 'platinum':
                    $query->where('total_purchases', '>=', 50000);
                    break;
                case 'gold':
                    $query->whereBetween('total_purchases', [25000, 49999.99]);
                    break;
                case 'silver':
                    $query->whereBetween('total_purchases', [10000, 24999.99]);
                    break;
                case 'regular':
                    $query->where('total_purchases', '<', 10000);
                    break;
            }
        }
        
        $customers = $query->orderBy('created_at', 'desc')->paginate(10);
        
        return Inertia::render('Customer/Index', [
            'customers' => $customers,
        ]);
    }

     public function store(Request $request)
    {

    // dd($request->all());
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customers,phone,NULL,id,shop_id,' . auth()->user()->shop_id,
            'email' => 'nullable|email|max:255|unique:customers,email,NULL,id,shop_id,' . auth()->user()->shop_id,
            'address' => 'nullable|string',
            'loyalty_card_number' => 'nullable|string|max:50|unique:customers,loyalty_card_number,NULL,id,shop_id,' . auth()->user()->shop_id,
            'is_active' => 'boolean'
        ]);

        Customer::create([
            'shop_id' => auth()->user()->shop_id,
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'loyalty_card_number' => $request->loyalty_card_number,
            'is_active' => $request->is_active ?? true,
            'total_purchases' => 0,
            'total_due' => 0,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer created successfully!'
            ]);
        }

        return redirect()->route('customers.index')
            ->with('success', 'Customer created successfully!');
    }
    
      // Update customer
    public function update(Request $request, Customer $customer)
    {
        $this->authorizeShop($customer);

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customers,phone,' . $customer->id . ',id,shop_id,' . auth()->user()->shop_id,
            'email' => 'nullable|email|max:255|unique:customers,email,' . $customer->id . ',id,shop_id,' . auth()->user()->shop_id,
            'address' => 'nullable|string',
            'loyalty_card_number' => 'nullable|string|max:50|unique:customers,loyalty_card_number,' . $customer->id . ',id,shop_id,' . auth()->user()->shop_id,
            'is_active' => 'boolean'
        ]);

        $customer->update($request->all());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer updated successfully!',
                'customer' => $customer
            ]);
        }

        return redirect()->route('customers.index')
            ->with('success', 'Customer updated successfully!');
    }

    public function toggleStatus(Customer $customer)
    {
        $this->authorizeShop($customer);
        
        $customer->update(['is_active' => !$customer->is_active]);
        
        return redirect()->route('customers.index')
            ->with('success', 'Customer status updated successfully!');
    }
    
    // Delete customer
    public function destroy(Customer $customer)
    {
        // Check authorization
        $this->authorizeShop($customer);
        
        // Check if customer has any orders/invoices
        if ($customer->sales()->count() > 0) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete customer with purchase history! Please delete associated orders first.',
                    'has_orders' => true
                ], 422);
            }
            
            return redirect()->route('customers.index')
                ->with('error', 'Cannot delete customer with purchase history! Please delete associated orders first.');
        }
        
        // Check if customer has any due amount
        if ($customer->total_due > 0) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete customer with due amount! Please clear the due first.',
                    'has_due' => true,
                    'due_amount' => $customer->total_due
                ], 422);
            }
            
            return redirect()->route('customers.index')
                ->with('error', 'Cannot delete customer with due amount! Please clear the due first.');
        }
        
        // Store customer info for logging (optional)
        $customerInfo = [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email
        ];
        
        // Delete the customer
        $customer->delete();
        
        // You can add logging here
        // \Log::info('Customer deleted: ', $customerInfo);
        
        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Customer deleted successfully!',
                'deleted_customer' => $customerInfo
            ]);
        }
        
        return redirect()->route('customers.index')
            ->with('success', 'Customer deleted successfully!');
    }
    
    private function authorizeShop($customer)
    {
        if ($customer->shop_id !== auth()->user()->shop_id) {
            abort(403, 'Unauthorized action.');
        }
    }
}
