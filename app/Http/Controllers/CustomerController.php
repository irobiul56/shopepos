<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::where('shop_id', Auth::user()->shop_id);
        
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
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
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
        
        // Order by
        $query->orderBy('name');
        
        // Paginate results (10 per page)
        $customers = $query->paginate(10);
        
        // Add loyalty level to each customer
        $customers->getCollection()->transform(function ($customer) {
            $customer->loyalty_level = $this->getLoyaltyLevel($customer->total_purchases);
            return $customer;
        });
        
        return Inertia::render('Customer/Index', [
            'customers' => $customers,
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customers,phone',
            'email' => 'nullable|email|max:255|unique:customers,email',
            'address' => 'nullable|string|max:500',
            'loyalty_card_number' => 'nullable|string|max:50|unique:customers,loyalty_card_number',
            'is_active' => 'nullable|boolean'
        ]);
        
        try {
            // Generate unique loyalty card number if not provided
            $loyaltyCardNumber = $request->loyalty_card_number ?? $this->generateLoyaltyCardNumber();
            
            $customer = Customer::create([
                'shop_id' => Auth::user()->shop_id,
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
                'loyalty_card_number' => $loyaltyCardNumber,
                'total_purchases' => 0,
                'total_due' => 0,
                'is_active' => $request->is_active ?? true
            ]);
            
            return redirect()->route('customers.index')->with([
                'success' => 'Customer added successfully!'
            ]);
            
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Failed to add customer: ' . $e->getMessage()
            ]);
        }
    }
    
    public function update(Request $request, $id)
    {
        $customer = Customer::where('shop_id', Auth::user()->shop_id)
            ->findOrFail($id);
            
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:customers,phone,' . $id,
            'email' => 'nullable|email|max:255|unique:customers,email,' . $id,
            'address' => 'nullable|string|max:500',
            'loyalty_card_number' => 'nullable|string|max:50|unique:customers,loyalty_card_number,' . $id,
            'is_active' => 'nullable|boolean'
        ]);
        
        try {
            $customer->update([
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
                'loyalty_card_number' => $request->loyalty_card_number ?? $customer->loyalty_card_number,
                'is_active' => $request->is_active ?? $customer->is_active
            ]);
            
            return redirect()->route('customers.index')->with([
                'success' => 'Customer updated successfully!'
            ]);
            
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Failed to update customer: ' . $e->getMessage()
            ]);
        }
    }
    
    public function destroy($id)
    {
        $customer = Customer::where('shop_id', Auth::user()->shop_id)
            ->findOrFail($id);
            
        // Check if customer has purchases
        if ($customer->sales()->exists()) {
            return back()->withErrors([
                'error' => 'Cannot delete customer with purchase history.'
            ]);
        }
            
        try {
            $customer->delete();
            
            return redirect()->route('customers.index')->with([
                'success' => 'Customer deleted successfully!'
            ]);
            
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Failed to delete customer: ' . $e->getMessage()
            ]);
        }
    }
    
    public function show($id)
    {
        $customer = Customer::where('shop_id', Auth::user()->shop_id)
            ->with(['sales' => function($query) {
                $query->orderBy('created_at', 'desc')->limit(10);
            }])
            ->findOrFail($id);
            
        return Inertia::render('Customer/Show', [
            'customer' => $customer,
        ]);
    }
    
    public function toggleStatus($id)
    {
        $customer = Customer::where('shop_id', Auth::user()->shop_id)
            ->findOrFail($id);
            
        try {
            $customer->update([
                'is_active' => !$customer->is_active
            ]);
            
            $status = $customer->is_active ? 'activated' : 'deactivated';
            
            return redirect()->route('customers.index')->with([
                'success' => "Customer {$status} successfully!"
            ]);
            
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Failed to toggle customer status: ' . $e->getMessage()
            ]);
        }
    }
    
    public function updateLoyaltyCard($id)
    {
        $customer = Customer::where('shop_id', Auth::user()->shop_id)
            ->findOrFail($id);
            
        try {
            $newCardNumber = $this->generateLoyaltyCardNumber();
            $customer->update([
                'loyalty_card_number' => $newCardNumber
            ]);
            
            return redirect()->route('customers.index')->with([
                'success' => 'Loyalty card number updated successfully!'
            ]);
            
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Failed to update loyalty card: ' . $e->getMessage()
            ]);
        }
    }
    
    private function generateLoyaltyCardNumber()
    {
        $prefix = 'LYL';
        $number = $prefix . strtoupper(Str::random(8));
        
        // Check if exists and regenerate if needed
        while (Customer::where('loyalty_card_number', $number)->exists()) {
            $number = $prefix . strtoupper(Str::random(8));
        }
        
        return $number;
    }
    
    private function getLoyaltyLevel($totalPurchases)
    {
        if ($totalPurchases >= 50000) return 'Platinum';
        if ($totalPurchases >= 25000) return 'Gold';
        if ($totalPurchases >= 10000) return 'Silver';
        return 'Regular';
    }
}