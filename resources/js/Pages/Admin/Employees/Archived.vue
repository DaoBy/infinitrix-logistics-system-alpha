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

        <!-- Search Bar -->
        <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
          <div class="w-full sm:w-96">
            <SearchInput 
              v-model="search" 
              placeholder="Search disabled employees..." 
              class="w-full"
            />
          </div>
          
          <!-- Filters and Info Row -->
          <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full sm:w-auto">
            <!-- Dropdown Filters -->
            <div class="flex flex-wrap gap-2">
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
            </div>
            
            <!-- Info Counter -->
            <div class="text-sm text-gray-500 bg-blue-50 px-3 py-1 rounded border border-blue-100 whitespace-nowrap">
              📋 Showing {{ employees.data?.length || 0 }} of {{ employees.total }} employees
              <span v-if="employees.data && employees.data.length < employees.total" class="ml-1">
                (Page {{ employees.current_page }} of {{ employees.last_page }})
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
                        {{ row.name || 'N/A' }}
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
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                        :class="getRoleClass(row.role)">
                    {{ row.role ? row.role.charAt(0).toUpperCase() + row.role.slice(1) : 'N/A' }}
                  </span>
                </template>
                <template #region="{ row }">
                  <span class="text-gray-700">{{ row.employee_profile?.region?.name || 'Not assigned' }}</span>
                </template>
                <template #archived_at="{ row }">
                  <span class="text-gray-700">{{ row.archived_at ? new Date(row.archived_at).toLocaleDateString() : 'N/A' }}</span>
                </template>
                <template #status="{ row }">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
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
                    </div>
                  </div>
                </template>
              </DataTable>

              <!-- Pagination Component -->
              <div class="mt-4">
                <Pagination 
                  :pagination="employees" 
                  @page-changed="handlePageChange"
                  :per-page="10"
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
          Are you sure you want to permanently delete 
          <strong>{{ employeeToDelete?.name }}</strong>? 
          This action cannot be undone.
        </p>
        <div class="mt-4 flex justify-end space-x-3">
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
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
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
import axios from 'axios';

const props = defineProps({ 
  employees: Object, 
  status: String, 
  success: String, 
  error: String 
});

// State
const showDeleteModal = ref(false);
const employeeToDelete = ref(null);
const isDeleting = ref(false);
const search = ref(props.employees.filters?.search || '');
const roleFilter = ref(props.employees.filters?.role || '');
const regionFilter = ref(props.employees.filters?.region || '');
const sortField = ref(props.employees.filters?.sort_field || 'archived_at');
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

const columns = [
  { field: 'name', header: 'Employee Name', sortable: true },
  { field: 'email', header: 'Email', sortable: true },
  { field: 'role', header: 'Role', sortable: true },
  { field: 'region', header: 'Region/Branch', sortable: false },
  { field: 'archived_at', header: 'Disabled On', sortable: true },
  { field: 'status', header: 'Status', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false }
];

// Methods
const getInitials = (name) => {
  if (!name) return 'EM';
  const names = name.split(' ');
  if (names.length >= 2) {
    return `${names[0].charAt(0)}${names[1].charAt(0)}`.toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
};

const getRoleClass = (role) => {
  switch (role) {
    case 'admin': return 'bg-purple-100 text-purple-800';
    case 'staff': return 'bg-blue-100 text-blue-800';
    case 'driver': return 'bg-green-100 text-green-800';
    case 'collector': return 'bg-yellow-100 text-yellow-800';
    default: return 'bg-gray-100 text-gray-800';
  }
};

// Fixed handleSort function to properly handle the sort event
function handleSort(sortParams) {
  sortField.value = sortParams.field;
  sortDirection.value = sortParams.direction;
}

function handlePageChange(page) {
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
}

function confirmDeleteEmployee(employee) {
  employeeToDelete.value = employee;
  showDeleteModal.value = true;
}

function closeDeleteModal() {
  showDeleteModal.value = false;
  setTimeout(() => { 
    employeeToDelete.value = null; 
    isDeleting.value = false; 
  }, 300);
}

function handleDelete() {
  if (!employeeToDelete.value) return;
  
  isDeleting.value = true;
  router.delete(route('admin.employees.destroy', employeeToDelete.value.id), {}, {
    preserveScroll: true,
    onSuccess: () => router.reload({ only: ['employees'] }),
    onError: () => alert('Failed to delete employee'),
    onFinish: () => closeDeleteModal()
  });
}

function restoreEmployee(employee) {
  router.put(route('admin.employees.restore', employee.id), {}, {
    preserveScroll: true,
    onSuccess: () => router.reload({ only: ['employees'] })
  });
}

function viewActive() {
  router.get(route('admin.employees.index'));
}

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