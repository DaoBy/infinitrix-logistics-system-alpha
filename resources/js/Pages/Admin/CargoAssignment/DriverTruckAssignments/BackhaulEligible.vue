<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Backhaul Eligible Assignments
          </h2>
          <p class="text-sm text-gray-600 mt-1">
            Driver-truck sets available for backhaul assignments
          </p>
        </div>
        <div class="flex space-x-2">
          <SelectInput
            v-model="filters.region_id"
            :options="regionOptions"
            placeholder="Filter by Region"
            class="w-48"
            @change="handleFilterChange"
          />
          <SecondaryButton @click="$inertia.visit(route('driver-truck-assignments.index'))">
            View All Assignments
          </SecondaryButton>
        </div>
      </div>
    </template>

    <div class="py-6">
      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
        <svg class="mx-auto h-12 w-12 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-red-800">Failed to load assignments</h3>
        <p class="mt-1 text-sm text-red-600">{{ error }}</p>
        <PrimaryButton @click="loadData" class="mt-4">
          Try Again
        </PrimaryButton>
      </div>

      <!-- Empty State -->
      <div v-else-if="assignments.length === 0" class="bg-white shadow-sm rounded-lg overflow-hidden">
        <div class="px-6 py-12 text-center">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No backhaul eligible assignments</h3>
          <p class="mt-1 text-sm text-gray-500">
            {{ filters.region_id ? 'No backhaul eligible assignments found in this region.' : 'No driver-truck sets are currently available for backhaul assignments.' }}
          </p>
          <div class="mt-6">
            <PrimaryButton @click="refreshData">
              Refresh
            </PrimaryButton>
          </div>
        </div>
      </div>

      <!-- Data State -->
      <div v-else class="bg-white shadow-sm rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-purple-50">
          <div class="flex justify-between items-center">
            <div>
              <h3 class="text-lg font-medium text-purple-900">
                Available for Backhaul
              </h3>
              <p class="text-sm text-purple-700">
                {{ assignments.length }} driver-truck set{{ assignments.length !== 1 ? 's' : '' }} ready for backhaul assignments
              </p>
            </div>
            <div class="flex space-x-2">
              <SecondaryButton @click="exportData" :loading="exporting">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export
              </SecondaryButton>
              <PrimaryButton @click="refreshData" :loading="refreshing">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh
              </PrimaryButton>
            </div>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Driver</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Truck</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Region</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Home Region</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Eligible Since</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Truck Capacity</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="assignment in assignments" :key="assignment.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 h-10 w-10 bg-purple-100 rounded-full flex items-center justify-center">
                      <span class="text-purple-800 font-medium text-sm">
                        {{ getInitials(assignment.driver?.name) }}
                      </span>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900">{{ assignment.driver?.name }}</div>
                      <div class="text-sm text-gray-500">{{ assignment.driver?.employee_profile?.employee_id }}</div>
                      <div class="text-xs text-purple-600 font-semibold mt-1">
                        Backhaul Ready
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">{{ assignment.truck?.license_plate }}</div>
                  <div class="text-sm text-gray-500">{{ assignment.truck?.make }} {{ assignment.truck?.model }}</div>
                  <div class="text-xs text-gray-400">
                    {{ assignment.truck?.year }} • {{ assignment.truck?.vin?.substring(0, 8) }}...
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  <span class="font-medium text-purple-600">{{ assignment.current_region?.name }}</span>
                  <div class="text-xs text-gray-500 mt-1">
                    Current Location
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ assignment.region?.name }}
                  <div class="text-xs text-gray-500 mt-1">
                    Home Base
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  <div>{{ formatDateTime(assignment.backhaul_eligible_at) }}</div>
                  <div class="text-xs text-gray-400">
                    {{ timeAgo(assignment.backhaul_eligible_at) }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <div class="flex items-center space-x-2">
                    <div class="w-24 bg-gray-200 rounded-full h-2">
                      <div 
                        class="bg-green-600 h-2 rounded-full" 
                        :style="{ width: `${getCapacityPercentage(assignment.truck)}%` }"
                        :class="{
                          'bg-yellow-500': getCapacityPercentage(assignment.truck) > 60,
                          'bg-red-500': getCapacityPercentage(assignment.truck) > 85
                        }"
                      ></div>
                    </div>
                    <span class="text-xs text-gray-600">
                      {{ getCapacityPercentage(assignment.truck) }}%
                    </span>
                  </div>
                  <div class="text-xs text-gray-500 mt-1">
                    {{ assignment.truck?.current_volume?.toFixed(1) }}m³ / {{ assignment.truck?.volume_capacity }}m³
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex justify-end space-x-2">
                    <SecondaryButton 
                      @click="viewAssignmentDetails(assignment)"
                      size="xs"
                      title="View Details"
                    >
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </SecondaryButton>
                    <PrimaryButton 
                      @click="assignBackhaul(assignment)"
                      size="xs"
                      :loading="assigningId === assignment.id"
                      :disabled="assigningId !== null"
                    >
                      <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                      </svg>
                      Assign Backhaul
                    </PrimaryButton>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Additional Info Footer -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
          <div class="flex items-center justify-between text-sm text-gray-600">
            <div>
              Last updated: {{ lastUpdated }}
            </div>
            <div class="flex items-center space-x-4">
              <span class="flex items-center">
                <div class="w-3 h-3 bg-green-500 rounded-full mr-1"></div>
                Available (&lt;60%)
              </span>
              <span class="flex items-center">
                <div class="w-3 h-3 bg-yellow-500 rounded-full mr-1"></div>
                Moderate (60-85%)
              </span>
              <span class="flex items-center">
                <div class="w-3 h-3 bg-red-500 rounded-full mr-1"></div>
                Limited (&gt;85%)
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Assignment Details Modal -->
    <Modal :show="showDetailsModal" @close="showDetailsModal = false" max-width="2xl">
      <div class="p-6" v-if="selectedAssignment">
        <h2 class="text-xl font-semibold mb-4 text-gray-800">Assignment Details</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <!-- Driver Information -->
          <div class="bg-gray-50 rounded-lg p-4">
            <h3 class="font-semibold text-gray-700 mb-3 border-b pb-2">Driver Information</h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-500">Name:</span>
                <span class="font-medium">{{ selectedAssignment.driver?.name }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Employee ID:</span>
                <span class="font-medium">{{ selectedAssignment.driver?.employee_profile?.employee_id }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Current Region:</span>
                <span class="font-medium text-purple-600">{{ selectedAssignment.current_region?.name }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Home Region:</span>
                <span class="font-medium">{{ selectedAssignment.region?.name }}</span>
              </div>
            </div>
          </div>

          <!-- Truck Information -->
          <div class="bg-gray-50 rounded-lg p-4">
            <h3 class="font-semibold text-gray-700 mb-3 border-b pb-2">Truck Information</h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-500">License Plate:</span>
                <span class="font-medium">{{ selectedAssignment.truck?.license_plate }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Make/Model:</span>
                <span class="font-medium">{{ selectedAssignment.truck?.make }} {{ selectedAssignment.truck?.model }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Capacity:</span>
                <span class="font-medium">{{ selectedAssignment.truck?.volume_capacity }}m³</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Current Load:</span>
                <span class="font-medium">{{ selectedAssignment.truck?.current_volume?.toFixed(1) }}m³ ({{ getCapacityPercentage(selectedAssignment.truck) }}%)</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Backhaul Eligibility Info -->
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 mb-6">
          <h3 class="font-semibold text-purple-800 mb-2">Backhaul Eligibility</h3>
          <div class="text-sm text-purple-700 space-y-1">
            <div>✓ All packages delivered and verified</div>
            <div>✓ Driver is away from home region</div>
            <div>✓ Cooldown period completed</div>
            <div class="text-xs text-purple-600 mt-2">
              Eligible since: {{ formatDateTime(selectedAssignment.backhaul_eligible_at) }}
            </div>
          </div>
        </div>

        <div class="flex justify-end space-x-2">
          <SecondaryButton @click="showDetailsModal = false">
            Close
          </SecondaryButton>
          <PrimaryButton 
            @click="assignBackhaul(selectedAssignment)"
            :loading="assigningId === selectedAssignment.id"
          >
            Assign Backhaul
          </PrimaryButton>
        </div>
      </div>
    </Modal>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import Modal from '@/Components/Modal.vue';
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

// Use Inertia props for server-side data
const props = defineProps({
  assignments: {
    type: Array,
    default: () => []
  },
  regions: {
    type: Array,
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({})
  }
});

// Reactive state
const loading = ref(false);
const refreshing = ref(false);
const exporting = ref(false);
const error = ref(null);
const showDetailsModal = ref(false);
const selectedAssignment = ref(null);
const assigningId = ref(null);
const lastUpdated = ref(new Date().toLocaleTimeString());

// Filters
const filters = ref({
  region_id: props.filters.region_id || ''
});

// Computed
const regionOptions = computed(() => [
  { value: '', label: 'All Regions' },
  ...props.regions.map(region => ({
    value: region.id,
    label: region.name
  }))
]);

// Use assignments from props (server-side loaded)
const assignments = computed(() => props.assignments);

// Methods
function handleFilterChange() {
  router.get(route('driver-truck-assignments.backhaul-eligible.show'), filters.value, {
    preserveState: true,
    replace: true,
    onStart: () => loading.value = true,
    onFinish: () => loading.value = false,
    onError: (errors) => {
      error.value = 'Failed to filter assignments';
      console.error('Filter error:', errors);
    }
  });
}

async function refreshData() {
  refreshing.value = true;
  try {
    await router.reload({
      only: ['assignments'],
      onSuccess: () => {
        lastUpdated.value = new Date().toLocaleTimeString();
        error.value = null;
      },
      onError: (errors) => {
        error.value = 'Failed to refresh data';
        console.error('Refresh error:', errors);
      }
    });
  } catch (err) {
    error.value = 'Network error while refreshing';
    console.error('Refresh failed:', err);
  } finally {
    refreshing.value = false;
  }
}

async function loadData() {
  loading.value = true;
  error.value = null;
  try {
    await router.reload({
      only: ['assignments'],
      onError: (errors) => {
        error.value = 'Failed to load assignments';
        console.error('Load error:', errors);
      }
    });
  } catch (err) {
    error.value = 'Network error while loading data';
    console.error('Load failed:', err);
  } finally {
    loading.value = false;
  }
}

async function assignBackhaul(assignment) {
  assigningId.value = assignment.id;
  try {
    // Navigate to cargo assignment with backhaul parameters
    router.visit(route('cargo-assignment.index', { 
      driver_truck_assignment_id: assignment.id,
      region_id: assignment.current_region_id,
      assignment_type: 'backhaul'
    }), {
      onError: (errors) => {
        alert('Failed to start backhaul assignment. Please try again.');
        console.error('Backhaul assignment error:', errors);
      }
    });
  } catch (err) {
    alert('Network error while starting backhaul assignment');
    console.error('Backhaul assignment failed:', err);
  } finally {
    assigningId.value = null;
  }
}

function viewAssignmentDetails(assignment) {
  selectedAssignment.value = assignment;
  showDetailsModal.value = true;
}

async function exportData() {
  exporting.value = true;
  try {
    const response = await axios.post(route('driver-truck-assignments.backhaul-eligible.export'), {
      region_id: filters.value.region_id
    }, {
      responseType: 'blob'
    });
    
    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `backhaul-eligible-assignments-${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  } catch (err) {
    alert('Failed to export data');
    console.error('Export error:', err);
  } finally {
    exporting.value = false;
  }
}

function getInitials(name) {
  if (!name) return '??';
  return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
}

function formatDateTime(dateString) {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleString();
}

function timeAgo(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);

  if (diffMins < 1) return 'just now';
  if (diffMins < 60) return `${diffMins}m ago`;
  if (diffHours < 24) return `${diffHours}h ago`;
  return `${diffDays}d ago`;
}

function getCapacityPercentage(truck) {
  if (!truck || !truck.volume_capacity) return 0;
  const currentVolume = truck.current_volume || 0;
  return Math.round((currentVolume / truck.volume_capacity) * 100);
}

// Initialize
onMounted(() => {
  // If no assignments were passed via props, load them
  if (props.assignments.length === 0) {
    loadData();
  }
});
</script>