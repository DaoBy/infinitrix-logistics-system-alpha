<template>
  <EmployeeLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Price Matrix Configuration
      </h2>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <!-- Success Message -->
            <div v-if="$page.props.flash.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
              {{ $page.props.flash.success }}
            </div>

            <form @submit.prevent="submit">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <InputLabel for="base_fee" value="Base Fee (₱)" />
                  <TextInput
                    id="base_fee"
                    type="number"
                    step="0.01"
                    min="0"
                    class="mt-1 block w-full"
                    v-model="form.base_fee"
                    required
                  />
                  <InputError class="mt-2" :message="form.errors.base_fee" />
                </div>

                <div>
                  <InputLabel for="volume_rate" value="Volume Rate (₱ per m³)" />
                  <TextInput
                    id="volume_rate"
                    type="number"
                    step="0.0001"
                    min="0"
                    class="mt-1 block w-full"
                    v-model="form.volume_rate"
                    required
                  />
                  <InputError class="mt-2" :message="form.errors.volume_rate" />
                </div>

                <div>
                  <InputLabel for="weight_rate" value="Weight Rate (₱ per kg)" />
                  <TextInput
                    id="weight_rate"
                    type="number"
                    step="0.01"
                    min="0"
                    class="mt-1 block w-full"
                    v-model="form.weight_rate"
                    required
                  />
                  <InputError class="mt-2" :message="form.errors.weight_rate" />
                </div>

                <div>
                  <InputLabel for="package_rate" value="Package Rate (₱ per package)" />
                  <TextInput
                    id="package_rate"
                    type="number"
                    step="0.01"
                    min="0"
                    class="mt-1 block w-full"
                    v-model="form.package_rate"
                    required
                  />
                  <InputError class="mt-2" :message="form.errors.package_rate" />
                </div>
              </div>

              <div class="flex items-center justify-end mt-6">
                <PrimaryButton
                  :class="{ 'opacity-25': form.processing }"
                  :disabled="form.processing"
                >
                  Save Changes
                </PrimaryButton>
              </div>
            </form>

            <div class="mt-8 bg-gray-50 p-4 rounded-lg">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Pricing Formula</h3>
              <div class="space-y-2">
                <p class="text-sm text-gray-600">
                  <span class="font-medium">Total Price =</span> 
                  (Base Fee + (Total Volume × Volume Rate) + (Total Weight × Weight Rate) + (Package Count × Package Rate)) × Distance Multiplier
                </p>
                <p class="text-sm text-gray-600">
                  <span class="font-medium">Distance Multiplier:</span> 
                  1.0 for same region, 1.5 for different regions
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
import { useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';


const props = defineProps({
  priceMatrix: {
    type: Object,
    required: true,
  },
  success: {
    type: String,
    default: '',
  },
  error: {
    type: String,
    default: '',
  },
  status: {
    type: String,
    default: '',
  }
});



const form = useForm({
  base_fee: props.priceMatrix?.base_fee || 50.00,
  volume_rate: props.priceMatrix?.volume_rate || 10.00,
  weight_rate: props.priceMatrix?.weight_rate || 5.00,
  package_rate: props.priceMatrix?.package_rate || 2.00,
});

const submit = () => {
  form.put(route('admin.price-matrix.update'), {
    preserveScroll: true,
  });
};
</script>