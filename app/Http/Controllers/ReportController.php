<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Payment;
use App\Models\Shop;
use App\Models\SaleDetail;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function unified(Request $request)
    {
        $shopId = auth()->user()->shop_id ?? 1;
        $shop = Shop::find($shopId);
        
        // ============ SALES DATA ============
        $salesQuery = Sale::with(['customer', 'user', 'details.product'])
            ->where('shop_id', $shopId);
        
        if ($request->filled('start_date')) {
            $salesQuery->whereDate('sale_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $salesQuery->whereDate('sale_date', '<=', $request->end_date);
        }
        if ($request->filled('sale_type') && $request->sale_type !== 'all') {
            $salesQuery->where('sale_type', $request->sale_type);
        }
        
        $sales = $salesQuery->orderBy('created_at', 'desc')->get();
        
        // Calculate total sales and revenue
        $totalSales = $sales->count();
        $totalRevenue = $sales->sum('total_amount');
        
        // Calculate total profit from sale details
        $totalProfit = $this->calculateTotalProfit($sales);
        
        // Add profit to each sale object
        $salesWithProfit = $sales->map(function($sale) {
            $sale->profit = $this->calculateSaleProfit($sale);
            return $sale;
        });
        
        // ============ PAYMENT DATA ============
        $paymentQuery = Payment::with(['sale.customer', 'user', 'shop'])
            ->where('shop_id', $shopId);
        
        if ($request->filled('start_date')) {
            $paymentQuery->whereDate('payment_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $paymentQuery->whereDate('payment_date', '<=', $request->end_date);
        }
        if ($request->filled('payment_method') && $request->payment_method !== 'all') {
            $paymentQuery->where('payment_method', $request->payment_method);
        }
        
        $payments = $paymentQuery->orderBy('payment_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $totalPayments = $payments->count();
        $totalPaymentAmount = $payments->sum('amount');
        
        $paymentSummary = $payments->groupBy('payment_method')
            ->map(function($group) {
                return $group->count();
            })
            ->toArray();
        
        $paymentMethodAmounts = $payments->groupBy('payment_method')
            ->map(function($group) {
                return $group->sum('amount');
            })
            ->toArray();
        
        return Inertia::render('Report/Unified', [
            'sales' => $salesWithProfit,
            'total_sales' => $totalSales,
            'total_revenue' => $totalRevenue,
            'total_profit' => $totalProfit,
            'payments' => $payments,
            'total_payments' => $totalPayments,
            'total_payment_amount' => $totalPaymentAmount,
            'payment_summary' => $paymentSummary,
            'payment_method_amounts' => $paymentMethodAmounts,
            'shop' => $shop,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'payment_method' => $request->payment_method,
            'sale_type' => $request->sale_type,
        ]);
    }
    
    /**
     * Calculate profit for a single sale
     */
    private function calculateSaleProfit($sale)
    {
        $profit = 0;
        
        // If details are not loaded, load them
        if (!$sale->relationLoaded('details')) {
            $sale->load('details.product');
        }
        
        foreach ($sale->details as $detail) {
            if ($detail->product) {
                $costPrice = $detail->product->purchase_price ?? 0;
                $profit += ($detail->unit_price - $costPrice) * $detail->quantity;
            }
        }
        
        return $profit;
    }
    
    /**
     * Calculate total profit for all sales
     */
    private function calculateTotalProfit($sales)
    {
        $totalProfit = 0;
        
        foreach ($sales as $sale) {
            $totalProfit += $this->calculateSaleProfit($sale);
        }
        
        return $totalProfit;
    }
    
    public function export(Request $request)
    {
        $shopId = auth()->user()->shop_id ?? 1;
        $type = $request->get('type', 'sales');
        
        if ($type === 'sales') {
            return $this->exportSales($request, $shopId);
        } else {
            return $this->exportPayments($request, $shopId);
        }
    }
    
    private function exportSales($request, $shopId)
    {
        $query = Sale::with(['customer', 'user', 'details.product'])
            ->where('shop_id', $shopId);
        
        if ($request->filled('start_date')) {
            $query->whereDate('sale_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('sale_date', '<=', $request->end_date);
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
                'Subtotal', 'Discount', 'Tax', 'Shipping', 'Total', 'Profit',
                'Paid', 'Due', 'Payment Method', 'Payment Status', 'Notes'
            ]);
            
            foreach ($sales as $sale) {
                // Calculate profit for this sale
                $saleProfit = 0;
                foreach ($sale->details as $detail) {
                    if ($detail->product) {
                        $costPrice = $detail->product->purchase_price ?? 0;
                        $saleProfit += ($detail->unit_price - $costPrice) * $detail->quantity;
                    }
                }
                
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
                    number_format($saleProfit, 2),
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
    
    private function exportPayments($request, $shopId)
    {
        $query = Payment::with(['sale.customer', 'user'])
            ->where('shop_id', $shopId);
        
        if ($request->filled('start_date')) {
            $query->whereDate('payment_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('payment_date', '<=', $request->end_date);
        }
        if ($request->filled('payment_method') && $request->payment_method !== 'all') {
            $query->where('payment_method', $request->payment_method);
        }
        
        $payments = $query->orderBy('payment_date', 'desc')->get();
        
        $fileName = 'payment_report_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];
        
        $callback = function() use ($payments) {
            $handle = fopen('php://output', 'w');
            
            fputcsv($handle, [
                'SL', 'Invoice', 'Customer', 'Customer Phone', 'Payment Date',
                'Payment Method', 'Amount', 'Transaction ID', 'Collected By', 'Notes'
            ]);
            
            $sl = 1;
            foreach ($payments as $payment) {
                fputcsv($handle, [
                    $sl++,
                    $payment->sale->invoice_no ?? 'N/A',
                    $payment->sale->customer->name ?? 'Walk-in',
                    $payment->sale->customer->phone ?? 'N/A',
                    $payment->payment_date->format('Y-m-d'),
                    str_replace('_', ' ', $payment->payment_method),
                    number_format($payment->amount, 2),
                    $payment->transaction_id ?? 'N/A',
                    $payment->user->name ?? 'N/A',
                    $payment->notes ?? '',
                ]);
            }
            
            fclose($handle);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}