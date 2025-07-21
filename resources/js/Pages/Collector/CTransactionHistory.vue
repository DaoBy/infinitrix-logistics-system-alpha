<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Transaction History</h2>

      </div>
    </template>

    <div class="py-6">
      <div class="mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <div class="mb-6 flex justify-between items-center">
              <SearchInput v-model="search" placeholder="Search customers..." />
              <div class="flex space-x-2">
                <SelectInput 
                  v-model="entityType" 
                  :options="entityTypeOptions" 
                  placeholder="Filter by type"
                />
                <SelectInput 
                  v-model="frequencyType" 
                  :options="frequencyTypeOptions" 
                  placeholder="Filter by frequency"
                />
              </div>
            </div>

            <DataTable 
      :columns="columns" 
      :data="customers"
      :sort-field="sortField"
      :sort-direction="sortDirection"
      @sort="handleSort"
    >
      <!-- 
        Custom Cell Templates
        * Override default cell rendering for specific columns
        * 'name' shows either personal name or company name
        * 'company_name' shows company name or 'N/A'
        * 'actions' contains buttons for each row
      -->
      <template #name="{ row }">
        {{ row.name || row.company_name || 'N/A' }}
      </template>
      <template #company_name="{ row }">
        {{ row.company_name || 'N/A' }}
      </template>
      <template #actions="{ row }">
        <div class="flex flex-wrap gap-2">
          <!-- Action Buttons - customize these as needed -->
          <SecondaryButton @click="viewCustomer(row.id)">View</SecondaryButton>
          <PrimaryButton @click="editCustomer(row.id)">Edit</PrimaryButton>
          <DangerButton @click="confirmArchiveCustomer(row)">
            Archive
          </DangerButton>
        </div>
      </template>
    </DataTable>

    <!-- 
      Archive Confirmation Modal Pewde niyo toh gamitin if you want. Does need ng Methods to make it work. 
      Check niyo na lang yung sa baba "showArchiveModal" "closeArchiveModal" "archivecustomer"

      * Appears when user clicks "Archive" button
      * Customize the message as needed
    -->
    <Modal :show="showArchiveModal" @close="closeArchiveModal">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">Archive Customer?</h2>
        <p class="mt-1 text-sm text-gray-600">
          Are you sure you want to archive <strong>{{ customerToArchive?.name || customerToArchive?.company_name }}</strong>?
        </p>
        <div class="mt-6 flex justify-end space-x-4">
          <SecondaryButton @click="closeArchiveModal">Cancel</SecondaryButton>
          <DangerButton @click="archiveCustomer" :disabled="isProcessing">
            <span v-if="isProcessing">Processing...</span>
            <span v-else>Archive</span>
          </DangerButton>
        </div>
      </div>
    </Modal>
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
import SearchInput from '@/Components/SearchInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  customers: {
    type: Array,
    required: true,
  },
  status: String,
  success: String,
  error: String,
});

const search = ref('');
const entityType = ref('');
const frequencyType = ref('');

const entityTypeOptions = [
  { value: '', label: 'All Types' },
  { value: 'individual', label: 'Individual' },
  { value: 'company', label: 'Company' }
];

const frequencyTypeOptions = [
  { value: '', label: 'All Frequencies' },
  { value: 'regular', label: 'Regular' },
  { value: 'occasional', label: 'Occasional' }
];

const customers = ref([
  {
    id: 1001,
    name: "Blase Mariano",
    company_name: "",
    package_code: 100001234,  
    payment_status: "Paid",   
    amount_paid: "10000",    
    payment_type: "cash",      
    total_orders: 5,
    created_at: "2025-05-26"         
  },  {
    id: 1002,
    name: "Jose Pasqual",
    company_name: "",
    package_code: 100001235,   
    payment_status: "Paid",   
    amount_paid: "10000",    
    payment_type: "cash",  
    total_orders: 5,
    created_at: "2025-05-26"         
  },
]);

// STATE MANAGEMENT
const sortField = ref('created_at');   // Current column being sorted
const sortDirection = ref('desc');     // Sort direction (asc/desc)
const showArchiveModal = ref(false);   // Controls archive modal visibility
const customerToArchive = ref(null);   // Stores customer being archived
const isProcessing = ref(false);       // Loading state during archive operation

const columns = [
  { field: 'id', header: 'Transaction ID', sortable: true },
  { field: 'name', header: 'Name', sortable: true },
  { field: 'company_name', header: 'Company', sortable: true },
  { field: 'package_code', header: 'Package code', sortable: true },
  { field: 'payment_status', header: 'Payment Status', sortable: true },
  { field: 'amount_paid', header: 'Amount Paid', sortable: true },
  { field: 'payment_type', header: 'Payment', sortable: true },
  { field: 'created_at', header: 'Created', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false },
];

function handleSort(field) {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortField.value = field;
    sortDirection.value = 'asc';
  }
}

function viewCustomer(id) {
  alert(`Would view customer with ID: ${id}`);

}

function editCustomer(id) {
  alert(`Would edit customer with ID: ${id}`);

}

function confirmArchiveCustomer(customer) {
  customerToArchive.value = customer;
  showArchiveModal.value = true;
}

function closeArchiveModal() {
  showArchiveModal.value = false;
  customerToArchive.value = null;
  isProcessing.value = false;
}

function archiveCustomer() {
  if (!customerToArchive.value) return;
  
  isProcessing.value = true;
  
 
  setTimeout(() => {
    customers.value = customers.value.filter(
      c => c.id !== customerToArchive.value.id
    );
    closeArchiveModal();
  }, 800);
}

const filteredCustomers = computed(() => {
  if (!props.customers) return [];
  
  return props.customers.filter(customer => {
    // Search filter
    const matchesSearch = 
      customer.name?.toLowerCase().includes(search.value.toLowerCase()) ||
      customer.company_name?.toLowerCase().includes(search.value.toLowerCase()) ||
      customer.email?.toLowerCase().includes(search.value.toLowerCase()) ||
      customer.mobile?.toLowerCase().includes(search.value.toLowerCase());

    // Entity type filter - handle null/undefined cases
    const entityTypeValue = customer.entity_type?.toLowerCase();
    const matchesEntityType = !entityType.value || 
                            entityTypeValue === entityType.value.toLowerCase();

    // Frequency type filter - handle null/undefined cases
    const frequencyTypeValue = customer.frequency_type?.toLowerCase();
    const matchesFrequencyType = !frequencyType.value || 
                               frequencyTypeValue === frequencyType.value.toLowerCase();

    return matchesSearch && matchesEntityType && matchesFrequencyType;
  });
});

console.log('Entity Type Filter:', entityType.value);
console.log('Frequency Type Filter:', frequencyType.value);
console.log('Filtered Customers:', filteredCustomers.value);
</script>