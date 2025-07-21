<template>
  <GuestLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
          Edit Delivery Request: #{{ request.id }}
        </h2>
        <div class="flex space-x-2">
          <SecondaryButton @click="$inertia.visit(route('customer.delivery-requests.index'))">
            Back to List
          </SecondaryButton>
          <DangerButton
            v-if="request.status === 'pending' || request.status === 'draft'"
            @click="cancelRequest"
          >
            Cancel Request
          </DangerButton>
        </div>
      </div>
    </template>

    <DeliveryRequestForm
      :delivery="request"
      :regions="regions"
      :categories="categories"
      :status="status"
      :success="success"
      :error="error"
      :priceMatrix="priceMatrix"
      context="customer"
    />
  </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DeliveryRequestForm from '@/Shared/DeliveryRequestForm.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  request: Object,
  regions: Array,
  categories: Array,
  status: String,
  success: String,
  error: String,
  priceMatrix: Object
});

const cancelRequest = () => {
  if (confirm('Are you sure you want to cancel this delivery request?')) {
    router.delete(route('customer.delivery-requests.destroy', props.request.id), {
      preserveScroll: true,
      onSuccess: () => {
        router.visit(route('customer.delivery-requests.index'));
      },
      onError: () => {
        alert('Failed to cancel request');
      },
    });
  }
};
</script>
