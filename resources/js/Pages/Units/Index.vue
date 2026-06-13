<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"
import { Head, Link, usePage, useForm } from "@inertiajs/vue3";
import { ref, computed } from 'vue'
import { ElMessage, ElMessageBox, ElButton } from "element-plus";

const { props } = usePage()
const units = ref(props.units?.data || [])
const pagination = ref(props.units || {})

const currentPage = ref(props.units?.current_page || 1)
const searchQuery = ref('')

const filteredunits = computed(() => {
  if (!searchQuery.value) return units.value
  return units.value.filter(unit => 
    unit.name.toLowerCase().includes(searchQuery.value.toLowerCase()) 
  )
})

const totalPages = computed(() => {
  return pagination.value.last_page || 1
})

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++
  }
}

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
  }
}

defineProps({ 
    errors: Object 
})

// Modal visibility
const isModalVisible = ref(false);
const isDeleteModalVisible = ref(false);
const unitToDelete = ref(null);

// Form to add/edit unit
const unitForm = useForm({
    id: null,
    name: '',
    short_name: '',
    is_active: true
});

const openAddModal = () => {
    unitForm.reset();
    unitForm.id = null;
    isModalVisible.value = true;
};

const editunit = (unit) => {
    unitForm.id = unit.id;
    unitForm.name = unit.name;
    unitForm.short_name = unit.short_name || '';
    unitForm.is_active = unit.is_active;
    isModalVisible.value = true;
};

const submitunit = () => {
    const isUpdate = Boolean(unitForm.id);
    const routeName = isUpdate ? 'units.update' : 'units.store';
    const routeParams = isUpdate ? unitForm.id : null;
    const method = isUpdate ? 'put' : 'post';

    unitForm[method](route(routeName, routeParams), {
        onSuccess: (page) => {
            isModalVisible.value = false;
            units.value = page.props.units?.data || [];
            pagination.value = page.props.units || {};
            unitForm.reset();
            ElMessage.success({
                message: isUpdate ? "unit updated successfully!" : "unit created successfully!",
                customClass: 'success-message',
                duration: 3000
            });
        },
        onError: (errors) => {
            ElMessage.error({
                message: Object.values(errors)[0] || "Failed to submit the data. Please try again.",
                customClass: 'error-message',
                duration: 3000
            });
        }
    });
};

const closeModal = () => {
    isModalVisible.value = false;
    unitForm.reset();
};

const confirmDelete = (unitId) => {
    unitToDelete.value = unitId;
    isDeleteModalVisible.value = true;
};

const deleteunit = () => {
    if (!unitToDelete.value) return;
    
    unitForm.delete(route('units.destroy', unitToDelete.value), {
        onSuccess: (page) => {
            isDeleteModalVisible.value = false;
            units.value = page.props.units?.data || [];
            pagination.value = page.props.units || {};
            ElMessage.success({
                message: "unit deleted successfully!",
                customClass: 'success-message',
                duration: 3000
            });
        },
        onError: (errors) => {
            isDeleteModalVisible.value = false;
            ElMessage.error({
                message: errors.message || "Failed to delete the unit.",
                customClass: 'error-message',
                duration: 3000
            });
        },
    });
};

const closeDeleteModal = () => {
    isDeleteModalVisible.value = false;
    unitToDelete.value = null;
};

const toggleStatus = (unit) => {
    unitForm.put(route('units.toggle-status', unit.id), {
        onSuccess: (page) => {
            units.value = page.props.units?.data || [];
            ElMessage.success({
                message: "unit status updated!",
                customClass: 'success-message',
                duration: 3000
            });
        }
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Unit Management" />
        
        <!-- Flash Messages -->
        <div v-if="$page.props.flash?.success" class="fixed top-6 right-6 z-50 animate-fade-in">
            <div class="bg-emerald-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                {{ $page.props.flash.success }}
            </div>
        </div>
        
        <div v-if="$page.props.flash?.error" class="fixed top-6 right-6 z-50 animate-fade-in">
            <div class="bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                {{ $page.props.flash.error }}
            </div>
        </div>
        
        <!-- Main Content Area -->
        <div class="min-h-screen w-full bg-gradient-to-br from-gray-50 to-blue-50 px-4 sm:px-6 lg:px-8 py-8">
            <div class="max-w-7xl mx-auto">
                <!-- Header Section -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Unit Management</h1>
                    <p class="mt-2 text-gray-600">Manage your product units efficiently</p>
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
                                placeholder="Search units..."
                            >
                        </div>
                        <el-button 
                            @click="openAddModal" 
                            type="primary" 
                            size="large"
                            class="!rounded-lg !px-6 !py-2.5 !font-semibold"
                        >
                            <i class="fas fa-plus mr-2"></i>Add unit
                        </el-button>
                    </div>
                </div>
                
                <!-- units Table -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-indigo-500 to-purple-600">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-wider">
                                        #
                                    </th>
                                    <th scope="col" class="px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-wider">
                                        Name
                                    </th>
                                    
                                    <th scope="col" class="px-6 py-4 text-left text-sm font-bold text-white uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th scope="col" class="px-6 py-4 text-right text-sm font-bold text-white uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="filteredunits.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                        <i class="fas fa-folder-open text-4xl mb-3"></i>
                                        <p class="text-lg">No units found</p>
                                    </td>
                                </tr>
                                <tr 
                                    v-for="(unit, index) in filteredunits" 
                                    :key="unit.id" 
                                    class="hover:bg-indigo-50 transition-colors duration-150"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ index + 1 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                                                    <i class="fas fa-folder text-white"></i>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ unit.name }}</div>
                                                <div v-if="unit.short_name" class="text-sm text-gray-500">{{ unit.short_name }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span 
                                            :class="[
                                                'px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full',
                                                unit.is_active 
                                                    ? 'bg-green-100 text-green-800' 
                                                    : 'bg-red-100 text-red-800'
                                            ]"
                                        >
                                            {{ unit.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end space-x-2">
                                            <button 
                                                @click="toggleStatus(unit)"
                                                :class="[
                                                    'px-3 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200',
                                                    unit.is_active 
                                                        ? 'bg-yellow-50 text-yellow-600 hover:bg-yellow-100' 
                                                        : 'bg-green-50 text-green-600 hover:bg-green-100'
                                                ]"
                                            >
                                                <i :class="unit.is_active ? 'fas fa-toggle-on' : 'fas fa-toggle-off'" class="mr-1"></i>
                                                {{ unit.is_active ? 'Deactivate' : 'Activate' }}
                                            </button>

                                            <button 
                                                @click="editunit(unit)"
                                                class="px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg text-xs font-semibold transition-all duration-200"
                                            >
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </button>
                                            <button 
                                                @click="confirmDelete(unit.id)"
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

        <!-- Add/Edit unit Modal -->
        <div v-if="isModalVisible" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeModal">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-900 opacity-50"></div>
                </div>
                
                <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-2xl">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-2xl font-bold text-gray-900">
                            {{ unitForm.id ? 'Edit unit' : 'Add New unit' }}
                        </h3>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <form @submit.prevent="submitunit" class="space-y-6">
                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Unit Name *</label>
                            <input
                                v-model="unitForm.name"
                                type="text"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                                placeholder="Enter unit name (পিস, কেজি, লিটার, মিটার)"
                            >
                            <p v-if="errors?.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
                        </div>
                        
                        <!-- short_name -->
                  
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2"> Short Name *</label>
                            <input
                                v-model="unitForm.short_name"
                                type="text"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200"
                                placeholder="Enter unit short name (pc, kg, ltr, m)"
                            >
                            <p v-if="errors?.short_name" class="mt-1 text-sm text-red-600">{{ errors.short_name }}</p>
                        </div>
                        
                        <!-- Active Status -->
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-semibold text-gray-700">Active Status</label>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    v-model="unitForm.is_active" 
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
                                :disabled="unitForm.processing"
                                class="px-6 py-2.5 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                            >
                                <i v-if="unitForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
                                {{ unitForm.id ? 'Update unit' : 'Create unit' }}
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
                        <h3 class="text-xl font-bold text-gray-900 mb-2">Delete unit</h3>
                        <p class="text-gray-600 mb-6">
                            Are you sure you want to delete this unit? This action cannot be undone.
                        </p>
                        
                        <div class="flex justify-center space-x-3">
                            <button
                                @click="closeDeleteModal"
                                class="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-all duration-200"
                            >
                                Cancel
                            </button>
                            <button
                                @click="deleteunit"
                                :disabled="unitForm.processing"
                                class="px-6 py-2.5 border border-transparent rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                            >
                                <i v-if="unitForm.processing" class="fas fa-spinner fa-spin mr-2"></i>
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

.animate-fade-in {
    animation: fadeIn 0.3s ease-out;
}

/* Element Plus custom styles */
:deep(.el-button--primary) {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border: none !important;
}

:deep(.el-button--primary:hover) {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%) !important;
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