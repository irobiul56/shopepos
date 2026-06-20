<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

const { props } = usePage();

// Dashboard data from controller
const stats = ref(props.stats || {
    total_sales: 0,
    total_revenue: 0,
    total_profit: 0,
    total_customers: 0,
    total_products: 0,
    low_stock_products: 0,
    today_sales: 0,
    today_revenue: 0,
});

const recentSales = ref(props.recent_sales || []);
const topProducts = ref(props.top_products || []);
const salesChart = ref(props.sales_chart || { labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], data: [0, 0, 0, 0, 0, 0, 0] });
const paymentMethods = ref(props.payment_methods || { cash: 0, card: 0, mobile_banking: 0, credit: 0 });

// Format currency
const formatCurrency = (amount) => {
    return 'Tk ' + Number(amount).toFixed(2);
};

// Format date
const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Calculate percentages for payment methods
const paymentPercentages = computed(() => {
    const total = Object.values(paymentMethods.value).reduce((a, b) => a + b, 0);
    if (total === 0) return { cash: 0, card: 0, mobile_banking: 0, credit: 0 };
    return {
        cash: (paymentMethods.value.cash / total) * 100,
        card: (paymentMethods.value.card / total) * 100,
        mobile_banking: (paymentMethods.value.mobile_banking / total) * 100,
        credit: (paymentMethods.value.credit / total) * 100,
    };
});

// Get max value for chart
const maxChartValue = computed(() => {
    const data = salesChart.value.data || [];
    if (data.length === 0) return 100;
    const max = Math.max(...data);
    return max > 0 ? max : 100;
});

// Get status color
const getStatusColor = (status) => {
    const colors = {
        paid: 'bg-emerald-100 text-emerald-700',
        partial: 'bg-amber-100 text-amber-700',
        unpaid: 'bg-red-100 text-red-700',
    };
    return colors[status] || 'bg-gray-100 text-gray-700';
};

// View sale details
const viewSaleDetails = (saleId) => {
    router.visit(route('pos.invoice', saleId));
};

// Go to POS
const goToPos = () => {
    router.visit(route('pos.index'));
};

// Go to Report
const goToReport = () => {
    router.visit(route('report.unified'));
};

onMounted(() => {
    // Any initialization if needed
    console.log('Dashboard mounted');
    console.log('Sales Chart Data:', salesChart.value);
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Dashboard" />
        
        <div class="min-h-screen w-full bg-gray-50 p-4">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-chart-pie mr-2 text-indigo-600"></i> Dashboard
                        </h1>
                        <p class="text-sm text-gray-500">Welcome back! Here's your business overview.</p>
                    </div>
                    <div class="flex gap-2">
                        <button 
                            @click="goToPos"
                            class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition text-sm flex items-center gap-2"
                        >
                            <i class="fas fa-cash-register"></i> New Sale
                        </button>
                        <button 
                            @click="goToReport"
                            class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition text-sm flex items-center gap-2"
                        >
                            <i class="fas fa-chart-bar"></i> Reports
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
                    <!-- Total Sales -->
                    <div class="bg-white rounded-xl shadow-sm p-3 border-l-4 border-indigo-500 hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[10px] font-medium text-gray-500 uppercase tracking-wider">Total Sales</p>
                                <p class="text-xl font-bold text-gray-800">{{ stats.total_sales }}</p>
                            </div>
                            <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-shopping-cart text-indigo-600 text-sm"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue -->
                    <div class="bg-white rounded-xl shadow-sm p-3 border-l-4 border-emerald-500 hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[10px] font-medium text-gray-500 uppercase tracking-wider">Revenue</p>
                                <p class="text-xl font-bold text-gray-800">{{ formatCurrency(stats.total_revenue) }}</p>
                            </div>
                            <div class="w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-dollar-sign text-emerald-600 text-sm"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Profit -->
                    <div class="bg-white rounded-xl shadow-sm p-3 border-l-4 border-amber-500 hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[10px] font-medium text-gray-500 uppercase tracking-wider">Profit</p>
                                <p class="text-xl font-bold text-gray-800">{{ formatCurrency(stats.total_profit) }}</p>
                            </div>
                            <div class="w-8 h-8 bg-amber-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-coins text-amber-600 text-sm"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Today Sales -->
                    <div class="bg-white rounded-xl shadow-sm p-3 border-l-4 border-cyan-500 hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[10px] font-medium text-gray-500 uppercase tracking-wider">Today's Sales</p>
                                <p class="text-xl font-bold text-gray-800">{{ stats.today_sales }}</p>
                            </div>
                            <div class="w-8 h-8 bg-cyan-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-calendar-day text-cyan-600 text-sm"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Customers -->
                    <div class="bg-white rounded-xl shadow-sm p-3 border-l-4 border-purple-500 hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[10px] font-medium text-gray-500 uppercase tracking-wider">Customers</p>
                                <p class="text-xl font-bold text-gray-800">{{ stats.total_customers }}</p>
                            </div>
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-users text-purple-600 text-sm"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Low Stock Alert -->
                    <div class="bg-white rounded-xl shadow-sm p-3 border-l-4 border-rose-500 hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[10px] font-medium text-gray-500 uppercase tracking-wider">Low Stock</p>
                                <p class="text-xl font-bold text-gray-800">{{ stats.low_stock_products }}</p>
                            </div>
                            <div class="w-8 h-8 bg-rose-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-exclamation-triangle text-rose-600 text-sm"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <!-- Chart / Left Column (2/3) -->
                    <div class="lg:col-span-2 space-y-4">
                        <!-- Sales Chart -->
                        <div class="bg-white rounded-xl shadow-sm p-4">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-bold text-gray-700 text-sm">
                                    <i class="fas fa-chart-line mr-2 text-indigo-500"></i> Sales Overview
                                </h3>
                                <span class="text-xs text-gray-400">Last 7 days</span>
                            </div>
                            
                            <!-- Chart Bars -->
                            <div class="h-48 flex items-end justify-between gap-2">
                                <div 
                                    v-for="(value, index) in salesChart.data" 
                                    :key="index" 
                                    class="flex flex-col items-center flex-1 h-full justify-end"
                                >
                                    <!-- Bar with value label -->
                                    <div class="relative w-full flex justify-center">
                                        <span class="text-[10px] font-medium text-indigo-600 mb-1">
                                            {{ formatCurrency(value) }}
                                        </span>
                                    </div>
                                    <div 
                                        class="w-full bg-gradient-to-t from-indigo-500 to-indigo-400 rounded-t hover:opacity-80 transition-all duration-300"
                                        :style="{ 
                                            height: (maxChartValue > 0 ? (value / maxChartValue) * 100 : 0) + '%',
                                            minHeight: value > 0 ? '4px' : '0px'
                                        }"
                                    >
                                    </div>
                                    <span class="text-[10px] text-gray-500 mt-2 font-medium">
                                        {{ salesChart.labels[index] || '' }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Empty state -->
                            <div v-if="salesChart.data.every(v => v === 0)" class="text-center py-4 text-gray-400 text-sm">
                                <i class="fas fa-chart-simple text-2xl block mb-2"></i>
                                <p>No sales data available for the last 7 days</p>
                            </div>
                        </div>

                        <!-- Recent Sales Table -->
                        <div class="bg-white rounded-xl shadow-sm p-4">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-bold text-gray-700 text-sm">
                                    <i class="fas fa-clock mr-2 text-indigo-500"></i> Recent Sales
                                </h3>
                                <button @click="goToReport" class="text-xs text-indigo-600 hover:text-indigo-800">
                                    View All <i class="fas fa-arrow-right ml-1"></i>
                                </button>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase tracking-wider">Invoice</th>
                                            <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                            <th class="px-3 py-2 text-right text-[10px] font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                            <th class="px-3 py-2 text-center text-[10px] font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-3 py-2 text-center text-[10px] font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="sale in recentSales" :key="sale.id" class="hover:bg-gray-50 transition">
                                            <td class="px-3 py-2 text-xs font-medium text-gray-900">{{ sale.invoice_no }}</td>
                                            <td class="px-3 py-2 text-xs text-gray-600">
                                                {{ sale.customer?.name || 'Walk-in' }}
                                            </td>
                                            <td class="px-3 py-2 text-xs text-right font-medium">{{ formatCurrency(sale.total_amount) }}</td>
                                            <td class="px-3 py-2 text-center">
                                                <span class="px-2 py-0.5 text-[10px] rounded-full font-medium" :class="getStatusColor(sale.payment_status)">
                                                    {{ sale.payment_status }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <button @click="viewSaleDetails(sale.id)" class="text-indigo-600 hover:text-indigo-800 transition" title="View invoice">
                                                    <i class="fas fa-eye text-xs"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="recentSales.length === 0">
                                            <td colspan="5" class="px-3 py-6 text-center text-gray-400">
                                                <i class="fas fa-inbox text-xl block mb-1"></i>
                                                <p class="text-sm">No recent sales</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column (1/3) -->
                    <div class="space-y-4">
                        <!-- Today's Summary -->
                        <div class="bg-white rounded-xl shadow-sm p-4">
                            <h3 class="font-bold text-gray-700 text-sm mb-4">
                                <i class="fas fa-sun mr-2 text-amber-500"></i> Today's Summary
                            </h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center border-b pb-2">
                                    <span class="text-xs text-gray-500">Total Sales</span>
                                    <span class="text-sm font-bold text-gray-800">{{ stats.today_sales }}</span>
                                </div>
                                <div class="flex justify-between items-center border-b pb-2">
                                    <span class="text-xs text-gray-500">Revenue</span>
                                    <span class="text-sm font-bold text-emerald-600">{{ formatCurrency(stats.today_revenue) }}</span>
                                </div>
                                <div class="flex justify-between items-center border-b pb-2">
                                    <span class="text-xs text-gray-500">Average Sale</span>
                                    <span class="text-sm font-bold text-gray-800">
                                        {{ stats.today_sales > 0 ? formatCurrency(stats.today_revenue / stats.today_sales) : 'Tk 0.00' }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center pt-1">
                                    <span class="text-xs text-gray-500">Status</span>
                                    <span class="text-xs text-emerald-600 font-medium">
                                        <i class="fas fa-circle text-[6px] mr-1"></i> Active
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Top Products -->
                        <div class="bg-white rounded-xl shadow-sm p-4">
                            <h3 class="font-bold text-gray-700 text-sm mb-4">
                                <i class="fas fa-fire mr-2 text-orange-500"></i> Top Products
                            </h3>
                            <div class="space-y-3">
                                <div v-for="(product, index) in topProducts" :key="index" class="flex items-center gap-3">
                                    <div class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center text-[10px] font-bold text-indigo-600">
                                        {{ index + 1 }}
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-xs font-medium text-gray-800">{{ product.name }}</p>
                                        <div class="flex justify-between text-[10px] text-gray-400">
                                            <span>{{ product.total_quantity || 0 }} sold</span>
                                            <span>{{ formatCurrency(product.total_revenue || 0) }}</span>
                                        </div>
                                    </div>
                                    <div class="w-16 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-indigo-500 rounded-full" 
                                             :style="{ width: Math.min((product.total_quantity / (topProducts[0]?.total_quantity || 1)) * 100, 100) + '%' }">
                                        </div>
                                    </div>
                                </div>
                                <div v-if="topProducts.length === 0" class="text-center text-gray-400 text-xs py-4">
                                    No products sold yet
                                </div>
                            </div>
                        </div>

                        <!-- Payment Methods -->
                        <div class="bg-white rounded-xl shadow-sm p-4">
                            <h3 class="font-bold text-gray-700 text-sm mb-4">
                                <i class="fas fa-credit-card mr-2 text-purple-500"></i> Payment Methods
                            </h3>
                            <div class="space-y-2.5">
                                <div>
                                    <div class="flex justify-between text-xs mb-1">
                                        <span class="text-gray-600">Cash</span>
                                        <span class="text-gray-800 font-medium">{{ Math.round(paymentPercentages.cash) }}%</span>
                                    </div>
                                    <div class="w-full h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-emerald-500 rounded-full" :style="{ width: paymentPercentages.cash + '%' }"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-xs mb-1">
                                        <span class="text-gray-600">Card</span>
                                        <span class="text-gray-800 font-medium">{{ Math.round(paymentPercentages.card) }}%</span>
                                    </div>
                                    <div class="w-full h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-blue-500 rounded-full" :style="{ width: paymentPercentages.card + '%' }"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-xs mb-1">
                                        <span class="text-gray-600">Mobile Banking</span>
                                        <span class="text-gray-800 font-medium">{{ Math.round(paymentPercentages.mobile_banking) }}%</span>
                                    </div>
                                    <div class="w-full h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-purple-500 rounded-full" :style="{ width: paymentPercentages.mobile_banking + '%' }"></div>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-xs mb-1">
                                        <span class="text-gray-600">Credit</span>
                                        <span class="text-gray-800 font-medium">{{ Math.round(paymentPercentages.credit) }}%</span>
                                    </div>
                                    <div class="w-full h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-amber-500 rounded-full" :style="{ width: paymentPercentages.credit + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>