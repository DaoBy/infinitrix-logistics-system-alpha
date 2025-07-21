<script setup>
import { computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  show: Boolean,
  maxWidth: {
    type: String,
    default: '2xl',
  },
  closeable: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['close']);

const maxWidthClass = computed(() => {
  return {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
  }[props.maxWidth];
});

const close = () => {
  if (props.closeable) emit('close');
};

const closeOnEscape = (e) => {
  if (e.key === 'Escape' && props.show) {
    e.preventDefault();
    close();
  }
};

watch(
  () => props.show,
  (val) => {
    document.body.style.overflow = val ? 'hidden' : '';
  }
);

onMounted(() => window.addEventListener('keydown', closeOnEscape));
onUnmounted(() => {
  window.removeEventListener('keydown', closeOnEscape);
  document.body.style.overflow = '';
});
</script>

<template>
  <transition name="fade">
    <div
      v-if="show"
      class="fixed inset-0 z-[1000] flex items-center justify-center bg-black/30 backdrop-blur-sm"
      @click.self="close"
    >
      <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full p-6"
        :class="maxWidthClass"
      >
        <slot />
      </div>
    </div>
  </transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
