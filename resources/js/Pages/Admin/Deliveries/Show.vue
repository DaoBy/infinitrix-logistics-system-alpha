<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
          Delivery Request: {{ delivery.reference_number || delivery.order_number || '#' + delivery.id }}
        </h2>
        <div class="flex space-x-2">
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

    <div class="py-4">
      <!-- Make the main container more compact by reducing max-width and padding -->
      <div class="max-w-4xl mx-auto px-2 lg:px-4 space-y-4">
        <!-- Status / Success / Error Alerts -->
        <div v-if="status || success || error" class="mb-4 space-y-2">
          <div v-if="status" class="p-3 bg-blue-100 text-blue-800 rounded">{{ status }}</div>
          <div v-if="success" class="p-3 bg-green-100 text-green-800 rounded">{{ success }}</div>
          <div v-if="error" class="p-3 bg-red-100 text-red-800 rounded">{{ error }}</div>
        </div>

        <!-- Delivery Information Card -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 mb-2">
          <div class="p-3 bg-indigo-50 border-b border-gray-200 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
              <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                  Reference #
                </span>
                <span class="text-base font-bold text-indigo-900 tracking-wide">
                  {{ delivery.reference_number || delivery.order_number || ('DR-' + String(delivery.id).padStart(6, '0')) }}
                </span>
              </div>
              <div class="mt-1 text-xs text-gray-500">
                Delivery ID: DO-{{ String(delivery.id).padStart(6, '0') }}
                <span v-if="delivery.created_at">&nbsp;|&nbsp;Created: {{ formatDate(delivery.created_at) }}</span>
              </div>
            </div>
            <div class="mt-2 md:mt-0 flex items-center gap-2">
              <span class="font-medium text-gray-900">
                ₱{{ Number(delivery.total_price).toFixed(2) }}
              </span>
              <span class="text-xs text-gray-500">Total Amount</span>
            </div>
          </div>
          <div class="p-3 grid grid-cols-1 md:grid-cols-2 gap-3">
            <!-- Delivery Info -->
            <section class="space-y-2">
              <h3 class="text-base font-medium text-gray-900">Delivery Information</h3>
              <div>
                <label class="block text-xs font-medium text-gray-500">Status</label>
                <span
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                  :class="deliveryStatusBadgeClass(delivery.status)"
                >
                  {{ deliveryStatusBadgeText(delivery.status) }}
                </span>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500">Reference Number</label>
                <p class="mt-0.5 text-sm text-gray-900">
                  {{ delivery.reference_number || delivery.order_number || ('DR-' + String(delivery.id).padStart(6, '0')) }}
                </p>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500">Order Number</label>
                <p class="mt-0.5 text-sm text-gray-900">{{ delivery.order_number || 'N/A' }}</p>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500">Request Date</label>
                <p class="mt-0.5 text-sm text-gray-900">{{ formatDate(delivery.created_at) }}</p>
              </div>
              <div v-if="delivery.approved_at">
                <label class="block text-xs font-medium text-gray-500">Approved Date</label>
                <p class="mt-0.5 text-sm text-gray-900">{{ formatDate(delivery.approved_at) }}</p>
              </div>
              <div v-if="delivery.approved_by">
                <label class="block text-xs font-medium text-gray-500">Approved By</label>
                <p class="mt-0.5 text-sm text-gray-900">{{ delivery.approved_by }}</p>
              </div>
              <div v-if="delivery.rejected_at">
                <label class="block text-xs font-medium text-gray-500">Rejected Date</label>
                <p class="mt-0.5 text-sm text-gray-900">{{ formatDate(delivery.rejected_at) }}</p>
              </div>
              <div v-if="delivery.rejected_by">
                <label class="block text-xs font-medium text-gray-500">Rejected By</label>
                <p class="mt-0.5 text-sm text-gray-900">{{ delivery.rejected_by }}</p>
              </div>
              <div v-if="delivery.rejection_reason">
                <label class="block text-xs font-medium text-gray-500">Rejection Reason</label>
                <p class="mt-0.5 text-sm text-gray-900 whitespace-pre-wrap">{{ delivery.rejection_reason }}</p>
              </div>
            </section>

            <!-- Payment Info -->
            <section class="space-y-2">
              <h3 class="text-base font-medium text-gray-900">Payment</h3>
              <div>
                <label class="block text-xs font-medium text-gray-500">Total Amount</label>
                <p class="mt-0.5 text-sm text-gray-900">₱{{ delivery.total_price || '0.00' }}</p>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500">Payment Method</label>
                <p class="mt-0.5 text-sm text-gray-900 capitalize">{{ delivery.payment_method || 'N/A' }}</p>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500">Payment Status</label>
                <div class="mt-0.5">
                  <span
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                    :class="paymentStatusBadgeClass(delivery.payment_status)"
                  >
                    {{ paymentStatusBadgeText(delivery.payment_status) }}
                  </span>
                </div>
              </div>
            </section>
          </div>
        </div>

        <!-- Sender/Receiver Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-2">
          <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-3">
            <h3 class="text-base font-medium text-gray-900 mb-1">Sender</h3>
            <div>
              <label class="block text-xs font-medium text-gray-500">Name</label>
              <p class="mt-0.5 text-sm text-gray-900">
                {{ delivery.sender?.name || delivery.sender?.company_name || 'N/A' }}
              </p>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500">Contact</label>
              <p class="mt-0.5 text-sm text-gray-900">
                {{ delivery.sender?.phone || delivery.sender?.mobile || 'N/A' }}
              </p>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500">Email</label>
              <p class="mt-0.5 text-sm text-gray-900">
                {{ delivery.sender?.email || 'N/A' }}
              </p>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500">Address</label>
              <p class="mt-0.5 text-sm text-gray-900">
                {{ formatAddress(delivery.sender) }}
              </p>
            </div>
          </div>
          <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-3">
            <h3 class="text-base font-medium text-gray-900 mb-1">Receiver</h3>
            <div>
              <label class="block text-xs font-medium text-gray-500">Name</label>
              <p class="mt-0.5 text-sm text-gray-900">
                {{ delivery.receiver?.name || delivery.receiver?.company_name || 'N/A' }}
              </p>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500">Contact</label>
              <p class="mt-0.5 text-sm text-gray-900">
                {{ delivery.receiver?.phone || delivery.receiver?.mobile || 'N/A' }}
              </p>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500">Email</label>
              <p class="mt-0.5 text-sm text-gray-900">
                {{ delivery.receiver?.email || 'N/A' }}
              </p>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500">Address</label>
              <p class="mt-0.5 text-sm text-gray-900">
                {{ formatAddress(delivery.receiver) }}
              </p>
            </div>
          </div>
        </div>

        <!-- Packages Section -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-3 mb-2">
          <h3 class="text-base font-medium text-gray-900 mb-2">Packages ({{ delivery.packages?.length || 0 }})</h3>
          <DataTable 
            v-if="delivery.packages && delivery.packages.length"
            :columns="packageColumns" 
            :data="sortedPackages" 
            :sort-field="packageSortField" 
            :sort-direction="packageSortDirection" 
            @sort="handlePackageSort"
          >
            <template #dimensions="{ row }">
              {{ row.height }} × {{ row.width }} × {{ row.length }} cm
            </template>
            <template #value="{ row }">
              ₱{{ row.value?.toFixed(2) || '0.00' }}
            </template>
            <template #photo="{ row }">
              <img 
                v-if="row.photo_path" 
                :src="`/storage/${row.photo_path}`" 
                alt="Package photo" 
                class="h-10 w-10 object-cover rounded cursor-pointer"
                @click="openImageModal(`/storage/${row.photo_path}`)"
              />
              <span v-else>No photo</span>
            </template>
            <template #status_history="{ row }">
              <SecondaryButton
                class="!px-2 !py-1 !text-xs !font-normal"
                @click="showStatusHistory(row)"
              >
                View History
              </SecondaryButton>
            </template>
          </DataTable>
          <div v-else class="text-gray-500">
            No packages found for this delivery.
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

    <!-- Image Preview Modal -->
    <Modal :show="showImageModal" @close="closeImageModal" max-width="4xl">
      <div class="p-4">
        <img :src="currentImage" alt="Package photo preview" class="max-h-[80vh] mx-auto" />
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
import StatusBadge from '@/Components/StatusBadge.vue';
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

const showStatusHistoryModal = ref(false);
const currentStatusHistory = ref([]);


// --- Table Sorting ---
const packageColumns = [
  { field: 'item_name', header: 'Item', sortable: true },
  { field: 'category', header: 'Category', sortable: true },
  { field: 'dimensions', header: 'Dimensions', sortable: false },
  { field: 'weight', header: 'Weight (kg)', sortable: true },
  { field: 'value', header: 'Value', sortable: true },
  { field: 'photo', header: 'Photo', sortable: false },
  { field: 'status_history', header: 'Status History', sortable: false },
];
const packageSortField = ref('item_name');
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
function getStatusClass(status) {
  switch (status) {
    case 'pending': return 'bg-yellow-100 text-yellow-800';
    case 'approved': return 'bg-green-100 text-green-800';
    case 'rejected': return 'bg-red-100 text-red-800';
    default: return 'bg-gray-100 text-gray-800';
  }
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


function paymentStatusBadgeClass(status) {
  if (!status) return 'bg-gray-200 text-gray-700';
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
  if (!status) return 'bg-gray-200 text-gray-700';
  const s = String(status).toLowerCase().trim();
  // Use the same green as paymentStatusBadgeClass for "completed"/"complete"
  if (s === 'completed' || s === 'complete') return 'bg-green-100 text-green-800';
  if (s === 'approved') return 'bg-blue-100 text-blue-800';
  if (s === 'pending') return 'bg-yellow-100 text-yellow-800';
  if (s === 'rejected') return 'bg-red-100 text-red-800';
  return 'bg-gray-200 text-gray-700';
}
function deliveryStatusBadgeText(status) {
  if (!status) return 'N/A';
  const s = String(status).toLowerCase().trim();
  if (s === 'completed') return 'Completed';
  if (s === 'complete') return 'Complete';
  if (s === 'approved') return 'Approved';
  if (s === 'pending') return 'Pending';
  if (s === 'rejected') return 'Rejected';
  return status.charAt(0).toUpperCase() + status.slice(1);
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