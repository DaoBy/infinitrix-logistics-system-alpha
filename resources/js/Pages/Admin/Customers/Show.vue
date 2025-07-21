<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
          Customer Details: {{ customer.name || customer.company_name }}
        </h2>
        <div class="flex space-x-2">
          <SecondaryButton @click="$inertia.visit(route('admin.customers.index'))">
            Back to List
          </SecondaryButton>
          <PrimaryButton @click="editCustomer">
            Edit
          </PrimaryButton>
          <DangerButton 
            v-if="customer.user?.is_active"
            @click="archiveCustomer"
          >
            Archive
          </DangerButton>
          <PrimaryButton 
            v-else
            @click="restoreCustomer"
          >
            Restore
          </PrimaryButton>
        </div>
      </div>
    </template>

    <div class="py-6 px-6 sm:px-8">
      <div class="mx-auto max-w-7xl">
        <!-- Status Messages -->
        <div v-if="status || success || error" class="mb-6">
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

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-5 bg-white border-b border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Account Information -->
              <div class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900">Account Information</h3>
                <div class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-500">Account Email</label>
                    <p class="mt-1 text-sm text-gray-900">{{ customer.user?.email || 'N/A' }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-500">Account Status</label>
                    <span :class="customer.user?.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" 
                          class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                      {{ customer.user?.is_active ? 'Active' : 'Inactive' }}
                    </span>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-500">Created At</label>
                    <p class="mt-1 text-sm text-gray-900">
                      {{ new Date(customer.created_at).toLocaleDateString() }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Customer Information -->
              <div class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900">Customer Information</h3>
                <div class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-500">Type</label>
                    <p class="mt-1 text-sm text-gray-900 capitalize">
                      {{ customer.customer_category === 'company' ? 'Company' : 'Individual' }}
                    </p>
                  </div>

                  <div v-if="customer.name">
                    <label class="block text-sm font-medium text-gray-500">
                      {{ customer.customer_category === 'company' ? 'Contact Person' : 'Full Name' }}
                    </label>
                    <p class="mt-1 text-sm text-gray-900">{{ customer.name }}</p>
                  </div>

                  <div v-if="customer.company_name">
                    <label class="block text-sm font-medium text-gray-500">Company Name</label>
                    <p class="mt-1 text-sm text-gray-900">{{ customer.company_name }}</p>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-500">Contact</label>
                    <p class="mt-1 text-sm text-gray-900">{{ customer.mobile }}</p>
                    <p v-if="customer.phone" class="mt-1 text-sm text-gray-900">{{ customer.phone }}</p>
                    <p class="mt-1 text-sm text-gray-900">{{ customer.email }}</p>
                  </div>
                </div>
              </div>

              <!-- Address Information -->
              <div class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900">Address Information</h3>
                <div>
                  <label class="block text-sm font-medium text-gray-500">Address</label>
                  <p class="mt-1 text-sm text-gray-900">
                    {{ [customer.building_number, customer.street].filter(Boolean).join(' ') }}
                  </p>
                  <p class="mt-1 text-sm text-gray-900">
                    {{ [customer.barangay, customer.city, customer.province, customer.zip_code].filter(Boolean).join(', ') }}
                  </p>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-500">Frequency</label>
                  <p class="mt-1 text-sm text-gray-900 capitalize">
                    {{ customer.frequency_type === 'regular' ? 'Regular' : 'Occasional' }}
                  </p>
                </div>

                <div v-if="customer.notes">
                  <label class="block text-sm font-medium text-gray-500">Notes</label>
                  <p class="mt-1 text-sm text-gray-900 whitespace-pre-line">{{ customer.notes }}</p>
                </div>
              </div>

              <!-- Recent Deliveries -->
              <div class="space-y-4" v-if="recent_sent_deliveries?.length || recent_received_deliveries?.length">
                <h3 class="text-lg font-medium text-gray-900">Recent Deliveries</h3>
                
                <div v-if="recent_sent_deliveries?.length">
                  <h4 class="text-md font-medium text-gray-700">Sent</h4>
                  <ul class="mt-2 space-y-2">
                    <li v-for="delivery in recent_sent_deliveries" :key="delivery.id" class="text-sm text-gray-600">
                      {{ delivery.tracking_number }} - {{ new Date(delivery.created_at).toLocaleDateString() }}
                    </li>
                  </ul>
                </div>

                <div v-if="recent_received_deliveries?.length">
                  <h4 class="text-md font-medium text-gray-700">Received</h4>
                  <ul class="mt-2 space-y-2">
                    <li v-for="delivery in recent_received_deliveries" :key="delivery.id" class="text-sm text-gray-600">
                      {{ delivery.tracking_number }} - {{ new Date(delivery.created_at).toLocaleDateString() }}
                    </li>
                  </ul>
                </div>
              </div>
            </div>

            <div class="mt-8">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Delivery Requests</h3>
              <div class="overflow-x-auto w-full">
                <PendingRequestsTable 
                  :requests="deliveryRequests"
                  @view="viewRequest"
                  @edit="editRequest"
                  @cancel="cancelRequest"
                />
              </div>
            </div>

            <div class="mt-8">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Transaction History</h3>
              <div class="overflow-x-auto w-full">
                <TransactionHistoryTable 
                  :transactions="transactions"
                  @view="viewTransaction"
                  @view-request="viewRequestFromTransaction"
                />
              </div>
            </div>

            <div class="mt-8 flex justify-end space-x-4">
              <SecondaryButton @click="$inertia.visit(route('admin.customers.index'))">
                Back to List
              </SecondaryButton>
              <PrimaryButton @click="editCustomer">
                Edit Customer
              </PrimaryButton>
            </div>
          </div>
        </div>

        <!-- Sender and Receiver Information -->
        <div class="mt-8">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Sender Info -->
            <div>
              <h3 class="font-semibold text-lg mb-2">Sender</h3>
              <div><span class="font-medium">Name:</span> {{ delivery.sender?.name || delivery.sender?.company_name || 'N/A' }}</div>
              <div><span class="font-medium">Contact:</span> {{ delivery.sender?.mobile || delivery.sender?.phone || 'N/A' }}</div>
              <div><span class="font-medium">Email:</span> {{ delivery.sender?.email || 'N/A' }}</div>
              <div>
                <span class="font-medium">Address:</span>
                <span>
                  {{
                    [
                      delivery.sender?.building_number,
                      delivery.sender?.street,
                      delivery.sender?.barangay,
                      delivery.sender?.city,
                      delivery.sender?.province,
                      delivery.sender?.zip_code
                    ].filter(Boolean).join(', ') || 'N/A'
                  }}
                </span>
              </div>
            </div>

            <!-- Receiver Info -->
            <div>
              <h3 class="font-semibold text-lg mb-2">Receiver</h3>
              <div><span class="font-medium">Name:</span> {{ delivery.receiver?.name || delivery.receiver?.company_name || 'N/A' }}</div>
              <div><span class="font-medium">Contact:</span> {{ delivery.receiver?.mobile || delivery.receiver?.phone || 'N/A' }}</div>
              <div><span class="font-medium">Email:</span> {{ delivery.receiver?.email || 'N/A' }}</div>
              <div>
                <span class="font-medium">Address:</span>
                <span>
                  {{
                    [
                      delivery.receiver?.building_number,
                      delivery.receiver?.street,
                      delivery.receiver?.barangay,
                      delivery.receiver?.city,
                      delivery.receiver?.province,
                      delivery.receiver?.zip_code
                    ].filter(Boolean).join(', ') || 'N/A'
                  }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import PendingRequestsTable from '@/Components/PendingRequestsTable.vue';
import TransactionHistoryTable from '@/Components/TransactionHistoryTable.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  customer: Object,
  recent_sent_deliveries: Array,
  recent_received_deliveries: Array,
  deliveryRequests: Object,
  transactions: Object,
  status: String,
  success: String,
  error: String,
});

const editCustomer = () => {
  router.get(route('admin.customers.edit', props.customer.id));
};

const archiveCustomer = () => {
  if (confirm('Are you sure you want to archive this customer?')) {
    router.put(route('admin.customers.archive', props.customer.id), {
      preserveScroll: true,
      onSuccess: () => {
        router.reload();
      }
    });
  }
};

const restoreCustomer = () => {
  if (confirm('Are you sure you want to restore this customer?')) {
    router.put(route('admin.customers.restore', props.customer.id), {
      preserveScroll: true,
      onSuccess: () => {
        router.reload();
      }
    });
  }
};

const viewRequest = (id) => {
  router.get(route('admin.delivery-requests.show', id));
};

const editRequest = (id) => {
  router.get(route('admin.delivery-requests.edit', id));
};

const cancelRequest = (id) => {
  if (confirm('Are you sure you want to cancel this request?')) {
    router.delete(route('admin.delivery-requests.destroy', id), {
      preserveScroll: true,
      onSuccess: () => router.reload(),
    });
  }
};

const viewTransaction = (id) => {
  router.get(route('admin.transactions.show', id));
};

const viewRequestFromTransaction = (id) => {
  router.get(route('admin.delivery-requests.show', id));
};
</script>

<style scoped>
/* Make sure parent containers don't force a min-width */
.bg-white {
  min-width: 0 !important;
}
.p-6 {
  min-width: 0 !important;
}
</style>