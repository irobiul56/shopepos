<script setup>
import { defineEmits, ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

const { props } = usePage();

// Get shop from props
const shop = computed(() => props.auth?.user?.shop || null);
const shopLogo = computed(() => {
    if (shop.value?.logo) {
        return `/storage/${shop.value.logo}`;
    }
    return '/storage/images/logos.png'; // Default logo
});

const emit = defineEmits(['toggle-sidebar']);
const showMobileMenu = ref(false);
</script>

<template>
    <nav class="bg-white text-gray-800 shadow-sm w-full p-2 flex justify-between items-center border-b border-gray-100">
        <div class="flex items-center space-x-2 md:space-x-4">
            <button 
                @click="emit('toggle-sidebar')" 
                class="p-2 rounded-md hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
                aria-label="Toggle sidebar"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            
            <!-- Shop Logo with Name -->
            <div class="flex items-center gap-3">
                <img 
                    :src="shopLogo" 
                    :alt="shop?.name || 'Shop Logo'" 
                    class="h-10 w-10 rounded-lg object-cover border border-gray-200"
                    @error="(e) => e.target.src = '/storage/images/logos.png'"
                />
                <div class="hidden sm:block">
                    <h1 class="text-sm font-bold text-gray-800 leading-tight">{{ shop?.name || 'POS APP' }}</h1>
                    <p class="text-[10px] text-gray-500 leading-tight">{{ shop?.owner_name || 'Shop Owner' }}</p>
                </div>
            </div>
        </div>
        
        <!-- Desktop Navigation -->
        <div class="hidden sm:flex items-center space-x-4">
            <button 
                class="p-2 rounded-full hover:bg-gray-100 transition-colors relative focus:outline-none focus:ring-2 focus:ring-blue-500"
                aria-label="Notifications"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <span class="absolute top-0 right-0 h-2 w-2 rounded-full bg-red-500"></span>
            </button>
            
            <div class="relative">
                <button 
                    class="p-2 rounded-md hover:bg-gray-100 transition-colors flex items-center focus:outline-none focus:ring-2 focus:ring-blue-500"
                    aria-label="Language selector"
                >
                    <span class="text-gray-600">🌍 EN</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>
            
            <div class="relative ms-3">
                <Dropdown align="right" width="48">
                    <template #trigger>
                        <span class="inline-flex rounded-md">
                            <button 
                                type="button" 
                                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-700 transition duration-150 ease-in-out hover:text-gray-900 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <!-- User Avatar with initial -->
                                <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold mr-2">
                                    {{ $page.props.auth.user.name?.charAt(0).toUpperCase() || 'U' }}
                                </span>
                                {{ $page.props.auth.user.name }}
                                <svg class="-me-0.5 ms-2 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </span>
                    </template>
                    <template #content>
                        <DropdownLink :href="route('profile.edit')" class="hover:bg-gray-100 text-gray-700">
                            <i class="fas fa-user mr-2"></i> Profile
                        </DropdownLink>
                        <DropdownLink 
                            :href="route('shop.edit', $page.props.auth.user.shop_id)" 
                            class="hover:bg-gray-100 text-gray-700"
                            v-if="$page.props.auth.user.shop_id"
                        >
                            <i class="fas fa-store mr-2"></i> Shop Settings
                        </DropdownLink>
                        <DropdownLink 
                            :href="route('logout')" 
                            method="post" 
                            as="button" 
                            class="hover:bg-gray-100 text-gray-700 w-full text-left"
                        >
                            <i class="fas fa-sign-out-alt mr-2"></i> Log Out
                        </DropdownLink>
                    </template>
                </Dropdown>
            </div>
        </div>
        
        <!-- Mobile Navigation Button -->
        <div class="sm:hidden">
            <button 
                @click="showMobileMenu = !showMobileMenu"
                class="p-2 rounded-md hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
                aria-label="User menu"
            >
                <!-- User avatar for mobile -->
                <span class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">
                    {{ $page.props.auth.user.name?.charAt(0).toUpperCase() || 'U' }}
                </span>
            </button>
        </div>
        
        <!-- Mobile Menu Dropdown -->
        <div 
            v-show="showMobileMenu"
            class="sm:hidden absolute top-16 right-0 mt-2 w-56 bg-white rounded-lg shadow-lg py-2 z-50 border border-gray-200"
        >
            <!-- Shop Info in mobile menu -->
            <div class="px-4 py-3 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <img 
                        :src="shopLogo" 
                        :alt="shop?.name || 'Shop Logo'" 
                        class="w-10 h-10 rounded-lg object-cover border border-gray-200"
                        @error="(e) => e.target.src = '/storage/images/logos.png'"
                    />
                    <div>
                        <p class="text-sm font-semibold text-gray-800">{{ shop?.name || 'POS APP' }}</p>
                        <p class="text-xs text-gray-500">{{ $page.props.auth.user.name }}</p>
                    </div>
                </div>
            </div>
            
            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                <i class="fas fa-bell mr-2"></i> Notifications
            </a>
            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                <i class="fas fa-globe mr-2"></i> Language (EN)
            </a>
            <DropdownLink 
                :href="route('profile.edit')" 
                class="block px-4 py-2 text-sm hover:bg-gray-100 text-gray-700"
                @click="showMobileMenu = false"
            >
                <i class="fas fa-user mr-2"></i> Profile
            </DropdownLink>
            <DropdownLink 
                :href="route('shop.edit', $page.props.auth.user.shop_id)" 
                class="block px-4 py-2 text-sm hover:bg-gray-100 text-gray-700"
                @click="showMobileMenu = false"
                v-if="$page.props.auth.user.shop_id"
            >
                <i class="fas fa-store mr-2"></i> Shop Settings
            </DropdownLink>
            <div class="border-t border-gray-100 mt-1 pt-1">
                <DropdownLink 
                    :href="route('logout')" 
                    method="post" 
                    as="button" 
                    class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-100 text-red-600"
                    @click="showMobileMenu = false"
                >
                    <i class="fas fa-sign-out-alt mr-2"></i> Log Out
                </DropdownLink>
            </div>
        </div>
    </nav>
</template>