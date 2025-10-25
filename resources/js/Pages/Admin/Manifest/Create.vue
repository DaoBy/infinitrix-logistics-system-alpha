<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-6">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Create Manifest for Truck: {{ truck.license_plate }}
        </h2>
        <div class="flex space-x-2">
          <SecondaryButton @click="goBack" class="inline-flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Back
          </SecondaryButton>
        </div>
      </div>
    </template>

    <div class="px-6 py-4">
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 max-w-7xl mx-auto">
        <div class="p-6">
          <form @submit.prevent="submitForm">
            <div class="grid grid-cols-1 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Manifest Number
                </label>
                <input 
                  type="text" 
                  v-model="form.manifest_number" 
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                  readonly
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Truck Information
                </label>
                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                  <p class="text-sm text-gray-900 dark:text-gray-100">{{ truck.make }} {{ truck.model }} ({{ truck.license_plate }})</p>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Driver Information
                </label>
                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                  <p class="text-sm text-gray-900 dark:text-gray-100">{{ driverName }} (ID: {{ driverEmployeeId }})</p>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Notes
                </label>
                <textarea 
                  v-model="form.notes" 
                  rows="3" 
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                  placeholder="Optional notes about this manifest..."
                ></textarea>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Packages to Include ({{ packages.length }})
                </label>
                <div class="overflow-x-auto">
             <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
    <thead class="bg-gray-50 dark:bg-gray-700">
        <tr>
            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                Item Code
            </th>
            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                Category
            </th>
            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                Package Name
            </th>
            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                Destination
            </th>
            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                Waybill #
            </th>
        </tr>
    </thead>
    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
        <tr v-for="pkg in packages" :key="pkg.id">
            <td class="px-4 py-2 whitespace-nowrap text-sm font-mono text-gray-900 dark:text-gray-100 font-medium">
                {{ pkg.item_code }}
            </td>
            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                {{ pkg.category }}
            </td>
            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                {{ pkg.item_name }}
            </td>
            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                {{ pkg.drop_off_region || 'N/A' }}
            </td>
            <td class="px-4 py-2 whitespace-nowrap text-sm text-green-600 font-semibold">
                {{ pkg.waybill_number }}
            </td>
        </tr>
    </tbody>
</table>
                </div>
              </div>

              <div class="flex justify-end">
                <PrimaryButton 
                  type="submit" 
                  class="inline-flex items-center"
                  :disabled="form.processing"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  <span v-if="form.processing">Creating...</span>
                  <span v-else>Create Finalized Manifest</span>
                </PrimaryButton>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  truck: {
    type: Object,
    required: true
  },
  driver: {
    type: Object,
    default: () => ({
      id: null,
      name: null,
      employee_id: null
    })
  },
  packages: {
    type: Array,
    default: () => []
  },
  manifestNumber: {
    type: String,
    required: true
  }
});

const form = useForm({
  manifest_number: props.manifestNumber,
  notes: ''
});

const driverName = computed(() => {
  return props.driver?.name || 'Not assigned';
});

const driverEmployeeId = computed(() => {
  return props.driver?.employee_id || 'N/A';
});

const submitForm = () => {
  form.post(route('manifests.store', { truck: props.truck.id }), {
    preserveScroll: true,
    onSuccess: () => {
      // Success handled by controller redirect
    },
    onError: (errors) => {
      console.error('Error creating manifest:', errors);
    }
  });
};

const goBack = () => {
  router.visit(route('manifests.index'));
};
</script>