<template>
  <div class="p-6">
    <!-- 
      Main Title 
      * This appears at the top of the component
      * You can change the text to match your use case
    -->
    <h2 class="text-xl font-semibold leading-tight text-gray-800 mb-6">Customer Management</h2>
    
    <!-- 
      DataTable Component
      * Displays your customer data in a clean, sortable table
      * Requires columns definition and data props
      * Emits sort events when column headers are clicked
    -->
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
</template>

<script setup>
import { ref } from 'vue';
// Import required components - make sure these exist in your project
import DataTable from '@/Components/DataTable.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';

/*
   Edit niyo lang yung columns para sa data structure
*/

// DEMO DATA - REPLACE THIS WITH YOUR ACTUAL DATA SOURCE
const customers = ref([
  {
    id: 1,
    name: "Skibi Di",
    company_name: "",
    email: "Skibi@example.com",
    mobile: "555-0101",
    customer_category: "Individual",  
    frequency_type: "Regular",      
    total_orders: 5,
    created_at: "2023-01-15"         
  },  {
    id: 1,
    name: "Toilet Toi",
    company_name: "",
    email: "Toilet@example.com",
    mobile: "555-0101",
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

/*
  COLUMNS CONFIGURATION
  * Defines what data appears in each column
  * Properties for each column:
    - field: data property name
    - header: column title
    - sortable: whether column can be sorted
*/
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

/*
  SORTING HANDLER
  * Called when user clicks a column header
  * Toggles between ascending/descending if same column
  * Sets new column with ascending sort if different column
*/
function handleSort(field) {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortField.value = field;
    sortDirection.value = 'asc';
  }
}

/*
  ACTION METHODS - (These are all backend stuff ako na bahala dito. Same with the filtering.)
*/
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
</script>