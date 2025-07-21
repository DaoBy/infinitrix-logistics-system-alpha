<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-6">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Employee Details</h2>
        <div class="flex space-x-2">
          <Link :href="route('admin.employees.edit', employee.id)" as="button">
            <PrimaryButton>Edit Profile</PrimaryButton>
          </Link>
          <Link :href="route('admin.employees.index')" as="button">
            <SecondaryButton>Back to List</SecondaryButton>
          </Link>
        </div>
      </div>
    </template>

    <div class="px-6">
      <!-- Status Messages -->
      <div v-if="$page.props.status || $page.props.success || $page.props.error" class="mb-6 max-w-7xl mx-auto">
        <div v-if="$page.props.status" class="p-4 bg-blue-100 text-blue-800 rounded">
          {{ $page.props.status }}
        </div>
        <div v-if="$page.props.success" class="p-4 bg-green-100 text-green-800 rounded">
          {{ $page.props.success }}
        </div>
        <div v-if="$page.props.error" class="p-4 bg-red-100 text-red-800 rounded">
          {{ $page.props.error }}
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg max-w-7xl mx-auto">
        <div class="p-6 bg-white border-b border-gray-200">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Basic Information Section -->
            <div class="space-y-6">
              <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">
                  Basic Information
                </h3>
                <div class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-500">Employee ID</label>
                    <p class="mt-1 text-sm text-gray-900">
                      {{ employee.employee_profile?.employee_id || 'Not specified' }}
                    </p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-500">Full Name</label>
                    <p class="mt-1 text-sm text-gray-900">{{ employee.name }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-500">Email</label>
                    <p class="mt-1 text-sm text-gray-900">{{ employee.email }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-500">Status</label>
                    <span :class="employee.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" 
                          class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                      {{ employee.is_active ? 'Active' : 'Inactive' }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Contact Information Section -->
              <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">
                  Contact Information
                </h3>
                <div class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-500">Phone</label>
                    <p class="mt-1 text-sm text-gray-900">
                      {{ employee.employee_profile?.phone || 'Not specified' }}
                    </p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-500">Mobile</label>
                    <p class="mt-1 text-sm text-gray-900">
                      {{ employee.employee_profile?.mobile || 'Not specified' }}
                    </p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-500">Address</label>
                    <p class="mt-1 text-sm text-gray-900">
                      {{ formatAddress(employee.employee_profile) }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Employment Information Section -->
            <div class="space-y-6">
              <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">
                  Employment Details
                </h3>
                <div class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-500">Role</label>
                    <p class="mt-1 text-sm text-gray-900 capitalize">{{ employee.role }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-500">Assigned Region/Branch</label>
                    <p class="mt-1 text-sm text-gray-900">
                      {{ employee.employee_profile?.region?.name || 'Not assigned' }}
                    </p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-500">Hire Date</label>
                    <p class="mt-1 text-sm text-gray-900">
                      {{ employee.employee_profile?.hire_date ? formatDate(employee.employee_profile.hire_date) : 'Not specified' }}
                    </p>
                  </div>
                  <div v-if="employee.employee_profile?.termination_date">
                    <label class="block text-sm font-medium text-gray-500">Termination Date</label>
                    <p class="mt-1 text-sm text-gray-900">
                      {{ formatDate(employee.employee_profile.termination_date) }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Notes Section -->
              <div v-if="employee.employee_profile?.notes">
                <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">
                  Notes
                </h3>
                <p class="mt-2 text-sm text-gray-700 whitespace-pre-line">
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
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  employee: Object,
});

const formatDate = (dateString) => {
  const options = { year: 'numeric', month: 'long', day: 'numeric' };
  return new Date(dateString).toLocaleDateString(undefined, options);
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
</script>