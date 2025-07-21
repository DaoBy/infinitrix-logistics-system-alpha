<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
          Add Component - {{ truck.make }} {{ truck.model }} ({{ truck.license_plate }})
        </h2>
        <SecondaryButton @click="router.get(route('admin.trucks.components.index', truck.id))">
          Back to Components
        </SecondaryButton>
      </div>
    </template>

    <div class="py-6">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <!-- Status messages -->
        <div v-if="status || success || error" class="mb-6">
          <div v-if="status" class="p-4 bg-blue-100 text-blue-800 rounded">{{ status }}</div>
          <div v-if="success" class="p-4 bg-green-100 text-green-800 rounded">{{ success }}</div>
          <div v-if="error" class="p-4 bg-red-100 text-red-800 rounded">{{ error }}</div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <form @submit.prevent="submit">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name Field -->
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

                <!-- Type Field -->
                <div>
                  <InputLabel for="type" value="Type *" />
                  <SelectInput
                    id="type"
                    v-model="form.type"
                    :options="componentTypes"
                    class="mt-1 block w-full"
                    required
                  />
                  <InputError class="mt-2" :message="form.errors.type" />
                </div>

                <!-- Serial Number -->
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

                <!-- Installation Date -->
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

                <!-- Condition -->
                <div>
                  <InputLabel for="condition" value="Condition *" />
                  <SelectInput
                    id="condition"
                    v-model="form.condition"
                    :options="conditionOptions"
                    class="mt-1 block w-full"
                    required
                  />
                  <InputError class="mt-2" :message="form.errors.condition" />
                </div>

                <!-- Notes -->
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

              <div class="mt-6 flex justify-end space-x-4">
                <SecondaryButton type="button" @click="router.get(route('admin.trucks.components.index', truck.id))">
                  Cancel
                </SecondaryButton>
                <PrimaryButton type="submit" :disabled="form.processing">
                  Add Component
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
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextArea from '@/Components/TextArea.vue';
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  truck: {
    type: Object,
    required: true
  },
  componentTypes: {
    type: Array,
    default: () => []
  },
  conditionOptions: {
    type: Array,
    default: () => []
  },
  status: {
    type: String,
    default: ''
  },
  success: {
    type: String,
    default: ''
  },
  error: {
    type: String,
    default: ''
  },
});

const form = useForm({
  truck_id: props.truck.id,
  name: '',
  type: '',
  serial_number: '',
  installation_date: '',
  condition: 'good', // Default to 'good'
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