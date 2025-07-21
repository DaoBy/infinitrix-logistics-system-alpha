<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
          Edit Package: {{ package.item_name }} ({{ package.item_code }})
        </h2>
        <SecondaryButton @click="showCancelModal = true">Back to List</SecondaryButton>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div v-if="status || success || error" class="mb-6">
          <div v-if="status" class="p-4 bg-blue-100 text-blue-800 rounded">{{ status }}</div>
          <div v-if="success" class="p-4 bg-green-100 text-green-800 rounded">{{ success }}</div>
          <div v-if="error" class="p-4 bg-red-100 text-red-800 rounded">{{ error }}</div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <form @submit.prevent="submit">
              <!-- Basic Information -->
              <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">
                  Package Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <InputLabel for="item_code" value="Item Code *" />
                    <TextInput
                      id="item_code"
                      type="text"
                      class="mt-1 block w-full font-mono"
                      v-model="form.item_code"
                      required
                    />
                    <InputError class="mt-2" :message="form.errors.item_code" />
                  </div>

                  <div>
                    <InputLabel for="item_name" value="Item Name *" />
                    <TextInput
                      id="item_name"
                      type="text"
                      class="mt-1 block w-full"
                      v-model="form.item_name"
                      required
                    />
                    <InputError class="mt-2" :message="form.errors.item_name" />
                  </div>

                  <div>
                    <InputLabel for="category" value="Category *" />
                    <SelectInput
                      id="category"
                      v-model="form.category"
                      :options="categories"
                      option-value="value"
                      option-label="label"
                      placeholder="Select category"
                      class="mt-1 block w-full"
                      required
                    />
                    <InputError class="mt-2" :message="form.errors.category" />
                  </div>

                  <div>
                    <InputLabel for="current_region_id" value="Current Location *" />
                    <SelectInput
                      id="current_region_id"
                      v-model="form.current_region_id"
                      :options="regions"
                      option-value="id"
                      option-label="name"
                      class="mt-1 block w-full"
                      required
                    />
                    <InputError class="mt-2" :message="form.errors.current_region_id" />
                  </div>

                  <div>
                    <InputLabel for="quantity" value="Quantity *" />
                    <TextInput
                      id="quantity"
                      type="number"
                      class="mt-1 block w-full"
                      v-model="form.quantity"
                      min="1"
                      required
                    />
                    <InputError class="mt-2" :message="form.errors.quantity" />
                  </div>

                  <div>
                    <InputLabel for="value" value="Value ($)" />
                    <TextInput
                      id="value"
                      type="number"
                      class="mt-1 block w-full"
                      v-model="form.value"
                      min="0"
                      step="0.01"
                    />
                    <InputError class="mt-2" :message="form.errors.value" />
                  </div>
                </div>
              </div>

              <!-- Dimensions -->
              <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">
                  Dimensions (cm)
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div>
                    <InputLabel for="height" value="Height" />
                    <TextInput
                      id="height"
                      type="number"
                      class="mt-1 block w-full"
                      v-model="form.height"
                      min="0"
                      step="0.01"
                      @change="calculateVolume"
                    />
                    <InputError class="mt-2" :message="form.errors.height" />
                  </div>

                  <div>
                    <InputLabel for="width" value="Width" />
                    <TextInput
                      id="width"
                      type="number"
                      class="mt-1 block w-full"
                      v-model="form.width"
                      min="0"
                      step="0.01"
                      @change="calculateVolume"
                    />
                    <InputError class="mt-2" :message="form.errors.width" />
                  </div>

                  <div>
                    <InputLabel for="length" value="Length" />
                    <TextInput
                      id="length"
                      type="number"
                      class="mt-1 block w-full"
                      v-model="form.length"
                      min="0"
                      step="0.01"
                      @change="calculateVolume"
                    />
                    <InputError class="mt-2" :message="form.errors.length" />
                  </div>
                </div>

                <div class="mt-4">
                  <InputLabel value="Calculated Volume" />
                  <div class="mt-1 p-2 bg-gray-50 rounded">
                    <span class="font-mono">
                      {{ calculatedVolume ? calculatedVolume + ' m³' : 'Enter dimensions to calculate volume' }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Weight -->
              <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-200">
                  Weight (kg)
                </h3>
                <div>
                  <InputLabel for="weight" value="Weight" />
                  <TextInput
                    id="weight"
                    type="number"
                    class="mt-1 block w-full"
                    v-model="form.weight"
                    min="0"
                    step="0.01"
                  />
                  <InputError class="mt-2" :message="form.errors.weight" />
                </div>
              </div>

              <div class="mt-6 flex justify-end space-x-4">
                <SecondaryButton type="button" @click="showCancelModal = true">
                  Cancel
                </SecondaryButton>
                <PrimaryButton type="submit" :disabled="form.processing">
                  Update Package
                </PrimaryButton>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Cancel Modal -->
    <Modal :show="showCancelModal" @close="showCancelModal = false">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">Discard Changes?</h2>
        <p class="mt-1 text-sm text-gray-600">Any unsaved changes will be lost.</p>
        <div class="mt-6 flex justify-end space-x-4">
          <SecondaryButton @click="showCancelModal = false">Continue Editing</SecondaryButton>
          <DangerButton @click="handleDiscard">Discard Changes</DangerButton>
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
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  package: Object,
  categories: Array,
  regions: Array,
  status: String,
  success: String,
  error: String,
});

const showCancelModal = ref(false);

const form = useForm({
  item_code: props.package.item_code,
  item_name: props.package.item_name,
  category: props.package.category,
  current_region_id: props.package.current_region_id,
  height: props.package.height,
  width: props.package.width,
  length: props.package.length,
  weight: props.package.weight,
  quantity: props.package.quantity,
  value: props.package.value,
});

const calculatedVolume = computed(() => {
  if (form.height && form.width && form.length) {
    return ((form.height / 100) * (form.width / 100) * (form.length / 100)).toFixed(3);
  }
  return null;
});

const calculateVolume = () => {
  if (form.height && form.width && form.length) {
    form.volume = (form.height / 100) * (form.width / 100) * (form.length / 100);
  }
};

const submit = () => {
  form.put(route('admin.packages.update', props.package.id), {
    preserveScroll: true,
  });
};

const handleDiscard = () => {
  showCancelModal.value = false;
  router.visit(route('admin.packages.index'));
};

const categories = [
  { value: 'piece', label: 'Piece' },
  { value: 'carton', label: 'Carton' },
  { value: 'sack', label: 'Sack' },
  { value: 'bundle', label: 'Bundle' },
  { value: 'roll', label: 'Roll' },
  { value: 'B/R', label: 'Bundle/Roll' },
  { value: 'C/S', label: 'Carton/Sack' },
];
</script>