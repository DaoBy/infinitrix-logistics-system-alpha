<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">Delivery Request Details</h2>
          <p class="mt-1 text-sm text-gray-500">
            View delivery request information
          </p>
        </div>

        <!-- Right: Buttons -->
        <div class="flex gap-2">
          <SecondaryButton @click="goBack">
            Back
          </SecondaryButton>
          <DangerButton 
            v-if="delivery.status === 'pending'"
            @click="openRejectModal"
          >
            Reject Request
          </DangerButton>
          <PrimaryButton 
            v-if="delivery.status === 'pending'"
            @click="handleApprove"
          >
            Approve Request
          </PrimaryButton>
        </div>
      </div>
    </template>

    <!-- MAIN CONTENT AREA - This should scroll independently -->
    <div class="flex-1 overflow-auto">
      <!-- ZOOM CONTENT WRAPPER -->
      <div class="zoom-content">
        <!-- MAIN CONTENT CONTAINER WITH PROPER PADDING -->
        <div class="px-6 py-4">
          <div v-if="status || success || error" class="mb-4">
            <div v-if="status" class="p-3 bg-blue-100 text-blue-800 rounded">{{ status }}</div>
            <div v-if="success" class="p-3 bg-green-100 text-green-800 rounded">{{ success }}</div>
            <div v-if="error" class="p-3 bg-red-100 text-red-800 rounded">{{ error }}</div>
          </div>

          <!-- Data Table Container - 20% NARROWER -->
          <div class="justify-center flex items-center">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-full max-w-[75vw]">
              <div class="p-4 bg-white border-b border-gray-200">
                
                <!-- Header Card with Delivery ID and Status -->
                <div class="bg-white shadow-sm rounded-lg border border-gray-200 mb-6">
                  <div class="p-6">
                    <div class="flex flex-col">
                      <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                          Delivery ID:
                        </span>
                        <span class="font-bold text-green-700 tracking-wide">
                         DR-{{ String(delivery.id).padStart(6, '0') }}
                        </span>
                      </div>
                      <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                        <span>
                          Reference # {{ delivery.status === 'pending' ? 'N/A' : (delivery.reference_number || delivery.order_number || ('DR-' + String(delivery.id).padStart(6, '0'))) }}
                        </span>
                        <span v-if="delivery.created_at"> | Created: {{ formatDate(delivery.created_at) }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Main Information Grid - 3 Columns -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                  <!-- Column 1: Sender Information -->
                  <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                    <div class="p-4 border-b border-gray-200">
                      <h3 class="text-lg font-medium text-gray-900">Sender Information</h3>
                    </div>
                    <div class="p-4 space-y-4">
                      <div class="grid grid-cols-1 gap-3">
                        <div>
                          <label class="block text-sm font-medium text-gray-700">Name</label>
                          <p class="mt-1 text-sm text-gray-900">
                            {{ delivery.sender?.name || delivery.sender?.company_name || 'N/A' }}
                          </p>
                        </div>
                        <div>
                          <label class="block text-sm font-medium text-gray-700">Contact</label>
                          <p class="mt-1 text-sm text-gray-900">
                            {{ delivery.sender?.phone || delivery.sender?.mobile || 'N/A' }}
                          </p>
                        </div>
                        <div>
                          <label class="block text-sm font-medium text-gray-700">Email</label>
                          <p class="mt-1 text-sm text-gray-900">
                            {{ delivery.sender?.email || 'N/A' }}
                          </p>
                        </div>
                        <div>
                          <label class="block text-sm font-medium text-gray-700">Address</label>
                          <p class="mt-1 text-sm text-gray-900">
                            {{ formatAddress(delivery.sender) }}
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Column 2: Receiver Information -->
                  <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                    <div class="p-4 border-b border-gray-200">
                      <h3 class="text-lg font-medium text-gray-900">Receiver Information</h3>
                    </div>
                    <div class="p-4 space-y-4">
                      <div class="grid grid-cols-1 gap-3">
                        <div>
                          <label class="block text-sm font-medium text-gray-700">Name</label>
                          <p class="mt-1 text-sm text-gray-900">
                            {{ delivery.receiver?.name || delivery.receiver?.company_name || 'N/A' }}
                          </p>
                        </div>
                        <div>
                          <label class="block text-sm font-medium text-gray-700">Contact</label>
                          <p class="mt-1 text-sm text-gray-900">
                            {{ delivery.receiver?.phone || delivery.receiver?.mobile || 'N/A' }}
                          </p>
                        </div>
                        <div>
                          <label class="block text-sm font-medium text-gray-700">Email</label>
                          <p class="mt-1 text-sm text-gray-900">
                            {{ delivery.receiver?.email || 'N/A' }}
                          </p>
                        </div>
                        <div>
                          <label class="block text-sm font-medium text-gray-700">Address</label>
                          <p class="mt-1 text-sm text-gray-900">
                            {{ formatAddress(delivery.receiver) }}
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Column 3: Delivery Information -->
                  <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                    <div class="p-4 border-b border-gray-200">
                      <h3 class="text-lg font-medium text-gray-900">Delivery Information</h3>
                    </div>
                    <div class="p-4 space-y-4">
                      <div class="grid grid-cols-1 gap-3">
                        <div>
                          <label class="block text-sm font-medium text-gray-700">Reference Number</label>
                          <div class="mt-1">
                            <span
                              v-if="delivery.reference_number"
                              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800"
                            >
                              {{ delivery.reference_number }}
                            </span>
                            <span
                              v-else
                              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-gray-200 text-gray-700"
                            >
                              N/A
                            </span>
                          </div>
                        </div>
                        <div>
                          <label class="block text-sm font-medium text-gray-700">Order Number</label>
                          <p class="mt-1 text-sm text-gray-900">{{ delivery.order_number || 'N/A' }}</p>
                        </div>
                        <div>
                          <label class="block text-sm font-medium text-gray-700">Request Date</label>
                          <p class="mt-1 text-sm text-gray-900">{{ formatDate(delivery.created_at) }}</p>
                        </div>
                        <div>
                          <label class="block text-sm font-medium text-gray-700">Pick Up Region</label>
                          <p class="mt-1 text-sm text-gray-900">{{ delivery.pick_up_region || 'N/A' }}</p>
                        </div>
                        <div>
                          <label class="block text-sm font-medium text-gray-700">Drop Off Region</label>
                          <p class="mt-1 text-sm text-gray-900">{{ delivery.drop_off_region || 'N/A' }}</p>
                        </div>
                        <div v-if="delivery.approved_at">
                          <label class="block text-sm font-medium text-gray-700">Approved Date</label>
                          <p class="mt-1 text-sm text-gray-900">{{ formatDate(delivery.approved_at) }}</p>
                        </div>
                        <div v-if="delivery.approved_by">
                          <label class="block text-sm font-medium text-gray-700">Approved By</label>
                          <p class="mt-1 text-sm text-gray-900">{{ delivery.approved_by }}</p>
                        </div>
                        <div v-if="delivery.rejected_at">
                          <label class="block text-sm font-medium text-gray-700">Rejected Date</label>
                          <p class="mt-1 text-sm text-gray-900">{{ formatDate(delivery.rejected_at) }}</p>
                        </div>
                        <div v-if="delivery.rejected_by">
                          <label class="block text-sm font-medium text-gray-700">Rejected By</label>
                          <p class="mt-1 text-sm text-gray-900">{{ delivery.rejected_by }}</p>
                        </div>
                        <div v-if="delivery.rejection_reason">
                          <label class="block text-sm font-medium text-gray-700">Rejection Reason</label>
                          <p class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ delivery.rejection_reason }}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Second Row: Payment Information (spans Sender + Receiver width) -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                  <!-- Payment Information Card - spans 2 columns (same as Sender + Receiver) -->
                  <div class="lg:col-span-2 bg-white shadow-sm rounded-lg border border-gray-200">
                    <div class="p-4 border-b border-gray-200">
                      <h3 class="text-lg font-medium text-gray-900">Payment Information</h3>
                    </div>
                    <div class="p-4">
                      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                          <label class="block text-sm font-medium text-gray-700">Total Amount</label>
                          <p class="mt-1 text-lg font-semibold text-gray-900">₱{{ formatCurrency(delivery.total_price) }}</p>
                        </div>
                        <div>
                          <label class="block text-sm font-medium text-gray-700">Payment Method</label>
                          <p class="mt-1 text-sm text-gray-900 capitalize">{{ delivery.payment_method || 'N/A' }}</p>
                        </div>
                        <div>
                          <label class="block text-sm font-medium text-gray-700">Payment Status</label>
                          <div class="mt-1">
                            <span
                              class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold"
                              :class="paymentStatusBadgeClass(delivery.payment_status)"
                            >
                              {{ paymentStatusBadgeText(delivery.payment_status) }}
                            </span>
                          </div>
                        </div>
                        <div>
                          <label class="block text-sm font-medium text-gray-700">Payment Terms</label>
                          <div class="mt-1">
                            <span
                              v-if="delivery.payment_terms"
                              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800"
                            >
                              {{ formatPaymentTerms(delivery.payment_terms) }}
                            </span>
                            <span
                              v-else
                              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-gray-200 text-gray-700"
                            >
                              N/A
                            </span>
                          </div>
                        </div>
                        <div v-if="delivery.payment_due_date" class="md:col-span-2">
                          <label class="block text-sm font-medium text-gray-700">Payment Due Date</label>
                          <p class="mt-1 text-sm text-gray-900">{{ formatDate(delivery.payment_due_date) }}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Packages Section - Full Width matching Sender + Receiver -->
                <div class="bg-white shadow-sm rounded-lg border border-gray-200">
                  <div class="p-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Packages ({{ delivery.packages?.length || 0 }})</h3>
                  </div>
                  <div class="p-4">
                    <DataTable 
                      v-if="delivery.packages && delivery.packages.length"
                      :columns="packageColumns" 
                      :data="sortedPackages" 
                      :sort-field="packageSortField" 
                      :sort-direction="packageSortDirection" 
                      @sort="handlePackageSort"
                      class="compact-table"
                    >
                      <template #item_code="{ row }">
                        <span class="font-mono text-xs text-gray-600">{{ row.item_code || 'N/A' }}</span>
                      </template>
                      <template #category="{ row }">
                        <span
                          class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold"
                          :class="categoryBadgeClass(row.category)"
                        >
                          {{ formatCategory(row.category) }}
                        </span>
                      </template>
                      <template #dimensions="{ row }">
                        {{ formatDimension(row.height) }} × {{ formatDimension(row.width) }} × {{ formatDimension(row.length) }} cm
                      </template>
                      <template #volume="{ row }">
                        {{ formatVolume(row.volume) }} m³
                      </template>
                      <template #weight="{ row }">
                        {{ formatWeight(row.weight) }} kg
                      </template>
                      <template #value="{ row }">
                        ₱{{ formatCurrency(row.value) }}
                      </template>
                      <template #photo="{ row }">
                        <div class="flex flex-col gap-1 max-w-[80px]">
                          <template v-if="row.photo_path && Array.isArray(row.photo_path) && row.photo_path.length">
                            <!-- First row of 2 images -->
                            <div class="flex gap-1">
                              <img 
                                v-for="(photo, index) in row.photo_path.slice(0, 2)" 
                                :key="index"
                                :src="getPhotoUrl(photo)" 
                                :alt="`Package photo ${index + 1}`" 
                                class="h-8 w-8 object-cover rounded cursor-pointer border border-gray-300"
                                @click="openImageModal(getPhotoUrl(photo))"
                              />
                            </div>
                            <!-- Second row of 2 images -->
                            <div v-if="row.photo_path.length > 2" class="flex gap-1">
                              <img 
                                v-for="(photo, index) in row.photo_path.slice(2, 4)" 
                                :key="index + 2"
                                :src="getPhotoUrl(photo)" 
                                :alt="`Package photo ${index + 3}`" 
                                class="h-8 w-8 object-cover rounded cursor-pointer border border-gray-300"
                                @click="openImageModal(getPhotoUrl(photo))"
                              />
                            </div>
                            <!-- Third row of 2 images -->
                            <div v-if="row.photo_path.length > 4" class="flex gap-1">
                              <img 
                                v-for="(photo, index) in row.photo_path.slice(4, 6)" 
                                :key="index + 4"
                                :src="getPhotoUrl(photo)" 
                                :alt="`Package photo ${index + 5}`" 
                                class="h-8 w-8 object-cover rounded cursor-pointer border border-gray-300"
                                @click="openImageModal(getPhotoUrl(photo))"
                              />
                            </div>
                          </template>
                          <template v-else-if="row.photo_path && typeof row.photo_path === 'string'">
                            <img 
                              :src="getPhotoUrl(row.photo_path)" 
                              alt="Package photo" 
                              class="h-8 w-8 object-cover rounded cursor-pointer border border-gray-300"
                              @click="openImageModal(getPhotoUrl(row.photo_path))"
                            />
                          </template>
                          <span v-else class="text-gray-400 text-xs">No photos</span>
                        </div>
                      </template>
                      <template #status="{ row }">
                        <span
                          class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold"
                          :class="packageStatusBadgeClass(row.status)"
                        >
                          {{ formatPackageStatus(row.status) }}
                        </span>
                      </template>
                      <template #status_history="{ row }">
                        <SecondaryButton
                          class="!px-2 !py-1 !text-xs !font-normal"
                          @click="() => router.visit(route('packages.track', row.id))"
                        >
                          Track Package
                        </SecondaryButton>
                      </template>
                    </DataTable>
                    <div v-else class="text-center py-8 text-gray-500">
                      No packages found for this delivery.
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Status History Modal -->
    <Modal :show="showStatusHistoryModal" @close="closeStatusHistoryModal" max-width="lg">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">Status History</h2>
        <div class="mt-4 space-y-4">
          <div 
            v-for="(history, index) in currentStatusHistory" 
            :key="index"
            class="border-l-4 border-blue-500 pl-4 py-2"
          >
            <div class="flex justify-between">
              <span class="font-medium">{{ history.status }}</span>
              <span class="text-sm text-gray-500">{{ formatDateTime(history.created_at) }}</span>
            </div>
            <p class="text-sm text-gray-600 mt-1">{{ history.notes || 'No notes provided' }}</p>
            <p class="text-xs text-gray-500 mt-1">Updated by: {{ history.user?.name || 'System' }}</p>
          </div>
        </div>
        <div class="mt-6 flex justify-end">
          <SecondaryButton @click="closeStatusHistoryModal">
            Close
          </SecondaryButton>
        </div>
      </div>
    </Modal>

    <!-- Reject Confirmation Modal -->
    <Modal :show="showRejectModal" @close="closeRejectModal">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">Reject Delivery Request?</h2>
        <p class="mt-1 text-sm text-gray-600">
          Are you sure you want to reject this delivery request?
        </p>
        <div class="mt-4">
          <InputLabel for="rejection_reason" value="Reason for Rejection (Required)" />
          <TextArea
            id="rejection_reason"
            class="mt-1 block w-full"
            v-model="rejectionReason"
            :rows="3"
            placeholder="Enter reason for rejection..."
            required
          />
        </div>
        <div class="mt-6 flex justify-end space-x-4">
          <SecondaryButton @click="closeRejectModal">
            Cancel
          </SecondaryButton>
          <DangerButton @click="handleReject" :disabled="isProcessing || !rejectionReason.trim()">
            <span v-if="isProcessing">Rejecting...</span>
            <span v-else>Reject</span>
          </DangerButton>
        </div>
      </div>
    </Modal>

    <!-- Image Preview Modal - Optimized Size -->
    <Modal :show="showImageModal" @close="closeImageModal" max-width="2xl">
      <div class="p-4">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium text-gray-900">Package Photo</h3>
          <button @click="closeImageModal" class="text-gray-500 hover:text-gray-700 p-1 rounded-full hover:bg-gray-100">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        <div class="flex justify-center items-center bg-gray-50 rounded-lg p-3">
          <img 
            :src="currentImage" 
            alt="Package photo preview" 
            class="max-h-[65vh] max-w-[90%] rounded-lg shadow-md object-contain"
          />
        </div>
      </div>
    </Modal>

    <!-- Image Gallery Modal - Optimized Size -->
    <Modal :show="showImageGalleryModal" @close="closeImageGalleryModal" max-width="5xl">
      <div class="p-4">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium text-gray-900">Package Photos ({{ currentGalleryImages.length }})</h3>
          <button @click="closeImageGalleryModal" class="text-gray-500 hover:text-gray-700 p-1 rounded-full hover:bg-gray-100">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <div 
            v-for="(photo, index) in currentGalleryImages" 
            :key="index"
            class="cursor-pointer group"
            @click="openImageModal(getPhotoUrl(photo))"
          >
            <div class="bg-gray-50 rounded-lg p-3 flex justify-center items-center h-40">
              <img 
                :src="getPhotoUrl(photo)" 
                :alt="`Package photo ${index + 1}`" 
                class="max-h-32 max-w-full object-contain rounded-lg border border-gray-200 group-hover:border-blue-400 transition-colors"
              />
            </div>
            <p class="text-center text-xs text-gray-600 mt-1">Photo {{ index + 1 }}</p>
          </div>
        </div>
      </div>
    </Modal>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextArea from '@/Components/TextArea.vue';
import Modal from '@/Components/Modal.vue';
import DataTable from '@/Components/DataTable.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

// --- Props ---
const props = defineProps({
  delivery: { 
    type: Object, 
    required: true,
    default: () => ({
      id: null,
      order_number: null,
      status: null,
      created_at: null,
      approved_at: null,
      approved_by: null,
      rejected_at: null,
      rejected_by: null,
      rejection_reason: null,
      total_price: 0,
      payment_method: null,
      payment_status: null,
      payment_terms: null,
      payment_due_date: null,
      pick_up_region: null,
      drop_off_region: null,
      sender: null,
      receiver: null,
      packages: [],
    })
  },
  packages: {
    type: Array,
    default: () => []
  },
  status: String,
  success: String,
  error: String,
});

// --- Modal State ---
const showRejectModal = ref(false);
const rejectionReason = ref('');
const isProcessing = ref(false);

const showImageModal = ref(false);
const currentImage = ref('');

const showImageGalleryModal = ref(false);
const currentGalleryImages = ref([]);

const showStatusHistoryModal = ref(false);
const currentStatusHistory = ref([]);

// --- Table Sorting ---
const packageColumns = [
  { field: 'item_code', header: 'Item Code', sortable: true },
  { field: 'item_name', header: 'Item Name', sortable: true },
  { field: 'category', header: 'Category', sortable: true },
  { field: 'dimensions', header: 'Dimensions', sortable: false },
  { field: 'volume', header: 'Volume', sortable: true },
  { field: 'weight', header: 'Weight', sortable: true },
  { field: 'value', header: 'Value', sortable: true },
  { field: 'status', header: 'Status', sortable: true },
  { field: 'photo', header: 'Photos', sortable: false, width: '150px' },
  { field: 'status_history', header: 'Actions', sortable: false, width: '120px' },
];
const packageSortField = ref('item_code');
const packageSortDirection = ref('asc');

const sortedPackages = computed(() => {
  if (!props.delivery.packages) return [];
  return [...props.delivery.packages].sort((a, b) => {
    if (!packageSortField.value) return 0;
    const modifier = packageSortDirection.value === 'asc' ? 1 : -1;
    const valA = getNestedValue(a, packageSortField.value);
    const valB = getNestedValue(b, packageSortField.value);
    if (valA < valB) return -1 * modifier;
    if (valA > valB) return 1 * modifier;
    return 0;
  });
});

function getNestedValue(obj, path) {
  return path.split('.').reduce((o, p) => o?.[p], obj);
}

// --- Formatting Helpers ---
function formatCurrency(value) {
  const num = Number(value) || 0;
  return num.toFixed(2);
}

function formatDimension(value) {
  const num = Number(value) || 0;
  return num.toFixed(1);
}

function formatVolume(value) {
  const num = Number(value) || 0;
  return num.toFixed(3);
}

function formatWeight(value) {
  const num = Number(value) || 0;
  return num.toFixed(1);
}

function formatDate(dateString) {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function formatDateTime(dateString) {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleString('en-US', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function formatAddress(customer) {
  if (!customer) return 'N/A';
  const addressParts = [
    customer.building_number,
    customer.street,
    customer.barangay,
    customer.city,
    customer.province,
    customer.zip_code
  ].filter(Boolean);
  return addressParts.join(', ') || 'Address not specified';
}

function formatCategory(category) {
  if (!category) return 'N/A';
  const categoryMap = {
    'piece': 'Piece',
    'carton': 'Carton',
    'sack': 'Sack',
    'bundle': 'Bundle',
    'roll': 'Roll',
    'B/R': 'B/R',
    'C/S': 'C/S'
  };
  return categoryMap[category] || category.charAt(0).toUpperCase() + category.slice(1);
}

function formatPackageStatus(status) {
  if (!status) return 'N/A';
  const statusMap = {
    'preparing': 'Preparing',
    'ready_for_pickup': 'Ready for Pickup',
    'loaded': 'Loaded',
    'in_transit': 'In Transit',
    'delivered': 'Delivered',
    'completed': 'Completed',
    'returned': 'Returned',
    'rejected': 'Rejected',
    'damaged_in_transit': 'Damaged in Transit',
    'lost_in_transit': 'Lost in Transit'
  };
  return statusMap[status] || status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function formatPaymentTerms(terms) {
  if (!terms) return 'N/A';
  const termsMap = {
    'net_7': 'Net 7 Days',
    'net_15': 'Net 15 Days',
    'net_30': 'Net 30 Days',
    'cnd': 'Cash on Delivery'
  };
  return termsMap[terms] || terms.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

// --- Badge Classes ---
function categoryBadgeClass(category) {
  if (!category) return 'bg-gray-100 text-gray-800';
  
  const normalizedCategory = String(category).toLowerCase();
  switch(normalizedCategory) {
    case 'piece': return 'bg-blue-100 text-blue-800';
    case 'carton': return 'bg-green-100 text-green-800';
    case 'sack': return 'bg-amber-100 text-amber-800';
    case 'bundle': return 'bg-purple-100 text-purple-800';
    case 'roll': return 'bg-red-100 text-red-800';
    case 'b/r': return 'bg-indigo-100 text-indigo-800';
    case 'c/s': return 'bg-pink-100 text-pink-800';
    default: return 'bg-gray-100 text-gray-800';
  }
}

function packageStatusBadgeClass(status) {
  if (!status) return 'bg-gray-100 text-gray-800';
  
  switch(status) {
    case 'delivered':
    case 'completed':
      return 'bg-green-100 text-green-800';
    case 'approved':
    case 'ready_for_pickup':
      return 'bg-blue-100 text-blue-800';
    case 'pending':
    case 'preparing':
      return 'bg-yellow-100 text-yellow-800';
    case 'rejected':
    case 'cancelled':
    case 'damaged_in_transit':
    case 'lost_in_transit':
      return 'bg-red-100 text-red-800';
    case 'in_transit':
    case 'loaded':
      return 'bg-purple-100 text-purple-800';
    case 'returned':
      return 'bg-orange-100 text-orange-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
}

function paymentStatusBadgeClass(status) {
  if (!status || status === 'N/A') return 'bg-gray-200 text-gray-700';
  const s = String(status).toLowerCase().trim();
  if (s === 'completed' || s === 'complete' || s === 'paid') return 'bg-green-100 text-green-800';
  if (s === 'pending' || s === 'pending_payment') return 'bg-yellow-100 text-yellow-800';
  if (s === 'unpaid' || s === 'uncollectible') return 'bg-red-100 text-red-800';
  return 'bg-gray-200 text-gray-700';
}

function paymentStatusBadgeText(status) {
  if (!status || status === '' || status === 'N/A') return 'N/A';
  const s = String(status).toLowerCase().trim();
  if (s === 'paid') return 'Paid';
  if (s === 'complete') return 'Complete';
  if (s === 'completed') return 'Completed';
  if (s === 'pending') return 'Pending';
  if (s === 'pending_payment') return 'Pending Payment';
  if (s === 'unpaid') return 'Unpaid';
  if (s === 'uncollectible') return 'Uncollectible';
  // Capitalize first letter, rest as is
  return status.charAt(0).toUpperCase() + status.slice(1);
}

function deliveryStatusBadgeClass(status) {
  if (!status || status === 'N/A') return 'bg-gray-200 text-gray-700';
  const s = String(status).toLowerCase().trim();
  
  switch(s) {
    case 'completed':
    case 'complete':
    case 'delivered':
      return 'bg-green-100 text-green-800';
    case 'approved':
    case 'accepted':
      return 'bg-blue-100 text-blue-800';
    case 'pending':
    case 'pending_approval':
      return 'bg-yellow-100 text-yellow-800';
    case 'rejected':
    case 'cancelled':
    case 'canceled':
      return 'bg-red-100 text-red-800';
    case 'in_transit':
    case 'in_progress':
      return 'bg-purple-100 text-purple-800';
    case 'ready_for_pickup':
    case 'preparing':
      return 'bg-orange-100 text-orange-800';
    case 'returned':
    case 'failed':
      return 'bg-red-100 text-red-800';
    default:
      return 'bg-gray-200 text-gray-700';
  }
}

function deliveryStatusBadgeText(status) {
  if (!status || status === 'N/A') return 'N/A';
  const s = String(status).toLowerCase().trim();
  
  switch(s) {
    case 'completed': return 'Completed';
    case 'complete': return 'Complete';
    case 'delivered': return 'Delivered';
    case 'approved': return 'Approved';
    case 'accepted': return 'Accepted';
    case 'pending': return 'Pending';
    case 'pending_approval': return 'Pending Approval';
    case 'rejected': return 'Rejected';
    case 'cancelled': return 'Cancelled';
    case 'canceled': return 'Canceled';
    case 'in_transit': return 'In Transit';
    case 'in_progress': return 'In Progress';
    case 'ready_for_pickup': return 'Ready for Pickup';
    case 'preparing': return 'Preparing';
    case 'returned': return 'Returned';
    case 'failed': return 'Failed';
    default: 
      // Capitalize first letter, rest as is
      return status.charAt(0).toUpperCase() + status.slice(1);
  }
}

// --- Modal Logic ---
function openRejectModal() {
  showRejectModal.value = true;
}

function closeRejectModal() {
  showRejectModal.value = false;
  rejectionReason.value = '';
}

function openImageModal(imageUrl) {
  currentImage.value = imageUrl;
  showImageModal.value = true;
}

function closeImageModal() {
  showImageModal.value = false;
  currentImage.value = '';
}

function openImageGallery(images) {
  currentGalleryImages.value = images;
  showImageGalleryModal.value = true;
}

function closeImageGalleryModal() {
  showImageGalleryModal.value = false;
  currentGalleryImages.value = [];
}

function showStatusHistory(pkg) {
  currentStatusHistory.value = pkg.status_history || [];
  showStatusHistoryModal.value = true;
}

function closeStatusHistoryModal() {
  showStatusHistoryModal.value = false;
  currentStatusHistory.value = [];
}

// --- Table Sorting Handler ---
function handlePackageSort({ field, direction }) {
  packageSortField.value = field;
  packageSortDirection.value = direction;
}

// --- Actions ---
async function handleApprove() {
  if (confirm('Are you sure you want to approve this delivery request?')) {
    isProcessing.value = true;
    try {
      await router.post(route('deliveries.approve', props.delivery.id), {}, { 
        preserveScroll: true,
        onSuccess: () => {
          router.reload();
        }
      });
    } catch (error) {
      console.error('Failed to approve request:', error);
    } finally {
      isProcessing.value = false;
    }
  }
}

// --- Photo Helper ---
function getPhotoUrl(photoPath) {
  if (!photoPath) return '';
  // If it's already a full URL, return as is
  if (photoPath.startsWith('http')) return photoPath;
  // If it's a storage path, convert to URL
  if (photoPath.startsWith('package-photos/')) {
    return `/storage/${photoPath}`;
  }
  // Default case
  return `/storage/${photoPath}`;
}

async function handleReject() {
  if (!rejectionReason.value.trim()) return;
  isProcessing.value = true;
  try {
    await router.post(route('deliveries.reject', props.delivery.id), {
      rejection_reason: rejectionReason.value,
    }, { 
      preserveScroll: true,
      onSuccess: () => {
        closeRejectModal();
        router.reload();
      }
    });
  } catch (error) {
    console.error('Failed to reject request:', error);
  } finally {
    isProcessing.value = false;
  }
}

// --- Navigation ---
const page = usePage();
const previousUrl = ref(document.referrer);

function goBack() {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    router.visit('/'); // fallback to home if no history
  }
}
</script>

<style scoped>
.zoom-content {
  zoom: 0.90;
}

.compact-table :deep(table) {
  @apply text-xs;
}

.compact-table :deep(th),
.compact-table :deep(td) {
  @apply px-2 py-2;
}

.compact-table :deep(th) {
  @apply text-xs font-medium text-gray-500 uppercase tracking-wider;
}

/* Override DataTable's left padding if needed */
:deep(.datatable) {
  margin-left: 2rem;
}

:deep(.datatable-table) {
  width: 100%;
}

/* Further reduce table row padding for more compact rows */
:deep(.datatable-table td) {
  padding-top: 0.375rem !important;
  padding-bottom: 0.375rem !important;
}

/* Further reduce table header padding */
:deep(.datatable-table th) {
  padding-top: 0.5rem !important;
  padding-bottom: 0.5rem !important;
  font-size: 0.875rem !important;
}

/* Reduce button sizes in the table */
:deep(.datatable-table .btn) {
  padding: 0.25rem 0.5rem !important;
  font-size: 0.75rem !important;
}
</style>