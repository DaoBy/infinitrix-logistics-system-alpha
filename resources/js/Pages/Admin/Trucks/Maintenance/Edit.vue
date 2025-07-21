<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
          Edit Maintenance - {{ truck.make }} {{ truck.model }} ({{ truck.license_plate }})
        </h2>
        <SecondaryButton @click="router.get(route('admin.trucks.maintenance.index', truck.id))">
          Back to Maintenance
        </SecondaryButton>
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
                  <InputLabel for="maintenance_date" value="Date *" />
                  <TextInput
                    id="maintenance_date"
                    type="date"
                    class="mt-1 block w-full"
                    v-model="form.maintenance_date"
                    required
                    :max="new Date().toISOString().split('T')[0]"
                  />
                  <InputError class="mt-2" :message="form.errors.maintenance_date" />
                </div>

                 <div>
                      <InputLabel for="type" value="Type *" />
                     <SelectInput
                    id="type"
                    v-model="form.type"
                    :options="maintenanceTypes"
                    option-value="value"
                    option-label="text"
                    class="mt-1 block w-full"
                    required
                  />
                      <InputError class="mt-2" :message="form.errors.type" />
                    </div>

                <div>
                  <InputLabel for="service_provider" value="Service Provider *" />
                  <TextInput
                    id="service_provider"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.service_provider"
                    required
                  />
                  <InputError class="mt-2" :message="form.errors.service_provider" />
                </div>

                <div class="md:col-span-2">
  <InputLabel for="component_id" value="Related Component (Optional)" />
  <SelectInput
    id="component_id"
    v-model="form.component_id"
    :options="truck.components"
    option-value="id"
    option-label="name"
    class="mt-1 block w-full"
  />
  <InputError class="mt-2" :message="form.errors.component_id" />
</div>

                <div>
                  <InputLabel for="cost" value="Cost *" />
                   <TextInput
                    id="cost"
                    type="number"
                    step="0.01"
                    min="0"
                    class="mt-1 block w-full"
                    v-model="form.cost"
                    required
                    @update:modelValue="handleCostInput"
                  />
                  <InputError class="mt-2" :message="form.errors.cost" />
                </div>

                <div class="md:col-span-2">
                  <InputLabel for="service_details" value="Service Details *" />
                <TextArea
  id="service_details"
  class="mt-1 block w-full"
  v-model="form.service_details"
  required
  :rows="4"
/>
                  <InputError class="mt-2" :message="form.errors.service_details" />
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
                <SecondaryButton type="button" @click="router.get(route('admin.trucks.maintenance.index', truck.id))">
                  Cancel
                </SecondaryButton>
                <PrimaryButton type="submit" :disabled="form.processing">
                  Update Maintenance
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
  maintenance: Object,
  maintenanceTypes: {
    type: Array,
    default: () => [
      { value: 'routine', text: 'Routine Maintenance' },
      { value: 'repair', text: 'Repair' },
      { value: 'component_replacement', text: 'Component Replacement' },
      { value: 'inspection', text: 'Inspection' }
    ]
  },
  status: String,
  success: String,
  error: String,
});

const form = useForm({
  maintenance_date: props.maintenance.maintenance_date?.split('T')[0] || new Date().toISOString().split('T')[0],
  type: props.maintenance.type || 'routine',
  service_details: props.maintenance.service_details || '',
  cost: props.maintenance.cost ? String(props.maintenance.cost) : '', 
  service_provider: props.maintenance.service_provider || '',
  notes: props.maintenance.notes || '',
  component_id: props.maintenance.component_id || null,
});

const handleCostInput = (value) => {
  form.cost = value === '' ? '' : Number(value);
};

const submit = () => {
  form.put(route('admin.trucks.maintenance.update', {
    truck: props.truck.id,
    maintenance: props.maintenance.id
  }), {
    preserveScroll: true,
    onSuccess: () => {
    },
    onError: () => {
    }
  });
};
</script>