<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';

const { props } = usePage();

// Props from controller
const sales = ref(props.sales || []);
const totalSales = ref(props.total_sales || 0);
const totalAmount = ref(props.total_amount || 0);
const totalProfit = ref(props.total_profit || 0);
const shop = ref(props.shop || null);

// Filter states
const filters = ref({
    start_date: props.start_date || '',
    end_date: props.end_date || '',
    payment_method: props.payment_method || 'all',
    sale_type: props.sale_type || 'all',
    search: '',
});

const currentPage = ref(1);
const itemsPerPage = ref(15);
const isFiltering = ref(false);

// Computed filtered sales
const filteredSales = computed(() => {
    let filtered = [...sales.value];
    
    if (filters.value.search) {
        const query = filters.value.search.toLowerCase().trim();
        filtered = filtered.filter(sale =>
            sale.invoice_no?.toLowerCase().includes(query) ||
            sale.customer?.name?.toLowerCase().includes(query) ||
            sale.customer?.phone?.includes(query)
        );
    }
    
    return filtered;
});

// Pagination
const totalPages = computed(() => {
    return Math.ceil(filteredSales.value.length / itemsPerPage.value);
});

const paginatedSales = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredSales.value.slice(start, end);
});

// Summary statistics
const summary = computed(() => {
    const filtered = filteredSales.value;
    const total = filtered.reduce((sum, sale) => sum + Number(sale.total_amount || 0), 0);
    const count = filtered.length;
    const profit = filtered.reduce((sum, sale) => sum + Number(sale.profit || 0), 0);
    
    return {
        total_sales: count,
        total_amount: total,
        total_profit: profit,
        average_sale: count > 0 ? total / count : 0
    };
});

// Format currency
const formatCurrency = (amount) => {
    return 'BDT ' + Number(amount).toFixed(2);
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

// Apply filters
const applyFilters = () => {
    isFiltering.value = true;
    
    const params = new URLSearchParams();
    
    if (filters.value.start_date) {
        params.append('start_date', filters.value.start_date);
    }
    if (filters.value.end_date) {
        params.append('end_date', filters.value.end_date);
    }
    if (filters.value.payment_method && filters.value.payment_method !== 'all') {
        params.append('payment_method', filters.value.payment_method);
    }
    if (filters.value.sale_type && filters.value.sale_type !== 'all') {
        params.append('sale_type', filters.value.sale_type);
    }
    
    const url = route('pos.report') + '?' + params.toString();
    
    router.visit(url, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
            isFiltering.value = false;
            sales.value = page.props.sales || [];
            totalSales.value = page.props.total_sales || 0;
            totalAmount.value = page.props.total_amount || 0;
            totalProfit.value = page.props.total_profit || 0;
            shop.value = page.props.shop || null;
            currentPage.value = 1;
        },
        onError: () => {
            isFiltering.value = false;
        }
    });
};

// Reset filters
const resetFilters = () => {
    filters.value = {
        start_date: '',
        end_date: '',
        payment_method: 'all',
        sale_type: 'all',
        search: '',
    };
    currentPage.value = 1;
    applyFilters();
};

// Export report
const exportReport = () => {
    const params = new URLSearchParams();
    
    if (filters.value.start_date) {
        params.append('start_date', filters.value.start_date);
    }
    if (filters.value.end_date) {
        params.append('end_date', filters.value.end_date);
    }
    if (filters.value.payment_method && filters.value.payment_method !== 'all') {
        params.append('payment_method', filters.value.payment_method);
    }
    if (filters.value.sale_type && filters.value.sale_type !== 'all') {
        params.append('sale_type', filters.value.sale_type);
    }
    
    const url = route('pos.report.export') + '?' + params.toString();
    window.open(url, '_blank');
};

// Print report - One row summary
const printReport = () => {
    const printContent = document.getElementById('report-print-content');
    if (!printContent) {
        alert('Report content not found');
        return;
    }
    
    const printWindow = window.open('', '_blank', 'width=1000,height=800');
    if (!printWindow) {
        alert('Please allow popups for printing');
        return;
    }
    
    // Get shop information
    const shopName = shop.value?.name || 'Your Shop';
    const shopAddress = shop.value?.address || 'Shop Address';
    const shopPhone = shop.value?.phone || 'Phone Number';
    const shopEmail = shop.value?.email || 'Email Address';
    
    // Get summary data
    const totalSalesCount = summary.value.total_sales;
    const totalRevenue = formatCurrency(summary.value.total_amount);
    const totalProfitAmount = formatCurrency(summary.value.total_profit);
    const averageSaleAmount = formatCurrency(summary.value.average_sale);
    
    // Get filter info
    let filterInfo = [];
    if (filters.value.start_date) filterInfo.push(`From: ${filters.value.start_date}`);
    if (filters.value.end_date) filterInfo.push(`To: ${filters.value.end_date}`);
    if (filters.value.payment_method && filters.value.payment_method !== 'all') {
        filterInfo.push(`Payment: ${filters.value.payment_method}`);
    }
    if (filters.value.sale_type && filters.value.sale_type !== 'all') {
        filterInfo.push(`Type: ${filters.value.sale_type}`);
    }
    const filterText = filterInfo.length > 0 ? filterInfo.join(' | ') : 'All Sales';
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Sales Report - ${shopName}</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { 
                    font-family: 'Courier New', Courier, monospace; 
                    font-size: 11px; 
                    padding: 15px; 
                    background: white;
                    color: #000;
                }
                .report-container { 
                    max-width: 1100px; 
                    margin: 0 auto; 
                }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .text-left { text-align: left; }
                .fw-bold { font-weight: bold; }
                .mb-5 { margin-bottom: 5px; }
                .mb-10 { margin-bottom: 10px; }
                .mb-15 { margin-bottom: 15px; }
                .mt-5 { margin-top: 5px; }
                .mt-10 { margin-top: 10px; }
                .pt-5 { padding-top: 5px; }
                .pb-5 { padding-bottom: 5px; }
                .border-bottom { border-bottom: 1px solid #000; }
                .border-bottom-dashed { border-bottom: 1px dashed #999; }
                .border-top { border-top: 2px solid #000; }
                
                /* Shop Header */
                .shop-header { 
                    text-align: center;
                    border-bottom: 2px solid #000; 
                    padding-bottom: 8px; 
                    margin-bottom: 10px;
                }
                .shop-name { 
                    font-size: 20px; 
                    font-weight: bold; 
                    margin-bottom: 2px;
                }
                .shop-details {
                    font-size: 10px;
                    color: #333;
                    line-height: 1.5;
                }
                .shop-details span {
                    margin: 0 5px;
                }
                
                .report-title { 
                    font-size: 14px; 
                    font-weight: bold; 
                    margin: 5px 0;
                }
                .filter-info {
                    font-size: 9px;
                    color: #333;
                    margin-bottom: 8px;
                    padding: 3px 8px;
                    background: #f5f5f5;
                    border: 1px solid #ddd;
                    text-align: center;
                }
                
                /* Summary Row - One line with columns */
                .summary-row {
                    display: table;
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 10px;
                    border: 1px solid #000;
                }
                .summary-row .summary-item {
                    display: table-cell;
                    padding: 6px 8px;
                    text-align: center;
                    border-right: 1px solid #000;
                    vertical-align: middle;
                }
                .summary-row .summary-item:last-child {
                    border-right: none;
                }
                .summary-row .summary-item .label {
                    font-size: 9px;
                    text-transform: uppercase;
                    color: #555;
                    font-weight: bold;
                    letter-spacing: 0.3px;
                }
                .summary-row .summary-item .value {
                    font-size: 16px;
                    font-weight: bold;
                    margin-top: 2px;
                }
                /* Individual item colors */
                .summary-item.item-sales .value { color: #4f46e5; }
                .summary-item.item-revenue .value { color: #059669; }
                .summary-item.item-profit .value { color: #d97706; }
                .summary-item.item-average .value { color: #0891b2; }
                
                /* Table Styles */
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin: 8px 0;
                    font-size: 10px;
                }
                th, td { 
                    padding: 4px 6px; 
                    text-align: left; 
                    border-bottom: 1px solid #ddd;
                }
                th { 
                    background-color: #f0f0f0; 
                    font-weight: bold; 
                    font-size: 8px;
                    text-transform: uppercase;
                    border-top: 2px solid #000;
                    border-bottom: 2px solid #000;
                }
                .text-right { text-align: right; }
                .status-paid { color: #059669; font-weight: bold; }
                .status-partial { color: #d97706; font-weight: bold; }
                .status-unpaid { color: #dc2626; font-weight: bold; }
                
                .footer { 
                    text-align: center; 
                    margin-top: 15px; 
                    padding-top: 8px; 
                    border-top: 2px solid #000;
                    font-size: 9px;
                    color: #555;
                }
                @media print {
                    body { padding: 10px; }
                }
            </style>
        </head>
        <body>
            <div class="report-container">
                <!-- Shop Header -->
                <div class="shop-header">
                    <div class="shop-name">${shopName}</div>
                    <div class="shop-details">
                        <div>${shopAddress}</div>
                        <div>
                            <span>Phone: ${shopPhone}</span>
                            <span>|</span>
                            <span>Email: ${shopEmail}</span>
                        </div>
                    </div>
                </div>
                
                <!-- Report Title -->
                <div class="text-center mb-5">
                    <div class="report-title">SALES REPORT</div>
                    <div style="font-size: 9px; color: #666;">Generated on: ${new Date().toLocaleString()}</div>
                </div>
                
                <!-- Filter Info -->
                <div class="filter-info">${filterText}</div>
                
                <!-- Summary Row - One line with 4 columns -->
                <div class="summary-row">
                    <div class="summary-item item-sales">
                        <div class="label">Total Sales</div>
                        <div class="value">${totalSalesCount}</div>
                    </div>
                    <div class="summary-item item-revenue">
                        <div class="label">Revenue</div>
                        <div class="value">${totalRevenue}</div>
                    </div>
                    <div class="summary-item item-profit">
                        <div class="label">Profit</div>
                        <div class="value">${totalProfitAmount}</div>
                    </div>
                    <div class="summary-item item-average">
                        <div class="label">Average Sale</div>
                        <div class="value">${averageSaleAmount}</div>
                    </div>
                </div>
                
                <!-- Report Content -->
                ${printContent.innerHTML}
            </div>
            <script>
                window.onload = function() {
                    window.print();
                    window.onafterprint = function() {
                        window.close();
                    };
                };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
};

// View sale details
const viewSaleDetails = (saleId) => {
    router.visit(route('pos.invoice', saleId));
};

// Watch for search changes
watch(() => filters.value.search, () => {
    currentPage.value = 1;
});

// Set default dates
onMounted(() => {
    if (!filters.value.start_date && !filters.value.end_date) {
        const now = new Date();
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        
        filters.value.start_date = thirtyDaysAgo.toISOString().split('T')[0];
        filters.value.end_date = now.toISOString().split('T')[0];
        applyFilters();
    }
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Sales Report" />
        
        <div class="min-h-screen bg-gray-50 p-4">
            <div class="max-w-7xl mx-auto">
                <!-- Company Header (only visible on screen) -->
                <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 rounded-xl shadow-lg p-5 mb-4 text-white print:hidden">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-2xl font-bold">{{ shop?.name || 'Your Shop' }}</h1>
                            <p class="text-indigo-100 text-sm mt-1">{{ shop?.address || 'Shop Address' }}</p>
                            <div class="flex gap-4 mt-2 text-sm text-indigo-100">
                                <span><i class="fas fa-phone mr-1"></i> {{ shop?.phone || 'Phone Number' }}</span>
                                <span><i class="fas fa-envelope mr-1"></i> {{ shop?.email || 'Email Address' }}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-indigo-100">Report Generated</div>
                            <div class="text-lg font-bold">{{ new Date().toLocaleDateString() }}</div>
                            <div class="text-xs text-indigo-100">{{ new Date().toLocaleTimeString() }}</div>
                        </div>
                    </div>
                </div>

                <!-- Smart Filter Bar -->
                <div class="bg-white rounded-xl shadow-sm p-3 mb-4 print:hidden">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="flex items-center gap-1">
                            <i class="fas fa-calendar text-gray-400 text-xs"></i>
                            <input
                                v-model="filters.start_date"
                                type="date"
                                class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 w-32"
                            />
                            <span class="text-xs text-gray-400">to</span>
                            <input
                                v-model="filters.end_date"
                                type="date"
                                class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 w-32"
                            />
                        </div>

                        <div class="w-px h-6 bg-gray-300"></div>

                        <div class="flex items-center gap-1">
                            <i class="fas fa-credit-card text-gray-400 text-xs"></i>
                            <select
                                v-model="filters.payment_method"
                                class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option value="all">All Payments</option>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="mobile_banking">Mobile</option>
                                <option value="credit">Credit</option>
                            </select>
                        </div>

                        <div class="w-px h-6 bg-gray-300"></div>

                        <div class="flex items-center gap-1">
                            <i class="fas fa-tag text-gray-400 text-xs"></i>
                            <select
                                v-model="filters.sale_type"
                                class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option value="all">All Types</option>
                                <option value="retail">Retail</option>
                                <option value="wholesale">Wholesale</option>
                            </select>
                        </div>

                        <div class="w-px h-6 bg-gray-300"></div>

                        <div class="flex-1 min-w-[150px] relative">
                            <i class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input
                                v-model="filters.search"
                                type="text"
                                placeholder="Search invoice, customer..."
                                class="w-full border border-gray-300 rounded-lg pl-7 pr-3 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            />
                        </div>

                        <button
                            @click="applyFilters"
                            :disabled="isFiltering"
                            class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg hover:bg-indigo-700 transition text-xs flex items-center gap-1 disabled:opacity-50"
                        >
                            <i v-if="isFiltering" class="fas fa-spinner fa-spin text-xs"></i>
                            <i v-else class="fas fa-filter text-xs"></i>
                            {{ isFiltering ? 'Loading...' : 'Apply' }}
                        </button>
                        <button
                            @click="resetFilters"
                            class="bg-gray-200 text-gray-700 px-2 py-1.5 rounded-lg hover:bg-gray-300 transition text-xs"
                            title="Reset filters"
                        >
                            <i class="fas fa-undo"></i>
                        </button>
                        <button
                            @click="printReport"
                            class="bg-emerald-600 text-white px-3 py-1.5 rounded-lg hover:bg-emerald-700 transition text-xs flex items-center gap-1"
                        >
                            <i class="fas fa-print text-xs"></i> Print
                        </button>
                        <button
                            @click="exportReport"
                            class="bg-amber-600 text-white px-3 py-1.5 rounded-lg hover:bg-amber-700 transition text-xs flex items-center gap-1"
                        >
                            <i class="fas fa-file-export text-xs"></i> Export
                        </button>
                    </div>
                </div>

                <!-- Report Content - This will be printed -->
                <div id="report-print-content">
                    <!-- Sales Table -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase tracking-wider">Invoice</th>
                                        <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                        <th class="px-3 py-2 text-center text-[10px] font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-3 py-2 text-right text-[10px] font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-3 py-2 text-right text-[10px] font-medium text-gray-500 uppercase tracking-wider">Paid</th>
                                        <th class="px-3 py-2 text-right text-[10px] font-medium text-gray-500 uppercase tracking-wider">Due</th>
                                        <th class="px-3 py-2 text-center text-[10px] font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-3 py-2 text-center text-[10px] font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="sale in paginatedSales" :key="sale.id" class="hover:bg-gray-50 transition">
                                        <td class="px-3 py-2 text-xs font-medium text-gray-900">
                                            {{ sale.invoice_no }}
                                        </td>
                                        <td class="px-3 py-2 text-xs text-gray-600 whitespace-nowrap">
                                            {{ formatDate(sale.sale_date) }}
                                        </td>
                                        <td class="px-3 py-2 text-xs text-gray-600">
                                            <div>{{ sale.customer?.name || 'Walk-in' }}</div>
                                            <div class="text-[10px] text-gray-400">{{ sale.customer?.phone || 'N/A' }}</div>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="px-2 py-0.5 text-[10px] rounded-full" :class="sale.sale_type === 'retail' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'">
                                                {{ sale.sale_type }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-xs text-right font-medium">
                                            {{ formatCurrency(sale.total_amount) }}
                                        </td>
                                        <td class="px-3 py-2 text-xs text-right text-emerald-600">
                                            {{ formatCurrency(sale.paid_amount) }}
                                        </td>
                                        <td class="px-3 py-2 text-xs text-right" :class="sale.due_amount > 0 ? 'text-amber-600 font-medium' : 'text-gray-400'">
                                            {{ formatCurrency(sale.due_amount) }}
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="px-2 py-0.5 text-[10px] rounded-full font-medium" :class="{
                                                'bg-emerald-100 text-emerald-700': sale.payment_status === 'paid',
                                                'bg-amber-100 text-amber-700': sale.payment_status === 'partial',
                                                'bg-red-100 text-red-700': sale.payment_status === 'unpaid'
                                            }">
                                                {{ sale.payment_status }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <button
                                                @click="viewSaleDetails(sale.id)"
                                                class="text-indigo-600 hover:text-indigo-800 transition"
                                                title="View invoice"
                                            >
                                                <i class="fas fa-eye text-xs"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="paginatedSales.length === 0">
                                        <td colspan="9" class="px-3 py-8 text-center text-gray-400">
                                            <i class="fas fa-inbox text-2xl block mb-2"></i>
                                            <p class="text-sm">No sales found</p>
                                            <p class="text-xs">Try adjusting your filters</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="px-3 py-2 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                            <div class="text-[11px] text-gray-500">
                                Showing {{ (currentPage - 1) * itemsPerPage + 1 }} - 
                                {{ Math.min(currentPage * itemsPerPage, filteredSales.length) }} 
                                of {{ filteredSales.length }} sales
                            </div>
                            <div class="flex gap-1" v-if="totalPages > 1">
                                <button
                                    @click="currentPage--"
                                    :disabled="currentPage === 1"
                                    class="px-2 py-0.5 border border-gray-300 rounded text-xs disabled:opacity-50 hover:bg-gray-50 transition"
                                >
                                    <i class="fas fa-chevron-left text-xs"></i>
                                </button>
                                <span class="px-2 py-0.5 text-xs bg-indigo-50 text-indigo-600 rounded">
                                    {{ currentPage }} / {{ totalPages }}
                                </span>
                                <button
                                    @click="currentPage++"
                                    :disabled="currentPage === totalPages"
                                    class="px-2 py-0.5 border border-gray-300 rounded text-xs disabled:opacity-50 hover:bg-gray-50 transition"
                                >
                                    <i class="fas fa-chevron-right text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="mt-4 text-center text-[11px] text-gray-400 border-t pt-3">
                        <p>Generated on {{ new Date().toLocaleString() }}</p>
                        <p v-if="filters.start_date || filters.end_date">
                            Period: {{ filters.start_date || 'Start' }} to {{ filters.end_date || 'End' }}
                        </p>
                        <p v-if="filters.payment_method !== 'all' || filters.sale_type !== 'all'">
                            Filters: 
                            <span v-if="filters.payment_method !== 'all'">Payment: {{ filters.payment_method }} | </span>
                            <span v-if="filters.sale_type !== 'all'">Type: {{ filters.sale_type }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Hide elements when printing */
@media print {
    .print\:hidden {
        display: none !important;
    }
}
</style>