<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold text-gray-800">
          Edit Payment Collection
        </h2>
        <v-btn
          :to="route('collector.payments.show', payment.id)"
          color="secondary"
        >
          Back to Details
        </v-btn>
      </div>
    </template>

    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="p-6">
        <div class="mb-6">
          <h3 class="text-lg font-medium text-gray-900 mb-2">Delivery Information</h3>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-gray-600">Reference:</span>
              <span class="font-medium">{{ payment.delivery_request?.reference_number || 'N/A' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Amount Due:</span>
              <span>₱{{ payment.delivery_request?.total_price?.toFixed(2) || '0.00' }}</span>
            </div>
          </div>
        </div>

        <v-form @submit.prevent="submit">
          <div class="space-y-4">
            <v-text-field
              v-model="form.amount"
              label="Amount Collected *"
              type="number"
              step="0.01"
              min="0"
              prefix="₱"
              :error-messages="form.errors.amount"
              :hint="`Minimum: ₱${minAmount.toFixed(2)} (90% of total)`"
              persistent-hint
            ></v-text-field>

            <v-file-input
              v-model="form.receipt_image"
              label="Receipt Photo (Optional)"
              accept="image/*"
              prepend-icon="mdi-camera"
              :error-messages="form.errors.receipt_image"
              @change="previewImage"
            ></v-file-input>

            <div v-if="imagePreview" class="mt-2">
              <img :src="imagePreview" alt="Receipt preview" class="max-w-xs h-auto rounded border border-gray-200">
            </div>

            <v-textarea
              v-model="form.notes"
              label="Notes"
              rows="3"
            ></v-textarea>

            <div class="flex justify-end pt-4">
              <v-btn
                type="submit"
                color="primary"
                :loading="form.processing"
              >
                Update Payment
              </v-btn>
            </div>
          </div>
        </v-form>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  payment: Object
});

const minAmount = computed(() => props.payment.delivery_request?.total_price * 0.9 || 0);
const imagePreview = ref(null);

const form = useForm({
  amount: props.payment.amount,
  receipt_image: null,
  notes: props.payment.notes
});

const previewImage = (file) => {
  if (!file) {
    imagePreview.value = null;
    return;
  }
  const reader = new FileReader();
  reader.onload = (e) => {
    imagePreview.value = e.target.result;
  };
  reader.readAsDataURL(file);
};

const submit = () => {
  form.put(route('collector.payments.update', props.payment.id), {
    forceFormData: true,
    onSuccess: () => {
      form.reset();
    }
  });
};
</script>