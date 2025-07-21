<!-- resources/js/Pages/DriverTruckAssignments/Index.vue -->
<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex flex-wrap justify-between items-center gap-4 px-4 md:px-6 max-w-screen-xl mx-auto w-full">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
          Driver-Truck Assignments & Monitoring
        </h2>
        <div class="flex flex-wrap items-center gap-2">
          <SearchInput
            v-model="filters.search"
            placeholder="Search by Driver, Truck, or Region"
            @keyup.enter="handleFilterChange"
            @input="handleFilterChange"
            class="w-64"
          />
          <SecondaryButton @click="resetFilters">
            Reset
          </SecondaryButton>
          <PrimaryButton @click="showCreateModal = true">
            <PlusIcon class="w-4 h-4 mr-1" /> New Assignment
          </PrimaryButton>
        </div>
      </div>
    </template>

    <div class="py-6 px-2 md:px-6">
      <div class="bg-white shadow-sm rounded-lg overflow-hidden border border-gray-200 max-w-screen-xl mx-auto">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <h3 class="text-lg font-medium leading-6 text-gray-900">
              Driver-Truck Assignments & Monitoring
            </h3>
            <p class="mt-1 text-sm text-gray-500">
              Monitor and manage driver-truck assignments across regions.
            </p>
          </div>
          <div class="flex flex-wrap items-center gap-2 md:ml-auto">
            <SelectInput
              v-model="filters.region_id"
              :options="regionOptions"
              placeholder="Filter by Region"
              class="w-44"
              @change="handleFilterChange"
            />
          </div>
        </div>

        <!-- Assignments Table -->
        <div class="overflow-x-auto px-2 md:px-4">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Driver</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Truck</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Region</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned At</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Return Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="row in assignments.data" :key="row.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  <div class="flex items-center">
                    <UserCircleIcon class="h-5 w-5 text-gray-400 mr-2" />
                    <span>{{ row.driver?.name || 'N/A' }}</span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  <div class="flex items-center">
                    <TruckIcon class="h-5 w-5 text-gray-400 mr-2" />
                    <div>
                      <div>{{ row.truck?.license_plate || 'N/A' }}</div>
                      <div class="text-xs text-gray-500">
                        {{ row.truck ? `${row.truck.make} ${row.truck.model}` : '' }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ row.region?.name || 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span class="text-gray-400">{{ formatDate(row.assigned_at) }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span
                    :class="[
                      'px-2 py-1 rounded-full text-xs font-semibold',
                      row.return_status === 'Returned'
                        ? 'bg-emerald-100 text-emerald-800'
                        : row.return_status === 'Pending Verification'
                          ? 'bg-yellow-100 text-yellow-800'
                          : row.return_status === 'Verified'
                            ? 'bg-blue-100 text-blue-800'
                            : 'bg-gray-100 text-gray-500'
                    ]"
                  >
                    {{ row.return_status ?? 'Not Returned' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span
                    :class="[
                      'px-2 py-1 rounded-full text-xs font-semibold',
                      getTruckStatusColor(row.truck?.status) === 'green'
                        ? 'bg-green-100 text-green-700'
                        : getTruckStatusColor(row.truck?.status) === 'yellow'
                          ? 'bg-yellow-100 text-yellow-700'
                          : getTruckStatusColor(row.truck?.status) === 'blue'
                            ? 'bg-blue-100 text-blue-700'
                            : getTruckStatusColor(row.truck?.status) === 'indigo'
                              ? 'bg-indigo-100 text-indigo-700'
                              : getTruckStatusColor(row.truck?.status) === 'purple'
                                ? 'bg-purple-100 text-purple-700'
                                : getTruckStatusColor(row.truck?.status) === 'red'
                                  ? 'bg-red-100 text-red-700'
                                  : 'bg-gray-100 text-gray-500'
                    ]"
                  >
                    {{ getTruckStatusLabel(row.truck?.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex justify-end space-x-2">
                    <SecondaryButton 
                      @click="viewAssignment(row)"
                      size="xs"
                      title="View Details"
                    >
                      <EyeIcon class="w-3 h-3" />
                    </SecondaryButton>
                    <DangerButton 
                      v-if="row.is_active"
                      @click="confirmDeactivate(row)" 
                      size="xs"
                      title="Deactivate"
                    >
                      <TrashIcon class="w-3 h-3" />
                    </DangerButton>
                    <PrimaryButton 
                      v-else
                      @click="confirmReactivate(row)"
                      size="xs"
                      title="Reactivate"
                    >
                      <ArrowPathIcon class="w-3 h-3" />
                    </PrimaryButton>
                    <PrimaryButton 
                      v-if="row.return_status === 'Pending Verification'"
                      @click="verifyDriverReturn(row)"
                      size="xs"
                      title="Verify Return"
                    >
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                      </svg>
                    </PrimaryButton>
                  </div>
                </td>
              </tr>
              <tr v-if="!assignments.data || assignments.data.length === 0">
                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                  No assignments found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Standard Pagination Controls -->
        <div class="bg-white px-4 py-3 flex items-center justify-center border-t border-gray-200 sm:px-6">
          <div class="flex items-center space-x-2">
            <button
              @click="handlePageChange(assignments.current_page - 1)"
              :disabled="assignments.current_page <= 1"
              class="px-3 py-1 rounded border text-sm"
            >Previous</button>
            <span>Page {{ assignments.current_page }} of {{ assignments.last_page }}</span>
            <button
              @click="handlePageChange(assignments.current_page + 1)"
              :disabled="assignments.current_page >= assignments.last_page"
              class="px-3 py-1 rounded border text-sm"
            >Next</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Assignment Modal -->
    <Modal :show="showCreateModal" @close="closeCreateModal">
      <div class="p-6">
        <h2 class="text-lg font-medium mb-4">Create New Assignment</h2>
        <div class="space-y-4">
          <SelectInput
            label="Region"
            v-model="form.region_id"
            :options="regionOptions.filter(r => r.value)"
            required
            @change="loadAvailableResources"
          />
          <SelectInput
            label="Driver"
            v-model="form.driver_id"
            :options="driverOptions"
            required
            :disabled="!form.region_id"
            :loading="loadingDrivers"
          />
          <SelectInput
            label="Truck"
            v-model="form.truck_id"
            :options="truckOptions"
            required
            :disabled="!form.region_id"
            :loading="loadingTrucks"
          />
        </div>
        <div class="mt-6 flex justify-end space-x-4">
          <SecondaryButton @click="closeCreateModal">
            Cancel
          </SecondaryButton>
          <PrimaryButton 
            @click="submit" 
            :disabled="form.processing || !form.region_id"
            :loading="form.processing"
          >
            Create Assignment
          </PrimaryButton>
        </div>
      </div>
    </Modal>

    <!-- Assignment Detail Modal -->
    <Modal :show="showDetailModal" @close="showDetailModal = false" max-width="2xl">
      <div class="p-6" v-if="selectedAssignment">
        <h2 class="text-xl font-semibold mb-6 text-gray-800">Assignment Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Driver Information -->
          <div class="bg-gray-50 rounded-lg p-4 shadow-sm border">
            <h3 class="font-semibold text-gray-700 mb-3 border-b pb-2">Driver Information</h3>
            <div class="space-y-2">
              <div class="flex justify-between">
                <span class="text-gray-500">Name</span>
                <span class="font-medium text-gray-900">{{ selectedAssignment.driver?.name || 'N/A' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Employee ID</span>
                <span class="font-medium text-gray-900">{{ selectedAssignment.driver?.employee_profile?.employee_id || 'N/A' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Region</span>
                <span class="font-medium text-gray-900">{{ selectedAssignment.region?.name || 'N/A' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Assigned At</span>
                <span class="font-medium text-gray-900">{{ formatDate(selectedAssignment.assigned_at) }}</span>
              </div>
            </div>
          </div>
          <!-- Truck Information -->
          <div class="bg-gray-50 rounded-lg p-4 shadow-sm border">
            <h3 class="font-semibold text-gray-700 mb-3 border-b pb-2">Truck Information</h3>
            <div class="space-y-2">
              <div class="flex justify-between">
                <span class="text-gray-500">License Plate</span>
                <span class="font-medium text-gray-900">{{ selectedAssignment.truck?.license_plate || 'N/A' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Make/Model</span>
                <span class="font-medium text-gray-900">{{ `${selectedAssignment.truck?.make || ''} ${selectedAssignment.truck?.model || ''}`.trim() || 'N/A' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Status</span>
                <span class="font-medium text-gray-900">{{ getTruckStatusLabel(selectedAssignment.truck?.status) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Current Load</span>
                <span class="font-medium text-gray-900">{{ `${selectedAssignment.truck?.current_volume ?? 0}m³ ${selectedAssignment.truck?.current_weight ?? 0}kg` }}</span>
              </div>
            </div>
          </div>
        </div>
        <!-- Active Deliveries -->
        <div class="mt-8" v-if="selectedAssignment.driver?.delivery_orders?.length">
          <h3 class="font-semibold mb-3 text-gray-700">Active Deliveries</h3>
          <div class="space-y-3">
            <div v-for="order in selectedAssignment.driver.delivery_orders" :key="order.id" class="p-3 border rounded bg-white flex flex-col md:flex-row md:items-center md:justify-between">
              <div>
                <span class="font-medium text-gray-800">Order #{{ order.id }}</span>
                <div class="text-sm text-gray-500">
                  {{ order.delivery_request?.pick_up_region?.name }} → {{ order.delivery_request?.drop_off_region?.name }}
                </div>
              </div>
              <StatusBadge :status="order.status" class="mt-2 md:mt-0">{{ order.status }}</StatusBadge>
            </div>
          </div>
        </div>
        <!-- Modal Actions -->
        <div class="mt-8 flex justify-end space-x-2">
          <SecondaryButton @click="showDetailModal = false">
            Close
          </SecondaryButton>
          <DangerButton 
            v-if="selectedAssignment.is_active"
            @click="confirmDeactivate(selectedAssignment)" 
          >
            Deactivate
          </DangerButton>
          <PrimaryButton 
            v-else
            @click="confirmReactivate(selectedAssignment)"
          >
            Reactivate
          </PrimaryButton>
        </div>
      </div>
    </Modal>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import InfoRow from '@/Components/InfoRow.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import SearchInput from '@/Components/SearchInput.vue';
import { ref, computed, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { 
  EyeIcon,
  TrashIcon, 
  ArrowPathIcon,
  UserCircleIcon,
  TruckIcon,
  PlusIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  assignments: Object,
  regions: Array,
  metrics: Object,
  filters: Object
});

const showDetailModal = ref(false);
const showCreateModal = ref(false);
const showDeactivateModal = ref(false);
const showReactivateModal = ref(false);
const selectedAssignment = ref(null);
const assignmentToDeactivate = ref(null);
const assignmentToReactivate = ref(null);
const loading = ref(false);
const loadingDrivers = ref(false);
const loadingTrucks = ref(false);
const availableDrivers = ref([]);
const availableTrucks = ref([]);

const filters = ref({
  region_id: props.filters.region_id || '',
  status: props.filters.status || 'active',
  search: props.filters.search || ''
});

const form = useForm({
  driver_id: null,
  truck_id: null,
  region_id: null
});

const columns = [
  { field: 'driver', header: 'Driver', width: '200px' },
  { field: 'truck', header: 'Truck', width: '200px' },
  { field: 'region', header: 'Region', formatter: (value) => value?.name || 'N/A' },
  { field: 'assigned_at', header: 'Assigned At', formatter: (value) => formatDate(value) },
  { 
    field: 'return_status', 
    header: 'Return Status', 
    formatter: (value) => value ?? 'Not Returned'
  },
  { field: 'status', header: 'Status', width: '150px' },
  { field: 'actions', header: 'Actions', width: '120px' },
  { field: 'debug', header: 'Debug', width: '200px' }
];

const regionOptions = computed(() => [
  { value: '', label: 'All Regions' },
  ...props.regions.map(region => ({
    value: region.id,
    label: region.name
  }))
]);

const driverOptions = computed(() => {
  const options = [
    { value: '', label: 'Select Driver' }
  ];
  if (availableDrivers.value?.length) {
    options.push(
      ...availableDrivers.value.map(driver => ({
        value: driver.id,
        label: `${driver.name} (${driver.employeeProfile?.employee_id || 'No ID'})`
      }))
    );
  }
  return options;
});

const truckOptions = computed(() => {
  const options = [
    { value: '', label: 'Select Truck' }
  ];
  if (availableTrucks.value?.length) {
    options.push(
      ...availableTrucks.value.map(truck => ({
        value: truck.id,
        label: `${truck.license_plate} - ${truck.make} ${truck.model}`
      }))
    );
  }
  return options;
});

const Truck = {
  STATUS_AVAILABLE: 'available',
  STATUS_NEARLY_FULL: 'nearly_full',
  STATUS_ASSIGNED: 'assigned',
  STATUS_IN_TRANSIT: 'in_transit',
  STATUS_RETURNING: 'returning',
  STATUS_MAINTENANCE: 'maintenance',
  STATUS_UNAVAILABLE: 'unavailable'
};

function getTruckStatusLabel(status) {
  const statusMap = {
    [Truck.STATUS_AVAILABLE]: 'Available',
    [Truck.STATUS_NEARLY_FULL]: 'Nearly Full',
    [Truck.STATUS_ASSIGNED]: 'Assigned',
    [Truck.STATUS_IN_TRANSIT]: 'In Transit',
    [Truck.STATUS_RETURNING]: 'Returning',
    [Truck.STATUS_MAINTENANCE]: 'Maintenance',
    [Truck.STATUS_UNAVAILABLE]: 'Unavailable'
  };
  return statusMap[status] || 'Unknown';
}

function getTruckStatusColor(status) {
  const colorMap = {
    [Truck.STATUS_AVAILABLE]: 'green',
    [Truck.STATUS_NEARLY_FULL]: 'yellow',
    [Truck.STATUS_ASSIGNED]: 'blue',
    [Truck.STATUS_IN_TRANSIT]: 'indigo',
    [Truck.STATUS_RETURNING]: 'purple',
    [Truck.STATUS_MAINTENANCE]: 'red',
    [Truck.STATUS_UNAVAILABLE]: 'gray'
  };
  return colorMap[status] || 'gray';
}

function getTruckCapacity(truck) {
  if (!truck) return 'N/A';
  return `${truck.volume_capacity}m³ ${truck.weight_capacity}kg`;
}

function formatDate(dateString) {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleString();
}

function viewAssignment(assignment) {
  selectedAssignment.value = assignment;
  showDetailModal.value = true;
}

function confirmDeactivate(assignment) {
  assignmentToDeactivate.value = assignment;
  showDeactivateModal.value = true;
}

function confirmReactivate(assignment) {
  assignmentToReactivate.value = assignment;
  showReactivateModal.value = true;
}

function deactivateAssignment(assignment) {
  assignmentToDeactivate.value = assignment;
  showDeactivateModal.value = true;
}

function reactivateAssignment(assignment) {
  assignmentToReactivate.value = assignment;
  showReactivateModal.value = true;
}

watch(() => form.region_id, (newRegionId, oldRegionId) => {
  if (newRegionId && newRegionId !== oldRegionId) {
    form.driver_id = null;
    form.truck_id = null;
    loadAvailableResources();
  }
});

async function loadAvailableResources() {
  if (!form.region_id) {
    availableDrivers.value = [];
    availableTrucks.value = [];
    form.driver_id = null;
    form.truck_id = null;
    return;
  }
  try {
    loadingDrivers.value = true;
    loadingTrucks.value = true;
    const response = await axios.post(
      route('driver-truck-assignments.available-resources'),
      { region_id: form.region_id }
    );
    availableDrivers.value = response.data.drivers;
    availableTrucks.value = response.data.trucks;
    if (form.driver_id && !availableDrivers.value.some(d => d.id === form.driver_id)) {
      form.driver_id = null;
    }
    if (form.truck_id && !availableTrucks.value.some(t => t.id === form.truck_id)) {
      form.truck_id = null;
    }
  } catch (error) {
    console.error('API Error:', error);
  } finally {
    loadingDrivers.value = false;
    loadingTrucks.value = false;
  }
}

function submit() {
  form.post(route('driver-truck-assignments.store'), {
    onSuccess: () => {
      closeCreateModal();
    },
    preserveScroll: true
  });
}

function handlePageChange(page) {
  router.get(route('driver-monitoring.index', { 
    page,
    ...filters.value 
  }), {
    preserveState: true,
    preserveScroll: true,
    onStart: () => loading.value = true,
    onFinish: () => loading.value = false
  });
}

function handleFilterChange() {
  router.get(route('driver-monitoring.index', {
    ...filters.value,
    page: 1
  }), {
    preserveState: true,
    preserveScroll: true,
    replace: true,
    onStart: () => loading.value = true,
    onFinish: () => loading.value = false
  });
}

function resetFilters() {
  filters.value = {
    region_id: '',
    status: 'active',
    search: ''
  };
  handleFilterChange();
}

function closeCreateModal() {
  showCreateModal.value = false;
  form.reset();
  availableDrivers.value = [];
  availableTrucks.value = [];
}

watch(selectedAssignment, (newVal) => {
  console.log('Selected Assignment:', newVal);
  console.log('Employee Profile:', newVal?.driver?.employeeProfile);
}, { deep: true });

watch(() => props.assignments, (newVal) => {
    if (newVal.data.length > 0) {
        const firstAssignment = newVal.data[0];
        console.log('First assignment:', {
            id: firstAssignment.id,
            driver: firstAssignment.driver?.name,
            logs: firstAssignment.driver?.regionLogs,
            return_status: firstAssignment.return_status
        });
    }
}, { immediate: true, deep: true });

const verifyDriverReturn = (assignment) => {
  router.post(route('driver-truck-assignments.verify-return', assignment.id), {
    preserveScroll: true,
    onSuccess: () => {
      // Force reload the page to get updated assignment data
      router.reload({
        only: ['assignments'],
        onFinish: () => {
          // Optionally, show a notification here
        }
      });
    }
  });
};
</script>