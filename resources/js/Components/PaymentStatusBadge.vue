<template>
  <span :class="badgeClasses">
    {{ statusText }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  payment: Object,
  delivery: Object
});

const isPrepaid = computed(() => props.delivery?.payment_type === 'prepaid');
const isVerified = computed(() => props.payment?.verified_by !== null && props.payment?.verified_by !== undefined);
const isCollected = computed(() => props.payment?.collected_by !== null && props.payment?.collected_by !== undefined);

const statusText = computed(() => {
  if (isPrepaid.value) {
    return isVerified.value ? 'Verified' : 'Pending';
  }
  if (isCollected.value) {
    return isVerified.value ? 'Verified' : 'Collected';
  }
  return 'Pending';
});

const badgeClasses = computed(() => {
  const base = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full';
  if (isVerified.value) return `${base} bg-green-100 text-green-800`;
  if (isCollected.value) return `${base} bg-blue-100 text-blue-800`;
  return `${base} bg-yellow-100 text-yellow-800`;
});
</script>
