<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-6">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Customer Update Requests</h2>
        <div class="flex space-x-2">
          <PrimaryButton @click="refreshRequests">Refresh</PrimaryButton>
        </div>
      </div>
    </template>

    <div class="py-4 px-2 w-full">
      <div class="mb-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 w-full">
        <SearchInput 
          v-model="search" 
          placeholder="Search customers..." 
          class="w-full md:w-64"
        />
      </div>

      <div class="flex items-center justify-center">
        <div class="bg-white overflow-x-auto shadow-sm sm:rounded-lg w-full px-4">
          <div class="p-2 bg-white border-b border-gray-200">
            <DataTable
              :columns="columns"
              :data="requests.data"
              :sort-field="sortField"
              :sort-direction="sortDirection"
              @sort="handleSort"
              class="w-full text-xs md:text-sm min-w-[700px]"
            >
              <template #customer_name="{ row }">
                <span class="truncate block w-32">
                  {{ row.customer?.name || 'N/A' }}
                </span>
              </template>
              <template #email="{ row }">
                <span class="truncate block w-48">{{ row.customer?.email || 'N/A' }}</span>
              </template>
              <template #changed_fields="{ row }">
                <span class="truncate block w-48">
                  {{ formatChangedFields(row) }}
                </span>
              </template>
              <template #status="{ row }">
                <span :class="statusClass(row.status)">
                  {{ formatStatus(row.status) }}
                </span>
              </template>
              <template #created_at="{ row }">
                {{ formatDate(row.created_at) }}
              </template>
              <template #actions="{ row }">
                <div class="flex space-x-2 min-w-[200px]">
                  <PrimaryButton @click="viewRequest(row.id)">View</PrimaryButton>
                </div>
              </template>
            </DataTable>

            <div v-if="requests.links?.length > 3" class="mt-4 flex justify-center">
              <div class="flex flex-wrap -mb-1">
                <template v-for="(link, index) in requests.links" :key="index">
                  <div v-if="!link.url" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded" 
                       v-html="link.label" />
                  <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white focus:border-indigo-500 focus:text-indigo-500" 
                        :class="{ 'bg-blue-700 text-white': link.active }" :href="link.url" v-html="link.label" />
                </template>
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
import SearchInput from '@/Components/SearchInput.vue';
import DataTable from '@/Components/DataTable.vue';
import { ref, onMounted, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';

const props = defineProps({ 
  requests: Object,
  filters: Object
});

// State
const search = ref(props.filters?.search || '');
const sortField = ref(props.filters?.sort_field || 'created_at');
const sortDirection = ref(props.filters?.sort_direction || 'desc');

// Columns
const columns = [
  { field: 'customer_name', header: 'Customer', sortable: true },
  { field: 'email', header: 'Email', sortable: true },
  { field: 'changed_fields', header: 'Changed Fields', sortable: false },
  { field: 'status', header: 'Status', sortable: true },
  { field: 'created_at', header: 'Submitted', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false }
];

// Methods
const handleSort = (field) => {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortField.value = field;
    sortDirection.value = 'asc';
  }
};

const formatChangedFields = (request) => {
  const fields = [];
  const profileFields = [
    'first_name', 'middle_name', 'last_name', 'email', 'mobile', 'phone',
    'building_number', 'street', 'barangay', 'city', 'province', 'zip_code'
  ];
  
  profileFields.forEach(field => {
    if (request[field] !== null) {
      fields.push(field.replace('_', ' '));
    }
  });
  
  return fields.join(', ') || 'No changes';
};

const formatStatus = (status) => {
  const statusMap = {
    pending: 'Pending',
    approved: 'Approved',
    rejected: 'Rejected'
  };
  return statusMap[status] || status;
};

const statusClass = (status) => {
  const classes = {
    pending: 'text-yellow-600 bg-yellow-100 px-2 py-1 rounded text-xs',
    approved: 'text-green-600 bg-green-100 px-2 py-1 rounded text-xs',
    rejected: 'text-red-600 bg-red-100 px-2 py-1 rounded text-xs'
  };
  return classes[status] || 'text-gray-600 bg-gray-100 px-2 py-1 rounded text-xs';
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString();
};

const viewRequest = (id) => {
  router.get(route('admin.customer-update-requests.show', id));
};

const refreshRequests = () => {
  router.reload({ only: ['requests'] });
};

// Watchers
watch([search, sortField, sortDirection], () => {
  router.get(route('admin.customer-update-requests.index'), {
    search: search.value,
    sort_field: sortField.value,
    sort_direction: sortDirection.value,
    page: 1
  }, { 
    preserveState: true, 
    replace: true 
  });
}, { deep: true });
</script>

<style scoped>
:deep(.datatable-cell) {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>