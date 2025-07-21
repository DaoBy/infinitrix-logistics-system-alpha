<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-6">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Archived Customers</h2>
        <div class="flex space-x-2">
          <SecondaryButton @click="viewActive">View Active Customers</SecondaryButton>
        </div>
      </div>
    </template>

    <div class="px-6 sm:px-8">
      <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 w-full mx-auto">
        <SearchInput 
          v-model="search" 
          placeholder="Search archived customers..." 
          class="w-full md:w-72"
        />
        <div class="flex flex-wrap gap-2 w-full md:w-auto">
          <SelectInput 
            v-model="customerCategoryFilter" 
            :options="customerCategoryOptions" 
            option-value="value"
            option-label="text"
            placeholder="All Types"
            class="w-full md:w-48"
          />
          <SelectInput 
            v-model="frequencyTypeFilter" 
            :options="frequencyTypeOptions" 
            option-value="value"
            option-label="text"
            placeholder="All Frequencies"
            class="w-full md:w-48"
          />
        </div>
      </div>

 <div class="justify-center flex items-center">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-center">
        <div class="p-5 bg-white border-b border-gray-200">
          <DataTable
            :columns="columns"
            :data="customers.data"
            :sort-field="sortField"
            :sort-direction="sortDirection"
            @sort="handleSort"
            class="w-full"
          >
            <template #name="{ row }">
              <span class="truncate block w-48">
                {{ row.name }}
              </span>
            </template>
            <template #company_name="{ row }">
              <span class="truncate block w-48">{{ row.company_name || 'N/A' }}</span>
            </template>
            <template #email="{ row }">
              <span class="truncate block w-48">{{ row.email || 'N/A' }}</span>
            </template>
            <template #mobile="{ row }">
              {{ row.mobile || 'N/A' }}
            </template>
            <template #customer_category="{ row }">
              {{ row.customer_category ? row.customer_category.charAt(0).toUpperCase() + row.customer_category.slice(1) : 'N/A' }}
            </template>
            <template #frequency_type="{ row }">
              {{ row.frequency_type ? row.frequency_type.charAt(0).toUpperCase() + row.frequency_type.slice(1) : 'N/A' }}
            </template>
            <template #total_orders="{ row }">
              {{ row.total_orders || 0 }}
            </template>
            <template #archived_at="{ row }">
              {{ row.archived_at ? new Date(row.archived_at).toLocaleDateString() : 'N/A' }}
            </template>
            <template #status="{ row }">
              <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                Archived
              </span>
            </template>
            <template #actions="{ row }">
              <div class="flex space-x-2 min-w-[200px]">
                <SecondaryButton @click="restoreCustomer(row)">Restore</SecondaryButton>
                <DangerButton @click="confirmDeleteCustomer(row)">Delete Permanently</DangerButton>
              </div>
            </template>
          </DataTable>

          <div v-if="customers.links?.length > 3" class="mt-4 flex justify-center">
            <div class="flex flex-wrap -mb-1">
              <template v-for="(link, index) in customers.links" :key="index">
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

    <Modal :show="showDeleteModal" @close="closeDeleteModal">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">Delete Customer Permanently?</h2>
        <p class="mt-1 text-sm text-gray-600">
          Are you sure you want to permanently delete <strong>{{ customerToDelete?.name || customerToDelete?.company_name }}</strong>?
          This action cannot be undone.
        </p>
        <div class="mt-6 flex justify-end space-x-4">
          <SecondaryButton @click="closeDeleteModal">Cancel</SecondaryButton>
          <DangerButton @click="handleDelete" :disabled="isDeleting">
            <span v-if="isDeleting">Deleting...</span>
            <span v-else>Delete Permanently</span>
          </DangerButton>
        </div>
      </div>
    </Modal>
  </EmployeeLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SearchInput from '@/Components/SearchInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({ 
  customers: Object, 
  status: String, 
  success: String, 
  error: String 
});

// State
const showDeleteModal = ref(false);
const customerToDelete = ref(null);
const isDeleting = ref(false);
const search = ref(props.customers.filters?.search || '');
const customerCategoryFilter = ref(props.customers.filters?.customer_category || '');
const frequencyTypeFilter = ref(props.customers.filters?.frequency_type || '');
const sortField = ref(props.customers.filters?.sort_field || 'archived_at');
const sortDirection = ref(props.customers.filters?.sort_direction || 'desc');

// Constants
const customerCategoryOptions = [
  { value: '', text: 'All Types' },
  { value: 'individual', text: 'Individual' },
  { value: 'company', text: 'Company' }
];

const frequencyTypeOptions = [
  { value: '', text: 'All Frequencies' },
  { value: 'regular', text: 'Regular' },
  { value: 'occasional', text: 'Occasional' }
];

const columns = [
  { field: 'name', header: 'Name', sortable: true},
  { field: 'company_name', header: 'Company', sortable: true},
  { field: 'email', header: 'Email', sortable: true},
  { field: 'mobile', header: 'Mobile', sortable: false},
  { field: 'customer_category', header: 'Type', sortable: true },
  { field: 'frequency_type', header: 'Frequency', sortable: true },
  { field: 'total_orders', header: 'Orders', sortable: true},
  { field: 'archived_at', header: 'Archived On', sortable: true},
  { field: 'status', header: 'Status', sortable: true},
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

const confirmDeleteCustomer = (customer) => {
  customerToDelete.value = customer;
  showDeleteModal.value = true;
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  setTimeout(() => { 
    customerToDelete.value = null; 
    isDeleting.value = false; 
  }, 300);
};

const handleDelete = async () => {
  if (!customerToDelete.value) return;
  isDeleting.value = true;
  try {
    await router.delete(route('admin.customers.destroy', customerToDelete.value.id), { 
      preserveScroll: true 
    });
    closeDeleteModal();
    router.reload({ only: ['customers'] });
  } catch {
    closeDeleteModal();
    alert('Failed to delete customer. Please try again.');
  } finally {
    isDeleting.value = false;
  }
};

const restoreCustomer = (customer) => {
  router.put(route('admin.customers.restore', customer.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      router.reload();
    },
  });
};

const viewActive = () => router.get(route('admin.customers.index'));

// Watchers
watch([search, customerCategoryFilter, frequencyTypeFilter, sortField, sortDirection], () => {
  router.get(route('admin.customers.archived'), {
    search: search.value,
    customer_category: customerCategoryFilter.value,
    frequency_type: frequencyTypeFilter.value,
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