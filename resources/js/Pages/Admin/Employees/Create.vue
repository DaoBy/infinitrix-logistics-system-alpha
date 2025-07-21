<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-6">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Create New Employee</h2>
        <SecondaryButton @click="showCancelModal = true">
          Back to List
        </SecondaryButton>
      </div>
    </template>

    <div class="px-6">
      <!-- Status Messages -->
      <div v-if="status || success || error" class="mb-6 max-w-7xl mx-auto">
        <div v-if="status" class="p-4 bg-blue-100 text-blue-800 rounded">
          {{ status }}
        </div>
        <div v-if="success" class="p-4 bg-green-100 text-green-800 rounded">
          {{ success }}
        </div>
        <div v-if="error" class="p-4 bg-red-100 text-red-800 rounded">
          {{ error }}
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg max-w-7xl mx-auto">
        <div class="p-6 bg-white border-b border-gray-200">
          <form @submit.prevent="submit">
            <!-- Basic Information Section -->
            <div class="mb-8">
              <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">
                Basic Information
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <InputLabel for="role" value="Role *" />
                  <SelectInput
                    id="role"
                    v-model="form.role"
                    :options="roleOptions"
                    option-value="value"
                    option-label="label"
                    class="mt-1 block w-full"
                    required
                  />
                  <InputError class="mt-2" :message="form.errors.role" />
                </div>

                 <div>
                 <InputLabel for="employee_id" value="Employee ID" />
    <TextInput
      id="employee_id"
      type="text"
      class="mt-1 block w-full bg-gray-100"
      :model-value="getEmployeeIdPreview()"
      readonly
      disabled
    />
                  <InputError class="mt-2" :message="form.errors.employee_id" />
                </div>

                <div>
                  <InputLabel for="name" value="Full Name *" />
                  <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                     required
                    autofocus
                    placeholder="Employee Name"
                  />
                  <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                  <InputLabel for="email" value="Email *" />
                  <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    placeholder="employee@example.com"

                  />
                  <InputError class="mt-2" :message="form.errors.email" />
                </div>
              </div>
            </div>

            <!-- Login Credentials Section -->
            <div class="mb-8">
              <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">
                Login Credentials
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <InputLabel for="password" value="Password *" />
                  <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    placeholder="Enter Password"
                  />
                  <InputError class="mt-2" :message="form.errors.password" />
                </div>

                <div>
                  <InputLabel for="password_confirmation" value="Confirm Password *" />
                  <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    placeholder="Confirm Password"
                  />
                  <InputError class="mt-2" :message="form.errors.password_confirmation" />
                </div>
              </div>
            </div>

            <!-- Contact Information Section -->
            <div class="mb-8">
              <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">
                Contact Information
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <InputLabel for="phone" value="Phone (Landline)" />
                  <TextInput
                    id="phone"
                    type="tel"
                    class="mt-1 block w-full"
                    v-model="form.phone"
                    placeholder=" (02) 1234-5678"
                    maxlength="10"
                  />
                  <InputError class="mt-2" :message="form.errors.phone" />
                </div>

                <div>
                  <InputLabel for="mobile" value="Mobile *" />
                  <TextInput
                    id="mobile"
                    type="tel"
                    class="mt-1 block w-full"
                    v-model="form.mobile"
                    required
                    placeholder=" 0917-123-4567"
                    maxlength="11"
                  />
                  <InputError class="mt-2" :message="form.errors.mobile" />
                </div>
              </div>
            </div>

            <!-- Address Information Section -->
            <div class="mb-8">
              <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">
                Address Information
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                  <InputLabel for="building_number" value="Building Number" />
                  <TextInput
                    id="building_number"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.building_number"
                    placeholder=" Building/Unit No."
                  maxlength="10"
                  />
                  <InputError class="mt-2" :message="form.errors.building_number" />
                </div>

                <div>
                  <InputLabel for="street" value="Street" />
                  <TextInput
                    id="street"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.street"
                    placeholder=" Street Name"
                    maxlength="50"
                  />
                  <InputError class="mt-2" :message="form.errors.street" />
                </div>

                <div>
                  <InputLabel for="barangay" value="Barangay" />
                  <TextInput
                    id="barangay"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.barangay"
                    placeholder=" Barangay/District"
                    maxlength="50"
                  />
                  <InputError class="mt-2" :message="form.errors.barangay" />
                </div>

                <div>
                  <InputLabel for="city" value="City/Municipality" />
                  <TextInput
                    id="city"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.city"
                    placeholder=" City or Municipality"
                    maxlength="50"
                  />
                  <InputError class="mt-2" :message="form.errors.city" />
                </div>

                <div>
                  <InputLabel for="province" value="Province" />
                  <TextInput
                    id="province"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.province"
                    placeholder=" Province Name"
                    maxlength="50"
                  />
                  <InputError class="mt-2" :message="form.errors.province" />
                </div>

                <div>
                  <InputLabel for="zip_code" value="ZIP Code" />
                  <TextInput
                    id="zip_code"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.zip_code"
                    maxlength="4"
                    placeholder=" e.g., 1234"
                    pattern="^\d{4}$"
                  />
                  <InputError class="mt-2" :message="form.errors.zip_code" />
                </div>
              </div>
            </div>

            <!-- Employment Information Section -->
            <div class="mb-8">
              <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">
                Employment Information
              </h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <InputLabel for="hire_date" value="Hire Date" />
                  <TextInput
                    id="hire_date"
                    type="date"
                    class="mt-1 block w-full"
                    v-model="form.hire_date"
                  />
                  <InputError class="mt-2" :message="form.errors.hire_date" />
                </div>

                <div>
                  <InputLabel for="region_id" value="Assigned Region/Branch" />
                  <SelectInput
                    id="region_id"
                    v-model="form.region_id"
                    :options="branches"
                    option-value="value"
                    option-label="label"
                    class="mt-1 block w-full"
                  />
                  <InputError class="mt-2" :message="form.errors.region_id" />
                </div>
              </div>
            </div>

            <div class="mt-6 flex justify-end space-x-4">
              <SecondaryButton type="button" @click="showCancelModal = true">
                Cancel
              </SecondaryButton>
              <PrimaryButton type="submit" :disabled="form.processing">
                Create Employee
              </PrimaryButton>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Cancel Confirmation Modal -->
    <Modal :show="showCancelModal" @close="showCancelModal = false">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">
          Are you sure you want to cancel?
        </h2>
        <p class="mt-1 text-sm text-gray-600">
          All entered information will be lost.
        </p>
        <div class="mt-6 flex justify-end space-x-4">
          <SecondaryButton @click="showCancelModal = false">
            Continue Creating
          </SecondaryButton>
          <DangerButton @click="handleDiscard">
            Discard
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
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';


const roleAbbreviations = {
  admin: 'ADM',
  staff: 'STA',
  driver: 'DRI',
  collector: 'COL'
};

const props = defineProps({
  status: String,
  success: String,
  error: String,
  regions: Array,
});

const showCancelModal = ref(false);
const branches = ref(props.regions.map(region => ({
  value: region.id,
  label: region.name
})));


const getEmployeeIdPreview = () => {
  const roleAbbr = roleAbbreviations[form.role] || 'EMP';
  return `EMP-${roleAbbr}-XXXX (will be generated)`;
};

const roleOptions = [
  { value: 'admin', label: 'Admin' },
  { value: 'staff', label: 'Staff' },
  { value: 'driver', label: 'Driver' },
  { value: 'collector', label: 'Collector' }
];

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'staff',
  phone: '',
  mobile: '',
  building_number: '',
  street: '',
  barangay: '',
  city: '',
  province: '',
  zip_code: '',
  hire_date: '',
  region_id: '',
});

const submit = () => {
  form.post(route('admin.employees.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
    },
    onError: (errors) => {
      console.error('Form submission errors:', errors);
    }
  });
};

watch(() => form.role, () => {
});

const handleDiscard = () => {
  showCancelModal.value = false;
  form.reset();
  router.visit(route('admin.employees.index'));
};
</script>