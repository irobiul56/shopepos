<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';

const { props } = usePage();

// Reactive data
const products = ref(props.products || []);
const customers = ref(props.customers || []);
const cart = ref([]);
const itemsPerPage = ref(15);
const currentPage = ref(1);
const searchQuery = ref('');
const searchBarCode = ref('');
const activeCategory = ref('all');
const discount = ref(0);
const discountType = ref('fixed');
const shippingCost = ref(0);
const paidAmount = ref(0);
const notes = ref('');
const selectedCustomer = ref(null);
const paymentMethod = ref('cash');
const saleType = ref('retail');
const isLoading = ref(false);


const getImageUrl = (image) => {
    if (!image) return null;
    if (image.startsWith('http')) return image;
    if (image.startsWith('/storage')) return image;
    return `/storage/${image}`;
};


// Customer search
const customerSearchQuery = ref('');
const showCustomerDropdown = ref(false);

// Customer modal
const isModalVisible = ref(false);
const customerForm = useForm({
    name: '',
    email: '',
    phone: '',
    address: ''
});

// Message state
const message = ref({
    show: false,
    text: '',
    type: 'success'
});

// Filtered customers for search
const filteredCustomers = computed(() => {
    if (!customerSearchQuery.value.trim()) return customers.value;
    const query = customerSearchQuery.value.toLowerCase().trim();
    return customers.value.filter(c => 
        c.name.toLowerCase().includes(query) || 
        c.phone.includes(query) ||
        c.email?.toLowerCase().includes(query)
    );
});

// Categories
const categories = computed(() => {
    if (!products.value.length) return ['all'];
    return ['all', ...new Set(products.value.map(p => p.category?.name).filter(Boolean))];
});

// Filtered products
const filteredProducts = computed(() => {
    if (!products.value.length) return [];
    
    let filtered = [...products.value];
    
    if (searchQuery.value) {
        filtered = filtered.filter(product =>
            product.name?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            product.sku?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            product.barcode?.toLowerCase().includes(searchQuery.value.toLowerCase())
        );
    }
    
    if (activeCategory.value !== 'all') {
        filtered = filtered.filter(product => product.category?.name === activeCategory.value);
    }
    
    return filtered;
});

// Pagination
const totalPages = computed(() => {
    return Math.ceil(filteredProducts.value.length / itemsPerPage.value);
});

const paginatedProducts = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredProducts.value.slice(start, end);
});

// Cart calculations
const totalQuantity = computed(() => cart.value.reduce((total, item) => total + (item.qnt || 0), 0));
const subTotal = computed(() => cart.value.reduce((total, item) => total + ((item.price || 0) * (item.qnt || 0)), 0));

const discountAmount = computed(() => {
    if (discountType.value === 'percentage') {
        return (subTotal.value * (discount.value || 0)) / 100;
    }
    return parseFloat(discount.value) || 0;
});

const tax = computed(() => {
    return cart.value.reduce((total, item) => {
        if (item.is_taxable && item.tax_rate) {
            const itemTax = (item.price * item.qnt) * (item.tax_rate / 100);
            return total + itemTax;
        }
        return total;
    }, 0);
});

const totalAfterDiscount = computed(() => subTotal.value - discountAmount.value);
const totalAmount = computed(() => totalAfterDiscount.value + tax.value + shippingCost.value);
const dueAmount = computed(() => totalAmount.value - paidAmount.value);

// Show message function
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

// Check flash messages
const checkFlashData = () => {
    if (props.flash?.success) {
        showMessage(props.flash.success, 'success');
    }
    if (props.flash?.error) {
        showMessage(props.flash.error, 'error');
    }
};

onMounted(() => {
    checkFlashData();
    document.getElementById('barcodeInput')?.focus();
});

// Watch for flash data
watch(() => props.flash, (newVal) => {
    if (newVal) {
        checkFlashData();
    }
}, { deep: true });

// Watch for search and category changes
watch([searchQuery, activeCategory], () => {
    currentPage.value = 1;
});

// Barcode scanner
watch(searchBarCode, (newValue) => {
    if (newValue && newValue.length > 3) {
        const product = products.value.find(product => 
            product.sku === newValue || product.barcode === newValue
        );
        if (product) {
            addToCart(product);
            searchBarCode.value = '';
        } else {
            showMessage('Product not found!', 'warning');
            searchBarCode.value = '';
        }
    }
});

// Cart functions
const addToCart = (product) => {
    if (!product) return;
    
    const cartItem = cart.value.find(item => item.id === product.id);
    const currentQtyInCart = cartItem ? cartItem.qnt : 0;
    
    if (currentQtyInCart + 1 > (product.stock_quantity || 0)) {
        showMessage(`Only ${product.stock_quantity} items available in stock!`, 'warning');
        return;
    }
    
    if (cartItem) {
        cartItem.qnt++;
    } else {
        cart.value.push({ 
            ...product, 
            qnt: 1,
            price: product.selling_price || 0
        });
    }
    showMessage(`${product.name} added to cart`, 'success');
};

const updateQuantity = (item, delta) => {
    const newQty = item.qnt + delta;
    if (newQty < 1) {
        removeFromCart(item);
    } else if (newQty > (item.stock_quantity || 0)) {
        showMessage(`Only ${item.stock_quantity} items available in stock!`, 'warning');
    } else {
        item.qnt = newQty;
    }
};

const removeFromCart = (product) => {
    const index = cart.value.findIndex(item => item.id === product.id);
    if (index !== -1) {
        cart.value.splice(index, 1);
        showMessage(`${product.name} removed from cart`, 'info');
    }
};

const clearCart = () => {
    if (cart.value.length > 0) {
        cart.value = [];
        showMessage('Cart cleared', 'info');
    }
};

// Customer functions
const selectCustomer = (customer) => {
    selectedCustomer.value = customer;
    customerSearchQuery.value = customer.name;
    showCustomerDropdown.value = false;
};

const AddCustomer = () => {
    customerForm.reset();
    isModalVisible.value = true;
};

const closeModal = () => {
    isModalVisible.value = false;
    customerForm.reset();
};

const submitCustomer = () => {
    customerForm.post(route('customers.store'), {
        preserveScroll: true,
        onSuccess: (response) => {
            isModalVisible.value = false;
            customers.value = response.props.customers || [];
            
            const newCustomer = response.props.customers?.find(c => c.phone === customerForm.phone);
            if (newCustomer) {
                selectedCustomer.value = newCustomer;
                customerSearchQuery.value = newCustomer.name;
                showMessage(`Customer "${newCustomer.name}" added successfully!`, 'success');
            }
            
            customerForm.reset();
        },
        onError: (errors) => {
            console.error('Customer add error:', errors);
            showMessage("Failed to add the customer.", 'error');
        },
    });
};

const handleCompleteSale = () => {
    if (cart.value.length === 0) {
        showMessage("Cart is empty!", 'warning');
        return;
    }
    
    if (paidAmount.value < 0) {
        showMessage("Paid amount cannot be negative!", 'error');
        return;
    }
    
    if (dueAmount.value > 0) {
        if (!confirm(`Due amount is $${dueAmount.value.toFixed(2)}. Do you want to continue with partial payment?`)) {
            return;
        }
    }
    
    isLoading.value = true;
    
    const invoiceData = {
        sale_type: saleType.value,
        customer_id: selectedCustomer.value?.id || null,
        subtotal: subTotal.value,
        discount: discount.value,
        discount_type: discountType.value,
        tax: tax.value,
        shipping_cost: shippingCost.value,
        total_amount: totalAmount.value,
        paid_amount: paidAmount.value,
        due_amount: dueAmount.value,
        payment_method: paymentMethod.value,
        payment_status: dueAmount.value > 0 ? (paidAmount.value > 0 ? 'partial' : 'unpaid') : 'paid',
        notes: notes.value,
        sale_date: new Date().toISOString().split('T')[0],
        items: cart.value.map(item => ({
            product_id: item.id,
            quantity: item.qnt,
            unit_price: item.price,
            total_price: item.price * item.qnt
        }))
    };
    
    console.log('Sending invoice data:', invoiceData);
    
    router.post(route('pos.store'), invoiceData, {
        preserveScroll: true,
        onSuccess: (response) => {
            console.log('Success response:', response);
            isLoading.value = false;
            resetPos();
            
            // Inertia.location ব্যবহার করলে auto redirect হবে
            // তাই এখানে আর কিছু করার দরকার নেই
        },
        onError: (errors) => {
            console.error('Sale error:', errors);
            showMessage("Failed to complete sale. Please try again.", 'error');
            isLoading.value = false;
        }
    });
};

// Keyboard shortcuts
onMounted(() => {
    const handleKeyDown = (e) => {
        if (e.ctrlKey && e.key === 'f') {
            e.preventDefault();
            document.querySelector('input[placeholder*="Name"]')?.focus();
        }
        if (e.key === 'F1') {
            e.preventDefault();
            document.getElementById('barcodeInput')?.focus();
        }
        if (e.key === 'F2') {
            e.preventDefault();
            handleCompleteSale();
        }
        if (e.key === 'Escape') {
            searchQuery.value = '';
            searchBarCode.value = '';
            showCustomerDropdown.value = false;
        }
    };

    window.addEventListener('keydown', handleKeyDown);
    
    return () => {
        window.removeEventListener('keydown', handleKeyDown);
    };
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Point of Sale" />
        
        <!-- Flash Message -->
        <div v-if="message.show" 
             :class="[
               'fixed top-3 right-3 z-50 px-4 py-1.5 rounded-full shadow-lg animate-fade',
               message.type === 'success' ? 'bg-emerald-600 text-white' : 
               message.type === 'error' ? 'bg-rose-600 text-white' : 
               message.type === 'warning' ? 'bg-amber-500 text-white' : 'bg-blue-500 text-white'
             ]">
            <i :class="[
                'mr-1',
                message.type === 'success' ? 'fas fa-check-circle' : 
                message.type === 'error' ? 'fas fa-exclamation-circle' : 
                message.type === 'warning' ? 'fas fa-exclamation-triangle' : 'fas fa-info-circle'
            ]"></i>
            <span class="text-sm font-medium">{{ message.text }}</span>
        </div>

        <!-- Loading Overlay -->
        <div v-if="isLoading" class="fixed inset-0 bg-black/30 backdrop-blur-sm z-50 flex items-center justify-center">
            <div class="bg-white rounded-xl p-6 shadow-xl flex items-center gap-3">
                <i class="fas fa-spinner fa-spin text-indigo-600 text-2xl"></i>
                <span class="font-medium">Processing sale...</span>
            </div>
        </div>

        <div class="min-h-screen bg-gray-100 p-2 md:p-4 w-full">
            <div class="pos-grid">
                <!-- LEFT: Products -->
                <div class="left-panel">
                    <!-- Search & Categories -->
                    <div class="bg-white/90 backdrop-blur-sm p-3 rounded-xl shadow-sm border border-white/30">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <div>
                                <label class="block compact-label text-slate-500 uppercase">
                                    <i class="fas fa-barcode mr-1"></i> Barcode / SKU
                                </label>
                                <input
                                    id="barcodeInput"
                                    v-model="searchBarCode"
                                    type="text"
                                    placeholder="Scan or SKU"
                                    class="w-full border border-slate-200 rounded-lg compact-input bg-slate-50/80 focus:bg-white"
                                    autocomplete="off"
                                />
                                <p class="text-[0.6rem] text-slate-400 mt-0.5">⌨️ F1</p>
                            </div>
                            <div>
                                <label class="block compact-label text-slate-500 uppercase">
                                    <i class="fas fa-search mr-1"></i> Search
                                </label>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Name, SKU"
                                    class="w-full border border-slate-200 rounded-lg compact-input bg-slate-50/80 focus:bg-white"
                                />
                                <p class="text-[0.6rem] text-slate-400 mt-0.5">⌨️ Ctrl+F</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <button
                                v-for="category in categories"
                                :key="category"
                                @click="activeCategory = category"
                                :class="[
                                    'px-3 py-0.5 text-[0.7rem] rounded-full transition',
                                    activeCategory === category
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                                ]"
                            >
                                {{ category === 'all' ? 'All' : category.charAt(0).toUpperCase() + category.slice(1) }}
                            </button>
                        </div>
                    </div>

                    <!-- Products Table -->
                    <div class="bg-white/90 backdrop-blur-sm rounded-xl shadow-sm border border-white/30 p-3 flex-1 flex flex-col overflow-hidden">
                        <div class="flex justify-between items-center mb-1">
                            <h4 class="font-bold text-slate-700 text-sm">
                                <i class="fas fa-cubes mr-1 text-indigo-500"></i>Products
                            </h4>
                            <span class="text-[0.7rem] text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">
                                {{ paginatedProducts.length }}
                            </span>
                        </div>
                        <div class="scroll-products">
                            <table class="w-full product-table">
                                <thead class="text-slate-500 text-[0.6rem] uppercase tracking-wider border-b border-slate-100">
                                    <tr>
                                        <th class="text-left">Product</th>
                                        <th class="text-left">SKU</th>
                                        <th class="text-left">Price</th>
                                        <th class="text-left">Stock</th>
                                        <th class="text-center">+</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr 
                                        v-for="item in paginatedProducts" 
                                        :key="item.id"
                                        class="hover:bg-slate-50 transition cursor-pointer"
                                        @click="addToCart(item)"
                                    >
                                        <td>
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0">
                                                <img 
                                                    v-if="item.product_image" 
                                                    :src="getImageUrl(item.product_image)"
                                                    class="h-10 w-10 rounded-lg object-cover"
                                                   
                                                >
                                                <div v-else class="h-10 w-10 rounded-lg bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center">
                                                    <i class="fas fa-box text-indigo-400"></i>
                                                </div>
                                            </div>
                                                <span class="ml-2">{{ item.name }}</span>
                                            </div>
                                        </td>
                                        <td>{{ item.sku }}</td>
                                        <td>${{ Number(item.selling_price).toFixed(2) }}</td>
                                        <td>
                                            <span class="badge-pill" :class="(item.stock_quantity || 0) > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                                                {{ item.stock_quantity || 0 }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <button 
                                                @click.stop="addToCart(item)"
                                                :disabled="(item.stock_quantity || 0) === 0 || !item.is_active"
                                                class="bg-indigo-600 text-white rounded-full btn-circle hover:bg-indigo-700 transition disabled:bg-slate-300 disabled:cursor-not-allowed"
                                            >
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="paginatedProducts.length === 0">
                                        <td colspan="5" class="text-center py-8 text-slate-400">
                                            <i class="fas fa-box-open text-3xl block mb-2"></i>
                                            <span class="text-sm">No products found</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div class="flex justify-between items-center mt-2 text-[0.65rem] text-slate-400" v-if="totalPages > 1">
                                <span>Page {{ currentPage }} / {{ totalPages }}</span>
                                <div class="flex gap-1">
                                    <button 
                                        @click="currentPage--" 
                                        :disabled="currentPage === 1"
                                        class="px-2 py-0.5 border rounded-full disabled:opacity-30"
                                    >
                                        ‹
                                    </button>
                                    <button 
                                        @click="currentPage++" 
                                        :disabled="currentPage === totalPages"
                                        class="px-2 py-0.5 border rounded-full disabled:opacity-30"
                                    >
                                        ›
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Cart + Checkout -->
                <div class="right-panel">
                    <!-- Cart -->
                    <div class="bg-white/90 backdrop-blur-sm rounded-xl shadow-sm border border-white/30 p-3 flex-1 flex flex-col">
                        <div class="flex justify-between items-center border-b border-slate-100 pb-1.5">
                            <h4 class="font-bold text-slate-700 text-sm">
                                <i class="fas fa-shopping-bag mr-1 text-indigo-500"></i>Cart (<span>{{ totalQuantity }}</span>)
                            </h4>
                            <button 
                                v-if="cart.length > 0"
                                @click="clearCart"
                                class="text-rose-500 text-[0.7rem] hover:text-rose-700"
                            >
                                <i class="fas fa-trash-alt mr-1"></i>Clear
                            </button>
                        </div>
                        <div class="cart-scroll mt-1.5 space-y-1.5 pr-1">
                            <div 
                                v-for="item in cart"
                                :key="item.id"
                                class="border border-slate-200 rounded-lg p-2 flex justify-between items-start bg-slate-50/50 cart-item"
                            >
                                <div>
                                    <span class="font-semibold">{{ item.name }}</span>
                                    <div class="text-[0.7rem] text-slate-400">${{ Number(item.price).toFixed(2) }} × {{ item.qnt }}</div>
                                    <div class="flex items-center mt-0.5">
                                        <button 
                                            @click="updateQuantity(item, -1)"
                                            class="bg-slate-200 rounded-full qty-btn hover:bg-slate-300 transition"
                                        >
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <span class="mx-1.5 text-sm font-bold">{{ item.qnt }}</span>
                                        <button 
                                            @click="updateQuantity(item, 1)"
                                            :disabled="item.qnt >= (item.stock_quantity || 0)"
                                            class="bg-indigo-100 rounded-full qty-btn text-indigo-600 hover:bg-indigo-200 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="font-bold text-sm">${{ ((item.price || 0) * (item.qnt || 0)).toFixed(2) }}</div>
                            </div>
                            <div v-if="cart.length === 0" class="text-center py-4 text-slate-400 text-sm">
                                <i class="fas fa-shopping-bag text-2xl block mb-1"></i>
                                No items in cart
                            </div>
                        </div>
                        <div class="border-t border-slate-200 pt-1.5 mt-1.5 flex justify-between text-sm font-medium">
                            <span>Subtotal</span>
                            <span class="text-indigo-700">${{ subTotal.toFixed(2) }}</span>
                        </div>
                    </div>

                    <!-- Checkout -->
                    <div class="bg-white/90 backdrop-blur-sm rounded-xl shadow-sm border border-white/30 p-3 checkout-scrollable">
                        <h4 class="font-bold text-slate-700 text-sm mb-1.5">
                            <i class="fas fa-cash-register mr-1 text-indigo-500"></i>Checkout
                        </h4>
                        <div class="space-y-1.5 text-sm">
                            <!-- Sale Type -->
                            <div>
                                <label class="block compact-label text-slate-500">Sale Type</label>
                                <div class="flex gap-1.5">
                                    <button
                                        @click="saleType = 'retail'"
                                        :class="[
                                            'px-3 py-0.5 rounded-full text-sm border transition',
                                            saleType === 'retail'
                                                ? 'border-indigo-300 bg-indigo-50 text-indigo-700 font-medium'
                                                : 'border-slate-300 hover:bg-slate-50'
                                        ]"
                                    >
                                        Retail
                                    </button>
                                    <button
                                        @click="saleType = 'wholesale'"
                                        :class="[
                                            'px-3 py-0.5 rounded-full text-sm border transition',
                                            saleType === 'wholesale'
                                                ? 'border-indigo-300 bg-indigo-50 text-indigo-700 font-medium'
                                                : 'border-slate-300 hover:bg-slate-50'
                                        ]"
                                    >
                                        Wholesale
                                    </button>
                                </div>
                            </div>

                            <!-- Customer with search -->
                            <div>
                                <label class="block compact-label text-slate-500">Customer</label>
                                <div class="flex gap-1.5">
                                    <div class="customer-search-wrap flex-1">
                                        <input
                                            v-model="customerSearchQuery"
                                            @focus="showCustomerDropdown = true"
                                            @blur="setTimeout(() => showCustomerDropdown = false, 200)"
                                            type="text"
                                            placeholder="Search or select customer..."
                                            class="w-full border border-slate-200 rounded-lg compact-input bg-slate-50/80 focus:bg-white"
                                            autocomplete="off"
                                        />
                                        <i class="fas fa-search search-icon"></i>
                                        <div v-if="showCustomerDropdown && filteredCustomers.length > 0" class="customer-dropdown show">
                                            <div
                                                v-for="customer in filteredCustomers"
                                                :key="customer.id"
                                                @mousedown="selectCustomer(customer)"
                                                class="option"
                                            >
                                                {{ customer.name }}
                                                <span class="text-[0.6rem] text-slate-400 block">{{ customer.phone }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <button 
                                        @click="AddCustomer"
                                        class="bg-emerald-600 text-white px-3 py-0.5 rounded-lg hover:bg-emerald-700 transition text-sm flex items-center gap-1"
                                    >
                                        <i class="fas fa-user-plus"></i> Add
                                    </button>
                                </div>
                            </div>

                            <!-- Discount -->
                            <div class="grid grid-cols-2 gap-1.5">
                                <div>
                                    <label class="block compact-label text-slate-500">Discount</label>
                                    <select 
                                        v-model="discountType"
                                        class="w-full border border-slate-200 rounded-lg compact-select bg-slate-50/80"
                                    >
                                        <option value="fixed">Fixed ($)</option>
                                        <option value="percentage">%</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block compact-label text-slate-500">Amount</label>
                                    <input
                                        v-model="discount"
                                        type="number"
                                        min="0"
                                        class="w-full border border-slate-200 rounded-lg compact-input bg-slate-50/80"
                                    />
                                </div>
                            </div>

                            <!-- Shipping -->
                            <div>
                                <label class="block compact-label text-slate-500">Shipping</label>
                                <input
                                    v-model="shippingCost"
                                    type="number"
                                    min="0"
                                    class="w-full border border-slate-200 rounded-lg compact-input bg-slate-50/80"
                                />
                            </div>

                            <!-- Payment Method -->
                            <div>
                                <label class="block compact-label text-slate-500">Payment</label>
                                <div class="flex gap-1">
                                    <button
                                        v-for="method in ['cash', 'card', 'mobile_banking', 'credit']"
                                        :key="method"
                                        @click="paymentMethod = method"
                                        :class="[
                                            'px-3 py-0.5 rounded-full text-sm border transition',
                                            paymentMethod === method
                                                ? 'border-indigo-300 bg-indigo-50 text-indigo-700 font-medium'
                                                : 'border-slate-300 hover:bg-slate-50'
                                        ]"
                                    >
                                        {{ method.replace('_', ' ') }}
                                    </button>
                                </div>
                            </div>

                            <!-- Paid Amount -->
                            <div>
                                <label class="block compact-label text-slate-500">Paid</label>
                                <input
                                    v-model="paidAmount"
                                    type="number"
                                    min="0"
                                    :max="totalAmount"
                                    class="w-full border border-slate-200 rounded-lg compact-input bg-slate-50/80"
                                />
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="block compact-label text-slate-500">Notes</label>
                                <textarea
                                    v-model="notes"
                                    rows="1"
                                    class="w-full border border-slate-200 rounded-lg compact-input bg-slate-50/80"
                                ></textarea>
                            </div>

                            <!-- Summary -->
                            <div class="summary-flow">
                                <div class="space-y-0.5 text-sm bg-slate-50/80 p-2 rounded-lg">
                                    <div class="flex justify-between">
                                        <span class="text-slate-500">Subtotal</span>
                                        <span>${{ subTotal.toFixed(2) }}</span>
                                    </div>
                                    <div class="flex justify-between text-rose-500">
                                        <span>Discount</span>
                                        <span>-${{ discountAmount.toFixed(2) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500">Tax</span>
                                        <span>${{ tax.toFixed(2) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500">Shipping</span>
                                        <span>${{ Number(shippingCost).toFixed(2) }}</span>
                                    </div>
                                    <div class="flex justify-between font-bold text-base pt-0.5 border-t border-slate-200">
                                        <span>Total</span>
                                        <span class="text-indigo-700">${{ totalAmount.toFixed(2) }}</span>
                                    </div>
                                    <div v-if="dueAmount > 0" class="flex justify-between text-amber-600 font-bold">
                                        <span>Due</span>
                                        <span>${{ dueAmount.toFixed(2) }}</span>
                                    </div>
                                    <div class="flex justify-between text-xs text-slate-500">
                                        <span>Payment Status</span>
                                        <span :class="{
                                            'text-emerald-600': dueAmount <= 0,
                                            'text-amber-600': dueAmount > 0 && paidAmount > 0,
                                            'text-rose-600': dueAmount > 0 && paidAmount === 0
                                        }" class="font-medium capitalize">
                                            {{ dueAmount <= 0 ? 'Paid' : (paidAmount > 0 ? 'Partial' : 'Unpaid') }}
                                        </span>
                                    </div>
                                </div>
                                <button
                                    @click="handleCompleteSale"
                                    :disabled="cart.length === 0 || isLoading"
                                    :class="[
                                        'w-full py-2 rounded-xl font-bold text-sm transition shadow-sm mt-1.5',
                                        cart.length === 0 || isLoading
                                            ? 'bg-slate-300 text-slate-500 cursor-not-allowed'
                                            : 'bg-gradient-to-r from-indigo-600 to-indigo-700 text-white hover:shadow-lg'
                                    ]"
                                >
                                    <i v-if="isLoading" class="fas fa-spinner fa-spin mr-1"></i>
                                    <i v-else class="fas fa-print mr-1"></i>
                                    {{ isLoading ? 'Processing...' : 'Complete Sale (F2)' }}
                                </button>
                                <div class="text-[0.6rem] text-slate-400 text-center flex justify-center gap-3 mt-0.5">
                                    <span>F1 Barcode</span>
                                    <span>F2 Sale</span>
                                    <span>Esc Clear</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Modal -->
        <div v-if="isModalVisible" class="modal-overlay" @click.self="closeModal">
            <div class="modal-card">
                <div class="modal-header">
                    <h3 class="font-bold text-base flex items-center gap-2">
                        <i class="fas fa-user-plus text-emerald-600"></i>Add Customer
                    </h3>
                    <button @click="closeModal" class="text-slate-400 hover:text-rose-500 transition">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="space-y-2.5 text-sm">
                    <div>
                        <input
                            v-model="customerForm.name"
                            type="text"
                            placeholder="Full Name *"
                            class="form-input w-full border border-slate-200 rounded-lg p-2 text-sm"
                            :class="{ 'border-rose-500': customerForm.errors.name }"
                        />
                        <div v-if="customerForm.errors.name" class="text-rose-500 text-xs mt-0.5">
                            {{ customerForm.errors.name }}
                        </div>
                    </div>
                    <div>
                        <input
                            v-model="customerForm.phone"
                            type="tel"
                            placeholder="Phone Number *"
                            class="form-input w-full border border-slate-200 rounded-lg p-2 text-sm"
                            :class="{ 'border-rose-500': customerForm.errors.phone }"
                        />
                        <div v-if="customerForm.errors.phone" class="text-rose-500 text-xs mt-0.5">
                            {{ customerForm.errors.phone }}
                        </div>
                    </div>
                    <div>
                        <input
                            v-model="customerForm.email"
                            type="email"
                            placeholder="Email Address"
                            class="form-input w-full border border-slate-200 rounded-lg p-2 text-sm"
                            :class="{ 'border-rose-500': customerForm.errors.email }"
                        />
                        <div v-if="customerForm.errors.email" class="text-rose-500 text-xs mt-0.5">
                            {{ customerForm.errors.email }}
                        </div>
                    </div>
                    <div>
                        <textarea
                            v-model="customerForm.address"
                            rows="2"
                            placeholder="Address"
                            class="form-input w-full border border-slate-200 rounded-lg p-2 text-sm"
                        ></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="closeModal" class="btn-secondary">Cancel</button>
                    <button 
                        @click="submitCustomer" 
                        :disabled="customerForm.processing"
                        class="btn-primary"
                    >
                        <span v-if="customerForm.processing">
                            <i class="fas fa-spinner fa-spin mr-1"></i>Saving...
                        </span>
                        <span v-else>
                            <i class="fas fa-save mr-1"></i>Save
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.pos-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 0.6rem;
    height: calc(100vh - 2rem);
    min-height: 0;
}

.left-panel, .right-panel {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-height: 0;
}

.scroll-products {
    flex: 1 1 auto;
    overflow-y: auto;
    min-height: 0;
}

.cart-scroll {
    flex: 1 1 auto;
    overflow-y: auto;
    min-height: 0;
    max-height: 150px;
}

.checkout-scrollable {
    flex: 1 1 auto;
    overflow-y: auto;
    min-height: 0;
    padding-right: 0.2rem;
}

.summary-flow {
    margin-top: 0.5rem;
    border-top: 1px solid #e2e8f0;
    padding-top: 0.5rem;
}

.compact-input {
    padding: 0.3rem 0.6rem;
    font-size: 0.8rem;
    border-radius: 0.5rem;
}

.compact-select {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
    border-radius: 0.5rem;
}

.compact-label {
    font-size: 0.65rem;
    letter-spacing: 0.02em;
    font-weight: 600;
}

.product-table td, .product-table th {
    padding: 0.35rem 0.25rem;
    font-size: 0.8rem;
}

.product-table .btn-circle {
    width: 1.6rem;
    height: 1.6rem;
    font-size: 0.7rem;
    border-radius: 9999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.cart-item {
    padding: 0.35rem 0.5rem;
    font-size: 0.8rem;
}

.cart-item .qty-btn {
    width: 1.4rem;
    height: 1.4rem;
    font-size: 0.65rem;
    border-radius: 9999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.badge-pill {
    padding: 0.1rem 0.6rem;
    border-radius: 9999px;
    font-size: 0.7rem;
    font-weight: 600;
}

.customer-search-wrap {
    position: relative;
}

.customer-search-wrap input {
    width: 100%;
    padding-right: 2rem;
}

.customer-search-wrap .search-icon {
    position: absolute;
    right: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.7rem;
}

.customer-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    max-height: 120px;
    overflow-y: auto;
    z-index: 20;
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    display: none;
}

.customer-dropdown.show {
    display: block;
}

.customer-dropdown .option {
    padding: 0.3rem 0.6rem;
    font-size: 0.8rem;
    cursor: pointer;
    border-bottom: 1px solid #f1f5f9;
}

.customer-dropdown .option:hover {
    background: #f1f5f9;
}

.customer-dropdown .option:last-child {
    border-bottom: none;
}

.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 999;
}

.modal-card {
    background: white;
    border-radius: 1.25rem;
    max-width: 500px;
    width: 92%;
    max-height: 85vh;
    overflow-y: auto;
    padding: 1.25rem;
    box-shadow: 0 20px 40px -12px rgba(0,0,0,0.4);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 0.5rem;
    margin-bottom: 0.75rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    border-top: 1px solid #e2e8f0;
    padding-top: 0.75rem;
    margin-top: 0.75rem;
}

.btn-primary {
    background: #2563eb;
    color: white;
    padding: 0.4rem 1.2rem;
    border-radius: 9999px;
    font-weight: 600;
    font-size: 0.8rem;
    transition: 0.2s;
    border: none;
    cursor: pointer;
}

.btn-primary:hover:not(:disabled) {
    background: #1d4ed8;
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-secondary {
    background: #f1f5f9;
    color: #1e293b;
    padding: 0.4rem 1.2rem;
    border-radius: 9999px;
    border: 1px solid #cbd5e1;
    font-size: 0.8rem;
    cursor: pointer;
}

.btn-secondary:hover {
    background: #e2e8f0;
}

.scroll-products::-webkit-scrollbar,
.cart-scroll::-webkit-scrollbar,
.checkout-scrollable::-webkit-scrollbar {
    width: 3px;
}

.scroll-products::-webkit-scrollbar-thumb,
.cart-scroll::-webkit-scrollbar-thumb,
.checkout-scrollable::-webkit-scrollbar-thumb {
    background: #94a3b8;
    border-radius: 8px;
}

.animate-fade {
    animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>