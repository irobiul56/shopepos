<script setup>
import { ref } from 'vue';
import UserNavbar from '@/Components/UserNavbar.vue';
import UserSidebar from '@/Components/UserSidebar.vue';

const isCollapsed = ref(false);
const toggleSidebar = () => {
    isCollapsed.value = !isCollapsed.value;
};
</script>

<template>
  <UserNavbar @toggle-sidebar="toggleSidebar" />
  <div class="flex">
    <UserSidebar :is-collapsed="isCollapsed" />
    <slot />
  </div>
</template>

<style scoped>
</style>
