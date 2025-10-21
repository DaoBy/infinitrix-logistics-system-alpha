<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">Customer Details</h2>
          <p class="mt-1 text-sm text-gray-500">
            View customer information and delivery history
          </p>
        </div>

        <!-- Right: Buttons -->
        <div class="flex gap-2">
          <SecondaryButton @click="$inertia.visit(route('admin.customers.index'))">
            Back to List
          </SecondaryButton>
          <PrimaryButton @click="editCustomer">
            Edit Profile
          </PrimaryButton>
        </div>
      </div>
    </template>

    <!-- ZOOM CONTENT WRAPPER -->
    <div class="zoom-content">
      <!-- MAIN CONTENT CONTAINER WITH PROPER PADDING -->
      <div class="px-6 py-4">
        <div v-if="status || success || error" class="mb-4">
          <div v-if="status" class="p-3 bg-blue-100 text-blue-800 rounded">{{ status }}</div>
          <div v-if="success" class="p-3 bg-green-100 text-green-800 rounded">{{ success }}</div>
          <div v-if="error" class="p-3 bg-red-100 text-red-800 rounded">{{ error }}</div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg max-w-6xl mx-auto">
          <div class="p-6 bg-white border-b border-gray-200">
            <!-- Main Information Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
              <!-- Account Information Card -->
              <div class="bg-white border border-gray-200 rounded-lg p-4">
                <h3 class="text-lg font-medium text-gray-900 mb-3">Account Information</h3>
                <div class="space-y-3">
                  <div class="flex justify-between items-center py-1">
                    <span class="text-sm font-medium text-gray-500 min-w-[120px]">Account Email</span>
                    <span class="text-sm text-gray-900 text-right">{{ customer.user?.email || 'N/A' }}</span>
                  </div>
                  <div class="flex justify-between items-center py-1">
                    <span class="text-sm font-medium text-gray-500 min-w-[120px]">Account Status</span>
                    <span :class="customer.user?.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" 
                          class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                      {{ customer.user?.is_active ? 'Active' : 'Inactive' }}
                    </span>
                  </div>
                  <div class="flex justify-between items-center py-1">
                    <span class="text-sm font-medium text-gray-500 min-w-[120px]">Created At</span>
                    <span class="text-sm text-gray-900 text-right">
                      {{ new Date(customer.created_at).toLocaleDateString() }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Customer Information Card -->
              <div class="bg-white border border-gray-200 rounded-lg p-4">
                <h3 class="text-lg font-medium text-gray-900 mb-3">Customer Information</h3>
                <div class="space-y-3">
                  <div class="flex justify-between items-center py-1">
                    <span class="text-sm font-medium text-gray-500 min-w-[120px]">Type</span>
                    <span class="text-sm text-gray-900 text-right capitalize">
                      {{ customer.customer_category === 'company' ? 'Company' : 'Individual' }}
                    </span>
                  </div>
                  <div v-if="customer.name" class="flex justify-between items-center py-1">
                    <span class="text-sm font-medium text-gray-500 min-w-[120px]">
                      {{ customer.customer_category === 'company' ? 'Contact Person' : 'Full Name' }}
                    </span>
                    <span class="text-sm text-gray-900 text-right">{{ customer.name }}</span>
                  </div>
                  <div v-if="customer.company_name" class="flex justify-between items-center py-1">
                    <span class="text-sm font-medium text-gray-500 min-w-[120px]">Company Name</span>
                    <span class="text-sm text-gray-900 text-right">{{ customer.company_name }}</span>
                  </div>
                  <div class="flex justify-between items-center py-1">
                    <span class="text-sm font-medium text-gray-500 min-w-[120px]">Frequency</span>
                    <span class="text-sm text-gray-900 text-right capitalize">
                      {{ customer.frequency_type === 'regular' ? 'Regular' : 'Occasional' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Contact & Address Information -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
              <!-- Contact Information Card -->
              <div class="bg-white border border-gray-200 rounded-lg p-4">
                <h3 class="text-lg font-medium text-gray-900 mb-3">Contact Information</h3>
                <div class="space-y-3">
                  <div class="flex justify-between items-center py-1">
                    <span class="text-sm font-medium text-gray-500 min-w-[120px]">Mobile</span>
                    <span class="text-sm text-gray-900 text-right">{{ customer.mobile || 'N/A' }}</span>
                  </div>
                  <div class="flex justify-between items-center py-1">
                    <span class="text-sm font-medium text-gray-500 min-w-[120px]">Phone</span>
                    <span class="text-sm text-gray-900 text-right">{{ customer.phone || 'N/A' }}</span>
                  </div>
                  <div class="flex justify-between items-center py-1">
                    <span class="text-sm font-medium text-gray-500 min-w-[120px]">Email</span>
                    <span class="text-sm text-gray-900 text-right">{{ customer.email || 'N/A' }}</span>
                  </div>
                </div>
              </div>

              <!-- Address Information Card -->
              <div class="bg-white border border-gray-200 rounded-lg p-4">
                <h3 class="text-lg font-medium text-gray-900 mb-3">Address Information</h3>
                <div class="space-y-3">
                  <div class="flex justify-between items-start py-1">
                    <span class="text-sm font-medium text-gray-500 min-w-[120px]">Building/Street</span>
                    <span class="text-sm text-gray-900 text-right max-w-[200px]">
                      {{ [customer.building_number, customer.street].filter(Boolean).join(' ') || 'N/A' }}
                    </span>
                  </div>
                  <div class="flex justify-between items-start py-1">
                    <span class="text-sm font-medium text-gray-500 min-w-[120px]">Barangay/City</span>
                    <span class="text-sm text-gray-900 text-right max-w-[200px]">
                      {{ [customer.barangay, customer.city].filter(Boolean).join(', ') || 'N/A' }}
                    </span>
                  </div>
                  <div class="flex justify-between items-start py-1">
                    <span class="text-sm font-medium text-gray-500 min-w-[120px]">Province/ZIP</span>
                    <span class="text-sm text-gray-900 text-right max-w-[200px]">
                      {{ [customer.province, customer.zip_code].filter(Boolean).join(' ') || 'N/A' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Notes Card -->
            <div v-if="customer.notes" class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
              <h3 class="text-lg font-medium text-gray-900 mb-3">Notes</h3>
              <p class="text-sm text-gray-700 whitespace-pre-line">
                {{ customer.notes }}
              </p>
            </div>

            <!-- Delivery Requests Section -->
            <div class="mt-6">
              <h3 class="text-lg font-medium text-gray-900 mb-3">Delivery Requests</h3>
              <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                      <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/5">Reference #</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-2/5">Receiver</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/5">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/5">Created</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/5">Actions</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                      <tr v-for="request in deliveryRequests.data" :key="request.id">
                        <td class="px-4 py-2 whitespace-nowrap">
                          <span class="text-sm font-medium text-green-600 font-mono">
                            {{ request.reference_number || 'N/A' }}
                          </span>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-900">
                          {{ request.receiver?.name || request.receiver?.company_name || 'N/A' }}
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap">
                          <span :class="getStatusClass(request.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                            {{ request.status ? formatStatus(request.status) : 'N/A' }}
                          </span>
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                          {{ new Date(request.created_at).toLocaleDateString() }}
                        </td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm font-medium">
                          <SecondaryButton @click="viewDelivery(request.id)" class="text-xs py-1 px-2">
                            View
                          </SecondaryButton>
                        </td>
                      </tr>
                      <tr v-if="!deliveryRequests.data || deliveryRequests.data.length === 0">
                        <td colspan="5" class="px-4 py-4 text-center text-sm text-gray-500">
                          No delivery requests found
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                
                <!-- Pagination for Delivery Requests -->
                <div v-if="deliveryRequests.links && deliveryRequests.links.length > 3" class="bg-white px-4 py-3 border-t border-gray-200">
                  <div class="flex justify-between items-center">
                    <div class="flex items-center">
                      <p class="text-sm text-gray-700">
                        Showing
                        <span class="font-medium">{{ deliveryRequests.from }}</span>
                        to
                        <span class="font-medium">{{ deliveryRequests.to }}</span>
                        of
                        <span class="font-medium">{{ deliveryRequests.total }}</span>
                        results
                      </p>
                    </div>
                    <div class="flex space-x-1">
                      <button 
                        v-for="(link, index) in deliveryRequests.links" 
                        :key="index"
                        @click="handleDeliveryRequestPageChange(link.url)"
                        class="px-2 py-1 text-xs rounded border"
                        :class="link.active ? 'bg-blue-500 text-white border-blue-500' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                        v-html="link.label"
                        :disabled="!link.url"
                      ></button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Recent Received Deliveries -->
            <div class="mt-6" v-if="recent_received_deliveries?.length">
              <div class="bg-white border border-gray-200 rounded-lg p-4">
                <h3 class="text-lg font-medium text-gray-900 mb-3">Recent Received Deliveries</h3>
                <div class="space-y-2">
                  <div v-for="delivery in recent_received_deliveries" :key="delivery.id" class="flex justify-between items-center py-1 border-b border-gray-100">
                    <span class="text-sm text-gray-700">{{ delivery.reference_number }}</span>
                    <span class="text-xs text-gray-500">{{ new Date(delivery.created_at).toLocaleDateString() }}</span>
                  </div>
                </div>
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

// Helper function for status badges
const getStatusClass = (status) => {
  const statusClasses = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'approved': 'bg-blue-100 text-blue-800',
    'assigned': 'bg-purple-100 text-purple-800',
    'in_transit': 'bg-indigo-100 text-indigo-800',
    'delivered': 'bg-green-100 text-green-800',
    'cancelled': 'bg-red-100 text-red-800',
    'completed': 'bg-green-100 text-green-800'
  };
  return statusClasses[status] || 'bg-gray-100 text-gray-800';
};

// Format status for display
const formatStatus = (status) => {
  return status ? status.replace(/_/g, ' ').toUpperCase() : 'N/A';
};

const editCustomer = () => {
  router.get(route('admin.customers.edit', props.customer.id));
};

const viewDelivery = (id) => {
  router.get(route('deliveries.show', id));
};

const handleDeliveryRequestPageChange = (url) => {
  if (url) {
    router.visit(url, {
      preserveState: true,
      preserveScroll: true
    });
  }
};
</script>

<style scoped>
.zoom-content {
  zoom: 0.80;
}

/* Add tighter spacing for desktop */
@media (min-width: 1024px) {
  .zoom-content {
    zoom: 0.90;
  }
}
</style>