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
    
    return {
        total_sales: count,
        total_amount: total,
        total_profit: totalProfit.value,
        average_sale: count > 0 ? total / count : 0
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

// Print report - Professional clean design with serial numbers and page numbers
const printReport = () => {
    const printContent = document.getElementById('report-print-content');
    if (!printContent) {
        alert('Report content not found');
        return;
    }
    
    const printWindow = window.open('', '_blank', 'width=1100,height=900');
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
        filterInfo.push(`Payment: ${filters.value.payment_method.charAt(0).toUpperCase() + filters.value.payment_method.slice(1)}`);
    }
    if (filters.value.sale_type && filters.value.sale_type !== 'all') {
        filterInfo.push(`Type: ${filters.value.sale_type.charAt(0).toUpperCase() + filters.value.sale_type.slice(1)}`);
    }
    const filterText = filterInfo.length > 0 ? filterInfo.join(' | ') : 'All Sales';
    
    // Get sales data from the current view
    const salesData = paginatedSales.value;
    const startSerial = (currentPage.value - 1) * itemsPerPage.value + 1;
    
    // Build table rows with serial numbers
    let tableRows = '';
    salesData.forEach((sale, index) => {
        const serial = startSerial + index;
        const statusClass = sale.payment_status === 'paid' ? 'status-paid' : 
                           sale.payment_status === 'partial' ? 'status-partial' : 'status-unpaid';
        const statusDisplay = sale.payment_status.charAt(0).toUpperCase() + sale.payment_status.slice(1);
        const typeDisplay = sale.sale_type?.charAt(0).toUpperCase() + sale.sale_type?.slice(1) || 'N/A';
        const customerName = sale.customer?.name || 'Walk-in Customer';
        const customerPhone = sale.customer?.phone || '—';
        
        tableRows += `
            <tr>
                <td class="text-center" style="font-weight:500;color:#6b7280;">${serial}</td>
                <td class="text-center"><strong>${sale.invoice_no || 'N/A'}</strong></td>
                <td class="text-center">${formatDate(sale.sale_date)}</td>
                <td>
                    <div><strong>${customerName}</strong></div>
                    <div style="font-size:7.5px;color:#9ca3af;">${customerPhone}</div>
                </td>
                <td class="text-center"><span style="background:${sale.sale_type === 'retail' ? '#dbeafe' : '#f3e8ff'};padding:2px 10px;border-radius:12px;font-size:7.5px;font-weight:600;color:${sale.sale_type === 'retail' ? '#1e40af' : '#6b21a8'};">${typeDisplay}</span></td>
                <td class="text-right"><strong>${formatCurrency(sale.total_amount)}</strong></td>
                <td class="text-right" style="color:#059669;">${formatCurrency(sale.paid_amount)}</td>
                <td class="text-right" style="color:${sale.due_amount > 0 ? '#d97706' : '#9ca3af'};">${formatCurrency(sale.due_amount)}</td>
                <td class="text-center"><span class="${statusClass}">${statusDisplay}</span></td>
            </tr>
        `;
    });
    
    // If no data
    if (salesData.length === 0) {
        tableRows = `
            <tr>
                <td colspan="9" style="text-align:center;padding:40px 20px;color:#9ca3af;">
                    <div style="font-size:20px;margin-bottom:10px;">📋</div>
                    <div style="font-size:14px;font-weight:500;">No sales found</div>
                    <div style="font-size:11px;color:#d1d5db;">Try adjusting your filters</div>
                </td>
            </tr>
        `;
    }
    
    // Calculate total pages
    const totalPages = Math.ceil(filteredSales.value.length / itemsPerPage.value);
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Sales Report - ${shopName}</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                    font-size: 10px; 
                    padding: 20px 25px; 
                    background: white;
                    color: #1f2937;
                    line-height: 1.5;
                }
                .report-container { 
                    max-width: 1200px; 
                    margin: 0 auto; 
                }
                
                /* Header Styles */
                .header-section {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    border-bottom: 3px double #1f2937;
                    padding-bottom: 15px;
                    margin-bottom: 15px;
                }
                .shop-info h1 {
                    font-size: 22px;
                    font-weight: 700;
                    color: #1f2937;
                    letter-spacing: -0.5px;
                }
                .shop-info .address {
                    font-size: 9px;
                    color: #4b5563;
                    margin-top: 3px;
                }
                .shop-info .contact {
                    font-size: 8.5px;
                    color: #6b7280;
                    margin-top: 2px;
                }
                .shop-info .contact span {
                    margin-right: 12px;
                }
                .report-meta {
                    text-align: right;
                    flex-shrink: 0;
                }
                .report-meta .title {
                    font-size: 16px;
                    font-weight: 700;
                    color: #1f2937;
                    letter-spacing: 1px;
                    text-transform: uppercase;
                }
                .report-meta .subtitle {
                    font-size: 8px;
                    color: #6b7280;
                    margin-top: 2px;
                }
                .report-meta .datetime {
                    font-size: 8px;
                    color: #9ca3af;
                    margin-top: 1px;
                }
                .report-meta .page-info {
                    font-size: 8px;
                    color: #6b7280;
                    margin-top: 3px;
                    font-weight: 500;
                    background: #f3f4f6;
                    padding: 2px 10px;
                    border-radius: 4px;
                    display: inline-block;
                }
                
                /* Filter Badge */
                .filter-badge {
                    background: #f3f4f6;
                    border: 1px solid #e5e7eb;
                    border-radius: 6px;
                    padding: 6px 12px;
                    margin-bottom: 12px;
                    text-align: center;
                    font-size: 8.5px;
                    color: #4b5563;
                }
                .filter-badge strong {
                    color: #1f2937;
                }
                
                /* Summary Grid */
                .summary-grid {
                    display: grid;
                    grid-template-columns: repeat(4, 1fr);
                    gap: 10px;
                    margin-bottom: 15px;
                }
                .summary-card {
                    background: #f9fafb;
                    border: 1px solid #e5e7eb;
                    border-radius: 8px;
                    padding: 10px 14px;
                    text-align: center;
                }
                .summary-card .label {
                    font-size: 7.5px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    color: #6b7280;
                    font-weight: 600;
                }
                .summary-card .value {
                    font-size: 18px;
                    font-weight: 700;
                    margin-top: 2px;
                }
                .summary-card .value-sales { color: #4f46e5; }
                .summary-card .value-revenue { color: #059669; }
                .summary-card .value-profit { color: #d97706; }
                .summary-card .value-average { color: #0891b2; }
                
                /* Main Table */
                .table-wrapper {
                    border: 1px solid #e5e7eb;
                    border-radius: 8px;
                    overflow: hidden;
                    margin-bottom: 12px;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    font-size: 9px;
                }
                thead {
                    background: #f3f4f6;
                }
                th { 
                    padding: 8px 8px; 
                    text-align: left; 
                    font-weight: 600;
                    font-size: 7px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    color: #374151;
                    border-bottom: 2px solid #d1d5db;
                    white-space: nowrap;
                }
                th.text-center { text-align: center; }
                th.text-right { text-align: right; }
                
                td { 
                    padding: 6px 8px; 
                    border-bottom: 1px solid #f3f4f6;
                    vertical-align: middle;
                }
                td.text-center { text-align: center; }
                td.text-right { text-align: right; }
                
                /* Status Badges */
                .status-paid { 
                    background: #d1fae5; 
                    color: #065f46; 
                    padding: 2px 10px; 
                    border-radius: 12px; 
                    font-weight: 600;
                    font-size: 7.5px;
                    display: inline-block;
                }
                .status-partial { 
                    background: #fef3c7; 
                    color: #92400e; 
                    padding: 2px 10px; 
                    border-radius: 12px; 
                    font-weight: 600;
                    font-size: 7.5px;
                    display: inline-block;
                }
                .status-unpaid { 
                    background: #fee2e2; 
                    color: #991b1b; 
                    padding: 2px 10px; 
                    border-radius: 12px; 
                    font-weight: 600;
                    font-size: 7.5px;
                    display: inline-block;
                }
                
                /* Footer */
                .footer-section {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    border-top: 2px solid #e5e7eb;
                    padding-top: 10px;
                    margin-top: 5px;
                    font-size: 8px;
                    color: #6b7280;
                }
                .footer-section .total-count {
                    font-weight: 600;
                    color: #1f2937;
                }
                .footer-section .generated {
                    color: #9ca3af;
                }
                .footer-section .page-number {
                    font-weight: 600;
                    color: #4f46e5;
                }
                
                /* Print optimization */
                @media print {
                    body { padding: 15px 20px; }
                    .summary-card { break-inside: avoid; }
                    tr { break-inside: avoid; }
                    
                    /* Add page number at bottom of each page */
                    @page {
                        @bottom-center {
                            content: "Page " counter(page) " of ${totalPages}";
                            font-size: 8px;
                            color: #9ca3af;
                        }
                    }
                }
                
                /* Zebra stripes for better readability */
                tbody tr:nth-child(even) {
                    background: #fafafa;
                }
                tbody tr:hover {
                    background: #f3f4f6;
                }
                
                /* Serial number column */
                .serial-col {
                    width: 40px;
                    min-width: 40px;
                }
            </style>
        </head>
        <body>
            <div class="report-container">
                <!-- Header -->
                <div class="header-section">
                    <div class="shop-info">
                        <h1>${shopName}</h1>
                        <div class="address">${shopAddress}</div>
                        <div class="contact">
                            <span>📞 ${shopPhone}</span>
                            <span>✉️ ${shopEmail}</span>
                        </div>
                    </div>
                    <div class="report-meta">
                        <div class="title">Sales Report</div>
                        <div class="subtitle">${filterText}</div>
                        <div class="datetime">${new Date().toLocaleString('en-US', { 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        })}</div>
                        <div class="page-info">Page ${currentPage.value} of ${totalPages}</div>
                    </div>
                </div>

                <!-- Filter Badge (if filters applied) -->
                ${filterInfo.length > 0 ? `
                <div class="filter-badge">
                    <strong>📊 Filters Applied:</strong> ${filterInfo.join(' • ')}
                </div>
                ` : ''}

                <!-- Summary Cards -->
                <div class="summary-grid">
                    <div class="summary-card">
                        <div class="label">Total Sales</div>
                        <div class="value value-sales">${totalSalesCount}</div>
                    </div>
                    <div class="summary-card">
                        <div class="label">Total Revenue</div>
                        <div class="value value-revenue">${totalRevenue}</div>
                    </div>
                    <div class="summary-card">
                        <div class="label">Total Profit</div>
                        <div class="value value-profit">${totalProfitAmount}</div>
                    </div>
                    <div class="summary-card">
                        <div class="label">Average Sale</div>
                        <div class="value value-average">${averageSaleAmount}</div>
                    </div>
                </div>

                <!-- Sales Table -->
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th class="text-center serial-col">#</th>
                                <th class="text-center">Invoice</th>
                                <th class="text-center">Date</th>
                                <th>Customer</th>
                                <th class="text-center">Type</th>
                                <th class="text-right">Amount</th>
                                <th class="text-right">Paid</th>
                                <th class="text-right">Due</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tableRows}
                        </tbody>
                    </table>
                </div>

                <!-- Footer -->
                <div class="footer-section">
                    <div>
                        Showing <span class="total-count">${salesData.length}</span> 
                        ${salesData.length !== filteredSales.value.length ? `of ${filteredSales.value.length} filtered` : ''} 
                        sales
                        ${salesData.length > 0 && salesData.length !== filteredSales.value.length ? `(Page ${currentPage.value} of ${totalPages})` : ''}
                    </div>
                    <div>
                        <span class="page-number">Page ${currentPage.value} of ${totalPages}</span>
                    </div>
                    <div class="generated">
                        Generated on ${new Date().toLocaleString()}
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
        
        <div class="min-h-screen w-full bg-gray-50 p-4">
            <div class="max-w-7xl mx-auto">
                <!-- Company Header (only on screen) -->
                <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 rounded-xl shadow-lg p-4 mb-4 text-white print:hidden">
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

                <!-- Smart Filter Bar -->
                <div class="bg-white rounded-xl shadow-sm p-2 mb-4 print:hidden">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <div class="flex items-center gap-1">
                            <i class="fas fa-calendar text-gray-400 text-[10px]"></i>
                            <input
                                v-model="filters.start_date"
                                type="date"
                                class="border border-gray-300 rounded-lg px-1.5 py-1 text-[10px] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 w-28"
                            />
                            <span class="text-[10px] text-gray-400">to</span>
                            <input
                                v-model="filters.end_date"
                                type="date"
                                class="border border-gray-300 rounded-lg px-1.5 py-1 text-[10px] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 w-28"
                            />
                        </div>

                        <div class="w-px h-5 bg-gray-300"></div>

                        <div class="flex items-center gap-1">
                            <i class="fas fa-credit-card text-gray-400 text-[10px]"></i>
                            <select
                                v-model="filters.payment_method"
                                class="border border-gray-300 rounded-lg px-1.5 py-1 text-[10px] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option value="all">All Payments</option>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="mobile_banking">Mobile</option>
                                <option value="credit">Credit</option>
                            </select>
                        </div>

                        <div class="w-px h-5 bg-gray-300"></div>

                        <div class="flex items-center gap-1">
                            <i class="fas fa-tag text-gray-400 text-[10px]"></i>
                            <select
                                v-model="filters.sale_type"
                                class="border border-gray-300 rounded-lg px-1.5 py-1 text-[10px] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            >
                                <option value="all">All Types</option>
                                <option value="retail">Retail</option>
                                <option value="wholesale">Wholesale</option>
                            </select>
                        </div>

                        <div class="w-px h-5 bg-gray-300"></div>

                        <div class="flex-1 min-w-[120px] relative">
                            <i class="fas fa-search absolute left-1.5 top-1/2 -translate-y-1/2 text-gray-400 text-[10px]"></i>
                            <input
                                v-model="filters.search"
                                type="text"
                                placeholder="Search invoice, customer..."
                                class="w-full border border-gray-300 rounded-lg pl-6 pr-2 py-1 text-[10px] focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            />
                        </div>

                        <button
                            @click="applyFilters"
                            :disabled="isFiltering"
                            class="bg-indigo-600 text-white px-2 py-1 rounded-lg hover:bg-indigo-700 transition text-[10px] flex items-center gap-1 disabled:opacity-50"
                        >
                            <i v-if="isFiltering" class="fas fa-spinner fa-spin text-[10px]"></i>
                            <i v-else class="fas fa-filter text-[10px]"></i>
                            {{ isFiltering ? 'Loading...' : 'Apply' }}
                        </button>
                        <button
                            @click="resetFilters"
                            class="bg-gray-200 text-gray-700 px-1.5 py-1 rounded-lg hover:bg-gray-300 transition text-[10px]"
                            title="Reset filters"
                        >
                            <i class="fas fa-undo"></i>
                        </button>
                        <button
                            @click="printReport"
                            class="bg-emerald-600 text-white px-2 py-1 rounded-lg hover:bg-emerald-700 transition text-[10px] flex items-center gap-1"
                        >
                            <i class="fas fa-print text-[10px]"></i> Print
                        </button>
                        <button
                            @click="exportReport"
                            class="bg-amber-600 text-white px-2 py-1 rounded-lg hover:bg-amber-700 transition text-[10px] flex items-center gap-1"
                        >
                            <i class="fas fa-file-export text-[10px]"></i> Export
                        </button>
                    </div>
                </div>

                <!-- Report Content - This will be printed -->
                <div id="report-print-content">
                    <!-- Summary Cards (Screen only - print uses different layout) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4 print:hidden">
                        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-indigo-500">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Sales</p>
                                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ summary.total_sales }}</p>
                                </div>
                                <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-shopping-cart text-indigo-600 text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-emerald-500">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</p>
                                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ formatCurrency(summary.total_amount) }}</p>
                                </div>
                                <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-dollar-sign text-emerald-600 text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-amber-500">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Profit</p>
                                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ formatCurrency(summary.total_profit) }}</p>
                                </div>
                                <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-coins text-amber-600 text-xl"></i>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-cyan-500">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Average Sale</p>
                                    <p class="text-2xl font-bold text-gray-800 mt-1">{{ formatCurrency(summary.average_sale) }}</p>
                                </div>
                                <div class="w-12 h-12 bg-cyan-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-chart-line text-cyan-600 text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>

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