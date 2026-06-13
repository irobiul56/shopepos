<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'

// Props from controller
const props = defineProps({
    product: Object,
    categories: Array,
    brands: Array,
    units: Array,
    errors: Object
})

// Helper function to get image URL (MUST BE DECLARED FIRST)
const getImageUrl = (image) => {
    if (!image) return null
    if (image.startsWith('http')) return image
    if (image.startsWith('/storage')) return image
    return `/storage/${image}`
}

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
    id: props.product.id,
    name: props.product.name,
    sku: props.product.sku,
    barcode: props.product.barcode || '',
    category_id: props.product.category_id,
    brand_id: props.product.brand_id || '',
    unit_id: props.product.unit_id,
    purchase_price: props.product.purchase_price,
    selling_price: props.product.selling_price,
    wholesale_price: props.product.wholesale_price || 0,
    stock_quantity: props.product.stock_quantity,
    min_stock_alert: props.product.min_stock_alert,
    tax_rate: props.product.tax_rate || 0,
    description: props.product.description || '',
    is_active: props.product.is_active,
    is_taxable: props.product.is_taxable,
    product_image: null,
    gallery_images: [],
    existing_product_image: props.product.product_image,
    existing_gallery_images: props.product.gallery_images || [],
    images_to_delete: []
})

// Image previews
const mainImagePreview = ref(props.product.product_image ? getImageUrl(props.product.product_image) : null)
const galleryPreviews = ref([])

// Initialize gallery previews
onMounted(() => {
    if (props.product.gallery_images && props.product.gallery_images.length > 0) {
        galleryPreviews.value = props.product.gallery_images.map(img => ({
            url: getImageUrl(img),
            path: img,
            is_new: false
        }))
    }
})

// Calculate selling price with tax
const calculateSellingPriceWithTax = computed(() => {
    if (!form.selling_price) return 0
    if (!form.is_taxable) return form.selling_price
    
    const taxAmount = (form.selling_price * form.tax_rate) / 100
    return parseFloat(form.selling_price) + taxAmount
})

// Stock status text
const stockStatusText = computed(() => {
    if (form.stock_quantity <= 0) return 'Out of Stock'
    if (form.stock_quantity <= form.min_stock_alert) return 'Low Stock'
    return 'In Stock'
})

const stockStatusClass = computed(() => {
    if (form.stock_quantity <= 0) return 'text-red-600 bg-red-50'
    if (form.stock_quantity <= form.min_stock_alert) return 'text-yellow-600 bg-yellow-50'
    return 'text-green-600 bg-green-50'
})

// Main image upload handler
const handleMainImageUpload = (event) => {
    const file = event.target.files[0]
    if (file) {
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg']
        if (!allowedTypes.includes(file.type)) {
            showNotification('Only JPEG, PNG, JPG formats are supported', 'error')
            return
        }
        
        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            showNotification('Image size must be less than 5MB', 'error')
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

// Remove main image
const removeMainImage = () => {
    if (confirm('Are you sure you want to remove the main image?')) {
        form.product_image = null
        form.existing_product_image = null
        mainImagePreview.value = null
        const mainImageInput = document.getElementById('mainImage')
        if (mainImageInput) mainImageInput.value = ''
    }
}

// Gallery images upload handler
const handleGalleryUpload = (event) => {
    const files = Array.from(event.target.files)
    
    files.forEach(file => {
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg']
        if (!allowedTypes.includes(file.type)) {
            showNotification('Only JPEG, PNG, JPG formats are supported', 'error')
            return
        }
        
        // Validate file size (max 5MB each)
        if (file.size > 5 * 1024 * 1024) {
            showNotification('Image size must be less than 5MB', 'error')
            return
        }
        
        form.gallery_images.push(file)
        
        // Create preview
        const reader = new FileReader()
        reader.onload = (e) => {
            galleryPreviews.value.push({
                url: e.target.result,
                file: file,
                is_new: true
            })
        }
        reader.readAsDataURL(file)
    })
}

// Remove gallery image
const removeGalleryImage = (index) => {
    const image = galleryPreviews.value[index]
    
    if (!image.is_new && image.path) {
        // Mark existing image for deletion
        form.images_to_delete.push(image.path)
    }
    
    if (image.is_new) {
        form.gallery_images.splice(index, 1)
    }
    
    galleryPreviews.value.splice(index, 1)
}

// Generate SKU automatically
const generateSKU = () => {
    if (!form.name) {
        showNotification('Please enter product name first', 'error')
        return
    }
    const prefix = form.name.substring(0, 3).toUpperCase()
    const random = Math.floor(Math.random() * 10000)
    form.sku = `${prefix}-${random}`
}

// Update product
const updateProduct = () => {
    // Validation
    if (!form.name) {
        showNotification('Please enter product name', 'error')
        return
    }
    if (!form.sku) {
        showNotification('Please enter SKU', 'error')
        return
    }
    if (!form.category_id) {
        showNotification('Please select category', 'error')
        return
    }
    if (!form.unit_id) {
        showNotification('Please select unit', 'error')
        return
    }
    if (!form.purchase_price && form.purchase_price !== 0) {
        showNotification('Please enter purchase price', 'error')
        return
    }
    if (!form.selling_price && form.selling_price !== 0) {
        showNotification('Please enter selling price', 'error')
        return
    }
    if (!form.stock_quantity && form.stock_quantity !== 0) {
        showNotification('Please enter stock quantity', 'error')
        return
    }
    
    // Create FormData for file upload
    const formData = new FormData()
    formData.append('_method', 'PUT')
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
    formData.append('tax_rate', form.tax_rate || 0)
    formData.append('description', form.description)
    formData.append('is_active', form.is_active ? 1 : 0)
    formData.append('is_taxable', form.is_taxable ? 1 : 0)
    
    // Handle main image
    if (form.product_image) {
        formData.append('product_image', form.product_image)
    }
    
    // Handle gallery images
    form.gallery_images.forEach((image, index) => {
        formData.append(`gallery_images[${index}]`, image)
    })
    
    // Handle images to delete
    if (form.images_to_delete && form.images_to_delete.length > 0) {
        formData.append('images_to_delete', JSON.stringify(form.images_to_delete))
    }
    
    // Submit using Inertia
    router.post(route('products.update', form.id), formData, {
        onSuccess: () => {
            showNotification('Product updated successfully!', 'success')
            
            // Redirect to product list after 2 seconds
            setTimeout(() => {
                router.get(route('products.index'))
            }, 2000)
        },
        onError: (errors) => {
            const errorMessage = Object.values(errors)[0] || 'Failed to update product'
            showNotification(errorMessage, 'error')
        }
    })
}

// Go back
const goBack = () => {
    if (confirm('Are you sure you want to leave? Changes will not be saved.')) {
        router.get(route('products.index'))
    }
}
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Edit Product" />
        
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
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-edit text-indigo-600"></i>
                            Edit Product
                        </h1>
                        <p class="text-sm text-gray-500 mt-1">Update product information</p>
                    </div>
                    <div class="flex gap-3">
                        <button 
                            @click="goBack" 
                            class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition flex items-center gap-2 text-sm font-medium"
                        >
                            <i class="fas fa-arrow-left"></i>
                            Cancel
                        </button>
                        <button 
                            @click="updateProduct"
                            :disabled="form.processing"
                            class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition shadow-md flex items-center gap-2 text-sm font-medium disabled:opacity-50"
                        >
                            <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-save"></i>
                            Update Product
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Form - 2 columns wide -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Basic Information -->
                        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                                <h2 class="font-semibold text-gray-800 flex items-center gap-2">
                                    <i class="fas fa-info-circle text-indigo-600"></i>
                                    Basic Information
                                </h2>
                            </div>
                            <div class="p-6 space-y-5">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <!-- Product Name -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Product Name <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            v-model="form.name"
                                            type="text" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                                            placeholder="Enter product name"
                                        >
                                        <p v-if="errors?.name" class="mt-1 text-xs text-red-600">{{ errors.name }}</p>
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
                                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                                                placeholder="Stock Keeping Unit"
                                            >
                                            <button 
                                                type="button"
                                                @click="generateSKU"
                                                class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition text-sm"
                                                title="Generate SKU"
                                            >
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </div>
                                        <p v-if="errors?.sku" class="mt-1 text-xs text-red-600">{{ errors.sku }}</p>
                                    </div>
                                    
                                    <!-- Barcode -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Barcode
                                        </label>
                                        <input 
                                            v-model="form.barcode"
                                            type="text" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                                            placeholder="Barcode number"
                                        >
                                    </div>
                                    
                                    <!-- Category -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Category <span class="text-red-500">*</span>
                                        </label>
                                        <select 
                                            v-model="form.category_id"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                                        >
                                            <option value="">Select Category</option>
                                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                                {{ category.name }}
                                            </option>
                                        </select>
                                        <p v-if="errors?.category_id" class="mt-1 text-xs text-red-600">{{ errors.category_id }}</p>
                                    </div>
                                    
                                    <!-- Brand -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Brand
                                        </label>
                                        <select 
                                            v-model="form.brand_id"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                                        >
                                            <option value="">Select Brand</option>
                                            <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                                                {{ brand.name }}
                                            </option>
                                        </select>
                                    </div>
                                    
                                    <!-- Unit -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Unit <span class="text-red-500">*</span>
                                        </label>
                                        <select 
                                            v-model="form.unit_id"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                                        >
                                            <option value="">Select Unit</option>
                                            <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                                {{ unit.name }} ({{ unit.short_name }})
                                            </option>
                                        </select>
                                        <p v-if="errors?.unit_id" class="mt-1 text-xs text-red-600">{{ errors.unit_id }}</p>
                                    </div>
                                </div>
                                
                                <!-- Description -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Description
                                    </label>
                                    <textarea 
                                        v-model="form.description"
                                        rows="4" 
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                                        placeholder="Product description..."
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pricing & Stock -->
                        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                                <h2 class="font-semibold text-gray-800 flex items-center gap-2">
                                    <i class="fas fa-tags text-green-600"></i>
                                    Pricing & Stock
                                </h2>
                            </div>
                            <div class="p-6 space-y-5">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                    <!-- Purchase Price -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Purchase Price <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-2 text-gray-500">৳</span>
                                            <input 
                                                v-model.number="form.purchase_price"
                                                type="number" 
                                                step="0.01" 
                                                class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                                                placeholder="0.00"
                                            >
                                        </div>
                                        <p v-if="errors?.purchase_price" class="mt-1 text-xs text-red-600">{{ errors.purchase_price }}</p>
                                    </div>
                                    
                                    <!-- Selling Price -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Selling Price <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-2 text-gray-500">৳</span>
                                            <input 
                                                v-model.number="form.selling_price"
                                                type="number" 
                                                step="0.01" 
                                                class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                                                placeholder="0.00"
                                            >
                                        </div>
                                        <p v-if="form.is_taxable && form.tax_rate > 0" class="mt-1 text-xs text-green-600">
                                            With tax: ৳ {{ calculateSellingPriceWithTax.toFixed(2) }}
                                        </p>
                                        <p v-if="errors?.selling_price" class="mt-1 text-xs text-red-600">{{ errors.selling_price }}</p>
                                    </div>
                                    
                                    <!-- Wholesale Price -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Wholesale Price
                                        </label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-2 text-gray-500">৳</span>
                                            <input 
                                                v-model.number="form.wholesale_price"
                                                type="number" 
                                                step="0.01" 
                                                class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                                                placeholder="0.00"
                                            >
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                    <!-- Stock Quantity -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Stock Quantity <span class="text-red-500">*</span>
                                        </label>
                                        <input 
                                            v-model.number="form.stock_quantity"
                                            type="number" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                                            placeholder="Quantity"
                                        >
                                        <p class="mt-1 text-xs" :class="stockStatusClass">
                                            <i class="fas fa-chart-line mr-1"></i>
                                            {{ stockStatusText }}
                                        </p>
                                    </div>
                                    
                                    <!-- Min Stock Alert -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Min Stock Alert
                                        </label>
                                        <input 
                                            v-model.number="form.min_stock_alert"
                                            type="number" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                                            placeholder="Alert at"
                                        >
                                    </div>
                                    
                                    <!-- Tax Rate -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Tax Rate (%)
                                        </label>
                                        <input 
                                            v-model.number="form.tax_rate"
                                            type="number" 
                                            step="0.01" 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                                            placeholder="0.00"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Images -->
                        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                                <h2 class="font-semibold text-gray-800 flex items-center gap-2">
                                    <i class="fas fa-image text-purple-600"></i>
                                    Product Images
                                </h2>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Main Image -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Main Image
                                        </label>
                                        <div 
                                            class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-indigo-500 transition cursor-pointer"
                                            @dragover.prevent
                                        >
                                            <div class="space-y-1 text-center">
                                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400"></i>
                                                <div class="flex text-sm text-gray-600">
                                                    <label class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500">
                                                        <span>Upload new image</span>
                                                        <input type="file" class="sr-only" accept="image/*" id="mainImage" @change="handleMainImageUpload">
                                                    </label>
                                                </div>
                                                <p class="text-xs text-gray-500">PNG, JPG, JPEG up to 5MB</p>
                                            </div>
                                        </div>
                                        <div v-if="mainImagePreview" class="mt-3 relative inline-block">
                                            <img :src="mainImagePreview" alt="Main preview" class="h-32 w-32 object-cover rounded-lg shadow-md">
                                            <button 
                                                type="button"
                                                @click="removeMainImage"
                                                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 transition shadow-md"
                                            >
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Gallery Images -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Gallery Images (Multiple)
                                        </label>
                                        <div 
                                            class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-indigo-500 transition cursor-pointer"
                                            @dragover.prevent
                                        >
                                            <div class="space-y-1 text-center">
                                                <i class="fas fa-images text-4xl text-gray-400"></i>
                                                <div class="flex text-sm text-gray-600">
                                                    <label class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500">
                                                        <span>Upload images</span>
                                                        <input type="file" class="sr-only" accept="image/*" multiple id="galleryImages" @change="handleGalleryUpload">
                                                    </label>
                                                </div>
                                                <p class="text-xs text-gray-500">Upload multiple images (PNG, JPG, JPEG up to 5MB each)</p>
                                            </div>
                                        </div>
                                        <div class="mt-3 grid grid-cols-3 gap-2">
                                            <div v-for="(preview, index) in galleryPreviews" :key="index" class="relative group">
                                                <img :src="preview.url" alt="Gallery" class="h-24 w-full object-cover rounded-lg shadow-md">
                                                <button 
                                                    type="button"
                                                    @click="removeGalleryImage(index)"
                                                    class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 text-xs opacity-0 group-hover:opacity-100 transition shadow-md"
                                                >
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <span v-if="!preview.is_new" class="absolute bottom-1 left-1 bg-blue-500 text-white text-xs px-1 rounded">Existing</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sidebar - 1 column -->
                    <div class="space-y-6">
                        <!-- Status Card -->
                        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                                <h2 class="font-semibold text-gray-800 flex items-center gap-2">
                                    <i class="fas fa-sliders-h text-indigo-600"></i>
                                    Status
                                </h2>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="flex items-center justify-between">
                                    <label class="text-sm font-medium text-gray-700">Product Status</label>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                    </label>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <label class="text-sm font-medium text-gray-700">Taxable</label>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" v-model="form.is_taxable" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Info Card -->
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-5">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-info-circle text-indigo-600 text-lg mt-0.5"></i>
                                <div>
                                    <h3 class="font-semibold text-gray-800 text-sm mb-1">Product Information</h3>
                                    <p class="text-xs text-gray-600 mb-2">
                                        Product ID: <span class="font-mono font-semibold">{{ product.id }}</span>
                                    </p>
                                    <p class="text-xs text-gray-600 mb-2">
                                        Created: {{ new Date(product.created_at).toLocaleDateString() }}
                                    </p>
                                    <p class="text-xs text-gray-600">
                                        Last Updated: {{ new Date(product.updated_at).toLocaleDateString() }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Help Card -->
                        <div class="bg-white rounded-xl shadow-sm p-5">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-lightbulb text-yellow-500 text-lg"></i>
                                <div>
                                    <h3 class="font-semibold text-gray-800 text-sm mb-1">Quick Tips</h3>
                                    <ul class="text-xs text-gray-600 space-y-1 list-disc list-inside">
                                        <li>SKU must be unique</li>
                                        <li>Selling price should be higher than purchase price</li>
                                        <li>Set min stock alert to get low stock notifications</li>
                                        <li>Gallery images will appear in product details</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
    height: 8px;
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