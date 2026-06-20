<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $shopId = auth()->user()->shop_id ?? 1;
        
        // Get current date
        $today = now()->toDateString();
        
        // Total stats
        $totalSales = Sale::where('shop_id', $shopId)->count();
        $totalRevenue = Sale::where('shop_id', $shopId)->sum('total_amount');
        $totalProfit = $this->calculateTotalProfit($shopId);
        $totalCustomers = Customer::where('shop_id', $shopId)->count();
        $totalProducts = Product::where('shop_id', $shopId)->count();
        $lowStockProducts = Product::where('shop_id', $shopId)
            ->whereRaw('stock_quantity <= min_stock_alert')
            ->count();
        
        // Today's stats
        $todaySales = Sale::where('shop_id', $shopId)
            ->whereDate('sale_date', $today)
            ->count();
        $todayRevenue = Sale::where('shop_id', $shopId)
            ->whereDate('sale_date', $today)
            ->sum('total_amount');
        
        // Recent sales (last 10)
        $recentSales = Sale::with(['customer'])
            ->where('shop_id', $shopId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Top products (last 30 days)
        $topProducts = SaleDetail::select(
                'products.id',
                'products.name',
                DB::raw('SUM(sale_details.quantity) as total_quantity'),
                DB::raw('SUM(sale_details.total_price) as total_revenue')
            )
            ->join('products', 'sale_details.product_id', '=', 'products.id')
            ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
            ->where('sales.shop_id', $shopId)
            ->whereDate('sales.sale_date', '>=', now()->subDays(30))
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_quantity', 'desc')
            ->limit(5)
            ->get();
        
        // Sales chart (last 7 days)
        $salesChart = $this->getSalesChart($shopId);
        
        // Payment methods distribution
        $paymentMethods = $this->getPaymentMethods($shopId);
        
        return Inertia::render('Dashboard', [
            'stats' => [
                'total_sales' => $totalSales,
                'total_revenue' => $totalRevenue,
                'total_profit' => $totalProfit,
                'total_customers' => $totalCustomers,
                'total_products' => $totalProducts,
                'low_stock_products' => $lowStockProducts,
                'today_sales' => $todaySales,
                'today_revenue' => $todayRevenue,
            ],
            'recent_sales' => $recentSales,
            'top_products' => $topProducts,
            'sales_chart' => $salesChart,
            'payment_methods' => $paymentMethods,
        ]);
    }
    
    private function calculateTotalProfit($shopId)
    {
        $totalProfit = 0;
        
        $sales = Sale::with('details.product')
            ->where('shop_id', $shopId)
            ->get();
        
        foreach ($sales as $sale) {
            foreach ($sale->details as $detail) {
                if ($detail->product) {
                    $costPrice = $detail->product->purchase_price ?? 0;
                    $profit = ($detail->unit_price - $costPrice) * $detail->quantity;
                    $totalProfit += $profit;
                }
            }
        }
        
        return $totalProfit;
    }
    
    private function getSalesChart($shopId)
    {
        $labels = [];
        $data = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $labels[] = now()->subDays($i)->format('D');
            
            $revenue = Sale::where('shop_id', $shopId)
                ->whereDate('sale_date', $date)
                ->sum('total_amount');
            
            $data[] = $revenue;
        }
        
        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }
    
    private function getPaymentMethods($shopId)
    {
        $methods = Sale::where('shop_id', $shopId)
            ->select('payment_method', DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get()
            ->pluck('count', 'payment_method')
            ->toArray();
        
        return [
            'cash' => $methods['cash'] ?? 0,
            'card' => $methods['card'] ?? 0,
            'mobile_banking' => $methods['mobile_banking'] ?? 0,
            'credit' => $methods['credit'] ?? 0,
        ];
    }
}