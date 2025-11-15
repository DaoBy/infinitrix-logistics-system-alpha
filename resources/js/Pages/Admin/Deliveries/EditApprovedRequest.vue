<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Update Package Details - Request #DR-{{ String(delivery.id).padStart(6, '0') }}
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Update package dimensions and weights for accurate pricing
          </p>
        </div>

        <div class="flex gap-2">
          <SecondaryButton @click="goBack">
            Cancel
          </SecondaryButton>
          <PrimaryButton @click="saveChanges" :disabled="form.processing">
            <span v-if="form.processing">Updating...</span>
            <span v-else>Update Packages</span>
          </PrimaryButton>
        </div>
      </div>
    </template>

    <div class="px-6 py-4">
      <!-- Status Messages -->
      <div v-if="status || success || error" class="mb-6">
        <div v-if="status" class="p-4 bg-blue-100 text-blue-800 rounded">{{ status }}</div>
        <div v-if="success" class="p-4 bg-green-100 text-green-800 rounded">{{ success }}</div>
        <div v-if="error" class="p-4 bg-red-100 text-red-800 rounded">{{ error }}</div>
      </div>

      <!-- Read-only Information -->
      <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Request Information (Read-only)</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
          <div>
            <label class="font-medium text-gray-700">Reference Number:</label>
            <p class="text-gray-900 font-mono">{{ delivery.reference_number || 'N/A' }}</p>
          </div>
          <div>
            <label class="font-medium text-gray-700">Sender:</label>
            <p class="text-gray-900">{{ delivery.sender?.name || delivery.sender?.company_name }}</p>
          </div>
          <div>
            <label class="font-medium text-gray-700">Receiver:</label>
            <p class="text-gray-900">{{ delivery.receiver?.name || delivery.receiver?.company_name }}</p>
          </div>
          <div>
            <label class="font-medium text-gray-700">Payment Method:</label>
            <p class="text-gray-900 capitalize">{{ delivery.payment_method }}</p>
          </div>
          <div>
            <label class="font-medium text-gray-700">Original Total:</label>
            <p class="text-gray-900">₱{{ delivery.total_price }}</p>
          </div>
        </div>
      </div>

      <!-- Edit Form -->
      <form @submit.prevent="saveChanges" class="space-y-8">
        <!-- Packages Information Section -->
        <div class="bg-white shadow-sm rounded-lg p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Package Details</h3>
            <div class="text-sm text-gray-500">
              Update package dimensions and weights as measured during handover
            </div>
          </div>

          <!-- Package List -->
          <div v-for="(pkg, index) in form.packages" :key="index" class="border border-gray-200 rounded-lg p-6 space-y-4 mb-6">
            <div class="flex items-center justify-between">
              <h4 class="text-md font-medium text-gray-900">Package {{ index + 1 }}</h4>
              <div class="text-sm text-gray-500">
                ID: {{ pkg.id }}
              </div>
            </div>

            <!-- Package Basic Info (Read-only) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg">
              <div>
                <label class="font-medium text-gray-700 text-sm">Item Name:</label>
                <p class="text-gray-900">{{ pkg.item_name }}</p>
              </div>
              <div>
                <label class="font-medium text-gray-700 text-sm">Category:</label>
                <p class="text-gray-900 capitalize">{{ pkg.category }}</p>
              </div>
              <div v-if="pkg.description">
                <label class="font-medium text-gray-700 text-sm">Description:</label>
                <p class="text-gray-900">{{ pkg.description }}</p>
              </div>
              <div>
                <label class="font-medium text-gray-700 text-sm">Declared Value:</label>
                <p class="text-gray-900">₱{{ pkg.value || '0.00' }}</p>
              </div>
            </div>

            <!-- Package Dimensions (Editable) -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <InputLabel value="Actual Length (cm) *" />
                <TextInput
                  v-model="pkg.actual_length"
                  type="number"
                  step="0.1"
                  min="0.1"
                  class="mt-1 block w-full"
                  :class="{ 'border-red-500': form.errors[`packages.${index}.actual_length`] }"
                  placeholder="0.0"
                />
                <p class="text-xs text-gray-500 mt-1">Original: {{ pkg.length }} cm</p>
                <InputError :message="form.errors[`packages.${index}.actual_length`]" />
              </div>

              <div>
                <InputLabel value="Actual Height (cm) *" />
                <TextInput
                  v-model="pkg.actual_height"
                  type="number"
                  step="0.1"
                  min="0.1"
                  class="mt-1 block w-full"
                  :class="{ 'border-red-500': form.errors[`packages.${index}.actual_height`] }"
                  placeholder="0.0"
                />
                <p class="text-xs text-gray-500 mt-1">Original: {{ pkg.height }} cm</p>
                <InputError :message="form.errors[`packages.${index}.actual_height`]" />
              </div>

              <div>
                <InputLabel value="Actual Width (cm) *" />
                <TextInput
                  v-model="pkg.actual_width"
                  type="number"
                  step="0.1"
                  min="0.1"
                  class="mt-1 block w-full"
                  :class="{ 'border-red-500': form.errors[`packages.${index}.actual_width`] }"
                  placeholder="0.0"
                />
                <p class="text-xs text-gray-500 mt-1">Original: {{ pkg.width }} cm</p>
                <InputError :message="form.errors[`packages.${index}.actual_width`]" />
              </div>

              <div>
                <InputLabel value="Actual Weight (kg) *" />
                <TextInput
                  v-model="pkg.actual_weight"
                  type="number"
                  step="0.1"
                  min="0.1"
                  class="mt-1 block w-full"
                  :class="{ 'border-red-500': form.errors[`packages.${index}.actual_weight`] }"
                  placeholder="0.0"
                />
                <p class="text-xs text-gray-500 mt-1">Original: {{ pkg.weight }} kg</p>
                <InputError :message="form.errors[`packages.${index}.actual_weight`]" />
              </div>
            </div>

            <!-- Volume Comparison -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <h5 class="font-medium text-blue-800 text-sm mb-2">Volume Comparison</h5>
              <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <span class="text-blue-600">Original Volume:</span>
                  <p class="font-semibold text-blue-800">{{ calculateVolume(pkg.length, pkg.height, pkg.width) }} m³</p>
                </div>
                <div>
                  <span class="text-green-600">Actual Volume:</span>
                  <p class="font-semibold text-green-800">{{ calculateVolume(pkg.actual_length || pkg.length, pkg.actual_height || pkg.height, pkg.actual_width || pkg.width) }} m³</p>
                </div>
              </div>
            </div>
          </div>

        <!-- Price Comparison -->
<div v-if="priceComparison" class="bg-green-50 border border-green-200 rounded-lg p-4 mt-6">
  <h4 class="text-md font-semibold text-green-800 mb-3">Price Adjustment</h4>
  
  <!-- Processing Fee Info -->
  <div class="bg-blue-50 border border-blue-200 rounded p-3 mb-4">
    <p class="text-sm text-blue-700">
      <strong>Note:</strong> All prices shown are net amounts after ₱200 processing fee deduction
    </p>
  </div>
  
  <div class="grid grid-cols-3 gap-4 text-sm">
    <div>
      <p class="text-green-600">Original Total (Net)</p>
      <p class="font-semibold text-green-800">₱{{ priceComparison.original_total }}</p>
      <p class="text-xs text-gray-500">Gross: ₱{{ (parseFloat(priceComparison.original_total) + 200).toFixed(2) }}</p>
    </div>
    <div>
      <p class="text-green-600">Adjusted Total (Net)</p>
      <p class="font-semibold text-green-800">₱{{ priceComparison.adjusted_total.toFixed(2) }}</p>
      <p class="text-xs text-gray-500">Gross: ₱{{ (parseFloat(priceComparison.adjusted_total) + 200).toFixed(2) }}</p>
    </div>
    <div>
      <p class="text-green-600">Net Difference</p>
      <p :class="priceComparison.difference >= 0 ? 'text-red-600' : 'text-blue-600'" class="font-semibold">
        {{ priceComparison.difference >= 0 ? '+' : '' }}₱{{ formatPriceDifference(priceComparison.difference) }}
      </p>
    </div>
  </div>
  
  <!-- Detailed Breakdown -->
  <div class="mt-4 pt-4 border-t border-green-200">
    <h5 class="font-medium text-green-800 text-sm mb-2">Detailed Breakdown (Gross Amounts)</h5>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
      <div>
        <p class="text-green-600">Base Fee</p>
        <p class="font-semibold text-green-800">₱{{ priceComparison.breakdown.base_fee }}</p>
      </div>
      <div>
        <p class="text-green-600">Volume Fee</p>
        <p class="font-semibold text-green-800">₱{{ priceComparison.breakdown.volume_fee }}</p>
      </div>
      <div>
        <p class="text-green-600">Weight Fee</p>
        <p class="font-semibold text-green-800">₱{{ priceComparison.breakdown.weight_fee }}</p>
      </div>
      <div>
        <p class="text-green-600">Package Fee</p>
        <p class="font-semibold text-green-800">₱{{ priceComparison.breakdown.package_fee }}</p>
      </div>
    </div>
    <div class="mt-2 text-xs text-gray-600">
      <p>Processing Fee: -₱200.00</p>
      <p class="font-semibold">Net Total: ₱{{ priceComparison.adjusted_total.toFixed(2) }}</p>
    </div>
  </div>
</div>

          <!-- Loading State -->
          <div v-if="isCalculating" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-6">
            <div class="flex items-center justify-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span class="text-yellow-700">Calculating price adjustment...</span>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end space-x-4 pt-6">
          <SecondaryButton @click="goBack" type="button">
            Cancel
          </SecondaryButton>
          <PrimaryButton type="submit" :disabled="form.processing || isCalculating">
            <span v-if="form.processing">Updating Packages...</span>
            <span v-else-if="isCalculating">Calculating...</span>
            <span v-else>Update Packages & Pricing</span>
          </PrimaryButton>
        </div>
      </form>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
  delivery: Object,
  status: String,
  success: String,
  error: String,
  csrf_token: String,
});

// Form data - ensure we're using the actual measurements
const form = useForm({
  _method: 'PUT',
  packages: props.delivery.packages?.map(pkg => ({
    id: pkg.id,
    item_name: pkg.item_name,
    description: pkg.description,
    category: pkg.category,
    // Original dimensions (read-only)
    length: parseFloat(pkg.length) || 0,
    height: parseFloat(pkg.height) || 0,
    width: parseFloat(pkg.width) || 0,
    weight: parseFloat(pkg.weight) || 0,
    value: parseFloat(pkg.value) || 0,
    // Actual measurements (editable) - use existing actual values or fallback to original
    actual_length: parseFloat(pkg.actual_length) || parseFloat(pkg.length) || 0,
    actual_height: parseFloat(pkg.actual_height) || parseFloat(pkg.height) || 0,
    actual_width: parseFloat(pkg.actual_width) || parseFloat(pkg.width) || 0,
    actual_weight: parseFloat(pkg.actual_weight) || parseFloat(pkg.weight) || 0,
  })) || [],
});

const priceComparison = ref(null);
const isCalculating = ref(false);

// Methods
const calculateVolume = (length, height, width) => {
  const volume = (length * height * width) / 1000000; // Convert to m³
  return volume.toFixed(4);
};

// Fix the floating point precision issue
const formatPriceDifference = (difference) => {
  // Round to 2 decimal places to avoid floating point precision issues
  return Math.abs(parseFloat(difference).toFixed(2));
};

const calculatePriceComparison = async () => {
  if (!form.packages || form.packages.length === 0) {
    return;
  }

  isCalculating.value = true;
  
  try {
    const response = await fetch(route('deliveries.calculate-approved-price'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': props.csrf_token,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        packages: form.packages.map(pkg => ({
          height: parseFloat(pkg.actual_height) || 0,
          width: parseFloat(pkg.actual_width) || 0,
          length: parseFloat(pkg.actual_length) || 0,
          weight: parseFloat(pkg.actual_weight) || 0,
        })),
        original_total: props.delivery.total_price,
        delivery_id: props.delivery.id,
        // Add processing fee information
        processing_fee: 200, // ₱200 processing fee
      }),
    });

    if (response.ok) {
      const data = await response.json();
      if (data.success) {
        const comparison = data.comparison;
        
        // Apply processing fee to both totals for display
        const originalTotalWithFee = parseFloat(props.delivery.total_price) + 200;
        const adjustedTotalWithFee = parseFloat(comparison.adjusted_total) + 200;
        
        // The actual difference should be between the net amounts (after fee)
        const actualDifference = comparison.adjusted_total - props.delivery.total_price;
        
        priceComparison.value = {
          original_total: props.delivery.total_price, // This is net after fee
          adjusted_total: comparison.adjusted_total, // This should also be net after fee
          difference: actualDifference,
          breakdown: comparison.breakdown,
          // Add these for display clarity
          original_gross: originalTotalWithFee,
          adjusted_gross: adjustedTotalWithFee,
          processing_fee: 200
        };
      } else {
        console.error('Price calculation failed:', data.error);
      }
    } else {
      console.error('Failed to calculate price - server error:', response.status);
    }
  } catch (error) {
    console.error('Error calculating price comparison:', error);
  } finally {
    isCalculating.value = false;
  }
};

const saveChanges = () => {
  console.log('Saving changes with data:', form.packages);
  
  // Ensure we're only sending the fields we want to update
  const submitData = {
    _method: 'PUT',
    packages: form.packages.map(pkg => ({
      id: pkg.id,
      actual_height: parseFloat(pkg.actual_height) || 0,
      actual_width: parseFloat(pkg.actual_width) || 0,
      actual_length: parseFloat(pkg.actual_length) || 0,
      actual_weight: parseFloat(pkg.actual_weight) || 0,
    }))
  };

  console.log('Submit data:', submitData);

  form.put(route('deliveries.approved.update', props.delivery.id), submitData, {
    preserveScroll: true,
    onSuccess: () => {
      console.log('Update successful');
      router.visit(route('deliveries.index'));
    },
    onError: (errors) => {
      console.log('Form errors:', errors);
    },
    onFinish: () => {
      console.log('Request finished');
    }
  });
};

const goBack = () => {
  router.visit(route('deliveries.index'));
};

// Watch for changes to recalculate price (with debounce)
let calculationTimeout = null;
watch(
  () => form.packages,
  () => {
    // Debounce the calculation to avoid too many API calls
    if (calculationTimeout) {
      clearTimeout(calculationTimeout);
    }
    calculationTimeout = setTimeout(() => {
      calculatePriceComparison();
    }, 500);
  },
  { deep: true }
);

onMounted(() => {
  // Calculate initial price comparison
  setTimeout(() => {
    calculatePriceComparison();
  }, 100);
});
</script>