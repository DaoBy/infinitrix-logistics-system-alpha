<template>
  <Head title="Assigned Deliveries" />

  <EmployeeLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
          <h2 class="text-2xl font-bold leading-tight text-gray-900 dark:text-gray-100">
            Assigned Deliveries
          </h2>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Manage your deliveries and report package status at destination
          </p>
        </div>
        <SecondaryButton
          @click="goToDashboard"
          class="ml-0 sm:ml-4 mt-3 sm:mt-0"
        >
          <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h2l.4 2M7 13h10l4-8H5.4" />
          </svg>
          Dashboard
        </SecondaryButton>
      </div>
    </template>

    <div class="py-12">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        
        <!-- Packages Ready for Status Update Section -->
        <div v-if="readyForStatusUpdate.length > 0" class="mb-8">
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 dark:bg-yellow-900/20 dark:border-yellow-700">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center">
                <svg class="h-6 w-6 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200">
                  Packages Ready for Status Update
                </h3>
              </div>
              <span class="bg-yellow-100 text-yellow-800 text-sm font-medium px-3 py-1 rounded-full dark:bg-yellow-900 dark:text-yellow-200">
                {{ readyForStatusUpdate.length }} package(s)
              </span>
            </div>
            <p class="text-yellow-700 dark:text-yellow-300 mb-4">
              You've arrived at destination regions. Please update the status of these packages.
            </p>
            
            <div class="space-y-4">
              <div 
                v-for="pkg in readyForStatusUpdate" 
                :key="pkg.id"
                class="bg-white border border-yellow-200 rounded-lg p-4 dark:bg-gray-800 dark:border-yellow-600"
              >
                <div class="flex items-center justify-between">
                  <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                      <span class="font-medium text-gray-900 dark:text-white">{{ pkg.item_code }}</span>
                      <span class="text-sm text-gray-500 dark:text-gray-400">{{ pkg.item_name }}</span>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">
                      <div class="flex items-center gap-4">
                        <span>Reference: {{ pkg.deliveryRequest?.reference_number }}</span>
                        <span>Destination: {{ pkg.deliveryRequest?.dropOffRegion }}</span>
                      </div>
                    </div>
                  </div>
                  
                  <PrimaryButton
                    @click="openStatusModal(pkg)"
                    class="ml-4"
                  >
                    Update Status
                  </PrimaryButton>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Main Deliveries List -->
        <div class="bg-white shadow-sm rounded-xl dark:bg-gray-800 overflow-hidden">
          <div class="p-6">
            <div v-if="deliveries.length === 0" class="text-center py-12">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2a4 4 0 018 0v2M12 7a4 4 0 100 8 4 4 0 000-8z" />
              </svg>
              <p class="mt-4 text-gray-500 dark:text-gray-400">No assigned deliveries found</p>
            </div>

            <div v-else class="space-y-6">
              <div 
                v-for="delivery in deliveries" 
                :key="delivery.id"
                class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-6 shadow-sm"
                :class="{
                  'border-purple-300 dark:border-purple-700': delivery.is_backhaul
                }"
              >
                <!-- Delivery Header -->
                <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-4">
                  <div class="flex items-center gap-3">
                    <!-- Reference Number -->
                    <div class="bg-blue-50 dark:bg-blue-900/30 px-3 py-1 rounded-lg">
                      <span class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        {{ delivery.reference_number }}
                      </span>
                    </div>
                    
                    <!-- Backhaul Assignment Indicator -->
                    <span 
                      v-if="delivery.is_backhaul"
                      class="px-3 py-1 text-sm font-medium rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-200 flex items-center gap-1"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                      </svg>
                      Backhaul Assignment
                    </span>
                    
                    <!-- Status Badge -->
                    <span 
                      class="px-3 py-1 text-sm font-medium rounded-full"
                      :class="{
                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200': delivery.status === 'assigned',
                        'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200': delivery.status === 'dispatched',
                        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200': delivery.status === 'in_transit'
                      }"
                    >
                      {{ formatStatus(delivery.status) }}
                    </span>
                  </div>
                  
                  <!-- Actions -->
                  <div class="flex gap-3">
                    <PrimaryButton
                      type="button"
                      class="flex items-center"
                      @click="goToStatusUpdate"
                    >
                      Update Location
                    </PrimaryButton>
                    <SecondaryButton
                      v-if="delivery.packages && delivery.packages.length > 0"
                      type="button"
                      class="flex items-center"
                      @click="goToTrack(delivery.packages[0].id)"
                    >
                      Track
                    </SecondaryButton>
                  </div>
                </div>

                <!-- Region Route Display -->
                <div class="mb-4 p-3 bg-blue-50 rounded-lg dark:bg-blue-900/20">
                  <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center">
                      <span class="font-medium text-blue-700 dark:text-blue-300">Route:</span>
                      <span class="mx-2 text-blue-600 dark:text-blue-400">{{ delivery.current_region }}</span>
                      <svg class="w-4 h-4 text-blue-500 mx-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                      </svg>
                      <span class="text-blue-600 dark:text-blue-400">{{ delivery.destination }}</span>
                    </div>
                    <span v-if="delivery.is_backhaul" class="text-xs text-purple-600 dark:text-purple-400 font-medium">
                      Return to {{ delivery.home_region }}
                    </span>
                  </div>
                </div>

                <!-- Special Backhaul Instructions -->
                <div 
                  v-if="delivery.is_backhaul"
                  class="mb-4 p-3 bg-purple-50 border border-purple-200 rounded-lg dark:bg-purple-900/20 dark:border-purple-700"
                >
                  <h4 class="text-sm font-medium text-purple-800 dark:text-purple-200 mb-2 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Backhaul Instructions
                  </h4>
                  <ul class="text-xs text-purple-700 dark:text-purple-300 list-disc list-inside space-y-1">
                    <li>Return trip to home region: {{ delivery.home_region }}</li>
                    <li>Priority: Standard Backhaul</li>
                    <li>No additional pickups allowed</li>
                    <li>Direct route to home region</li>
                  </ul>
                </div>

                <!-- Packages Section -->
                <div>
                  <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1">
                      <svg class="h-4 w-4 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect width="20" height="12" x="2" y="6" rx="2" />
                      </svg>
                      Packages ({{ delivery.package_count || 0 }})
                    </h3>
                    <SecondaryButton
                      type="button"
                      @click="togglePackageDetails(delivery.id)"
                      class="text-xs px-2 py-1"
                    >
                      {{ showDetails[delivery.id] ? 'Hide' : 'Show' }}
                    </SecondaryButton>
                  </div>
                  
                  <div v-if="showDetails[delivery.id] && delivery.packages" class="space-y-2">
                    <div 
                      v-for="pkg in delivery.packages" 
                      :key="pkg.id"
                      class="text-sm text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 rounded-lg px-3 py-2 border"
                    >
                      <div class="flex justify-between items-start">
                        <div>
                          <p class="font-medium">{{ pkg.item_code || 'N/A' }}</p>
                          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Status: 
                            <span :class="getStatusColor(pkg.status)">
                              {{ formatStatus(pkg.status) }}
                            </span>
                          </p>
                          <p v-if="pkg.incident_reported_at" class="text-xs text-red-600 dark:text-red-400 mt-1">
                            Incident Reported: {{ formatDate(pkg.incident_reported_at) }}
                          </p>
                        </div>
                        <span class="text-xs text-gray-400">
                          #{{ pkg.id }}
                        </span>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Package Verification Summary -->
                  <div v-if="delivery.packages" class="mt-3 p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                    <div class="flex justify-between text-xs">
                      <span class="text-gray-600 dark:text-gray-300">Verified Packages:</span>
                      <span class="font-medium">
                        {{ delivery.packages.filter(p => p.verified_at).length }} / {{ delivery.packages.length }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Status Update Modal -->
    <div v-if="showStatusModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white dark:bg-gray-800 rounded-lg max-w-md w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Update Package Status
            </h3>
            <button @click="closeStatusModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <div v-if="selectedPackage" class="space-y-4">
            <!-- Package Info -->
            <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
              <p class="font-medium">{{ selectedPackage.item_code }}</p>
              <p class="text-sm text-gray-600 dark:text-gray-300">{{ selectedPackage.item_name }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                Reference: {{ selectedPackage.deliveryRequest?.reference_number }}
              </p>
            </div>

            <!-- Status Selection -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Package Status *
              </label>
              <select v-model="statusUpdate.status" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="delivered">✅ Delivered - Package successfully delivered</option>
                <option value="damaged_in_transit">⚠️ Damaged - Package arrived but damaged</option>
                <option value="lost_in_transit">❌ Lost - Package missing/lost in transit</option>
              </select>
            </div>

            <!-- Remarks -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Remarks
              </label>
              <textarea 
                v-model="statusUpdate.remarks" 
                placeholder="Add any notes about the package condition..."
                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                rows="3"
              ></textarea>
            </div>

            <!-- Evidence Upload (for incidents) -->
            <div v-if="statusUpdate.status !== 'delivered'">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Evidence Photos
              </label>
              <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center">
                <input 
                  type="file" 
                  ref="fileInput"
                  multiple 
                  accept="image/*"
                  @change="handleFileUpload"
                  class="hidden"
                />
                <button 
                  @click="$refs.fileInput.click()"
                  class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                >
                  <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                  Upload Photos
                </button>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  Upload photos as evidence for damaged or lost packages
                </p>
              </div>
              
              <!-- Uploaded Files Preview -->
              <div v-if="uploadedFiles.length > 0" class="mt-3">
                <div v-for="(file, index) in uploadedFiles" :key="index" class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 p-2 rounded mb-2">
                  <span class="text-sm text-gray-600 dark:text-gray-300">{{ file.name }}</span>
                  <button @click="removeFile(index)" class="text-red-500 hover:text-red-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-3 pt-4">
              <SecondaryButton @click="closeStatusModal" class="flex-1">
                Cancel
              </SecondaryButton>
              <PrimaryButton 
                @click="submitStatusUpdate" 
                :disabled="submitting"
                class="flex-1"
              >
                <span v-if="submitting">Updating...</span>
                <span v-else>Update Status</span>
              </PrimaryButton>
            </div>
          </div>
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  deliveries: {
    type: Array,
    default: () => []
  },
  readyForStatusUpdate: {
    type: Array,
    default: () => []
  }
});

const showDetails = ref({});
const showStatusModal = ref(false);
const selectedPackage = ref(null);
const submitting = ref(false);
const uploadedFiles = ref([]);

const statusUpdate = reactive({
  status: 'delivered',
  remarks: '',
  evidence: []
});

const togglePackageDetails = (deliveryId) => {
  showDetails.value = {
    ...showDetails.value,
    [deliveryId]: !showDetails.value[deliveryId]
  };
};

function formatStatus(status) {
  if (!status) return 'N/A';
  const map = {
    assigned: 'Assigned',
    dispatched: 'Dispatched',
    in_transit: 'In Transit',
    delivered: 'Delivered',
    completed: 'Completed',
    returned: 'Returned',
    rejected: 'Rejected',
    damaged_in_transit: 'Damaged',
    lost_in_transit: 'Lost'
  };
  return map[status] || (typeof status === 'string' ? status.charAt(0).toUpperCase() + status.slice(1) : status);
}

function getStatusColor(status) {
  const colors = {
    delivered: 'text-green-600 dark:text-green-400',
    completed: 'text-green-600 dark:text-green-400',
    damaged_in_transit: 'text-yellow-600 dark:text-yellow-400',
    lost_in_transit: 'text-red-600 dark:text-red-400',
    in_transit: 'text-blue-600 dark:text-blue-400',
    assigned: 'text-gray-600 dark:text-gray-400'
  };
  return colors[status] || 'text-gray-600 dark:text-gray-400';
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString();
}

// Modal Functions
function openStatusModal(pkg) {
  selectedPackage.value = pkg;
  showStatusModal.value = true;
  // Reset form
  statusUpdate.status = 'delivered';
  statusUpdate.remarks = '';
  statusUpdate.evidence = [];
  uploadedFiles.value = [];
}

function closeStatusModal() {
  showStatusModal.value = false;
  selectedPackage.value = null;
}

function handleFileUpload(event) {
  const files = Array.from(event.target.files);
  uploadedFiles.value = [...uploadedFiles.value, ...files];
}

function removeFile(index) {
  uploadedFiles.value.splice(index, 1);
}

async function submitStatusUpdate() {
  if (!selectedPackage.value) return;

  submitting.value = true;

  try {
    // Create FormData for the entire request (including files)
    const formData = new FormData();
    
    // Add the package update data
    formData.append('package_updates[0][package_id]', selectedPackage.value.id);
    formData.append('package_updates[0][status]', statusUpdate.status);
    formData.append('package_updates[0][remarks]', statusUpdate.remarks);
    
    // Add evidence files
    uploadedFiles.value.forEach((file, index) => {
      formData.append(`package_updates[0][evidence][${index}]`, file);
    });

    // Use Inertia's post method with FormData
    router.post(route('driver.packages.update-destination-status'), formData, {
      onFinish: () => {
        submitting.value = false;
        closeStatusModal();
      },
      onError: (errors) => {
        console.error('Status update errors:', errors);
        alert('Failed to update status. Please check your inputs.');
      }
    });

  } catch (error) {
    console.error('Error updating status:', error);
    submitting.value = false;
    alert('Failed to update status. Please try again.');
  }
}

// Navigation handlers
function goToStatusUpdate() {
  router.visit(route('driver.status-update'));
}

function goToTrack(packageId) {
  router.visit(route('driver.track-package', { package: packageId }));
}

function goToDashboard() {
  router.visit(route('driver.dashboard'));
}
</script>