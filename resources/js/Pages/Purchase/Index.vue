<script setup>
import { ref, computed, watch, onMounted } from 'vue'
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
            fetchPurchases() // Refresh the list
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

// Print purchase
const printPurchase = (purchase) => {
    // Open print window with purchase details
    const printWindow = window.open('', '_blank', 'width=800,height=600')
    
    if (!printWindow) {
        showNotification('Please allow pop-ups to print', 'error')
        return
    }
    
    // Get purchase details with products
    router.get(route('purchases.show', purchase.id), {}, {
        onSuccess: (response) => {
            const purchaseData = response.props.purchase
            
            // Create print content
            const printContent = generatePrintContent(purchaseData)
            
            printWindow.document.write(printContent)
            printWindow.document.close()
            printWindow.print()
        },
        onError: () => {
            showNotification('Failed to load purchase details for printing', 'error')
            printWindow.close()
        }
    })
}

// Generate print content
const generatePrintContent = (purchase) => {
    return `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Purchase Invoice - ${purchase.invoice_no}</title>
            <meta charset="utf-8">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 20px;
                    background: white;
                }
                .invoice-container {
                    max-width: 800px;
                    margin: 0 auto;
                    background: white;
                    padding: 20px;
                    border: 1px solid #ddd;
                }
                .header {
                    text-align: center;
                    border-bottom: 2px solid #333;
                    padding-bottom: 10px;
                    margin-bottom: 20px;
                }
                .header h1 {
                    margin: 0;
                    color: #333;
                }
                .header p {
                    margin: 5px 0;
                    color: #666;
                }
                .info-section {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 20px;
                    padding: 10px;
                    background: #f9f9f9;
                }
                .info-box {
                    flex: 1;
                }
                .info-box h3 {
                    margin: 0 0 5px 0;
                    font-size: 14px;
                    color: #333;
                }
                .info-box p {
                    margin: 3px 0;
                    font-size: 12px;
                    color: #666;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                }
                th {
                    background-color: #f2f2f2;
                    font-weight: bold;
                }
                .text-right {
                    text-align: right;
                }
                .text-center {
                    text-align: center;
                }
                .summary {
                    width: 300px;
                    margin-left: auto;
                    margin-top: 20px;
                }
                .summary table {
                    width: 100%;
                }
                .summary td {
                    border: none;
                    padding: 5px;
                }
                .total-row {
                    font-weight: bold;
                    font-size: 16px;
                    border-top: 2px solid #333;
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    padding-top: 10px;
                    border-top: 1px solid #ddd;
                    font-size: 12px;
                    color: #666;
                }
                @media print {
                    body {
                        margin: 0;
                        padding: 0;
                    }
                    .invoice-container {
                        border: none;
                    }
                }
            </style>
        </head>
        <body>
            <div class="invoice-container">
                <div class="header">
                    <h1>PURCHASE INVOICE</h1>
                    <p>Invoice No: ${purchase.invoice_no}</p>
                    <p>Date: ${new Date(purchase.purchase_date).toLocaleDateString()}</p>
                </div>
                
                <div class="info-section">
                    <div class="info-box">
                        <h3>Supplier Information:</h3>
                        <p><strong>Name:</strong> ${purchase.supplier?.name || 'N/A'}</p>
                        <p><strong>Phone:</strong> ${purchase.supplier?.phone || 'N/A'}</p>
                        <p><strong>Address:</strong> ${purchase.supplier?.address || 'N/A'}</p>
                    </div>
                    <div class="info-box">
                        <h3>Purchase Information:</h3>
                        <p><strong>Invoice No:</strong> ${purchase.invoice_no}</p>
                        <p><strong>Date:</strong> ${new Date(purchase.purchase_date).toLocaleDateString()}</p>
                        <p><strong>Status:</strong> ${purchase.payment_status.toUpperCase()}</p>
                    </div>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Product</th>
                            <th class="text-center">Quantity</th>
                            <th class="text-right">Unit Price</th>
                            <th class="text-right">Total Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${purchase.purchase_details?.map((detail, index) => `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${detail.product?.name || 'N/A'}</td>
                                <td class="text-center">${detail.quantity}</td>
                                <td class="text-right">${formatCurrencyPrint(detail.unit_price)}</td>
                                <td class="text-right">${formatCurrencyPrint(detail.total_price)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                
                <div class="summary">
                    <table>
                        <tr>
                            <td>Subtotal:</td>
                            <td class="text-right">${formatCurrencyPrint(purchase.subtotal)}</td>
                        </tr>
                        ${purchase.discount > 0 ? `
                        <tr>
                            <td>Discount:</td>
                            <td class="text-right">${formatCurrencyPrint(purchase.discount)}</td>
                        </tr>
                        ` : ''}
                        ${purchase.tax > 0 ? `
                        <tr>
                            <td>Tax/VAT:</td>
                            <td class="text-right">${formatCurrencyPrint(purchase.tax)}</td>
                        </tr>
                        ` : ''}
                        ${purchase.shipping_cost > 0 ? `
                        <tr>
                            <td>Shipping Cost:</td>
                            <td class="text-right">${formatCurrencyPrint(purchase.shipping_cost)}</td>
                        </tr>
                        ` : ''}
                        <tr class="total-row">
                            <td><strong>Total Amount:</strong></td>
                            <td class="text-right"><strong>${formatCurrencyPrint(purchase.total_amount)}</strong></td>
                        </tr>
                        <tr>
                            <td>Paid Amount:</td>
                            <td class="text-right">${formatCurrencyPrint(purchase.paid_amount)}</td>
                        </tr>
                        <tr>
                            <td>Due Amount:</td>
                            <td class="text-right" style="color: ${purchase.due_amount > 0 ? 'red' : 'green'}">${formatCurrencyPrint(purchase.due_amount)}</td>
                        </tr>
                    </table>
                </div>
                
                ${purchase.notes ? `
                <div style="margin-top: 20px; padding: 10px; background: #f9f9f9;">
                    <strong>Notes:</strong>
                    <p style="margin: 5px 0 0 0; font-size: 12px;">${purchase.notes}</p>
                </div>
                ` : ''}
                
                <div class="footer">
                    <p>Thank you for your business!</p>
                    <p>Generated on: ${new Date().toLocaleString()}</p>
                </div>
            </div>
            <script>
                function formatCurrencyPrint(amount) {
                    return new Intl.NumberFormat('en-US', {
                        style: 'currency',
                        currency: 'BDT',
                        minimumFractionDigits: 2
                    }).format(amount || 0);
                }
            <\/script>
        </body>
        </html>
    `
}

const formatCurrencyPrint = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: 2
    }).format(amount || 0)
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
        case 'paid':
            return 'bg-green-100 text-green-800'
        case 'partial':
            return 'bg-yellow-100 text-yellow-800'
        case 'unpaid':
            return 'bg-red-100 text-red-800'
        default:
            return 'bg-gray-100 text-gray-800'
    }
}

const getPaymentStatusText = (status) => {
    switch (status) {
        case 'paid':
            return 'Paid'
        case 'partial':
            return 'Partial'
        case 'unpaid':
            return 'Unpaid'
        default:
            return status
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
            <div 
                v-if="notification.show" 
                :class="[
                    'fixed top-5 right-5 z-50 px-5 py-3 rounded-lg shadow-xl flex items-center gap-3 min-w-[280px] max-w-md',
                    notification.type === 'success' ? 'bg-gradient-to-r from-emerald-500 to-green-600' : 'bg-gradient-to-r from-red-500 to-rose-600'
                ]"
            >
                <i :class="notification.type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle'" class="text-white text-lg"></i>
                <p class="text-white font-medium text-sm flex-1">{{ notification.message }}</p>
                <button @click="notification.show = false" class="text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </Transition>
        
        <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50/30 px-4 py-6">
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
                    <button 
                        @click="createPurchase" 
                        class="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white px-5 py-2.5 rounded-xl transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2 text-sm font-semibold"
                    >
                        <i class="fas fa-plus"></i>
                        New Purchase
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-indigo-500 hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-xs uppercase tracking-wide">Total Purchases</p>
                                <p class="text-2xl font-bold text-gray-800">{{ stats.totalPurchases }}</p>
                            </div>
                            <div class="h-10 w-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-shopping-cart text-indigo-600"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-blue-500 hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-xs uppercase tracking-wide">Total Amount</p>
                                <p class="text-2xl font-bold text-gray-800">{{ formatCurrency(stats.totalAmount) }}</p>
                            </div>
                            <div class="h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-dollar-sign text-blue-600"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-green-500 hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-xs uppercase tracking-wide">Total Paid</p>
                                <p class="text-2xl font-bold text-green-600">{{ formatCurrency(stats.totalPaid) }}</p>
                            </div>
                            <div class="h-10 w-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-600"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-red-500 hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-xs uppercase tracking-wide">Total Due</p>
                                <p class="text-2xl font-bold text-red-600">{{ formatCurrency(stats.totalDue) }}</p>
                            </div>
                            <div class="h-10 w-10 bg-red-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-hand-holding-usd text-red-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Smart Filter Bar -->
                <div class="bg-white rounded-xl shadow-sm mb-6 p-3">
                    <div class="flex flex-col lg:flex-row gap-3">
                        <div class="flex-1 min-w-[200px]">
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                <input 
                                    v-model="filters.search"
                                    type="text" 
                                    placeholder="Search by invoice no, supplier name..."
                                    class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 text-sm transition"
                                >
                            </div>
                        </div>

                        <div class="w-full lg:w-56">
                            <div class="relative">
                                <i class="fas fa-building absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                <select 
                                    v-model="filters.supplier_id"
                                    class="w-full pl-9 pr-8 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 appearance-none bg-white text-sm cursor-pointer"
                                >
                                    <option value="">All Suppliers</option>
                                    <option v-for="supplier in props.suppliers" :key="supplier.id" :value="supplier.id">
                                        {{ supplier.name }}
                                    </option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                            </div>
                        </div>

                        <div class="w-full lg:w-48">
                            <div class="relative">
                                <i class="fas fa-credit-card absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                <select 
                                    v-model="filters.payment_status"
                                    class="w-full pl-9 pr-8 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 appearance-none bg-white text-sm cursor-pointer"
                                >
                                    <option value="">All Status</option>
                                    <option value="paid">Paid</option>
                                    <option value="partial">Partial</option>
                                    <option value="unpaid">Unpaid</option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                            </div>
                        </div>

                        <div class="w-full lg:w-56">
                            <div class="relative">
                                <i class="fas fa-calendar-alt absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                <input 
                                    v-model="filters.date_from"
                                    type="date" 
                                    class="w-full pl-9 pr-2 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 text-sm"
                                    placeholder="From Date"
                                >
                            </div>
                        </div>
                        <div class="w-full lg:w-56">
                            <div class="relative">
                                <i class="fas fa-calendar-alt absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                <input 
                                    v-model="filters.date_to"
                                    type="date" 
                                    class="w-full pl-9 pr-2 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 text-sm"
                                    placeholder="To Date"
                                >
                            </div>
                        </div>

                        <button 
                            v-if="hasActiveFilters"
                            @click="resetFilters"
                            class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition text-sm font-medium flex items-center gap-2"
                        >
                            <i class="fas fa-times"></i>
                            Reset
                        </button>
                    </div>
                </div>

                <!-- Purchases Table -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gradient-to-r from-indigo-50 to-purple-50 border-b border-gray-200">
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Invoice No</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Supplier</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Total Amount</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Paid</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Due</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-if="purchases.length === 0">
                                    <td colspan="8" class="px-4 py-12 text-center">
                                        <i class="fas fa-shopping-cart text-4xl text-gray-300 mb-3 block"></i>
                                        <p class="text-gray-500">No purchases found</p>
                                        <button @click="createPurchase" class="mt-3 text-indigo-600 hover:text-indigo-700 text-sm font-medium">
                                            Click here to create your first purchase
                                        </button>
                                    </td>
                                </tr>
                                <tr 
                                    v-for="purchase in purchases" 
                                    :key="purchase.id" 
                                    class="hover:bg-gray-50 transition group"
                                >
                                    <td class="px-4 py-3">
                                        <div>
                                            <p class="font-medium text-gray-800 text-sm">{{ purchase.invoice_no }}</p>
                                            <p class="text-xs text-gray-400">ID: #{{ purchase.id }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div>
                                            <p class="text-sm text-gray-800">{{ purchase.supplier?.name }}</p>
                                            <p class="text-xs text-gray-500">{{ purchase.supplier?.phone }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-sm text-gray-600">{{ formatDate(purchase.purchase_date) }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-sm font-semibold text-gray-800">{{ formatCurrency(purchase.total_amount) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-sm text-green-600">{{ formatCurrency(purchase.paid_amount) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span :class="['text-sm font-semibold', purchase.due_amount > 0 ? 'text-red-600' : 'text-green-600']">
                                            {{ formatCurrency(purchase.due_amount) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span :class="['px-2 py-1 inline-flex text-xs leading-4 font-semibold rounded-full', getPaymentStatusClass(purchase.payment_status)]">
                                            {{ getPaymentStatusText(purchase.payment_status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-2">
                                            <button 
                                                @click="viewDetails(purchase)"
                                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                                title="View Details"
                                            >
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button 
                                                @click="printPurchase(purchase)"
                                                class="p-1.5 text-gray-600 hover:bg-gray-100 rounded-lg transition"
                                                title="Print"
                                            >
                                                <i class="fas fa-print"></i>
                                            </button>
                                            <button 
                                                @click="confirmDelete(purchase)"
                                                class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition"
                                                title="Delete"
                                                :disabled="purchase.payment_status === 'paid'"
                                            >
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="totalPages > 1" class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                        <div class="flex items-center justify-between flex-wrap gap-3">
                            <div class="text-sm text-gray-600">
                                Showing {{ (currentPage - 1) * 10 + 1 }} to {{ Math.min(currentPage * 10, pagination.total) }} of {{ pagination.total }} results
                            </div>
                            <div class="flex gap-2">
                                <button 
                                    @click="prevPage" 
                                    :disabled="currentPage === 1"
                                    class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
                                >
                                    <i class="fas fa-chevron-left mr-1 text-xs"></i>
                                    Previous
                                </button>
                                <div class="flex gap-1">
                                    <button 
                                        v-for="page in Math.min(5, totalPages)"
                                        :key="page"
                                        @click="currentPage = page; fetchPurchases()"
                                        :class="[
                                            'px-3 py-1.5 rounded-lg text-sm font-medium transition',
                                            currentPage === page 
                                                ? 'bg-indigo-600 text-white' 
                                                : 'border border-gray-300 text-gray-700 hover:bg-gray-50'
                                        ]"
                                    >
                                        {{ page }}
                                    </button>
                                </div>
                                <button 
                                    @click="nextPage" 
                                    :disabled="currentPage === totalPages"
                                    class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
                                >
                                    Next
                                    <i class="fas fa-chevron-right ml-1 text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase Details Modal -->
        <div v-if="isDetailsModalVisible" class="fixed inset-0 z-50 overflow-y-auto" @click.self="isDetailsModalVisible = false">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-900 opacity-50"></div>
                </div>
                
                <div class="inline-block w-full max-w-4xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-file-invoice text-indigo-600"></i>
                            Purchase Details
                        </h3>
                        <button @click="isDetailsModalVisible = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <div v-if="selectedPurchase" class="space-y-6">
                        <!-- Purchase Info -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg">
                            <div>
                                <p class="text-xs text-gray-500">Invoice Number</p>
                                <p class="font-semibold text-gray-800">{{ selectedPurchase.invoice_no }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Purchase Date</p>
                                <p class="font-semibold text-gray-800">{{ formatDate(selectedPurchase.purchase_date) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Supplier</p>
                                <p class="font-semibold text-gray-800">{{ selectedPurchase.supplier?.name }}</p>
                                <p class="text-xs text-gray-500">{{ selectedPurchase.supplier?.phone }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Payment Status</p>
                                <span :class="['px-2 py-1 inline-flex text-xs leading-4 font-semibold rounded-full', getPaymentStatusClass(selectedPurchase.payment_status)]">
                                    {{ getPaymentStatusText(selectedPurchase.payment_status) }}
                                </span>
                            </div>
                        </div>
                        
                        <!-- Products Table -->
                        <div>
                            <h4 class="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                                <i class="fas fa-boxes text-orange-600"></i>
                                Products
                            </h4>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-sm font-semibold">Product</th>
                                            <th class="px-4 py-2 text-center text-sm font-semibold">Quantity</th>
                                            <th class="px-4 py-2 text-right text-sm font-semibold">Unit Price</th>
                                            <th class="px-4 py-2 text-right text-sm font-semibold">Total Price</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="detail in selectedPurchase.purchase_details" :key="detail.id" class="border-b">
                                            <td class="px-4 py-2 text-sm">{{ detail.product?.name }}</td>
                                            <td class="px-4 py-2 text-center text-sm">{{ detail.quantity }}</td>
                                            <td class="px-4 py-2 text-right text-sm">{{ formatCurrency(detail.unit_price) }}</td>
                                            <td class="px-4 py-2 text-right text-sm font-semibold">{{ formatCurrency(detail.total_price) }}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-gray-50">
                                        <tr>
                                            <td colspan="3" class="px-4 py-2 text-right font-semibold">Subtotal:</td>
                                            <td class="px-4 py-2 text-right font-semibold">{{ formatCurrency(selectedPurchase.subtotal) }}</td>
                                        </tr>
                                        <tr v-if="selectedPurchase.discount > 0">
                                            <td colspan="3" class="px-4 py-2 text-right text-sm">Discount:</td>
                                            <td class="px-4 py-2 text-right text-red-600">{{ formatCurrency(selectedPurchase.discount) }}</td>
                                        </tr>
                                        <tr v-if="selectedPurchase.tax > 0">
                                            <td colspan="3" class="px-4 py-2 text-right text-sm">Tax/VAT:</td>
                                            <td class="px-4 py-2 text-right text-yellow-600">{{ formatCurrency(selectedPurchase.tax) }}</td>
                                        </tr>
                                        <tr v-if="selectedPurchase.shipping_cost > 0">
                                            <td colspan="3" class="px-4 py-2 text-right text-sm">Shipping:</td>
                                            <td class="px-4 py-2 text-right text-blue-600">{{ formatCurrency(selectedPurchase.shipping_cost) }}</td>
                                        </tr>
                                        <tr class="border-t-2">
                                            <td colspan="3" class="px-4 py-2 text-right font-bold">Total Amount:</td>
                                            <td class="px-4 py-2 text-right font-bold text-green-600">{{ formatCurrency(selectedPurchase.total_amount) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="px-4 py-2 text-right text-sm">Paid Amount:</td>
                                            <td class="px-4 py-2 text-right text-green-600">{{ formatCurrency(selectedPurchase.paid_amount) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3" class="px-4 py-2 text-right text-sm font-semibold">Due Amount:</td>
                                            <td class="px-4 py-2 text-right font-semibold text-red-600">{{ formatCurrency(selectedPurchase.due_amount) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Notes -->
                        <div v-if="selectedPurchase.notes" class="bg-yellow-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-500 mb-1">Notes:</p>
                            <p class="text-sm text-gray-700">{{ selectedPurchase.notes }}</p>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex justify-end gap-3 pt-4 border-t">
                            <button
                                @click="isDetailsModalVisible = false"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
                            >
                                Close
                            </button>
                            <button
                                @click="printPurchase(selectedPurchase)"
                                class="px-4 py-2 bg-gray-600 text-white rounded-lg text-sm font-medium hover:bg-gray-700 transition"
                            >
                                <i class="fas fa-print mr-2"></i>
                                Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="isDeleteModalVisible" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeDeleteModal">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-900 opacity-50"></div>
                </div>
                
                <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                    <div class="text-center">
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                            <i class="fas fa-exclamation-triangle text-3xl text-red-600"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Delete Purchase</h3>
                        <p class="text-gray-600 mb-4">
                            Are you sure you want to delete this purchase?
                        </p>
                        <p class="text-sm text-red-600 mb-6">
                            <i class="fas fa-warning mr-1"></i>
                            <strong>Warning:</strong> This action will:
                            <ul class="list-disc list-inside mt-2 text-left">
                                <li>Remove this purchase record</li>
                                <li>Decrease product stock quantities</li>
                                <li>Update supplier due amount</li>
                            </ul>
                        </p>
                        
                        <div class="flex justify-center space-x-3">
                            <button
                                @click="closeDeleteModal"
                                class="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-all duration-200"
                            >
                                Cancel
                            </button>
                            <button
                                @click="deletePurchase"
                                :disabled="isProcessing"
                                class="px-6 py-2.5 border border-transparent rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                            >
                                <i v-if="isProcessing" class="fas fa-spinner fa-spin mr-2"></i>
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Smooth transitions */
.transition {
    transition: all 0.2s ease;
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>