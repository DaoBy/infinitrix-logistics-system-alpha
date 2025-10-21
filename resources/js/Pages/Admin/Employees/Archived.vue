<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">Disabled Employees</h2>
          <p class="mt-1 text-sm text-gray-500">
            Manage disabled employee accounts
          </p>
        </div>

        <!-- Right: Buttons -->
        <div class="flex gap-2">
          <SecondaryButton @click="viewActive">View Active Employees</SecondaryButton>
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

        <!-- Search and Filters -->
        <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
          <div class="w-full sm:w-96">
            <SearchInput 
              v-model="search" 
              placeholder="Search disabled employees by name, email, or employee ID..." 
              class="w-full"
            />
          </div>
          <div class="flex items-center gap-3">
            <SelectInput 
              v-model="roleFilter" 
              :options="roleOptions" 
              option-value="value"
              option-label="text"
              placeholder="All Roles"
              class="w-full sm:w-48"
            />
            <SelectInput 
              v-model="regionFilter" 
              :options="regionOptions" 
              option-value="value"
              option-label="text"
              placeholder="All Regions"
              class="w-full sm:w-48"
            />
            <div class="text-sm text-gray-500 bg-blue-50 px-3 py-1 rounded border border-blue-100 whitespace-nowrap">
              📋 Showing {{ employees.data?.length || 0 }} disabled {{ employees.data?.length === 1 ? 'employee' : 'employees' }}
              <span v-if="employees.data && employees.data.length < employees.total" class="ml-1">
                (Page {{ employees.current_page }} of {{ employees.last_page }})
              </span>
            </div>
          </div>
        </div>

        <!-- Data Table Container -->
        <div class="justify-center flex items-center">
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-full max-w-[95vw]">
            <div class="p-4 bg-white border-b border-gray-200">
              <DataTable
                :columns="columns"
                :data="employees.data || []"
                :sort-field="sortField"
                :sort-direction="sortDirection"
                @sort="handleSort"
                class="w-full"
              >
                <template #name="{ row }">
                  <div class="flex items-center">
                    <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                      <span class="text-sm font-medium text-gray-700">
                        {{ getInitials(row.name) }}
                      </span>
                    </div>
                    <div>
                      <span class="font-medium text-gray-900 block">
                        {{ capitalizeWords(row.name) || 'N/A' }}
                      </span>
                      <span v-if="row.employee_profile?.employee_id" class="text-xs text-gray-500 block">
                        ID: {{ row.employee_profile.employee_id }}
                      </span>
                    </div>
                  </div>
                </template>
                <template #email="{ row }">
                  <span class="text-gray-700">{{ row.email || 'No email' }}</span>
                </template>
                <template #role="{ row }">
                  <span 
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full capitalize"
                    :class="getRoleClass(row.role)"
                  >
                    {{ row.role || 'N/A' }}
                  </span>
                </template>
                <template #region.name="{ row }">
                  <div class="flex items-center">
                    <div 
                      v-if="row.employee_profile?.region && row.employee_profile.region.color_hex"
                      class="w-3 h-3 rounded-full mr-2 border border-gray-300" 
                      :style="{ backgroundColor: row.employee_profile.region.color_hex }"
                    ></div>
                    <span class="text-gray-700">{{ row.employee_profile?.region?.name || 'Not assigned' }}</span>
                  </div>
                </template>
                <template #archived_at="{ row }">
                  <span class="text-gray-600">{{ row.employee_profile?.archived_at ? formatDate(row.employee_profile.archived_at) : 'N/A' }}</span>
                </template>
                <template #status="{ row }">
                  <span 
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800"
                  >
                    Disabled
                  </span>
                </template>
                <template #actions="{ row }">
                  <div class="flex space-x-2">
                    <SecondaryButton @click="restoreEmployee(row)" class="text-xs py-1 px-2">Restore</SecondaryButton>
                    <DangerButton @click="confirmDeleteEmployee(row)" class="text-xs py-1 px-2">Delete</DangerButton>
                  </div>
                </template>
                
                <!-- Empty State Slot -->
                <template #empty>
                  <div class="text-center py-8">
                    <div class="bg-gray-50 rounded-lg p-6 max-w-md mx-auto">
                      <UserGroupIcon class="h-10 w-10 text-gray-400 mx-auto mb-3" />
                      <h3 class="text-lg font-medium text-gray-900 mb-2">No disabled employees found</h3>
                      <p class="text-gray-500 mb-3">
                        {{ search ? 'Try adjusting your search terms' : 'All employee accounts are currently active' }}
                      </p>
                      <SecondaryButton @click="viewActive">View Active Employees</SecondaryButton>
                    </div>
                  </div>
                </template>
              </DataTable>

              <!-- Pagination Component -->
              <div v-if="employees.links?.length > 3" class="mt-4">
                <Pagination 
                  :pagination="employees" 
                  @page-changed="handlePageChange" 
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <Modal :show="showDeleteModal" @close="closeDeleteModal">
      <div class="p-5">
        <h2 class="text-lg font-medium text-gray-900">Delete Employee Permanently?</h2>
        <p class="mt-1 text-sm text-gray-600">
          Are you sure you want to permanently delete <strong>{{ employeeToDelete?.name }}</strong>? 
          This action cannot be undone.
        </p>
        <div class="mt-4 flex justify-end space-x-3">
          <SecondaryButton @click="closeDeleteModal">Cancel</SecondaryButton>
          <DangerButton 
            @click="deleteEmployee" 
            :disabled="isProcessing"
          >
            <span v-if="isProcessing">Processing...</span>
            <span v-else>Delete Permanently</span>
          </DangerButton>
        </div>
      </div>
    </Modal>
  </EmployeeLayout>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SearchInput from '@/Components/SearchInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import { UserGroupIcon } from '@heroicons/vue/24/outline';

const props = defineProps({ 
  employees: Object, 
  status: String, 
  success: String, 
  error: String 
});

// State
const showDeleteModal = ref(false);
const employeeToDelete = ref(null);
const isProcessing = ref(false);
const search = ref(props.employees.filters?.search || '');
const roleFilter = ref(props.employees.filters?.role || '');
const regionFilter = ref(props.employees.filters?.region || '');
const sortField = ref(props.employees.filters?.sort_field || 'employee_profile.archived_at');
const sortDirection = ref(props.employees.filters?.sort_direction || 'desc');
const regionOptions = ref([{ value: '', text: 'All Regions' }]);

// Constants
const roleOptions = [
  { value: '', text: 'All Roles' },
  { value: 'admin', text: 'Admin' },
  { value: 'staff', text: 'Staff' },
  { value: 'driver', text: 'Driver' },
  { value: 'collector', text: 'Collector' }
];

const roleClasses = {
  admin: 'bg-purple-100 text-purple-800',
  staff: 'bg-blue-100 text-blue-800',
  driver: 'bg-green-100 text-green-800',
  collector: 'bg-yellow-100 text-yellow-800'
};

const columns = [
  { field: 'name', header: 'Employee Name', sortable: true },
  { field: 'email', header: 'Email', sortable: true },
  { field: 'role', header: 'Role', sortable: true },
  { field: 'region.name', header: 'Region', sortable: true },
  { field: 'archived_at', header: 'Disabled On', sortable: true },
  { field: 'status', header: 'Status', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false }
];

// Computed
const filteredEmployees = computed(() => {
  const employeesData = props.employees.data || [];
  
  return employeesData.filter(employee => {
    const matchesSearch = search.value === '' || 
      employee.name?.toLowerCase().includes(search.value.toLowerCase()) ||
      employee.email?.toLowerCase().includes(search.value.toLowerCase()) ||
      employee.employee_profile?.employee_id?.toLowerCase().includes(search.value.toLowerCase());
    
    const matchesRole = !roleFilter.value || employee.role === roleFilter.value;
    const matchesRegion = !regionFilter.value || 
      employee.employee_profile?.region?.id?.toString() === regionFilter.value;
    
    return matchesSearch && matchesRole && matchesRegion;
  }).sort((a, b) => {
    const modifier = sortDirection.value === 'asc' ? 1 : -1;
    
    let aValue = a[sortField.value];
    let bValue = b[sortField.value];
    
    // Handle nested fields for region name
    if (sortField.value === 'region.name') {
      aValue = a.employee_profile?.region?.name;
      bValue = b.employee_profile?.region?.name;
    }
    
    // Handle archived_at field
    if (sortField.value === 'archived_at') {
      aValue = a.employee_profile?.archived_at;
      bValue = b.employee_profile?.archived_at;
    }
    
    // Handle null/undefined values
    if (aValue == null) aValue = '';
    if (bValue == null) bValue = '';
    
    // Convert to string for case-insensitive comparison
    aValue = String(aValue).toLowerCase();
    bValue = String(bValue).toLowerCase();

    if (aValue < bValue) return -1 * modifier;
    if (aValue > bValue) return 1 * modifier;
    return 0;
  });
});

// Methods
const getInitials = (name) => {
  if (!name) return 'EM';
  const names = name.split(' ');
  if (names.length >= 2) {
    return `${names[0].charAt(0)}${names[1].charAt(0)}`.toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
};

const capitalizeWords = (str) => {
  if (!str) return '';
  return str.replace(/\b\w/g, char => char.toUpperCase());
};

const getRoleClass = (role) => {
  return roleClasses[role] || 'bg-gray-100 text-gray-800';
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  try {
    return new Date(dateString).toLocaleDateString();
  } catch (e) {
    return 'Invalid Date';
  }
};

const handleSort = (sortParams) => {
  sortField.value = sortParams.field;
  sortDirection.value = sortParams.direction;
};

const handlePageChange = (page) => {
  router.get(route('admin.employees.archived'), { 
    page: page,
    search: search.value,
    role: roleFilter.value,
    region: regionFilter.value,
    sort_field: sortField.value,
    sort_direction: sortDirection.value
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
};

const confirmDeleteEmployee = (employee) => {
  employeeToDelete.value = employee;
  showDeleteModal.value = true;
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  employeeToDelete.value = null;
  isProcessing.value = false;
};

const deleteEmployee = () => {
  if (!employeeToDelete.value) return;
  
  isProcessing.value = true;
  router.delete(route('admin.employees.destroy', employeeToDelete.value.id), {}, {
    preserveScroll: true,
    onSuccess: () => router.reload({ only: ['employees'] }),
    onError: () => alert('Failed to delete employee'),
    onFinish: () => closeDeleteModal()
  });
};

const restoreEmployee = (employee) => {
  router.put(route('admin.employees.restore', employee.id), {}, {
    preserveScroll: true,
    onSuccess: () => router.reload({ only: ['employees'] })
  });
};

const viewActive = () => router.get(route('admin.employees.index'));

const fetchRegions = async () => {
  try {
    const { data } = await axios.get('/api/delivery/regions');
    const regionsData = Array.isArray(data) ? data : data.data || data.regions || [];
    regionOptions.value = [
      { value: '', text: 'All Regions' },
      ...regionsData.map(r => ({ value: String(r.id), text: r.name }))
    ];
  } catch (error) {
    console.error('Failed to fetch regions:', error);
  }
};

// Watchers
watch([search, roleFilter, regionFilter, sortField, sortDirection], () => {
  router.get(route('admin.employees.archived'), {
    search: search.value,
    role: roleFilter.value,
    region: regionFilter.value,
    sort_field: sortField.value,
    sort_direction: sortDirection.value,
    page: 1
  }, { 
    preserveState: true, 
    replace: true 
  });
}, { deep: true });

// Lifecycle
onMounted(fetchRegions);
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

/* Style for disabled disable button */
:deep(.datatable-table .btn:disabled) {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>