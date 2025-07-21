<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-6">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Customer Management</h2>
        <div class="flex space-x-2">
          <PrimaryButton @click="createCustomer">Add New Customer</PrimaryButton>
          <SecondaryButton @click="viewArchived">View Disabled</SecondaryButton>
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
        <div class="flex flex-wrap gap-2 w-full md:w-auto">
          <SelectInput 
            v-model="customerCategoryFilter" 
            :options="customerCategoryOptions" 
            option-value="value"
            option-label="text"
            placeholder="All Types"
            class="w-full md:w-40"
          />
          <SelectInput 
            v-model="frequencyTypeFilter" 
            :options="frequencyTypeOptions" 
            option-value="value"
            option-label="text"
            placeholder="All Frequencies"
            class="w-full md:w-40"
          />
        </div>
      </div>

      <div class="flex items-center justify-center">
        <div class="bg-white overflow-x-auto shadow-sm sm:rounded-lg w-full px-4">
          <div class="p-2 bg-white border-b border-gray-200">
            <DataTable
              :columns="columns"
              :data="customers.data"
              :sort-field="sortField"
              :sort-direction="sortDirection"
              @sort="handleSort"
              class="w-full text-xs md:text-sm min-w-[700px]"
            >
              <template #first_name="{ row }">
                <span class="truncate block w-32">
                  {{ row.first_name || 'N/A' }}
                </span>
              </template>
              <template #last_name="{ row }">
                <span class="truncate block w-32">
                  {{ row.last_name || 'N/A' }}
                </span>
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
              <template #actions="{ row }">
                <div class="flex space-x-2 min-w-[200px]">
                  <!-- <SecondaryButton @click="viewCustomer(row.id)">View</SecondaryButton> -->
                  <PrimaryButton @click="editCustomer(row.id)">Edit</PrimaryButton>
                  <DangerButton @click="confirmArchiveCustomer(row)">Disable</DangerButton>
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

      <Modal :show="showArchiveModal" @close="closeArchiveModal">
        <div class="p-6">
          <h2 class="text-lg font-medium text-gray-900">Disable Customer?</h2>
          <p class="mt-1 text-sm text-gray-600">
            Are you sure you want to disable <strong>{{ customerToArchive?.name || customerToArchive?.company_name }}</strong>?
            This action can be undone later if needed.
          </p>
          <div class="mt-6 flex justify-end space-x-4">
            <SecondaryButton @click="closeArchiveModal">Cancel</SecondaryButton>
            <DangerButton @click="handleArchive" :disabled="isArchiving">
              <span v-if="isArchiving">Disabling...</span>
              <span v-else>Disable</span>
            </DangerButton>
          </div>
        </div>
      </Modal>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SearchInput from '@/Components/SearchInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import { ref, onMounted, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';

const props = defineProps({ 
  customers: Object, 
  status: String, 
  success: String, 
  error: String 
});

// State
const showArchiveModal = ref(false);
const customerToArchive = ref(null);
const isArchiving = ref(false);
const search = ref(props.customers.filters?.search || '');
const customerCategoryFilter = ref(props.customers.filters?.customer_category || '');
const frequencyTypeFilter = ref(props.customers.filters?.frequency_type || '');
const sortField = ref(props.customers.filters?.sort_field || 'created_at');
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
  { field: 'first_name', header: 'First Name', sortable: true },
  { field: 'last_name', header: 'Last Name', sortable: true },
  { field: 'email', header: 'Email', sortable: true },
  { field: 'mobile', header: 'Mobile', sortable: false },
  { field: 'customer_category', header: 'Type', sortable: true },
  { field: 'frequency_type', header: 'Frequency', sortable: true },
  { field: 'total_orders', header: 'Orders', sortable: true },
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

const confirmArchiveCustomer = (customer) => {
  customerToArchive.value = customer;
  showArchiveModal.value = true;
};

const closeArchiveModal = () => {
  showArchiveModal.value = false;
  setTimeout(() => { 
    customerToArchive.value = null; 
    isArchiving.value = false; 
  }, 300);
};

const handleArchive = async () => {
  if (!customerToArchive.value) return;
  isArchiving.value = true;
  try {
    await router.put(route('admin.customers.archive', customerToArchive.value.id), {}, { 
      preserveScroll: true 
    });
    closeArchiveModal();
    router.reload({ only: ['customers'] });
  } catch {
    closeArchiveModal();
    alert('Failed to archive customer. Please try again.');
  } finally {
    isArchiving.value = false;
  }
};

// Navigation
const createCustomer = () => router.get(route('admin.customers.create'));
const viewCustomer = (id) => router.get(route('admin.customers.show', id));
const editCustomer = (id) => router.get(route('admin.customers.edit', id));
const viewArchived = () => router.get(route('admin.customers.archived'));

// Watchers
watch([search, customerCategoryFilter, frequencyTypeFilter, sortField, sortDirection], () => {
  router.get(route('admin.customers.index'), {
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