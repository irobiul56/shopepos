<!-- UserNavBar.vue -->
<script setup>
import { defineEmits, ref } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

const imageUrl = ref('/storage/images/logos.png');

const emit = defineEmits(['toggle-sidebar']);
const showMobileMenu = ref(false);
</script>

<template>
  <nav class="bg-white text-gray-800 shadow-sm p-2 flex justify-between items-center border-b border-gray-100">
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
      <img 
        :src="imageUrl" 
        alt="POS APP" 
        class="h-10 w-30 md:h-17 md:w-10 object-contain"
      />
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
                {{ $page.props.auth.user.name }}
                <svg class="-me-0.5 ms-2 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
              </button>
            </span>
          </template>
          <template #content>
            <DropdownLink :href="route('profile.edit')" class="hover:bg-gray-100 text-gray-700">Profile</DropdownLink>
            <DropdownLink 
              :href="route('logout')" 
              method="post" 
              as="button" 
              class="hover:bg-gray-100 text-gray-700 w-full text-left"
            >
              Log Out
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
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
    </div>
    
    <!-- Mobile Menu Dropdown -->
    <div 
      v-show="showMobileMenu"
      class="sm:hidden absolute top-16 right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200"
    >
      <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Notifications</a>
      <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Language (EN)</a>
      <DropdownLink 
        :href="route('profile.edit')" 
        class="block px-4 py-2 text-sm hover:bg-gray-100 text-gray-700"
        @click="showMobileMenu = false"
      >
        Profile
      </DropdownLink>
      <DropdownLink 
        :href="route('logout')" 
        method="post" 
        as="button" 
        class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-100 text-gray-700"
        @click="showMobileMenu = false"
      >
        Log Out
      </DropdownLink>
    </div>
  </nav>
</template>