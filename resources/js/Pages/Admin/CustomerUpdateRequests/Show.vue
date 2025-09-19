<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-6">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
          Customer Update Request: {{ requestData.customer?.name || 'N/A' }}
        </h2>
        <div class="flex space-x-2">
          <SecondaryButton @click="goBack">Back to List</SecondaryButton>
        </div>
      </div>
    </template>

    <div class="px-6">
      <!-- Status Messages -->
      <div v-if="status || success || error" class="mb-6 max-w-7xl mx-auto">
        <div v-if="status" class="p-4 bg-blue-100 text-blue-800 rounded">
          {{ status }}
        </div>
        <div v-if="success" class="p-4 bg-green-100 text-green-800 rounded">
          {{ success }}
        </div>
        <div v-if="error" class="p-4 bg-red-100 text-red-800 rounded">
          {{ error }}
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg max-w-7xl mx-auto">
        <!-- Request Info Header -->
        <div class="bg-gray-50 px-6 py-4 border-b">
          <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
            <div>
              <h3 class="text-lg font-medium text-gray-900">Request Details</h3>
              <p class="text-sm text-gray-600">Submitted on {{ formatDate(requestData.created_at) }}</p>
            </div>
            <div class="mt-2 md:mt-0">
              <span :class="statusBadgeClass" class="px-3 py-1 rounded-full text-sm font-medium">
                {{ formatStatus(requestData.status) }}
              </span>
            </div>
          </div>
        </div>

        <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-8">
          <!-- Current Profile Information -->
          <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">
              Current Profile Information
            </h3>
            <div class="space-y-4">
              <div v-if="customer">
                <ProfileField 
                  label="Name" 
                  :value="customer.name" 
                />
                <ProfileField 
                  label="Email" 
                  :value="customer.email" 
                />
                <ProfileField 
                  label="Mobile" 
                  :value="customer.mobile" 
                />
                <ProfileField 
                  label="Phone" 
                  :value="customer.phone" 
                />
                <ProfileField 
                  label="Address" 
                  :value="customer.address" 
                />
              </div>
            </div>
          </div>

          <!-- Requested Changes -->
          <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">
              Requested Changes
            </h3>
            <div class="space-y-4">
              <ProfileField 
                label="First Name" 
                :value="requestData.first_name" 
                :current-value="customer?.first_name"
                :has-change="requestData.first_name !== null"
              />
              <ProfileField 
                label="Middle Name" 
                :value="requestData.middle_name" 
                :current-value="customer?.middle_name"
                :has-change="requestData.middle_name !== null"
              />
              <ProfileField 
                label="Last Name" 
                :value="requestData.last_name" 
                :current-value="customer?.last_name"
                :has-change="requestData.last_name !== null"
              />
              <ProfileField 
                label="Email" 
                :value="requestData.email" 
                :current-value="customer?.email"
                :has-change="requestData.email !== null"
              />
              <ProfileField 
                label="Mobile" 
                :value="requestData.mobile" 
                :current-value="customer?.mobile"
                :has-change="requestData.mobile !== null"
              />
              <ProfileField 
                label="Phone" 
                :value="requestData.phone" 
                :current-value="customer?.phone"
                :has-change="requestData.phone !== null"
              />
              <ProfileField 
                label="Building Number" 
                :value="requestData.building_number" 
                :current-value="customer?.building_number"
                :has-change="requestData.building_number !== null"
              />
              <ProfileField 
                label="Street" 
                :value="requestData.street" 
                :current-value="customer?.street"
                :has-change="requestData.street !== null"
              />
              <ProfileField 
                label="Barangay" 
                :value="requestData.barangay" 
                :current-value="customer?.barangay"
                :has-change="requestData.barangay !== null"
              />
              <ProfileField 
                label="City" 
                :value="requestData.city" 
                :current-value="customer?.city"
                :has-change="requestData.city !== null"
              />
              <ProfileField 
                label="Province" 
                :value="requestData.province" 
                :current-value="customer?.province"
                :has-change="requestData.province !== null"
              />
              <ProfileField 
                label="ZIP Code" 
                :value="requestData.zip_code" 
                :current-value="customer?.zip_code"
                :has-change="requestData.zip_code !== null"
              />
            </div>
          </div>
        </div>

        <!-- Reason Section -->
        <div class="px-6 pb-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Reason for Changes</h3>
          <div class="bg-gray-50 p-4 rounded-lg">
            <p class="text-gray-700">{{ requestData.reason || 'No reason provided' }}</p>
          </div>
        </div>

        <!-- Review Information (if reviewed) -->
        <div v-if="requestData.reviewed_by" class="px-6 pb-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Review Information</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Reviewed By</label>
              <p class="mt-1 text-sm text-gray-900">{{ requestData.reviewer?.name || 'N/A' }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Reviewed At</label>
              <p class="mt-1 text-sm text-gray-900">{{ formatDate(requestData.reviewed_at) }}</p>
            </div>
          </div>
        </div>

        <!-- Action Buttons (only for pending requests) -->
        <div v-if="requestData.status === 'pending'" class="px-6 py-4 bg-gray-50 border-t">
          <div class="flex justify-end space-x-4">
            <DangerButton @click="showRejectModal = true">Reject</DangerButton>
            <PrimaryButton @click="approveRequest">Approve</PrimaryButton>
          </div>
        </div>
      </div>
    </div>

    <!-- Reject Confirmation Modal -->
    <Modal :show="showRejectModal" @close="showRejectModal = false">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">Reject Update Request?</h2>
        <p class="mt-1 text-sm text-gray-600">
          Please provide a reason for rejecting this profile update request.
        </p>
        
        <div class="mt-4">
          <InputLabel for="rejection_reason" value="Rejection Reason *" />
          <textarea
            id="rejection_reason"
            rows="4"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
            v-model="rejectionReason"
            required
            placeholder="Explain why this request is being rejected..."
          ></textarea>
          <InputError class="mt-2" :message="form.errors.rejection_reason" />
        </div>
        
        <div class="mt-6 flex justify-end space-x-4">
          <SecondaryButton @click="showRejectModal = false">Cancel</SecondaryButton>
          <DangerButton @click="rejectRequest" :disabled="!rejectionReason.trim()">
            Confirm Reject
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
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import ProfileField from '@/Components/ProfileField.vue'; // Import the separate component
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  request: Object,
  customer: Object,
  status: String,
  success: String,
  error: String,
});

const showRejectModal = ref(false);
const rejectionReason = ref('');
const requestData = ref(props.request);
const customer = ref(props.customer);

const statusBadgeClass = computed(() => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800'
  };
  return classes[requestData.value.status] || 'bg-gray-100 text-gray-800';
});

const formatStatus = (status) => {
  const statusMap = {
    pending: 'Pending Review',
    approved: 'Approved',
    rejected: 'Rejected'
  };
  return statusMap[status] || status;
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const goBack = () => {
  router.visit(route('admin.customer-update-requests.index'));
};

const approveRequest = () => {
  if (confirm('Are you sure you want to approve these changes?')) {
    router.post(route('admin.customer-update-requests.approve', requestData.value.id), {}, {
      preserveScroll: true,
      onSuccess: () => {
        router.reload({ only: ['request', 'customer'] });
      }
    });
  }
};

const rejectRequest = () => {
  if (!rejectionReason.value.trim()) {
    alert('Please provide a rejection reason');
    return;
  }

  router.post(route('admin.customer-update-requests.reject', requestData.value.id), {
    rejection_reason: rejectionReason.value
  }, {
    preserveScroll: true,
    onSuccess: () => {
      showRejectModal.value = false;
      rejectionReason.value = '';
      router.reload({ only: ['request', 'customer'] });
    }
  });
};
</script>