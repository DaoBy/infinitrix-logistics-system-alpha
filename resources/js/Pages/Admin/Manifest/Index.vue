<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-6">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Manifests Management
        </h2>
        <div class="flex space-x-2">
          <SearchInput v-model="search" placeholder="Search manifests..." />
          <PrimaryButton @click="refreshManifests" class="inline-flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
            </svg>
            Refresh
          </PrimaryButton>
        </div>
      </div>
    </template>

    <div class="px-6 py-4 space-y-6">
      <!-- Trucks with Assignments Section -->
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">
            Trucks with Assignments
          </h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Trucks that have delivery orders ready for manifest creation
          </p>
        </div>
        
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Truck
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Driver
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Delivery Orders
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Packages
                </th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="truck in trucksWithAssignments" :key="truck.id">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ truck.make }} {{ truck.model }} ({{ truck.license_plate }})
                  </div>
                  <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ truck.status }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900 dark:text-gray-100" v-if="truck.driver">
                    {{ truck.driver.name }}
                  </div>
                  <div class="text-sm text-gray-500 dark:text-gray-400" v-else>
                    Not assigned
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                  {{ truck.delivery_orders_count }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                  {{ truck.packages_count }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <PrimaryButton 
                    @click="createManifest(truck.id)" 
                    class="inline-flex items-center"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Create Manifest
                  </PrimaryButton>
                </td>
              </tr>
              <tr v-if="trucksWithAssignments.length === 0">
                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                  No trucks with assignments found
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Generated Manifests Section -->
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">
              Generated Manifests
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              All manifests that have been generated
            </p>
          </div>
          <!-- You can add filters/sort here if needed, similar to Waybills -->
        </div>
        
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Manifest #
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Truck
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Driver
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Packages
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Status
                </th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="manifest in limitedManifests" :key="manifest.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ manifest.manifest_number }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                  {{ manifest.truck?.license_plate || 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                  {{ manifest.driver?.name || 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                  {{ manifest.package_ids?.length || 0 }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="statusClasses(manifest.status)">
                    {{ manifest.status }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex justify-end space-x-2">
                    <SecondaryButton @click="viewManifest(manifest)" class="inline-flex items-center">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                      </svg>
                      View
                    </SecondaryButton>
                    <PrimaryButton @click="printManifest(manifest)" class="inline-flex items-center">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                      </svg>
                      Print
                    </PrimaryButton>
                  </div>
                </td>
              </tr>
              <tr v-if="limitedManifests.length === 0">
                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                  No manifests found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Inline Pagination Controls for manifests -->
        <div class="bg-white dark:bg-gray-800 px-4 py-3 flex items-center justify-center border-t border-gray-200 dark:border-gray-700 sm:px-6">
          <div class="flex items-center space-x-2">
            <button
              @click="goToManifestsPage(manifests.current_page - 1)"
              :disabled="manifests.current_page <= 1"
              class="px-3 py-1 rounded border text-sm"
            >Previous</button>
            <span>Page {{ manifests.current_page }} of {{ manifests.last_page }}</span>
            <button
              @click="goToManifestsPage(manifests.current_page + 1)"
              :disabled="manifests.current_page >= manifests.last_page"
              class="px-3 py-1 rounded border text-sm"
            >Next</button>
          </div>
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SearchInput from '@/Components/SearchInput.vue';
import { router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
  manifests: {
    type: Object,
    required: true,
    default: () => ({
      data: [],
      links: [],
      current_page: 1,
      last_page: 1
    })
  },
  trucksWithAssignments: {
    type: Array,
    required: true,
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({})
  }
});

const search = ref(props.filters.search);

watch(search, debounce((value) => {
  router.get(route('manifests.index'), { search: value }, {
    preserveState: true,
    replace: true
  });
}, 300));

const statusClasses = (status) => {
  const base = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full';
  
  switch (status) {
    case 'draft':
      return `${base} bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200`;
    case 'finalized':
      return `${base} bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200`;
    default:
      return `${base} bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300`;
  }
};

const refreshManifests = () => {
  router.get(route('manifests.index'), {}, {
    preserveState: true,
    replace: true,
    only: ['manifests', 'trucksWithAssignments']
  });
};

const viewManifest = (manifest) => {
  router.visit(route('manifests.show', manifest.id));
};

const printManifest = (manifest) => {
  window.open(route('manifests.print', manifest.id), '_blank');
};

const createManifest = (truckId) => {
  const draftManifest = props.manifests.data.find(
    m => m.truck?.id === truckId && m.status === 'draft'
  );
  if (draftManifest) {
    router.visit(route('manifests.show', draftManifest.id), {
      preserveScroll: true,
      preserveState: true,
      only: ['manifest', 'truck', 'driver', 'packages'],
      onSuccess: () => {
        // Optionally show a notification
      }
    });
  } else {
    router.visit(route('manifests.create', { truck: truckId }));
  }
};

function goToManifestsPage(page) {
  if (page >= 1 && page <= props.manifests.last_page) {
    router.get(route('manifests.index'), { ...props.filters, page }, {
      preserveState: true,
      replace: true,
    });
  }
}

const limitedManifests = computed(() => {
  return props.manifests.data ? props.manifests.data.slice(0, 5) : [];
});
</script>