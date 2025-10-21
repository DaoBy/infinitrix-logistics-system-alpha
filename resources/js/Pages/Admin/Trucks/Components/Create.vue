<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Add Component - {{ truck.make }} {{ truck.model }} ({{ truck.license_plate }})
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Add a new component to this truck
          </p>
        </div>

        <!-- Right: Buttons -->
        <div class="flex gap-2">
          <SecondaryButton @click="router.get(route('admin.trucks.components.index', truck.id))">
            Back to Components
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
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <InputLabel for="name" value="Name *" />
                  <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                  />
                  <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                  <InputLabel for="type" value="Type *" />
                  <SelectInput
                    id="type"
                    v-model="form.type"
                    :options="componentTypes"
                    option-value="key"
                    option-label="text"
                    class="mt-1 block w-full"
                    required
                  />
                  <InputError class="mt-2" :message="form.errors.type" />
                </div>

                <div>
                  <InputLabel for="serial_number" value="Serial Number" />
                  <TextInput
                    id="serial_number"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.serial_number"
                  />
                  <InputError class="mt-2" :message="form.errors.serial_number" />
                </div>

                <div>
                  <InputLabel for="installation_date" value="Installation Date *" />
                  <TextInput
                    id="installation_date"
                    type="date"
                    class="mt-1 block w-full"
                    v-model="form.installation_date"
                    required
                  />
                  <InputError class="mt-2" :message="form.errors.installation_date" />
                </div>

                <div>
                  <InputLabel for="condition" value="Condition *" />
                  <SelectInput
                    id="condition"
                    v-model="form.condition"
                    :options="conditionOptions"
                    option-value="key"
                    option-label="text"
                    class="mt-1 block w-full"
                    required
                  />
                  <InputError class="mt-2" :message="form.errors.condition" />
                </div>

                <div class="md:col-span-2">
                  <InputLabel for="notes" value="Notes" />
                  <TextArea
                    id="notes"
                    class="mt-1 block w-full"
                    v-model="form.notes"
                    :rows="3"
                  />
                  <InputError class="mt-2" :message="form.errors.notes" />
                </div>
              </div>

              <div class="mt-6 flex justify-end space-x-3">
                <SecondaryButton type="button" @click="router.get(route('admin.trucks.components.index', truck.id))">
                  Cancel
                </SecondaryButton>
                <PrimaryButton type="submit" :disabled="form.processing">
                  <span v-if="form.processing">Creating...</span>
                  <span v-else>Add Component</span>
                </PrimaryButton>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextArea from '@/Components/TextArea.vue';
import InputError from '@/Components/InputError.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  truck: Object,
  componentTypes: Array,
  conditionOptions: Array,
  status: String,
  success: String,
  error: String,
});

// Format the options to match backend format and capitalize display text
const componentTypes = props.componentTypes.map(option => ({
  key: option.value, // Use 'value' as 'key' (this is the actual value sent to backend)
  text: option.text.replace(/\b\w/g, char => char.toUpperCase()) // Capitalize display text
}));

const conditionOptions = props.conditionOptions.map(option => ({
  key: option.value, // Use 'value' as 'key' (this is the actual value sent to backend)
  text: option.text.replace(/\b\w/g, char => char.toUpperCase()) // Capitalize display text
}));

const form = useForm({
  truck_id: props.truck.id,
  name: '',
  type: componentTypes[0]?.key || '', // Set to first option's key
  serial_number: '',
  installation_date: '',
  condition: conditionOptions.find(opt => opt.key === 'good')?.key || conditionOptions[0]?.key || '', // Default to 'good'
  notes: '',
});

const submit = () => {
  form.post(route('admin.trucks.components.store', props.truck.id), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
    },
  });
};
</script>

<style scoped>
.zoom-content {
  zoom: 0.80;
}
</style>