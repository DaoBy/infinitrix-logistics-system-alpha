<template>
  <GuestLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
          Delivery Request Details: #{{ deliveryRequest.id }}
        </h2>
        <div class="flex space-x-2">
          <SecondaryButton @click="$inertia.visit(route('customer.delivery-requests.index'))">
            Back to List
          </SecondaryButton>
          <PrimaryButton
            v-if="['pending', 'draft'].includes(deliveryRequest.status)"
            @click="$inertia.visit(route('customer.delivery-requests.edit', deliveryRequest.id))"
          >
            Edit Request
          </PrimaryButton>
          <DangerButton
            v-if="deliveryRequest.status === 'pending'"
            @click="openCancelModal"
          >
            Cancel Request
          </DangerButton>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Status / Success / Error Alerts -->
        <div v-if="status || success || error" class="mb-6 space-y-2">
          <div v-if="status" class="p-4 bg-blue-100 text-blue-800 rounded">{{ status }}</div>
          <div v-if="success" class="p-4 bg-green-100 text-green-800 rounded">{{ success }}</div>
          <div v-if="error" class="p-4 bg-red-100 text-red-800 rounded">{{ error }}</div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200 space-y-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Delivery Information -->
              <section class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900">Delivery Information</h3>
                <div>
                  <label class="block text-sm font-medium text-gray-500">Status</label>
                  <span :class="getStatusClass(deliveryRequest.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                    {{ deliveryRequest.status }}
                  </span>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-500">Request Date</label>
                  <p class="mt-1 text-sm text-gray-900">{{ formatDate(deliveryRequest.created_at) }}</p>
                </div>
                <div v-if="deliveryRequest.approved_at">
                  <label class="block text-sm font-medium text-gray-500">Approved Date</label>
                  <p class="mt-1 text-sm text-gray-900">{{ formatDate(deliveryRequest.approved_at) }}</p>
                </div>
                <div v-if="deliveryRequest.rejected_at">
                  <label class="block text-sm font-medium text-gray-500">Rejected Date</label>
                  <p class="mt-1 text-sm text-gray-900">{{ formatDate(deliveryRequest.rejected_at) }}</p>
                </div>
                <div v-if="deliveryRequest.rejection_reason">
                  <label class="block text-sm font-medium text-gray-500">Rejection Reason</label>
                  <p class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ deliveryRequest.rejection_reason }}</p>
                </div>
                <div v-if="deliveryRequest.deliveryOrder">
                  <label class="block text-sm font-medium text-gray-500">Tracking Number</label>
                  <p class="mt-1 text-sm text-gray-900">{{ deliveryRequest.deliveryOrder.tracking_number }}</p>
                </div>
              </section>

              <!-- Payment Information -->
              <section class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900">Payment</h3>
                <div>
                  <label class="block text-sm font-medium text-gray-500">Total Amount</label>
                  <p class="mt-1 text-sm text-gray-900">₱{{ deliveryRequest.total_price.toFixed(2) }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-500">Payment Method</label>
                  <p class="mt-1 text-sm text-gray-900 capitalize">{{ deliveryRequest.payment_method }}</p>
                </div>
                <div v-if="deliveryRequest.deliveryOrder">
                  <label class="block text-sm font-medium text-gray-500">Payment Status</label>
                  <p class="mt-1 text-sm text-gray-900 capitalize">{{ deliveryRequest.deliveryOrder.payment_status }}</p>
                </div>
              </section>

              <!-- Pickup Location -->
              <section class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900">Pickup Location</h3>
                <div>
                  <label class="block text-sm font-medium text-gray-500">Branch</label>
                  <p class="mt-1 text-sm text-gray-900">{{ deliveryRequest.pickUpBranch?.name || 'N/A' }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-500">Address</label>
                  <p class="mt-1 text-sm text-gray-900">{{ deliveryRequest.pickUpBranch?.address || 'N/A' }}</p>
                </div>
              </section>

              <!-- Dropoff Location -->
              <section class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900">Dropoff Location</h3>
                <div>
                  <label class="block text-sm font-medium text-gray-500">Branch</label>
                  <p class="mt-1 text-sm text-gray-900">{{ deliveryRequest.dropOffBranch?.name || 'N/A' }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-500">Address</label>
                  <p class="mt-1 text-sm text-gray-900">{{ deliveryRequest.dropOffBranch?.address || 'N/A' }}</p>
                </div>
              </section>
            </div>

            <!-- Receiver Information -->
            <section class="mt-8">
              <h3 class="text-lg font-medium text-gray-900">Receiver Details</h3>
              <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-500">Name</label>
                  <p class="mt-1 text-sm text-gray-900">{{ deliveryRequest.receiver.name || deliveryRequest.receiver.company_name }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-500">Contact</label>
                  <p class="mt-1 text-sm text-gray-900">{{ deliveryRequest.receiver.phone }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-500">Email</label>
                  <p class="mt-1 text-sm text-gray-900">{{ deliveryRequest.receiver.email }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-500">Address</label>
                  <p class="mt-1 text-sm text-gray-900">{{ formatAddress(deliveryRequest.receiver) }}</p>
                </div>
                <div v-if="deliveryRequest.receiver.company_name">
                  <label class="block text-sm font-medium text-gray-500">Company</label>
                  <p class="mt-1 text-sm text-gray-900">{{ deliveryRequest.receiver.company_name }}</p>
                </div>
              </div>
            </section>

            <!-- Packages Section -->
            <section class="mt-8">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Packages</h3>
              <DataTable 
                :columns="packageColumns" 
                :data="deliveryRequest.packages" 
                :sort-field="packageSortField" 
                :sort-direction="packageSortDirection" 
                @sort="handlePackageSort"
              >
                <template #dimensions="{ row }">
                  {{ row.height }} × {{ row.width }} × {{ row.length }} cm
                </template>
                <template #value="{ row }">
                  ₱{{ row.value.toFixed(2) }}
                </template>
                <template #photo="{ row }">
                  <img 
                    v-if="row.photo_path" 
                    :src="`/storage/${row.photo_path}`" 
                    alt="Package photo" 
                    class="h-10 w-10 object-cover rounded"
                  />
                  <span v-else>No photo</span>
                </template>
              </DataTable>
            </section>
          </div>
        </div>
      </div>
    </div>

    <!-- Cancel Confirmation Modal -->
    <Modal :show="showCancelModal" @close="closeCancelModal">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">Cancel Delivery Request?</h2>
        <p class="mt-1 text-sm text-gray-600">
          Are you sure you want to cancel this delivery request?
        </p>
        <div class="mt-6 flex justify-end space-x-4">
          <SecondaryButton @click="closeCancelModal">
            Cancel
          </SecondaryButton>
          <DangerButton @click="handleCancel" :disabled="isProcessing">
            <span v-if="isProcessing">Cancelling...</span>
            <span v-else>Confirm Cancel</span>
          </DangerButton>
        </div>
      </div>
    </Modal>
  </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import DataTable from '@/Components/DataTable.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  deliveryRequest: { type: Object, required: true },
  status: String,
  success: String,
  error: String,
});

const showCancelModal = ref(false);
const isProcessing = ref(false);

const packageColumns = [
  { field: 'item_name', header: 'Item', sortable: true },
  { field: 'category', header: 'Category', sortable: true },
  { field: 'dimensions', header: 'Dimensions', sortable: false },
  { field: 'weight', header: 'Weight (kg)', sortable: true },
  { field: 'quantity', header: 'Quantity', sortable: true },
  { field: 'value', header: 'Value', sortable: true },
  { field: 'photo', header: 'Photo', sortable: false },
];

const packageSortField = ref('');
const packageSortDirection = ref('asc');

function getStatusClass(status) {
  switch (status) {
    case 'pending': return 'bg-yellow-100 text-yellow-800';
    case 'approved': return 'bg-green-100 text-green-800';
    case 'rejected': return 'bg-red-100 text-red-800';
    case 'draft': return 'bg-gray-100 text-gray-800';
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

function openCancelModal() {
  showCancelModal.value = true;
}

function closeCancelModal() {
  showCancelModal.value = false;
}

async function handleCancel() {
  if (isProcessing.value) return;
  isProcessing.value = true;
  
  try {
    await router.delete(route('customer.delivery-requests.destroy', props.deliveryRequest.id));
    router.visit(route('customer.delivery-requests.index'), {
      onSuccess: () => {
        // Success handled by Inertia
      },
    });
  } catch (error) {
    console.error('Failed to cancel request:', error);
  } finally {
    isProcessing.value = false;
  }
}

function handlePackageSort({ field, direction }) {
  packageSortField.value = field;
  packageSortDirection.value = direction;
  props.deliveryRequest.packages.sort((a, b) => {
    const valA = a[field];
    const valB = b[field];
    if (valA < valB) return direction === 'asc' ? -1 : 1;
    if (valA > valB) return direction === 'asc' ? 1 : -1;
    return 0;
  });
}
</script>