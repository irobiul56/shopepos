<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'menu-item flex items-center p-3 rounded-lg font-medium text-blue-600 bg-blue-50 border-l-4 border-blue-600'
        : 'menu-item flex items-center p-3 rounded-lg font-medium text-gray-600 hover:bg-gray-50 hover:text-blue-500 transition-colors',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>