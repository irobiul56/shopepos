<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';

const { props } = usePage();

// Active tab
const activeTab = ref('sales');

// Sales Data
const sales = ref(props.sales || []);
const totalSales = ref(props.total_sales || 0);
const totalRevenue = ref(props.total_revenue || 0);
const totalProfit = ref(props.total_profit || 0);
const shop = ref(props.shop || null);

// Payment Data
const payments = ref(props.payments || []);
const totalPayments = ref(props.total_payments || 0);
const totalPaymentAmount = ref(props.total_payment_amount || 0);
const paymentSummary = ref(props.payment_summary || {});
const paymentMethodAmounts = ref(props.payment_method_amounts || {});

// Common filters
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
const isPrinting = ref(false);

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

// Computed filtered payments
const filteredPayments = computed(() => {
    let filtered = [...payments.value];
    
    if (filters.value.search) {
        const query = filters.value.search.toLowerCase().trim();
        filtered = filtered.filter(payment =>
            payment.sale?.invoice_no?.toLowerCase().includes(query) ||
            payment.sale?.customer?.name?.toLowerCase().includes(query) ||
            payment.sale?.customer?.phone?.includes(query)
        );
    }
    
    return filtered;
});

// Pagination
const totalPages = computed(() => {
    const data = activeTab.value === 'sales' ? filteredSales.value : filteredPayments.value;
    return Math.ceil(data.length / itemsPerPage.value);
});

const paginatedData = computed(() => {
    const data = activeTab.value === 'sales' ? filteredSales.value : filteredPayments.value;
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return data.slice(start, end);
});

// Sales summary
const salesSummary = computed(() => {
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

// Payment summary
const paymentSummaryData = computed(() => {
    const filtered = filteredPayments.value;
    const total = filtered.reduce((sum, payment) => sum + Number(payment.amount || 0), 0);
    const count = filtered.length;
    
    return {
        total_payments: count,
        total_amount: total,
        average_payment: count > 0 ? total / count : 0
    };
});

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

// Format date only
const formatDateOnly = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
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
    
    const url = route('report.unified') + '?' + params.toString();
    
    router.visit(url, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
            isFiltering.value = false;
            sales.value = page.props.sales || [];
            totalSales.value = page.props.total_sales || 0;
            totalRevenue.value = page.props.total_revenue || 0;
            totalProfit.value = page.props.total_profit || 0;
            payments.value = page.props.payments || [];
            totalPayments.value = page.props.total_payments || 0;
            totalPaymentAmount.value = page.props.total_payment_amount || 0;
            paymentSummary.value = page.props.payment_summary || {};
            paymentMethodAmounts.value = page.props.payment_method_amounts || {};
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
    
    const url = route('report.export') + '?' + params.toString() + '&type=' + activeTab.value;
    window.open(url, '_blank');
};

// Smart Print Report - Only prints summary and table
const printReport = () => {
    isPrinting.value = true;
    
    const printWindow = window.open('', '_blank', 'width=1000,height=800');
    if (!printWindow) {
        alert('Please allow popups for printing');
        isPrinting.value = false;
        return;
    }
    
    // Get shop information
    const shopName = shop.value?.name || 'Your Shop';
    const shopAddress = shop.value?.address || 'Shop Address';
    const shopPhone = shop.value?.phone || 'Phone Number';
    const shopEmail = shop.value?.email || 'Email Address';
    const reportTitle = activeTab.value === 'sales' ? 'Sales Report' : 'Payment Report';
    const reportDate = new Date().toLocaleString();
    
    // Get filter info
    let filterInfo = [];
    if (filters.value.start_date) filterInfo.push(`From: ${filters.value.start_date}`);
    if (filters.value.end_date) filterInfo.push(`To: ${filters.value.end_date}`);
    if (filters.value.payment_method && filters.value.payment_method !== 'all' && activeTab.value === 'payments') {
        filterInfo.push(`Method: ${filters.value.payment_method}`);
    }
    if (filters.value.sale_type && filters.value.sale_type !== 'all' && activeTab.value === 'sales') {
        filterInfo.push(`Type: ${filters.value.sale_type}`);
    }
    const filterText = filterInfo.length > 0 ? filterInfo.join(' | ') : 'All Records';
    
    // Get data for printing
    const dataList = activeTab.value === 'sales' ? paginatedData.value : paginatedData.value;
    
    // Build table rows
    let tableRows = '';
    if (activeTab.value === 'sales') {
        dataList.forEach((sale, index) => {
            tableRows += `
                <tr>
                    <td>${sale.invoice_no}</td>
                    <td>${formatDate(sale.sale_date)}</td>
                    <td>${sale.customer?.name || 'Walk-in'}</td>
                    <td class="text-center">${sale.sale_type}</td>
                    <td class="text-right">${formatCurrency(sale.total_amount)}</td>
                    <td class="text-right">${formatCurrency(sale.paid_amount)}</td>
                    <td class="text-right">${formatCurrency(sale.due_amount)}</td>
                    <td class="text-center">${sale.payment_status}</td>
                </tr>
            `;
        });
    } else {
        dataList.forEach((payment, index) => {
            tableRows += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${payment.sale?.invoice_no || 'N/A'}</td>
                    <td>${payment.sale?.customer?.name || 'Walk-in'}</td>
                    <td>${formatDateOnly(payment.payment_date)}</td>
                    <td class="text-center">${payment.payment_method}</td>
                    <td class="text-right">${formatCurrency(payment.amount)}</td>
                    <td>${payment.transaction_id || '—'}</td>
                    <td class="text-center">${payment.user?.name || 'N/A'}</td>
                </tr>
            `;
        });
    }
    
    // Summary data
    const summaryData = activeTab.value === 'sales' ? salesSummary.value : paymentSummaryData.value;
    const summaryKeys = activeTab.value === 'sales' 
        ? [
            { label: 'Total Sales', value: summaryData.total_sales },
            { label: 'Revenue', value: formatCurrency(summaryData.total_amount) },
            { label: 'Profit', value: formatCurrency(summaryData.total_profit) },
            { label: 'Average Sale', value: formatCurrency(summaryData.average_sale) }
        ]
        : [
            { label: 'Total Payments', value: summaryData.total_payments },
            { label: 'Total Amount', value: formatCurrency(summaryData.total_amount) },
            { label: 'Average Payment', value: formatCurrency(summaryData.average_payment) },
            { label: 'Methods', value: Object.keys(paymentSummary.value).length }
        ];
    
    // Build summary row
    let summaryRow = '';
    summaryKeys.forEach(item => {
        const valueClass = item.label.toLowerCase().replace(/ /g, '-');
        summaryRow += `
            <td>
                <div class="label">${item.label}</div>
                <div class="value ${valueClass}">${item.value}</div>
            </td>
        `;
    });
    
    // Payment method summary for payment report
    let paymentMethodSummary = '';
    if (activeTab.value === 'payments' && Object.keys(paymentSummary.value).length > 0) {
        paymentMethodSummary = `
            <div class="method-summary">
                <div class="method-title">Payment Methods</div>
                <div class="method-list">
                    ${Object.keys(paymentSummary.value).map(method => `
                        <span class="method-item">
                            ${method}: ${paymentSummary.value[method]} (${formatCurrency(paymentMethodAmounts.value[method] || 0)})
                        </span>
                    `).join('')}
                </div>
            </div>
        `;
    }
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>${reportTitle} - ${shopName}</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { 
                    font-family: 'Courier New', Courier, monospace; 
                    font-size: 10px; 
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
                .border-top { border-top: 2px solid #000; }
                
                /* Shop Header */
                .shop-header { 
                    text-align: center;
                    border-bottom: 2px solid #000; 
                    padding-bottom: 8px; 
                    margin-bottom: 10px;
                }
                .shop-name { 
                    font-size: 18px; 
                    font-weight: bold; 
                    margin-bottom: 2px;
                }
                .shop-details {
                    font-size: 9px;
                    color: #333;
                    line-height: 1.5;
                }
                .shop-details span {
                    margin: 0 5px;
                }
                
                .report-title { 
                    font-size: 13px; 
                    font-weight: bold; 
                    margin: 5px 0;
                }
                .filter-info {
                    font-size: 8px;
                    color: #333;
                    margin-bottom: 10px;
                    padding: 3px 8px;
                    background: #f5f5f5;
                    border: 1px solid #ddd;
                    text-align: center;
                }
                
                /* Summary Table */
                .summary-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 10px;
                    font-size: 9px;
                }
                .summary-table td {
                    padding: 5px 8px;
                    border: 1px solid #000;
                    text-align: center;
                    vertical-align: middle;
                    width: 25%;
                }
                .summary-table .label {
                    font-weight: bold;
                    text-transform: uppercase;
                    font-size: 7px;
                    color: #555;
                    background-color: #f5f5f5;
                    border-bottom: 2px solid #000;
                }
                .summary-table .value {
                    font-weight: bold;
                    font-size: 14px;
                    padding-top: 3px;
                }
                .summary-table .Total-Sales { color: #4f46e5; }
                .summary-table .Revenue { color: #059669; }
                .summary-table .Profit { color: #d97706; }
                .summary-table .Average-Sale { color: #0891b2; }
                .summary-table .Total-Payments { color: #4f46e5; }
                .summary-table .Total-Amount { color: #059669; }
                .summary-table .Average-Payment { color: #0891b2; }
                .summary-table .Methods { color: #7c3aed; }
                
                /* Payment Method Summary */
                .method-summary {
                    margin-bottom: 10px;
                    padding: 5px 10px;
                    background: #f8f9fa;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                }
                .method-title {
                    font-weight: bold;
                    font-size: 8px;
                    text-transform: uppercase;
                    color: #666;
                    margin-bottom: 3px;
                }
                .method-list {
                    display: flex;
                    gap: 15px;
                    flex-wrap: wrap;
                }
                .method-item {
                    font-size: 8px;
                    padding: 2px 6px;
                    background: white;
                    border: 1px solid #e5e7eb;
                    border-radius: 3px;
                }
                
                /* Main Table */
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin: 8px 0;
                    font-size: 8px;
                }
                th, td { 
                    padding: 4px 6px; 
                    text-align: left; 
                    border-bottom: 1px solid #ddd;
                }
                th { 
                    background-color: #f0f0f0; 
                    font-weight: bold; 
                    font-size: 7px;
                    text-transform: uppercase;
                    border-top: 2px solid #000;
                    border-bottom: 2px solid #000;
                }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                
                .footer { 
                    text-align: center; 
                    margin-top: 15px; 
                    padding-top: 8px; 
                    border-top: 2px solid #000;
                    font-size: 8px;
                    color: #555;
                }
                .signature {
                    margin-top: 15px;
                    padding-top: 10px;
                    border-top: 1px solid #ddd;
                    display: flex;
                    justify-content: space-between;
                    font-size: 8px;
                }
                .signature .line {
                    border-top: 1px solid #000;
                    width: 120px;
                    margin-top: 3px;
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
                    <div class="report-title">${reportTitle}</div>
                    <div style="font-size: 7px; color: #666;">Generated on: ${reportDate}</div>
                </div>
                
                <!-- Filter Info -->
                <div class="filter-info">${filterText}</div>
                
                <!-- Summary Row -->
                <table class="summary-table">
                    <tr>
                        ${summaryRow}
                    </tr>
                </table>
                
                <!-- Payment Method Summary (for payment tab) -->
                ${paymentMethodSummary}
                
                <!-- Data Table -->
                <table>
                    <thead>
                        <tr>
                            ${activeTab === 'sales' ? `
                                <th>Invoice</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th class="text-center">Type</th>
                                <th class="text-right">Amount</th>
                                <th class="text-right">Paid</th>
                                <th class="text-right">Due</th>
                                <th class="text-center">Status</th>
                            ` : `
                                <th>#</th>
                                <th>Invoice</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th class="text-center">Method</th>
                                <th class="text-right">Amount</th>
                                <th>Transaction</th>
                                <th class="text-center">Collected By</th>
                            `}
                        </tr>
                    </thead>
                    <tbody>
                        ${tableRows || `<tr><td colspan="8" class="text-center" style="padding: 20px; color: #999;">No records found</td></tr>`}
                    </tbody>
                </table>
                
                <!-- Footer -->
                <div class="footer">
                    <p>This is a computer generated report. | All amounts are in Taka (Tk)</p>
                    <p>Total ${activeTab === 'sales' ? 'Sales' : 'Payments'}: ${activeTab === 'sales' ? filteredSales.length : filteredPayments.length}</p>
                </div>
                
                <!-- Signature -->
                <div class="signature">
                    <div>
                        <div class="line"></div>
                        <div>Authorized Signature</div>
                    </div>
                    <div>
                        <div class="line"></div>
                        <div>Shop Owner</div>
                    </div>
                    <div>
                        <div class="line"></div>
                        <div>Date</div>
                    </div>
                </div>
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
    
    setTimeout(() => {
        isPrinting.value = false;
    }, 3000);
};

// Get payment method info
const getPaymentMethodInfo = (method) => {
    const methods = {
        cash: { icon: 'fa-money-bill-wave', label: 'Cash', class: 'method-cash' },
        card: { icon: 'fa-credit-card', label: 'Card', class: 'method-card' },
        mobile_banking: { icon: 'fa-mobile-alt', label: 'Mobile Banking', class: 'method-mobile' },
        bank_transfer: { icon: 'fa-university', label: 'Bank Transfer', class: 'method-bank' },
    };
    return methods[method] || { icon: 'fa-money-bill', label: method, class: '' };
};

// Get status color
const getStatusColor = (status) => {
    const colors = {
        paid: 'bg-emerald-100 text-emerald-700',
        partial: 'bg-amber-100 text-amber-700',
        unpaid: 'bg-red-100 text-red-700',
    };
    return colors[status] || 'bg-gray-100 text-gray-700';
};

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

// Watch for tab change
watch(activeTab, () => {
    currentPage.value = 1;
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Reports" />
        
        <div class="min-h-screen w-full bg-gray-50 p-4">
            <div class="max-w-7xl mx-auto">
                <!-- Shop Information Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 rounded-xl shadow-lg p-4 mb-4 text-white">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-xl font-bold">{{ shop?.name || 'Your Shop' }}</h1>
                            <p class="text-indigo-100 text-xs mt-1">{{ shop?.address || 'Shop Address' }}</p>
                            <div class="flex gap-3 mt-1 text-xs text-indigo-100">
                                <span><i class="fas fa-phone mr-1"></i> {{ shop?.phone || 'Phone Number' }}</span>
                                <span><i class="fas fa-envelope mr-1"></i> {{ shop?.email || 'Email Address' }}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-indigo-100">Report Generated</div>
                            <div class="text-lg font-bold">{{ new Date().toLocaleDateString() }}</div>
                            <div class="text-[10px] text-indigo-100">{{ new Date().toLocaleTimeString() }}</div>
                        </div>
                    </div>
                </div>

                <!-- Small Tabs -->
                <div class="flex gap-1 mb-4">
                    <button
                        @click="activeTab = 'sales'"
                        :class="[
                            'px-4 py-1.5 rounded-lg text-xs font-medium transition',
                            activeTab === 'sales'
                                ? 'bg-indigo-600 text-white shadow-sm'
                                : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'
                        ]"
                    >
                        <i class="fas fa-shopping-cart mr-1"></i> Sales
                        <span class="ml-1 text-[10px] bg-white/20 px-1.5 py-0.5 rounded-full" v-if="activeTab === 'sales'">
                            {{ filteredSales.length }}
                        </span>
                    </button>
                    <button
                        @click="activeTab = 'payments'"
                        :class="[
                            'px-4 py-1.5 rounded-lg text-xs font-medium transition',
                            activeTab === 'payments'
                                ? 'bg-indigo-600 text-white shadow-sm'
                                : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200'
                        ]"
                    >
                        <i class="fas fa-credit-card mr-1"></i> Payments
                        <span class="ml-1 text-[10px] bg-white/20 px-1.5 py-0.5 rounded-full" v-if="activeTab === 'payments'">
                            {{ filteredPayments.length }}
                        </span>
                    </button>
                    
                    <div class="flex-1"></div>
                    
                    <button 
                        @click="printReport"
                        :disabled="isPrinting"
                        class="bg-emerald-600 text-white px-3 py-1.5 rounded-lg hover:bg-emerald-700 transition text-xs flex items-center gap-1 disabled:opacity-50"
                    >
                        <i v-if="isPrinting" class="fas fa-spinner fa-spin"></i>
                        <i v-else class="fas fa-print"></i>
                        {{ isPrinting ? 'Printing...' : 'Print' }}
                    </button>
                    <button 
                        @click="exportReport"
                        class="bg-amber-600 text-white px-3 py-1.5 rounded-lg hover:bg-amber-700 transition text-xs flex items-center gap-1"
                    >
                        <i class="fas fa-file-export"></i> Export
                    </button>
                </div>

                <!-- Summary Cards -->
                <div v-if="activeTab === 'sales'" class="grid grid-cols-4 gap-3 mb-4">
                    <div class="bg-white rounded-lg shadow-sm p-3 border-l-4 border-indigo-500">
                        <p class="text-[10px] text-gray-500 uppercase font-medium">Total Sales</p>
                        <p class="text-lg font-bold text-gray-800">{{ salesSummary.total_sales }}</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-3 border-l-4 border-emerald-500">
                        <p class="text-[10px] text-gray-500 uppercase font-medium">Revenue</p>
                        <p class="text-lg font-bold text-gray-800">{{ formatCurrency(salesSummary.total_amount) }}</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-3 border-l-4 border-amber-500">
                        <p class="text-[10px] text-gray-500 uppercase font-medium">Profit</p>
                        <p class="text-lg font-bold text-gray-800">{{ formatCurrency(salesSummary.total_profit) }}</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-3 border-l-4 border-cyan-500">
                        <p class="text-[10px] text-gray-500 uppercase font-medium">Average Sale</p>
                        <p class="text-lg font-bold text-gray-800">{{ formatCurrency(salesSummary.average_sale) }}</p>
                    </div>
                </div>

                <div v-if="activeTab === 'payments'" class="grid grid-cols-4 gap-3 mb-4">
                    <div class="bg-white rounded-lg shadow-sm p-3 border-l-4 border-indigo-500">
                        <p class="text-[10px] text-gray-500 uppercase font-medium">Total Payments</p>
                        <p class="text-lg font-bold text-gray-800">{{ paymentSummaryData.total_payments }}</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-3 border-l-4 border-emerald-500">
                        <p class="text-[10px] text-gray-500 uppercase font-medium">Total Amount</p>
                        <p class="text-lg font-bold text-gray-800">{{ formatCurrency(paymentSummaryData.total_amount) }}</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-3 border-l-4 border-amber-500">
                        <p class="text-[10px] text-gray-500 uppercase font-medium">Average Payment</p>
                        <p class="text-lg font-bold text-gray-800">{{ formatCurrency(paymentSummaryData.average_payment) }}</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-3 border-l-4 border-purple-500">
                        <p class="text-[10px] text-gray-500 uppercase font-medium">Methods</p>
                        <p class="text-lg font-bold text-gray-800">{{ Object.keys(paymentSummary).length }}</p>
                    </div>
                </div>

                <!-- Filter Bar -->
                <div class="bg-white rounded-lg shadow-sm p-2 mb-4">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <div class="flex items-center gap-1">
                            <i class="fas fa-calendar text-gray-400 text-[10px]"></i>
                            <input
                                v-model="filters.start_date"
                                type="date"
                                class="border border-gray-300 rounded px-1.5 py-1 text-[10px] focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 w-28"
                            />
                            <span class="text-[10px] text-gray-400">to</span>
                            <input
                                v-model="filters.end_date"
                                type="date"
                                class="border border-gray-300 rounded px-1.5 py-1 text-[10px] focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 w-28"
                            />
                        </div>

                        <div class="w-px h-5 bg-gray-300"></div>

                        <div v-if="activeTab === 'sales'" class="flex items-center gap-1">
                            <i class="fas fa-tag text-gray-400 text-[10px]"></i>
                            <select
                                v-model="filters.sale_type"
                                class="border border-gray-300 rounded px-1.5 py-1 text-[10px] focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option value="all">All Types</option>
                                <option value="retail">Retail</option>
                                <option value="wholesale">Wholesale</option>
                            </select>
                        </div>

                        <div v-if="activeTab === 'payments'" class="flex items-center gap-1">
                            <i class="fas fa-credit-card text-gray-400 text-[10px]"></i>
                            <select
                                v-model="filters.payment_method"
                                class="border border-gray-300 rounded px-1.5 py-1 text-[10px] focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option value="all">All Methods</option>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="mobile_banking">Mobile</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>

                        <div class="w-px h-5 bg-gray-300"></div>

                        <div class="flex-1 min-w-[120px] relative">
                            <i class="fas fa-search absolute left-1.5 top-1/2 -translate-y-1/2 text-gray-400 text-[10px]"></i>
                            <input
                                v-model="filters.search"
                                type="text"
                                placeholder="Search..."
                                class="w-full border border-gray-300 rounded pl-6 pr-2 py-1 text-[10px] focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                            />
                        </div>

                        <button
                            @click="applyFilters"
                            :disabled="isFiltering"
                            class="bg-indigo-600 text-white px-2 py-1 rounded hover:bg-indigo-700 transition text-[10px] flex items-center gap-1 disabled:opacity-50"
                        >
                            <i v-if="isFiltering" class="fas fa-spinner fa-spin text-[10px]"></i>
                            <i v-else class="fas fa-filter text-[10px]"></i>
                            {{ isFiltering ? '...' : 'Apply' }}
                        </button>
                        <button
                            @click="resetFilters"
                            class="bg-gray-200 text-gray-700 px-1.5 py-1 rounded hover:bg-gray-300 transition text-[10px]"
                            title="Reset filters"
                        >
                            <i class="fas fa-undo"></i>
                        </button>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-gray-50">
                                <tr v-if="activeTab === 'sales'">
                                    <th class="px-2 py-1.5 text-left text-[9px] font-medium text-gray-500 uppercase tracking-wider">Invoice</th>
                                    <th class="px-2 py-1.5 text-left text-[9px] font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-2 py-1.5 text-left text-[9px] font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-2 py-1.5 text-center text-[9px] font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-2 py-1.5 text-right text-[9px] font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-2 py-1.5 text-right text-[9px] font-medium text-gray-500 uppercase tracking-wider">Paid</th>
                                    <th class="px-2 py-1.5 text-right text-[9px] font-medium text-gray-500 uppercase tracking-wider">Due</th>
                                    <th class="px-2 py-1.5 text-center text-[9px] font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                                <tr v-if="activeTab === 'payments'">
                                    <th class="px-2 py-1.5 text-left text-[9px] font-medium text-gray-500 uppercase tracking-wider">#</th>
                                    <th class="px-2 py-1.5 text-left text-[9px] font-medium text-gray-500 uppercase tracking-wider">Invoice</th>
                                    <th class="px-2 py-1.5 text-left text-[9px] font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-2 py-1.5 text-left text-[9px] font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-2 py-1.5 text-center text-[9px] font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                    <th class="px-2 py-1.5 text-right text-[9px] font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-2 py-1.5 text-left text-[9px] font-medium text-gray-500 uppercase tracking-wider">Transaction</th>
                                    <th class="px-2 py-1.5 text-center text-[9px] font-medium text-gray-500 uppercase tracking-wider">Collected By</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="item in paginatedData" :key="item.id" class="hover:bg-gray-50 transition">
                                    <template v-if="activeTab === 'sales'">
                                        <td class="px-2 py-1.5 font-medium text-gray-900">{{ item.invoice_no }}</td>
                                        <td class="px-2 py-1.5 text-gray-600">{{ formatDate(item.sale_date) }}</td>
                                        <td class="px-2 py-1.5 text-gray-600">
                                            <div>{{ item.customer?.name || 'Walk-in' }}</div>
                                            <div class="text-[9px] text-gray-400">{{ item.customer?.phone || 'N/A' }}</div>
                                        </td>
                                        <td class="px-2 py-1.5 text-center">
                                            <span class="px-1.5 py-0.5 text-[9px] rounded" :class="item.sale_type === 'retail' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'">
                                                {{ item.sale_type }}
                                            </span>
                                        </td>
                                        <td class="px-2 py-1.5 text-right font-medium">{{ formatCurrency(item.total_amount) }}</td>
                                        <td class="px-2 py-1.5 text-right text-emerald-600">{{ formatCurrency(item.paid_amount) }}</td>
                                        <td class="px-2 py-1.5 text-right" :class="item.due_amount > 0 ? 'text-amber-600 font-medium' : 'text-gray-400'">
                                            {{ formatCurrency(item.due_amount) }}
                                        </td>
                                        <td class="px-2 py-1.5 text-center">
                                            <span class="px-1.5 py-0.5 text-[9px] rounded font-medium" :class="getStatusColor(item.payment_status)">
                                                {{ item.payment_status }}
                                            </span>
                                        </td>
                                    </template>
                                    <template v-if="activeTab === 'payments'">
                                        <td class="px-2 py-1.5 text-gray-500">{{ (currentPage - 1) * itemsPerPage + index + 1 }}</td>
                                        <td class="px-2 py-1.5 font-medium text-gray-900">{{ item.sale?.invoice_no || 'N/A' }}</td>
                                        <td class="px-2 py-1.5 text-gray-600">
                                            <div>{{ item.sale?.customer?.name || 'Walk-in' }}</div>
                                            <div class="text-[9px] text-gray-400">{{ item.sale?.customer?.phone || 'N/A' }}</div>
                                        </td>
                                        <td class="px-2 py-1.5 text-gray-600">{{ formatDateOnly(item.payment_date) }}</td>
                                        <td class="px-2 py-1.5 text-center">
                                            <span class="px-1.5 py-0.5 text-[9px] rounded font-medium" :class="getPaymentMethodInfo(item.payment_method).class">
                                                {{ getPaymentMethodInfo(item.payment_method).label }}
                                            </span>
                                        </td>
                                        <td class="px-2 py-1.5 text-right font-bold text-emerald-600">{{ formatCurrency(item.amount) }}</td>
                                        <td class="px-2 py-1.5 text-gray-500">{{ item.transaction_id || '—' }}</td>
                                        <td class="px-2 py-1.5 text-center text-gray-500">{{ item.user?.name || 'N/A' }}</td>
                                    </template>
                                </tr>
                                <tr v-if="paginatedData.length === 0">
                                    <td :colspan="8" class="px-2 py-6 text-center text-gray-400">
                                        <i class="fas fa-inbox text-lg block mb-1"></i>
                                        <p class="text-sm">No {{ activeTab === 'sales' ? 'sales' : 'payments' }} found</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="px-2 py-1.5 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                        <div class="text-[10px] text-gray-500">
                            Showing {{ (currentPage - 1) * itemsPerPage + 1 }} - 
                            {{ Math.min(currentPage * itemsPerPage, activeTab === 'sales' ? filteredSales.length : filteredPayments.length) }} 
                            of {{ activeTab === 'sales' ? filteredSales.length : filteredPayments.length }}
                        </div>
                        <div class="flex gap-0.5" v-if="totalPages > 1">
                            <button @click="currentPage--" :disabled="currentPage === 1" class="px-1.5 py-0.5 border border-gray-300 rounded text-[10px] disabled:opacity-50 hover:bg-gray-50 transition">
                                <i class="fas fa-chevron-left text-[10px]"></i>
                            </button>
                            <span class="px-1.5 py-0.5 text-[10px] bg-indigo-50 text-indigo-600 rounded">{{ currentPage }} / {{ totalPages }}</span>
                            <button @click="currentPage++" :disabled="currentPage === totalPages" class="px-1.5 py-0.5 border border-gray-300 rounded text-[10px] disabled:opacity-50 hover:bg-gray-50 transition">
                                <i class="fas fa-chevron-right text-[10px]"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>