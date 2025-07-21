<template>
  <EmployeeLayout>
    <template #header>
      <div
        v-if="$page.props.flash && $page.props.flash.info"
        class="mb-4 p-3 bg-blue-50 text-blue-800 rounded"
      >
        {{ $page.props.flash.info }}
      </div>
      <div class="flex justify-between items-center px-6 print:hidden">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Truck Manifest: {{ manifest.manifest_number }}
        </h2>
        <div class="flex space-x-2">
          <PrimaryButton 
            v-if="manifest.status === 'draft'" 
            @click="finalizeManifest" 
            class="inline-flex items-center"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Finalize Manifest
          </PrimaryButton>
          <PrimaryButton @click="printManifest" class="inline-flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Print Manifest
          </PrimaryButton>
          <SecondaryButton @click="goBack" class="inline-flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Back to Manifests
          </SecondaryButton>
        </div>
      </div>
    </template>

    <div class="px-6 py-4 print:p-0">
      <div class="printable-area bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 max-w-7xl mx-auto print:border-0 print:shadow-none">
        <div class="p-6 print:p-0">
          <!-- Manifest Header -->
          <div class="border-b border-gray-200 dark:border-gray-700 pb-4 mb-4 print:border-b-0">
            <div class="flex justify-between items-start">
              <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">INFINITRIX EXPRESS CARGO</h1>
                <p class="text-lg text-gray-600 dark:text-gray-300">TRUCK MANIFEST</p>
              </div>
              <div class="text-right">
                <p class="text-sm text-gray-500 dark:text-gray-400">Date: <span class="font-medium text-gray-900 dark:text-gray-100">{{ currentDate }}</span></p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Manifest #: <span class="font-medium text-gray-900 dark:text-gray-100">{{ manifest.manifest_number }}</span></p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Status: <span :class="statusClasses(manifest.status)" class="font-medium">{{ manifest.status }}</span></p>
              </div>
            </div>
          </div>

          <!-- Truck Information -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Truck</p>
              <p class="text-sm text-gray-900 dark:text-gray-100">{{ truck.make }} {{ truck.model }} ({{ truck.license_plate }})</p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Driver</p>
              <p class="text-sm text-gray-900 dark:text-gray-100">{{ driverName }} (ID: {{ driverEmployeeId }})</p>
            </div>
          </div>

          <!-- Packages Table -->
          <div v-if="packages.length > 0" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
              <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    DO #
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Category
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Package Name
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Municipality
                  </th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Waybill #
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-for="pkg in packages" :key="pkg.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                    DO-{{ pkg.delivery_order_id.toString().padStart(6, '0') }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                    {{ pkg.category }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                    {{ pkg.item_name }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                    {{ pkg.drop_off_region || 'N/A' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                    {{ pkg.waybill_number || 'Pending' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="p-4 text-center text-gray-500">
            No packages assigned to this truck
          </div>

          <!-- Footer -->
          <div class="mt-8 pt-4 border-t border-gray-200 dark:border-gray-700 grid grid-cols-2 gap-4 print:border-t-0">
            <div>
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Driver's Signature</p>
              <div class="mt-8 h-16 border-b border-gray-300 dark:border-gray-600"></div>
            </div>
            <div class="text-right">
              <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Dispatcher's Signature</p>
              <div class="mt-8 h-16 border-b border-gray-300 dark:border-gray-600"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Finalize Error Modal -->
    <Modal :show="showFinalizeErrorModal" @close="showFinalizeErrorModal = false" max-width="md">
      <div class="p-8 flex flex-col items-center text-center">
        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-red-100 mb-4">
          <svg class="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </div>
        <h2 class="text-2xl font-semibold text-red-700 dark:text-red-400 mb-2">Cannot Finalize Manifest</h2>
        <p class="text-base text-gray-700 dark:text-gray-300 mb-2">
          There was a problem finalizing this manifest. Please review the details below and resolve any issues before trying again.
        </p>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
          {{ finalizeError.message }}
        </p>
        <ul v-if="finalizeError.missing_packages.length" class="mt-2 mb-4 list-disc pl-5 text-sm text-gray-600 dark:text-gray-400 text-left w-full max-w-xl mx-auto">
          <li v-for="(pkg, idx) in finalizeError.missing_packages" :key="idx">
            <span class="font-semibold">{{ pkg.item_name }}</span> ({{ pkg.category }}) - {{ pkg.drop_off_region }}<span v-if="pkg.waybill_status"> - Waybill: <span class="font-semibold">{{ pkg.waybill_status }}</span></span>
          </li>
        </ul>
        <div class="mt-4 flex justify-center w-full">
          <PrimaryButton @click="showFinalizeErrorModal = false">Close</PrimaryButton>
        </div>
      </div>
    </Modal>

    <!-- Success Modal -->
    <Modal :show="showSuccessModal" @close="showSuccessModal = false" max-width="md">
      <div class="p-8 flex flex-col items-center text-center">
        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-green-100 mb-4">
          <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h2 class="text-2xl font-semibold text-green-700 mb-2">Manifest Finalized Successfully</h2>
        <p class="text-base text-gray-700 dark:text-gray-300 mb-2">
          The manifest has been finalized and is now ready for further processing.
        </p>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
          {{ successMessage }}
        </p>
        <div class="mt-4 flex justify-center w-full">
          <PrimaryButton @click="showSuccessModal = false">Close</PrimaryButton>
        </div>
      </div>
    </Modal>

  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ref, computed, watch, onMounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const props = defineProps({
  manifest: {
    type: Object,
    required: true
  },
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
  }
});

const page = usePage();
const successMessage = computed(() => page.props.flash?.success || null);

const showFinalizeErrorModal = ref(false);
const showSuccessModal = ref(false);
const finalizeError = ref({
  message: '',
  missing_packages: []
});

watch(successMessage, (val) => {
  if (val) showSuccessModal.value = true;
});

const currentDate = computed(() => {
  return new Date().toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
});

const driverName = computed(() => {
  return props.driver?.name || 'Not assigned';
});

const driverEmployeeId = computed(() => {
  return props.driver?.employee_id || 'N/A';
});

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

const printManifest = () => {
  window.open(route('manifests.print', props.manifest.id), '_blank');
};

const finalizeManifest = () => {
  // Check for missing or pending waybills
  const missing = props.packages.filter(pkg => !pkg.waybill_number || pkg.waybill_number === 'Pending');
  if (missing.length > 0) {
    finalizeError.value = {
      message: 'Some packages are missing valid waybills. Please resolve before finalizing.',
      missing_packages: missing.map(pkg => ({
        item_name: pkg.item_name,
        category: pkg.category,
        drop_off_region: pkg.drop_off_region,
        waybill_status: pkg.waybill_number || 'none'
      }))
    };
    showFinalizeErrorModal.value = true;
    return;
  }

  // If all waybills are present, proceed to finalize
  router.post(route('manifests.finalize', props.manifest.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      // Optional: show success notification
      showSuccessModal.value = true;
    },
    onError: (errors) => {
      alert('Failed to finalize manifest. Please try again.');
    }
  });
};

const goBack = () => {
  router.visit(route('manifests.index'));
};

onMounted(() => {
  // No-op for error modal on mount since we now handle errors only on frontend
});
</script>

<style scoped>
</style>