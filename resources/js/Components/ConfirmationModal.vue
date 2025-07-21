<template>
  <Modal :show="show" @close="close">
    <div class="p-6">
      <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
        {{ title }}
      </h2>
      
      <div class="mt-4">
        <slot />
      </div>
      
      <div class="mt-6 flex justify-end space-x-4">
        <SecondaryButton @click="close">
          Cancel
        </SecondaryButton>
        <PrimaryButton 
          @click="confirm" 
          :class="{
            'bg-red-600 hover:bg-red-700 focus:ring-red-500': confirmVariant === 'danger',
            'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500': confirmVariant !== 'danger'
          }"
          class="focus:ring-2 focus:ring-offset-2"
        >
          {{ confirmText }}
        </PrimaryButton>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import Modal from './Modal.vue';
import PrimaryButton from './PrimaryButton.vue';
import SecondaryButton from './SecondaryButton.vue';

const emit = defineEmits(['close', 'confirmed']);

const props = defineProps({
  show: Boolean,
  title: String,
  confirmText: {
    type: String,
    default: 'Confirm'
  },
  confirmVariant: {
    type: String,
    default: 'primary'
  }
});

function close() {
  emit('close');
}

function confirm() {
  emit('confirmed');
}
</script>