<template>
  <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
          {{ title }}
        </p>
        <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
          {{ value }}
        </p>
      </div>
      <div class="flex-shrink-0">
        <div class="p-3 rounded-full" :class="iconBgColor">
          <component :is="iconComponent" class="h-6 w-6" :class="iconColor" />
        </div>
      </div>
    </div>
    <div v-if="trend" class="mt-2 flex items-center">
      <span :class="trendArrowClass" aria-hidden="true" />
      <span class="text-xs font-medium" :class="trendTextClass">
        {{ trendValue }}%
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: String,
  value: [String, Number],
  icon: {
    type: String,
    default: 'chart-bar'
  },
  trend: {
    type: String,
    validator: (value) => ['up', 'down', 'neutral'].includes(value)
  },
  trendValue: {
    type: [String, Number],
    default: 0
  }
});

const icons = {
  truck: 'TruckIcon',
  'clipboard-check': 'ClipboardCheckIcon',
  clock: 'ClockIcon',
  'check-circle': 'CheckCircleIcon',
  'chart-bar': 'ChartBarIcon'
};

const iconComponent = computed(() => icons[props.icon] || icons['chart-bar']);

const iconBgColor = computed(() => {
  const colors = {
    truck: 'bg-blue-100 dark:bg-blue-900/30',
    'clipboard-check': 'bg-green-100 dark:bg-green-900/30',
    clock: 'bg-yellow-100 dark:bg-yellow-900/30',
    'check-circle': 'bg-indigo-100 dark:bg-indigo-900/30'
  };
  return colors[props.icon] || 'bg-gray-100 dark:bg-gray-700';
});

const iconColor = computed(() => {
  const colors = {
    truck: 'text-blue-600 dark:text-blue-300',
    'clipboard-check': 'text-green-600 dark:text-green-300',
    clock: 'text-yellow-600 dark:text-yellow-300',
    'check-circle': 'text-indigo-600 dark:text-indigo-300'
  };
  return colors[props.icon] || 'text-gray-600 dark:text-gray-300';
});

const trendArrowClass = computed(() => {
  return {
    'up': 'text-green-500 h-4 w-4 flex-shrink-0',
    'down': 'text-red-500 h-4 w-4 flex-shrink-0',
    'neutral': 'text-gray-500 h-4 w-4 flex-shrink-0'
  }[props.trend];
});

const trendTextClass = computed(() => {
  return {
    'up': 'text-green-600 dark:text-green-400',
    'down': 'text-red-600 dark:text-red-400',
    'neutral': 'text-gray-600 dark:text-gray-400'
  }[props.trend];
});
</script>