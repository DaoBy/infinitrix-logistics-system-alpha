<template>
    <Modal :show="show" @close="closeModal">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">
          {{ mode === 'add' ? 'Add' : 'Edit' }} Maintenance Record
        </h2>
        
        <form @submit.prevent="submitForm" class="mt-4 space-y-4">
          <!-- Date Field -->
          <div>
            <InputLabel for="maintenance_date" value="Date *" />
            <TextInput
              id="maintenance_date"
              type="date"
              class="mt-1 block w-full"
              v-model="form.maintenance_date"
              required
              :max="new Date().toISOString().split('T')[0]"
            />
            <InputError class="mt-2" :message="form.errors.maintenance_date" />
          </div>
          
          <!-- Service Details -->
          <div>
            <InputLabel for="service_details" value="Service Details *" />
            <TextArea
              id="service_details"
              class="mt-1 block w-full"
              v-model="form.service_details"
              required
              :rows="3"
            />
            <InputError class="mt-2" :message="form.errors.service_details" />
          </div>
          
          <!-- Provider and Cost -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <InputLabel for="service_provider" value="Service Provider *" />
              <TextInput
                id="service_provider"
                type="text"
                class="mt-1 block w-full"
                v-model="form.service_provider"
                required
              />
              <InputError class="mt-2" :message="form.errors.service_provider" />
            </div>
            
            <div>
              <InputLabel for="cost" value="Cost *" />
              <TextInput
                id="cost"
                type="number"
                step="0.01"
                min="0"
                class="mt-1 block w-full"
                v-model="form.cost"
                required
              />
              <InputError class="mt-2" :message="form.errors.cost" />
            </div>
          </div>
          
          <!-- Notes -->
          <div>
            <InputLabel for="notes" value="Notes" />
            <TextArea
              id="notes"
              class="mt-1 block w-full"
              v-model="form.notes"
              :rows="2"
            />
            <InputError class="mt-2" :message="form.errors.notes" />
          </div>
          
          <!-- Action Buttons -->
          <div class="mt-6 flex justify-end space-x-4">
            <SecondaryButton @click="closeModal">
              Cancel
            </SecondaryButton>
            <PrimaryButton type="submit" :disabled="form.processing">
              {{ mode === 'add' ? 'Save' : 'Update' }} Maintenance
            </PrimaryButton>
          </div>
        </form>
      </div>
    </Modal>
  </template>
  
  <script setup>
  import { ref, watch } from 'vue';
  import Modal from '@/Components/Modal.vue';
  import InputLabel from '@/Components/InputLabel.vue';
  import TextInput from '@/Components/TextInput.vue';
  import TextArea from '@/Components/TextArea.vue';
  import InputError from '@/Components/InputError.vue';
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import SecondaryButton from '@/Components/SecondaryButton.vue';
  
  const props = defineProps({
    show: {
      type: Boolean,
      required: true
    },
    mode: {
      type: String,
      required: true,
      validator: (value) => ['add', 'edit'].includes(value)
    },
    formData: {
      type: Object,
      default: () => ({
        maintenance_date: new Date().toISOString().split('T')[0],
        service_details: '',
        service_provider: '',
        cost: null,
        notes: ''
      })
    },
    formErrors: {
      type: Object,
      default: () => ({})
    },
    isProcessing: {
      type: Boolean,
      default: false
    }
  });
  
  const emit = defineEmits(['close', 'submit']);
  
  const form = ref({
    ...props.formData,
    errors: { ...props.formErrors },
    processing: props.isProcessing
  });
  
  watch(() => props.show, (newVal) => {
    if (newVal) {
      form.value = {
        ...props.formData,
        errors: { ...props.formErrors },
        processing: props.isProcessing
      };
    }
  });
  
  watch(() => props.formErrors, (newVal) => {
    form.value.errors = { ...newVal };
  });
  
  watch(() => props.isProcessing, (newVal) => {
    form.value.processing = newVal;
  });
  
  const closeModal = () => {
    emit('close');
  };
  
  const submitForm = () => {
    emit('submit', form.value);
  };
  </script>