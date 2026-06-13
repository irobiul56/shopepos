<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { usePage, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from "@inertiajs/vue3";
import { debounce } from 'lodash';

const { props } = usePage()

// Reactive data
const products = ref([])
const pagination = ref({})
const filters = ref({
    search: '',
    category_id: '',
    brand_id: '',
    stock_status: ''
})

const currentPage = ref(1)
const isProcessing = ref(false)

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
    if (props.products) {
        products.value = props.products.data || []
        pagination.value = props.products
        currentPage.value = props.products.current_page || 1
    }
}

// Call init on mount
initData()

// Watch for prop changes
watch(() => props.products, () => {
    initData()
}, { deep: true })

// Stock status options
const stockStatusOptions = [
    { value: '', label: 'All Stock', icon: 'fa-boxes' },
    { value: 'in', label: 'In Stock', icon: 'fa-check-circle', color: 'text-green-600' },
    { value: 'low', label: 'Low Stock', icon: 'fa-exclamation-triangle', color: 'text-yellow-600' },
    { value: 'out', label: 'Out of Stock', icon: 'fa-times-circle', color: 'text-red-600' }
]

// Fetch products with filters
const fetchProducts = () => {
    router.get(route('products.index', {
        page: currentPage.value,
        search: filters.value.search,
        category_id: filters.value.category_id,
        brand_id: filters.value.brand_id,
        stock_status: filters.value.stock_status
    }), {}, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (pageData) => {
            products.value = pageData.props.products?.data || []
            pagination.value = pageData.props.products || {}
        },
        onError: (errors) => {
            showNotification('Failed to fetch products', 'error')
        }
    })
}

// Debounced search
const debouncedSearch = debounce(() => {
    currentPage.value = 1
    fetchProducts()
}, 500)

// Watch filters
watch(() => filters.value.search, () => {
    debouncedSearch()
})

watch(() => filters.value.category_id, () => {
    currentPage.value = 1
    fetchProducts()
})

watch(() => filters.value.brand_id, () => {
    currentPage.value = 1
    fetchProducts()
})

watch(() => filters.value.stock_status, () => {
    currentPage.value = 1
    fetchProducts()
})

// Reset all filters
const resetFilters = () => {
    filters.value = {
        search: '',
        category_id: '',
        brand_id: '',
        stock_status: ''
    }
    currentPage.value = 1
    fetchProducts()
}

// Check if any filter is active
const hasActiveFilters = computed(() => {
    return filters.value.search || filters.value.category_id || filters.value.brand_id || filters.value.stock_status
})

// Pagination
const totalPages = computed(() => pagination.value.last_page || 1)

const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++
        fetchProducts()
    }
}

const prevPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--
        fetchProducts()
    }
}

// Delete modal
const isDeleteModalVisible = ref(false);
const productToDelete = ref(null);

const confirmDelete = (productId) => {
    productToDelete.value = productId;
    isDeleteModalVisible.value = true;
};

const deleteProduct = () => {
    if (!productToDelete.value) return;
    
    isProcessing.value = true;
    
    router.delete(route('products.destroy', productToDelete.value), {
        onSuccess: () => {
            isDeleteModalVisible.value = false;
            fetchProducts();
            showNotification('Product deleted successfully!', 'success');
            productToDelete.value = null;
        },
        onError: (errors) => {
            isDeleteModalVisible.value = false;
            showNotification(errors.message || 'Failed to delete the product.', 'error');
        },
        onFinish: () => {
            isProcessing.value = false;
        }
    });
};

const closeDeleteModal = () => {
    isDeleteModalVisible.value = false;
    productToDelete.value = null;
};

// Toggle status
const toggleStatus = (product) => {
    router.put(route('products.toggle-status', product.id), {}, {
        onSuccess: () => {
            const index = products.value.findIndex(p => p.id === product.id);
            if (index !== -1) {
                products.value[index].is_active = !products.value[index].is_active;
            }
            showNotification('Product status updated!', 'success');
        },
        onError: (errors) => {
            showNotification('Failed to update status.', 'error');
        }
    });
};

// Utility functions
const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: 2
    }).format(price);
};

const getStockBadgeClass = (product) => {
    if (product.stock_quantity <= 0) {
        return 'bg-red-100 text-red-800'
    } else if (product.stock_quantity <= product.min_stock_alert) {
        return 'bg-yellow-100 text-yellow-800'
    } else {
        return 'bg-green-100 text-green-800'
    }
};

const getStockText = (product) => {
    if (product.stock_quantity <= 0) {
        return 'Out of Stock'
    } else if (product.stock_quantity <= product.min_stock_alert) {
        return 'Low Stock'
    } else {
        return 'In Stock'
    }
};

const getImageUrl = (image) => {
    if (!image) return null;
    if (image.startsWith('http')) return image;
    if (image.startsWith('/storage')) return image;
    return `/storage/${image}`;
};

const handleImageError = (event) => {
    event.target.style.display = 'none';
    if (event.target.nextElementSibling) {
        event.target.nextElementSibling.style.display = 'flex';
    }
};

// Navigation
const createProduct = () => {
    router.get(route('products.create'))
}

const editProduct = (productId) => {
    router.get(route('products.edit', productId))
}

// Quick stats
const stats = computed(() => {
    const total = products.value.length
    const inStock = products.value.filter(p => p.stock_quantity > p.min_stock_alert).length
    const lowStock = products.value.filter(p => p.stock_quantity <= p.min_stock_alert && p.stock_quantity > 0).length
    const outStock = products.value.filter(p => p.stock_quantity <= 0).length
    
    return { total, inStock, lowStock, outStock }
})
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Product Management" />
        
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
        
        <div class=" min-h-screen w-full bg-gradient-to-br from-gray-50 to-blue-50/30 sm:px-6 lg:px-8 py-8">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-boxes text-indigo-600"></i>
                            Products
                        </h1>
                        <p class="text-sm text-gray-500 mt-1">Manage your product inventory</p>
                    </div>
                    <button 
                        @click="createProduct" 
                        class="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white px-5 py-2.5 rounded-xl transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2 text-sm font-semibold"
                    >
                        <i class="fas fa-plus"></i>
                        Add Product
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-indigo-500 hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-xs uppercase tracking-wide">Total Products</p>
                                <p class="text-2xl font-bold text-gray-800">{{ stats.total }}</p>
                            </div>
                            <div class="h-10 w-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-boxes text-indigo-600"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-green-500 hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-xs uppercase tracking-wide">In Stock</p>
                                <p class="text-2xl font-bold text-gray-800">{{ stats.inStock }}</p>
                            </div>
                            <div class="h-10 w-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-600"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-yellow-500 hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-xs uppercase tracking-wide">Low Stock</p>
                                <p class="text-2xl font-bold text-gray-800">{{ stats.lowStock }}</p>
                            </div>
                            <div class="h-10 w-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-red-500 hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-xs uppercase tracking-wide">Out of Stock</p>
                                <p class="text-2xl font-bold text-gray-800">{{ stats.outStock }}</p>
                            </div>
                            <div class="h-10 w-10 bg-red-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-times-circle text-red-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Smart Filter Bar - One Line -->
                <div class="bg-white rounded-xl shadow-sm mb-6 p-3">
                    <div class="flex flex-col lg:flex-row gap-3">
                        <!-- Search Bar -->
                        <div class="flex-1 min-w-[200px]">
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                <input 
                                    v-model="filters.search"
                                    type="text" 
                                    placeholder="Search by name, SKU or barcode..."
                                    class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 text-sm transition"
                                >
                            </div>
                        </div>

                        <!-- Category Filter -->
                        <div class="w-full lg:w-64">
                            <div class="relative">
                                <i class="fas fa-tags absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                <select 
                                    v-model="filters.category_id"
                                    class="w-full pl-9 pr-8 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 appearance-none bg-white text-sm cursor-pointer"
                                >
                                    <option value="">All Categories</option>
                                    <option v-for="category in props.categories" :key="category.id" :value="category.id">
                                        {{ category.name }}
                                    </option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                            </div>
                        </div>

                        <!-- Brand Filter -->
                        <div class="w-full lg:w-64">
                            <div class="relative">
                                <i class="fas fa-building absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                <select 
                                    v-model="filters.brand_id"
                                    class="w-full pl-9 pr-8 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 appearance-none bg-white text-sm cursor-pointer"
                                >
                                    <option value="">All Brands</option>
                                    <option v-for="brand in props.brands" :key="brand.id" :value="brand.id">
                                        {{ brand.name }}
                                    </option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                            </div>
                        </div>

                        <!-- Stock Status Filter -->
                        <div class="w-full lg:w-56">
                            <div class="relative">
                                <i class="fas fa-chart-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                <select 
                                    v-model="filters.stock_status"
                                    class="w-full pl-9 pr-8 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 appearance-none bg-white text-sm cursor-pointer"
                                >
                                    <option v-for="option in stockStatusOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                            </div>
                        </div>

                        <!-- Reset Button -->
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

                <!-- Products Table -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gradient-to-r from-indigo-50 to-purple-50 border-b border-gray-200">
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Product</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">SKU</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Category</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Price</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Stock</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-if="products.length === 0">
                                    <td colspan="7" class="px-4 py-12 text-center">
                                        <i class="fas fa-box-open text-4xl text-gray-300 mb-3 block"></i>
                                        <p class="text-gray-500">No products found</p>
                                        <button @click="createProduct" class="mt-3 text-indigo-600 hover:text-indigo-700 text-sm font-medium">
                                            Click here to add your first product
                                        </button>
                                    </td>
                                </tr>
                                <tr 
                                    v-for="product in products" 
                                    :key="product.id" 
                                    class="hover:bg-gray-50 transition group"
                                >
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-shrink-0">
                                                <img 
                                                    v-if="product.product_image" 
                                                    :src="getImageUrl(product.product_image)"
                                                    class="h-10 w-10 rounded-lg object-cover"
                                                    @error="handleImageError"
                                                >
                                                <div v-else class="h-10 w-10 rounded-lg bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center">
                                                    <i class="fas fa-box text-indigo-400"></i>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-800 text-sm">{{ product.name }}</p>
                                                <p class="text-xs text-gray-400">{{ product.barcode || 'No barcode' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <code class="text-xs bg-gray-100 px-2 py-1 rounded text-gray-600">{{ product.sku }}</code>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-sm text-gray-600">{{ product.category?.name || 'N/A' }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-800">{{ formatPrice(product.selling_price) }}</p>
                                            <p class="text-xs text-gray-400">Cost: {{ formatPrice(product.purchase_price) }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col gap-1">
                                            <span class="text-sm font-medium text-gray-700">{{ product.stock_quantity }} {{ product.unit?.short_name }}</span>
                                            <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium w-fit', getStockBadgeClass(product)]">
                                                {{ getStockText(product) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="[
                                            'px-2 py-1 inline-flex text-xs leading-4 font-semibold rounded-full',
                                            product.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                                        ]">
                                            {{ product.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-2">
                                            <button 
                                                @click="toggleStatus(product)"
                                                :class="[
                                                    'p-1.5 rounded-lg transition tooltip',
                                                    product.is_active ? 'text-yellow-600 hover:bg-yellow-50' : 'text-green-600 hover:bg-green-50'
                                                ]"
                                                :title="product.is_active ? 'Deactivate' : 'Activate'"
                                            >
                                                <i :class="product.is_active ? 'fas fa-toggle-on text-lg' : 'fas fa-toggle-off text-lg'"></i>
                                            </button>
                                            <button 
                                                @click="editProduct(product.id)"
                                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                                title="Edit"
                                            >
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button 
                                                @click="confirmDelete(product.id)"
                                                class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition"
                                                title="Delete"
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
                                        @click="currentPage = page; fetchProducts()"
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

        <!-- Delete Modal -->
        <div v-if="isDeleteModalVisible" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeDeleteModal">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-900 opacity-50"></div>
                <div class="relative bg-white rounded-xl max-w-md w-full p-6 shadow-xl transform transition-all">
                    <div class="text-center">
                        <div class="mx-auto h-12 w-12 rounded-full bg-red-100 flex items-center justify-center mb-4">
                            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Delete Product</h3>
                        <p class="text-sm text-gray-500 mb-4">
                            Are you sure you want to delete this product? This action cannot be undone.
                        </p>
                        <div class="flex gap-3 justify-center">
                            <button
                                @click="closeDeleteModal"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
                            >
                                Cancel
                            </button>
                            <button
                                @click="deleteProduct"
                                :disabled="isProcessing"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 disabled:opacity-50 transition"
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
.tooltip {
    position: relative;
}

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