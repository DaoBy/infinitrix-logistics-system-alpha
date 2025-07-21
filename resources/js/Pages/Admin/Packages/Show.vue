<template>
  <EmployeeLayout>
<template #header>
  <div class="flex justify-between items-center px-6">
    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
      Package Details: {{ package.item_name }}
      <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">
        ({{ package.item_code }})
      </span>
    </h2>
    <div class="flex space-x-2">
      <PrimaryButton 
        @click="showTransferModal = true"
        class="inline-flex items-center"
      >
        <!-- icon -->
        Transfer
      </PrimaryButton>
      <PrimaryButton 
        @click="openStatusModal"
        class="inline-flex items-center"
      >
        <!-- icon -->
        Update Status
      </PrimaryButton>
      <SecondaryButton @click="$inertia.visit(route('admin.packages.index'))" class="inline-flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Back to List
      </SecondaryButton>
    </div>
  </div>
</template>

    <div class="px-6 py-4">
      <!-- Status Messages -->
      <div v-if="status || success || error" class="mb-6 max-w-7xl mx-auto">
        <div v-if="status" class="p-4 bg-blue-100 text-blue-800 rounded-lg border border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800">
          <div class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            {{ status }}
          </div>
        </div>
        <div v-if="success" class="p-4 bg-green-100 text-green-800 rounded-lg border border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-800">
          <div class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            {{ success }}
          </div>
        </div>
        <div v-if="error" class="p-4 bg-red-100 text-red-800 rounded-lg border border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800">
          <div class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            {{ error }}
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 max-w-7xl mx-auto">
        <!-- Package Information -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
          <div class="p-4 bg-blue-50 dark:bg-blue-900/30 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-medium text-gray-900 dark:text-gray-100 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
              </svg>
              Package Information
            </h3>
          </div>
          <div class="p-4">
            <dl class="divide-y divide-gray-200 dark:divide-gray-700">
              <div class="py-4 grid grid-cols-3 gap-4">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Item Code</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 col-span-2">{{ package.item_code }}</dd>
              </div>
              <div class="py-4 grid grid-cols-3 gap-4">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Item Name</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 col-span-2">{{ package.item_name }}</dd>
              </div>
              <div class="py-4 grid grid-cols-3 gap-4">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Category</dt>
                <dd class="mt-1 text-sm col-span-2">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="categoryBadgeClass(package.category)">
                    {{ package.category }}
                  </span>
                </dd>
              </div>
              <div class="py-4 grid grid-cols-3 gap-4">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Quantity</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 col-span-2">{{ package.quantity }}</dd>
              </div>
              <div class="py-4 grid grid-cols-3 gap-4">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created At</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 col-span-2">{{ new Date(package.created_at).toLocaleString() }}</dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Status & Location -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
          <div class="p-4 bg-blue-50 dark:bg-blue-900/30 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-medium text-gray-900 dark:text-gray-100 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
              </svg>
              Status & Location
            </h3>
          </div>
          <div class="p-4">
            <dl class="divide-y divide-gray-200 dark:divide-gray-700">
              <div class="py-4 grid grid-cols-3 gap-4">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                <dd class="mt-1 text-sm col-span-2 flex items-center justify-between">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="statusBadgeClass(package.status)">
                    {{ package.status }}
                  </span>
                  <button 
                    @click="openStatusModal"
                    class="text-xs text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                  >
                    Update
                  </button>
                </dd>
              </div>
              <div class="py-4 grid grid-cols-3 gap-4">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Location</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 col-span-2">
                  <div v-if="package.current_region">
                    {{ package.current_region.name }}
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      {{ package.current_region.warehouse_address }}
                    </div>
                  </div>
                  <span v-else class="text-gray-500 dark:text-gray-400">N/A</span>
                </dd>
              </div>
            </dl>
          </div>
        </div>
      </div>

      <!-- Transfer History -->
      <div class="mt-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 max-w-7xl mx-auto">
        <div class="p-4 bg-blue-50 dark:bg-blue-900/30 border-b border-gray-200 dark:border-gray-700">
          <h3 class="font-medium text-gray-900 dark:text-gray-100 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
              <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
              <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-1a1 1 0 011-1h2a1 1 0 011 1v1a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H19a1 1 0 001-1V5a1 1 0 00-1-1H3z" />
            </svg>
            Transfer History
          </h3>
        </div>
        <div class="p-4">
          <div v-if="package.transfers?.length" class="space-y-4">
            <div v-for="transfer in package.transfers" :key="transfer.id" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
              <div class="flex justify-between items-start">
                <div>
                  <h4 class="font-medium text-gray-900 dark:text-gray-100">
                    {{ transfer.from_region.name }} → {{ transfer.to_region.name }}
                  </h4>
                  <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Processed by: {{ transfer.processor.name }}
                  </div>
                  <div v-if="transfer.remarks" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Notes: {{ transfer.remarks }}
                  </div>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                  {{ new Date(transfer.created_at).toLocaleString() }}
                </div>
              </div>
              <div v-if="transfer.arrived_at" class="mt-2 text-sm text-green-600 dark:text-green-400 flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                Arrived at: {{ new Date(transfer.arrived_at).toLocaleString() }}
              </div>
              <div v-else-if="$page.props.auth.user.is_admin" class="mt-2">
                <PrimaryButton @click="markAsArrived(transfer)" size="xs">
                  Mark as Arrived
                </PrimaryButton>
              </div>
            </div>
          </div>
          <div v-else class="text-center text-gray-500 dark:text-gray-400 py-4">
            No transfer history available for this package
          </div>

          <div class="mt-4">
            <PrimaryButton @click="showTransferModal = true">
              Transfer Package
            </PrimaryButton>
          </div>
        </div>
      </div>

      <!-- Status History -->
      <div class="mt-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 max-w-7xl mx-auto">
        <div class="p-4 bg-blue-50 dark:bg-blue-900/30 border-b border-gray-200 dark:border-gray-700">
          <h3 class="font-medium text-gray-900 dark:text-gray-100 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
            </svg>
            Status History
          </h3>
        </div>
        <div class="p-4">
          <div v-if="package.status_history?.length" class="space-y-4">
            <div v-for="history in package.status_history" :key="history.id" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
              <div class="flex justify-between items-start">
                <div>
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="statusBadgeClass(history.status)">
                    {{ history.status }}
                  </span>
                  <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Updated by: {{ history.updated_by?.name || 'System' }}
                  </div>
                  <div v-if="history.remarks" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Remarks: {{ history.remarks }}
                  </div>
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                  {{ new Date(history.created_at).toLocaleString() }}
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-center text-gray-500 dark:text-gray-400 py-4">
            No status history available for this package
          </div>
        </div>
      </div>

      <!-- Transfer Modal -->
      <Modal :show="showTransferModal" @close="showTransferModal = false" max-width="md">
        <div class="p-6">
          <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
              <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
              <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-1a1 1 0 011-1h2a1 1 0 011 1v1a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H19a1 1 0 001-1V5a1 1 0 00-1-1H3z" />
            </svg>
            Transfer Package
          </h2>
          
          <div class="space-y-4">
            <div>
              <InputLabel for="to_region_id" value="Destination Branch *" />
              <select
                id="to_region_id"
                v-model="transferForm.to_region_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 dark:focus:border-blue-500"
                required
              >
                <option disabled value="">Select a destination</option>
                <option v-for="region in availableRegions" :key="region.id" :value="region.id">
                  {{ region.name }}
                </option>
              </select>
              <InputError class="mt-2" :message="transferForm.errors.to_region_id" />
            </div>

            <div>
              <InputLabel for="remarks" value="Notes" />
              <textarea
                id="remarks"
                rows="3"
                v-model="transferForm.remarks"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 dark:focus:border-blue-500"
              ></textarea>
              <InputError class="mt-2" :message="transferForm.errors.remarks" />
            </div>
          </div>

          <div class="mt-6 flex justify-end space-x-4">
            <SecondaryButton @click="showTransferModal = false">
              Cancel
            </SecondaryButton>
            <PrimaryButton @click="submitTransfer" :disabled="transferForm.processing">
              <svg v-if="transferForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ transferForm.processing ? 'Processing...' : 'Confirm Transfer' }}
            </PrimaryButton>
          </div>
        </div>
      </Modal>

      <!-- Status Update Modal -->
      <Modal :show="showStatusModal" @close="showStatusModal = false" max-width="md">
        <div class="p-6">
          <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
            Update Package Status
          </h2>
          
          <div class="space-y-4">
            <div>
              <InputLabel for="status" value="New Status *" />
              <select
                id="status"
                v-model="statusForm.status"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 dark:focus:border-blue-500"
                required
              >
                <option disabled value="">Select a status</option>
                <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                  {{ status.title }}
                </option>
              </select>
              <InputError class="mt-2" :message="statusForm.errors.status" />
            </div>

            <div>
              <InputLabel for="remarks" value="Remarks" />
              <textarea
                id="remarks"
                rows="3"
                v-model="statusForm.remarks"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 dark:focus:border-blue-500"
              ></textarea>
              <InputError class="mt-2" :message="statusForm.errors.remarks" />
            </div>
          </div>

          <div class="mt-6 flex justify-end space-x-4">
            <SecondaryButton @click="showStatusModal = false">
              Cancel
            </SecondaryButton>
            <PrimaryButton @click="submitStatusUpdate" :disabled="statusForm.processing">
              <svg v-if="statusForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ statusForm.processing ? 'Updating...' : 'Update Status' }}
            </PrimaryButton>
          </div>
        </div>
      </Modal>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  package: Object,
  regions: Array,
  status: String,
  success: String,
  error: String,
});

const showTransferModal = ref(false);
const showStatusModal = ref(false);
const transferForm = useForm({
  to_region_id: null,
  remarks: ''
});

const statusForm = useForm({
  status: '',
  remarks: ''
});

const statusOptions = [
  { value: 'preparing', title: 'Preparing' },
  { value: 'loaded', title: 'Loaded' },
  { value: 'in_transit', title: 'In Transit' },
  { value: 'delivered', title: 'Delivered' },
  { value: 'completed', title: 'Completed' },
  { value: 'returned', title: 'Returned' },
];

const availableRegions = computed(() => {
  return props.regions.filter(region => region.id !== props.package.current_region_id);
});

function markAsArrived(transfer) {
  if (confirm('Mark this transfer as arrived at destination?')) {
    router.post(route('admin.packages.transfers.arrived', transfer.id), {
      preserveScroll: true,
    });
  }
}

function openStatusModal() {
  statusForm.status = props.package.status;
  statusForm.remarks = '';
  showStatusModal.value = true;
}

function submitStatusUpdate() {
  statusForm.put(route('admin.packages.mark-status', props.package.id), {
    preserveScroll: true,
    onSuccess: () => {
      showStatusModal.value = false;
    }
  });
}

function submitTransfer() {
  transferForm.post(route('admin.packages.transfer', props.package.id), {
    preserveScroll: true,
    onSuccess: () => {
      showTransferModal.value = false;
      transferForm.reset();
    }
  });
}

function statusBadgeClass(status) {
  switch (status) {
    case 'preparing': return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
    case 'loaded': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
    case 'in_transit': return 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300';
    case 'delivered': return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
    case 'completed': return 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-300';
    case 'returned': return 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300';
    default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
  }
}

function categoryBadgeClass(category) {
  switch (category) {
    case 'piece': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
    case 'carton': return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
    case 'sack': return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
    case 'bundle': return 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300';
    case 'roll': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
    case 'B/R': return 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300';
    case 'C/S': return 'bg-pink-100 text-pink-800 dark:bg-pink-900/30 dark:text-pink-300';
    default: return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
  }
}
</script>