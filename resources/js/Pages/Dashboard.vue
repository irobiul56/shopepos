<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, nextTick } from 'vue';

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
const salesChart = ref(props.sales_chart || { 
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'], 
    data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0] 
});
const paymentMethods = ref(props.payment_methods || { cash: 0, card: 0, mobile_banking: 0, credit: 0 });
const shop = ref(props.shop || null);

// Chart ref
const chartContainer = ref(null);
const tooltipRef = ref(null);
const chartData = ref([]);

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
    return max > 0 ? max * 1.2 : 100;
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

// Go to Due Collection
const goToduecollection = () => {
    router.visit(route('due-collection.index'));
};

// ============================================================
// SMART LINE GRAPH
// ============================================================
const renderChart = () => {
    const container = chartContainer.value;
    if (!container) return;
    
    const data = salesChart.value.data || [];
    const labels = salesChart.value.labels || [];
    
    if (data.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-gray-400 text-sm">
                <i class="fas fa-chart-simple text-3xl block mb-2 opacity-50"></i>
                <p>No sales data available</p>
            </div>
        `;
        return;
    }
    
    const maxVal = Math.max(...data, 1);
    const padding = { top: 30, bottom: 30, left: 10, right: 10 };
    const width = container.clientWidth - padding.left - padding.right;
    const height = 200 - padding.top - padding.bottom;
    
    // Create SVG
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('width', '100%');
    svg.setAttribute('height', '100%');
    svg.setAttribute('viewBox', `0 0 ${container.clientWidth} 220`);
    svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
    svg.style.overflow = 'visible';
    
    // Defs for gradient
    const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
    const gradient = document.createElementNS('http://www.w3.org/2000/svg', 'linearGradient');
    gradient.id = 'areaGradient';
    gradient.setAttribute('x1', '0%');
    gradient.setAttribute('y1', '0%');
    gradient.setAttribute('x2', '0%');
    gradient.setAttribute('y2', '100%');
    
    const stop1 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
    stop1.setAttribute('offset', '0%');
    stop1.setAttribute('stop-color', '#4f46e5');
    stop1.setAttribute('stop-opacity', '0.3');
    
    const stop2 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
    stop2.setAttribute('offset', '100%');
    stop2.setAttribute('stop-color', '#4f46e5');
    stop2.setAttribute('stop-opacity', '0');
    
    gradient.appendChild(stop1);
    gradient.appendChild(stop2);
    defs.appendChild(gradient);
    svg.appendChild(defs);
    
    // Calculate points
    const points = data.map((value, index) => {
        const x = padding.left + (index / (data.length - 1 || 1)) * width;
        const y = padding.top + height - (value / maxVal) * height;
        return { x, y, value, label: labels[index] || '' };
    });
    
    // Draw area
    const areaPath = points.map((p, i) => {
        return (i === 0 ? 'M' : 'L') + p.x + ',' + p.y;
    }).join(' ') + ' L' + points[points.length-1].x + ',' + (padding.top + height) + ' L' + points[0].x + ',' + (padding.top + height) + ' Z';
    
    const area = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    area.setAttribute('d', areaPath);
    area.setAttribute('fill', 'url(#areaGradient)');
    area.setAttribute('opacity', '0.8');
    svg.appendChild(area);
    
    // Draw line
    const linePath = points.map((p, i) => {
        return (i === 0 ? 'M' : 'L') + p.x + ',' + p.y;
    }).join(' ');
    
    const line = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    line.setAttribute('d', linePath);
    line.setAttribute('stroke', '#4f46e5');
    line.setAttribute('stroke-width', '3');
    line.setAttribute('fill', 'none');
    line.setAttribute('stroke-linecap', 'round');
    line.setAttribute('stroke-linejoin', 'round');
    svg.appendChild(line);
    
    // Draw points with tooltips
    points.forEach((point, index) => {
        const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        
        const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('cx', point.x);
        circle.setAttribute('cy', point.y);
        circle.setAttribute('r', '4');
        circle.setAttribute('fill', '#4f46e5');
        circle.setAttribute('stroke', '#fff');
        circle.setAttribute('stroke-width', '2');
        circle.setAttribute('style', 'cursor: pointer; transition: all 0.2s;');
        
        // Tooltip on hover
        circle.addEventListener('mouseenter', function(e) {
            const tooltip = document.getElementById('chartTooltip');
            if (tooltip) {
                const rect = container.getBoundingClientRect();
                tooltip.style.left = (rect.left + point.x + 10) + 'px';
                tooltip.style.top = (rect.top + point.y - 30) + 'px';
                tooltip.textContent = `${point.label}: ${formatCurrency(point.value)}`;
                tooltip.classList.remove('hidden');
            }
            this.setAttribute('r', '8');
            this.setAttribute('fill', '#818cf8');
        });
        
        circle.addEventListener('mouseleave', function() {
            const tooltip = document.getElementById('chartTooltip');
            if (tooltip) {
                tooltip.classList.add('hidden');
            }
            this.setAttribute('r', '4');
            this.setAttribute('fill', '#4f46e5');
        });
        
        g.appendChild(circle);
        
        // X-axis label
        if (index % 2 === 0 || index === data.length - 1) {
            const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            text.setAttribute('x', point.x);
            text.setAttribute('y', padding.top + height + 18);
            text.setAttribute('text-anchor', 'middle');
            text.setAttribute('fill', '#9ca3af');
            text.setAttribute('font-size', '8');
            text.setAttribute('font-family', 'Inter, sans-serif');
            text.textContent = point.label;
            g.appendChild(text);
        }
        
        svg.appendChild(g);
    });
    
    // Clean previous and add new
    container.innerHTML = '';
    container.appendChild(svg);
    
    // Add tooltip element if not exists
    if (!document.getElementById('chartTooltip')) {
        const tooltip = document.createElement('div');
        tooltip.id = 'chartTooltip';
        tooltip.className = 'fixed hidden bg-gray-900 text-white text-xs px-3 py-1.5 rounded-lg shadow-lg z-50 pointer-events-none transition-opacity duration-200';
        tooltip.style.transform = 'translateY(-100%)';
        document.body.appendChild(tooltip);
    }
};

// ============================================================
// LIFECYCLE
// ============================================================
onMounted(() => {
    console.log('Dashboard mounted');
    console.log('Sales Chart Data:', salesChart.value);
    
    // Render chart after DOM is ready
    nextTick(() => {
        renderChart();
        
        // Re-render on resize
        const resizeObserver = new ResizeObserver(() => {
            renderChart();
        });
        if (chartContainer.value) {
            resizeObserver.observe(chartContainer.value);
        }
    });
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
                        <p class="text-sm text-gray-500">
                            Welcome back! Here's your business overview. 
                            <span class="text-indigo-600 font-medium">{{ shop?.name || '' }}</span>
                        </p>
                    </div>
                    <div class="flex gap-2 flex-wrap">
                        <button 
                            @click="goToPos"
                            class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition text-sm flex items-center gap-2 shadow-sm"
                        >
                            <i class="fas fa-cash-register"></i> New Sale
                        </button>
                        <button 
                            @click="goToReport"
                            class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition text-sm flex items-center gap-2 shadow-sm"
                        >
                            <i class="fas fa-chart-bar"></i> Reports
                        </button>
                        <button 
                            @click="goToduecollection"
                            class="bg-amber-600 text-white px-4 py-2 rounded-lg hover:bg-amber-700 transition text-sm flex items-center gap-2 shadow-sm"
                        >
                            <i class="fas fa-hand-holding-usd"></i> Due Collection
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
                    <div class="bg-white rounded-xl shadow-sm p-3 border-l-4 border-indigo-500 hover:shadow-md transition group">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[10px] font-medium text-gray-500 uppercase tracking-wider">Total Sales</p>
                                <p class="text-xl font-bold text-gray-800">{{ stats.total_sales }}</p>
                            </div>
                            <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center group-hover:scale-110 transition">
                                <i class="fas fa-shopping-cart text-indigo-600 text-sm"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-3 border-l-4 border-emerald-500 hover:shadow-md transition group">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[10px] font-medium text-gray-500 uppercase tracking-wider">Revenue</p>
                                <p class="text-xl font-bold text-gray-800">{{ formatCurrency(stats.total_revenue) }}</p>
                            </div>
                            <div class="w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center group-hover:scale-110 transition">
                                <i class="fas fa-dollar-sign text-emerald-600 text-sm"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-3 border-l-4 border-amber-500 hover:shadow-md transition group">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[10px] font-medium text-gray-500 uppercase tracking-wider">Profit</p>
                                <p class="text-xl font-bold text-gray-800">{{ formatCurrency(stats.total_profit) }}</p>
                            </div>
                            <div class="w-8 h-8 bg-amber-100 rounded-full flex items-center justify-center group-hover:scale-110 transition">
                                <i class="fas fa-coins text-amber-600 text-sm"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-3 border-l-4 border-cyan-500 hover:shadow-md transition group">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[10px] font-medium text-gray-500 uppercase tracking-wider">Today's Sales</p>
                                <p class="text-xl font-bold text-gray-800">{{ stats.today_sales }}</p>
                            </div>
                            <div class="w-8 h-8 bg-cyan-100 rounded-full flex items-center justify-center group-hover:scale-110 transition">
                                <i class="fas fa-calendar-day text-cyan-600 text-sm"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-3 border-l-4 border-purple-500 hover:shadow-md transition group">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[10px] font-medium text-gray-500 uppercase tracking-wider">Customers</p>
                                <p class="text-xl font-bold text-gray-800">{{ stats.total_customers }}</p>
                            </div>
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center group-hover:scale-110 transition">
                                <i class="fas fa-users text-purple-600 text-sm"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-3 border-l-4 border-rose-500 hover:shadow-md transition group">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[10px] font-medium text-gray-500 uppercase tracking-wider">Low Stock</p>
                                <p class="text-xl font-bold text-gray-800">{{ stats.low_stock_products }}</p>
                            </div>
                            <div class="w-8 h-8 bg-rose-100 rounded-full flex items-center justify-center group-hover:scale-110 transition">
                                <i class="fas fa-exclamation-triangle text-rose-600 text-sm"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <!-- Chart / Left Column (2/3) -->
                    <div class="lg:col-span-2 space-y-4">
                        <!-- Sales Chart with Smart Line Graph -->
                        <div class="bg-white rounded-xl shadow-sm p-4">
                            <div class="flex justify-between items-center mb-4">
                                <div>
                                    <h3 class="font-bold text-gray-700 text-sm">
                                        <i class="fas fa-chart-line mr-2 text-indigo-500"></i> Sales Overview
                                    </h3>
                                    <p class="text-[10px] text-gray-400">Monthly sales trend</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] text-gray-400">Last 12 months</span>
                                </div>
                            </div>
                            
                            <!-- Chart Container -->
                            <div class="relative">
                                <div ref="chartContainer" class="w-full h-[220px]"></div>
                            </div>
                            
                            <!-- Chart Stats -->
                            <div v-if="salesChart.data.some(v => v > 0)" class="grid grid-cols-3 gap-3 mt-3 pt-3 border-t border-gray-100">
                                <div>
                                    <span class="text-[10px] text-gray-400">Highest</span>
                                    <p class="text-sm font-bold text-gray-800">{{ formatCurrency(Math.max(...salesChart.data)) }}</p>
                                </div>
                                <div class="text-center">
                                    <span class="text-[10px] text-gray-400">Average</span>
                                    <p class="text-sm font-bold text-gray-800">
                                        {{ formatCurrency(salesChart.data.reduce((a, b) => a + b, 0) / (salesChart.data.length || 1)) }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] text-gray-400">Total</span>
                                    <p class="text-sm font-bold text-indigo-600">{{ formatCurrency(salesChart.data.reduce((a, b) => a + b, 0)) }}</p>
                                </div>
                            </div>
                            
                            <!-- Empty state -->
                            <div v-if="salesChart.data.every(v => v === 0)" class="text-center py-4 text-gray-400 text-sm">
                                <i class="fas fa-chart-simple text-2xl block mb-2 opacity-50"></i>
                                <p>No sales data available</p>
                            </div>
                        </div>

                        <!-- Recent Sales Table -->
                        <div class="bg-white rounded-xl shadow-sm p-4">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="font-bold text-gray-700 text-sm">
                                    <i class="fas fa-clock mr-2 text-indigo-500"></i> Recent Sales
                                </h3>
                                <button @click="goToReport" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
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
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold text-white" 
                                         :class="[
                                             index === 0 ? 'bg-amber-500' : 
                                             index === 1 ? 'bg-gray-400' : 
                                             index === 2 ? 'bg-amber-700' : 'bg-indigo-400'
                                         ]">
                                        {{ index + 1 }}
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-xs font-medium text-gray-800 truncate">{{ product.name }}</p>
                                        <div class="flex justify-between text-[10px] text-gray-400">
                                            <span>{{ product.total_quantity || 0 }} sold</span>
                                            <span>{{ formatCurrency(product.total_revenue || 0) }}</span>
                                        </div>
                                    </div>
                                    <div class="w-16 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500" 
                                             :class="[
                                                 index === 0 ? 'bg-amber-500' : 
                                                 index === 1 ? 'bg-gray-400' : 
                                                 index === 2 ? 'bg-amber-700' : 'bg-indigo-400'
                                             ]"
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
                                <div v-for="(method, key) in { cash: 'Cash', card: 'Card', mobile_banking: 'Mobile Banking', credit: 'Credit' }" :key="key">
                                    <div class="flex justify-between text-xs mb-1">
                                        <span class="text-gray-600">{{ method }}</span>
                                        <span class="text-gray-800 font-medium">
                                            {{ paymentMethods[key] || 0 }} 
                                            <span class="text-gray-400 font-normal">({{ Math.round(paymentPercentages[key] || 0) }}%)</span>
                                        </span>
                                    </div>
                                    <div class="w-full h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500" 
                                             :class="{
                                                 'bg-emerald-500': key === 'cash',
                                                 'bg-blue-500': key === 'card',
                                                 'bg-purple-500': key === 'mobile_banking',
                                                 'bg-amber-500': key === 'credit'
                                             }"
                                             :style="{ width: (paymentPercentages[key] || 0) + '%' }">
                                        </div>
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

<style scoped>
/* Chart tooltip */
#chartTooltip {
    transition: opacity 0.2s ease;
    white-space: nowrap;
    font-family: 'Inter', sans-serif;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(8px);
}
</style>