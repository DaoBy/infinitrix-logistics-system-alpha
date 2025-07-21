<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-6">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Payment Details
        </h2>
        <div class="flex space-x-2">
          <DangerButton
            v-if="!payment.verified_by"
            @click="confirmDelete"
            class="!px-4 !py-2 !text-xs"
          >
            Delete
          </DangerButton>
          <Link
            :href="route('collector.payments.index')"
            class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to collections
          </Link>
        </div>
      </div>
    </template>

    <div class="px-2 md:px-6 py-4 max-w-5xl mx-auto">
      <!-- Delivery Info -->
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between p-4 bg-indigo-50 dark:bg-indigo-900 border-b border-gray-200 dark:border-gray-700">
          <div>
            <div class="flex items-center gap-2">
              <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-800 dark:text-indigo-100">
                Reference #
              </span>
              <span class="text-lg font-bold text-indigo-900 dark:text-indigo-100 tracking-wide">
                {{ payment.delivery_request?.reference_number || ('DR-' + String(payment.delivery_request_id).padStart(6, '0')) }}
              </span>
            </div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-300">
              Delivery ID: DO-{{ String(payment.delivery_request_id).padStart(6, '0') }}
              <span v-if="payment.delivery_request?.created_at">&nbsp;|&nbsp;Created: {{ formatDate(payment.delivery_request.created_at) }}</span>
            </div>
          </div>
          <div class="mt-3 md:mt-0 flex items-center gap-2">
            <span class="font-medium text-gray-900 dark:text-gray-100">
              ₱{{ payment.delivery_request?.total_price !== undefined && payment.delivery_request?.total_price !== null
                ? Number(payment.delivery_request.total_price).toFixed(2)
                : '0.00'
              }}
            </span>
            <span class="text-xs text-gray-500 dark:text-gray-300">Total Amount</span>
          </div>
        </div>
        <div class="p-4 md:p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
            <!-- Sender Information -->
            <div class="bg-gray-50 dark:bg-gray-700/50 p-3 md:p-4 rounded-lg flex flex-col gap-1">
              <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-2">Sender</h3>
              <p class="font-medium truncate">{{ payment.delivery_request?.sender?.name || 'N/A' }}</p>
              <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ payment.delivery_request?.sender?.email || '' }}</p>
              <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ payment.delivery_request?.sender?.mobile || '' }}</p>
            </div>
            <!-- Receiver Information -->
            <div class="bg-gray-50 dark:bg-gray-700/50 p-3 md:p-4 rounded-lg flex flex-col gap-1">
              <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-2">Receiver</h3>
              <p class="font-medium truncate">{{ payment.delivery_request?.receiver?.name || 'N/A' }}</p>
              <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ payment.delivery_request?.receiver?.email || '' }}</p>
              <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ payment.delivery_request?.receiver?.mobile || '' }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment Details -->
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 mb-6">
        <div class="p-3 md:p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
          <h3 class="font-medium text-gray-900 dark:text-gray-100">
            Collection Details
          </h3>
        </div>
        <div class="p-4 md:p-6">
          <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <div class="flex justify-between mb-2">
                  <span class="text-gray-600 dark:text-gray-400">Payment Method:</span>
                  <span class="capitalize font-medium">{{ payment.method }}</span>
                </div>
                <div class="flex justify-between mb-2">
                  <span class="text-gray-600 dark:text-gray-400">Amount Collected:</span>
                  <span class="font-medium">₱{{ Number(payment.amount).toFixed(2) }}</span>
                </div>
                <div class="flex justify-between mb-2">
                  <span class="text-gray-600 dark:text-gray-400">Status:</span>
                  <span>
                    <span v-if="payment.verified_by" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">Verified</span>
                    <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">Pending Verification</span>
                  </span>
                </div>
                <div class="flex justify-between mb-2">
                  <span class="text-gray-600 dark:text-gray-400">Collected By:</span>
                  <span>{{ payment.collected_by?.name || 'N/A' }}</span>
                </div>
                <div class="flex justify-between mb-2">
                  <span class="text-gray-600 dark:text-gray-400">Collected At:</span>
                  <span>{{ payment.collected_at ? formatDateTime(payment.collected_at) : 'N/A' }}</span>
                </div>
                <div v-if="payment.verified_by" class="flex justify-between mb-2">
                  <span class="text-gray-600 dark:text-gray-400">Verified By:</span>
                  <span>{{ payment.verified_by?.name || 'N/A' }}</span>
                </div>
              </div>
              <div v-if="payment.receipt_image">
                <div>
                  <span class="block text-gray-600 dark:text-gray-400 mb-1">Receipt:</span>
                  <img 
                    :src="'/storage/' + payment.receipt_image" 
                    alt="Payment receipt"
                    class="max-w-full h-auto rounded border border-gray-200 dark:border-gray-700"
                  >
                </div>
              </div>
            </div>
            <div v-if="payment.notes">
              <span class="block text-gray-600 dark:text-gray-400 mb-1">Notes:</span>
              <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded">
                <p class="whitespace-pre-line text-gray-900 dark:text-gray-100">{{ payment.notes }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <v-dialog v-model="deleteDialog" max-width="500">
      <v-card>
        <v-card-title>Confirm Deletion</v-card-title>
        <v-card-text>
          Are you sure you want to delete this payment record? This action cannot be undone.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <SecondaryButton type="button" @click="deleteDialog = false">
            Cancel
          </SecondaryButton>
          <DangerButton type="button" @click="deletePayment">
            Delete
          </DangerButton>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
  payment: Object
});

const deleteDialog = ref(false);

const confirmDelete = () => {
  deleteDialog.value = true;
};

const deletePayment = () => {
  router.delete(route('collector.payments.destroy', props.payment.id), {
    onSuccess: () => {
      deleteDialog.value = false;
    }
  });
};

function formatDate(dateString) {
  if (!dateString) return 'N/A';
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  } catch (e) {
    return 'Invalid date';
  }
}

function formatDateTime(dateString) {
  if (!dateString) return 'N/A';
  try {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch (e) {
    return 'Invalid date';
  }
}
</script>