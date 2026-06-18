<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PurchaseController extends Controller
{

    public function index(Request $request)
    {
        $query = Purchase::with(['supplier', 'user', 'purchaseDetails.product'])
            ->where('shop_id', auth()->user()->shop_id);
        
        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                ->orWhereHas('supplier', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            });
        }
        
        // Supplier filter
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        
        // Payment status filter
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        
        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('purchase_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('purchase_date', '<=', $request->date_to);
        }
        
        $purchases = $query->orderBy('created_at', 'desc')->paginate(10);
        
        // Get suppliers for filter
        $suppliers = Supplier::where('shop_id', auth()->user()->shop_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return Inertia::render('Purchase/Index', [
            'purchases' => $purchases,
            'suppliers' => $suppliers
        ]);
    }

    public function create()
    {
        $suppliers = Supplier::where('shop_id', auth()->user()->shop_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        $products = Product::where('shop_id', auth()->user()->shop_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return Inertia::render('Purchase/Create', [
            'suppliers' => $suppliers,
            'products' => $products
        ]);
    }
    
    public function store(Request $request)
    {

    // dd($request->all());
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_no' => 'required|string|max:100|unique:purchases,invoice_no',
            'purchase_date' => 'required|date',
            'subtotal' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'due_amount' => 'nullable|numeric|min:0',
            'payment_status' => 'required|in:paid,partial,unpaid',
            'notes' => 'nullable|string',
            'purchase_details' => 'required|array|min:1',
            'purchase_details.*.product_id' => 'required|exists:products,id',
            'purchase_details.*.quantity' => 'required|numeric|min:1',
            'purchase_details.*.unit_price' => 'required|numeric|min:0',
            'purchase_details.*.total_price' => 'required|numeric|min:0',
        ]);
        
        // Create purchase
        $purchase = Purchase::create([
            'shop_id' => auth()->user()->shop_id,
            'supplier_id' => $request->supplier_id,
            'user_id' => auth()->user()->id,
            'invoice_no' => $request->invoice_no,
            'purchase_date' => $request->purchase_date,
            'subtotal' => $request->subtotal,
            'discount' => $request->discount ?? 0,
            'tax' => $request->tax ?? 0,
            'shipping_cost' => $request->shipping_cost ?? 0,
            'total_amount' => $request->total_amount,
            'paid_amount' => $request->paid_amount,
            'due_amount' => $request->due_amount ?? ($request->total_amount - $request->paid_amount),
            'payment_status' => $request->payment_status,
            'notes' => $request->notes,
        ]);
        
        // Create purchase details and update product stock
        foreach ($request->purchase_details as $item) {
            PurchaseDetail::create([
                'purchase_id' => $purchase->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['total_price']
            ]);
            
            // Update product stock and purchase price
            $product = Product::find($item['product_id']);
            $product->increment('stock_quantity', $item['quantity']);
            
            // Update purchase price if needed
            if ($product->purchase_price != $item['unit_price']) {
                $product->update(['purchase_price' => $item['unit_price']]);
            }
        }
        
        // Update supplier total due
        $supplier = Supplier::find($request->supplier_id);
        $dueAmount = $request->due_amount ?? ($request->total_amount - $request->paid_amount);
        $supplier->increment('total_due', $dueAmount);
        
        return redirect()->route('purchases.index')
            ->with('success', 'Purchase created successfully!');
    }

    public function show(Purchase $purchase)
{
    $this->authorizeShop($purchase);
    
    $purchase->load(['supplier', 'user', 'purchaseDetails.product']);
    
    if (request()->wantsJson()) {
        return response()->json([
            'purchase' => $purchase
        ]);
    }
    
    return Inertia::render('Purchase/Show', [
        'purchase' => $purchase
    ]);
}

    
    public function destroy(Purchase $purchase)
    {
        
        $this->authorizeShop($purchase);
            // Check if purchase can be deleted (only unpaid or partial)
            if ($purchase->payment_status === 'paid') {
                return redirect()->route('purchases.index')
                    ->with('error', 'Cannot delete paid purchase!');
            }
            
            // Restore product stock
            foreach ($purchase->purchaseDetails as $detail) {
                $product = $detail->product;
                $product->decrement('stock_quantity', $detail->quantity);
            }
            
            // Update supplier due
            $supplier = $purchase->supplier;
            $supplier->decrement('total_due', $purchase->due_amount);
            
            $purchase->delete();
            
            return redirect()->route('purchases.index')
                ->with('success', 'Purchase deleted successfully!');
        }

        private function authorizeShop($purchase)
        {
            if ($purchase->shop_id !== auth()->user()->shop_id) {
                abort(403, 'Unauthorized action.');
            }
        }

        // Get purchase data for printing
            public function printData(Purchase $purchase)
            {
                $this->authorizeShop($purchase);
                
                $purchase->load(['supplier', 'user', 'purchaseDetails.product.unit']);
                
                return response()->json([
                    'purchase' => $purchase
                ]);
            }

         public function reportData(Request $request)
{
    try {
        $query = Purchase::with(['supplier', 'user', 'purchaseDetails.product.unit'])
            ->where('shop_id', auth()->user()->shop_id);
        
        // Apply filters (same as before)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }
        
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('purchase_date', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('purchase_date', '<=', $request->date_to);
        }
        
        $purchases = $query->orderBy('purchase_date', 'desc')->get();
        
        // Get supplier name if filtered
        $supplierName = null;
        if ($request->filled('supplier_id')) {
            $supplier = Supplier::find($request->supplier_id);
            $supplierName = $supplier ? $supplier->name : null;
        }
        
        $summary = [
            'total_purchases' => $purchases->count(),
            'total_amount' => $purchases->sum('total_amount'),
            'total_paid' => $purchases->sum('paid_amount'),
            'total_due' => $purchases->sum('due_amount'),
        ];
        
        return response()->json([
            'success' => true,
            'purchases' => $purchases,
            'summary' => $summary,
            'shop_name' => auth()->user()->shop->name ?? auth()->user()->name . "'s Shop",
            'generated_by' => auth()->user()->name,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'supplier_name' => $supplierName,
            'payment_status' => $request->payment_status,
            'search' => $request->search,
        ]);
        
    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch report data: ' . $e->getMessage()
        ], 500);
    }
}
}