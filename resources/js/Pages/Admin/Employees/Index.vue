<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-6">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Employee Management</h2>
        <div class="flex space-x-2">
          <PrimaryButton @click="createEmployee">Add New Employee</PrimaryButton>
          <SecondaryButton @click="viewArchived">View Disabled Accounts</SecondaryButton>
        </div>
      </div>
    </template>

    <div class="px-6 sm:px-8">
      <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 w-full mx-auto">
        <SearchInput 
          v-model="search" 
          placeholder="Search employees..." 
          class="w-full md:w-72"
        />
        <div class="flex flex-wrap gap-2 w-full md:w-auto">
          <SelectInput 
            v-model="roleFilter" 
            :options="roleOptions" 
            option-value="value"
            option-label="text"
            placeholder="All Roles"
            class="w-full md:w-48"
          />
          <SelectInput 
            v-model="regionFilter" 
            :options="regionOptions" 
            option-value="value"
            option-label="text"
            placeholder="All Regions"
            class="w-full md:w-48"
          />
        </div>
      </div>

      <div class="justify-center flex items-center">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-center">
          <div class="p-5 bg-white border-b border-gray-200">
            <DataTable
              :columns="columns"
              :data="employees.data"
              :sort-field="sortField"
              :sort-direction="sortDirection"
              @sort="handleSort"
              class="w-full"
            >
              <template #name="{ row }">
                <span class="truncate block">{{ row.name || 'N/A' }}</span>
              </template>
              <template #email="{ row }">
                <span class="truncate block">{{ row.email || 'N/A' }}</span>
              </template>
              <template #role="{ row }">
                {{ row.role ? row.role.charAt(0).toUpperCase() + row.role.slice(1) : 'N/A' }}
              </template>
              <template #employee_id="{ row }">
                <span v-if="row.employee_profile?.employee_id">
                  {{ row.employee_profile.employee_id }}
                </span>
                <span v-else class="text-gray-400">
                  Not assigned
                </span>
              </template>
              <template #region="{ row }">
                {{ row.employee_profile?.region?.name || 'Not assigned' }}
              </template>
              <template #status="{ row }">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                      :class="row.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                  {{ row.is_active ? 'Active' : 'Inactive' }}
                </span>
              </template>
              <template #actions="{ row }">
                <div class="flex space-x-2">
                  <SecondaryButton @click="viewEmployee(row.id)">View</SecondaryButton>
                  <PrimaryButton @click="editEmployee(row.id)">Edit</PrimaryButton>
                  <DangerButton @click="confirmArchiveEmployee(row)">Disable</DangerButton>
                </div>
              </template>
            </DataTable>

            <div v-if="employees.links?.length > 3" class="mt-4 flex justify-center">
              <div class="flex flex-wrap -mb-1">
                <template v-for="(link, index) in employees.links" :key="index">
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

    <Modal :show="showArchiveModal" @close="closeArchiveModal">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">Disable Employee?</h2>
        <p class="mt-1 text-sm text-gray-600">
          Are you sure you want to Disable <strong>{{ employeeToArchive?.name }}</strong>?
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
  </EmployeeLayout>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import axios from 'axios';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SearchInput from '@/Components/SearchInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({ 
  employees: Object, 
  status: String, 
  success: String, 
  error: String 
});

// State
const showArchiveModal = ref(false);
const employeeToArchive = ref(null);
const isArchiving = ref(false);
const search = ref(props.employees.filters?.search || '');
const roleFilter = ref(props.employees.filters?.role || '');
const regionFilter = ref(props.employees.filters?.region || '');
const sortField = ref(props.employees.filters?.sort_field || 'created_at');
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
  { field: 'name', header: 'Name', sortable: true },
  { field: 'email', header: 'Email', sortable: true },
  { field: 'role', header: 'Role', sortable: true, formatter: v => v?.charAt(0).toUpperCase() + v?.slice(1) || 'N/A' },
  { field: 'employee_profile', header: 'Employee ID', sortable: true, formatter: p => p?.employee_id || 'N/A' },
  { field: 'employee_profile', header: 'Region/Branch', sortable: false, formatter: p => p?.region?.name || 'Not assigned' },
  { field: 'actions', header: 'Actions', sortable: false }
];


// Methods
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

const handleSort = (field) => {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortField.value = field;
    sortDirection.value = 'asc';
  }
};

const confirmArchiveEmployee = (employee) => {
  employeeToArchive.value = employee;
  showArchiveModal.value = true;
};

const closeArchiveModal = () => {
  showArchiveModal.value = false;
  setTimeout(() => { 
    employeeToArchive.value = null; 
    isArchiving.value = false; 
  }, 300);
};

const handleArchive = async () => {
  if (!employeeToArchive.value) return;
  isArchiving.value = true;
  try {
    await router.put(route('admin.employees.archive', employeeToArchive.value.id), {}, { 
      preserveScroll: true 
    });
    closeArchiveModal();
    router.reload({ only: ['employees'] });
  } catch {
    closeArchiveModal();
    alert('Failed to archive employee. Please try again.');
  } finally {
    isArchiving.value = false;
  }
};

// Navigation
const createEmployee = () => router.get(route('admin.employees.create'));
const viewEmployee = (id) => router.get(route('admin.employees.show', id));
const editEmployee = (id) => router.get(route('admin.employees.edit', id));
const viewArchived = () => router.get(route('admin.employees.archived'));

// Watchers
watch([search, roleFilter, regionFilter, sortField, sortDirection], () => {
  router.get(route('admin.employees.index'), {
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
:deep(.datatable-cell) {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>