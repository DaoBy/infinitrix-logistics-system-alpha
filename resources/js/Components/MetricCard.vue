<template>
  <div class="bg-white p-4 rounded-lg shadow border">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-500">{{ title }}</p>
        <p class="text-2xl font-semibold">{{ value }}</p>
      </div>
      <div v-if="icon" class="p-2 rounded-full" :class="iconBgColor">
        <component :is="dynamicIcon" class="h-6 w-6" :class="iconColor" />
      </div>
    </div>
    <div v-if="trend" class="mt-2 flex items-center">
      <ArrowTrendingUpIcon v-if="trend === 'up'" class="h-4 w-4 text-green-500" />
      <ArrowTrendingDownIcon v-else-if="trend === 'down'" class="h-4 w-4 text-red-500" />
      <span v-else class="h-4 w-4"></span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import {
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon,
  UserIcon,
  UserCircleIcon,
  TruckIcon,
  CheckIcon,
  XMarkIcon,
  CubeIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  title: String,
  value: [String, Number],
  icon: String,
  trend: String
});

const dynamicIcon = computed(() => {
  const icons = {
    users: UserIcon,
    user: UserCircleIcon,
    'user-check': CheckIcon,
    truck: TruckIcon,
    package: CubeIcon
  };
  return icons[props.icon] || UserIcon;
});

const iconBgColor = computed(() => {
  return 'bg-blue-50';
});

const iconColor = computed(() => {
  return 'text-blue-600';
});
</script>