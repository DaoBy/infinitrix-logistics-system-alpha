<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
          Edit Component - {{ component.name }}
        </h2>
        <div class="flex space-x-2">
          <SecondaryButton @click="router.get(route('admin.trucks.components.index', truck.id))">
            Back to Components
          </SecondaryButton>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div v-if="status || success || error" class="mb-6">
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
                option-label="value"
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
                  <InputLabel for="last_maintenance_date" value="Last Maintenance Date" />
                  <TextInput
                    id="last_maintenance_date"
                    type="date"
                    class="mt-1 block w-full"
                    v-model="form.last_maintenance_date"
                  />
                  <InputError class="mt-2" :message="form.errors.last_maintenance_date" />
                </div>

                <div>
                  <InputLabel for="condition" value="Condition *" />
                  <SelectInput
                       id="condition"
                        v-model="form.condition"
                        :options="conditionOptions"
                        option-value="key"
                        option-label="value"
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

              <div class="mt-6 flex justify-end space-x-4">
                <SecondaryButton type="button" @click="router.get(route('admin.trucks.components.index', truck.id))">
                  Cancel
                </SecondaryButton>
                <PrimaryButton type="submit" :disabled="form.processing">
                  Update Component
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
  truck: Object,
  component: Object,
  componentTypes: Array,
  conditionOptions: Array,
  status: String,
  success: String,
  error: String,
});

const form = useForm({
  name: props.component.name,
  type: props.component.type,
  serial_number: props.component.serial_number,
  last_maintenance_date: props.component.last_maintenance_date?.split('T')[0] || '',
  condition: props.component.condition,
  notes: props.component.notes,
});

const submit = () => {
  form.put(route('admin.trucks.components.update', {
    truck: props.truck.id,
    component: props.component.id
  }), {
    preserveScroll: true,
  });
};
</script>