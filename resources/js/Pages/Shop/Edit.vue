<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    shop: Object,
});

// Initialize form with all fields
const form = useForm({
    name: props.shop?.name || '',
    owner_name: props.shop?.owner_name || '',
    email: props.shop?.email || '',
    phone: props.shop?.phone || '',
    address: props.shop?.address || '',
    shop_type: props.shop?.shop_type || 'other',
    logo: null,
    tin_number: props.shop?.tin_number || '',
    currency_symbol: props.shop?.currency_symbol || '$',
    currency_code: props.shop?.currency_code || 'USD',
    is_active: props.shop?.is_active ?? true,
});

const previewLogo = ref(props.shop?.logo ? `/storage/${props.shop.logo}` : null);
const isUploading = ref(false);
const logoFile = ref(null);

const shopTypes = [
    { value: 'grocery', label: 'Grocery Store', icon: 'fa-store' },
    { value: 'clothing', label: 'Clothing Store', icon: 'fa-tshirt' },
    { value: 'pharmacy', label: 'Pharmacy', icon: 'fa-prescription-bottle' },
    { value: 'tailor', label: 'Tailor Shop', icon: 'fa-cut' },
    { value: 'electronics', label: 'Electronics Store', icon: 'fa-laptop' },
    { value: 'restaurant', label: 'Restaurant', icon: 'fa-utensils' },
    { value: 'other', label: 'Other', icon: 'fa-store-alt' },
];

const handleLogoUpload = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    if (!allowedTypes.includes(file.type)) {
        alert('Please upload a valid image file (JPG, PNG, GIF, WEBP, or SVG)');
        event.target.value = '';
        return;
    }

    // Validate file size (max 2MB)
    if (file.size > 2 * 1024 * 1024) {
        alert('File size should be less than 2MB');
        event.target.value = '';
        return;
    }

    isUploading.value = true;
    logoFile.value = file;
    
    // Set preview
    const reader = new FileReader();
    reader.onload = (e) => {
        previewLogo.value = e.target.result;
        isUploading.value = false;
    };
    reader.readAsDataURL(file);
    
    // Reset input so same file can be selected again
    event.target.value = '';
};

const removeLogo = () => {
    previewLogo.value = null;
    logoFile.value = null;
    form.logo = null;
};

const updateShop = () => {
    // Clear previous errors
    form.clearErrors();
    
    // Transform data for submission - এখানে PUT মেথড যোগ করা হচ্ছে
    form.transform((data) => {
        // Create FormData for file upload
        const formData = new FormData();
        
        // Add all form fields
        formData.append('name', data.name || '');
        formData.append('owner_name', data.owner_name || '');
        formData.append('email', data.email || '');
        formData.append('phone', data.phone || '');
        formData.append('address', data.address || '');
        formData.append('shop_type', data.shop_type || 'other');
        formData.append('tin_number', data.tin_number || '');
        formData.append('currency_symbol', data.currency_symbol || '$');
        formData.append('currency_code', data.currency_code || 'USD');
        formData.append('is_active', data.is_active ? '1' : '0');
        
        // Add logo if exists
        if (logoFile.value) {
            formData.append('logo', logoFile.value);
        }
        
        // Add _method for PUT
        formData.append('_method', 'PUT');
        
        return formData;
    });
    
    // Use post with FormData - Inertia automatically handles _method
    form.post(route('shop.update', props.shop.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            console.log('Shop updated successfully');
        },
        onError: (errors) => {
            console.error('Update error:', errors);
        },
    });
};

// Watch for form errors to display them properly
watch(() => form.errors, (newErrors) => {
    console.log('Form errors:', newErrors);
}, { deep: true });
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Shop Information" />
        
        <div class="min-h-screen w-full bg-gray-50 p-4">
            <div class="max-w-4xl mx-auto">
                <!-- Header -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-store mr-2 text-indigo-600"></i> Shop Information
                        </h1>
                        <p class="text-sm text-gray-500">Update your shop details and preferences</p>
                    </div>
                    <div class="flex gap-2">
                        <a :href="route('dashboard')" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition text-sm flex items-center gap-2">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <!-- Error Summary -->
                <div v-if="Object.keys(form.errors).length > 0" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                        <div>
                            <p class="text-sm font-medium text-red-800">Please fix the following errors:</p>
                            <ul class="mt-1 text-sm text-red-700 list-disc list-inside">
                                <li v-for="(error, field) in form.errors" :key="field">
                                    {{ field }}: {{ error }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <form @submit.prevent="updateShop" class="space-y-6">
                    <!-- Shop Logo Section -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="font-bold text-gray-700 text-sm mb-4">
                            <i class="fas fa-image mr-2 text-indigo-500"></i> Shop Logo
                        </h3>
                        <div class="flex items-center gap-6">
                            <div class="relative">
                                <div class="w-28 h-28 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden bg-gray-50">
                                    <img 
                                        v-if="previewLogo" 
                                        :src="previewLogo" 
                                        alt="Shop Logo" 
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="text-center text-gray-400">
                                        <i class="fas fa-store text-3xl block mb-1"></i>
                                        <span class="text-[10px]">No Logo</span>
                                    </div>
                                </div>
                                <div v-if="isUploading" class="absolute inset-0 bg-black/30 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-spinner fa-spin text-white text-2xl"></i>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex gap-2 flex-wrap">
                                    <label class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition text-sm cursor-pointer">
                                        <i class="fas fa-upload mr-1"></i> Upload Logo
                                        <input 
                                            type="file" 
                                            accept="image/*" 
                                            class="hidden" 
                                            @change="handleLogoUpload" 
                                            :disabled="form.processing"
                                        />
                                    </label>
                                    <button 
                                        v-if="previewLogo" 
                                        type="button"
                                        @click="removeLogo"
                                        :disabled="form.processing"
                                        class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition text-sm disabled:opacity-50"
                                    >
                                        <i class="fas fa-trash mr-1"></i> Remove
                                    </button>
                                </div>
                                <p class="text-[10px] text-gray-400">Recommended: Square image, max 2MB (JPG, PNG, GIF, WEBP, SVG)</p>
                                <p v-if="form.errors.logo" class="text-xs text-red-500">{{ form.errors.logo }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Basic Information -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="font-bold text-gray-700 text-sm mb-4">
                            <i class="fas fa-info-circle mr-2 text-indigo-500"></i> Basic Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Shop Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Shop Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                    :class="{ 'border-red-500': form.errors.name }"
                                    placeholder="Enter shop name"
                                    :disabled="form.processing"
                                />
                                <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                            </div>

                            <!-- Owner Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Owner Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.owner_name"
                                    type="text"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                    :class="{ 'border-red-500': form.errors.owner_name }"
                                    placeholder="Enter owner name"
                                    :disabled="form.processing"
                                />
                                <p v-if="form.errors.owner_name" class="text-xs text-red-500 mt-1">{{ form.errors.owner_name }}</p>
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Email Address <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                    :class="{ 'border-red-500': form.errors.email }"
                                    placeholder="Enter email address"
                                    :disabled="form.processing"
                                />
                                <p v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</p>
                            </div>

                            <!-- Phone -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Phone Number <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.phone"
                                    type="tel"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                    :class="{ 'border-red-500': form.errors.phone }"
                                    placeholder="Enter phone number"
                                    :disabled="form.processing"
                                />
                                <p v-if="form.errors.phone" class="text-xs text-red-500 mt-1">{{ form.errors.phone }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Address & Shop Type -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="font-bold text-gray-700 text-sm mb-4">
                            <i class="fas fa-location-dot mr-2 text-indigo-500"></i> Location & Type
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Address -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Address <span class="text-red-500">*</span>
                                </label>
                                <textarea
                                    v-model="form.address"
                                    rows="3"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                    :class="{ 'border-red-500': form.errors.address }"
                                    placeholder="Enter shop address"
                                    :disabled="form.processing"
                                ></textarea>
                                <p v-if="form.errors.address" class="text-xs text-red-500 mt-1">{{ form.errors.address }}</p>
                            </div>

                            <!-- Shop Type -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Shop Type <span class="text-red-500">*</span>
                                </label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        v-for="type in shopTypes"
                                        :key="type.value"
                                        type="button"
                                        @click="form.shop_type = type.value"
                                        :disabled="form.processing"
                                        :class="[
                                            'px-3 py-2 rounded-lg border text-sm transition flex items-center gap-2',
                                            form.shop_type === type.value
                                                ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                                : 'border-gray-300 hover:border-gray-400 hover:bg-gray-50'
                                        ]"
                                    >
                                        <i :class="['fas', type.icon, form.shop_type === type.value ? 'text-indigo-500' : 'text-gray-400']"></i>
                                        {{ type.label }}
                                    </button>
                                </div>
                                <p v-if="form.errors.shop_type" class="text-xs text-red-500 mt-1">{{ form.errors.shop_type }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- TIN & Currency -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="font-bold text-gray-700 text-sm mb-4">
                            <i class="fas fa-coins mr-2 text-indigo-500"></i> TIN & Currency Settings
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- TIN Number -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    TIN Number
                                </label>
                                <input
                                    v-model="form.tin_number"
                                    type="text"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                    :class="{ 'border-red-500': form.errors.tin_number }"
                                    placeholder="Enter TIN number (optional)"
                                    :disabled="form.processing"
                                />
                                <p v-if="form.errors.tin_number" class="text-xs text-red-500 mt-1">{{ form.errors.tin_number }}</p>
                            </div>

                            <!-- Currency -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Currency <span class="text-red-500">*</span>
                                </label>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Symbol</label>
                                        <select
                                            v-model="form.currency_symbol"
                                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                            :disabled="form.processing"
                                        >
                                            <option value="$">$ (Dollar)</option>
                                            <option value="Tk">Tk (Taka)</option>
                                            <option value="€">€ (Euro)</option>
                                            <option value="£">£ (Pound)</option>
                                            <option value="₹">₹ (Rupee)</option>
                                            <option value="₨">₨ (PKR)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Code</label>
                                        <select
                                            v-model="form.currency_code"
                                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                            :disabled="form.processing"
                                        >
                                            <option value="USD">USD</option>
                                            <option value="BDT">BDT</option>
                                            <option value="EUR">EUR</option>
                                            <option value="GBP">GBP</option>
                                            <option value="INR">INR</option>
                                            <option value="PKR">PKR</option>
                                        </select>
                                    </div>
                                </div>
                                <p v-if="form.errors.currency_symbol || form.errors.currency_code" class="text-xs text-red-500 mt-1">
                                    {{ form.errors.currency_symbol || form.errors.currency_code }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h3 class="font-bold text-gray-700 text-sm mb-4">
                            <i class="fas fa-toggle-on mr-2 text-indigo-500"></i> Shop Status
                        </h3>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    @click="form.is_active = true"
                                    :disabled="form.processing"
                                    :class="[
                                        'px-4 py-2 rounded-lg border text-sm transition flex items-center gap-2',
                                        form.is_active
                                            ? 'border-emerald-500 bg-emerald-50 text-emerald-700'
                                            : 'border-gray-300 text-gray-500 hover:bg-gray-50'
                                    ]"
                                >
                                    <i class="fas fa-check-circle"></i> Active
                                </button>
                                <button
                                    type="button"
                                    @click="form.is_active = false"
                                    :disabled="form.processing"
                                    :class="[
                                        'px-4 py-2 rounded-lg border text-sm transition flex items-center gap-2',
                                        !form.is_active
                                            ? 'border-red-500 bg-red-50 text-red-700'
                                            : 'border-gray-300 text-gray-500 hover:bg-gray-50'
                                    ]"
                                >
                                    <i class="fas fa-times-circle"></i> Inactive
                                </button>
                            </div>
                            <span class="text-xs text-gray-400">
                                {{ form.is_active ? 'Shop is currently active and visible' : 'Shop is currently inactive' }}
                            </span>
                        </div>
                    </div>

                    <!-- Preview Section -->
                    <div class="bg-white rounded-xl shadow-sm p-6 border-2 border-dashed border-indigo-200">
                        <h3 class="font-bold text-gray-700 text-sm mb-4">
                            <i class="fas fa-eye mr-2 text-indigo-500"></i> Preview
                        </h3>
                        <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg p-4">
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 rounded-lg bg-white shadow-sm flex items-center justify-center overflow-hidden">
                                    <img 
                                        v-if="previewLogo" 
                                        :src="previewLogo" 
                                        alt="Shop Logo" 
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="text-gray-400">
                                        <i class="fas fa-store text-2xl"></i>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800">{{ form.name || 'Shop Name' }}</h4>
                                    <p class="text-sm text-gray-600">{{ form.owner_name || 'Owner Name' }}</p>
                                    <div class="flex gap-3 text-xs text-gray-500 mt-1 flex-wrap">
                                        <span><i class="fas fa-envelope mr-1"></i> {{ form.email || 'email@example.com' }}</span>
                                        <span><i class="fas fa-phone mr-1"></i> {{ form.phone || '+880 1234 567890' }}</span>
                                        <span><i class="fas fa-tag mr-1"></i> {{ form.shop_type || 'other' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <a :href="route('dashboard')" class="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </a>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg hover:bg-indigo-700 transition flex items-center gap-2 disabled:opacity-50"
                        >
                            <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-save"></i>
                            {{ form.processing ? 'Saving...' : 'Update Shop' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>