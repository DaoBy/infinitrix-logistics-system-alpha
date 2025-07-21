<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
          Edit Delivery Request: #{{ delivery.id }}
        </h2>
        <div class="flex space-x-2">
          <SecondaryButton @click="$inertia.visit(route('deliveries.pending'))">
            Back to List
          </SecondaryButton>
        </div>
      </div>
    </template>

    <DeliveryRequestForm
      :delivery="delivery"
      :regions="regions"
      :categories="categories"
      :status="status"
      :success="success"
      :error="error"
      :priceMatrix="priceMatrix"
      context="admin"
    />
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DeliveryRequestForm from '@/Shared/DeliveryRequestForm.vue';

const props = defineProps({
  delivery: Object,
  regions: Array,
  categories: Array,
  status: String,
  success: String,
  error: String,
  priceMatrix: Object
});
</script>