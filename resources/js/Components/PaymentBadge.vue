<template>
  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="statusClasses">
    {{ statusLabel }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: {
    type: String,
    required: true
  }
});

const statusLabel = computed(() => {
  const labels = {
    unpaid: 'Unpaid',
    pending_payment: 'Pending Payment',
    pending_verification: 'Verifying',
    paid: 'Paid',
    verified: 'Verified',
    rejected: 'Rejected'
  };
  return labels[props.status] || props.status;
});

const statusClasses = computed(() => {
  const classes = {
    unpaid: 'bg-red-100 text-red-800',
    pending_payment: 'bg-yellow-100 text-yellow-800',
    pending_verification: 'bg-blue-100 text-blue-800',
    paid: 'bg-green-100 text-green-800',
    verified: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800'
  };
  return classes[props.status] || 'bg-gray-100 text-gray-800';
});
</script>