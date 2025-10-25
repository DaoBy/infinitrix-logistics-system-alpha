[file name]: SettingsSection.vue
[file content begin]
<template>
  <div class="space-y-6">
    <!-- Settings Statistics -->
   

      

    <!-- Price Matrix -->
    <div class="bg-white shadow rounded-lg border border-gray-200">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Price Matrix</h3>
        <p class="mt-1 text-sm text-gray-500">
          Configure pricing rates for calculations
        </p>
      </div>
      <div class="px-6 py-4">
        <!-- Pricing Information -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-blue-800">
                How Pricing Works
              </h3>
              <div class="mt-2 text-sm text-blue-700 space-y-2">
                <p><strong>Total Price = Base Fee + Volume Fee + Weight Fee + Package Fee</strong></p>
                <div class="space-y-1">
                  <p>• <strong>Base Fee</strong>: Fixed cost applied to every shipment</p>
                  <p>• <strong>Volume Fee</strong>: (Height × Width × Length in meters) × Volume Rate</p>
                  <p>• <strong>Weight Fee</strong>: Package Weight (kg) × Weight Rate</p>
                  <p>• <strong>Package Fee</strong>: Fixed fee per package</p>
                </div>
                <p class="text-xs"><strong>Note:</strong> Dimensions converted from cm to m. Limits: Volume ≤ 10m³, Weight ≤ 100kg per package.</p>
              </div>
            </div>
          </div>
        </div>

        <form @submit.prevent="updatePriceMatrix" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <InputLabel value="Base Fee (₱)" class="mb-2" />
              <input
                v-model="priceForm.base_fee"
                type="number"
                step="0.01"
                min="0"
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                required
              >
            </div>

            <div>
              <InputLabel value="Volume Rate (₱ per m³)" class="mb-2" />
              <input
                v-model="priceForm.volume_rate"
                type="number"
                step="0.0001"
                min="0"
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                required
              >
            </div>

            <div>
              <InputLabel value="Weight Rate (₱ per kg)" class="mb-2" />
              <input
                v-model="priceForm.weight_rate"
                type="number"
                step="0.01"
                min="0"
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                required
              >
            </div>

            <div>
              <InputLabel value="Package Rate (₱ per package)" class="mb-2" />
              <input
                v-model="priceForm.package_rate"
                type="number"
                step="0.01"
                min="0"
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                required
              >
            </div>
          </div>

          <div class="flex justify-end">
            <PrimaryButton
              type="submit"
              :disabled="priceLoading"
              class="px-4 py-2"
            >
              <span v-if="priceLoading">Saving...</span>
              <span v-else>Save Price Matrix</span>
            </PrimaryButton>
          </div>
        </form>
      </div>
    </div>

    <!-- Font Preferences -->
    <div class="bg-white shadow rounded-lg border border-gray-200">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Font Preferences</h3>
        <p class="mt-1 text-sm text-gray-500">
          Customize your font style and size
        </p>
      </div>
      <div class="px-6 py-4">
        <form @submit.prevent="savePreferences" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <InputLabel value="" class="mb-2" />
              <SelectInput
                v-model="preferencesForm.font_style"
                :options="fontStyleOptions"
                option-value="value"
                option-label="label"
                placeholder="Select font style"
                class="w-full"
              />
            </div>

            <div>
              <InputLabel value="" class="mb-2" />
              <SelectInput
                v-model="preferencesForm.font_size"
                :options="fontSizeOptions"
                option-value="value"
                option-label="label"
                placeholder="Select font size"
                class="w-full"
              />
            </div>
          </div>

          <!-- Live Preview -->
          <div class="border-t pt-4 border-gray-200">
            <h4 class="text-sm font-medium text-gray-700 mb-3">Live Preview</h4>
            <div class="p-4 border rounded-lg bg-gray-50">
              <div class="space-y-2">
                <p class="font-bold" :style="previewStyle">This is a heading with your font settings</p>
                <p class="text-sm" :style="previewStyle">This is regular text with your chosen font style and size. The quick brown fox jumps over the lazy dog.</p>
                <p class="text-xs text-gray-600">Current: {{ preferencesForm.font_style }} • {{ preferencesForm.font_size }}</p>
              </div>
            </div>
          </div>

          <div class="flex justify-end space-x-3">
            <SecondaryButton
              type="button"
              @click="resetToDefault"
              class="px-4 py-2"
            >
              Reset to Default
            </SecondaryButton>
            <PrimaryButton
              type="submit"
              :disabled="loading"
              class="px-4 py-2"
            >
              <span v-if="loading">Saving...</span>
              <span v-else>Save Font Preferences</span>
            </PrimaryButton>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
// Use relative path
import { usePreferences } from '../../../../stores/preferences.js';

const { preferences, saveGlobalPreferences, applyGlobalPreferences } = usePreferences();
const loading = ref(false);
const priceLoading = ref(false);

// Price Matrix Form
const priceForm = reactive({
  base_fee: 0,
  volume_rate: 0,
  weight_rate: 0,
  package_rate: 0
});

// Font Preferences Form - NO DARK MODE
const preferencesForm = reactive({
  font_style: 'inter',
  font_size: 'medium'
});

// Options for SelectInput components
const fontStyleOptions = computed(() => [
  { value: 'inter', label: 'Inter (Modern)' },
  { value: 'arial', label: 'Arial (Classic)' },
  { value: 'roboto', label: 'Roboto (Clean)' }
]);

const fontSizeOptions = computed(() => [
  { value: 'small', label: 'Small' },
  { value: 'medium', label: 'Medium' },
  { value: 'large', label: 'Large' }
]);

// Compute preview style
const previewStyle = computed(() => {
  const fontFamily = 
    preferencesForm.font_style === 'inter' ? '"Inter", sans-serif' :
    preferencesForm.font_style === 'roboto' ? '"Roboto", sans-serif' : 'Arial, sans-serif';
  
  const fontSize =
    preferencesForm.font_size === 'small' ? '14px' :
    preferencesForm.font_size === 'large' ? '18px' : '16px';
  
  return {
    fontFamily: fontFamily,
    fontSize: fontSize
  };
});

// Load preferences and price matrix
onMounted(() => {
  // Load current preferences when component mounts
  Object.assign(preferencesForm, preferences.value);

  // Load price matrix from props
  if (props.priceMatrix) {
    Object.assign(priceForm, props.priceMatrix);
  }
});

// Price Matrix Methods
const updatePriceMatrix = () => {
  priceLoading.value = true;
  router.post('/admin/utilities/price-matrix', priceForm, {
    preserveScroll: true,
    onFinish: () => {
      priceLoading.value = false;
    },
  });
};

// Font Preferences Methods
const savePreferences = async () => {
  loading.value = true;
  
  try {
    await saveGlobalPreferences(preferencesForm);
  } catch (error) {
    console.error('Error saving preferences:', error);
  } finally {
    loading.value = false;
  }
};

// Reset to default
const resetToDefault = () => {
  preferencesForm.font_style = 'inter';
  preferencesForm.font_size = 'medium';
  savePreferences();
};

// Define props
const props = defineProps({
  priceMatrix: Object
});
</script>
[file content end]