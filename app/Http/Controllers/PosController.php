<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PosController extends Controller
{
    public function index()
    {
        $products = Product::with('category')
            ->where('is_active', true)
            ->get();

        $customers = Customer::orderBy('name')->get();

        return Inertia::render('Pos/Index', [
            'products' => $products,
            'customers' => $customers,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'sale_type' => 'required|in:retail,wholesale',
            'customer_id' => 'nullable|exists:customers,id',
            'subtotal' => 'required|numeric|min:0',
            'discount' => 'required|numeric|min:0',
            'discount_type' => 'required|in:fixed,percentage',
            'tax' => 'required|numeric|min:0',
            'shipping_cost' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'due_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,mobile_banking,credit',
            'payment_status' => 'required|in:paid,partial,unpaid',
            'notes' => 'nullable|string',
            'sale_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total_price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $user = auth()->user();
            $shopId = $user->shop_id ?? 1;
            $invoiceNo = $this->generateInvoiceNumber($shopId);

            // Create sale
            $sale = Sale::create([
                'shop_id' => $shopId,
                'customer_id' => $request->customer_id,
                'user_id' => $user->id,
                'invoice_no' => $invoiceNo,
                'sale_date' => $request->sale_date,
                'sale_type' => $request->sale_type,
                'subtotal' => $request->subtotal,
                'discount' => $request->discount,
                'discount_type' => $request->discount_type,
                'tax' => $request->tax,
                'shipping_cost' => $request->shipping_cost,
                'total_amount' => $request->total_amount,
                'paid_amount' => $request->paid_amount,
                'due_amount' => $request->due_amount,
                'payment_method' => $request->payment_method,
                'payment_status' => $request->payment_status,
                'notes' => $request->notes,
            ]);

            // Create sale details and update stock
            foreach ($request->items as $item) {
                SaleDetail::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                ]);

                $product = Product::find($item['product_id']);
                if ($product) {
                    $product->stock_quantity = max(0, $product->stock_quantity - $item['quantity']);
                    $product->save();
                }
            }

            DB::commit();

            // সরাসরি Invoice পেজে রিডাইরেক্ট
            return redirect()->route('pos.invoice', $sale->id);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to complete sale: ' . $e->getMessage());
        }
    }

    public function showInvoice($id)
    {
        $sale = Sale::with(['customer', 'details.product', 'user'])
            ->findOrFail($id);

        // Get shop info from the sale's shop
        $shop = Shop::find($sale->shop_id);

        return Inertia::render('Pos/Invoice', [
            'sale' => $sale,
            'shop' => $shop,
        ]);
    }

    private function generateInvoiceNumber($shopId)
    {
        $prefix = 'INV-';
        $year = date('Y');
        $month = date('m');
        
        $lastSale = Sale::where('shop_id', $shopId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastSale) {
            $lastNumber = intval(substr($lastSale->invoice_no, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $year . $month . $newNumber;
    }
}