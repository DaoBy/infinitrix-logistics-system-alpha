<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center px-2 md:px-8 py-4 max-w-7xl mx-auto w-full">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200 mb-2 md:mb-0">
          Pending Collections
        </h2>
        <div class="flex flex-col md:flex-row gap-2 w-full md:w-auto">
          <SearchInput 
            v-model="search"
            placeholder="Search by reference, sender, or receiver..."
            class="w-full md:w-64"
          />
          <SelectInput
            v-model="status"
            :options=" [
              { value: '', label: 'All Statuses' },
              { value: 'pending', label: 'Pending' },
              { value: 'pending_payment', label: 'Pending Payment' },
              { value: 'collected', label: 'Collected' },
              { value: 'uncollectible', label: 'Uncollectible' }
            ]"
            class="w-full md:w-44"
          />
        </div>
      </div>
    </template>

    <div class="px-1 md:px-8 py-4 flex justify-center">
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 w-full max-w-7xl">
        <div class="p-2 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
          <div>
            <h3 class="text-base font-medium leading-6 text-gray-900 dark:text-gray-100">
              Pending Collections
            </h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              Deliveries that are awaiting payment collection from customers.
            </p>
          </div>
          <div class="text-xs text-gray-500 dark:text-gray-400">
            Showing {{ deliveries?.data?.length ?? 0 }} of {{ deliveries?.total ?? 0 }} entries
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reference</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sender</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Receiver</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr
                v-for="item in deliveries?.data || []"
                :key="item.id"
                :class="{
                  'bg-red-100 dark:bg-red-900/30': item.delivery_request?.payment_due_date && new Date(item.delivery_request.payment_due_date) < new Date() && item.delivery_request.payment_status !== 'collected'
                }"
              >
                <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                  <div class="flex flex-col">
                    <div class="flex items-center gap-2">
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-800 dark:text-indigo-100">
                        Reference #
                      </span>
                      <span class="font-bold text-indigo-900 dark:text-indigo-100 tracking-wide">
                        {{ item.delivery_request?.reference_number || `DR-${String(item.delivery_request_id).padStart(6, '0')}` }}
                      </span>
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-300">
                      <span>
                        Delivery ID: DO-{{ String(item.delivery_request_id).padStart(6, '0') }}
                      </span>
                      <span v-if="item.created_at">
                        | Created: {{ formatDate(item.created_at) }}
                      </span>
                    </div>
                  </div>
                </td>
                <td class="px-3 py-2 whitespace-nowrap text-gray-900 dark:text-gray-100">
                  {{ item.delivery_request?.sender?.name || 'N/A' }}
                </td>
                <td class="px-3 py-2 whitespace-nowrap text-gray-900 dark:text-gray-100">
                  {{ item.delivery_request?.receiver?.name || 'N/A' }}
                </td>
                <td class="px-3 py-2 whitespace-nowrap">
                  ₱{{ item.delivery_request?.total_price !== undefined && item.delivery_request?.total_price !== null
                    ? (parseFloat(item.delivery_request.total_price) || 0).toFixed(2)
                    : '0.00'
                  }}
                </td>
                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                  {{ item.created_at ? formatDate(item.created_at) : '' }}
                </td>
                <td class="px-3 py-2 whitespace-nowrap flex gap-2">
                  <PrimaryButton
                    type="button"
                    @click="goToCollectPayment(item.delivery_request_id)"
                    class="!px-2 !py-1.5 !text-xs flex items-center justify-center"
                    :title="'Collect Payment'"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </PrimaryButton>
                  <SecondaryButton
                    type="button"
                    @click="openInfoDialog(item)"
                    class="!px-2 !py-1.5 !text-xs flex items-center justify-center"
                    :title="'View Info'"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </SecondaryButton>
                  <DangerButton
                    type="button"
                    @click="openUncollectibleModal(item)"
                    class="!px-2 !py-1.5 !text-xs flex items-center justify-center"
                    :title="'Mark Uncollectible'"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                  </DangerButton>
                </td>
              </tr>
              <tr v-if="!deliveries?.data || deliveries.data.length === 0">
                <td colspan="6" class="px-3 py-2 text-center text-sm text-gray-500 dark:text-gray-400">
                  No pending collections found
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- aaaa built in pagination -->
        <div v-if="deliveries?.last_page > 1" class="flex justify-center items-center gap-2 p-2 border-t border-gray-200 dark:border-gray-700">
          <button
            class="px-3 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200"
            :disabled="deliveries.current_page === 1"
            @click="handlePageChange(deliveries.current_page - 1)"
          >
            Prev
          </button>
          <button
            v-for="page in deliveries.last_page"
            :key="page"
            class="px-3 py-1 rounded"
            :class="{
              'bg-indigo-600 text-white': deliveries.current_page === page,
              'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200': deliveries.current_page !== page
            }"
            @click="handlePageChange(page)"
          >
            {{ page }}
          </button>
          <button
            class="px-3 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200"
            :disabled="deliveries.current_page === deliveries.last_page"
            @click="handlePageChange(deliveries.current_page + 1)"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Mark as Uncollectible Modal -->
    <v-dialog v-model="uncollectibleDialog" max-width="500">
      <v-card class="border border-gray-200 dark:border-gray-700 rounded-lg">
        <v-card-title class="pb-0 border-b border-gray-100 dark:border-gray-700 relative">
          <div>
            <span class="text-lg font-semibold text-red-700 dark:text-red-300">Mark as Uncollectible</span>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
              Reference:
              <span class="font-bold text-indigo-900 dark:text-indigo-100">
                {{ selectedDelivery?.delivery_request?.reference_number || `DR-${String(selectedDelivery?.delivery_request_id).padStart(6, '0')}` }}
              </span>
            </div>
          </div>
          <button
            type="button"
            @click="closeUncollectibleModal"
            class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 focus:outline-none"
            aria-label="Close"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </v-card-title>
        <v-card-text class="p-6">
          <div class="space-y-4">
            <div class="bg-red-50 dark:bg-red-900/30 rounded-lg p-4 border border-red-200 dark:border-red-700">
              <div class="text-sm text-gray-900 dark:text-gray-100 font-medium mb-2">
                Please select the reason(s) why this delivery cannot be collected.
              </div>
              <div class="space-y-2 mb-4">
                <div class="flex items-center space-x-2">
                  <Checkbox value="Unavailable" v-model="multipleCheckboxes" class="shadow-black/50"/>
                  <InputLabel>Unavailable</InputLabel>
                </div>
                <div class="flex items-center space-x-2">
                  <Checkbox value="Missing Payment Proof" v-model="multipleCheckboxes" class="shadow-black/50"/>
                  <InputLabel>Missing Payment Proof</InputLabel>
                </div>
                <div class="flex items-center space-x-2">
                  <Checkbox value="Wrong Transaction Information" v-model="multipleCheckboxes" class="shadow-black/50"/>
                  <InputLabel>Wrong Transaction Information</InputLabel>
                </div>
              </div>
              <v-textarea
                v-model="nonPaymentReason"
                label="Other reason (optional)"
                rows="2"
                :error-messages="formErrors.non_payment_reason"
                class="mt-2"
              ></v-textarea>
            </div>
          </div>
        </v-card-text>
        <v-card-actions class="px-6 pb-4">
          <v-spacer></v-spacer>
          <SecondaryButton type="button" @click="closeUncollectibleModal">
            Cancel
          </SecondaryButton>
          <DangerButton
            type="button"
            :disabled="submitting"
            :class="{ 'opacity-50': submitting }"
            @click="submitUncollectible"
          >
            Mark as Uncollectible
          </DangerButton>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delivery Info Modal -->
    <v-dialog v-model="infoDialog" max-width="600">
      <template v-if="infoDelivery">
        <v-card class="border border-gray-200 dark:border-gray-700 rounded-lg">
          <v-card-title class="pb-0 border-b border-gray-100 dark:border-gray-700 relative">
            <div>
              <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">Delivery Request Details</span>
              <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Reference: 
                <span class="font-bold text-indigo-900 dark:text-indigo-100">
                  {{ infoDelivery.delivery_request?.reference_number || `DR-${String(infoDelivery.delivery_request_id).padStart(6, '0')}` }}
                </span>
              </div>
            </div>
            <button
              type="button"
              @click="closeInfoDialog"
              class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 focus:outline-none"
              aria-label="Close"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </v-card-title>
          <v-card-text class="p-6">
            <div class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Sender -->
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                  <h4 class="font-semibold text-indigo-700 dark:text-indigo-200 mb-2 flex items-center gap-1">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Sender
                  </h4>
                  <div class="text-sm text-gray-900 dark:text-gray-100 font-medium">{{ infoDelivery.delivery_request?.sender?.name || 'N/A' }}</div>
                  <div class="text-xs text-gray-600 dark:text-gray-400">{{ infoDelivery.delivery_request?.sender?.mobile || '—' }}</div>
                  <div class="text-xs text-gray-600 dark:text-gray-400 break-words">{{ infoDelivery.delivery_request?.sender?.address || '—' }}</div>
                </div>
                <!-- Receiver -->
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                  <h4 class="font-semibold text-indigo-700 dark:text-indigo-200 mb-2 flex items-center gap-1">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Receiver
                  </h4>
                  <div class="text-sm text-gray-900 dark:text-gray-100 font-medium">{{ infoDelivery.delivery_request?.receiver?.name || 'N/A' }}</div>
                  <div class="text-xs text-gray-600 dark:text-gray-400">{{ infoDelivery.delivery_request?.receiver?.mobile || '—' }}</div>
                  <div class="text-xs text-gray-600 dark:text-gray-400 break-words">{{ infoDelivery.delivery_request?.receiver?.address || '—' }}</div>
                </div>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                    <span class="font-semibold">Region:</span>
                    {{ infoDelivery.delivery_request?.drop_off_region?.name || infoDelivery.delivery_request?.dropOffRegion?.name || 'N/A' }}
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                    <span class="font-semibold">Notes:</span>
                    {{ infoDelivery.delivery_request?.notes || 'None' }}
                  </div>
                </div>
                <div>
                  <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                    <span class="font-semibold">Created:</span>
                    {{ infoDelivery.delivery_request?.created_at ? formatDate(infoDelivery.delivery_request.created_at) : 'N/A' }}
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                    <span class="font-semibold">Amount Due:</span>
                    ₱{{ infoDelivery.delivery_request?.total_price !== undefined && infoDelivery.delivery_request?.total_price !== null
                      ? (parseFloat(infoDelivery.delivery_request.total_price) || 0).toFixed(2)
                      : '0.00'
                    }}
                  </div>
                </div>
              </div>
            </div>
          </v-card-text>
          <v-card-actions class="px-6 pb-4">
            <v-spacer></v-spacer>
         
          </v-card-actions>
        </v-card>
      </template>
    </v-dialog>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import SearchInput from '@/Components/SearchInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';

const props = defineProps({
  deliveries: Object,
  filters: Object
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

const handlePageChange = (page) => {
  router.get(route('collector.payments.pending'), {
    page: page,
    search: search.value,
    status: status.value
  }, {
    preserveState: true,
    preserveScroll: true
  });
};

watch([search, status], debounce(([searchValue, statusValue]) => {
  router.get(route('collector.payments.pending'), {
    search: searchValue,
    status: statusValue
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
}, 300));

// --- Mark as Uncollectible Modal Logic ---
const uncollectibleDialog = ref(false);
const selectedDelivery = ref(null);
const nonPaymentReason = ref('');
const formErrors = ref({});
const submitting = ref(false);
const multipleCheckboxes = ref([]);

function openUncollectibleModal(item) {
  selectedDelivery.value = item;
  nonPaymentReason.value = '';
  formErrors.value = {};
  uncollectibleDialog.value = true;
}

function closeUncollectibleModal() {
  uncollectibleDialog.value = false;
  selectedDelivery.value = null;
  nonPaymentReason.value = '';
  formErrors.value = {};
  multipleCheckboxes.value = [];
}

function submitUncollectible() {
  if (!selectedDelivery.value) return;
  submitting.value = true;
  formErrors.value = {};
  // Combine selected reasons and textarea
  const reasons = [...multipleCheckboxes.value];
  if (nonPaymentReason.value.trim()) {
    reasons.push(nonPaymentReason.value.trim());
  }
  router.post(
    route('collector.payments.mark-uncollectible', selectedDelivery.value.delivery_request_id),
    { non_payment_reason: reasons.join('; ') },
    {
      preserveScroll: true,
      onSuccess: () => {
        submitting.value = false;
        closeUncollectibleModal();
        multipleCheckboxes.value = [];
      },
      onError: (errors) => {
        formErrors.value = errors;
        submitting.value = false;
      }
    }
  );
}

function goToCollectPayment(deliveryRequestId) {
  router.visit(route('collector.payments.create', deliveryRequestId));
}

function formatDate(dateString) {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
}

// --- Delivery Info Modal Logic ---
const infoDialog = ref(false);
const infoDelivery = ref(null);

function openInfoDialog(item) {
  infoDelivery.value = item;
  infoDialog.value = true;
}

function closeInfoDialog() {
  infoDialog.value = false;
  infoDelivery.value = null;
}

// Add helper methods
const isPostpaidUnpaid = (delivery) => {
  return !delivery.delivery_request.payment_method?.includes(['cash', 'gcash', 'bank']) && 
         ['awaiting_payment', 'unpaid', 'pending'].includes(delivery.delivery_request.payment_status);
};

const canCollectPayment = (delivery) => {
  // Can't collect if already paid online
  if (['pending_verification', 'paid'].includes(delivery.delivery_request.payment_status)) {
    return false;
  }
  // Only postpaid deliveries that are completed/delivered
  return !delivery.delivery_request.payment_method?.includes(['cash', 'gcash', 'bank']) && 
         ['completed', 'delivered'].includes(delivery.delivery_request.status);
};

const canMarkUncollectible = (delivery) => {
  // Similar logic to canCollectPayment but for marking uncollectible
  return canCollectPayment(delivery);
};
</script>