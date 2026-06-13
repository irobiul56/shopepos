<script setup>
import { ref, reactive, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import { ElMessage } from 'element-plus'

// Props from controller
const props = defineProps({
    categories: Array,
    brands: Array,
    units: Array,
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

// Form data
const form = useForm({
    name: '',
    sku: '',
    barcode: '',
    category_id: '',
    brand_id: '',
    unit_id: '',
    purchase_price: '',
    selling_price: '',
    wholesale_price: '',
    stock_quantity: '',
    min_stock_alert: 5,
    tax_rate: 0,
    description: '',
    is_active: true,
    is_taxable: true,
    product_image: null,
    gallery_images: []
})

// Image previews
const mainImagePreview = ref(null)
const galleryPreviews = ref([])

// Generate SKU automatically
const generateSKU = () => {
    const prefix = form.name.substring(0, 3).toUpperCase()
    const random = Math.floor(Math.random() * 10000)
    form.sku = `${prefix}-${random}`
}

// Watch product name to generate SKU
const watchName = () => {
    if (form.name && !form.sku) {
        generateSKU()
    }
}

// Main image upload handler
const handleMainImageUpload = (event) => {
    const file = event.target.files[0]
    if (file) {
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg']
        if (!allowedTypes.includes(file.type)) {
            showNotification('শুধুমাত্র JPEG, PNG, JPG ফরম্যাট সাপোর্টেড', 'error')
            return
        }
        
        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            showNotification('ছবির সাইজ ৫MB এর কম হতে হবে', 'error')
            return
        }
        
        form.product_image = file
        
        // Create preview
        const reader = new FileReader()
        reader.onload = (e) => {
            mainImagePreview.value = e.target.result
        }
        reader.readAsDataURL(file)
    }
}

// Gallery images upload handler
const handleGalleryUpload = (event) => {
    const files = Array.from(event.target.files)
    
    files.forEach(file => {
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg']
        if (!allowedTypes.includes(file.type)) {
            showNotification('শুধুমাত্র JPEG, PNG, JPG ফরম্যাট সাপোর্টেড', 'error')
            return
        }
        
        // Validate file size (max 5MB each)
        if (file.size > 5 * 1024 * 1024) {
            showNotification('ছবির সাইজ ৫MB এর কম হতে হবে', 'error')
            return
        }
        
        form.gallery_images.push(file)
        
        // Create preview
        const reader = new FileReader()
        reader.onload = (e) => {
            galleryPreviews.value.push({
                url: e.target.result,
                file: file
            })
        }
        reader.readAsDataURL(file)
    })
}

// Remove gallery image
const removeGalleryImage = (index) => {
    form.gallery_images.splice(index, 1)
    galleryPreviews.value.splice(index, 1)
}

// Remove main image
const removeMainImage = () => {
    form.product_image = null
    mainImagePreview.value = null
    document.getElementById('mainImage').value = ''
}

// Calculate selling price with tax
const calculateSellingPriceWithTax = computed(() => {
    if (!form.selling_price) return 0
    if (!form.is_taxable) return form.selling_price
    
    const taxAmount = (form.selling_price * form.tax_rate) / 100
    return parseFloat(form.selling_price) + taxAmount
})

// Submit form
const submitProduct = () => {
    // Validation
    if (!form.name) {
        showNotification('পণ্যের নাম দিন', 'error')
        return
    }
    if (!form.sku) {
        showNotification('SKU দিন', 'error')
        return
    }
    if (!form.category_id) {
        showNotification('ক্যাটেগরি নির্বাচন করুন', 'error')
        return
    }
    if (!form.unit_id) {
        showNotification('ইউনিট নির্বাচন করুন', 'error')
        return
    }
    if (!form.purchase_price) {
        showNotification('ক্রয় মূল্য দিন', 'error')
        return
    }
    if (!form.selling_price) {
        showNotification('বিক্রয় মূল্য দিন', 'error')
        return
    }
    if (!form.stock_quantity) {
        showNotification('প্রাথমিক স্টক দিন', 'error')
        return
    }
    
    // Create FormData for file upload
    const formData = new FormData()
    formData.append('name', form.name)
    formData.append('sku', form.sku)
    formData.append('barcode', form.barcode)
    formData.append('category_id', form.category_id)
    formData.append('brand_id', form.brand_id || '')
    formData.append('unit_id', form.unit_id)
    formData.append('purchase_price', form.purchase_price)
    formData.append('selling_price', form.selling_price)
    formData.append('wholesale_price', form.wholesale_price || 0)
    formData.append('stock_quantity', form.stock_quantity)
    formData.append('min_stock_alert', form.min_stock_alert)
    formData.append('tax_rate', form.tax_rate)
    formData.append('description', form.description)
    formData.append('is_active', form.is_active ? 1 : 0)
    formData.append('is_taxable', form.is_taxable ? 1 : 0)
    
    if (form.product_image) {
        formData.append('product_image', form.product_image)
    }
    
    form.gallery_images.forEach((image, index) => {
        formData.append(`gallery_images[${index}]`, image)
    })
    
    // Submit using Inertia
    router.post(route('products.store'), formData, {
        onSuccess: () => {
            showNotification('পণ্য সফলভাবে সংরক্ষণ করা হয়েছে!', 'success')
            
            // Reset form
            form.reset()
            mainImagePreview.value = null
            galleryPreviews.value = []
            
            // Redirect to product list after 2 seconds
            setTimeout(() => {
                router.get(route('products.index'))
            }, 2000)
        },
        onError: (errors) => {
            const errorMessage = Object.values(errors)[0] || 'পণ্য সংরক্ষণে ব্যর্থ হয়েছে'
            showNotification(errorMessage, 'error')
        }
    })
}

// Reset form
const resetForm = () => {
    form.reset()
    mainImagePreview.value = null
    galleryPreviews.value = []
    form.min_stock_alert = 5
    form.tax_rate = 0
    form.is_active = true
    form.is_taxable = true
    document.getElementById('mainImage').value = ''
    document.getElementById('galleryImages').value = ''
    showNotification('ফর্ম রিসেট করা হয়েছে', 'success')
}

// Go back
const goBack = () => {
    router.get(route('products.index'))
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="নতুন পণ্য যোগ করুন" />
        
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
        
        <div class="container mx-auto px-4 py-8 max-w-7xl">
            <!-- Header Section -->
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                            <i class="fas fa-boxes text-blue-600"></i>
                            নতুন পণ্য যোগ করুন
                        </h1>
                        <p class="text-gray-600 mt-2">পণ্যের সম্পূর্ণ বিবরণ দিন</p>
                    </div>
                    <button 
                        @click="goBack" 
                        class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg transition duration-200 flex items-center gap-2"
                    >
                        <i class="fas fa-arrow-left"></i>
                        ফিরে যান
                    </button>
                </div>
            </div>

            <!-- Main Form Card -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
                    <h2 class="text-xl font-semibold text-white flex items-center gap-2">
                        <i class="fas fa-plus-circle"></i>
                        পণ্যের তথ্য
                    </h2>
                </div>
                
                <form @submit.prevent="submitProduct" class="p-6 space-y-8">
                    <!-- Basic Information Section -->
                    <div class="border-b border-gray-200 pb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-info-circle text-blue-600"></i>
                            মৌলিক তথ্য
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Product Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    পণ্যের নাম <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    v-model="form.name"
                                    @input="watchName"
                                    type="text" 
                                    required 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                                    placeholder="যেমন: ব্র্যান্ডেড চাল ৫ কেজি"
                                >
                                <p v-if="errors?.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
                            </div>
                            
                            <!-- SKU -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    SKU <span class="text-red-500">*</span>
                                </label>
                                <div class="flex gap-2">
                                    <input 
                                        v-model="form.sku"
                                        type="text" 
                                        required 
                                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                                        placeholder="স্টক কিপিং ইউনিট"
                                    >
                                    <button 
                                        type="button"
                                        @click="generateSKU"
                                        class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition"
                                    >
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                                <p v-if="errors?.sku" class="mt-1 text-sm text-red-600">{{ errors.sku }}</p>
                            </div>
                            
                            <!-- Barcode -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    বারকোড
                                </label>
                                <input 
                                    v-model="form.barcode"
                                    type="text" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                                    placeholder="বারকোড নম্বর"
                                >
                            </div>
                            
                            <!-- Category -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    ক্যাটেগরি <span class="text-red-500">*</span>
                                </label>
                                <select 
                                    v-model="form.category_id"
                                    required 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                                >
                                    <option value="">ক্যাটেগরি নির্বাচন করুন</option>
                                    <option v-for="category in categories" :key="category.id" :value="category.id">
                                        {{ category.name }}
                                    </option>
                                </select>
                                <p v-if="errors?.category_id" class="mt-1 text-sm text-red-600">{{ errors.category_id }}</p>
                            </div>
                            
                            <!-- Brand -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    ব্র্যান্ড
                                </label>
                                <select 
                                    v-model="form.brand_id"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                                >
                                    <option value="">ব্র্যান্ড নির্বাচন করুন</option>
                                    <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                                        {{ brand.name }}
                                    </option>
                                </select>
                            </div>
                            
                            <!-- Unit -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    ইউনিট <span class="text-red-500">*</span>
                                </label>
                                <select 
                                    v-model="form.unit_id"
                                    required 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                                >
                                    <option value="">ইউনিট নির্বাচন করুন</option>
                                    <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                        {{ unit.name }} ({{ unit.short_name }})
                                    </option>
                                </select>
                                <p v-if="errors?.unit_id" class="mt-1 text-sm text-red-600">{{ errors.unit_id }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pricing Section -->
                    <div class="border-b border-gray-200 pb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-tags text-green-600"></i>
                            মূল্য ও স্টক
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Purchase Price -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    ক্রয় মূল্য <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2 text-gray-500">৳</span>
                                    <input 
                                        v-model.number="form.purchase_price"
                                        type="number" 
                                        step="0.01" 
                                        required 
                                        class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                                        placeholder="0.00"
                                    >
                                </div>
                                <p v-if="errors?.purchase_price" class="mt-1 text-sm text-red-600">{{ errors.purchase_price }}</p>
                            </div>
                            
                            <!-- Selling Price -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    বিক্রয় মূল্য <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2 text-gray-500">৳</span>
                                    <input 
                                        v-model.number="form.selling_price"
                                        type="number" 
                                        step="0.01" 
                                        required 
                                        class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                                        placeholder="0.00"
                                    >
                                </div>
                                <p v-if="form.is_taxable && form.tax_rate > 0" class="mt-1 text-xs text-green-600">
                                    ট্যাক্সসহ মূল্য: ৳ {{ calculateSellingPriceWithTax.toFixed(2) }}
                                </p>
                                <p v-if="errors?.selling_price" class="mt-1 text-sm text-red-600">{{ errors.selling_price }}</p>
                            </div>
                            
                            <!-- Wholesale Price -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    পাইকারি মূল্য
                                </label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2 text-gray-500">৳</span>
                                    <input 
                                        v-model.number="form.wholesale_price"
                                        type="number" 
                                        step="0.01" 
                                        class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                                        placeholder="0.00"
                                    >
                                </div>
                            </div>
                            
                            <!-- Stock Quantity -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    প্রাথমিক স্টক <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    v-model.number="form.stock_quantity"
                                    type="number" 
                                    required 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                                    placeholder="পণ্যের পরিমাণ"
                                >
                                <p v-if="errors?.stock_quantity" class="mt-1 text-sm text-red-600">{{ errors.stock_quantity }}</p>
                            </div>
                            
                            <!-- Min Stock Alert -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    নূন্যতম স্টক সতর্কতা
                                </label>
                                <input 
                                    v-model.number="form.min_stock_alert"
                                    type="number" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                                    placeholder="সতর্কতা সংখ্যা"
                                >
                            </div>
                            
                            <!-- Tax Rate -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    ট্যাক্স/ভ্যাট হার (%)
                                </label>
                                <input 
                                    v-model.number="form.tax_rate"
                                    type="number" 
                                    step="0.01" 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                                    placeholder="ট্যাক্স হার"
                                >
                            </div>
                        </div>
                    </div>
                    
                    <!-- Description Section -->
                    <div class="border-b border-gray-200 pb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-align-left text-purple-600"></i>
                            বিবরণ
                        </h3>
                        <div>
                            <textarea 
                                v-model="form.description"
                                rows="4" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                                placeholder="পণ্যের বিস্তারিত বিবরণ দিন..."
                            ></textarea>
                        </div>
                    </div>
                    
                    <!-- Images Section -->
                    <div class="border-b border-gray-200 pb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-image text-yellow-600"></i>
                            পণ্যের ছবি
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Main Image -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    প্রধান ছবি
                                </label>
                                <div 
                                    class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-blue-500 transition cursor-pointer"
                                    @dragover.prevent
                                    @drop.prevent="handleMainImageUpload($event.dataTransfer.files[0])"
                                >
                                    <div class="space-y-1 text-center">
                                        <i class="fas fa-cloud-upload-alt text-4xl text-gray-400"></i>
                                        <div class="flex text-sm text-gray-600">
                                            <label class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500">
                                                <span>ছবি আপলোড করুন</span>
                                                <input type="file" class="sr-only" accept="image/*" id="mainImage" @change="handleMainImageUpload">
                                            </label>
                                        </div>
                                        <p class="text-xs text-gray-500">PNG, JPG, JPEG পর্যন্ত 5MB</p>
                                    </div>
                                </div>
                                <div v-if="mainImagePreview" class="mt-3 relative inline-block">
                                    <img :src="mainImagePreview" alt="Preview" class="h-32 w-32 object-cover rounded-lg shadow-md">
                                    <button 
                                        type="button"
                                        @click="removeMainImage"
                                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 transition"
                                    >
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Gallery Images -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    গ্যালারি ছবি (একাধিক)
                                </label>
                                <div 
                                    class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-blue-500 transition cursor-pointer"
                                    @dragover.prevent
                                >
                                    <div class="space-y-1 text-center">
                                        <i class="fas fa-images text-4xl text-gray-400"></i>
                                        <div class="flex text-sm text-gray-600">
                                            <label class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500">
                                                <span>ছবি আপলোড করুন</span>
                                                <input type="file" class="sr-only" accept="image/*" multiple id="galleryImages" @change="handleGalleryUpload">
                                            </label>
                                        </div>
                                        <p class="text-xs text-gray-500">একাধিক ছবি আপলোড করতে পারেন</p>
                                    </div>
                                </div>
                                <div class="mt-3 grid grid-cols-3 gap-2">
                                    <div v-for="(preview, index) in galleryPreviews" :key="index" class="relative group">
                                        <img :src="preview.url" alt="Gallery" class="h-24 w-full object-cover rounded-lg shadow-md">
                                        <button 
                                            type="button"
                                            @click="removeGalleryImage(index)"
                                            class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 text-xs opacity-0 group-hover:opacity-100 transition"
                                        >
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Additional Options -->
                    <div class="border-b border-gray-200 pb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-sliders-h text-indigo-600"></i>
                            অতিরিক্ত অপশন
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Is Active -->
                            <div class="flex items-center justify-between">
                                <label class="text-sm font-medium text-gray-700">
                                    পণ্য সক্রিয় করুন
                                </label>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                            
                            <!-- Is Taxable -->
                            <div class="flex items-center justify-between">
                                <label class="text-sm font-medium text-gray-700">
                                    ট্যাক্সযোগ্য
                                </label>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.is_taxable" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex justify-end gap-4 pt-4">
                        <button 
                            type="button"
                            @click="resetForm" 
                            class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-200"
                        >
                            <i class="fas fa-undo-alt mr-2"></i>
                            রিসেট
                        </button>
                        <button 
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 transition duration-200 shadow-md disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <i v-if="form.processing" class="fas fa-spinner fa-spin mr-2"></i>
                            <i v-else class="fas fa-save mr-2"></i>
                            পণ্য সংরক্ষণ করুন
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Smooth transitions */
* {
    transition: all 0.2s ease-in-out;
}

/* Focus styles */
input:focus, select:focus, textarea:focus {
    outline: none;
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>