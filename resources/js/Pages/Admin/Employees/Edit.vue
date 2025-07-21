<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-6">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Edit Employee</h2>
        <SecondaryButton @click="showCancelModal = true">
          Back to List
        </SecondaryButton>
      </div>
    </template>

    <div class="px-6">
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
                 <InputLabel for="employee_id" value="Employee ID" />
    <TextInput
      id="employee_id"
      type="text"
      class="mt-1 block w-full bg-gray-100"
      v-model="form.employee_id"
      readonly
    />
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
                      autofocus
                      placeholder="employee@example.com"
                  />
                  <InputError class="mt-2" :message="form.errors.email" />
                </div>

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

                <div>
                  <InputLabel for="termination_date" value="Termination Date" />
                  <TextInput
                    id="termination_date"
                    type="date"
                    class="mt-1 block w-full"
                    v-model="form.termination_date"
                  />
                  <InputError class="mt-2" :message="form.errors.termination_date" />
                </div>
              </div>
            </div>

            <!-- Notes Section -->
            <div class="mb-8">
              <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">
                Additional Information
              </h3>
              <div>
                <InputLabel for="notes" value="Notes" />
                <textarea
                  id="notes"
                  rows="3"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  v-model="form.notes"
                ></textarea>
                <InputError class="mt-2" :message="form.errors.notes" />
              </div>
            </div>

            <div class="mt-6 flex justify-end space-x-4">
              <SecondaryButton type="button" @click="showCancelModal = true">
                Cancel
              </SecondaryButton>
              <PrimaryButton type="submit" :disabled="form.processing">
                Update Employee
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
          All unsaved changes will be lost.
        </p>
        <div class="mt-6 flex justify-end space-x-4">
          <SecondaryButton @click="showCancelModal = false">
            Continue Editing
          </SecondaryButton>
          <DangerButton @click="handleDiscard">
            Discard Changes
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
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  employee: Object,
  status: String,
});

const showCancelModal = ref(false);
const branches = ref([]);

const roleOptions = [
  { value: 'admin', label: 'Admin' },
  { value: 'staff', label: 'Staff' },
  { value: 'driver', label: 'Driver' },
  { value: 'collector', label: 'Collector' }
];

const form = useForm({
  name: props.employee.name,
  email: props.employee.email,
  role: props.employee.role,
  employee_id: props.employee.employee_profile?.employee_id,
  phone: props.employee.employee_profile?.phone || '',
  mobile: props.employee.employee_profile?.mobile || '',
  building_number: props.employee.employee_profile?.building_number || '',
  street: props.employee.employee_profile?.street || '',
  barangay: props.employee.employee_profile?.barangay || '',
  city: props.employee.employee_profile?.city || '',
  province: props.employee.employee_profile?.province || '',
  zip_code: props.employee.employee_profile?.zip_code || '',
  hire_date: props.employee.employee_profile?.hire_date || '',
  termination_date: props.employee.employee_profile?.termination_date || '',
  region_id: props.employee.employee_profile?.region_id || '',
  notes: props.employee.employee_profile?.notes || '',
});

const fetchRegions = async () => {
  try {
    const response = await axios.get('/api/delivery/regions');
    const regionsData = Array.isArray(response.data) ? response.data :
                      response.data.data || response.data.regions || [];
   
    branches.value = regionsData.map(region => ({
      value: region.id,
      label: region.name
    }));
  } catch (error) {
    console.error('Failed to fetch regions:', error);
    branches.value = [];
  }
};

const submit = () => {
  form.put(route('admin.employees.update', props.employee.id), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  });
};

const handleDiscard = () => {
  showCancelModal.value = false;
  router.visit(route('admin.employees.index'));
};

onMounted(() => {
  fetchRegions();
});
</script>