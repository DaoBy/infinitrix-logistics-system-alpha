<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Audit Logs: {{ customer.name }}
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Complete change history for this customer
          </p>
        </div>

        <!-- Right: Buttons -->
        <div class="flex gap-2">
          <SecondaryButton @click="goBack">Back to All Logs</SecondaryButton>
          <PrimaryButton @click="refreshLogs">Refresh</PrimaryButton>
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
              <label class="block font-medium text-gray-700">Total Changes</label>
              <p class="mt-0.5 text-gray-900 font-medium">{{ auditLogs.total || 0 }}</p>
            </div>
          </div>
        </div>

        <!-- Filters -->
        <div class="mb-4 bg-white p-3 rounded-lg shadow-sm border">
          <div class="flex items-end space-x-3">
            <div class="flex-1">
              <InputLabel for="change_type" value="Filter by Change Type" class="text-sm" />
              <SelectInput
                id="change_type"
                v-model="filters.change_type"
                :options="changeTypeOptions"
                option-value="value"
                option-label="text"
                placeholder="All Types"
                class="mt-1 block w-full text-sm"
              />
            </div>
            <div>
              <SecondaryButton @click="resetFilters" class="text-sm">Reset Filters</SecondaryButton>
            </div>
          </div>
        </div>

        <!-- Audit Logs in 2-column grid -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="px-4 py-4">
            <!-- Audit Logs Grid -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
              <div v-for="log in auditLogs.data" :key="log.id" class="border border-gray-200 rounded-lg p-3">
                <!-- Header -->
                <div class="flex justify-between items-start mb-2">
                  <div class="flex items-center space-x-2">
                    <span 
                      class="px-2 py-0.5 rounded-full text-xs font-medium capitalize"
                      :class="changeTypeClass(log.change_type)"
                    >
                      {{ formatChangeType(log.change_type) }}
                    </span>
                    <span class="text-xs text-gray-500">{{ formatDateTime(log.created_at) }}</span>
                  </div>
                  <div v-if="log.changed_by" class="text-right">
                    <p class="text-xs font-medium text-gray-900">{{ log.changed_by.name }}</p>
                    <p class="text-[10px] text-gray-500 truncate max-w-[120px]">{{ log.changed_by.email }}</p>
                  </div>
                  <div v-else class="text-right">
                    <p class="text-xs text-gray-400">System</p>
                  </div>
                </div>

                <!-- Field Changes -->
                <div class="space-y-2">
                  <div>
                    <label class="block text-xs font-medium text-gray-500">Field Changed</label>
                    <p class="text-xs font-medium text-gray-900 capitalize mt-0.5">
                      {{ log.field_name.replace('_', ' ') }}
                    </p>
                  </div>
                  <div class="grid grid-cols-2 gap-2">
                    <div>
                      <label class="block text-xs font-medium text-gray-500">Old Value</label>
                      <p class="text-xs text-red-600 bg-red-50 p-1.5 rounded mt-0.5 truncate">
                        {{ log.old_value || 'Empty' }}
                      </p>
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-gray-500">New Value</label>
                      <p class="text-xs text-green-600 bg-green-50 p-1.5 rounded mt-0.5 truncate">
                        {{ log.new_value || 'Empty' }}
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Reason -->
                <div v-if="log.change_reason" class="mt-2">
                  <label class="block text-xs font-medium text-gray-500">Reason</label>
                  <p class="text-xs text-gray-700 mt-0.5 line-clamp-2">{{ log.change_reason }}</p>
                </div>
              </div>
            </div>

            <!-- Pagination Component -->
            <div v-if="auditLogs.links?.length > 3" class="mt-6">
              <Pagination 
                :pagination="auditLogs" 
                @page-changed="handlePageChange" 
              />
            </div>

            <!-- Empty State -->
            <div v-if="auditLogs.data.length === 0" class="text-center py-8">
              <div class="bg-gray-50 rounded-lg p-6 max-w-md mx-auto">
                <svg class="h-10 w-10 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No audit logs found</h3>
                <p class="text-gray-500 text-sm">
                  {{ filters.change_type ? 'Try adjusting your filters' : 'No changes recorded for this customer yet' }}
                </p>
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
import InputLabel from '@/Components/InputLabel.vue';
import SelectInput from '@/Components/SelectInput.vue';
import Pagination from '@/Components/Pagination.vue';
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  customer: Object,
  auditLogs: Object,
  filters: Object,
  changeTypes: Array,
  status: String,
  success: String,
  error: String,
});

const filters = ref({
  change_type: props.filters?.change_type || '',
});

// Computed
const changeTypeOptions = computed(() => {
  const options = [{ value: '', text: 'All Types' }];
  props.changeTypes.forEach(type => {
    options.push({
      value: type,
      text: formatChangeType(type)
    });
  });
  return options;
});

// Methods
const formatChangeType = (type) => {
  const typeMap = {
    'customer_update': 'Customer Update',
    'admin_update': 'Admin Update',
    'auto_locked': 'Auto Locked',
    'approved_request': 'Approved Request',
  };
  return typeMap[type] || type;
};

const changeTypeClass = (type) => {
  const classes = {
    'customer_update': 'bg-blue-100 text-blue-800',
    'admin_update': 'bg-purple-100 text-purple-800',
    'auto_locked': 'bg-amber-100 text-amber-800',
    'approved_request': 'bg-green-100 text-green-800',
  };
  return classes[type] || 'bg-gray-100 text-gray-800';
};

const formatDateTime = (date) => {
  return new Date(date).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const goBack = () => {
  router.visit(route('admin.customer-audit-logs.index'));
};

const refreshLogs = () => {
  router.reload({ only: ['auditLogs'] });
};

const resetFilters = () => {
  filters.value.change_type = '';
};

const handlePageChange = (page) => {
  router.get(route('admin.customers.audit-logs', props.customer.id), { 
    page: page,
    ...filters.value
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
};

// Watchers
watch(filters, () => {
  router.get(route('admin.customers.audit-logs', props.customer.id), filters.value, {
    preserveState: true,
    replace: true,
  });
}, { deep: true });
</script>

<style scoped>
.zoom-content {
  zoom: 1;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>