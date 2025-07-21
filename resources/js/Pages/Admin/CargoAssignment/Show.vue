<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-6">
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            Delivery Order: DO-{{ order.id?.toString().padStart(6, '0') || 'N/A' }}
          </h2>
          <div class="flex items-center mt-1">
            <StatusBadge 
              :status="order.status || 'pending'" 
              class="mr-2" 
            />
            <span class="text-sm text-gray-500 dark:text-gray-400">
              Last updated: {{ formatDateTime(order.updated_at) }}
            </span>
          </div>
        </div>
        <div class="flex space-x-2">
          <SecondaryButton 
            @click="generateManifest" 
            class="inline-flex items-center"
            :disabled="!order.truck"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M5 4a1 1 0 011-1h8a1 1 0 011 1v1h1a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h1V4zm2 2v1a1 1 0 001 1h4a1 1 0 001-1V6H7zm0 4a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H8a1 1 0 01-1-1v-4z" clip-rule="evenodd" />
            </svg>
            Manifest
          </SecondaryButton>
          <SecondaryButton @click="generateWaybill" class="inline-flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V19a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
            </svg>
            Waybill
          </SecondaryButton>
          <PrimaryButton @click="goBack" class="inline-flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Back
          </PrimaryButton>
        </div>
      </div>
    </template>

    <!-- Status Messages -->
    <FlashMessages :flash="flash" class="px-6 py-2" />

    <div v-if="loading" class="flex justify-center items-center h-64">
      <LoadingSpinner size="lg" />
    </div>

    <div v-else class="px-6 py-4">
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 max-w-7xl mx-auto">
        <!-- Action Bar -->
        <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
          <div class="flex items-center space-x-4">
            <h3 class="font-medium text-gray-900 dark:text-gray-100">Order Details</h3>
            <span class="px-3 py-1 text-xs font-medium rounded-full"
                  :class="statusColor(order.status || 'pending')">
              {{ (order.status || 'pending')?.replace('_', ' ') }}
            </span>
          </div>
          <div class="flex space-x-2" v-if="canUpdateStatus">
            <template v-if="order.status === 'assigned'">
              <PrimaryButton @click="dispatchOrder" class="inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1 1 0 11-3 0 1 1 0 013 0z" />
                  <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-1a1 1 0 011-1h2a1 1 0 011 1v1a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H19a1 1 0 001-1V5a1 1 0 00-1-1H3z" />
                </svg>
                Dispatch
              </PrimaryButton>
            </template>
            <template v-if="order.status === 'dispatched'">
              <PrimaryButton @click="openArrivalModal" class="inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd" />
                </svg>
                Record Arrival
              </PrimaryButton>
            </template>
            <template v-if="order.status === 'in_transit'">
              <PrimaryButton @click="markDelivered" class="inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Mark Delivered
              </PrimaryButton>
            </template>
            <template v-if="order.status === 'delivered'">
              <PrimaryButton @click="completeDelivery" class="inline-flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Complete
              </PrimaryButton>
            </template>
            <DangerButton 
              v-if="order.status && ['completed', 'cancelled'].includes(order.status) === false"
              @click="confirmCancel" 
              class="inline-flex items-center"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
              Cancel
            </DangerButton>
          </div>
        </div>

        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Delivery Information -->
            <div class="space-y-6">
              <Section title="Delivery Information">
                <DetailItem 
                  label="Request Number" 
                  :value="deliveryRequest.id ? `DR-${deliveryRequest.id.toString().padStart(6, '0')}` : 'N/A'" 
                />
                <DetailItem label="Route">
                  <template #value>
                    <div class="flex items-center">
                      {{ deliveryRequest.pick_up_region?.name || 'N/A' }}
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mx-2 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                      </svg>
                      {{ deliveryRequest.drop_off_region?.name || 'N/A' }}
                    </div>
                  </template>
                </DetailItem>
                <DetailItem 
                  label="Sender" 
                  :value="deliveryRequest.sender?.name || deliveryRequest.sender?.company_name || 'N/A'" 
                />
                <DetailItem 
                  label="Receiver" 
                  :value="deliveryRequest.receiver?.name || deliveryRequest.receiver?.company_name || 'N/A'" 
                />
                <DetailItem 
                  label="Total Packages" 
                  :value="`${packages.length} packages`" 
                />
                <DetailItem 
                  label="Total Volume" 
                  :value="`${calculateTotalVolume(packages)} m³`" 
                />
              </Section>

              <!-- Timeline -->
              <Section title="Timeline">
                <DetailItem 
                  label="Estimated Departure" 
                  :value="order.estimated_departure ? formatDateTime(order.estimated_departure) : 'Not scheduled'" 
                />
                <DetailItem 
                  label="Estimated Arrival" 
                  :value="order.estimated_arrival ? formatDateTime(order.estimated_arrival) : 'Not scheduled'" 
                />
                <DetailItem 
                  v-if="order.actual_departure" 
                  label="Actual Departure" 
                  :value="formatDateTime(order.actual_departure)" 
                />
                <DetailItem 
                  v-if="order.actual_arrival" 
                  label="Actual Arrival" 
                  :value="formatDateTime(order.actual_arrival)" 
                />
              </Section>

              <!-- Region Logs -->
              <Section title="Route History" v-if="order.region_logs?.length">
                <div class="space-y-4">
                  <div v-for="log in order.region_logs" :key="log.id" class="flex items-start">
                    <div class="flex-shrink-0 mt-1">
                      <div class="h-2 w-2 rounded-full" :class="log.type === 'arrival' ? 'bg-green-500' : 'bg-blue-500'"></div>
                    </div>
                    <div class="ml-3">
                      <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ log.type === 'arrival' ? 'Arrived at' : 'Departed from' }} {{ log.region?.name || 'Unknown' }}
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ formatDateTime(log.logged_at) }}
                      </p>
                    </div>
                  </div>
                </div>
              </Section>
            </div>

            <!-- Assignment Information -->
            <div class="space-y-6">
              <Section title="Assignment Details">
                <DetailItem label="Assigned Driver">
                  <template #value>
                    <div class="flex items-center" v-if="order.driver">
                      <div class="flex-shrink-0 h-8 w-8 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center mr-2">
                        <span class="text-gray-600 dark:text-gray-300 text-xs font-medium">
                          {{ getInitials(order.driver?.name) }}
                        </span>
                      </div>
                      <div>
                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ order.driver?.name || 'N/A' }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ order.driver?.employee_profile?.employee_id || 'N/A' }}</p>
                      </div>
                    </div>
                    <span v-else class="text-sm text-gray-500 dark:text-gray-400">Not assigned</span>
                  </template>
                </DetailItem>
                <DetailItem label="Assigned Truck">
                  <template #value>
                    <div v-if="order.truck">
                      <p class="text-sm text-gray-900 dark:text-gray-100">
                        {{ order.truck?.make || '' }} {{ order.truck?.model || '' }}
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ order.truck?.license_plate || 'N/A' }} | 
                        Capacity: {{ order.truck?.available_capacity || 0 }}/{{ order.truck?.capacity || 0 }} m³
                      </p>
                    </div>
                    <span v-else class="text-sm text-gray-500 dark:text-gray-400">Not assigned</span>
                  </template>
                </DetailItem>
                <DetailItem label="Assigned By">
                  <template #value>
                    <span v-if="order.assigned_by" class="text-gray-900 dark:text-gray-100">
                      {{ order.assigned_by.name }}
                    </span>
                    <span v-else class="text-sm text-gray-500 dark:text-gray-400">Not assigned</span>
                  </template>
                </DetailItem>
                <DetailItem
                  v-if="order.dispatched_by"
                  label="Dispatched By"
                  :value="`${order.dispatched_by?.name || 'N/A'} on ${formatDateTime(order.dispatched_at)}`" 
                />
              </Section>

              <!-- Payment Information -->
              <Section title="Payment Information" v-if="deliveryRequest">
                <DetailItem 
                  label="Payment Method" 
                  :value="deliveryRequest.payment_method || 'N/A'" 
                />
                <DetailItem 
                  label="Payment Status" 
                  :value="deliveryRequest.payment_status || 'N/A'" 
                />
                <DetailItem 
                  label="Total Amount" 
                  :value="formatCurrency(deliveryRequest.total_price || 0)" 
                />
              </Section>

              <!-- Notes -->
              <Section title="Notes" v-if="order.notes">
                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                  <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ order.notes }}</p>
                </div>
              </Section>
            </div>
          </div>

          <!-- Packages Section -->
          <Section title="Packages" class="mt-8">
            <div class="overflow-x-auto">
              <DataTable
                :columns="packageHeaders"
                :data="packages"
              >
                <template #status="{ row }">
                  <StatusBadge :status="row.status || 'pending'" />
                </template>
                <template #dimensions="{ row }">
                  {{ row.length || 'N/A' }} × {{ row.width || 'N/A' }} × {{ row.height || 'N/A' }} cm
                </template>
              </DataTable>
            </div>
          </Section>

          <!-- Package Transfers -->
          <Section title="Package Transfers" class="mt-8" v-if="hasTransfers">
            <div class="space-y-4">
              <div v-for="pkg in packages" :key="pkg.id">
                <template v-if="pkg.transfers?.length">
                  <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                    {{ pkg.item_name || 'Package' }} ({{ pkg.item_code || 'N/A' }})
                  </h4>
                  <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                    <div class="space-y-3">
                      <div v-for="transfer in pkg.transfers" :key="transfer.id" class="flex items-start">
                        <div class="flex-shrink-0 mt-1">
                          <div class="h-2 w-2 rounded-full" :class="transfer.status === 'completed' ? 'bg-green-500' : 'bg-blue-500'"></div>
                        </div>
                        <div class="ml-3">
                          <p class="text-sm text-gray-900 dark:text-gray-100">
                            {{ transfer.status === 'completed' ? 'Transferred to' : 'Scheduled for transfer to' }} 
                            {{ transfer.to_region?.name || 'Unknown' }}
                          </p>
                          <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ formatDateTime(transfer.created_at) }} | 
                            Initiated by: {{ transfer.created_by?.name || 'System' }}
                          </p>
                          <p v-if="transfer.notes" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Notes: {{ transfer.notes }}
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                </template>
              </div>
            </div>
          </Section>
        </div>
      </div>
    </div>

    <!-- Arrival Modal -->
    <Modal :show="showArrivalModal" @close="showArrivalModal = false" max-width="md">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Record Arrival</h2>
        
        <div class="mt-4 space-y-4">
          <SelectInput
            label="Region *"
            v-model="arrivalForm.region_id"
            :options="regionOptions"
            :error="arrivalForm.errors.region_id"
          />

          <TextArea
            label="Notes"
            v-model="arrivalForm.notes"
            :error="arrivalForm.errors.notes"
          />
        </div>

        <div class="mt-6 flex justify-end space-x-4">
          <SecondaryButton @click="showArrivalModal = false">
            Cancel
          </SecondaryButton>
          <PrimaryButton @click="recordArrival" :disabled="arrivalForm.processing" class="inline-flex items-center">
            <LoadingSpinner v-if="arrivalForm.processing" class="mr-2" />
            {{ arrivalForm.processing ? 'Processing...' : 'Record Arrival' }}
          </PrimaryButton>
        </div>
      </div>
    </Modal>

    <!-- Cancel Confirmation Modal -->
    <ConfirmationModal 
      :show="showCancelModal" 
      @close="showCancelModal = false"
      @confirmed="cancelOrder"
      title="Cancel Delivery Order"
      confirm-text="Yes, Cancel"
      confirm-variant="danger"
    >
      <p>Are you sure you want to cancel this delivery order?</p>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">This will revert all packages to their previous status.</p>
    </ConfirmationModal>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DataTable from '@/Components/DataTable.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import Section from '@/Components/Section.vue';
import DetailItem from '@/Components/DetailItem.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextArea from '@/Components/TextArea.vue';

import { useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  order: {
    type: Object,
    default: () => ({})
  },
  regions: {
    type: Array,
    default: () => []
  },
  flash: {
    type: Object,
    default: () => ({})
  }
});

const loading = ref(false);
const showArrivalModal = ref(false);
const showCancelModal = ref(false);

const order = computed(() => props.order || {});
const deliveryRequest = computed(() => order.value.delivery_request || {});
const packages = computed(() => deliveryRequest.value.packages || []);

const arrivalForm = useForm({
  region_id: null,
  notes: null,
});

const regionOptions = computed(() => 
  props.regions.map(region => ({
    value: region.id,
    label: region.name
  }))
);

const packageHeaders = [
  { field: 'item_code', header: 'Item Code', sortable: true },
  { field: 'item_name', header: 'Description', sortable: true },
  { 
    field: 'dimensions', 
    header: 'Dimensions',
    sortable: false,
    formatter: (row) => `${row.length || 'N/A'} × ${row.width || 'N/A'} × ${row.height || 'N/A'} cm`
  },
  { field: 'weight', header: 'Weight (kg)', sortable: true },
  { 
    field: 'status', 
    header: 'Status', 
    sortable: true,
    formatter: (status) => ({ text: status, class: '' })
  }
];

const hasTransfers = computed(() => {
  return packages.value.some(pkg => pkg.transfers && pkg.transfers.length > 0);
});

const canUpdateStatus = computed(() => {
  return order.value?.status && ['assigned', 'dispatched', 'in_transit', 'delivered'].includes(order.value.status);
});

const statusColor = (status) => {
  const colors = {
    pending: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
    assigned: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
    dispatched: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
    in_transit: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
    delivered: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
    completed: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
  };
  return colors[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300';
};

const formatDateTime = (dateString) => {
  if (!dateString) return 'Not specified';
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
};

function formatCurrency(amount) {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(amount);
}

function calculateTotalVolume(packages) {
  if (!Array.isArray(packages)) return 0;
  return packages.reduce((total, pkg) => {
    return total + (parseFloat(pkg.volume) || 0);
  }, 0).toFixed(2);
}

function getInitials(name) {
  if (!name) return 'N/A';
  return name.split(' ').map(n => n[0]).join('');
}

function openArrivalModal() {
  arrivalForm.reset();
  arrivalForm.region_id = '';
  arrivalForm.notes = '';
  showArrivalModal.value = true;
}

function recordArrival() {
  arrivalForm.post(route('cargo-assignments.record-arrival', order.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showArrivalModal.value = false;
    }
  });
}

function dispatchOrder() {
  router.post(route('cargo-assignments.dispatch', order.value.id), {
    preserveScroll: true
  });
}

function markDelivered() {
  if (confirm('Mark this order as delivered? This will update all package statuses.')) {
    router.post(route('cargo-assignments.mark-delivered', order.value.id), {
      preserveScroll: true
    });
  }
}

function completeDelivery() {
    if (confirm('Complete this order? This will finalize the delivery.')) {
        loading.value = true;
        
        // Use Inertia's router with proper error handling
        router.post(route('cargo-assignments.complete', order.value.id), {}, {
            preserveScroll: false,
            preserveState: false,
            onSuccess: () => {
                // Use Inertia's visit method for full reload
                router.visit(route('cargo-assignments.show', order.value.id), {
                    only: [], // Force full reload
                    onFinish: () => loading.value = false
                });
            },
            onError: (errors) => {
                loading.value = false;
                console.error('Completion failed:', errors);
            }
        });
    }
}

function confirmCancel() {
  showCancelModal.value = true;
}

function cancelOrder() {
  router.post(route('cargo-assignments.cancel', order.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showCancelModal.value = false;
    }
  });
}

function generateManifest() {
  if (!order.value?.truck?.id) {
    console.warn('No truck assigned to this order yet!');
    return;
  }

  router.visit(route('cargo-assignments.manifest', order.value.truck.id));
}

function generateWaybill() {
  if (!deliveryRequest.value?.id) {
    console.warn('No delivery request associated with this order!');
    return;
  }

  router.post(route('waybills.generate', deliveryRequest.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      // After generating, redirect to the waybill show page
      router.visit(route('waybills.show', deliveryRequest.value.waybill?.id));
    }
  });
}
function goBack() {
  router.visit(route('cargo-assignments.index'));
}
</script>