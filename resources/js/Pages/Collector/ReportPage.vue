<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Customer Management</h2>
        <div class="flex space-x-2">
          <PrimaryButton @click="createCustomer">Add New Customer</PrimaryButton>
          <SecondaryButton @click="viewArchived">View Archived</SecondaryButton>
        </div>
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
          <SecondaryButton @click="openPaymentModal(row.transactionId)" class="bg-green">Confirm Payment</SecondaryButton>
          <PrimaryButton @click="editCustomer(row.id)">View</PrimaryButton>
          <DangerButton @click="openReportModal(row.transactionId)">
            !Report Issue
          </DangerButton>
        </div>
      </template>
    </DataTable>

    <Modal :show="showReportModal" @close="showReportModal = false" maxWidth="lg">
                  <div class="p-6">
                    <h3 class="text-lg font-bold mb-4 ">Report an Issue</h3>
                    <p class="text-gray-600 mb-6">Check all that apply</p>

                    <div class="space-y-2 mb-4">
                      <div class="flex items-center space-x-2">
                        <Checkbox value="Option 1" v-model="multipleCheckboxes" class="shadow-black/50"/>
                        <InputLabel>Unavailable</InputLabel>
                      </div>
                      <div class="flex items-center space-x-2">
                        <Checkbox value="Option 2" v-model="multipleCheckboxes" class="shadow-black/50"/>
                        <InputLabel>Missing Payment Proof</InputLabel>
                      </div>
                       <div class="flex items-center space-x-2">
                        <Checkbox value="Option 3" v-model="multipleCheckboxes" class="shadow-black/50"/>
                        <InputLabel>Wrong Transaction Information</InputLabel>
                      </div>

                      <div class="space-y-2 ">
                        <h3 class="text-lg font-medium">Additional Notes</h3>
                        <textarea
                          v-model="textValue"
                          placeholder="Describe the issue in detail..."
                          class="w-full h-32 p-3 bg-gray-100 rounded-md border border-black resize-y focus:outline-none focus:ring-2 focus:ring-blue-500"
                        ></textarea>
                      </div>

                      
                    </div>

                <div class="flex justify-between">
                  <SecondaryButton @click="showReportModal = false">Cancel</SecondaryButton>
                  <PrimaryButton @click="showReportModal = false">Report</PrimaryButton>
                </div>
              </div>
                </Modal>

                <Modal :show="showConfirmModal" @close="showConfirmModal = false" maxWidth="lg">
                  <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Confirm Payments</h3>
                      <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Confirming payment for Transaction ID: {{ selectedTransactionId }}
                      </p>
                    <div class="flex justify-between">
                      <SecondaryButton @click="showConfirmModal = false">Cancel</SecondaryButton>
                      <PrimaryButton @click="showConfirmModal = false">Confirm</PrimaryButton>
                    </div>
                  </div>
                </Modal>
    <!-- 
      Archive Confirmation Modal Pewde niyo toh gamitin if you want. Does need ng Methods to make it work. 
      Check niyo na lang yung sa baba "showArchiveModal" "closeArchiveModal" "archivecustomer"

      * Appears when user clicks "Archive" button
      * Customize the message as needed
    -->
  
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
import { ref } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { computed } from 'vue';

// Modal states
const showConfirmModal = ref(false);
const showReportModal = ref(false);
const selectedTransactionId = ref(null);

// Checkbox and notes state for report
const multipleCheckboxes = ref([]);
const textValue = ref("");

// Open confirm payment modal
const openPaymentModal = (transactionId) => {
  selectedTransactionId.value = transactionId;
  showConfirmModal.value = true;
};

// Open report issue modal
const openReportModal = (transactionId) => {
  selectedTransactionId.value = transactionId;
  showReportModal.value = true;
};

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
    id: 1,
    name: "Blase Mariano",
    company_name: "",
    email: "Blase@example.com",
    mobile: "091973219858",
    customer_category: "Individual",  
    frequency_type: "Regular",      
    total_orders: 5,
    created_at: "2023-01-15"         
  },  {
    id: 1,
    name: "Jose Pasqual",
    company_name: "",
    email: "Jose@example.com",
    mobile: "091973219859",
    customer_category: "Individual",  
    frequency_type: "Occasional",      
    total_orders: 5,
    created_at: "2023-01-15"         
  },
]);

// STATE MANAGEMENT
const sortField = ref('created_at');   // Current column being sorted
const sortDirection = ref('desc');     // Sort direction (asc/desc)
const showArchiveModal = ref(false);   // Controls archive modal visibility
const customerToArchive = ref(null);   // Stores customer being archived
const isProcessing = ref(false);       // Loading state during archive operation

const columns = [
  { field: 'name', header: 'Name', sortable: true },
  { field: 'company_name', header: 'Company', sortable: true },
  { field: 'email', header: 'Email', sortable: true },
  { field: 'mobile', header: 'Mobile', sortable: false },
  { field: 'customer_category', header: 'Category', sortable: true },
  { field: 'frequency_type', header: 'Frequency', sortable: true },
  { field: 'total_orders', header: 'Orders', sortable: true },
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