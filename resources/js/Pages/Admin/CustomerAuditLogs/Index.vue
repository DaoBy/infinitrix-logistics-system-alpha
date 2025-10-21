<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">Customer Profile Audit Logs</h2>
          <p class="mt-1 text-sm text-gray-500">
            Track and monitor all customer profile changes
          </p>
        </div>

        <!-- Right: Buttons -->
        <div class="flex gap-2">
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
          <div v-if="status" class="p-3 bg-blue-100 text-blue-800 rounded">{{ status }}</div>
          <div v-if="success" class="p-3 bg-green-100 text-green-800 rounded">{{ success }}</div>
          <div v-if="error" class="p-3 bg-red-100 text-red-800 rounded">{{ error }}</div>
        </div>

        <!-- Filters -->
        <div class="mb-6 bg-white p-4 rounded-lg shadow-sm border">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <InputLabel for="search" value="Search Customer" />
              <TextInput
                id="search"
                type="text"
                class="mt-1 block w-full"
                v-model="filters.search"
                placeholder="Name, email, company..."
              />
            </div>
            <div>
              <InputLabel for="change_type" value="Change Type" />
              <SelectInput
                id="change_type"
                v-model="filters.change_type"
                :options="changeTypeOptions"
                option-value="value"
                option-label="text"
                placeholder="All Types"
                class="mt-1 block w-full"
              />
            </div>
            <div>
              <InputLabel for="date_from" value="From Date" />
              <TextInput
                id="date_from"
                type="date"
                class="mt-1 block w-full"
                v-model="filters.date_from"
              />
            </div>
            <div>
              <InputLabel for="date_to" value="To Date" />
              <TextInput
                id="date_to"
                type="date"
                class="mt-1 block w-full"
                v-model="filters.date_to"
              />
            </div>
          </div>
          <div class="mt-4 flex justify-end space-x-2">
            <SecondaryButton @click="resetFilters">Reset Filters</SecondaryButton>
            <PrimaryButton @click="applyFilters">Apply Filters</PrimaryButton>
          </div>
        </div>

        <!-- Data Table Container -->
        <div class="justify-center flex items-center">
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-full max-w-[95vw]">
            <div class="p-4 bg-white border-b border-gray-200">
              <DataTable
                :columns="columns"
                :data="auditLogs.data"
                :sort-field="sortField"
                :sort-direction="sortDirection"
                @sort="handleSort"
                class="w-full"
              >
                <template #customer="{ row }">
                  <div class="flex items-center space-x-3">
                    <div>
                      <p class="font-medium text-gray-900">{{ row.customer.name }}</p>
                      <p class="text-sm text-gray-500">{{ row.customer.email }}</p>
                    </div>
                  </div>
                </template>

                <template #change_type="{ row }">
                  <span 
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full capitalize"
                    :class="changeTypeClass(row.change_type)"
                  >
                    {{ formatChangeType(row.change_type) }}
                  </span>
                </template>

                <template #changes="{ row }">
                  <div class="space-y-1">
                    <div class="font-medium text-gray-900 capitalize">
                      {{ row.field_name.replace('_', ' ') }}
                    </div>
                    <div class="text-sm">
                      <span class="text-red-600 line-through">{{ row.old_value || 'Empty' }}</span>
                      <span class="mx-2 text-gray-400">→</span>
                      <span class="text-green-600">{{ row.new_value || 'Empty' }}</span>
                    </div>
                  </div>
                </template>

                <template #changed_by="{ row }">
                  <div v-if="row.changed_by">
                    <p class="font-medium text-gray-900">{{ row.changed_by.name }}</p>
                    <p class="text-sm text-gray-500">{{ row.changed_by.email }}</p>
                  </div>
                  <span v-else class="text-gray-400">System</span>
                </template>

          

                <template #created_at="{ row }">
                  <div class="text-sm text-gray-900">
                    {{ formatDateTime(row.created_at) }}
                  </div>
                </template>

                <template #actions="{ row }">
                  <div class="flex space-x-2">
                    <SecondaryButton 
                      @click="viewCustomerLogs(row.customer_id)"
                      class="text-xs py-1 px-2"
                    >
                      View All
                    </SecondaryButton>
                  </div>
                </template>

                <!-- Empty State Slot -->
                <template #empty>
                  <div class="text-center py-8">
                    <div class="bg-gray-50 rounded-lg p-6 max-w-md mx-auto">
                      <svg class="h-10 w-10 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                      <h3 class="text-lg font-medium text-gray-900 mb-2">No audit logs found</h3>
                      <p class="text-gray-500 mb-3">
                        {{ filters.search ? 'Try adjusting your search terms' : 'No profile changes recorded yet' }}
                      </p>
                    </div>
                  </div>
                </template>
              </DataTable>

              <!-- Pagination Component -->
              <div v-if="auditLogs.links?.length > 3" class="mt-4">
                <Pagination 
                  :pagination="auditLogs" 
                  @page-changed="handlePageChange" 
                />
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
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import DataTable from '@/Components/DataTable.vue';
import Pagination from '@/Components/Pagination.vue';
import { ref, watch, onMounted, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { debounce } from 'lodash';

const props = defineProps({
  auditLogs: Object,
  filters: Object,
  changeTypes: Array,
  status: String,
  success: String,
  error: String,
});

// State
const filters = ref({
  search: props.filters?.search || '',
  change_type: props.filters?.change_type || '',
  date_from: props.filters?.date_from || '',
  date_to: props.filters?.date_to || '',
});

const sortField = ref('created_at');
const sortDirection = ref('desc');

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

// Columns
const columns = [
  { field: 'customer', header: 'Customer', sortable: true },
  { field: 'change_type', header: 'Change Type', sortable: true },
  { field: 'changes', header: 'Field Changes', sortable: false },
  { field: 'changed_by', header: 'Changed By', sortable: true },
  { field: 'created_at', header: 'Date & Time', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false },
];

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

const handleSort = (sortParams) => {
  sortField.value = sortParams.field;
  sortDirection.value = sortParams.direction;
};

const handlePageChange = (page) => {
  router.get(route('admin.customer-audit-logs.index'), { 
    page: page,
    ...filters.value,
    sort_field: sortField.value,
    sort_direction: sortDirection.value
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
};

const applyFilters = () => {
  router.get(route('admin.customer-audit-logs.index'), {
    ...filters.value,
  }, {
    preserveState: true,
    replace: true,
  });
};

const resetFilters = () => {
  filters.value = {
    search: '',
    change_type: '',
    date_from: '',
    date_to: '',
  };
  applyFilters();
};

const refreshLogs = () => {
  router.reload({ only: ['auditLogs'] });
};

const viewCustomerLogs = (customerId) => {
  router.get(route('admin.customers.audit-logs', customerId));
};

// Watchers
watch(filters, debounce(() => {
  applyFilters();
}, 500), { deep: true });

watch([sortField, sortDirection], () => {
  router.get(route('admin.customer-audit-logs.index'), {
    ...filters.value,
    sort_field: sortField.value,
    sort_direction: sortDirection.value,
  }, {
    preserveState: true,
    replace: true,
  });
});
</script>

<style scoped>
.zoom-content {
  zoom: 0.90;
}

/* DataTable styling adjustments */
:deep(.datatable-table) {
  width: 100%;
}

/* Reduce table row padding for more compact rows */
:deep(.datatable-table td) {
  padding-top: 0.375rem !important;
  padding-bottom: 0.375rem !important;
}

/* Reduce table header padding */
:deep(.datatable-table th) {
  padding-top: 0.5rem !important;
  padding-bottom: 0.5rem !important;
  font-size: 0.875rem !important;
}

/* Reduce button sizes in the table */
:deep(.datatable-table .btn) {
  padding: 0.25rem 0.5rem !important;
  font-size: 0.75rem !important;
}
</style>