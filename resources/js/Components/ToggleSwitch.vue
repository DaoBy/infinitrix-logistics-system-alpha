
<script setup>
defineProps({
    enabled: {
        type: Boolean,
        default: false,
    },
    label: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:enabled']);

const toggle = () => {
    emit('update:enabled', !props.enabled);
};
</script>

<template>
    <div class="flex items-center">
        <button
            type="button"
            @click="toggle"
            :class="[enabled ? 'bg-indigo-600' : 'bg-gray-200']"
            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2"
        >
            <span
                aria-hidden="true"
                :class="[enabled ? 'translate-x-5' : 'translate-x-0']"
                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
            />
        </button>
        <span class="ml-3 text-sm">
            <span class="font-medium text-gray-900">{{ label }}</span>
        </span>
    </div>
</template>