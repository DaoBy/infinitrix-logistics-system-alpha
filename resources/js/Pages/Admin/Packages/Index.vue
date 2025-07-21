<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-6">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Package Monitoring</h2>
        <div class="flex space-x-2">
          <SecondaryButton 
            v-if="$page.props.auth.user.is_admin"
            @click="showStatusFilters = !showStatusFilters" 
            class="inline-flex items-center"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
            </svg>
            {{ showStatusFilters ? 'Hide Tools' : 'Status Tools' }}
          </SecondaryButton>
          <SecondaryButton @click="refreshData" class="inline-flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Refresh
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

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 mb-6">
        <div class="p-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <InputLabel for="search" value="Search Packages" />
              <TextInput
                id="search"
                v-model="search"
                type="text"
                class="mt-1 block w-full"
                placeholder="Search by item code, name, or waybill"
              />
            </div>
            <div>
              <InputLabel for="category" value="Filter by Category" />
              <select
                id="category"
                v-model="categoryFilter"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 dark:focus:border-blue-500"
              >
                <option value="">All Categories</option>
                <option v-for="category in categories" :key="category.value" :value="category.value">
                  {{ category.title }}
                </option>
              </select>
            </div>
            <div>
              <InputLabel for="status" value="Filter by Status" />
              <select
                id="status"
                v-model="statusFilter"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100 dark:focus:border-blue-500"
              >
                <option value="">All Statuses</option>
                <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                  {{ status.title }}
                </option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Bulk Status Actions -->
      <div v-if="showStatusFilters && $page.props.auth.user.is_admin" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 mb-6">
        <div class="p-4 bg-blue-50 dark:bg-blue-900/30 border-b border-gray-200 dark:border-gray-700">
          <h3 class="font-medium text-gray-900 dark:text-gray-100 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
              <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
              <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
            </svg>
            Bulk Status Actions
          </h3>
        </div>
        <div class="p-4">
          <div class="flex flex-wrap gap-2">
            <button
              v-for="status in availableStatuses"
              :key="status.value"
              @click="applyBulkStatus(status.value)"
              class="px-3 py-1.5 text-sm rounded-md flex items-center"
              :class="statusButtonClass(status.value)"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                <path :d="getStatusIconPath(status.value)" />
              </svg>
              Mark as {{ status.title }}
            </button>
          </div>
        </div>
      </div>

      <!-- Packages Table -->
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Item Code</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Item Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Category</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Location</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Waybill</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="pkg in filteredPackages" :key="pkg.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ pkg.item_code }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900 dark:text-gray-100">{{ pkg.item_name }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="categoryBadgeClass(pkg.category)">
                    {{ pkg.category || 'N/A' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="statusBadgeClass(pkg.status)">
                    {{ statusLabels[pkg.status] || 'N/A' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900 dark:text-gray-100">
                    {{ pkg.current_region?.name || 'N/A' }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                  {{ pkg.waybill_number || 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex space-x-2 justify-end">
                    <button @click="viewPackage(pkg.id)" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                    </button>
                    <button v-if="$page.props.auth.user.is_admin" @click="openStatusModal(pkg)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredPackages.length === 0">
                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                  No packages found matching your criteria
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Status Modal -->
      <Modal :show="showStatusModal" @close="closeStatusModal" max-width="md">
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
                <option v-for="status in availableStatuses" :key="status.value" :value="status.value">
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
            <SecondaryButton @click="closeStatusModal">
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
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import DataTable from '@/Components/DataTable.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  packages: Array,
  status: String,
  success: String,
  error: String,
});

const search = ref('');
const categoryFilter = ref('');
const statusFilter = ref('');
const selectedPackages = ref([]);
const selectAll = ref(false);
const showStatusFilters = ref(false);
const showStatusModal = ref(false);
const currentPackage = ref(null);

const columns = [
  { field: 'item_code', header: 'Item Code', sortable: true },
  { field: 'item_name', header: 'Item Name', sortable: true },
  { 
    field: 'category', 
    header: 'Category', 
    sortable: true,
    format: (value) => value // Directly use the value without formatter object
  },
  { 
    field: 'status', 
    header: 'Status', 
    sortable: true, 
    format: (value) => statusLabels[value] // Directly use the status label
  },
  { 
    field: 'current_region.name', 
    header: 'Location', 
    sortable: true,
    format: (value) => value || 'N/A' // Directly use the value
  },
  { field: 'waybill_number', header: 'Waybill', sortable: true },
  { field: 'actions', header: 'Actions' }
];

const categories = [
  { value: 'piece', title: 'Piece' },
  { value: 'carton', title: 'Carton' },
  { value: 'sack', title: 'Sack' },
  { value: 'bundle', title: 'Bundle' },
  { value: 'roll', title: 'Roll' },
  { value: 'B/R', title: 'Bundle/Roll' },
  { value: 'C/S', title: 'Carton/Sack' },
];

const statusOptions = [
  { value: 'preparing', title: 'Preparing' },
  { value: 'ready_for_pickup', title: 'Ready for Pickup' },
  { value: 'loaded', title: 'Loaded' },
  { value: 'in_transit', title: 'In Transit' },
  { value: 'delivered', title: 'Delivered' },
  { value: 'completed', title: 'Completed' },
  { value: 'returned', title: 'Returned' },
  { value: 'rejected', title: 'Rejected' }
];

const availableStatuses = [
  { value: 'preparing', title: 'Preparing' },
  { value: 'ready_for_pickup', title: 'Ready for Pickup' },
  { value: 'loaded', title: 'Loaded' },
  { value: 'in_transit', title: 'In Transit' },
  { value: 'delivered', title: 'Delivered' },
  { value: 'completed', title: 'Completed' },
  { value: 'returned', title: 'Returned' }
];

const statusLabels = {
  preparing: 'Preparing',
  ready_for_pickup: 'Ready for Pickup',
  loaded: 'Loaded',
  in_transit: 'In Transit',
  delivered: 'Delivered',
  completed: 'Completed',
  returned: 'Returned',
  rejected: 'Rejected'
};

const filteredPackages = computed(() => {
  if (!props.packages) return [];
  
  return props.packages.filter(pkg => {
    const matchesSearch = search.value === '' || 
      pkg.item_code.toLowerCase().includes(search.value.toLowerCase()) ||
      pkg.item_name.toLowerCase().includes(search.value.toLowerCase()) ||
      (pkg.waybill_number && pkg.waybill_number.toLowerCase().includes(search.value.toLowerCase()));
    
    const matchesCategory = categoryFilter.value === '' || 
      pkg.category === categoryFilter.value;
    
    const matchesStatus = statusFilter.value === '' || 
      pkg.status === statusFilter.value;
    
    return matchesSearch && matchesCategory && matchesStatus;
  });
});

const statusForm = useForm({
  package_id: null,
  status: '',
  remarks: ''
});

function refreshData() {
  router.reload();
}

function viewPackage(id) {
  router.get(route('driver.track-package', id));
}

function openStatusModal(pkg) {
  currentPackage.value = pkg;
  statusForm.package_id = pkg.id;
  statusForm.status = pkg.status;
  statusForm.remarks = '';
  showStatusModal.value = true;
}

function closeStatusModal() {
  showStatusModal.value = false;
  statusForm.reset();
  currentPackage.value = null;
}

function submitStatusUpdate() {
  statusForm.put(route('admin.packages.mark-status', currentPackage.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      closeStatusModal();
    }
  });
}

function applyBulkStatus(status) {
  if (selectedPackages.value.length === 0) {
    alert('Please select at least one package');
    return;
  }

  if (confirm(`Are you sure you want to update ${selectedPackages.value.length} package(s) to ${status}?`)) {
    router.post(route('admin.packages.bulk-status'), {
      package_ids: selectedPackages.value.map(p => p.id),
      status: status
    }, {
      preserveScroll: true,
    });
  }
}

function statusButtonClass(status) {
  switch (status) {
    case 'preparing': return 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600';
    case 'ready_for_pickup': return 'bg-blue-100 text-blue-800 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/40';
    case 'loaded': return 'bg-indigo-100 text-indigo-800 hover:bg-indigo-200 dark:bg-indigo-900/30 dark:text-indigo-300 dark:hover:bg-indigo-900/40';
    case 'in_transit': return 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-300 dark:hover:bg-yellow-900/40';
    case 'delivered': return 'bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-300 dark:hover:bg-green-900/40';
    case 'completed': return 'bg-teal-100 text-teal-800 hover:bg-teal-200 dark:bg-teal-900/30 dark:text-teal-300 dark:hover:bg-teal-900/40';
    case 'returned': return 'bg-purple-100 text-purple-800 hover:bg-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:hover:bg-purple-900/40';
    default: return 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600';
  }
}

function statusBadgeClass(status) {
  switch (status) {
    case 'preparing': return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
    case 'ready_for_pickup': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
    case 'loaded': return 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300';
    case 'in_transit': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
    case 'delivered': return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
    case 'completed': return 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-300';
    case 'returned': return 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300';
    case 'rejected': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
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

function getStatusIconPath(status) {
  switch (status) {
    case 'preparing': return 'M10 2a1 1 0 00-1 1v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z';
    case 'ready_for_pickup': return 'M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-1a1 1 0 011-1h2a1 1 0 011 1v1a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H19a1 1 0 001-1V5a1 1 0 00-1-1H3z';
    case 'loaded': return 'M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z';
    case 'in_transit': return 'M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-1a1 1 0 011-1h2a1 1 0 011 1v1a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H19a1 1 0 001-1V5a1 1 0 00-1-1H3z';
    case 'delivered': return 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z';
    case 'completed': return 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z';
    case 'returned': return 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4';
    default: return 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
  }
}
</script>