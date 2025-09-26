<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">Customer Management</h2>
          <p class="mt-1 text-sm text-gray-500">
            Manage active customers and their information
          </p>
        </div>

        <!-- Right: Buttons -->
        <div class="flex gap-2">
          <SecondaryButton @click="viewArchived">View Disabled Customers</SecondaryButton>
          <PrimaryButton @click="createCustomer">Add New Customer</PrimaryButton>
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

        <!-- Search Bar -->
        <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
          <div class="w-full sm:w-96">
            <SearchInput 
              v-model="search" 
              placeholder="Search customers by name, email, or company..." 
              class="w-full"
            />
          </div>
          
          <!-- Filters and Info Row -->
          <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full sm:w-auto">
            <!-- Dropdown Filters -->
            <div class="flex flex-wrap gap-2">
              <SelectInput 
                v-model="customerCategoryFilter" 
                :options="customerCategoryOptions" 
                option-value="value"
                option-label="text"
                placeholder="All Types"
                class="w-full sm:w-48"
              />
              <SelectInput 
                v-model="frequencyTypeFilter" 
                :options="frequencyTypeOptions" 
                option-value="value"
                option-label="text"
                placeholder="All Frequencies"
                class="w-full sm:w-48"
              />
            </div>
            
            <!-- Info Counter -->
            <div class="text-sm text-gray-500 bg-blue-50 px-3 py-1 rounded border border-blue-100 whitespace-nowrap">
              📋 Showing {{ customers.data?.length || 0 }} of {{ customers.total }} customers
              <span v-if="customers.data && customers.data.length < customers.total" class="ml-1">
                (Page {{ customers.current_page }} of {{ customers.last_page }})
              </span>
            </div>
          </div>
        </div>

        <!-- Data Table Container with proper spacing -->
        <div class="justify-center flex items-center">
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-full max-w-[95vw]">
            <div class="p-4 bg-white border-b border-gray-200">
              <DataTable 
                :columns="columns" 
                :data="customers.data || []"
                :sort-field="sortField"
                :sort-direction="sortDirection"
                @sort="handleSort"
                class="w-full"
              >
                <template #name="{ row }">
                  <div class="flex items-center">
                    <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                      <span class="text-sm font-medium text-gray-700">
                        {{ getInitials(row.first_name, row.last_name, row.company_name) }}
                      </span>
                    </div>
                    <div>
                      <span class="font-medium text-gray-900 block">
                        {{ row.first_name }} {{ row.last_name }}
                      </span>
                      <span v-if="row.company_name" class="text-xs text-gray-500 block">
                        {{ row.company_name }}
                      </span>
                    </div>
                  </div>
                </template>
                <template #email="{ row }">
                  <span class="text-gray-700">{{ row.email || 'No email' }}</span>
                </template>
                <template #mobile="{ row }">
                  <span class="text-gray-700">{{ row.mobile || 'No phone' }}</span>
                </template>
                <template #customer_category="{ row }">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                        :class="getCategoryClass(row.customer_category)">
                    {{ row.customer_category ? row.customer_category.charAt(0).toUpperCase() + row.customer_category.slice(1) : 'N/A' }}
                  </span>
                </template>
                <template #frequency_type="{ row }">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                        :class="getFrequencyClass(row.frequency_type)">
                    {{ row.frequency_type ? row.frequency_type.charAt(0).toUpperCase() + row.frequency_type.slice(1) : 'N/A' }}
                  </span>
                </template>
                <template #total_orders="{ row }">
                  <span class="font-medium text-gray-900">{{ row.total_orders || 0 }}</span>
                </template>
                <template #actions="{ row }">
                  <div class="flex space-x-2">
                    <PrimaryButton @click="editCustomer(row.id)" class="text-xs py-1 px-2">Edit</PrimaryButton>
                    <DangerButton @click="confirmArchiveCustomer(row)" class="text-xs py-1 px-2">Disable</DangerButton>
                  </div>
                </template>
                
                <!-- Empty State Slot -->
                <template #empty>
                  <div class="text-center py-8">
                    <div class="bg-gray-50 rounded-lg p-6 max-w-md mx-auto">
                      <UserGroupIcon class="h-10 w-10 text-gray-400 mx-auto mb-3" />
                      <h3 class="text-lg font-medium text-gray-900 mb-2">No customers found</h3>
                      <p class="text-gray-500 mb-3">
                        {{ search ? 'Try adjusting your search terms' : 'Get started by adding your first customer' }}
                      </p>
                      <PrimaryButton @click="createCustomer">Add New Customer</PrimaryButton>
                    </div>
                  </div>
                </template>
              </DataTable>

              <!-- Pagination Component -->
              <div class="mt-4">
                <Pagination 
                  :pagination="customers" 
                  @page-changed="handlePageChange"
                  :per-page="10"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Archive Confirmation Modal -->
    <Modal :show="showArchiveModal" @close="closeArchiveModal">
      <div class="p-5">
        <h2 class="text-lg font-medium text-gray-900">Disable Customer?</h2>
        <p class="mt-1 text-sm text-gray-600">
          Are you sure you want to disable 
          <strong>{{ customerToArchive?.first_name }} {{ customerToArchive?.last_name }}</strong>? 
          This will move them to the disabled customers list.
        </p>
        <div class="mt-4 flex justify-end space-x-3">
          <SecondaryButton @click="closeArchiveModal">Cancel</SecondaryButton>
          <DangerButton @click="handleArchive" :disabled="isArchiving">
            <span v-if="isArchiving">Disabling...</span>
            <span v-else>Disable Customer</span>
          </DangerButton>
        </div>
      </div>
    </Modal>
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
import Pagination from '@/Components/Pagination.vue';
import { UserGroupIcon } from '@heroicons/vue/24/outline';
import { ref, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';

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
const sortField = ref(props.customers.filters?.sort_field || 'name');
const sortDirection = ref(props.customers.filters?.sort_direction || 'asc');

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
  { field: 'name', header: 'Customer Name', sortable: true },
  { field: 'email', header: 'Email', sortable: true },
  { field: 'mobile', header: 'Phone', sortable: false },
  { field: 'customer_category', header: 'Type', sortable: true },
  { field: 'frequency_type', header: 'Frequency', sortable: true },
  { field: 'total_orders', header: 'Total Orders', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false }
];

// Methods
const getInitials = (firstName, lastName, companyName) => {
  if (firstName && lastName) {
    return `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase();
  }
  if (companyName) {
    return companyName.substring(0, 2).toUpperCase();
  }
  return 'CU';
};

const getCategoryClass = (category) => {
  switch (category) {
    case 'individual': return 'bg-purple-100 text-purple-800';
    case 'company': return 'bg-blue-100 text-blue-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};

const getFrequencyClass = (frequency) => {
  switch (frequency) {
    case 'regular': return 'bg-green-100 text-green-800';
    case 'occasional': return 'bg-yellow-100 text-yellow-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};

// Fixed handleSort function to properly handle the sort event (from Warehouse page)
function handleSort(sortParams) {
  // The DataTable emits an object with field and direction properties
  sortField.value = sortParams.field;
  sortDirection.value = sortParams.direction;
}

function handlePageChange(page) {
  router.get(route('admin.customers.index'), { 
    page: page,
    search: search.value,
    customer_category: customerCategoryFilter.value,
    frequency_type: frequencyTypeFilter.value,
    sort_field: sortField.value,
    sort_direction: sortDirection.value
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
}

function createCustomer() {
  router.get(route('admin.customers.create'));
}

function editCustomer(id) {
  router.get(route('admin.customers.edit', id));
}

function viewArchived() {
  router.get(route('admin.customers.archived'));
}

function confirmArchiveCustomer(customer) {
  customerToArchive.value = customer;
  showArchiveModal.value = true;
}

function closeArchiveModal() {
  showArchiveModal.value = false;
  setTimeout(() => { 
    customerToArchive.value = null; 
    isArchiving.value = false; 
  }, 300);
}

function handleArchive() {
  if (!customerToArchive.value) return;
  
  isArchiving.value = true;
  router.put(route('admin.customers.archive', customerToArchive.value.id), {}, {
    preserveScroll: true,
    onSuccess: () => router.reload({ only: ['customers'] }),
    onError: () => alert('Failed to disable customer'),
    onFinish: () => closeArchiveModal()
  });
}

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
.zoom-content {
  zoom: 0.80;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Override DataTable's left padding if needed */
:deep(.datatable) {
  margin-left: 2rem;
}

:deep(.datatable-table) {
  width: 100%;
}

/* Further reduce table row padding for more compact rows */
:deep(.datatable-table td) {
  padding-top: 0.375rem !important;
  padding-bottom: 0.375rem !important;
}

/* Further reduce table header padding */
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