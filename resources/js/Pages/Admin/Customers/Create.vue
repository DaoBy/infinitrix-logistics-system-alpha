<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">Create New Customer</h2>
          <p class="mt-1 text-sm text-gray-500">
            Add a new customer to the system
          </p>
        </div>

        <!-- Right: Buttons -->
        <div class="flex gap-2">
          <SecondaryButton @click="showCancelModal = true">
            Back to List
          </SecondaryButton>
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

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg max-w-4xl mx-auto">
          <div class="p-6 bg-white border-b border-gray-200">
            <form @submit.prevent="submit">
              <!-- Customer Type Section -->
              <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Customer Type</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <InputLabel for="customer_category" value="Customer Category *" />
                    <SelectInput
                      id="customer_category"
                      v-model="form.customer_category"
                      :options="customerCategoryOptions"
                      option-value="value"
                      option-label="label"
                      class="mt-1 block w-full"
                      required
                    />
                    <InputError class="mt-2" :message="form.errors.customer_category" />
                  </div>

                  <div>
                    <InputLabel for="frequency_type" value="Frequency *" />
                    <SelectInput
                      id="frequency_type"
                      v-model="form.frequency_type"
                      :options="frequencyTypeOptions"
                      option-value="value"
                      option-label="label"
                      class="mt-1 block w-full"
                      required
                    />
                    <InputError class="mt-2" :message="form.errors.frequency_type" />
                  </div>
                </div>
              </div>

              <!-- Basic Information Section -->
              <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <InputLabel for="first_name" value="First Name *" />
                    <TextInput
                      id="first_name"
                      type="text"
                      class="mt-1 block w-full"
                      v-model="form.first_name"
                      required
                    />
                    <InputError class="mt-2" :message="form.errors.first_name" />
                  </div>
                  <div>
                    <InputLabel for="middle_name" value="Middle Name" />
                    <TextInput
                      id="middle_name"
                      type="text"
                      class="mt-1 block w-full"
                      v-model="form.middle_name"
                    />
                    <InputError class="mt-2" :message="form.errors.middle_name" />
                  </div>
                  <div>
                    <InputLabel for="last_name" value="Last Name *" />
                    <TextInput
                      id="last_name"
                      type="text"
                      class="mt-1 block w-full"
                      v-model="form.last_name"
                      required
                    />
                    <InputError class="mt-2" :message="form.errors.last_name" />
                  </div>
                </div>
                <div class="mt-4" v-if="form.customer_category === 'company'">
                  <InputLabel for="company_name" value="Company Name *" />
                  <TextInput
                    id="company_name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.company_name"
                    required
                  />
                  <InputError class="mt-2" :message="form.errors.company_name" />
                </div>
              </div>

              <!-- Contact Information Section -->
              <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Contact Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <InputLabel for="email" value="Email *" />
                    <TextInput
                      id="email"
                      type="email"
                      class="mt-1 block w-full"
                      v-model="form.email"
                      required
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                  </div>

                  <div>
                    <InputLabel for="mobile" value="Mobile *" />
                    <TextInput
                      id="mobile"
                      type="tel"
                      class="mt-1 block w-full"
                      v-model="form.mobile"
                      required
                    />
                    <InputError class="mt-2" :message="form.errors.mobile" />
                  </div>

                  <div>
                    <InputLabel for="phone" value="Phone (Landline)" />
                    <TextInput
                      id="phone"
                      type="tel"
                      class="mt-1 block w-full"
                      v-model="form.phone"
                    />
                    <InputError class="mt-2" :message="form.errors.phone" />
                  </div>
                </div>
              </div>

              <!-- Address Information Section -->
              <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Address Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <InputLabel for="building_number" value="Building Number" />
                    <TextInput
                      id="building_number"
                      type="text"
                      class="mt-1 block w-full"
                      v-model="form.building_number"
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
                    />
                    <InputError class="mt-2" :message="form.errors.zip_code" />
                  </div>
                </div>
              </div>

              <!-- Additional Information Section -->
              <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Additional Information</h3>
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

              <div class="mt-6 flex justify-end space-x-3">
                <SecondaryButton type="button" @click="showCancelModal = true">
                  Cancel
                </SecondaryButton>
                <PrimaryButton type="submit" :disabled="form.processing">
                  Create Customer
                </PrimaryButton>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Cancel Confirmation Modal -->
    <Modal :show="showCancelModal" @close="showCancelModal = false">
      <div class="p-5">
        <h2 class="text-lg font-medium text-gray-900">
          Are you sure you want to cancel?
        </h2>
        <p class="mt-1 text-sm text-gray-600">
          All entered information will be lost.
        </p>
        <div class="mt-4 flex justify-end space-x-3">
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
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  status: String,
  success: String,
  error: String,
});

const showCancelModal = ref(false);

const customerCategoryOptions = [
  { value: 'individual', label: 'Individual' },
  { value: 'company', label: 'Company' }
];

const frequencyTypeOptions = [
  { value: 'regular', label: 'Regular' },
  { value: 'occasional', label: 'Occasional' }
];

const form = useForm({
  first_name: '',
  middle_name: '',
  last_name: '',
  company_name: '',
  email: '',
  mobile: '',
  phone: '',
  building_number: '',
  street: '',
  barangay: '',
  city: '',
  province: '',
  zip_code: '',
  customer_category: 'individual',
  frequency_type: 'regular',
  notes: '',
});

const submit = () => {
  form.post(route('admin.customers.store'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  });
};

const handleDiscard = () => {
  showCancelModal.value = false;
  form.reset();
  router.visit(route('admin.customers.index'));
};
</script>

<style scoped>
.zoom-content {
  zoom: 0.80;
}

/* Add tighter spacing for desktop */
@media (min-width: 1024px) {
  .zoom-content {
    zoom: 0.90;
  }
}
</style>