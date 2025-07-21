<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import NavLink from '@/Components/NavLink.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DataTable from '@/Components/DataTable.vue';

/*
   Edit niyo lang yung columns para sa data structure
*/

// DEMO DATA - REPLACE THIS WITH YOUR ACTUAL DATA SOURCE
const customers = ref([
  {
    id: 1,
    name: "Blase Mariano",
    company_name: "",
    package_code: "100001234",
    issue: "No one showed up",
    description: "No one showed up for the scheduled payment collection",
    status: "In Progress",      
    created_at: "2025-05-26"         
  },  {
    id: 1,
    name: "Jose Pasqual",
    company_name: "",
    package_code: "100001235",
    issue: "Missing Payment Proof",
    description: "Missing payment proof for the last transaction",  
    status: "In Progress", 
    created_at: "2025-05-26"         
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
  { field: 'package_code', header: 'Package Code', sortable: true },
  { field: 'issue', header: 'Issue', sortable: false },
  { field: 'description', header: 'Description/Notes', sortable: true },
  { field: 'status', header: 'Status', sortable: true },
  { field: 'created_at', header: 'Date Reported', sortable: true },
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

<template>
  <Head title="Collector Payment Status" />
  <EmployeeLayout>
    <div class="p-6">
    <!-- 
      Main Title 
      * This appears at the top of the component
      * You can change the text to match your use case
    -->
    <h2 class="text-xl font-semibold leading-tight text-gray-800 mb-6">Report and Issue</h2>
    
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
          <PrimaryButton @click="editCustomer(row.id)">Resolve</PrimaryButton>
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
  </EmployeeLayout>
</template>