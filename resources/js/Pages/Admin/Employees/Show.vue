<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">Employee Details</h2>
          <p class="mt-1 text-sm text-gray-500">
            View and manage employee information and profile
          </p>
        </div>

        <!-- Right: Buttons -->
        <div class="flex gap-2">
          <SecondaryButton @click="$inertia.visit(route('admin.employees.index'))">
            Back to List
          </SecondaryButton>
          <PrimaryButton @click="editEmployee">
            Edit Profile
          </PrimaryButton>
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

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <!-- Main Information Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6 max-w-4xl mx-auto">
              <!-- Basic Information Card -->
              <div class="bg-white border border-gray-200 rounded-lg p-4">
                <h3 class="text-lg font-medium text-gray-900 mb-3">Basic Information</h3>
                <div class="space-y-2">
                  <div class="flex justify-between items-center py-1">
                    <span class="text-sm font-medium text-gray-500 min-w-[100px]">Employee ID</span>
                    <span class="text-sm text-gray-900 font-semibold text-right">
                      {{ employee.employee_profile?.employee_id || 'Not specified' }}
                    </span>
                  </div>
                  <div class="flex justify-between items-center py-1">
                    <span class="text-sm font-medium text-gray-500 min-w-[100px]">Full Name</span>
                    <span class="text-sm text-gray-900 text-right">{{ capitalizeWords(employee.name) }}</span>
                  </div>
                  <div class="flex justify-between items-center py-1">
                    <span class="text-sm font-medium text-gray-500 min-w-[100px]">Email</span>
                    <span class="text-sm text-gray-900 text-right">{{ employee.email }}</span>
                  </div>
                  <div class="flex justify-between items-center py-1">
                    <span class="text-sm font-medium text-gray-500 min-w-[100px]">Status</span>
                    <span :class="employee.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" 
                          class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                      {{ employee.is_active ? 'Active' : 'Inactive' }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Contact Information Card -->
              <div class="bg-white border border-gray-200 rounded-lg p-4">
                <h3 class="text-lg font-medium text-gray-900 mb-3">Contact Information</h3>
                <div class="space-y-2">
                  <div class="flex justify-between items-center py-1">
                    <span class="text-sm font-medium text-gray-500 min-w-[100px]">Phone</span>
                    <span class="text-sm text-gray-900 text-right">
                      {{ employee.employee_profile?.phone || 'Not specified' }}
                    </span>
                  </div>
                  <div class="flex justify-between items-center py-1">
                    <span class="text-sm font-medium text-gray-500 min-w-[100px]">Mobile</span>
                    <span class="text-sm text-gray-900 text-right">
                      {{ employee.employee_profile?.mobile || 'Not specified' }}
                    </span>
                  </div>
                  <div class="flex justify-between items-center py-1">
                    <span class="text-sm font-medium text-gray-500 min-w-[100px]">Address</span>
                    <span class="text-sm text-gray-900 text-right break-words whitespace-normal">
                      {{ formatAddress(employee.employee_profile) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Additional Information Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 max-w-4xl mx-auto">
              <!-- Employment Details Card -->
              <div class="bg-white border border-gray-200 rounded-lg p-4">
                <h3 class="text-lg font-medium text-gray-900 mb-3">Employment Details</h3>
                <div class="space-y-2">
                  <div class="flex justify-between items-center py-1">
                    <span class="text-sm font-medium text-gray-500 min-w-[100px]">Role</span>
                    <span class="text-sm text-gray-900 text-right capitalize">{{ capitalizeWords(employee.role) }}</span>
                  </div>
                  <div class="flex justify-between items-center py-1">
                    <span class="text-sm font-medium text-gray-500 min-w-[100px]">Assigned Region</span>
                    <span class="text-sm text-gray-900 text-right">
                      {{ employee.employee_profile?.region?.name || 'Not assigned' }}
                    </span>
                  </div>
                  <div class="flex justify-between items-center py-1">
                    <span class="text-sm font-medium text-gray-500 min-w-[100px]">Hire Date</span>
                    <span class="text-sm text-gray-900 text-right">
                      {{ employee.employee_profile?.hire_date ? formatDate(employee.employee_profile.hire_date) : 'Not specified' }}
                    </span>
                  </div>
                  <div v-if="employee.employee_profile?.termination_date" class="flex justify-between items-center py-1">
                    <span class="text-sm font-medium text-gray-500 min-w-[100px]">Termination Date</span>
                    <span class="text-sm text-gray-900 text-right">
                      {{ formatDate(employee.employee_profile.termination_date) }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Notes Card -->
              <div v-if="employee.employee_profile?.notes" class="bg-white border border-gray-200 rounded-lg p-4">
                <h3 class="text-lg font-medium text-gray-900 mb-3">Notes</h3>
                <p class="text-sm text-gray-700 whitespace-pre-line">
                  {{ employee.employee_profile.notes }}
                </p>
              </div>
            </div>
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
import { router } from '@inertiajs/vue3';

const props = defineProps({
  employee: Object,
  status: String,
  success: String,
  error: String,
});

// Helper function to capitalize words
const capitalizeWords = (str) => {
  if (!str) return '';
  return str.replace(/\b\w/g, char => char.toUpperCase());
};

const formatDate = (dateString) => {
  if (!dateString) return 'Not specified';
  try {
    return new Date(dateString).toLocaleDateString();
  } catch (e) {
    return 'Invalid Date';
  }
};

const formatAddress = (profile) => {
  if (!profile) return 'Not specified';
  
  const parts = [
    profile.building_number,
    profile.street,
    profile.barangay,
    profile.city,
    profile.province,
    profile.zip_code
  ].filter(part => part);

  return parts.length > 0 ? parts.join(', ') : 'Not specified';
};

const editEmployee = () => {
  router.get(route('admin.employees.edit', props.employee.id));
};
</script>

<style scoped>
.zoom-content {
  zoom: 0.80;
}

/* Add tighter spacing for desktop */
@media (min-width: 1024px) {
  .zoom-content {
    zoom: 0.90; /* Slightly less zoom on desktop */
  }
}
</style>