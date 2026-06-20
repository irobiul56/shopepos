<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';

const { props } = usePage();

// Props from controller
const customers = ref(props.customers || []);
const dueSales = ref(props.due_sales || []);
const totalDue = ref(props.total_due || 0);

// Filter states
const filters = ref({
    customer_id: props.customer_id || '',
    start_date: props.start_date || '',
    end_date: props.end_date || '',
    search: '',
});

const currentPage = ref(1);
const itemsPerPage = ref(15);
const isFiltering = ref(false);

// Payment modal
const showPaymentModal = ref(false);
const showHistoryModal = ref(false);
const selectedSale = ref(null);
const paymentHistory = ref([]);
const paymentForm = useForm({
    sale_id: null,
    amount: 0,
    payment_method: 'cash',
    payment_date: '',
    transaction_id: '',
    notes: '',
});

// Computed filtered sales
const filteredSales = computed(() => {
    let filtered = [...dueSales.value];
    
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

// Total due amount
const totalDueAmount = computed(() => {
    return filteredSales.value.reduce((sum, sale) => sum + Number(sale.due_amount || 0), 0);
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
    
    if (filters.value.customer_id) {
        params.append('customer_id', filters.value.customer_id);
    }
    if (filters.value.start_date) {
        params.append('start_date', filters.value.start_date);
    }
    if (filters.value.end_date) {
        params.append('end_date', filters.value.end_date);
    }
    
    const url = route('due-collection.index') + '?' + params.toString();
    
    router.visit(url, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
            isFiltering.value = false;
            customers.value = page.props.customers || [];
            dueSales.value = page.props.due_sales || [];
            totalDue.value = page.props.total_due || 0;
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
        customer_id: '',
        start_date: '',
        end_date: '',
        search: '',
    };
    currentPage.value = 1;
    applyFilters();
};

// Open payment modal
const openPaymentModal = (sale) => {
    selectedSale.value = sale;
    paymentForm.sale_id = sale.id;
    paymentForm.amount = sale.due_amount;
    paymentForm.payment_method = 'cash';
    paymentForm.payment_date = new Date().toISOString().split('T')[0];
    paymentForm.transaction_id = '';
    paymentForm.notes = '';
    paymentForm.clearErrors();
    showPaymentModal.value = true;
};

// Close payment modal
const closePaymentModal = () => {
    showPaymentModal.value = false;
    selectedSale.value = null;
    paymentForm.reset();
};

// View payment history
const viewPaymentHistory = (sale) => {
    selectedSale.value = sale;
    paymentHistory.value = sale.payments || [];
    showHistoryModal.value = true;
};

// Close history modal
const closeHistoryModal = () => {
    showHistoryModal.value = false;
    selectedSale.value = null;
    paymentHistory.value = [];
};

// Submit payment
const submitPayment = () => {
    paymentForm.clearErrors();
    
    if (paymentForm.amount <= 0) {
        alert('Please enter a valid amount');
        return;
    }
    
    if (paymentForm.amount > selectedSale.value.due_amount) {
        alert(`Amount cannot exceed due amount: ${formatCurrency(selectedSale.value.due_amount)}`);
        return;
    }
    
    if (!paymentForm.payment_date) {
        alert('Please select payment date');
        return;
    }
    
    paymentForm.post(route('due-collection.pay'), {
        preserveScroll: true,
        onSuccess: (page) => {
            closePaymentModal();
            // Update the due sales list
            dueSales.value = page.props.due_sales || [];
            totalDue.value = page.props.total_due || 0;
            showMessage('Payment collected successfully!', 'success');
        },
        onError: (errors) => {
            console.error('Payment error:', errors);
        },
    });
};

// Message state
const message = ref({
    show: false,
    text: '',
    type: 'success'
});

const showMessage = (text, type = 'success') => {
    message.value = {
        show: true,
        text: text,
        type: type
    };
    setTimeout(() => {
        message.value.show = false;
    }, 3000);
};

// Watch for flash messages
watch(() => props.flash, (newVal) => {
    if (newVal?.success) {
        showMessage(newVal.success, 'success');
    }
    if (newVal?.error) {
        showMessage(newVal.error, 'error');
    }
}, { deep: true });

// Set default dates
onMounted(() => {
    if (!filters.value.start_date && !filters.value.end_date) {
        const now = new Date();
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        
        filters.value.start_date = thirtyDaysAgo.toISOString().split('T')[0];
        filters.value.end_date = now.toISOString().split('T')[0];
    }
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

// Get payment method icon and label
const getPaymentMethodInfo = (method) => {
    const methods = {
        cash: { icon: 'fa-money-bill-wave', label: 'Cash' },
        card: { icon: 'fa-credit-card', label: 'Card' },
        mobile_banking: { icon: 'fa-mobile-alt', label: 'Mobile Banking' },
        bank_transfer: { icon: 'fa-university', label: 'Bank Transfer' },
    };
    return methods[method] || { icon: 'fa-money-bill', label: method };
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Due Collection" />
        
        <!-- Flash Message -->
        <div v-if="message.show" 
             :class="[
               'fixed top-4 right-4 z-50 px-4 py-2 rounded-lg shadow-lg',
               message.type === 'success' ? 'bg-emerald-500 text-white' : 
               message.type === 'error' ? 'bg-red-500 text-white' : 
               'bg-amber-500 text-white'
             ]">
            <i :class="[
                'mr-2',
                message.type === 'success' ? 'fas fa-check-circle' : 
                message.type === 'error' ? 'fas fa-exclamation-circle' : 
                'fas fa-exclamation-triangle'
            ]"></i>
            {{ message.text }}
        </div>
        
        <div class="min-h-screen bg-gray-50 p-4">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-hand-holding-usd mr-2 text-emerald-600"></i> Due Collection
                        </h1>
                        <p class="text-sm text-gray-500">Collect pending dues from customers</p>
                    </div>
                    <div class="bg-emerald-100 text-emerald-700 px-4 py-2 rounded-lg">
                        <span class="text-sm font-medium">Total Due: {{ formatCurrency(totalDueAmount) }}</span>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-indigo-500">
                        <p class="text-xs text-gray-500 uppercase font-medium">Total Due</p>
                        <p class="text-2xl font-bold text-gray-800">{{ formatCurrency(totalDueAmount) }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-amber-500">
                        <p class="text-xs text-gray-500 uppercase font-medium">Due Invoices</p>
                        <p class="text-2xl font-bold text-gray-800">{{ filteredSales.length }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-emerald-500">
                        <p class="text-xs text-gray-500 uppercase font-medium">Customers</p>
                        <p class="text-2xl font-bold text-gray-800">{{ customers.length }}</p>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-purple-500">
                        <p class="text-xs text-gray-500 uppercase font-medium">Avg Due</p>
                        <p class="text-2xl font-bold text-gray-800">
                            {{ filteredSales.length > 0 ? formatCurrency(totalDueAmount / filteredSales.length) : 'Tk 0.00' }}
                        </p>
                    </div>
                </div>

                <!-- Filter Bar -->
                <div class="bg-white rounded-xl shadow-sm p-3 mb-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="flex items-center gap-1">
                            <i class="fas fa-user text-gray-400 text-xs"></i>
                            <select
                                v-model="filters.customer_id"
                                class="border border-gray-300 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 min-w-[150px]"
                            >
                                <option value="">All Customers</option>
                                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                    {{ customer.name }} ({{ customer.phone }})
                                </option>
                            </select>
                        </div>

                        <div class="w-px h-6 bg-gray-300"></div>

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

                        <div class="flex-1 min-w-[150px] relative">
                            <i class="fas fa-search absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input
                                v-model="filters.search"
                                type="text"
                                placeholder="Search invoice or customer..."
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
                    </div>
                </div>

                <!-- Due Sales Table -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase tracking-wider">Invoice</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-3 py-2 text-left text-[10px] font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-3 py-2 text-right text-[10px] font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    <th class="px-3 py-2 text-right text-[10px] font-medium text-gray-500 uppercase tracking-wider">Paid</th>
                                    <th class="px-3 py-2 text-right text-[10px] font-medium text-gray-500 uppercase tracking-wider">Due</th>
                                    <th class="px-3 py-2 text-center text-[10px] font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-3 py-2 text-center text-[10px] font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="sale in paginatedSales" :key="sale.id" class="hover:bg-gray-50 transition">
                                    <td class="px-3 py-2 text-xs font-medium text-gray-900">{{ sale.invoice_no }}</td>
                                    <td class="px-3 py-2 text-xs text-gray-600">{{ formatDate(sale.sale_date) }}</td>
                                    <td class="px-3 py-2 text-xs text-gray-600">
                                        <div>{{ sale.customer?.name || 'Walk-in' }}</div>
                                        <div class="text-[10px] text-gray-400">{{ sale.customer?.phone || 'N/A' }}</div>
                                    </td>
                                    <td class="px-3 py-2 text-xs text-right font-medium">{{ formatCurrency(sale.total_amount) }}</td>
                                    <td class="px-3 py-2 text-xs text-right text-emerald-600">{{ formatCurrency(sale.paid_amount) }}</td>
                                    <td class="px-3 py-2 text-xs text-right font-bold text-amber-600">{{ formatCurrency(sale.due_amount) }}</td>
                                    <td class="px-3 py-2 text-center">
                                        <span class="px-2 py-0.5 text-[10px] rounded-full font-medium" :class="getStatusColor(sale.payment_status)">
                                            {{ sale.payment_status }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button
                                                @click="openPaymentModal(sale)"
                                                class="bg-emerald-600 text-white px-2 py-1 rounded-lg hover:bg-emerald-700 transition text-xs flex items-center gap-1"
                                                title="Collect Payment"
                                            >
                                                <i class="fas fa-hand-holding-usd"></i>
                                            </button>
                                            <button
                                                @click="viewPaymentHistory(sale)"
                                                class="bg-indigo-600 text-white px-2 py-1 rounded-lg hover:bg-indigo-700 transition text-xs flex items-center gap-1"
                                                title="View History"
                                            >
                                                <i class="fas fa-history"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="paginatedSales.length === 0">
                                    <td colspan="8" class="px-3 py-8 text-center text-gray-400">
                                        <i class="fas fa-check-circle text-2xl block mb-2 text-emerald-500"></i>
                                        <p class="text-sm">No due sales found</p>
                                        <p class="text-xs">All invoices are paid!</p>
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
                            of {{ filteredSales.length }} due invoices
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
            </div>
        </div>

        <!-- Payment Modal -->
        <div v-if="showPaymentModal" class="modal-overlay" @click.self="closePaymentModal">
            <div class="modal-card">
                <div class="modal-header">
                    <h3 class="font-bold text-base flex items-center gap-2">
                        <i class="fas fa-hand-holding-usd text-emerald-600"></i> Collect Payment
                    </h3>
                    <button @click="closePaymentModal" class="text-slate-400 hover:text-rose-500 transition">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="space-y-4 text-sm" v-if="selectedSale">
                    <!-- Sale Info -->
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <p class="text-xs text-gray-500">Invoice</p>
                                <p class="font-medium">{{ selectedSale.invoice_no }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Customer</p>
                                <p class="font-medium">{{ selectedSale.customer?.name || 'Walk-in' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Total Amount</p>
                                <p class="font-medium">{{ formatCurrency(selectedSale.total_amount) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Due Amount</p>
                                <p class="font-bold text-amber-600">{{ formatCurrency(selectedSale.due_amount) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Payment Amount <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="paymentForm.amount"
                            type="number"
                            step="0.01"
                            min="0"
                            :max="selectedSale.due_amount"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            :class="{ 'border-red-500': paymentForm.errors.amount }"
                        />
                        <p class="text-[10px] text-gray-400 mt-1">Max: {{ formatCurrency(selectedSale.due_amount) }}</p>
                        <p v-if="paymentForm.errors.amount" class="text-xs text-red-500 mt-1">{{ paymentForm.errors.amount }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Payment Method <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                v-for="method in ['cash', 'card', 'mobile_banking', 'bank_transfer']"
                                :key="method"
                                type="button"
                                @click="paymentForm.payment_method = method"
                                :class="[
                                    'px-3 py-2 rounded-lg border text-xs transition',
                                    paymentForm.payment_method === method
                                        ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                        : 'border-gray-300 hover:bg-gray-50'
                                ]"
                            >
                                <i :class="['fas', getPaymentMethodInfo(method).icon, 'mr-1']"></i>
                                {{ getPaymentMethodInfo(method).label }}
                            </button>
                        </div>
                        <p v-if="paymentForm.errors.payment_method" class="text-xs text-red-500 mt-1">{{ paymentForm.errors.payment_method }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Payment Date <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="paymentForm.payment_date"
                            type="date"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            :class="{ 'border-red-500': paymentForm.errors.payment_date }"
                        />
                        <p v-if="paymentForm.errors.payment_date" class="text-xs text-red-500 mt-1">{{ paymentForm.errors.payment_date }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Transaction ID</label>
                        <input
                            v-model="paymentForm.transaction_id"
                            type="text"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="Enter transaction ID (optional)"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Notes</label>
                        <textarea
                            v-model="paymentForm.notes"
                            rows="2"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="Add notes (optional)"
                        ></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button @click="closePaymentModal" class="btn-secondary">Cancel</button>
                    <button 
                        @click="submitPayment" 
                        :disabled="paymentForm.processing || !paymentForm.amount || paymentForm.amount <= 0"
                        class="btn-primary"
                    >
                        <i v-if="paymentForm.processing" class="fas fa-spinner fa-spin mr-1"></i>
                        <i v-else class="fas fa-check mr-1"></i>
                        {{ paymentForm.processing ? 'Processing...' : 'Collect Payment' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Payment History Modal -->
        <div v-if="showHistoryModal" class="modal-overlay" @click.self="closeHistoryModal">
            <div class="modal-card" style="max-width: 550px;">
                <div class="modal-header">
                    <h3 class="font-bold text-base flex items-center gap-2">
                        <i class="fas fa-history text-indigo-600"></i> Payment History
                    </h3>
                    <button @click="closeHistoryModal" class="text-slate-400 hover:text-rose-500 transition">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div v-if="selectedSale">
                    <!-- Sale Info -->
                    <div class="bg-gray-50 rounded-lg p-3 mb-4">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <p class="text-xs text-gray-500">Invoice</p>
                                <p class="font-medium">{{ selectedSale.invoice_no }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Customer</p>
                                <p class="font-medium">{{ selectedSale.customer?.name || 'Walk-in' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Total</p>
                                <p class="font-medium">{{ formatCurrency(selectedSale.total_amount) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Paid</p>
                                <p class="font-medium text-emerald-600">{{ formatCurrency(selectedSale.paid_amount) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Due</p>
                                <p class="font-bold text-amber-600">{{ formatCurrency(selectedSale.due_amount) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Status</p>
                                <span class="px-2 py-0.5 text-[10px] rounded-full font-medium" :class="getStatusColor(selectedSale.payment_status)">
                                    {{ selectedSale.payment_status }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment List -->
                    <div v-if="paymentHistory.length > 0" class="space-y-2 max-h-60 overflow-y-auto">
                        <div v-for="payment in paymentHistory" :key="payment.id" 
                             class="border border-gray-200 rounded-lg p-3 hover:bg-gray-50 transition">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-medium text-gray-800">
                                            <i :class="['fas', getPaymentMethodInfo(payment.payment_method).icon, 'mr-1 text-gray-500']"></i>
                                            {{ getPaymentMethodInfo(payment.payment_method).label }}
                                        </span>
                                        <span class="text-[10px] text-gray-400">|</span>
                                        <span class="text-[10px] text-gray-400">{{ formatDateOnly(payment.payment_date) }}</span>
                                    </div>
                                    <div class="text-sm font-bold text-emerald-600 mt-1">
                                        {{ formatCurrency(payment.amount) }}
                                    </div>
                                    <div v-if="payment.transaction_id" class="text-[10px] text-gray-400">
                                        Transaction: {{ payment.transaction_id }}
                                    </div>
                                    <div v-if="payment.notes" class="text-[10px] text-gray-400">
                                        {{ payment.notes }}
                                    </div>
                                </div>
                                <div class="text-[10px] text-gray-400 text-right">
                                    <div>By: {{ payment.user?.name || 'N/A' }}</div>
                                    <div>{{ formatDate(payment.created_at) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-6 text-gray-400">
                        <i class="fas fa-inbox text-2xl block mb-2"></i>
                        <p class="text-sm">No payment history found</p>
                    </div>
                </div>

                <div class="modal-footer">
                    <button @click="closeHistoryModal" class="btn-secondary">Close</button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(3px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 999;
}

.modal-card {
    background: white;
    border-radius: 1.25rem;
    max-width: 450px;
    width: 92%;
    max-height: 85vh;
    overflow-y: auto;
    padding: 1.5rem;
    box-shadow: 0 20px 40px -12px rgba(0,0,0,0.4);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 0.75rem;
    margin-bottom: 1rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    border-top: 1px solid #e2e8f0;
    padding-top: 1rem;
    margin-top: 1rem;
}

.btn-primary {
    background: #2563eb;
    color: white;
    padding: 0.5rem 1.5rem;
    border-radius: 0.75rem;
    font-weight: 600;
    font-size: 0.875rem;
    transition: 0.2s;
    border: none;
    cursor: pointer;
}

.btn-primary:hover:not(:disabled) {
    background: #1d4ed8;
}

.btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-secondary {
    background: #f1f5f9;
    color: #1e293b;
    padding: 0.5rem 1.5rem;
    border-radius: 0.75rem;
    border: 1px solid #cbd5e1;
    font-size: 0.875rem;
    cursor: pointer;
}

.btn-secondary:hover {
    background: #e2e8f0;
}
</style>