<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { usePage, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from "@inertiajs/vue3";
import { debounce } from 'lodash';

const { props } = usePage()

// Reactive data
const suppliers = ref([])
const pagination = ref({})
const filters = ref({
    search: '',
    status: ''
})

const currentPage = ref(1)
const isProcessing = ref(false)

// Modal visibility
const isModalVisible = ref(false)
const isEditMode = ref(false)

// Form data for supplier
const supplierForm = useForm({
    id: null,
    name: '',
    company_name: '',
    phone: '',
    email: '',
    address: '',
    is_active: true
})

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
    if (props.suppliers) {
        suppliers.value = props.suppliers.data || []
        pagination.value = props.suppliers
        currentPage.value = props.suppliers.current_page || 1
    }
}

// Call init on mount
initData()

// Watch for prop changes
watch(() => props.suppliers, () => {
    initData()
}, { deep: true })

// Fetch suppliers with filters
const fetchSuppliers = () => {
    router.get(route('suppliers.index', {
        page: currentPage.value,
        search: filters.value.search,
        status: filters.value.status
    }), {}, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (pageData) => {
            suppliers.value = pageData.props.suppliers?.data || []
            pagination.value = pageData.props.suppliers || {}
        },
        onError: (errors) => {
            showNotification('Failed to fetch suppliers', 'error')
        }
    })
}

// Debounced search
const debouncedSearch = debounce(() => {
    currentPage.value = 1
    fetchSuppliers()
}, 500)

// Watch filters
watch(() => filters.value.search, () => {
    debouncedSearch()
})

watch(() => filters.value.status, () => {
    currentPage.value = 1
    fetchSuppliers()
})

// Reset all filters
const resetFilters = () => {
    filters.value = {
        search: '',
        status: ''
    }
    currentPage.value = 1
    fetchSuppliers()
}

// Check if any filter is active
const hasActiveFilters = computed(() => {
    return filters.value.search || filters.value.status
})

// Pagination
const totalPages = computed(() => pagination.value.last_page || 1)

const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++
        fetchSuppliers()
    }
}

const prevPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--
        fetchSuppliers()
    }
}

// Open modal for add
const openAddModal = () => {
    isEditMode.value = false
    supplierForm.reset()
    supplierForm.is_active = true
    isModalVisible.value = true
}

// Open modal for edit
const editSupplier = (supplier) => {
    isEditMode.value = true
    supplierForm.id = supplier.id
    supplierForm.name = supplier.name
    supplierForm.company_name = supplier.company_name || ''
    supplierForm.phone = supplier.phone
    supplierForm.email = supplier.email || ''
    supplierForm.address = supplier.address || ''
    supplierForm.is_active = supplier.is_active
    isModalVisible.value = true
}

// Close modal
const closeModal = () => {
    isModalVisible.value = false
    supplierForm.reset()
    supplierForm.clearErrors()
}

// Submit supplier form
const submitSupplier = () => {
    if (isEditMode.value) {
        // Update supplier
        supplierForm.put(route('suppliers.update', supplierForm.id), {
            onSuccess: () => {
                closeModal()
                fetchSuppliers()
                showNotification('Supplier updated successfully!', 'success')
            },
            onError: (errors) => {
                showNotification(Object.values(errors)[0] || 'Failed to update supplier', 'error')
            }
        })
    } else {
        // Create supplier
        supplierForm.post(route('suppliers.store'), {
            onSuccess: () => {
                closeModal()
                fetchSuppliers()
                showNotification('Supplier created successfully!', 'success')
            },
            onError: (errors) => {
                showNotification(Object.values(errors)[0] || 'Failed to create supplier', 'error')
            }
        })
    }
}

// Delete modal
const isDeleteModalVisible = ref(false);
const supplierToDelete = ref(null);

const confirmDelete = (supplierId) => {
    supplierToDelete.value = supplierId;
    isDeleteModalVisible.value = true;
};

const deleteSupplier = () => {
    if (!supplierToDelete.value) return;
    
    isProcessing.value = true;
    
    router.delete(route('suppliers.destroy', supplierToDelete.value), {
        onSuccess: () => {
            isDeleteModalVisible.value = false;
            fetchSuppliers();
            showNotification('Supplier deleted successfully!', 'success');
            supplierToDelete.value = null;
        },
        onError: (errors) => {
            isDeleteModalVisible.value = false;
            showNotification(errors.message || 'Failed to delete the supplier.', 'error');
        },
        onFinish: () => {
            isProcessing.value = false;
        }
    });
};

const closeDeleteModal = () => {
    isDeleteModalVisible.value = false;
    supplierToDelete.value = null;
};

// Toggle status
const toggleStatus = (supplier) => {
    router.put(route('suppliers.toggle-status', supplier.id), {}, {
        onSuccess: () => {
            const index = suppliers.value.findIndex(s => s.id === supplier.id);
            if (index !== -1) {
                suppliers.value[index].is_active = !suppliers.value[index].is_active;
            }
            showNotification('Supplier status updated!', 'success');
        },
        onError: (errors) => {
            showNotification('Failed to update status.', 'error');
        }
    });
};


// Format currency
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: 2
    }).format(amount);
};

// Format date
const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

// Stats
const stats = computed(() => {
    const total = pagination.value.total || 0
    const active = suppliers.value.filter(s => s.is_active).length
    const totalDue = suppliers.value.reduce((sum, s) => sum + parseFloat(s.total_due || 0), 0)
    
    return { total, active, totalDue }
})
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Supplier Management" />
        
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
        
        <div class="min-h-screen w-full bg-gradient-to-br from-gray-50 to-blue-50/30 px-4 py-6">
            <div class="max-w-[1600px] mx-auto">
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-truck text-indigo-600"></i>
                            Supplier Management
                        </h1>
                        <p class="text-sm text-gray-500 mt-1">Manage your suppliers and vendor relationships</p>
                    </div>
                    <button 
                        @click="openAddModal" 
                        class="bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white px-5 py-2.5 rounded-xl transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2 text-sm font-semibold"
                    >
                        <i class="fas fa-plus"></i>
                        Add Supplier
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-indigo-500 hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-xs uppercase tracking-wide">Total Suppliers</p>
                                <p class="text-2xl font-bold text-gray-800">{{ stats.total }}</p>
                            </div>
                            <div class="h-10 w-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-truck text-indigo-600"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-green-500 hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-xs uppercase tracking-wide">Active Suppliers</p>
                                <p class="text-2xl font-bold text-gray-800">{{ stats.active }}</p>
                            </div>
                            <div class="h-10 w-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-check-circle text-green-600"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-yellow-500 hover:shadow-md transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-xs uppercase tracking-wide">Total Due</p>
                                <p class="text-2xl font-bold text-red-600">{{ formatCurrency(stats.totalDue) }}</p>
                            </div>
                            <div class="h-10 w-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-hand-holding-usd text-yellow-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Smart Filter Bar -->
                <div class="bg-white rounded-xl shadow-sm mb-6 p-3">
                    <div class="flex flex-col lg:flex-row gap-3">
                        <!-- Search Bar -->
                        <div class="flex-1 min-w-[200px]">
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                <input 
                                    v-model="filters.search"
                                    type="text" 
                                    placeholder="Search by name, company, phone or email..."
                                    class="w-full pl-9 pr-4 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 text-sm transition"
                                >
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div class="w-full lg:w-48">
                            <div class="relative">
                                <i class="fas fa-toggle-on absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                <select 
                                    v-model="filters.status"
                                    class="w-full pl-9 pr-8 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 appearance-none bg-white text-sm cursor-pointer"
                                >
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
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

                <!-- Suppliers Table -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gradient-to-r from-indigo-50 to-purple-50 border-b border-gray-200">
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Supplier</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Contact Info</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Total Due</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Added Date</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-if="suppliers.length === 0">
                                    <td colspan="6" class="px-4 py-12 text-center">
                                        <i class="fas fa-truck text-4xl text-gray-300 mb-3 block"></i>
                                        <p class="text-gray-500">No suppliers found</p>
                                        <button @click="openAddModal" class="mt-3 text-indigo-600 hover:text-indigo-700 text-sm font-medium">
                                            Click here to add your first supplier
                                        </button>
                                    </td>
                                </tr>
                                <tr 
                                    v-for="supplier in suppliers" 
                                    :key="supplier.id" 
                                    class="hover:bg-gray-50 transition group"
                                >
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-semibold text-sm">
                                                <i class="fas fa-building"></i>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-800 text-sm">{{ supplier.name }}</p>
                                                <p v-if="supplier.company_name" class="text-xs text-gray-500">
                                                    <i class="fas fa-building mr-1"></i>
                                                    {{ supplier.company_name }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div>
                                            <p class="text-sm text-gray-700">
                                                <i class="fas fa-phone-alt text-gray-400 mr-1 text-xs"></i>
                                                {{ supplier.phone }}
                                            </p>
                                            <p v-if="supplier.email" class="text-xs text-gray-500 mt-1">
                                                <i class="fas fa-envelope text-gray-400 mr-1 text-xs"></i>
                                                {{ supplier.email }}
                                            </p>
                                            <p v-if="supplier.address" class="text-xs text-gray-500 mt-1">
                                                <i class="fas fa-map-marker-alt text-gray-400 mr-1 text-xs"></i>
                                                {{ supplier.address.substring(0, 40) }}{{ supplier.address.length > 40 ? '...' : '' }}
                                            </p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span :class="['text-sm font-semibold', supplier.total_due > 0 ? 'text-red-600' : 'text-green-600']">
                                            {{ formatCurrency(supplier.total_due) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="[
                                            'px-2 py-1 inline-flex text-xs leading-4 font-semibold rounded-full',
                                            supplier.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                                        ]">
                                            {{ supplier.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-sm text-gray-600">
                                            <i class="far fa-calendar-alt text-gray-400 mr-1"></i>
                                            {{ formatDate(supplier.created_at) }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-2">
                                        
                                            <button 
                                                @click="toggleStatus(supplier)"
                                                :class="[
                                                    'p-1.5 rounded-lg transition',
                                                    supplier.is_active ? 'text-yellow-600 hover:bg-yellow-50' : 'text-green-600 hover:bg-green-50'
                                                ]"
                                                :title="supplier.is_active ? 'Deactivate' : 'Activate'"
                                            >
                                                <i :class="supplier.is_active ? 'fas fa-toggle-on text-lg' : 'fas fa-toggle-off text-lg'"></i>
                                            </button>
                                            <button 
                                                @click="editSupplier(supplier)"
                                                class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition"
                                                title="Edit"
                                            >
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button 
                                                @click="confirmDelete(supplier.id)"
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
                                        @click="currentPage = page; fetchSuppliers()"
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

        <!-- Add/Edit Supplier Modal -->
        <div v-if="isModalVisible" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-900 opacity-50"></div>
                </div>
                
                <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-2xl font-bold text-gray-900">
                            {{ isEditMode ? 'Edit Supplier' : 'Add New Supplier' }}
                        </h3>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <form @submit.prevent="submitSupplier" class="space-y-5">
                        <!-- Supplier Name -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Supplier Name <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <input
                                    v-model="supplierForm.name"
                                    type="text"
                                    required
                                    class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                                    placeholder="Enter supplier name"
                                >
                            </div>
                            <p v-if="supplierForm.errors.name" class="mt-1 text-sm text-red-600">{{ supplierForm.errors.name }}</p>
                        </div>

                        <!-- Company Name -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Company Name
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-building text-gray-400"></i>
                                </div>
                                <input
                                    v-model="supplierForm.company_name"
                                    type="text"
                                    class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                                    placeholder="Enter company name"
                                >
                            </div>
                            <p v-if="supplierForm.errors.company_name" class="mt-1 text-sm text-red-600">{{ supplierForm.errors.company_name }}</p>
                        </div>

                        <!-- Phone Number -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Phone Number <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-phone-alt text-gray-400"></i>
                                </div>
                                <input
                                    v-model="supplierForm.phone"
                                    type="tel"
                                    required
                                    class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                                    placeholder="Enter phone number"
                                >
                            </div>
                            <p v-if="supplierForm.errors.phone" class="mt-1 text-sm text-red-600">{{ supplierForm.errors.phone }}</p>
                        </div>

                        <!-- Email Address -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Email Address
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                </div>
                                <input
                                    v-model="supplierForm.email"
                                    type="email"
                                    class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                                    placeholder="Enter email address"
                                >
                            </div>
                            <p v-if="supplierForm.errors.email" class="mt-1 text-sm text-red-600">{{ supplierForm.errors.email }}</p>
                        </div>

                        <!-- Address -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Address
                            </label>
                            <div class="relative">
                                <div class="absolute top-3 left-3 pointer-events-none">
                                    <i class="fas fa-map-marker-alt text-gray-400"></i>
                                </div>
                                <textarea
                                    v-model="supplierForm.address"
                                    rows="3"
                                    class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                                    placeholder="Enter supplier address"
                                ></textarea>
                            </div>
                            <p v-if="supplierForm.errors.address" class="mt-1 text-sm text-red-600">{{ supplierForm.errors.address }}</p>
                        </div>

                        <!-- Active Status -->
                        <div class="flex items-center justify-between pt-2">
                            <label class="text-sm font-semibold text-gray-700">Active Status</label>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    v-model="supplierForm.is_active" 
                                    class="sr-only peer"
                                >
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>

                        <!-- Info Box -->
                        <div class="bg-blue-50 rounded-lg p-3">
                            <div class="flex items-start gap-2">
                                <i class="fas fa-info-circle text-blue-600 text-sm mt-0.5"></i>
                                <div class="text-xs text-blue-800">
                                    <p class="font-medium mb-1">Quick Tips:</p>
                                    <ul class="list-disc list-inside space-y-0.5">
                                        <li>Phone number must be unique</li>
                                        <li>Inactive suppliers won't appear in purchase orders</li>
                                        <li>Add company name for better organization</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                            <button
                                type="button"
                                @click="closeModal"
                                class="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-all duration-200"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="supplierForm.processing"
                                class="px-6 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                            >
                                <i v-if="supplierForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
                                {{ isEditMode ? 'Update Supplier' : 'Create Supplier' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="isDeleteModalVisible" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeDeleteModal">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-900 opacity-50"></div>
                </div>
                
                <div class="inline-block w-full max-w-sm p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                    <div class="text-center">
                        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                            <i class="fas fa-exclamation-triangle text-3xl text-red-600"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Delete Supplier</h3>
                        <p class="text-gray-600 mb-4">
                            Are you sure you want to delete this supplier? This action cannot be undone.
                        </p>
                        <p class="text-sm text-red-600 mb-6">
                            <i class="fas fa-warning mr-1"></i>
                            Note: Supplier with purchase history cannot be deleted.
                        </p>
                        
                        <div class="flex justify-center space-x-3">
                            <button
                                @click="closeDeleteModal"
                                class="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-all duration-200"
                            >
                                Cancel
                            </button>
                            <button
                                @click="deleteSupplier"
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