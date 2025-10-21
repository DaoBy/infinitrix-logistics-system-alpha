<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">Add New Truck</h2>
          <p class="mt-1 text-sm text-gray-500">
            Create a new truck entry with all necessary information
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

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <form @submit.prevent="submit">
              <!-- Basic Information Section -->
              <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">
                  Basic Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <InputLabel for="license_plate" value="License Plate *" />
                    <TextInput
                      id="license_plate"
                      type="text"
                      class="mt-1 block w-full"
                      v-model="form.license_plate"
                      required
                      autofocus
                    />
                    <InputError class="mt-2" :message="form.errors.license_plate" />
                  </div>

                  <div>
                    <InputLabel for="make" value="Make *" />
                    <TextInput
                      id="make"
                      type="text"
                      class="mt-1 block w-full"
                      v-model="form.make"
                      required
                    />
                    <InputError class="mt-2" :message="form.errors.make" />
                  </div>

                  <div>
                    <InputLabel for="model" value="Model *" />
                    <TextInput
                      id="model"
                      type="text"
                      class="mt-1 block w-full"
                      v-model="form.model"
                      required
                    />
                    <InputError class="mt-2" :message="form.errors.model" />
                  </div>

                  <div>
                    <InputLabel for="region_id" value="Region *" />
                    <SelectInput
                      id="region_id"
                      v-model="form.region_id"
                      :options="regionOptions"
                      option-value="id"
                      option-label="name"
                      class="mt-1 block w-full"
                      required
                    />
                    <InputError class="mt-2" :message="form.errors.region_id" />
                  </div>

                  <div>
                    <InputLabel for="volume_capacity" value="Volume Capacity (m³) *" />
                    <TextInput
                      id="volume_capacity"
                      type="number"
                      step="0.01"
                      min="0"
                      class="mt-1 block w-full"
                      v-model="form.volume_capacity"
                      required
                    />
                    <InputError class="mt-2" :message="form.errors.volume_capacity" />
                  </div>

                  <div>
                    <InputLabel for="weight_capacity" value="Weight Capacity (kg) *" />
                    <TextInput
                      id="weight_capacity"
                      type="number"
                      step="0.01"
                      min="0"
                      class="mt-1 block w-full"
                      v-model="form.weight_capacity"
                      required
                    />
                    <InputError class="mt-2" :message="form.errors.weight_capacity" />
                  </div>

                  <div>
                    <InputLabel for="current_volume" value="Current Volume (m³)" />
                    <TextInput
                      id="current_volume"
                      type="number"
                      step="0.01"
                      min="0"
                      class="mt-1 block w-full"
                      v-model="form.current_volume"
                    />
                    <InputError class="mt-2" :message="form.errors.current_volume" />
                  </div>

                  <div>
                    <InputLabel for="current_weight" value="Current Weight (kg)" />
                    <TextInput
                      id="current_weight"
                      type="number"
                      step="0.01"
                      min="0"
                      class="mt-1 block w-full"
                      v-model="form.current_weight"
                    />
                    <InputError class="mt-2" :message="form.errors.current_weight" />
                  </div>
                </div>
              </div>

              <!-- Status & Additional Information -->
              <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">
                  Status & Additional Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <InputLabel for="status" value="Status *" />
                    <SelectInput
                      id="status"
                      v-model="form.status"
                      :options="statusOptions"
                      option-value="value"
                      option-label="label"
                      class="mt-1 block w-full"
                      required
                    />
                    <InputError class="mt-2" :message="form.errors.status" />
                  </div>

                  <div>
                    <InputLabel for="year" value="Year" />
                    <TextInput
                      id="year"
                      type="number"
                      min="1900"
                      :max="new Date().getFullYear() + 1"
                      class="mt-1 block w-full"
                      v-model="form.year"
                    />
                    <InputError class="mt-2" :message="form.errors.year" />
                  </div>

                  <div>
                    <InputLabel for="vin" value="VIN" />
                    <TextInput
                      id="vin"
                      type="text"
                      class="mt-1 block w-full"
                      v-model="form.vin"
                    />
                    <InputError class="mt-2" :message="form.errors.vin" />
                  </div>

                  <div>
                    <InputLabel for="purchase_date" value="Purchase Date" />
                    <TextInput
                      id="purchase_date"
                      type="date"
                      :max="new Date().toISOString().split('T')[0]"
                      class="mt-1 block w-full"
                      v-model="form.purchase_date"
                    />
                    <InputError class="mt-2" :message="form.errors.purchase_date" />
                  </div>
                </div>
              </div>

              <!-- Financial Information -->
              <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">
                  Financial Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <InputLabel for="purchase_price" value="Purchase Price" />
                    <TextInput
                      id="purchase_price"
                      type="number"
                      step="0.01"
                      min="0"
                      class="mt-1 block w-full"
                      v-model="form.purchase_price"
                    />
                    <InputError class="mt-2" :message="form.errors.purchase_price" />
                  </div>

                  <div>
                    <InputLabel for="current_value" value="Current Value" />
                    <TextInput
                      id="current_value"
                      type="number"
                      step="0.01"
                      min="0"
                      class="mt-1 block w-full"
                      v-model="form.current_value"
                    />
                    <InputError class="mt-2" :message="form.errors.current_value" />
                  </div>
                </div>
              </div>

              <!-- Notes Section -->
              <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">
                  Additional Notes
                </h3>
                <div>
                  <InputLabel for="notes" value="Notes" />
                  <TextArea
                    id="notes"
                    class="mt-1 block w-full"
                    v-model="form.notes"
                    :rows="4"
                  />
                  <InputError class="mt-2" :message="form.errors.notes" />
                </div>
              </div>

              <div class="mt-6 flex justify-end space-x-3">
                <SecondaryButton type="button" @click="showCancelModal = true">
                  Cancel
                </SecondaryButton>
                <PrimaryButton type="submit" :disabled="form.processing">
                  <span v-if="form.processing">Creating...</span>
                  <span v-else>Create Truck</span>
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
        <h2 class="text-lg font-medium text-gray-900">Are you sure you want to cancel?</h2>
        <p class="mt-1 text-sm text-gray-600">
          All entered information will be lost.
        </p>
        <div class="mt-4 flex justify-end space-x-3">
          <SecondaryButton @click="showCancelModal = false">
            Continue Creating
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
import TextArea from '@/Components/TextArea.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  status: String,
  success: String,
  error: String,
  regions: Array, // Changed from API call to prop
  default_status: String,
  status_options: Object,
});

const showCancelModal = ref(false);

// Use props instead of API call
const regionOptions = ref(props.regions || []);

// Use the status options from props
const statusOptions = ref(Object.entries(props.status_options || {}).map(([value, label]) => ({
  value,
  label
})));

const form = useForm({
  license_plate: '',
  make: '',
  model: '',
  volume_capacity: '',
  weight_capacity: '',
  current_volume: 0,
  current_weight: 0,
  status: props.default_status || 'available',
  year: '',
  vin: '',
  purchase_date: '',
  purchase_price: '',
  current_value: '',
  notes: '',
  region_id: '',
});

const submit = () => {
  form.post(route('admin.trucks.store'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  });
};

const handleDiscard = () => {
  showCancelModal.value = false;
  form.reset();
  router.visit(route('admin.trucks.index'));
};
</script>

<style scoped>
.zoom-content {
  zoom: 0.80;
}
</style>