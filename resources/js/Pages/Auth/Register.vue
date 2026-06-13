<template>
    <Head title="Registration" />
  <div class="min-h-screen relative overflow-hidden transition-colors duration-300"
    :class="isDarkMode ? 'dark bg-gray-900' : 'bg-gradient-to-br from-[#667eea] via-[#764ba2] to-[#f093fb]'">
    
    <!-- Dark Mode Toggle Button -->
    <button @click="toggleDarkMode" 
      class="fixed top-5 right-5 z-50 w-12 h-12 rounded-full bg-white dark:bg-gray-800 shadow-lg flex items-center justify-center transition-all duration-300 hover:scale-110">
      <i :class="isDarkMode ? 'fas fa-sun text-yellow-400' : 'fas fa-moon text-gray-700'" class="text-xl"></i>
    </button>

    <!-- Animated Background Elements -->
    <div class="absolute inset-0 overflow-hidden">
      <div class="absolute -top-40 -right-40 w-80 h-80 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob"
        :class="isDarkMode ? 'bg-purple-500' : 'bg-white'"></div>
      <div class="absolute -bottom-40 -left-40 w-80 h-80 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob animation-delay-2000"
        :class="isDarkMode ? 'bg-blue-500' : 'bg-purple-300'"></div>
      <div class="absolute top-40 left-40 w-80 h-80 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob animation-delay-4000"
        :class="isDarkMode ? 'bg-pink-500' : 'bg-pink-300'"></div>
    </div>

    <div class="container mx-auto max-w-7xl relative z-10 min-h-screen flex items-center justify-center p-4">
      <div class="flex flex-col lg:flex-row gap-8 items-center w-full">
        <!-- Left Side - Branding Section -->
        <div class="lg:w-1/2 transform transition-all duration-700 hover:scale-105">
          <div class="space-y-6 backdrop-blur-sm bg-white/10 dark:bg-gray-800/30 p-8 rounded-2xl border border-white/20 dark:border-gray-700/50">
            <div class="flex items-center gap-3 animate-slideInLeft">
              <div class="w-16 h-16 bg-white/20 dark:bg-gray-700/50 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                <i class="fas fa-store text-4xl text-white dark:text-gray-200"></i>
              </div>
              <div>
                <h1 class="text-4xl font-bold text-white dark:text-gray-100">POS Software</h1>
                <p class="text-sm text-white/80 dark:text-gray-300">Point of Sale Solution</p>
              </div>
            </div>
            
            <div class="space-y-4 animate-slideInLeft animation-delay-300">
              <h2 class="text-4xl font-bold leading-tight text-white dark:text-gray-100">
                Your Business
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-pink-400">
                  Digital Partner
                </span>
              </h2>
              <p class="text-lg text-white/80 dark:text-gray-300 leading-relaxed">
                A complete POS solution that makes your shop management easy, 
                fast and efficient.
              </p>
            </div>
            
            <div class="space-y-4 mt-8 animate-slideInLeft animation-delay-600">
              <div class="flex items-center gap-3 group hover:translate-x-2 transition-transform duration-300">
                <div class="w-12 h-12 bg-white/20 dark:bg-gray-700/50 rounded-xl flex items-center justify-center backdrop-blur-sm group-hover:scale-110 transition">
                  <i class="fas fa-chart-line text-xl text-white dark:text-gray-200"></i>
                </div>
                <div>
                  <h3 class="font-semibold text-white dark:text-gray-200">Real-time Reports</h3>
                  <p class="text-sm text-white/70 dark:text-gray-400">Live sales and stock reports</p>
                </div>
              </div>
              <div class="flex items-center gap-3 group hover:translate-x-2 transition-transform duration-300">
                <div class="w-12 h-12 bg-white/20 dark:bg-gray-700/50 rounded-xl flex items-center justify-center backdrop-blur-sm group-hover:scale-110 transition">
                  <i class="fas fa-shield-alt text-xl text-white dark:text-gray-200"></i>
                </div>
                <div>
                  <h3 class="font-semibold text-white dark:text-gray-200">Secure & Protected</h3>
                  <p class="text-sm text-white/70 dark:text-gray-400">Encrypted data storage</p>
                </div>
              </div>
              <div class="flex items-center gap-3 group hover:translate-x-2 transition-transform duration-300">
                <div class="w-12 h-12 bg-white/20 dark:bg-gray-700/50 rounded-xl flex items-center justify-center backdrop-blur-sm group-hover:scale-110 transition">
                  <i class="fas fa-headset text-xl text-white dark:text-gray-200"></i>
                </div>
                <div>
                  <h3 class="font-semibold text-white dark:text-gray-200">24/7 Support</h3>
                  <p class="text-sm text-white/70 dark:text-gray-400">Help anytime you need</p>
                </div>
              </div>
            </div>

            <!-- Stats Section -->
            <div class="grid grid-cols-3 gap-4 pt-6 border-t border-white/20 dark:border-gray-700/50 animate-slideInLeft animation-delay-900">
              <div class="text-center">
                <div class="text-2xl font-bold text-white dark:text-gray-200">10K+</div>
                <div class="text-xs text-white/70 dark:text-gray-400">Happy Clients</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-white dark:text-gray-200">50K+</div>
                <div class="text-xs text-white/70 dark:text-gray-400">Shops Connected</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-white dark:text-gray-200">99.9%</div>
                <div class="text-xs text-white/70 dark:text-gray-400">Uptime</div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Right Side - Registration Form -->
        <div class="lg:w-1/2 w-full animate-slideInRight">
          <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl overflow-hidden transform transition-all duration-500 hover:shadow-3xl">
            <!-- Form Header -->
            <div class="bg-gradient-to-r from-[#667eea] to-[#764ba2] dark:from-[#4f46e5] dark:to-[#7c3aed] px-8 py-6 relative overflow-hidden group">
              <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-10 transition-opacity duration-500"></div>
              <div class="text-center relative z-10">
                <div class="w-20 h-20 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4 backdrop-blur-sm">
                  <i class="fas fa-user-plus text-3xl text-white"></i>
                </div>
                <h2 class="text-2xl font-bold text-white">Create New Account</h2>
                <p class="text-white/90 text-sm mt-1">Register your shop now</p>
              </div>
            </div>
            
            <!-- Form Body -->
            <form @submit.prevent="handleSubmit" class="p-8 space-y-5">
              <!-- Shop Type Selection -->
              <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-gray-700/50 dark:to-gray-700/30 p-4 rounded-2xl border-2 transition-all duration-300"
                :class="isDarkMode ? 'border-gray-600 hover:border-gray-500' : 'border-transparent hover:border-blue-200'">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                  <i class="fas fa-store text-blue-600 dark:text-blue-400 mr-2"></i>
                  Shop Type <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                  <label 
                    v-for="type in shopTypes" 
                    :key="type.value"
                    class="flex flex-col items-center p-3 rounded-xl border-2 cursor-pointer transition-all duration-300 hover:scale-105"
                    :class="{ 
                      'border-blue-500 bg-blue-50 dark:bg-blue-900/30 shadow-lg': form.shop_type === type.value,
                      'border-gray-200 dark:border-gray-600 hover:border-blue-300 dark:hover:border-blue-500': form.shop_type !== type.value,
                      'bg-white dark:bg-gray-700': true
                    }"
                  >
                    <input 
                      type="radio" 
                      :value="type.value" 
                      v-model="form.shop_type"
                      class="hidden"
                    >
                    <i :class="type.icon" class="text-2xl mb-1" :style="{ color: type.color }"></i>
                    <span class="text-xs font-medium text-center dark:text-gray-300">{{ type.label }}</span>
                  </label>
                </div>
                <div class="mt-3" v-if="form.shop_type === 'other'">
                  <input 
                    type="text" 
                    v-model="form.custom_shop_type"
                    placeholder="Enter shop type"
                    class="w-full px-4 py-2 text-sm border rounded-xl focus:outline-none focus:ring-2 transition dark:bg-gray-700 dark:text-gray-100"
                    :class="isDarkMode ? 'border-gray-600 focus:border-blue-500 focus:ring-blue-500/20 bg-gray-700 text-white' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-200 bg-white'"
                  >
                </div>
              </div>
              
              <!-- Shop Information -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="group">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    <i class="fas fa-store mr-1 text-blue-500 dark:text-blue-400"></i>
                    Shop Name <span class="text-red-500">*</span>
                  </label>
                  <input 
                    type="text" 
                    v-model="form.shop_name"
                    placeholder="Enter shop name"
                    class="w-full px-4 py-2.5 border rounded-xl focus:outline-none focus:ring-2 transition-all duration-300 group-hover:shadow-md dark:bg-gray-700 dark:text-gray-100"
                    :class="[errors.shop_name ? 'border-red-500' : (isDarkMode ? 'border-gray-600 focus:border-blue-500 focus:ring-blue-500/20' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-200')]"
                  >
                  <p v-if="errors.shop_name" class="text-xs text-red-500 mt-1 animate-shake">{{ errors.shop_name }}</p>
                </div>
                <div class="group">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    <i class="fas fa-user mr-1 text-blue-500 dark:text-blue-400"></i>
                    Owner Name <span class="text-red-500">*</span>
                  </label>
                  <input 
                    type="text" 
                    v-model="form.owner_name"
                    placeholder="Enter owner name"
                    class="w-full px-4 py-2.5 border rounded-xl focus:outline-none focus:ring-2 transition-all duration-300 group-hover:shadow-md dark:bg-gray-700 dark:text-gray-100"
                    :class="[errors.owner_name ? 'border-red-500' : (isDarkMode ? 'border-gray-600 focus:border-blue-500 focus:ring-blue-500/20' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-200')]"
                  >
                  <p v-if="errors.owner_name" class="text-xs text-red-500 mt-1">{{ errors.owner_name }}</p>
                </div>
              </div>
              
              <!-- Contact Information -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="group">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    <i class="fas fa-envelope mr-1 text-blue-500 dark:text-blue-400"></i>
                    Email Address <span class="text-red-500">*</span>
                  </label>
                  <input 
                    type="email" 
                    v-model="form.email"
                    placeholder="example@email.com"
                    class="w-full px-4 py-2.5 border rounded-xl focus:outline-none focus:ring-2 transition-all duration-300 group-hover:shadow-md dark:bg-gray-700 dark:text-gray-100"
                    :class="[errors.email ? 'border-red-500' : (isDarkMode ? 'border-gray-600 focus:border-blue-500 focus:ring-blue-500/20' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-200')]"
                  >
                  <p v-if="errors.email" class="text-xs text-red-500 mt-1">{{ errors.email }}</p>
                </div>
                <div class="group">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    <i class="fas fa-phone mr-1 text-blue-500 dark:text-blue-400"></i>
                    Phone Number <span class="text-red-500">*</span>
                  </label>
                  <input 
                    type="tel" 
                    v-model="form.phone"
                    @input="formatPhoneNumber"
                    placeholder="01XXXXXXXXX"
                    class="w-full px-4 py-2.5 border rounded-xl focus:outline-none focus:ring-2 transition-all duration-300 group-hover:shadow-md dark:bg-gray-700 dark:text-gray-100"
                    :class="[errors.phone ? 'border-red-500' : (isDarkMode ? 'border-gray-600 focus:border-blue-500 focus:ring-blue-500/20' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-200')]"
                  >
                  <p v-if="errors.phone" class="text-xs text-red-500 mt-1">{{ errors.phone }}</p>
                </div>
              </div>
              
              <!-- Address -->
              <div class="group">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  <i class="fas fa-location-dot mr-1 text-blue-500 dark:text-blue-400"></i>
                  Shop Address <span class="text-red-500">*</span>
                </label>
                <textarea 
                  v-model="form.address" 
                  rows="2"
                  placeholder="Enter full address"
                  class="w-full px-4 py-2.5 border rounded-xl focus:outline-none focus:ring-2 transition-all duration-300 group-hover:shadow-md dark:bg-gray-700 dark:text-gray-100"
                  :class="[errors.address ? 'border-red-500' : (isDarkMode ? 'border-gray-600 focus:border-blue-500 focus:ring-blue-500/20' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-200')]"
                ></textarea>
                <p v-if="errors.address" class="text-xs text-red-500 mt-1">{{ errors.address }}</p>
              </div>
              
              <!-- User Credentials -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="group">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    <i class="fas fa-user-circle mr-1 text-blue-500 dark:text-blue-400"></i>
                    Username <span class="text-red-500">*</span>
                  </label>
                  <input 
                    type="text" 
                    v-model="form.username"
                    @input="formatUsername"
                    placeholder="Choose username"
                    class="w-full px-4 py-2.5 border rounded-xl focus:outline-none focus:ring-2 transition-all duration-300 group-hover:shadow-md dark:bg-gray-700 dark:text-gray-100"
                    :class="[errors.username ? 'border-red-500' : (isDarkMode ? 'border-gray-600 focus:border-blue-500 focus:ring-blue-500/20' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-200')]"
                  >
                  <p v-if="errors.username" class="text-xs text-red-500 mt-1">{{ errors.username }}</p>
                </div>
                <div class="group">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    <i class="fas fa-briefcase mr-1 text-blue-500 dark:text-blue-400"></i>
                    Role <span class="text-red-500">*</span>
                  </label>
                  <select 
                    v-model="form.role"
                    class="w-full px-4 py-2.5 border rounded-xl focus:outline-none focus:ring-2 transition-all duration-300 cursor-pointer dark:bg-gray-700 dark:text-gray-100"
                    :class="[errors.role ? 'border-red-500' : (isDarkMode ? 'border-gray-600 focus:border-blue-500 focus:ring-blue-500/20' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-200')]"
                  >
                    <option value="" class="dark:bg-gray-800">Select Role</option>
                    <option value="shop_owner" class="dark:bg-gray-800">Shop Owner</option>
                    <option value="manager" class="dark:bg-gray-800">Manager</option>
                    <option value="cashier" class="dark:bg-gray-800">Cashier</option>
                    <option value="inventory_manager" class="dark:bg-gray-800">Inventory Manager</option>
                    <option value="sales_person" class="dark:bg-gray-800">Sales Person</option>
                  </select>
                  <p v-if="errors.role" class="text-xs text-red-500 mt-1">{{ errors.role }}</p>
                </div>
              </div>
              
              <!-- Password Section -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    <i class="fas fa-lock mr-1 text-blue-500 dark:text-blue-400"></i>
                    Password <span class="text-red-500">*</span>
                  </label>
                  <div class="relative">
                    <input 
                      :type="showPassword ? 'text' : 'password'"
                      v-model="form.password"
                      @input="checkPasswordStrength"
                      placeholder="Enter password"
                      class="w-full px-4 py-2.5 border rounded-xl focus:outline-none focus:ring-2 transition-all duration-300 pr-12 dark:bg-gray-700 dark:text-gray-100"
                      :class="[errors.password ? 'border-red-500' : (isDarkMode ? 'border-gray-600 focus:border-blue-500 focus:ring-blue-500/20' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-200')]"
                    >
                    <button 
                      type="button"
                      @click="showPassword = !showPassword"
                      class="absolute right-3 top-3 text-gray-500 hover:text-blue-600 transition dark:text-gray-400 dark:hover:text-blue-400"
                    >
                      <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                    </button>
                  </div>
                  <p v-if="errors.password" class="text-xs text-red-500 mt-1">{{ errors.password }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    <i class="fas fa-check-circle mr-1 text-blue-500 dark:text-blue-400"></i>
                    Confirm Password <span class="text-red-500">*</span>
                  </label>
                  <div class="relative">
                    <input 
                      :type="showConfirmPassword ? 'text' : 'password'"
                      v-model="form.password_confirmation"
                      placeholder="Confirm password"
                      class="w-full px-4 py-2.5 border rounded-xl focus:outline-none focus:ring-2 transition-all duration-300 pr-12 dark:bg-gray-700 dark:text-gray-100"
                      :class="[errors.password_confirmation ? 'border-red-500' : (isDarkMode ? 'border-gray-600 focus:border-blue-500 focus:ring-blue-500/20' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-200')]"
                    >
                    <button 
                      type="button"
                      @click="showConfirmPassword = !showConfirmPassword"
                      class="absolute right-3 top-3 text-gray-500 hover:text-blue-600 transition dark:text-gray-400 dark:hover:text-blue-400"
                    >
                      <i :class="showConfirmPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                    </button>
                  </div>
                  <p v-if="errors.password_confirmation" class="text-xs text-red-500 mt-1">{{ errors.password_confirmation }}</p>
                </div>
              </div>
              
              <!-- Password Strength Indicator -->
              <div class="space-y-1" v-if="form.password">
                <div class="flex gap-1 h-1.5">
                  <div 
                    v-for="i in 4" 
                    :key="i"
                    class="flex-1 rounded-full transition-all duration-500"
                    :class="getStrengthClass(i)"
                  ></div>
                </div>
                <p class="text-xs font-medium" :class="strengthTextClass">
                  <i class="fas fa-shield-alt mr-1"></i>
                  {{ strengthText }}
                </p>
              </div>
              
              <!-- TIN Number -->
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  <i class="fas fa-id-card mr-1 text-blue-500 dark:text-blue-400"></i>
                  TIN Number (Optional)
                </label>
                <input 
                  type="text" 
                  v-model="form.tin_number"
                  placeholder="Enter TIN number"
                  class="w-full px-4 py-2.5 border rounded-xl focus:outline-none focus:ring-2 transition-all duration-300 dark:bg-gray-700 dark:text-gray-100"
                  :class="isDarkMode ? 'border-gray-600 focus:border-blue-500 focus:ring-blue-500/20' : 'border-gray-300 focus:border-blue-500 focus:ring-blue-200'"
                >
              </div>
              
              <!-- Terms & Conditions -->
              <div class="flex items-start gap-3">
                <div class="relative">
                  <input 
                    type="checkbox" 
                    v-model="form.terms"
                    class="peer sr-only"
                    id="terms"
                  >
                  <label for="terms" class="w-5 h-5 border-2 rounded-md cursor-pointer peer-checked:bg-blue-600 peer-checked:border-blue-600 transition-all duration-200 flex items-center justify-center"
                    :class="isDarkMode ? 'border-gray-600 bg-gray-700' : 'border-gray-300 bg-white'">
                    <i v-if="form.terms" class="fas fa-check text-white text-xs"></i>
                  </label>
                </div>
                <label for="terms" class="text-sm cursor-pointer dark:text-gray-300">
                  I have read and agree to the 
                  <a href="#" class="text-blue-600 dark:text-blue-400 font-semibold hover:underline">Terms of Service</a> 
                  and 
                  <a href="#" class="text-blue-600 dark:text-blue-400 font-semibold hover:underline">Privacy Policy</a>
                </label>
              </div>
              <p v-if="errors.terms" class="text-xs text-red-500 -mt-2 ml-8">{{ errors.terms }}</p>
              
              <!-- Submit Button -->
              <button 
                type="submit" 
                :disabled="loading"
                class="w-full bg-gradient-to-r from-[#667eea] to-[#764ba2] dark:from-[#4f46e5] dark:to-[#7c3aed] text-white py-3.5 rounded-xl font-semibold hover:from-[#764ba2] hover:to-[#667eea] dark:hover:from-[#7c3aed] dark:hover:to-[#4f46e5] transition-all duration-300 shadow-lg hover:shadow-xl flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed transform hover:scale-[1.02]"
              >
                <i v-if="loading" class="fas fa-spinner fa-spin"></i>
                <i v-else class="fas fa-user-plus"></i>
                {{ loading ? 'Processing...' : 'Create Account' }}
              </button>
              
              <!-- Login Link -->
              <div class="text-center pt-4">
                <p class="text-sm dark:text-gray-400">
                  Already have an account? 
                  <Link href="/login" class="text-blue-600 dark:text-blue-400 font-semibold hover:underline hover:text-blue-700 dark:hover:text-blue-300 transition">
                    Sign In
                  </Link>
                </p>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Modern Toast Notification -->
    <Transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="transform translate-x-full opacity-0"
      enter-to-class="transform translate-x-0 opacity-100"
      leave-active-class="transition ease-in duration-300"
      leave-from-class="transform translate-x-0 opacity-100"
      leave-to-class="transform translate-x-full opacity-0"
    >
      <div v-if="toast.show" class="fixed bottom-5 right-5 z-50">
        <div class="rounded-xl shadow-2xl p-4 min-w-[320px] border-l-4 dark:bg-gray-800 dark:text-gray-100"
          :class="toast.typeClass">
          <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
              <i :class="toast.icon" class="text-xl"></i>
            </div>
            <div class="flex-1">
              <p class="font-semibold dark:text-gray-100">{{ toast.title }}</p>
              <p class="text-sm mt-0.5 dark:text-gray-300">{{ toast.message }}</p>
            </div>
            <button @click="toast.show = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { Link, router, Head } from '@inertiajs/vue3'

// Dark Mode
const isDarkMode = ref(false)

const toggleDarkMode = () => {
  isDarkMode.value = !isDarkMode.value
  if (isDarkMode.value) {
    document.documentElement.classList.add('dark')
    localStorage.setItem('darkMode', 'true')
  } else {
    document.documentElement.classList.remove('dark')
    localStorage.setItem('darkMode', 'false')
  }
}

onMounted(() => {
  const savedMode = localStorage.getItem('darkMode')
  if (savedMode === 'true') {
    isDarkMode.value = true
    document.documentElement.classList.add('dark')
  }
})

// Shop types data with icons
const shopTypes = [
  { value: 'grocery', label: 'Grocery', icon: 'fas fa-apple-alt', color: '#22c55e' },
  { value: 'clothing', label: 'Clothing', icon: 'fas fa-tshirt', color: '#3b82f6' },
  { value: 'pharmacy', label: 'Pharmacy', icon: 'fas fa-capsules', color: '#ef4444' },
  { value: 'other', label: 'Other', icon: 'fas fa-store', color: '#8b5cf6' }
]

// Form data
const form = reactive({
  shop_type: '',
  custom_shop_type: '',
  shop_name: '',
  owner_name: '',
  email: '',
  phone: '',
  address: '',
  username: '',
  role: '',
  password: '',
  password_confirmation: '',
  tin_number: '',
  terms: false
})

// UI state
const loading = ref(false)
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const passwordStrength = ref(0)

// Toast state
const toast = ref({
  show: false,
  title: '',
  message: '',
  typeClass: '',
  icon: '',
  iconColor: ''
})

// Errors state
const errors = reactive({
  shop_name: '',
  owner_name: '',
  email: '',
  phone: '',
  address: '',
  username: '',
  role: '',
  password: '',
  password_confirmation: '',
  terms: ''
})

// Computed properties
const strengthText = computed(() => {
  const texts = ['', 'Weak', 'Fair', 'Good', 'Strong']
  return texts[passwordStrength.value] || 'Enter password'
})

const strengthTextClass = computed(() => {
  const classes = {
    1: 'text-red-500',
    2: 'text-orange-500',
    3: 'text-yellow-500',
    4: 'text-green-500'
  }
  return classes[passwordStrength.value] || 'text-gray-500 dark:text-gray-400'
})

const getStrengthClass = (index) => {
  if (index <= passwordStrength.value) {
    if (passwordStrength.value === 1) return 'bg-red-500'
    if (passwordStrength.value === 2) return 'bg-orange-500'
    if (passwordStrength.value === 3) return 'bg-yellow-500'
    if (passwordStrength.value === 4) return 'bg-green-500'
  }
  return 'bg-gray-200 dark:bg-gray-600'
}

// Methods
const formatPhoneNumber = (e) => {
  let value = e.target.value.replace(/[^0-9]/g, '')
  if (value.startsWith('01') && value.length > 11) {
    value = value.slice(0, 11)
  }
  form.phone = value
}

const formatUsername = (e) => {
  form.username = e.target.value.replace(/\s/g, '').toLowerCase()
}

const checkPasswordStrength = () => {
  const password = form.password
  let strength = 0
  
  if (password.length >= 6) strength++
  if (password.length >= 10) strength++
  if (/[A-Z]/.test(password)) strength++
  if (/[0-9]/.test(password)) strength++
  if (/[^A-Za-z0-9]/.test(password)) strength++
  
  passwordStrength.value = Math.min(strength, 4)
}

const validateForm = () => {
  let isValid = true
  
  Object.keys(errors).forEach(key => errors[key] = '')
  
  if (!form.shop_name.trim()) {
    errors.shop_name = 'Shop name is required'
    isValid = false
  }
  
  if (!form.owner_name.trim()) {
    errors.owner_name = 'Owner name is required'
    isValid = false
  }
  
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!form.email) {
    errors.email = 'Email address is required'
    isValid = false
  } else if (!emailPattern.test(form.email)) {
    errors.email = 'Enter a valid email address'
    isValid = false
  }
  
  const phonePattern = /^(01[3-9]\d{8})$/
  if (!form.phone) {
    errors.phone = 'Phone number is required'
    isValid = false
  } else if (!phonePattern.test(form.phone)) {
    errors.phone = 'Enter a valid phone number (01XXXXXXXXX)'
    isValid = false
  }
  
  if (!form.address.trim()) {
    errors.address = 'Address is required'
    isValid = false
  }
  
  if (!form.username.trim()) {
    errors.username = 'Username is required'
    isValid = false
  } else if (form.username.length < 3) {
    errors.username = 'Username must be at least 3 characters'
    isValid = false
  }
  
  if (!form.role) {
    errors.role = 'Please select a role'
    isValid = false
  }
  
  if (!form.password) {
    errors.password = 'Password is required'
    isValid = false
  } else if (form.password.length < 6) {
    errors.password = 'Password must be at least 6 characters'
    isValid = false
  }
  
  if (form.password !== form.password_confirmation) {
    errors.password_confirmation = 'Passwords do not match'
    isValid = false
  }
  
  if (!form.terms) {
    errors.terms = 'You must agree to the terms and conditions'
    isValid = false
  }
  
  if (!form.shop_type) {
    showToast('Please select a shop type!', 'error')
    isValid = false
  }
  
  if (form.shop_type === 'other' && !form.custom_shop_type) {
    showToast('Please specify your shop type!', 'error')
    isValid = false
  }
  
  return isValid
}

const showToast = (message, type = 'success') => {
  const toastConfig = {
    success: {
      title: 'Success!',
      typeClass: 'border-green-500',
      icon: 'fas fa-check-circle',
      iconColor: 'text-green-500'
    },
    error: {
      title: 'Error!',
      typeClass: 'border-red-500',
      icon: 'fas fa-exclamation-circle',
      iconColor: 'text-red-500'
    }
  }
  
  const config = toastConfig[type]
  toast.value = {
    show: true,
    title: config.title,
    message: message,
    typeClass: config.typeClass,
    icon: config.icon,
    iconColor: config.iconColor
  }
  
  setTimeout(() => {
    toast.value.show = false
  }, 4000)
}

const handleSubmit = async () => {
  if (!validateForm()) return
  
  loading.value = true
  
  const submitData = {
    shop_type: form.shop_type === 'other' ? form.custom_shop_type : form.shop_type,
    shop_name: form.shop_name,
    owner_name: form.owner_name,
    email: form.email,
    phone: form.phone,
    address: form.address,
    username: form.username,
    role: form.role,
    password: form.password,
    password_confirmation: form.password_confirmation,
    tin_number: form.tin_number || null
  }
  
  router.post('/register', submitData, {
    onSuccess: () => {
      showToast('Registration successful! Please login.', 'success')
      setTimeout(() => {
        router.visit('/login')
      }, 2000)
    },
    onError: (error) => {
      if (error.email) showToast(error.email, 'error')
      else if (error.username) showToast(error.username, 'error')
      else if (error.phone) showToast(error.phone, 'error')
      else showToast('Registration failed! Please try again.', 'error')
    },
    onFinish: () => {
      loading.value = false
    }
  })
}
</script>

<style scoped>
@keyframes blob {
  0% { transform: translate(0px, 0px) scale(1); }
  33% { transform: translate(30px, -50px) scale(1.1); }
  66% { transform: translate(-20px, 20px) scale(0.9); }
  100% { transform: translate(0px, 0px) scale(1); }
}

@keyframes slideInLeft {
  from {
    opacity: 0;
    transform: translateX(-50px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(50px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-5px); }
  75% { transform: translateX(5px); }
}

.animate-blob {
  animation: blob 7s infinite;
}

.animate-slideInLeft {
  animation: slideInLeft 0.6s ease-out forwards;
}

.animate-slideInRight {
  animation: slideInRight 0.6s ease-out forwards;
}

.animate-shake {
  animation: shake 0.3s ease-in-out;
}

.animation-delay-2000 {
  animation-delay: 2s;
}

.animation-delay-4000 {
  animation-delay: 4s;
}

.animation-delay-300 {
  animation-delay: 0.3s;
}

.animation-delay-600 {
  animation-delay: 0.6s;
}

.animation-delay-900 {
  animation-delay: 0.9s;
}
</style>