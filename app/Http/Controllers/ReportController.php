<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::with(['customer', 'user'])
            ->where('shop_id', auth()->user()->shop_id ?? 1);

        // Apply date filters
        if ($request->filled('start_date')) {
            $query->whereDate('sale_date', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->whereDate('sale_date', '<=', $request->end_date);
        }
        
        // Apply payment method filter
        if ($request->filled('payment_method') && $request->payment_method !== 'all') {
            $query->where('payment_method', $request->payment_method);
        }
        
        // Apply sale type filter
        if ($request->filled('sale_type') && $request->sale_type !== 'all') {
            $query->where('sale_type', $request->sale_type);
        }

        $sales = $query->orderBy('created_at', 'desc')->get();

        // Calculate totals
        $totalSales = $sales->count();
        $totalAmount = $sales->sum('total_amount');
        $totalProfit = $this->calculateTotalProfit($sales);

        // Get shop info
        $shop = Shop::find(auth()->user()->shop_id ?? 1);

        return Inertia::render('Pos/Report', [
            'sales' => $sales,
            'total_sales' => $totalSales,
            'total_amount' => $totalAmount,
            'total_profit' => $totalProfit,
            'shop' => $shop,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'payment_method' => $request->payment_method,
            'sale_type' => $request->sale_type,
        ]);
    }

    public function export(Request $request)
    {
        $query = Sale::with(['customer', 'user'])
            ->where('shop_id', auth()->user()->shop_id ?? 1);

        if ($request->filled('start_date')) {
            $query->whereDate('sale_date', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->whereDate('sale_date', '<=', $request->end_date);
        }
        
        if ($request->filled('payment_method') && $request->payment_method !== 'all') {
            $query->where('payment_method', $request->payment_method);
        }
        
        if ($request->filled('sale_type') && $request->sale_type !== 'all') {
            $query->where('sale_type', $request->sale_type);
        }

        $sales = $query->orderBy('created_at', 'desc')->get();

        $fileName = 'sales_report_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $callback = function() use ($sales) {
            $handle = fopen('php://output', 'w');
            
            fputcsv($handle, [
                'Invoice No', 'Date', 'Customer', 'Customer Phone', 'Sale Type',
                'Subtotal', 'Discount', 'Tax', 'Shipping', 'Total',
                'Paid', 'Due', 'Payment Method', 'Payment Status', 'Notes'
            ]);

            foreach ($sales as $sale) {
                fputcsv($handle, [
                    $sale->invoice_no,
                    $sale->sale_date->format('Y-m-d H:i:s'),
                    $sale->customer->name ?? 'Walk-in',
                    $sale->customer->phone ?? 'N/A',
                    $sale->sale_type,
                    number_format($sale->subtotal, 2),
                    number_format($sale->discount, 2),
                    number_format($sale->tax, 2),
                    number_format($sale->shipping_cost, 2),
                    number_format($sale->total_amount, 2),
                    number_format($sale->paid_amount, 2),
                    number_format($sale->due_amount, 2),
                    str_replace('_', ' ', $sale->payment_method),
                    ucfirst($sale->payment_status),
                    $sale->notes ?? ''
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function calculateTotalProfit($sales)
    {
        $totalProfit = 0;
        
        foreach ($sales as $sale) {
            $details = SaleDetail::with('product')
                ->where('sale_id', $sale->id)
                ->get();
                
            foreach ($details as $detail) {
                if ($detail->product) {
                    $costPrice = $detail->product->purchase_price ?? 0;
                    $profit = ($detail->unit_price - $costPrice) * $detail->quantity;
                    $totalProfit += $profit;
                }
            }
        }
        
        return $totalProfit;
    }
}