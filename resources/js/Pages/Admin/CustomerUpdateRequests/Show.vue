<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Update Request: {{ requestData.customer?.name || 'N/A' }}
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Review and process profile update request
          </p>
        </div>

        <!-- Right: Buttons -->
        <div class="flex gap-2">
          <SecondaryButton @click="goBack">Back to Requests</SecondaryButton>
        </div>
      </div>
    </template>

    <!-- ZOOM CONTENT WRAPPER -->
    <div class="zoom-content">
      <!-- MAIN CONTENT CONTAINER WITH PROPER PADDING -->
      <div class="px-6 py-4">
        <!-- Status Messages -->
        <div v-if="status || success || error" class="mb-4">
          <div v-if="status" class="p-3 bg-blue-100 text-blue-800 rounded text-sm">{{ status }}</div>
          <div v-if="success" class="p-3 bg-green-100 text-green-800 rounded text-sm">{{ success }}</div>
          <div v-if="error" class="p-3 bg-red-100 text-red-800 rounded text-sm">{{ error }}</div>
        </div>

        <!-- Customer Summary -->
        <div class="mb-4 bg-white p-3 rounded-lg shadow-sm border">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
            <div>
              <label class="block font-medium text-gray-700">Customer Type</label>
              <p class="mt-0.5 text-gray-900 capitalize">{{ customer.customer_category }}</p>
            </div>
            <div>
              <label class="block font-medium text-gray-700">Field Locking</label>
              <p class="mt-0.5" :class="customer.critical_fields_locked ? 'text-amber-600' : 'text-green-600'">
                {{ customer.critical_fields_locked ? 'Locked' : 'Editable' }}
              </p>
            </div>
            <div>
              <label class="block font-medium text-gray-700">Delivery History</label>
              <p class="mt-0.5" :class="customer.has_delivery_history ? 'text-blue-600' : 'text-gray-600'">
                {{ customer.has_delivery_history ? 'Has History' : 'No History' }}
              </p>
            </div>
            <div>
              <label class="block font-medium text-gray-700">Status</label>
              <span 
                class="px-2 py-0.5 rounded-full text-xs font-medium capitalize mt-0.5 inline-block"
                :class="statusBadgeClass"
              >
                {{ formatStatus(requestData.status) }}
              </span>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
          <!-- Current Profile Information -->
          <div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg h-full">
              <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                <h3 class="text-sm font-medium text-gray-900">Current Profile</h3>
                <p class="text-xs text-gray-600 mt-0.5">Submitted: {{ formatDate(requestData.created_at) }}</p>
              </div>
              <div class="p-4 space-y-3">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <ProfileField 
                    label="First Name" 
                    :value="customer.first_name"
                    compact
                  />
                  <ProfileField 
                    label="Middle Name" 
                    :value="customer.middle_name"
                    compact
                  />
                  <ProfileField 
                    label="Last Name" 
                    :value="customer.last_name"
                    compact
                  />
                  <ProfileField 
                    label="Email" 
                    :value="customer.email"
                    compact
                  />
                  <ProfileField 
                    label="Mobile" 
                    :value="customer.mobile"
                    compact
                  />
                  <ProfileField 
                    label="Phone" 
                    :value="customer.phone"
                    compact
                  />
                  <ProfileField 
                    label="Building No." 
                    :value="customer.building_number"
                    compact
                  />
                  <ProfileField 
                    label="Street" 
                    :value="customer.street"
                    compact
                  />
                  <ProfileField 
                    label="Barangay" 
                    :value="customer.barangay"
                    compact
                  />
                  <ProfileField 
                    label="City" 
                    :value="customer.city"
                    compact
                  />
                  <ProfileField 
                    label="Province" 
                    :value="customer.province"
                    compact
                  />
                  <ProfileField 
                    label="ZIP Code" 
                    :value="customer.zip_code"
                    compact
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Requested Changes & Details -->
          <div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg h-full">
              <!-- Requested Changes -->
              <div class="border-b border-gray-200">
                <div class="px-4 py-3 bg-gray-50">
                  <h3 class="text-sm font-medium text-gray-900">Requested Changes</h3>
                  <p class="text-xs text-gray-600 mt-0.5">{{ changedFieldsCount }} field(s) modified</p>
                </div>
                <div class="p-4">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <ProfileField 
                      label="First Name" 
                      :value="requestData.first_name" 
                      :current-value="customer?.first_name"
                      :has-change="requestData.first_name !== null"
                      compact
                    />
                    <ProfileField 
                      label="Middle Name" 
                      :value="requestData.middle_name" 
                      :current-value="customer?.middle_name"
                      :has-change="requestData.middle_name !== null"
                      compact
                    />
                    <ProfileField 
                      label="Last Name" 
                      :value="requestData.last_name" 
                      :current-value="customer?.last_name"
                      :has-change="requestData.last_name !== null"
                      compact
                    />
                    <ProfileField 
                      label="Email" 
                      :value="requestData.email" 
                      :current-value="customer?.email"
                      :has-change="requestData.email !== null"
                      compact
                    />
                    <ProfileField 
                      label="Mobile" 
                      :value="requestData.mobile" 
                      :current-value="customer?.mobile"
                      :has-change="requestData.mobile !== null"
                      compact
                    />
                    <ProfileField 
                      label="Phone" 
                      :value="requestData.phone" 
                      :current-value="customer?.phone"
                      :has-change="requestData.phone !== null"
                      compact
                    />
                    <ProfileField 
                      label="Building No." 
                      :value="requestData.building_number" 
                      :current-value="customer?.building_number"
                      :has-change="requestData.building_number !== null"
                      compact
                    />
                    <ProfileField 
                      label="Street" 
                      :value="requestData.street" 
                      :current-value="customer?.street"
                      :has-change="requestData.street !== null"
                      compact
                    />
                    <ProfileField 
                      label="Barangay" 
                      :value="requestData.barangay" 
                      :current-value="customer?.barangay"
                      :has-change="requestData.barangay !== null"
                      compact
                    />
                    <ProfileField 
                      label="City" 
                      :value="requestData.city" 
                      :current-value="customer?.city"
                      :has-change="requestData.city !== null"
                      compact
                    />
                    <ProfileField 
                      label="Province" 
                      :value="requestData.province" 
                      :current-value="customer?.province"
                      :has-change="requestData.province !== null"
                      compact
                    />
                    <ProfileField 
                      label="ZIP Code" 
                      :value="requestData.zip_code" 
                      :current-value="customer?.zip_code"
                      :has-change="requestData.zip_code !== null"
                      compact
                    />
                  </div>
                </div>
              </div>

              <!-- Reason Section -->
              <div class="border-b border-gray-200">
                <div class="px-4 py-3 bg-gray-50">
                  <h3 class="text-sm font-medium text-gray-900">Reason for Changes</h3>
                </div>
                <div class="p-4">
                  <div class="bg-gray-50 p-3 rounded text-sm">
                    <p class="text-gray-700">{{ requestData.reason || 'No reason provided' }}</p>
                  </div>
                </div>
              </div>

              <!-- Review Information (if reviewed) -->
              <div v-if="requestData.reviewed_by" class="border-b border-gray-200">
                <div class="px-4 py-3 bg-gray-50">
                  <h3 class="text-sm font-medium text-gray-900">Review Information</h3>
                </div>
                <div class="p-4">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div>
                      <label class="block font-medium text-gray-700 text-xs">Reviewed By</label>
                      <p class="mt-0.5 text-gray-900">{{ requestData.reviewer?.name || 'N/A' }}</p>
                    </div>
                    <div>
                      <label class="block font-medium text-gray-700 text-xs">Reviewed At</label>
                      <p class="mt-0.5 text-gray-900">{{ formatDate(requestData.reviewed_at) }}</p>
                    </div>
                    <div v-if="requestData.admin_notes && requestData.status === 'rejected'" class="md:col-span-2">
                      <label class="block font-medium text-gray-700 text-xs">Rejection Reason</label>
                      <p class="mt-0.5 text-red-600 bg-red-50 p-2 rounded text-sm">{{ requestData.admin_notes }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Action Buttons (only for pending requests) -->
              <div v-if="requestData.status === 'pending'" class="px-4 py-3 bg-gray-50">
                <div class="flex justify-end space-x-3">
                  <DangerButton @click="showRejectModal = true" class="text-xs py-1.5 px-3">Reject</DangerButton>
                  <PrimaryButton @click="showApproveModal = true" class="text-xs py-1.5 px-3">Approve</PrimaryButton>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Approve Confirmation Modal -->
    <Modal :show="showApproveModal" @close="showApproveModal = false" max-width="md">
      <div class="p-6">
        <div class="flex items-center justify-center w-12 h-12 mx-auto bg-green-100 rounded-full">
          <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        
        <div class="mt-4 text-center">
          <h2 class="text-lg font-medium text-gray-900">Approve Update Request?</h2>
          <p class="mt-2 text-sm text-gray-600">
            Approve profile changes for <strong>{{ customer.name }}</strong>?
          </p>
          
          <div v-if="changedFieldsCount > 0" class="mt-3 p-2 bg-blue-50 rounded text-xs">
            <p class="font-medium text-blue-800">
              {{ changedFieldsCount }} field(s) will be updated
            </p>
          </div>
        </div>
        
        <div class="mt-6 flex justify-end space-x-3">
          <SecondaryButton @click="showApproveModal = false" class="text-sm">Cancel</SecondaryButton>
          <PrimaryButton @click="approveRequest" :disabled="isApproving" class="text-sm">
            <span v-if="isApproving">Processing...</span>
            <span v-else>Confirm Approval</span>
          </PrimaryButton>
        </div>
      </div>
    </Modal>

    <!-- Reject Confirmation Modal -->
    <Modal :show="showRejectModal" @close="showRejectModal = false" max-width="md">
      <div class="p-6">
        <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full">
          <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        
        <div class="mt-4 text-center">
          <h2 class="text-lg font-medium text-gray-900">Reject Update Request?</h2>
          <p class="mt-2 text-sm text-gray-600">
            Reject request from <strong>{{ customer.name }}</strong>?
          </p>
        </div>
        
        <div class="mt-4">
          <InputLabel for="rejection_reason" value="Rejection Reason *" class="text-sm" />
          <textarea
            id="rejection_reason"
            rows="3"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
            v-model="rejectionReason"
            required
            placeholder="Reason for rejection (visible to customer)..."
            :class="{ 'border-red-300': rejectionError }"
          ></textarea>
          <div v-if="rejectionError" class="mt-1 text-sm text-red-600">
            {{ rejectionError }}
          </div>
          <p class="mt-1 text-xs text-gray-500">
            Minimum 10 characters required
          </p>
        </div>
        
        <div class="mt-6 flex justify-end space-x-3">
          <SecondaryButton @click="showRejectModal = false" class="text-sm">Cancel</SecondaryButton>
          <DangerButton @click="rejectRequest" :disabled="!rejectionReason.trim() || isRejecting" class="text-sm">
            <span v-if="isRejecting">Processing...</span>
            <span v-else>Confirm Rejection</span>
          </DangerButton>
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
import Modal from '@/Components/Modal.vue';
import ProfileField from '@/Components/ProfileField.vue';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  request: Object,
  customer: Object,
  status: String,
  success: String,
  error: String,
});

const showApproveModal = ref(false);
const showRejectModal = ref(false);
const rejectionReason = ref('');
const rejectionError = ref('');
const isRejecting = ref(false);
const isApproving = ref(false);
const requestData = ref(props.request);
const customer = ref(props.customer);

// Computed properties
const statusBadgeClass = computed(() => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800'
  };
  return classes[requestData.value.status] || 'bg-gray-100 text-gray-800';
});

const changedFieldsCount = computed(() => {
  const fields = [
    'first_name', 'middle_name', 'last_name', 'email', 'mobile', 'phone',
    'building_number', 'street', 'barangay', 'city', 'province', 'zip_code'
  ];
  return fields.filter(field => requestData.value[field] !== null).length;
});

// Methods
const formatStatus = (status) => {
  const statusMap = {
    pending: 'Pending',
    approved: 'Approved',
    rejected: 'Rejected'
  };
  return statusMap[status] || status;
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const goBack = () => {
  router.visit(route('admin.customer-update-requests.index'));
};

const approveRequest = () => {
  isApproving.value = true;
  
  router.post(route('admin.customer-update-requests.approve', requestData.value.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      showApproveModal.value = false;
      isApproving.value = false;
      router.reload({ only: ['request', 'customer'] });
    },
    onError: () => {
      isApproving.value = false;
    }
  });
};

const rejectRequest = () => {
  if (!rejectionReason.value.trim()) {
    rejectionError.value = 'Please provide a rejection reason';
    return;
  }

  if (rejectionReason.value.trim().length < 10) {
    rejectionError.value = 'Rejection reason must be at least 10 characters long';
    return;
  }

  isRejecting.value = true;
  rejectionError.value = '';

  router.post(route('admin.customer-update-requests.reject', requestData.value.id), {
    rejection_reason: rejectionReason.value
  }, {
    preserveScroll: true,
    onSuccess: () => {
      showRejectModal.value = false;
      rejectionReason.value = '';
      isRejecting.value = false;
      router.reload({ only: ['request', 'customer'] });
    },
    onError: (errors) => {
      if (errors.rejection_reason) {
        rejectionError.value = errors.rejection_reason;
      } else {
        rejectionError.value = 'An error occurred while rejecting the request';
      }
      isRejecting.value = false;
    }
  });
};
</script>

<style scoped>
.zoom-content {
  zoom: 0.90;
}
</style>