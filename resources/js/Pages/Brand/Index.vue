<script setup>
import { ref, computed, watch } from 'vue'
import { usePage, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from "@inertiajs/vue3";

const { props } = usePage()

// Reactive data
const brands = ref([])
const pagination = ref({})
const searchQuery = ref('')
const currentPage = ref(1)

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
    if (props.brands) {
        brands.value = props.brands.data || []
        pagination.value = props.brands
        currentPage.value = props.brands.current_page || 1
    }
}

// Call init on mount
initData()

// Watch for prop changes
watch(() => props.brands, () => {
    initData()
}, { deep: true })

const filteredBrands = computed(() => {
    if (!searchQuery.value) return brands.value
    return brands.value.filter(brand => 
        brand.name.toLowerCase().includes(searchQuery.value.toLowerCase()) 
    )
})

const totalPages = computed(() => {
    return pagination.value.last_page || 1
})

const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++
        router.get(route('brands.index', { page: currentPage.value }), {}, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: (pageData) => {
                brands.value = pageData.props.brands?.data || []
                pagination.value = pageData.props.brands || {}
            }
        })
    }
}

const prevPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--
        router.get(route('brands.index', { page: currentPage.value }), {}, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: (pageData) => {
                brands.value = pageData.props.brands?.data || []
                pagination.value = pageData.props.brands || {}
            }
        })
    }
}

// Modal visibility
const isModalVisible = ref(false);
const isDeleteModalVisible = ref(false);
const brandToDelete = ref(null);
const previewImage = ref(null);
const isProcessing = ref(false);
const isEditMode = ref(false);

// Form data
const formData = ref({
    id: null,
    name: '',
    description: '',
    logo: null,
    is_active: true,
    existing_logo: null
})

// Helper function to get image URL
const getImageUrl = (logo) => {
    if (!logo) return null;
    if (logo.startsWith('http')) return logo;
    if (logo.startsWith('/storage')) return logo;
    return `/storage/${logo}`;
};

const resetForm = () => {
    formData.value = {
        id: null,
        name: '',
        description: '',
        logo: null,
        is_active: true,
        existing_logo: null
    }
    previewImage.value = null
    isEditMode.value = false
}

const openAddModal = () => {
    resetForm()
    isModalVisible.value = true
}

const editBrand = (brand) => {
    resetForm()
    formData.value = {
        id: brand.id,
        name: brand.name,
        description: brand.description || '',
        logo: null,
        is_active: brand.is_active,
        existing_logo: brand.logo
    }
    previewImage.value = brand.logo ? getImageUrl(brand.logo) : null
    isEditMode.value = true
    isModalVisible.value = true
}

const handleImageUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            showNotification('Only JPEG, PNG, JPG, GIF images are allowed', 'error');
            return;
        }
        
        if (file.size > 2048 * 1024) {
            showNotification('Image size must be less than 2MB', 'error');
            return;
        }
        
        formData.value.logo = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            previewImage.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const removeImage = () => {
    previewImage.value = null;
    formData.value.logo = null;
    formData.value.existing_logo = null;
};

const submitBrand = async () => {
    isProcessing.value = true;
    
    const submitData = new FormData();
    submitData.append('name', formData.value.name);
    submitData.append('description', formData.value.description || '');
    submitData.append('is_active', formData.value.is_active ? '1' : '0');
    
    if (formData.value.logo instanceof File) {
        submitData.append('logo', formData.value.logo);
    }
    
    if (isEditMode.value) {
        submitData.append('_method', 'PUT');
        
        router.post(route('brands.update', formData.value.id), submitData, {
            onSuccess: (response) => {
                isModalVisible.value = false;
                
                // Get the updated data from response
                const updatedBrand = response.props.brands?.data?.find(b => b.id === formData.value.id)
                
                if (updatedBrand) {
                    // Update in brands array
                    const index = brands.value.findIndex(b => b.id === formData.value.id)
                    if (index !== -1) {
                        brands.value[index] = updatedBrand
                    }
                    
                    // Update in pagination data
                    if (pagination.value.data) {
                        const pIndex = pagination.value.data.findIndex(b => b.id === formData.value.id)
                        if (pIndex !== -1) {
                            pagination.value.data[pIndex] = updatedBrand
                        }
                    }
                } else {
                    // Manual update if response doesn't have the brand
                    const index = brands.value.findIndex(b => b.id === formData.value.id)
                    if (index !== -1) {
                        brands.value[index] = {
                            ...brands.value[index],
                            name: formData.value.name,
                            description: formData.value.description,
                            is_active: formData.value.is_active,
                            logo: previewImage.value ? previewImage.value : brands.value[index].logo
                        }
                    }
                }
                
                resetForm()
                showNotification('Brand updated successfully!', 'success');
            },
            onError: (errors) => {
                console.error('Update error:', errors);
                showNotification(Object.values(errors)[0] || 'Failed to update brand.', 'error');
            },
            onFinish: () => {
                isProcessing.value = false;
            }
        });
    } else {
        router.post(route('brands.store'), submitData, {
            onSuccess: (response) => {
                isModalVisible.value = false;
                
                // Get the newly created brand from response
                const newBrand = response.props.brands?.data?.[0]
                
                if (newBrand) {
                    // Add to beginning of brands array
                    brands.value = [newBrand, ...brands.value]
                    
                    // Update pagination data
                    if (pagination.value.data) {
                        pagination.value.data = [newBrand, ...pagination.value.data]
                    }
                }
                
                resetForm()
                showNotification('Brand created successfully!', 'success');
            },
            onError: (errors) => {
                showNotification(Object.values(errors)[0] || 'Failed to create brand.', 'error');
            },
            onFinish: () => {
                isProcessing.value = false;
            }
        });
    }
};

const closeModal = () => {
    isModalVisible.value = false;
    resetForm()
    isProcessing.value = false;
};

const confirmDelete = (brandId) => {
    brandToDelete.value = brandId;
    isDeleteModalVisible.value = true;
};

const deleteBrand = () => {
    if (!brandToDelete.value) return;
    
    router.delete(route('brands.destroy', brandToDelete.value), {
        onSuccess: () => {
            isDeleteModalVisible.value = false;
            
            // Remove from brands array
            brands.value = brands.value.filter(b => b.id !== brandToDelete.value);
            
            // Remove from pagination data
            if (pagination.value.data) {
                pagination.value.data = pagination.value.data.filter(b => b.id !== brandToDelete.value);
            }
            
            showNotification('Brand deleted successfully!', 'success');
            brandToDelete.value = null;
        },
        onError: (errors) => {
            isDeleteModalVisible.value = false;
            showNotification(errors.message || 'Failed to delete the brand.', 'error');
        },
    });
};

const closeDeleteModal = () => {
    isDeleteModalVisible.value = false;
    brandToDelete.value = null;
};

const toggleStatus = (brand) => {
    router.put(route('brands.toggle-status', brand.id), {}, {
        onSuccess: (response) => {
            // Get updated brand from response
            const updatedBrand = response.props.brands?.data?.find(b => b.id === brand.id)
            
            if (updatedBrand) {
                // Update in brands array
                const index = brands.value.findIndex(b => b.id === brand.id);
                if (index !== -1) {
                    brands.value[index] = updatedBrand;
                }
                
                // Update in pagination data
                if (pagination.value.data) {
                    const pIndex = pagination.value.data.findIndex(b => b.id === brand.id);
                    if (pIndex !== -1) {
                        pagination.value.data[pIndex] = updatedBrand;
                    }
                }
            } else {
                // Manual toggle if response doesn't have the brand
                const index = brands.value.findIndex(b => b.id === brand.id);
                if (index !== -1) {
                    brands.value[index].is_active = !brands.value[index].is_active;
                }
            }
            
            showNotification('Brand status updated!', 'success');
        },
        onError: (errors) => {
            showNotification('Failed to update status.', 'error');
        }
    });
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const handleImageError = (event) => {
    event.target.style.display = 'none';
    if (event.target.nextElementSibling) {
        event.target.nextElementSibling.style.display = 'flex';
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Brand Management" />
        
        <!-- Custom Notification Toast -->
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
                <div class="flex-shrink-0">
                    <i 
                        :class="[
                            'text-white text-lg',
                            notification.type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle'
                        ]"
                    ></i>
                </div>
                <div class="flex-1">
                    <p class="text-white font-medium text-sm">{{ notification.message }}</p>
                </div>
                <button 
                    @click="notification.show = false" 
                    class="flex-shrink-0 text-white hover:text-gray-200 transition-colors"
                >
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
        </Transition>
        
        <!-- Main Content Area -->
        <div class="min-h-screen w-full bg-gradient-to-br from-gray-50 to-blue-50 px-4 sm:px-6 lg:px-8 py-8">
            <div class="max-w-7xl mx-auto">
                <!-- Header Section -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Brand Management</h1>
                    <p class="mt-2 text-gray-600">Manage your product brands efficiently</p>
                </div>
                
                <!-- Action Bar -->
                <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="relative flex-1 max-w-md">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z"></path>
                                </svg>
                            </span>
                            <input 
                                v-model="searchQuery"
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                                type="text" 
                                placeholder="Search brands..."
                            >
                        </div>
                        <button 
                            @click="openAddModal" 
                            class="px-6 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-lg hover:from-indigo-600 hover:to-purple-700 transition-all duration-200 font-semibold shadow-md hover:shadow-lg"
                        >
                            <i class="fas fa-plus mr-2"></i>Add Brand
                        </button>
                    </div>
                </div>
                
                <!-- Brands Table -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-indigo-500 to-purple-600">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-wider">#</th>
                                    <th scope="col" class="px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-wider">Brand</th>
                                    <th scope="col" class="px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-wider">Description</th>
                                    <th scope="col" class="px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-wider">Added Date</th>
                                    <th scope="col" class="px-6 py-4 text-right text-sm font-bold text-white uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="filteredBrands.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        <i class="fas fa-building text-4xl mb-3"></i>
                                        <p class="text-lg">No brands found</p>
                                    </td>
                                </tr>
                                <tr 
                                    v-for="(brand, index) in filteredBrands" 
                                    :key="brand.id" 
                                    class="hover:bg-indigo-50 transition-colors duration-150"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ index + 1 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img 
                                    v-if="brand.logo" 
                                    :src="getImageUrl(brand.logo)"
                                    :alt="brand.name"
                                    class="h-10 w-10 rounded-lg object-cover"
                                    @error="handleImageError"
                                >
                                                <div 
                                    v-else
                                    class="h-10 w-10 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center"
                                >
                                                    <i class="fas fa-building text-white text-sm"></i>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ brand.name }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <div class="max-w-xs truncate">
                                            {{ brand.description || 'No description' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span 
                                            :class="[
                                                'px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full',
                                                brand.is_active 
                                                    ? 'bg-green-100 text-green-800' 
                                                    : 'bg-red-100 text-red-800'
                                            ]"
                                        >
                                            {{ brand.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <i class="far fa-calendar-alt mr-1"></i>
                                        {{ formatDate(brand.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <button 
                                                @click="toggleStatus(brand)"
                                                :class="[
                                                    'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200',
                                                    brand.is_active 
                                                        ? 'bg-yellow-50 text-yellow-600 hover:bg-yellow-100' 
                                                        : 'bg-green-50 text-green-600 hover:bg-green-100'
                                                ]"
                                            >
                                                <i :class="brand.is_active ? 'fas fa-toggle-on' : 'fas fa-toggle-off'" class="mr-1"></i>
                                                {{ brand.is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                            <button 
                                                @click="editBrand(brand)"
                                                class="px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg text-xs font-semibold transition-all duration-200"
                                            >
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </button>
                                            <button 
                                                @click="confirmDelete(brand.id)"
                                                class="px-3 py-1.5 bg-red-50 text-red-600 hover:bg-red-100 rounded-lg text-xs font-semibold transition-all duration-200"
                                            >
                                                <i class="fas fa-trash mr-1"></i>Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div v-if="totalPages > 1" class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing page 
                                    <span class="font-medium">{{ currentPage }}</span> of 
                                    <span class="font-medium">{{ totalPages }}</span>
                                </p>
                            </div>
                            <div class="flex space-x-2">
                                <button 
                                    @click="prevPage" 
                                    :disabled="currentPage === 1"
                                    class="px-4 py-2 border border-gray-300 rounded-lg bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                                >
                                    <i class="fas fa-chevron-left mr-1"></i>Previous
                                </button>
                                <button 
                                    @click="nextPage" 
                                    :disabled="currentPage === totalPages"
                                    class="px-4 py-2 border border-gray-300 rounded-lg bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                                >
                                    Next<i class="fas fa-chevron-right ml-1"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Brand Modal -->
        <div v-if="isModalVisible" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-900 opacity-50"></div>
                </div>
                
                <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-2xl font-bold text-gray-900">
                            {{ isEditMode ? 'Edit Brand' : 'Add New Brand' }}
                        </h3>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <form @submit.prevent="submitBrand" class="space-y-6">
                        <!-- Brand Logo Upload -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Brand Logo (Optional)</label>
                            <div v-if="previewImage" class="relative inline-block mb-3">
                                <img :src="previewImage" class="w-24 h-24 rounded-lg object-cover border-2 border-gray-200">
                                <button 
                                    type="button"
                                    @click="removeImage"
                                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 transition"
                                >
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div v-else class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-indigo-500 transition-colors">
                                <input
                                    type="file"
                                    @change="handleImageUpload"
                                    accept="image/*"
                                    class="hidden"
                                    id="logo-upload"
                                >
                                <label for="logo-upload" class="cursor-pointer">
                                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                    <p class="text-sm text-gray-600">Click to upload logo</p>
                                    <p class="text-xs text-gray-500 mt-1">PNG, JPG, GIF up to 2MB</p>
                                </label>
                            </div>
                            <p v-if="$page.props.errors?.logo" class="mt-1 text-sm text-red-600">{{ $page.props.errors.logo }}</p>
                        </div>
                        
                        <!-- Brand Name -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Brand Name *</label>
                            <input
                                v-model="formData.name"
                                type="text"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                                placeholder="Enter brand name"
                            >
                            <p v-if="$page.props.errors?.name" class="mt-1 text-sm text-red-600">{{ $page.props.errors.name }}</p>
                        </div>
                        
                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                            <textarea
                                v-model="formData.description"
                                rows="4"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                                placeholder="Enter brand description (optional)"
                            ></textarea>
                            <p v-if="$page.props.errors?.description" class="mt-1 text-sm text-red-600">{{ $page.props.errors.description }}</p>
                        </div>
                        
                        <!-- Active Status -->
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-semibold text-gray-700">Active Status</label>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    v-model="formData.is_active" 
                                    class="sr-only peer"
                                >
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                            <button
                                type="button"
                                @click="closeModal"
                                class="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="isProcessing"
                                class="px-6 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                            >
                                <i v-if="isProcessing" class="fas fa-spinner fa-spin mr-2"></i>
                                {{ isEditMode ? 'Update Brand' : 'Create Brand' }}
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
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Delete Brand</h3>
                        <p class="text-gray-600 mb-6">
                            Are you sure you want to delete this brand? This action cannot be undone.
                        </p>
                        
                        <div class="flex justify-center space-x-3">
                            <button
                                @click="closeDeleteModal"
                                class="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-all duration-200"
                            >
                                Cancel
                            </button>
                            <button
                                @click="deleteBrand"
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
/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Smooth transitions */
* {
    transition: all 0.2s ease-in-out;
}

/* Focus styles */
input:focus, textarea:focus, select:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}
</style>