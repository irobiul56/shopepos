<!-- UserSidebar.vue -->
<script setup>
import { defineProps } from 'vue';
import NavLink from '@/Components/NavLink.vue';

const props = defineProps({
  isCollapsed: Boolean
});

// Route helper function for active state
const isRouteActive = (patterns) => {
  const currentRoute = route().current();
  if (!currentRoute) return false;

  return patterns.some(pattern => {
    if (pattern.endsWith('*')) {
      const basePattern = pattern.replace('*', '');
      return currentRoute.startsWith(basePattern);
    }
    return currentRoute === pattern;
  });
};
</script>

<template>
  <aside class="min-h-screen bg-white shadow-lg p-4 transition-all duration-300 border-r border-gray-100 flex flex-col"
    :class="isCollapsed ? 'w-20' : 'w-64'">
    <nav>
      <ul class="space-y-2">
      <li>
          <NavLink :href="route('dashboard')" :active="isRouteActive(['dashboard', 'dashboard.*'])"
            class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition-colors"
            :class="isCollapsed ? 'justify-center px-2' : 'px-3'">
            <div class="flex items-center">
              <i class="fa fa-dashboard mr-2" style="font-size:20px"></i>
              <span v-if="!isCollapsed" class="whitespace-nowrap">Dashboard</span>
            </div>
          </NavLink>
        </li>

        <li>
          <NavLink :href="route('products.index')" :active="isRouteActive(['products', 'products.*'])"
            class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition-colors"
            :class="isCollapsed ? 'justify-center px-2' : 'px-3'">
            <div class="flex items-center">
              <i class="fa-solid fa-store mr-2" style="font-size:20px"></i>
              <span v-if="!isCollapsed" class="whitespace-nowrap">Products</span>
            </div>
          </NavLink>
        </li>

        <li>
          <NavLink :href="route('categories.index')" :active="isRouteActive(['categories', 'categories.*'])"
            class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition-colors"
            :class="isCollapsed ? 'justify-center px-2' : 'px-3'">
            <div class="flex items-center">
              <i class="fa-solid fa-table-list mr-2" style="font-size:20px"></i>
              <span v-if="!isCollapsed" class="whitespace-nowrap">Categories</span>
            </div>
          </NavLink>
        </li>

        <li>
          <NavLink :href="route('brands.index')" :active="isRouteActive(['brands', 'brands.*'])"
            class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition-colors"
            :class="isCollapsed ? 'justify-center px-2' : 'px-3'">
            <div class="flex items-center">
              <i class="fa-solid fa-trademark mr-2" style="font-size:20px"></i>
              <span v-if="!isCollapsed" class="whitespace-nowrap">brands</span>
            </div>
          </NavLink>
        </li>

        <li>
          <NavLink :href="route('units.index')" :active="isRouteActive(['units', 'units.*'])"
            class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition-colors"
            :class="isCollapsed ? 'justify-center px-2' : 'px-3'">
            <div class="flex items-center">
              <i class="fa-solid fa-store mr-2" style="font-size:20px"></i>
              <span v-if="!isCollapsed" class="whitespace-nowrap">Units</span>
            </div>
          </NavLink>
        </li>
        
        <li>
          <NavLink :href="route('customers.index')" :active="isRouteActive(['customers', 'customers.*'])"
            class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition-colors"
            :class="isCollapsed ? 'justify-center px-2' : 'px-3'">
            <div class="flex items-center">
              <i class="fa-solid fa-users mr-2" style="font-size:20px"></i>
              <span v-if="!isCollapsed" class="whitespace-nowrap">Customers</span>
            </div>
          </NavLink>
        </li>

        <li>
          <NavLink :href="route('suppliers.index')" :active="isRouteActive(['suppliers', 'suppliers.*'])"
            class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition-colors"
            :class="isCollapsed ? 'justify-center px-2' : 'px-3'">
            <div class="flex items-center">
              <i class="fa-solid fa-user-gear mr-2" style="font-size:20px"></i>
              <span v-if="!isCollapsed" class="whitespace-nowrap">Suppliers</span>
            </div>
          </NavLink>
        </li>



      </ul>
    </nav>
  </aside>
</template>