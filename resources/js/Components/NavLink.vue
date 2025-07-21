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
    props.active === true
        ? 'flex items-center px-4 py-2 rounded-md text-sm font-medium text-white bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition duration-150 ease-in-out'
        : 'flex items-center px-4 py-2 rounded-md text-sm font-medium text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-500 transition duration-150 ease-in-out',
);

</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>