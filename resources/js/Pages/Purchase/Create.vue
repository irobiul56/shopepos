<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'

// Props from controller
const props = defineProps({
    suppliers: Array,
    products: Array,
    errors: Object
})

// Custom notification
const notification = ref({
    show: false,
    message: '',
    type: 'success'
})

const showNotification = (message, type = 'success') => {
    notification.value = {
        show: true,
        message: message,
        type: type
    }
    
    setTimeout(() => {
        notification.value.show = false
    }, 3000)
}

// Purchase form data
const purchaseForm = useForm({
    supplier_id: '',
    invoice_no: '',
    purchase_date: new Date().toISOString().split('T')[0],
    subtotal: 0,
    discount: 0,
    tax: 0,
    shipping_cost: 0,
    total_amount: 0,
    paid_amount: 0,
    due_amount: 0,
    payment_status: 'unpaid',
    notes: '',
    purchase_details: []  // This will be populated with product rows
})

// Product rows
const productRows = ref([
    {
        id: 1,
        product_id: '',
        name: '',
        code: '',
        quantity: 1,
        unit_price: 0,
        total_price: 0
    }
])

let nextProductId = 2

// Selected supplier info
const selectedSupplier = ref(null)

// Computed totals
const subtotal = computed(() => {
    return productRows.value.reduce((sum, row) => sum + (row.total_price || 0), 0)
})

const totalAmount = computed(() => {
    return subtotal.value + purchaseForm.tax + purchaseForm.shipping_cost - purchaseForm.discount
})

const dueAmount = computed(() => {
    return totalAmount.value - purchaseForm.paid_amount
})

// Watch for changes and update form
watch(subtotal, (newValue) => {
    purchaseForm.subtotal = newValue
    updateTotals()
})

watch(totalAmount, (newValue) => {
    purchaseForm.total_amount = newValue
    updateDueAmount()
})

watch(() => purchaseForm.paid_amount, () => {
    updateDueAmount()
})

watch(() => purchaseForm.payment_status, (newStatus) => {
    if (newStatus === 'paid') {
        purchaseForm.paid_amount = totalAmount.value
    } else if (newStatus === 'unpaid') {
        purchaseForm.paid_amount = 0
    }
})

// Update totals
const updateTotals = () => {
    purchaseForm.subtotal = subtotal.value
    purchaseForm.total_amount = totalAmount.value
    updateDueAmount()
}

const updateDueAmount = () => {
    purchaseForm.due_amount = totalAmount.value - purchaseForm.paid_amount
}

// Add product row
const addProductRow = () => {
    productRows.value.push({
        id: nextProductId++,
        product_id: '',
        name: '',
        code: '',
        quantity: 1,
        unit_price: 0,
        total_price: 0
    })
}

// Remove product row
const removeProductRow = (index) => {
    if (productRows.value.length > 1) {
        productRows.value.splice(index, 1)
        updateTotals()
    } else {
        showNotification('At least one product is required!', 'error')
    }
}

// Update product details
const updateProductDetails = (index, productId) => {
    const selectedProduct = props.products.find(p => p.id == productId)
    if (selectedProduct) {
        productRows.value[index].product_id = selectedProduct.id
        productRows.value[index].name = selectedProduct.name
        productRows.value[index].code = selectedProduct.sku || selectedProduct.code
        productRows.value[index].unit_price = selectedProduct.purchase_price || selectedProduct.price || 0
        calculateRowTotal(index)
    }
}

// Calculate row total
const calculateRowTotal = (index) => {
    const row = productRows.value[index]
    row.total_price = (row.quantity || 0) * (row.unit_price || 0)
    updateTotals()
}

// Update quantity
const updateQuantity = (index, quantity) => {
    productRows.value[index].quantity = quantity
    calculateRowTotal(index)
}

// Update unit price
const updateUnitPrice = (index, price) => {
    productRows.value[index].unit_price = price
    calculateRowTotal(index)
}

// Supplier change handler
const onSupplierChange = (supplierId) => {
    selectedSupplier.value = props.suppliers.find(s => s.id == supplierId)
}

// Generate invoice number
const generateInvoiceNo = () => {
    const date = new Date()
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0')
    purchaseForm.invoice_no = `INV-${year}${month}-${random}`
}

// Update purchase_details before submit
const updatePurchaseDetails = () => {
    // Clear existing purchase_details
    purchaseForm.purchase_details = []
    
    // Add all valid product rows to purchase_details
    productRows.value.forEach(row => {
        if (row.product_id && row.quantity > 0 && row.unit_price >= 0) {
            purchaseForm.purchase_details.push({
                product_id: row.product_id,
                quantity: row.quantity,
                unit_price: row.unit_price,
                total_price: row.total_price
            })
        }
    })
    
    console.log('Purchase Details being sent:', purchaseForm.purchase_details) // Debug log
}

// Submit purchase
const submitPurchase = () => {
    // Validation
    if (!purchaseForm.supplier_id) {
        showNotification('Please select a supplier', 'error')
        return
    }
    if (!purchaseForm.invoice_no) {
        showNotification('Please enter invoice number', 'error')
        return
    }
    
    // Check if any product is selected
    const hasProducts = productRows.value.some(row => row.product_id)
    if (!hasProducts) {
        showNotification('Please select at least one product', 'error')
        return
    }
    
    // Check if all products have valid data
    let hasInvalidProducts = false
    productRows.value.forEach(row => {
        if (row.product_id && (row.quantity <= 0 || row.unit_price < 0)) {
            hasInvalidProducts = true
        }
    })
    
    if (hasInvalidProducts) {
        showNotification('Please check product quantities and prices', 'error')
        return
    }
    
    // Update purchase_details before submit
    updatePurchaseDetails()
    
    // Check if purchase_details is not empty
    if (purchaseForm.purchase_details.length === 0) {
        showNotification('No valid products to submit', 'error')
        return
    }
    
    // Log the complete form data
    console.log('Form Data being submitted:', {
        supplier_id: purchaseForm.supplier_id,
        invoice_no: purchaseForm.invoice_no,
        purchase_date: purchaseForm.purchase_date,
        subtotal: purchaseForm.subtotal,
        discount: purchaseForm.discount,
        tax: purchaseForm.tax,
        shipping_cost: purchaseForm.shipping_cost,
        total_amount: purchaseForm.total_amount,
        paid_amount: purchaseForm.paid_amount,
        due_amount: purchaseForm.due_amount,
        payment_status: purchaseForm.payment_status,
        notes: purchaseForm.notes,
        purchase_details: purchaseForm.purchase_details
    })
    
    // Submit using Inertia
    purchaseForm.post(route('purchases.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showNotification('Purchase created successfully!', 'success')
            
            // Reset form after 2 seconds
            setTimeout(() => {
                resetForm()
            }, 2000)
        },
        onError: (errors) => {
            console.error('Form errors:', errors)
            const errorMessage = Object.values(errors)[0] || 'Failed to create purchase'
            showNotification(errorMessage, 'error')
        }
    })
}

// Reset form
const resetForm = () => {
    purchaseForm.reset()
    purchaseForm.purchase_date = new Date().toISOString().split('T')[0]
    purchaseForm.discount = 0
    purchaseForm.tax = 0
    purchaseForm.shipping_cost = 0
    purchaseForm.paid_amount = 0
    purchaseForm.payment_status = 'unpaid'
    purchaseForm.notes = ''
    purchaseForm.purchase_details = []
    
    productRows.value = [{
        id: 1,
        product_id: '',
        name: '',
        code: '',
        quantity: 1,
        unit_price: 0,
        total_price: 0
    }]
    nextProductId = 2
    selectedSupplier.value = null
    
    generateInvoiceNo()
    showNotification('Form reset successfully', 'success')
}

// Print invoice
const printInvoice = () => {
    window.print()
}

// Go back
const goBack = () => {
    if (confirm('Are you sure you want to leave? Unsaved data will be lost.')) {
        router.get(route('purchases.index'))
    }
}

// Format currency
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: 2
    }).format(amount || 0)
}

// Get product options for select
const productOptions = computed(() => {
    return props.products || []
})

// Initialize on mount
onMounted(() => {
    generateInvoiceNo()
})
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Create Purchase" />
        
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
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4 no-print">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-shopping-cart text-indigo-600"></i>
                            Product Purchase
                        </h1>
                        <p class="text-sm text-gray-500 mt-1">Purchase products from suppliers</p>
                    </div>
                    <div class="flex gap-3">
                        <button 
                            @click="goBack" 
                            class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition flex items-center gap-2 text-sm font-medium"
                        >
                            <i class="fas fa-arrow-left"></i>
                            Back
                        </button>
                        <button 
                            @click="printInvoice" 
                            class="px-5 py-2.5 bg-gray-600 text-white rounded-xl hover:bg-gray-700 transition flex items-center gap-2 text-sm font-medium"
                        >
                            <i class="fas fa-print"></i>
                            Print
                        </button>
                    </div>
                </div>

                <!-- Main Form -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden print-area">
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 no-print">
                        <h2 class="text-xl font-semibold text-white flex items-center gap-2">
                            <i class="fas fa-plus-circle"></i>
                            Purchase Information
                        </h2>
                    </div>
                    
                    <form @submit.prevent="submitPurchase" class="p-6 space-y-6">
                        <!-- Purchase Information Section -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Left Column - Supplier Info -->
                            <div class="lg:col-span-2 space-y-4">
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h3 class="text-md font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                        <i class="fas fa-building text-blue-600"></i>
                                        Supplier Information
                                    </h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Select Supplier <span class="text-red-500">*</span>
                                            </label>
                                            <select 
                                                v-model="purchaseForm.supplier_id"
                                                @change="onSupplierChange(purchaseForm.supplier_id)"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                            >
                                                <option value="">Select Supplier</option>
                                                <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                                                    {{ supplier.name }} {{ supplier.company_name ? '- ' + supplier.company_name : '' }}
                                                </option>
                                            </select>
                                            <p v-if="errors?.supplier_id" class="mt-1 text-xs text-red-600">{{ errors.supplier_id }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Supplier Phone
                                            </label>
                                            <input 
                                                type="text" 
                                                :value="selectedSupplier?.phone || ''" 
                                                readonly 
                                                class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg"
                                                placeholder="Phone number"
                                            >
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Supplier Address
                                            </label>
                                            <input 
                                                type="text" 
                                                :value="selectedSupplier?.address || ''" 
                                                readonly 
                                                class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg"
                                                placeholder="Address"
                                            >
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <h3 class="text-md font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                        <i class="fas fa-file-invoice text-green-600"></i>
                                        Purchase Information
                                    </h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Invoice Number <span class="text-red-500">*</span>
                                            </label>
                                            <div class="flex gap-2">
                                                <input 
                                                    v-model="purchaseForm.invoice_no"
                                                    type="text" 
                                                    required 
                                                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                                    placeholder="INV-2024-001"
                                                >
                                                <button 
                                                    type="button"
                                                    @click="generateInvoiceNo"
                                                    class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition"
                                                    title="Generate Invoice Number"
                                                >
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                            </div>
                                            <p v-if="errors?.invoice_no" class="mt-1 text-xs text-red-600">{{ errors.invoice_no }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Purchase Date <span class="text-red-500">*</span>
                                            </label>
                                            <input 
                                                v-model="purchaseForm.purchase_date"
                                                type="date" 
                                                required 
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right Column - Summary -->
                            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 p-4 rounded-lg">
                                <h3 class="text-md font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                    <i class="fas fa-calculator text-purple-600"></i>
                                    Price Summary
                                </h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">Subtotal:</span>
                                        <span class="font-semibold text-gray-800">{{ formatCurrency(purchaseForm.subtotal) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">Discount:</span>
                                        <span class="font-semibold text-red-600">{{ formatCurrency(purchaseForm.discount) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">Tax/VAT:</span>
                                        <span class="font-semibold text-yellow-600">{{ formatCurrency(purchaseForm.tax) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">Shipping Cost:</span>
                                        <span class="font-semibold text-blue-600">{{ formatCurrency(purchaseForm.shipping_cost) }}</span>
                                    </div>
                                    <div class="border-t-2 border-dashed border-gray-300 pt-2 mt-2">
                                        <div class="flex justify-between items-center">
                                            <span class="text-lg font-bold text-gray-800">Total Amount:</span>
                                            <span class="text-xl font-bold text-green-600">{{ formatCurrency(purchaseForm.total_amount) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Products Section -->
                        <div>
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                    <i class="fas fa-boxes text-orange-600"></i>
                                    Products List
                                </h3>
                                <button 
                                    type="button"
                                    @click="addProductRow" 
                                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition flex items-center gap-2 text-sm"
                                >
                                    <i class="fas fa-plus"></i>
                                    Add Product
                                </button>
                            </div>
                            
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Product</th>
                                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Code</th>
                                            <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Quantity</th>
                                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Unit Price</th>
                                            <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Total Price</th>
                                            <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(row, index) in productRows" :key="row.id" class="border-b border-gray-200 hover:bg-gray-50 transition">
                                            <td class="px-4 py-3">
                                                <select 
                                                    v-model="row.product_id"
                                                    @change="updateProductDetails(index, row.product_id)"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500"
                                                >
                                                    <option value="">Select Product</option>
                                                    <option v-for="product in productOptions" :key="product.id" :value="product.id">
                                                        {{ product.name }} ({{ formatCurrency(product.purchase_price || product.price) }})
                                                    </option>
                                                </select>
                                            </td>
                                            <td class="px-4 py-3">
                                                <input 
                                                    type="text" 
                                                    v-model="row.code" 
                                                    readonly 
                                                    class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50"
                                                >
                                            </td>
                                            <td class="px-4 py-3">
                                                <input 
                                                    type="number" 
                                                    v-model.number="row.quantity"
                                                    @input="calculateRowTotal(index)"
                                                    class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-sm text-center focus:ring-2 focus:ring-indigo-500"
                                                    min="1"
                                                >
                                            </td>
                                            <td class="px-4 py-3">
                                                <input 
                                                    type="number" 
                                                    v-model.number="row.unit_price"
                                                    @input="calculateRowTotal(index)"
                                                    class="w-28 px-3 py-2 border border-gray-300 rounded-lg text-sm text-right focus:ring-2 focus:ring-indigo-500"
                                                    step="0.01"
                                                >
                                            </td>
                                            <td class="px-4 py-3">
                                                <input 
                                                    type="text" 
                                                    :value="formatCurrency(row.total_price)" 
                                                    readonly 
                                                    class="w-28 px-3 py-2 border border-gray-300 rounded-lg text-sm text-right bg-gray-50 font-semibold"
                                                >
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <button 
                                                    type="button"
                                                    @click="removeProductRow(index)"
                                                    class="text-red-600 hover:text-red-800 transition"
                                                >
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Payment & Additional Section -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-md font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                    <i class="fas fa-money-bill-wave text-green-600"></i>
                                    Payment Information
                                </h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Payment Status <span class="text-red-500">*</span>
                                        </label>
                                        <select 
                                            v-model="purchaseForm.payment_status"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                        >
                                            <option value="paid">Paid</option>
                                            <option value="partial">Partial</option>
                                            <option value="unpaid">Unpaid</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Paid Amount
                                        </label>
                                        <input 
                                            type="number" 
                                            v-model.number="purchaseForm.paid_amount"
                                            step="0.01"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                            placeholder="0.00"
                                        >
                                        <p v-if="purchaseForm.due_amount > 0" class="mt-1 text-xs text-red-600">
                                            Due: {{ formatCurrency(purchaseForm.due_amount) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="text-md font-semibold text-gray-800 mb-4 flex items-center gap-2">
                                    <i class="fas fa-sliders-h text-purple-600"></i>
                                    Additional Options
                                </h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Discount (TK)
                                        </label>
                                        <input 
                                            type="number" 
                                            v-model.number="purchaseForm.discount"
                                            step="0.01"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                            placeholder="0"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Tax/VAT (TK)
                                        </label>
                                        <input 
                                            type="number" 
                                            v-model.number="purchaseForm.tax"
                                            step="0.01"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                            placeholder="0"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Shipping Cost
                                        </label>
                                        <input 
                                            type="number" 
                                            v-model.number="purchaseForm.shipping_cost"
                                            step="0.01"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                            placeholder="0"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Notes
                                        </label>
                                        <textarea 
                                            v-model="purchaseForm.notes"
                                            rows="2"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                            placeholder="Additional notes..."
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex justify-end gap-4 pt-4 border-t border-gray-200 no-print">
                            <button 
                                type="button"
                                @click="resetForm"
                                class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
                            >
                                <i class="fas fa-undo-alt mr-2"></i>
                                Reset
                            </button>
                            <button 
                                type="submit"
                                :disabled="purchaseForm.processing"
                                class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition shadow-md disabled:opacity-50"
                            >
                                <i v-if="purchaseForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
                                <i v-else class="fas fa-save mr-2"></i>
                                Save Purchase
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@media print {
    .no-print {
        display: none !important;
    }
    .print-area {
        box-shadow: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    body {
        background: white !important;
        padding: 0 !important;
    }
}

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

input:focus, select:focus, textarea:focus {
    outline: none;
}
</style>