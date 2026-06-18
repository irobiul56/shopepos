<script setup>
import { ref, computed, watch } from 'vue'
import { usePage, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from "@inertiajs/vue3";
import { debounce } from 'lodash';

const { props } = usePage()

// Reactive data
const purchases = ref([])
const pagination = ref({})
const filters = ref({
    search: '',
    supplier_id: '',
    payment_status: '',
    date_from: '',
    date_to: ''
})

const currentPage = ref(1)
const isProcessing = ref(false)
const isPrinting = ref(false)

// Modal visibility
const isDetailsModalVisible = ref(false)
const isDeleteModalVisible = ref(false)
const selectedPurchase = ref(null)
const purchaseToDelete = ref(null)

// Custom notification
const notification = ref({
    show: false,
    message: '',
    type: 'success'
});

const showNotification = (message, type = 'success') => {
    notification.value = {
        show: true,
        message: message,
        type: type
    };
    
    setTimeout(() => {
        notification.value.show = false;
    }, 3000);
};

// Initialize data from props
const initData = () => {
    if (props.purchases) {
        purchases.value = props.purchases.data || []
        pagination.value = props.purchases
        currentPage.value = props.purchases.current_page || 1
    }
}

// Call init on mount
initData()

// Watch for prop changes
watch(() => props.purchases, () => {
    initData()
}, { deep: true })

// Fetch purchases with filters
const fetchPurchases = () => {
    router.get(route('purchases.index', {
        page: currentPage.value,
        search: filters.value.search,
        supplier_id: filters.value.supplier_id,
        payment_status: filters.value.payment_status,
        date_from: filters.value.date_from,
        date_to: filters.value.date_to
    }), {}, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (pageData) => {
            purchases.value = pageData.props.purchases?.data || []
            pagination.value = pageData.props.purchases || {}
        },
        onError: (errors) => {
            showNotification('Failed to fetch purchases', 'error')
        }
    })
}

// Debounced search
const debouncedSearch = debounce(() => {
    currentPage.value = 1
    fetchPurchases()
}, 500)

// Watch filters
watch(() => filters.value.search, () => {
    debouncedSearch()
})

watch(() => filters.value.supplier_id, () => {
    currentPage.value = 1
    fetchPurchases()
})

watch(() => filters.value.payment_status, () => {
    currentPage.value = 1
    fetchPurchases()
})

watch(() => filters.value.date_from, () => {
    currentPage.value = 1
    fetchPurchases()
})

watch(() => filters.value.date_to, () => {
    currentPage.value = 1
    fetchPurchases()
})

// Reset all filters
const resetFilters = () => {
    filters.value = {
        search: '',
        supplier_id: '',
        payment_status: '',
        date_from: '',
        date_to: ''
    }
    currentPage.value = 1
    fetchPurchases()
}

// Check if any filter is active
const hasActiveFilters = computed(() => {
    return filters.value.search || filters.value.supplier_id || filters.value.payment_status || filters.value.date_from || filters.value.date_to
})

// Pagination
const totalPages = computed(() => pagination.value.last_page || 1)

const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++
        fetchPurchases()
    }
}

const prevPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--
        fetchPurchases()
    }
}

// View purchase details
const viewDetails = (purchase) => {
    selectedPurchase.value = purchase
    isDetailsModalVisible.value = true
}

// Delete purchase functions
const confirmDelete = (purchase) => {
    purchaseToDelete.value = purchase
    isDeleteModalVisible.value = true
}

const deletePurchase = () => {
    if (!purchaseToDelete.value) return
    
    isProcessing.value = true
    
    router.delete(route('purchases.destroy', purchaseToDelete.value.id), {
        onSuccess: () => {
            isDeleteModalVisible.value = false
            fetchPurchases()
            showNotification('Purchase deleted successfully!', 'success')
            purchaseToDelete.value = null
        },
        onError: (errors) => {
            isDeleteModalVisible.value = false
            const errorMessage = errors.message || errors.error || 'Failed to delete purchase'
            showNotification(errorMessage, 'error')
        },
        onFinish: () => {
            isProcessing.value = false
        }
    })
}

const closeDeleteModal = () => {
    isDeleteModalVisible.value = false
    purchaseToDelete.value = null
}

// Print single purchase invoice
const printPurchase = async (purchase) => {
    if (isPrinting.value) return
    isPrinting.value = true
    
    try {
        const response = await fetch(route('purchases.print-data', purchase.id), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        
        if (!response.ok) {
            throw new Error('Failed to fetch purchase data')
        }
        
        const data = await response.json()
        const purchaseData = data.purchase || purchase
        
        const printWindow = window.open('', '_blank', 'width=900,height=700,toolbars=yes,menubar=yes,scrollbars=yes')
        
        if (!printWindow) {
            showNotification('Please allow pop-ups to print', 'error')
            return
        }
        
        const printContent = generateInvoicePrintContent(purchaseData)
        
        printWindow.document.write(printContent)
        printWindow.document.close()
        
        printWindow.onload = () => {
            printWindow.print()
            printWindow.onafterprint = () => {
                printWindow.close()
            }
        }
        
    } catch (error) {
        console.error('Print error:', error)
        showNotification('Failed to load purchase details for printing', 'error')
    } finally {
        isPrinting.value = false
    }
}

// Print full report
const printFullReport = async () => {
    if (isPrinting.value) return
    isPrinting.value = true
    
    try {
        // Build query parameters from current filters
        const params = new URLSearchParams()
        
        if (filters.value.search) params.append('search', filters.value.search)
        if (filters.value.supplier_id) params.append('supplier_id', filters.value.supplier_id)
        if (filters.value.payment_status) params.append('payment_status', filters.value.payment_status)
        if (filters.value.date_from) params.append('date_from', filters.value.date_from)
        if (filters.value.date_to) params.append('date_to', filters.value.date_to)
        
        const url = route('purchases.report-data') + (params.toString() ? '?' + params.toString() : '')
        
        console.log('Fetching report from:', url)
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            credentials: 'same-origin'
        })
        
        if (!response.ok) {
            const errorText = await response.text()
            console.error('Response error:', errorText)
            throw new Error(`HTTP ${response.status}: ${response.statusText}`)
        }
        
        const data = await response.json()
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to fetch report data')
        }
        
        // Check if there are any purchases
        if (!data.purchases || data.purchases.length === 0) {
            showNotification('No purchases found with the selected filters', 'error')
            return
        }
        
        const printWindow = window.open('', '_blank', 'width=1000,height=800,toolbars=yes,menubar=yes,scrollbars=yes')
        
        if (!printWindow) {
            showNotification('Please allow pop-ups to print', 'error')
            return
        }
        
        const printContent = generateReportPrintContent(data)
        
        printWindow.document.write(printContent)
        printWindow.document.close()
        
        printWindow.onload = () => {
            printWindow.print()
            printWindow.onafterprint = () => {
                printWindow.close()
            }
        }
        
        showNotification('Report generated successfully', 'success')
        
    } catch (error) {
        console.error('Report print error:', error)
        showNotification(error.message || 'Failed to load report data for printing', 'error')
    } finally {
        isPrinting.value = false
    }
}

// Generate single invoice print content
const generateInvoicePrintContent = (purchase) => {
    const formatCurrencyPrint = (amount) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'BDT',
            minimumFractionDigits: 2
        }).format(amount || 0)
    }
    
    const formatDatePrint = (date) => {
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        })
    }
    
    return `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Purchase Invoice - ${purchase.invoice_no}</title>
            <meta charset="utf-8">
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: #f0f0f0; }
                .invoice-container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; }
                .invoice-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
                .invoice-header h1 { margin: 0; font-size: 28px; letter-spacing: 2px; }
                .invoice-header p { margin: 10px 0 0; opacity: 0.9; font-size: 14px; }
                .invoice-body { padding: 30px; }
                .info-section { display: flex; justify-content: space-between; margin-bottom: 30px; gap: 20px; flex-wrap: wrap; }
                .info-box { flex: 1; background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #667eea; }
                .info-box h3 { margin: 0 0 10px 0; font-size: 16px; color: #333; }
                .info-box p { margin: 5px 0; font-size: 13px; color: #666; }
                .info-box .label { font-weight: 600; color: #333; display: inline-block; width: 100px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
                th { background: #667eea; color: white; padding: 12px; text-align: left; font-size: 14px; font-weight: 600; }
                td { padding: 10px 12px; border-bottom: 1px solid #e0e0e0; font-size: 13px; }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .summary { width: 350px; margin-left: auto; background: #f8f9fa; border-radius: 8px; padding: 15px; }
                .summary-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; }
                .summary-row.total { border-top: 2px solid #ddd; margin-top: 8px; padding-top: 12px; font-weight: bold; font-size: 18px; color: #28a745; }
                .summary-row.due { color: #dc3545; font-weight: bold; }
                .notes-section { background: #fff3cd; padding: 15px; border-radius: 8px; margin-top: 20px; border-left: 4px solid #ffc107; }
                .notes-section h4 { margin: 0 0 8px 0; font-size: 14px; color: #856404; }
                .notes-section p { margin: 0; font-size: 13px; color: #856404; }
                .invoice-footer { background: #f8f9fa; padding: 15px 30px; text-align: center; font-size: 12px; color: #999; border-top: 1px solid #e0e0e0; }
                @media print {
                    body { background: white; padding: 0; }
                    .invoice-container { box-shadow: none; margin: 0; border-radius: 0; }
                    .invoice-header { background: #667eea; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                    th { background: #667eea; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                }
            </style>
        </head>
        <body>
            <div class="invoice-container">
                <div class="invoice-header">
                    <h1>PURCHASE INVOICE</h1>
                    <p>${purchase.invoice_no}</p>
                </div>
                <div class="invoice-body">
                    <div class="info-section">
                        <div class="info-box">
                            <h3>Supplier Information</h3>
                            <p><span class="label">Name:</span> ${purchase.supplier?.name || 'N/A'}</p>
                            <p><span class="label">Phone:</span> ${purchase.supplier?.phone || 'N/A'}</p>
                            <p><span class="label">Email:</span> ${purchase.supplier?.email || 'N/A'}</p>
                            <p><span class="label">Address:</span> ${purchase.supplier?.address || 'N/A'}</p>
                        </div>
                        <div class="info-box">
                            <h3>Purchase Information</h3>
                            <p><span class="label">Invoice No:</span> ${purchase.invoice_no}</p>
                            <p><span class="label">Date:</span> ${formatDatePrint(purchase.purchase_date)}</p>
                            <p><span class="label">Status:</span> ${purchase.payment_status?.toUpperCase() || 'UNPAID'}</p>
                            <p><span class="label">Created By:</span> ${purchase.user?.name || 'N/A'}</p>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr><th>SL</th><th>Product Name</th><th class="text-center">Quantity</th><th class="text-right">Unit Price</th><th class="text-right">Total Price</th></tr>
                        </thead>
                        <tbody>
                            ${purchase.purchase_details?.map((detail, index) => `
                                <tr><td>${index + 1}</td><td>${detail.product?.name || 'N/A'}</td><td class="text-center">${detail.quantity}</td><td class="text-right">${formatCurrencyPrint(detail.unit_price)}</td><td class="text-right">${formatCurrencyPrint(detail.total_price)}</td></tr>
                            `).join('') || '<tr><td colspan="5" class="text-center">No products found</td></tr>'}
                        </tbody>
                    </table>
                    <div class="summary">
                        <div class="summary-row"><span>Subtotal:</span><span>${formatCurrencyPrint(purchase.subtotal)}</span></div>
                        ${purchase.discount > 0 ? `<div class="summary-row"><span>Discount:</span><span style="color: #dc3545;">- ${formatCurrencyPrint(purchase.discount)}</span></div>` : ''}
                        ${purchase.tax > 0 ? `<div class="summary-row"><span>Tax/VAT:</span><span>+ ${formatCurrencyPrint(purchase.tax)}</span></div>` : ''}
                        ${purchase.shipping_cost > 0 ? `<div class="summary-row"><span>Shipping Cost:</span><span>+ ${formatCurrencyPrint(purchase.shipping_cost)}</span></div>` : ''}
                        <div class="summary-row total"><span>Total Amount:</span><span>${formatCurrencyPrint(purchase.total_amount)}</span></div>
                        <div class="summary-row"><span>Paid Amount:</span><span style="color: #28a745;">${formatCurrencyPrint(purchase.paid_amount)}</span></div>
                        <div class="summary-row due"><span>Due Amount:</span><span>${formatCurrencyPrint(purchase.due_amount)}</span></div>
                    </div>
                    ${purchase.notes ? `<div class="notes-section"><h4>Notes:</h4><p>${purchase.notes}</p></div>` : ''}
                </div>
                <div class="invoice-footer"><p>Thank you for your business!</p><p>Generated on: ${new Date().toLocaleString()}</p></div>
            </div>
        </body>
        </html>
    `
}

// Generate simple Excel-style report print content
const generateReportPrintContent = (data) => {
    const formatCurrencyPrint = (amount) => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'BDT',
            minimumFractionDigits: 2
        }).format(amount || 0)
    }
    
    const formatDatePrint = (date) => {
        if (!date) return 'N/A'
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        })
    }
    
    const purchases = data.purchases || []
    const summary = data.summary || {}
    const shopName = data.shop_name || 'Purchase Management System'
    
    return `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Purchase Report</title>
            <meta charset="utf-8">
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: 'Segoe UI', Arial, sans-serif;
                    padding: 20px;
                    background: white;
                }
                
                .report-container {
                    max-width: 100%;
                    background: white;
                }
                
                /* Header */
                .header {
                    margin-bottom: 20px;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #000;
                }
                
                .shop-name {
                    font-size: 20px;
                    font-weight: bold;
                    margin-bottom: 5px;
                }
                
                .report-title {
                    font-size: 16px;
                    font-weight: bold;
                    margin: 10px 0;
                }
                
                /* Filter Info */
                .filter-info {
                    margin-bottom: 15px;
                    padding: 10px;
                    background: #f5f5f5;
                    font-size: 11px;
                    border: 1px solid #ddd;
                }
                
                /* Summary Table */
                .summary-table {
                    width: 100%;
                    margin-bottom: 20px;
                    border-collapse: collapse;
                }
                
                .summary-table td {
                    padding: 5px;
                    border: 1px solid #ddd;
                }
                
                .summary-label {
                    font-weight: bold;
                    background: #f0f0f0;
                    width: 150px;
                }
                
                /* Main Table */
                .data-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                    font-size: 12px;
                }
                
                .data-table th {
                    background: #f0f0f0;
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                    font-weight: bold;
                }
                
                .data-table td {
                    border: 1px solid #ddd;
                    padding: 6px 8px;
                }
                
                .text-right {
                    text-align: right;
                }
                
                .text-center {
                    text-align: center;
                }
                
                /* Footer */
                .footer {
                    margin-top: 30px;
                    padding-top: 10px;
                    border-top: 1px solid #ddd;
                    font-size: 10px;
                    text-align: center;
                }
                
                .signature {
                    margin-top: 30px;
                    display: flex;
                    justify-content: space-between;
                }
                
                .signature-line {
                    width: 200px;
                    text-align: center;
                }
                
                .signature-dash {
                    border-top: 1px solid #000;
                    margin-top: 40px;
                    padding-top: 5px;
                }
                
                @media print {
                    body {
                        padding: 0;
                        margin: 0;
                    }
                    
                    .filter-info {
                        background: #f5f5f5;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    
                    .data-table th {
                        background: #f0f0f0;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                }
            </style>
        </head>
        <body>
            <div class="report-container">
                <!-- Header -->
                <div class="header">
                    <div class="shop-name">${shopName}</div>
                    <div>Purchase Report</div>
                    <div style="font-size: 11px; margin-top: 5px;">
                        Generated: ${new Date().toLocaleString()}
                    </div>
                </div>
                
                <!-- Filter Information -->
                <div class="filter-info">
                    <table style="width: 100%;">
                        <tr>
                            ${data.date_from ? `<td><strong>From:</strong> ${formatDatePrint(data.date_from)}</td>` : ''}
                            ${data.date_to ? `<td><strong>To:</strong> ${formatDatePrint(data.date_to)}</td>` : ''}
                            ${data.supplier_name ? `<td><strong>Supplier:</strong> ${data.supplier_name}</td>` : ''}
                            ${data.payment_status ? `<td><strong>Status:</strong> ${data.payment_status.toUpperCase()}</td>` : ''}
                        </tr>
                    </table>
                </div>
                
                <!-- Summary -->
                <table class="summary-table">
                    <tr>
                        <td class="summary-label">Total Purchases:</td>
                        <td><strong>${summary.total_purchases || 0}</strong></td>
                        <td class="summary-label">Total Amount:</td>
                        <td><strong>${formatCurrencyPrint(summary.total_amount)}</strong></td>
                    </tr>
                    <tr>
                        <td class="summary-label">Total Paid:</td>
                        <td><strong>${formatCurrencyPrint(summary.total_paid)}</strong></td>
                        <td class="summary-label">Total Due:</td>
                        <td><strong>${formatCurrencyPrint(summary.total_due)}</strong></td>
                    </tr>
                </table>
                
                <!-- Purchases Table -->
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Invoice No</th>
                            <th>Date</th>
                            <th>Supplier</th>
                            <th>Phone</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Paid</th>
                            <th class="text-right">Due</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${purchases.map((purchase, index) => `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${purchase.invoice_no}</td>
                                <td>${formatDatePrint(purchase.purchase_date)}</td>
                                <td>${purchase.supplier?.name || 'N/A'}</td>
                                <td>${purchase.supplier?.phone || 'N/A'}</td>
                                <td class="text-right">${formatCurrencyPrint(purchase.total_amount)}</td>
                                <td class="text-right">${formatCurrencyPrint(purchase.paid_amount)}</td>
                                <td class="text-right">${formatCurrencyPrint(purchase.due_amount)}</td>
                                <td class="text-center">${purchase.payment_status?.toUpperCase() || 'UNPAID'}</td>
                            </tr>
                        `).join('')}
                        ${purchases.length === 0 ? `
                            <tr>
                                <td colspan="9" class="text-center" style="padding: 20px;">
                                    No purchases found
                                </td>
                            </tr>
                        ` : ''}
                    </tbody>
                    ${purchases.length > 0 ? `
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-right"><strong>Grand Total:</strong></td>
                                <td class="text-right"><strong>${formatCurrencyPrint(summary.total_amount)}</strong></td>
                                <td class="text-right"><strong>${formatCurrencyPrint(summary.total_paid)}</strong></td>
                                <td class="text-right"><strong>${formatCurrencyPrint(summary.total_due)}</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    ` : ''}
                </table>
                
                <!-- Footer -->
                <div class="footer">
                    <div>This is a computer generated report</div>
                    <div>${shopName} - Purchase Management System</div>
                </div>
                
                <!-- Signature -->
                <div class="signature">
                    <div class="signature-line">
                        <div class="signature-dash"></div>
                        <div>Generated By</div>
                        <div style="font-size: 11px;">${data.generated_by || 'System'}</div>
                    </div>
                    <div class="signature-line">
                        <div class="signature-dash"></div>
                        <div>Authorized Signature</div>
                    </div>
                </div>
            </div>
        </body>
        </html>
    `
}

// Create new purchase
const createPurchase = () => {
    router.get(route('purchases.create'))
}

// Format currency
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: 2
    }).format(amount || 0)
}

// Format date
const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    })
}

// Get payment status badge class
const getPaymentStatusClass = (status) => {
    switch (status) {
        case 'paid': return 'bg-green-100 text-green-800'
        case 'partial': return 'bg-yellow-100 text-yellow-800'
        case 'unpaid': return 'bg-red-100 text-red-800'
        default: return 'bg-gray-100 text-gray-800'
    }
}

const getPaymentStatusText = (status) => {
    switch (status) {
        case 'paid': return 'Paid'
        case 'partial': return 'Partial'
        case 'unpaid': return 'Unpaid'
        default: return status
    }
}

// Stats
const stats = computed(() => {
    const totalPurchases = pagination.value.total || 0
    const totalAmount = purchases.value.reduce((sum, p) => sum + parseFloat(p.total_amount || 0), 0)
    const totalPaid = purchases.value.reduce((sum, p) => sum + parseFloat(p.paid_amount || 0), 0)
    const totalDue = purchases.value.reduce((sum, p) => sum + parseFloat(p.due_amount || 0), 0)
    return { totalPurchases, totalAmount, totalPaid, totalDue }
})
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Purchase Management" />
        
        <!-- Custom Notification -->
        <Transition 
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="transform translate-x-full opacity-0"
            enter-to-class="transform translate-x-0 opacity-100"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="transform translate-x-0 opacity-100"
            leave-to-class="transform translate-x-full opacity-0"
        >
            <div v-if="notification.show" :class="['fixed top-5 right-5 z-50 px-5 py-3 rounded-lg shadow-xl flex items-center gap-3 min-w-[280px] max-w-md', notification.type === 'success' ? 'bg-gradient-to-r from-emerald-500 to-green-600' : 'bg-gradient-to-r from-red-500 to-rose-600']">
                <i :class="notification.type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle'" class="text-white text-lg"></i>
                <p class="text-white font-medium text-sm flex-1">{{ notification.message }}</p>
                <button @click="notification.show = false" class="text-white hover:text-gray-200"><i class="fas fa-times"></i></button>
            </div>
        </Transition>
        
        <div class="min-h-screen w-full bg-gradient-to-br from-gray-50 to-blue-50/30 px-4 py-6">
            <div class="max-w-[1600px] mx-auto">
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-shopping-cart text-indigo-600"></i>
                            Purchase Management
                        </h1>
                        <p class="text-sm text-gray-500 mt-1">Manage all your purchase orders</p>
                    </div>
                    <div class="flex gap-3">
                        <button @click="printFullReport" :disabled="isPrinting" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-xl transition flex items-center gap-2 text-sm font-semibold shadow-md">
                            <i v-if="isPrinting" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-print"></i>
                            Print Report
                        </button>
                        <button @click="createPurchase" class="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white px-5 py-2.5 rounded-xl transition shadow-md flex items-center gap-2 text-sm font-semibold">
                            <i class="fas fa-plus"></i>
                            New Purchase
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-indigo-500 hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div><p class="text-gray-500 text-xs uppercase tracking-wide">Total Purchases</p><p class="text-2xl font-bold text-gray-800">{{ stats.totalPurchases }}</p></div>
                            <div class="h-10 w-10 bg-indigo-100 rounded-lg flex items-center justify-center"><i class="fas fa-shopping-cart text-indigo-600"></i></div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-blue-500 hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div><p class="text-gray-500 text-xs uppercase tracking-wide">Total Amount</p><p class="text-2xl font-bold text-gray-800">{{ formatCurrency(stats.totalAmount) }}</p></div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-green-500 hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div><p class="text-gray-500 text-xs uppercase tracking-wide">Total Paid</p><p class="text-2xl font-bold text-green-600">{{ formatCurrency(stats.totalPaid) }}</p></div>
                            <div class="h-10 w-10 bg-green-100 rounded-lg flex items-center justify-center"><i class="fas fa-check-circle text-green-600"></i></div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-red-500 hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div><p class="text-gray-500 text-xs uppercase tracking-wide">Total Due</p><p class="text-2xl font-bold text-red-600">{{ formatCurrency(stats.totalDue) }}</p></div>
                            <div class="h-10 w-10 bg-red-100 rounded-lg flex items-center justify-center"><i class="fas fa-hand-holding-usd text-red-600"></i></div>
                        </div>
                    </div>
                </div>

                <!-- Smart Filter Bar -->
                <div class="bg-white rounded-xl shadow-sm mb-6 p-3">
                    <div class="flex flex-col lg:flex-row gap-3">
                        <div class="flex-1 min-w-[200px]">
                            <div class="relative"><i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i><input v-model="filters.search" type="text" placeholder="Search by invoice no, supplier name..." class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 text-sm transition"></div>
                        </div>
                        <div class="w-full lg:w-56">
                            <div class="relative"><i class="fas fa-building absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i><select v-model="filters.supplier_id" class="w-full pl-9 pr-8 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 appearance-none bg-white text-sm cursor-pointer"><option value="">All Suppliers</option><option v-for="supplier in props.suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option></select><i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i></div>
                        </div>
                        <div class="w-full lg:w-48">
                            <div class="relative"><i class="fas fa-credit-card absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i><select v-model="filters.payment_status" class="w-full pl-9 pr-8 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 appearance-none bg-white text-sm cursor-pointer"><option value="">All Status</option><option value="paid">Paid</option><option value="partial">Partial</option><option value="unpaid">Unpaid</option></select><i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i></div>
                        </div>
                        <div class="w-full lg:w-48"><div class="relative"><i class="fas fa-calendar-alt absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i><input v-model="filters.date_from" type="date" class="w-full pl-9 pr-2 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 text-sm" placeholder="From Date"></div></div>
                        <div class="w-full lg:w-48"><div class="relative"><i class="fas fa-calendar-alt absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i><input v-model="filters.date_to" type="date" class="w-full pl-9 pr-2 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 text-sm" placeholder="To Date"></div></div>
                        <button v-if="hasActiveFilters" @click="resetFilters" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition text-sm font-medium flex items-center gap-2"><i class="fas fa-times"></i>Reset</button>
                    </div>
                </div>

                <!-- Purchases Table -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead><tr class="bg-gradient-to-r from-indigo-50 to-purple-50 border-b border-gray-200"><th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Invoice No</th><th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Supplier</th><th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th><th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Total Amount</th><th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Paid</th><th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Due</th><th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Status</th><th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Actions</th></tr></thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-if="purchases.length === 0"><td colspan="8" class="px-4 py-12 text-center"><i class="fas fa-shopping-cart text-4xl text-gray-300 mb-3 block"></i><p class="text-gray-500">No purchases found</p><button @click="createPurchase" class="mt-3 text-indigo-600 hover:text-indigo-700 text-sm font-medium">Click here to create your first purchase</button></td></tr>
                                <tr v-for="purchase in purchases" :key="purchase.id" class="hover:bg-gray-50 transition">
                                    <td class="px-4 py-3"><div><p class="font-medium text-gray-800 text-sm">{{ purchase.invoice_no }}</p><p class="text-xs text-gray-400">ID: #{{ purchase.id }}</p></div></td>
                                    <td class="px-4 py-3"><div><p class="text-sm text-gray-800">{{ purchase.supplier?.name }}</p><p class="text-xs text-gray-500">{{ purchase.supplier?.phone }}</p></div></td>
                                    <td class="px-4 py-3"><p class="text-sm text-gray-600">{{ formatDate(purchase.purchase_date) }}</p></td>
                                    <td class="px-4 py-3 text-right"><span class="text-sm font-semibold text-gray-800">{{ formatCurrency(purchase.total_amount) }}</span></td>
                                    <td class="px-4 py-3 text-right"><span class="text-sm text-green-600">{{ formatCurrency(purchase.paid_amount) }}</span></td>
                                    <td class="px-4 py-3 text-right"><span :class="['text-sm font-semibold', purchase.due_amount > 0 ? 'text-red-600' : 'text-green-600']">{{ formatCurrency(purchase.due_amount) }}</span></td>
                                    <td class="px-4 py-3 text-center"><span :class="['px-2 py-1 inline-flex text-xs leading-4 font-semibold rounded-full', getPaymentStatusClass(purchase.payment_status)]">{{ getPaymentStatusText(purchase.payment_status) }}</span></td>
                                    <td class="px-4 py-3"><div class="flex items-center justify-center gap-2"><button @click="viewDetails(purchase)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="View Details"><i class="fas fa-eye"></i></button><button @click="printPurchase(purchase)" :disabled="isPrinting" class="p-1.5 text-gray-600 hover:bg-gray-100 rounded-lg transition" title="Print"><i v-if="isPrinting" class="fas fa-spinner fa-spin"></i><i v-else class="fas fa-print"></i></button><button @click="confirmDelete(purchase)" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition" title="Delete" :disabled="purchase.payment_status === 'paid'"><i class="fas fa-trash-alt"></i></button></div></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="totalPages > 1" class="px-4 py-3 bg-gray-50 border-t border-gray-200"><div class="flex items-center justify-between flex-wrap gap-3"><div class="text-sm text-gray-600">Showing {{ (currentPage - 1) * 10 + 1 }} to {{ Math.min(currentPage * 10, pagination.total) }} of {{ pagination.total }} results</div><div class="flex gap-2"><button @click="prevPage" :disabled="currentPage === 1" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition"><i class="fas fa-chevron-left mr-1 text-xs"></i>Previous</button><button v-for="page in Math.min(5, totalPages)" :key="page" @click="currentPage = page; fetchPurchases()" :class="['px-3 py-1.5 rounded-lg text-sm font-medium transition', currentPage === page ? 'bg-indigo-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50']">{{ page }}</button><button @click="nextPage" :disabled="currentPage === totalPages" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition">Next<i class="fas fa-chevron-right ml-1 text-xs"></i></button></div></div></div>
                </div>
            </div>
        </div>

        <!-- Purchase Details Modal -->
        <div v-if="isDetailsModalVisible" class="fixed inset-0 z-50 overflow-y-auto" @click.self="isDetailsModalVisible = false"><div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center"><div class="fixed inset-0 transition-opacity" aria-hidden="true"><div class="absolute inset-0 bg-gray-900 opacity-50"></div></div><div class="inline-block w-full max-w-4xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl"><div class="flex items-center justify-between mb-6"><h3 class="text-2xl font-bold text-gray-900 flex items-center gap-2"><i class="fas fa-file-invoice text-indigo-600"></i>Purchase Details</h3><button @click="isDetailsModalVisible = false" class="text-gray-400 hover:text-gray-600 transition-colors"><i class="fas fa-times text-xl"></i></button></div><div v-if="selectedPurchase" class="space-y-6"><div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg"><div><p class="text-xs text-gray-500">Invoice Number</p><p class="font-semibold text-gray-800">{{ selectedPurchase.invoice_no }}</p></div><div><p class="text-xs text-gray-500">Purchase Date</p><p class="font-semibold text-gray-800">{{ formatDate(selectedPurchase.purchase_date) }}</p></div><div><p class="text-xs text-gray-500">Supplier</p><p class="font-semibold text-gray-800">{{ selectedPurchase.supplier?.name }}</p><p class="text-xs text-gray-500">{{ selectedPurchase.supplier?.phone }}</p></div><div><p class="text-xs text-gray-500">Payment Status</p><span :class="['px-2 py-1 inline-flex text-xs leading-4 font-semibold rounded-full', getPaymentStatusClass(selectedPurchase.payment_status)]">{{ getPaymentStatusText(selectedPurchase.payment_status) }}</span></div></div><div><h4 class="font-semibold text-gray-800 mb-3 flex items-center gap-2"><i class="fas fa-boxes text-orange-600"></i>Products</h4><div class="overflow-x-auto"><table class="w-full"><thead class="bg-gray-100"><tr><th class="px-4 py-2 text-left text-sm font-semibold">Product</th><th class="px-4 py-2 text-center text-sm font-semibold">Quantity</th><th class="px-4 py-2 text-right text-sm font-semibold">Unit Price</th><th class="px-4 py-2 text-right text-sm font-semibold">Total Price</th></tr></thead><tbody><tr v-for="detail in selectedPurchase.purchase_details" :key="detail.id" class="border-b"><td class="px-4 py-2 text-sm">{{ detail.product?.name }}</td><td class="px-4 py-2 text-center text-sm">{{ detail.quantity }}</td><td class="px-4 py-2 text-right text-sm">{{ formatCurrency(detail.unit_price) }}</td><td class="px-4 py-2 text-right text-sm font-semibold">{{ formatCurrency(detail.total_price) }}</td></tr></tbody><tfoot class="bg-gray-50"><tr><td colspan="3" class="px-4 py-2 text-right font-semibold">Subtotal:</td><td class="px-4 py-2 text-right font-semibold">{{ formatCurrency(selectedPurchase.subtotal) }}</td></tr><tr v-if="selectedPurchase.discount > 0"><td colspan="3" class="px-4 py-2 text-right text-sm">Discount:</td><td class="px-4 py-2 text-right text-red-600">{{ formatCurrency(selectedPurchase.discount) }}</td></tr><tr v-if="selectedPurchase.tax > 0"><td colspan="3" class="px-4 py-2 text-right text-sm">Tax/VAT:</td><td class="px-4 py-2 text-right text-yellow-600">{{ formatCurrency(selectedPurchase.tax) }}</td></tr><tr v-if="selectedPurchase.shipping_cost > 0"><td colspan="3" class="px-4 py-2 text-right text-sm">Shipping:</td><td class="px-4 py-2 text-right text-blue-600">{{ formatCurrency(selectedPurchase.shipping_cost) }}</td></tr><tr class="border-t-2"><td colspan="3" class="px-4 py-2 text-right font-bold">Total Amount:</td><td class="px-4 py-2 text-right font-bold text-green-600">{{ formatCurrency(selectedPurchase.total_amount) }}</td></tr><tr><td colspan="3" class="px-4 py-2 text-right text-sm">Paid Amount:</td><td class="px-4 py-2 text-right text-green-600">{{ formatCurrency(selectedPurchase.paid_amount) }}</td></tr><tr><td colspan="3" class="px-4 py-2 text-right text-sm font-semibold">Due Amount:</td><td class="px-4 py-2 text-right font-semibold text-red-600">{{ formatCurrency(selectedPurchase.due_amount) }}</td></tr></tfoot></table></div></div><div v-if="selectedPurchase.notes" class="bg-yellow-50 p-3 rounded-lg"><p class="text-xs text-gray-500 mb-1">Notes:</p><p class="text-sm text-gray-700">{{ selectedPurchase.notes }}</p></div><div class="flex justify-end gap-3 pt-4 border-t"><button @click="isDetailsModalVisible = false" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">Close</button><button @click="printPurchase(selectedPurchase)" class="px-4 py-2 bg-gray-600 text-white rounded-lg text-sm font-medium hover:bg-gray-700 transition"><i class="fas fa-print mr-2"></i>Print</button></div></div></div></div></div>

        <!-- Delete Confirmation Modal -->
        <div v-if="isDeleteModalVisible" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeDeleteModal"><div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center"><div class="fixed inset-0 transition-opacity" aria-hidden="true"><div class="absolute inset-0 bg-gray-900 opacity-50"></div></div><div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl"><div class="text-center"><div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4"><i class="fas fa-exclamation-triangle text-3xl text-red-600"></i></div><h3 class="text-xl font-bold text-gray-900 mb-2">Delete Purchase</h3><p class="text-gray-600 mb-4">Are you sure you want to delete this purchase?</p><p class="text-sm text-red-600 mb-6"><i class="fas fa-warning mr-1"></i><strong>Warning:</strong> This action will: <ul class="list-disc list-inside mt-2 text-left"><li>Remove this purchase record</li><li>Decrease product stock quantities</li><li>Update supplier due amount</li></ul></p><div class="flex justify-center space-x-3"><button @click="closeDeleteModal" class="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">Cancel</button><button @click="deletePurchase" :disabled="isProcessing" class="px-6 py-2.5 border border-transparent rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 transition"><i v-if="isProcessing" class="fas fa-spinner fa-spin mr-2"></i>Delete</button></div></div></div></div></div>
    </AuthenticatedLayout>
</template>

<style scoped>
.transition { transition: all 0.2s ease; }
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
</style>