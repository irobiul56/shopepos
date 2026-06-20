<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount } from 'vue';

const { props } = usePage();
const sale = ref(props.sale);
const shop = ref(props.shop);
const isPrinting = ref(false);
let printWindow = null;

// Auto print on mount
onMounted(() => {
    // Small delay to ensure DOM is fully rendered
    setTimeout(() => {
        printInvoice();
    }, 500);
});

// Clean up when component unmounts
onBeforeUnmount(() => {
    if (printWindow && !printWindow.closed) {
        printWindow.close();
    }
});

const printInvoice = () => {
    // Check if sale data exists
    if (!sale.value) {
        console.error('Sale data not found');
        alert('Sale data not found. Please try again.');
        return;
    }
    
    const printContent = document.getElementById('print-invoice-content');
    if (!printContent) {
        console.error('Print content not found');
        alert('Print content not found. Please refresh and try again.');
        return;
    }
    
    // Check if already printing
    if (isPrinting.value) {
        return;
    }
    
    isPrinting.value = true;
    
    // Get sale data directly from the sale ref
    const saleData = sale.value;
    const shopData = shop.value;
    
    // Log data for debugging
    console.log('Sale Data:', saleData);
    console.log('Shop Data:', shopData);
    
    // Calculate summary totals
    const subtotal = Number(saleData.subtotal || 0);
    const discount = Number(saleData.discount || 0);
    const tax = Number(saleData.tax || 0);
    const shipping = Number(saleData.shipping_cost || 0);
    const total = Number(saleData.total_amount || 0);
    const paid = Number(saleData.paid_amount || 0);
    const due = Number(saleData.due_amount || 0);
    
    // Format currency function for print
    const formatCurrencyForPrint = (amount) => {
        return '৳ ' + Number(amount).toFixed(2);
    };
    
    // Build items table rows
    let itemsRows = '';
    if (saleData.details && saleData.details.length > 0) {
        saleData.details.forEach((item, index) => {
            const productName = item.product?.name || 'Product';
            const quantity = Number(item.quantity || 0);
            const unitPrice = Number(item.unit_price || 0);
            const totalPrice = Number(item.total_price || 0);
            
            itemsRows += `
                <tr>
                    <td class="text-center">${index + 1}</td>
                    <td><strong>${productName}</strong></td>
                    <td class="text-center">${quantity}</td>
                    <td class="text-right">${formatCurrencyForPrint(unitPrice)}</td>
                    <td class="text-right"><strong>${formatCurrencyForPrint(totalPrice)}</strong></td>
                </tr>
            `;
        });
    } else {
        itemsRows = `
            <tr>
                <td colspan="5" class="text-center" style="padding:20px;color:#9ca3af;">
                    No items found
                </td>
            </tr>
        `;
    }
    
    // Status badge color
    const statusColors = {
        paid: '#d1fae5',
        partial: '#fef3c7',
        unpaid: '#fee2e2'
    };
    const statusTextColors = {
        paid: '#065f46',
        partial: '#92400e',
        unpaid: '#991b1b'
    };
    const statusLabel = saleData.payment_status ? 
        saleData.payment_status.charAt(0).toUpperCase() + saleData.payment_status.slice(1) : 
        'N/A';
    
    // Payment method display
    const paymentMethod = saleData.payment_method ? 
        saleData.payment_method.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) : 
        'N/A';
    
    // Sale type display
    const saleType = saleData.sale_type ? 
        saleData.sale_type.charAt(0).toUpperCase() + saleData.sale_type.slice(1) : 
        'N/A';
    
    // Open print window
    printWindow = window.open('', '_blank', 'width=900,height=700');
    if (!printWindow) {
        alert('Please allow popups for printing');
        isPrinting.value = false;
        setTimeout(() => {
            goBackToPos();
        }, 500);
        return;
    }
    
    // Write content to print window
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Invoice - ${saleData.invoice_no || 'Print'}</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                    font-size: 10px; 
                    padding: 25px 30px; 
                    background: white;
                    color: #1f2937;
                    line-height: 1.6;
                }
                .invoice-wrapper { 
                    max-width: 900px; 
                    margin: 0 auto; 
                    background: white;
                }
                
                /* Header Section */
                .invoice-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    border-bottom: 3px double #1f2937;
                    padding-bottom: 15px;
                    margin-bottom: 20px;
                }
                .brand-section {
                    flex: 1;
                }
                .brand-section .shop-name {
                    font-size: 24px;
                    font-weight: 700;
                    color: #1f2937;
                    letter-spacing: -0.5px;
                }
                .brand-section .shop-details {
                    font-size: 8.5px;
                    color: #6b7280;
                    margin-top: 3px;
                }
                .brand-section .shop-details div {
                    margin-top: 1px;
                }
                .invoice-title-section {
                    text-align: right;
                    flex-shrink: 0;
                    margin-left: 20px;
                }
                .invoice-title-section .invoice-badge {
                    background: #4f46e5;
                    color: white;
                    padding: 6px 18px;
                    border-radius: 6px;
                    font-size: 16px;
                    font-weight: 700;
                    letter-spacing: 1px;
                    display: inline-block;
                }
                .invoice-title-section .invoice-meta {
                    font-size: 8.5px;
                    color: #6b7280;
                    margin-top: 5px;
                }
                .invoice-title-section .invoice-meta span {
                    display: block;
                }
                
                /* Info Grid */
                .info-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 15px;
                    background: #f9fafb;
                    border: 1px solid #e5e7eb;
                    border-radius: 8px;
                    padding: 12px 16px;
                    margin-bottom: 20px;
                }
                .info-group label {
                    font-size: 7px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    color: #9ca3af;
                    font-weight: 600;
                    display: block;
                }
                .info-group .value {
                    font-size: 10.5px;
                    font-weight: 500;
                    color: #1f2937;
                    margin-top: 1px;
                }
                .info-group .value-sm {
                    font-size: 9px;
                    color: #6b7280;
                }
                
                /* Status Badge */
                .status-badge {
                    display: inline-block;
                    padding: 2px 14px;
                    border-radius: 12px;
                    font-weight: 600;
                    font-size: 8.5px;
                    background: ${statusColors[saleData.payment_status] || '#e5e7eb'};
                    color: ${statusTextColors[saleData.payment_status] || '#374151'};
                }
                
                /* Items Table */
                .table-wrapper {
                    border: 1px solid #e5e7eb;
                    border-radius: 8px;
                    overflow: hidden;
                    margin-bottom: 20px;
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
                    padding: 8px 12px; 
                    text-align: left; 
                    font-weight: 600;
                    font-size: 7.5px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    color: #374151;
                    border-bottom: 2px solid #d1d5db;
                }
                th.text-center { text-align: center; }
                th.text-right { text-align: right; }
                
                td { 
                    padding: 7px 12px; 
                    border-bottom: 1px solid #f3f4f6;
                    vertical-align: middle;
                }
                td.text-center { text-align: center; }
                td.text-right { text-align: right; }
                
                /* Zebra stripes */
                tbody tr:nth-child(even) {
                    background: #fafafa;
                }
                
                /* Summary Section */
                .summary-section {
                    display: flex;
                    justify-content: flex-end;
                    margin-bottom: 20px;
                }
                .summary-box {
                    width: 350px;
                    background: #f9fafb;
                    border: 1px solid #e5e7eb;
                    border-radius: 8px;
                    padding: 12px 16px;
                }
                .summary-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 3px 0;
                    font-size: 9px;
                }
                .summary-row .label {
                    color: #6b7280;
                }
                .summary-row .amount {
                    font-weight: 500;
                    color: #1f2937;
                }
                .summary-row.discount .amount {
                    color: #dc2626;
                }
                .summary-row.total {
                    border-top: 2px solid #1f2937;
                    margin-top: 5px;
                    padding-top: 8px;
                    font-size: 13px;
                    font-weight: 700;
                }
                .summary-row.total .amount {
                    color: #4f46e5;
                }
                .summary-row.paid .amount {
                    color: #059669;
                    font-weight: 600;
                }
                .summary-row.due .amount {
                    color: #d97706;
                    font-weight: 600;
                }
                
                /* Footer */
                .invoice-footer {
                    text-align: center;
                    border-top: 2px solid #e5e7eb;
                    padding-top: 15px;
                    margin-top: 10px;
                }
                .invoice-footer .thanks {
                    font-size: 12px;
                    font-weight: 600;
                    color: #1f2937;
                    margin-bottom: 2px;
                }
                .invoice-footer .note {
                    font-size: 7.5px;
                    color: #9ca3af;
                }
                .invoice-footer .generated {
                    font-size: 7px;
                    color: #d1d5db;
                    margin-top: 3px;
                }
                
                /* Print optimization */
                @media print {
                    body { padding: 15px 20px; }
                    .no-print { display: none !important; }
                    tr { break-inside: avoid; }
                    .summary-box { break-inside: avoid; }
                }
            </style>
        </head>
        <body>
            <div class="invoice-wrapper">
                <!-- Header -->
                <div class="invoice-header">
                    <div class="brand-section">
                        <div class="shop-name">${shopData?.name || 'Your Shop'}</div>
                        <div class="shop-details">
                            <div>${shopData?.address || 'Shop Address'}</div>
                            <div>📞 ${shopData?.phone || 'Phone Number'} | ✉️ ${shopData?.email || 'Email Address'}</div>
                        </div>
                    </div>
                    <div class="invoice-title-section">
                        <div class="invoice-badge">INVOICE</div>
                        <div class="invoice-meta">
                            <span><strong>Invoice #:</strong> ${saleData.invoice_no || 'N/A'}</span>
                            <span><strong>Date:</strong> ${saleData.sale_date ? new Date(saleData.sale_date).toLocaleString('en-US', { 
                                year: 'numeric', 
                                month: 'short', 
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            }) : 'N/A'}</span>
                        </div>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="info-grid">
                    <div class="info-group">
                        <label>Customer Information</label>
                        <div class="value"><strong>${saleData.customer?.name || 'Walk-in Customer'}</strong></div>
                        <div class="value-sm">📞 ${saleData.customer?.phone || 'N/A'}</div>
                        <div class="value-sm">✉️ ${saleData.customer?.email || 'N/A'}</div>
                    </div>
                    <div class="info-group" style="text-align:right;">
                        <label>Sale Information</label>
                        <div class="value">Type: <strong>${saleType}</strong></div>
                        <div class="value-sm" style="margin-top:3px;">
                            Status: <span class="status-badge">${statusLabel}</span>
                        </div>
                        <div class="value-sm">Payment: ${paymentMethod}</div>
                        <div class="value-sm" style="margin-top:2px;font-size:8px;color:#9ca3af;">
                            Sold by: ${saleData.user?.name || 'N/A'}
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th class="text-center" style="width:45px;">SL</th>
                                <th>Product Description</th>
                                <th class="text-center" style="width:70px;">Qty</th>
                                <th class="text-right" style="width:100px;">Unit Price</th>
                                <th class="text-right" style="width:110px;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsRows}
                        </tbody>
                    </table>
                </div>

                <!-- Summary -->
                <div class="summary-section">
                    <div class="summary-box">
                        <div class="summary-row">
                            <span class="label">Subtotal</span>
                            <span class="amount">${formatCurrencyForPrint(subtotal)}</span>
                        </div>
                        ${discount > 0 ? `
                        <div class="summary-row discount">
                            <span class="label">Discount (${saleData.discount_type || 'fixed'})</span>
                            <span class="amount">-${formatCurrencyForPrint(discount)}</span>
                        </div>
                        ` : ''}
                        ${tax > 0 ? `
                        <div class="summary-row">
                            <span class="label">Tax</span>
                            <span class="amount">${formatCurrencyForPrint(tax)}</span>
                        </div>
                        ` : ''}
                        ${shipping > 0 ? `
                        <div class="summary-row">
                            <span class="label">Shipping</span>
                            <span class="amount">${formatCurrencyForPrint(shipping)}</span>
                        </div>
                        ` : ''}
                        <div class="summary-row total">
                            <span class="label">Total Amount</span>
                            <span class="amount">${formatCurrencyForPrint(total)}</span>
                        </div>
                        <div class="summary-row paid">
                            <span class="label">Paid Amount</span>
                            <span class="amount">${formatCurrencyForPrint(paid)}</span>
                        </div>
                        ${due > 0 ? `
                        <div class="summary-row due">
                            <span class="label">Due Amount</span>
                            <span class="amount">${formatCurrencyForPrint(due)}</span>
                        </div>
                        ` : ''}
                        ${saleData.notes ? `
                        <div class="summary-row" style="border-top:1px solid #e5e7eb;margin-top:5px;padding-top:5px;font-size:8px;color:#6b7280;">
                            <span class="label">Notes:</span>
                            <span style="text-align:right;">${saleData.notes}</span>
                        </div>
                        ` : ''}
                    </div>
                </div>

                <!-- Footer -->
                <div class="invoice-footer">
                    <div class="thanks">Thank you for your business!</div>
                    <div class="note">This is a computer generated invoice. Please keep for your records.</div>
                    <div class="generated">Generated on ${new Date().toLocaleString('en-US', { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    })}</div>
                </div>
            </div>

            <script>
                // Auto print when loaded
                window.onload = function() {
                    // Small delay to ensure everything is rendered
                    setTimeout(function() {
                        window.print();
                    }, 300);
                    
                    // Close window after print
                    window.onafterprint = function() {
                        window.close();
                    };
                };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
    
    // Set a timeout to check if print window is closed
    const checkPrintWindow = setInterval(() => {
        if (printWindow && printWindow.closed) {
            clearInterval(checkPrintWindow);
            isPrinting.value = false;
            goBackToPos();
        }
    }, 2000);
    
    // Store the interval for cleanup
    window._printCheckInterval = checkPrintWindow;
};

// Function to go back to POS
const goBackToPos = () => {
    // Clear any intervals
    if (window._printCheckInterval) {
        clearInterval(window._printCheckInterval);
        window._printCheckInterval = null;
    }
    
    // Clean up print window
    if (printWindow && !printWindow.closed) {
        printWindow.close();
        printWindow = null;
    }
    
    // Remove event listener
    window.removeEventListener('message', goBackToPos);
    
    // Navigate back to POS
    router.visit(route('pos.index'));
};

// Manual back to POS
const handleBackToPos = () => {
    if (isPrinting.value) {
        if (confirm('Printing is in progress. Are you sure you want to go back?')) {
            // Close print window if open
            if (printWindow && !printWindow.closed) {
                printWindow.close();
                printWindow = null;
            }
            goBackToPos();
        }
    } else {
        goBackToPos();
    }
};

// Handle print cancel or completion
const handlePrintCancel = () => {
    // If print window is open, close it
    if (printWindow && !printWindow.closed) {
        printWindow.close();
        printWindow = null;
    }
    isPrinting.value = false;
    // Go back to POS
    goBackToPos();
};
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="'Invoice - ' + sale.invoice_no" />
        
        <div class="min-h-screen w-full bg-gray-50 p-4">
            <div class="max-w-4xl mx-auto">
                <!-- Invoice Content -->
                <div id="print-invoice-content" class="bg-white rounded-xl shadow-sm p-8">
                    <!-- Header -->
                    <div class="text-center mb-6">
                        <h1 class="text-2xl font-bold">{{ shop?.name || 'Your Shop' }}</h1>
                        <p class="text-gray-600">{{ shop?.address || 'Shop Address' }}</p>
                        <p class="text-gray-600">Phone: {{ shop?.phone || 'Phone Number' }}</p>
                        <p class="text-gray-600">Email: {{ shop?.email || 'Email Address' }}</p>
                        <h2 class="text-xl font-bold mt-4 text-indigo-700">SALE INVOICE</h2>
                    </div>
                    
                    <!-- Invoice Info -->
                    <div class="grid grid-cols-2 gap-4 mb-6 pb-4 border-b">
                        <div>
                            <p><strong>Invoice No:</strong> {{ sale.invoice_no }}</p>
                            <p><strong>Date:</strong> {{ new Date(sale.sale_date).toLocaleDateString() }}</p>
                            <p><strong>Sale Type:</strong> {{ sale.sale_type }}</p>
                        </div>
                        <div>
                            <p><strong>Customer:</strong> {{ sale.customer?.name || 'Walk-in Customer' }}</p>
                            <p><strong>Phone:</strong> {{ sale.customer?.phone || 'N/A' }}</p>
                            <p><strong>Email:</strong> {{ sale.customer?.email || 'N/A' }}</p>
                        </div>
                    </div>
                    
                    <!-- Items Table -->
                    <table class="w-full mb-6">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left">SL</th>
                                <th class="px-4 py-2 text-left">Product</th>
                                <th class="px-4 py-2 text-right">Qty</th>
                                <th class="px-4 py-2 text-right">Unit Price</th>
                                <th class="px-4 py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in sale.details" :key="index" class="border-b">
                                <td class="px-4 py-2">{{ index + 1 }}</td>
                                <td class="px-4 py-2">{{ item.product?.name || 'Product' }}</td>
                                <td class="px-4 py-2 text-right">{{ item.quantity }}</td>
                                <td class="px-4 py-2 text-right">${{ Number(item.unit_price).toFixed(2) }}</td>
                                <td class="px-4 py-2 text-right">${{ Number(item.total_price).toFixed(2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <!-- Summary -->
                    <div class="flex justify-end">
                        <div class="w-80">
                            <div class="flex justify-between py-1">
                                <span>Subtotal:</span>
                                <span>${{ Number(sale.subtotal).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between py-1 text-red-600">
                                <span>Discount ({{ sale.discount_type }}):</span>
                                <span>-${{ Number(sale.discount).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between py-1">
                                <span>Tax:</span>
                                <span>${{ Number(sale.tax).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between py-1">
                                <span>Shipping:</span>
                                <span>${{ Number(sale.shipping_cost).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between py-2 font-bold border-t">
                                <span>Total:</span>
                                <span class="text-indigo-700">${{ Number(sale.total_amount).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between py-1 text-emerald-600">
                                <span>Paid:</span>
                                <span>${{ Number(sale.paid_amount).toFixed(2) }}</span>
                            </div>
                            <div v-if="sale.due_amount > 0" class="flex justify-between py-1 text-amber-600 font-bold">
                                <span>Due:</span>
                                <span>${{ Number(sale.due_amount).toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between py-1 text-xs">
                                <span>Payment Status:</span>
                                <span :class="{
                                    'text-emerald-600': sale.payment_status === 'paid',
                                    'text-amber-600': sale.payment_status === 'partial',
                                    'text-rose-600': sale.payment_status === 'unpaid'
                                }" class="font-medium capitalize">
                                    {{ sale.payment_status }}
                                </span>
                            </div>
                            <div class="flex justify-between py-1 text-xs">
                                <span>Payment Method:</span>
                                <span>{{ sale.payment_method?.replace('_', ' ') || 'N/A' }}</span>
                            </div>
                            <div v-if="sale.notes" class="flex justify-between py-1 text-xs">
                                <span>Notes:</span>
                                <span>{{ sale.notes }}</span>
                            </div>
                            <div class="flex justify-between py-1 text-xs">
                                <span>Sold By:</span>
                                <span>{{ sale.user?.name || 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="text-center mt-8 pt-4 border-t">
                        <p class="text-gray-600">Thank you for your business!</p>
                        <p class="text-gray-500 text-sm">This is a computer generated invoice.</p>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="mt-4 text-center space-x-3">
                    <button 
                        @click="printInvoice"
                        :disabled="isPrinting"
                        class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition disabled:opacity-50"
                    >
                        <i v-if="isPrinting" class="fas fa-spinner fa-spin mr-2"></i>
                        <i v-else class="fas fa-print mr-2"></i>
                        {{ isPrinting ? 'Printing...' : 'Print Invoice' }}
                    </button>
                    
                    <button 
                        @click="handleBackToPos"
                        class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition"
                    >
                        <i class="fas fa-arrow-left mr-2"></i> Back to POS
                    </button>
                    
                    <button 
                        v-if="isPrinting"
                        @click="handlePrintCancel"
                        class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition"
                    >
                        <i class="fas fa-times mr-2"></i> Cancel Print
                    </button>
                </div>
                
                <!-- Info message -->
                <div class="mt-4 text-center text-sm text-gray-500">
                    <p v-if="!isPrinting">
                        <i class="fas fa-info-circle mr-1"></i> 
                        Print window will open automatically. After printing, you will be redirected to POS.
                    </p>
                    <p v-else>
                        <i class="fas fa-spinner fa-spin mr-1"></i> 
                        Printing in progress. Please wait...
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Add any additional styles here */
</style>