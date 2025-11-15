<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Edit Delivery Request #DR-{{ String(delivery.id).padStart(6, '0') }}
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Update receiver information and package details
          </p>
        </div>

        <div class="flex gap-2">
          <SecondaryButton @click="goBack">
            Cancel
          </SecondaryButton>
          <PrimaryButton @click="saveChanges" :disabled="form.processing">
            <span v-if="form.processing">Saving...</span>
            <span v-else>Save Changes</span>
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

      <!-- Edit Form -->
      <form @submit.prevent="saveChanges" class="space-y-8">
        <!-- Read-only Information -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Request Information (Read-only)</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
              <label class="font-medium text-gray-700">Sender:</label>
              <p class="text-gray-900">{{ delivery.sender?.name || delivery.sender?.company_name }}</p>
            </div>
            <div>
              <label class="font-medium text-gray-700">Payment Method:</label>
              <p class="text-gray-900 capitalize">{{ delivery.payment_method }}</p>
            </div>
            <div>
              <label class="font-medium text-gray-700">Pick-up Region:</label>
              <p class="text-gray-900">{{ delivery.pick_up_region?.name }}</p>
            </div>
            <div>
              <label class="font-medium text-gray-700">Drop-off Region:</label>
              <p class="text-gray-900">{{ delivery.drop_off_region?.name }}</p>
            </div>
          </div>
        </div>

        <!-- Receiver Information Section -->
        <div class="bg-white shadow-sm rounded-lg p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Receiver Information</h3>
          
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Customer Category -->
            <div>
              <InputLabel value="Customer Category *" />
              <div class="grid grid-cols-2 gap-4 mt-2">
                <label
                  v-for="option in customerCategoryOptions"
                  :key="option.value"
                  :class="[
                    'border rounded-lg p-3 cursor-pointer transition-all duration-200 text-center',
                    form.receiver.customer_category === option.value
                      ? 'border-green-500 bg-green-50 ring-2 ring-green-200'
                      : 'border-gray-300 hover:border-gray-400'
                  ]"
                >
                  <input
                    type="radio"
                    v-model="form.receiver.customer_category"
                    :value="option.value"
                    class="sr-only"
                  />
                  <span class="text-sm font-medium text-gray-900">{{ option.label }}</span>
                </label>
              </div>
              <InputError :message="form.errors['receiver.customer_category']" />
            </div>

            <!-- Company Name (Conditional) -->
            <div v-if="form.receiver.customer_category === 'company'">
              <InputLabel value="Company Name *" />
              <TextInput
                v-model="form.receiver.company_name"
                type="text"
                class="mt-1 block w-full"
                :class="{ 'border-red-500': form.errors['receiver.company_name'] }"
                placeholder="Enter company name"
              />
              <InputError :message="form.errors['receiver.company_name']" />
            </div>
          </div>

          <!-- Name Fields -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
            <div>
              <InputLabel value="First Name *" />
              <TextInput
                v-model="form.receiver.first_name"
                type="text"
                class="mt-1 block w-full"
                :class="{ 'border-red-500': form.errors['receiver.first_name'] }"
                placeholder="Enter first name"
              />
              <InputError :message="form.errors['receiver.first_name']" />
            </div>
            
            <div>
              <InputLabel value="Middle Name" />
              <TextInput
                v-model="form.receiver.middle_name"
                type="text"
                class="mt-1 block w-full"
                placeholder="Enter middle name"
              />
              <InputError :message="form.errors['receiver.middle_name']" />
            </div>
            
            <div>
              <InputLabel value="Last Name *" />
              <TextInput
                v-model="form.receiver.last_name"
                type="text"
                class="mt-1 block w-full"
                :class="{ 'border-red-500': form.errors['receiver.last_name'] }"
                placeholder="Enter last name"
              />
              <InputError :message="form.errors['receiver.last_name']" />
            </div>
          </div>

          <!-- Contact Information -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div>
              <InputLabel value="Email Address *" />
              <TextInput
                v-model="form.receiver.email"
                type="email"
                class="mt-1 block w-full"
                :class="{ 'border-red-500': form.errors['receiver.email'] }"
                placeholder="receiver.email@example.com"
              />
              <InputError :message="form.errors['receiver.email']" />
            </div>

            <div>
              <InputLabel value="Mobile Number *" />
              <TextInput
                v-model="form.receiver.mobile"
                type="tel"
                class="mt-1 block w-full"
                :class="{ 'border-red-500': form.errors['receiver.mobile'] }"
                placeholder="09123456789"
              />
              <InputError :message="form.errors['receiver.mobile']" />
            </div>
          </div>

          <!-- Address Information -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-6">
            <div>
              <InputLabel value="Building Number" />
              <TextInput
                v-model="form.receiver.building_number"
                type="text"
                class="mt-1 block w-full"
                placeholder="e.g., 123"
              />
              <InputError :message="form.errors['receiver.building_number']" />
            </div>

            <div>
              <InputLabel value="Street *" />
              <TextInput
                v-model="form.receiver.street"
                type="text"
                class="mt-1 block w-full"
                :class="{ 'border-red-500': form.errors['receiver.street'] }"
                placeholder="e.g., Main Street"
              />
              <InputError :message="form.errors['receiver.street']" />
            </div>

            <div>
              <InputLabel value="Barangay" />
              <TextInput
                v-model="form.receiver.barangay"
                type="text"
                class="mt-1 block w-full"
                placeholder="Enter barangay"
              />
              <InputError :message="form.errors['receiver.barangay']" />
            </div>

            <div>
              <InputLabel value="City *" />
              <TextInput
                v-model="form.receiver.city"
                type="text"
                class="mt-1 block w-full"
                :class="{ 'border-red-500': form.errors['receiver.city'] }"
                placeholder="Enter city"
              />
              <InputError :message="form.errors['receiver.city']" />
            </div>

            <div>
              <InputLabel value="Province *" />
              <TextInput
                v-model="form.receiver.province"
                type="text"
                class="mt-1 block w-full"
                :class="{ 'border-red-500': form.errors['receiver.province'] }"
                placeholder="Enter province"
              />
              <InputError :message="form.errors['receiver.province']" />
            </div>

            <div>
              <InputLabel value="ZIP Code *" />
              <TextInput
                v-model="form.receiver.zip_code"
                type="text"
                class="mt-1 block w-full"
                :class="{ 'border-red-500': form.errors['receiver.zip_code'] }"
                placeholder="e.g., 1000"
                maxlength="4"
              />
              <InputError :message="form.errors['receiver.zip_code']" />
            </div>
          </div>

          <!-- Notes -->
          <div class="mt-6">
            <InputLabel value="Notes (Optional)" />
            <TextArea
              v-model="form.receiver.notes"
              class="mt-1 block w-full"
              :rows="3"
              placeholder="Additional notes or special instructions..."
            />
            <InputError :message="form.errors['receiver.notes']" />
          </div>
        </div>

        <!-- Packages Information Section -->
        <div class="bg-white shadow-sm rounded-lg p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Package Information</h3>
            <SecondaryButton @click="addPackage" type="button" class="text-sm">
              + Add Package
            </SecondaryButton>
          </div>

          <!-- Package List -->
          <div v-for="(pkg, index) in form.packages" :key="index" class="border border-gray-200 rounded-lg p-6 space-y-4 mb-6">
            <div class="flex items-center justify-between">
              <h4 class="text-md font-medium text-gray-900">Package {{ index + 1 }}</h4>
              <button
                v-if="form.packages.length > 1"
                @click="removePackage(index)"
                type="button"
                class="text-red-600 hover:text-red-700 text-sm font-medium"
              >
                Remove
              </button>
            </div>

            <!-- Package Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <InputLabel value="Item Name *" />
                <TextInput
                  v-model="pkg.item_name"
                  type="text"
                  class="mt-1 block w-full"
                  :class="{ 'border-red-500': form.errors[`packages.${index}.item_name`] }"
                  placeholder="e.g., Documents, Electronics, Clothes"
                />
                <InputError :message="form.errors[`packages.${index}.item_name`]" />
              </div>

              <div>
                <InputLabel value="Description" />
                <TextInput
                  v-model="pkg.description"
                  type="text"
                  class="mt-1 block w-full"
                  placeholder="Item description"
                />
                <InputError :message="form.errors[`packages.${index}.description`]" />
              </div>
            </div>

            <!-- Package Dimensions -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <InputLabel value="Length (cm) *" />
                <TextInput
                  v-model="pkg.length"
                  type="number"
                  step="0.1"
                  min="0.1"
                  class="mt-1 block w-full"
                  :class="{ 'border-red-500': form.errors[`packages.${index}.length`] }"
                  placeholder="0.0"
                />
                <InputError :message="form.errors[`packages.${index}.length`]" />
              </div>

              <div>
                <InputLabel value="Height (cm) *" />
                <TextInput
                  v-model="pkg.height"
                  type="number"
                  step="0.1"
                  min="0.1"
                  class="mt-1 block w-full"
                  :class="{ 'border-red-500': form.errors[`packages.${index}.height`] }"
                  placeholder="0.0"
                />
                <InputError :message="form.errors[`packages.${index}.height`]" />
              </div>

              <div>
                <InputLabel value="Width (cm) *" />
                <TextInput
                  v-model="pkg.width"
                  type="number"
                  step="0.1"
                  min="0.1"
                  class="mt-1 block w-full"
                  :class="{ 'border-red-500': form.errors[`packages.${index}.width`] }"
                  placeholder="0.0"
                />
                <InputError :message="form.errors[`packages.${index}.width`]" />
              </div>

              <div>
                <InputLabel value="Weight (kg) *" />
                <TextInput
                  v-model="pkg.weight"
                  type="number"
                  step="0.1"
                  min="0.1"
                  class="mt-1 block w-full"
                  :class="{ 'border-red-500': form.errors[`packages.${index}.weight`] }"
                  placeholder="0.0"
                />
                <InputError :message="form.errors[`packages.${index}.weight`]" />
              </div>
            </div>

            <!-- Package Value and Category -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <InputLabel value="Category *" />
                <SelectInput
                  v-model="pkg.category"
                  class="mt-1 block w-full"
                  :class="{ 'border-red-500': form.errors[`packages.${index}.category`] }"
                  :options="categoryOptions"
                />
                <InputError :message="form.errors[`packages.${index}.category`]" />
              </div>

              <div>
                <InputLabel value="Value (₱)" />
                <TextInput
                  v-model="pkg.value"
                  type="number"
                  step="0.01"
                  min="0"
                  class="mt-1 block w-full"
                  placeholder="0.00"
                />
                <InputError :message="form.errors[`packages.${index}.value`]" />
              </div>
            </div>
          </div>

          <!-- Price Preview -->
          <div v-if="priceBreakdown" class="bg-green-50 border border-green-200 rounded-lg p-4 mt-6">
            <h4 class="text-md font-semibold text-green-800 mb-3">Updated Price Estimate</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
              <div>
                <p class="text-green-600">Base Fee</p>
                <p class="font-semibold text-green-800">₱{{ priceBreakdown.base_fee }}</p>
              </div>
              <div>
                <p class="text-green-600">Volume Fee</p>
                <p class="font-semibold text-green-800">₱{{ priceBreakdown.volume_fee }}</p>
              </div>
              <div>
                <p class="text-green-600">Weight Fee</p>
                <p class="font-semibold text-green-800">₱{{ priceBreakdown.weight_fee }}</p>
              </div>
              <div>
                <p class="text-green-600">Package Fee</p>
                <p class="font-semibold text-green-800">₱{{ priceBreakdown.package_fee }}</p>
              </div>
            </div>
            <div class="mt-3 pt-3 border-t border-green-200">
              <div class="flex justify-between items-center">
                <p class="text-lg font-semibold text-green-800">Total Estimated Cost</p>
                <p class="text-xl font-bold text-green-800">₱{{ priceBreakdown.total_price }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-end space-x-4 pt-6">
          <SecondaryButton @click="goBack" type="button">
            Cancel
          </SecondaryButton>
          <PrimaryButton type="submit" :disabled="form.processing">
            <span v-if="form.processing">Saving Changes...</span>
            <span v-else>Save Changes</span>
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
import TextArea from '@/Components/TextArea.vue';
import SelectInput from '@/Components/SelectInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
  delivery: Object,
  regions: Array,
  categories: Array,
  priceMatrix: Object,
  status: String,
  success: String,
  error: String,
});

// Format categories for select input
const categoryOptions = computed(() => {
  return props.categories.map(category => ({
    value: category,
    text: category.charAt(0).toUpperCase() + category.slice(1)
  }));
});

// Form data - ensure proper initialization
const form = useForm({
  _method: 'PUT', // Important for Laravel to recognize PUT request
  receiver: {
    customer_category: props.delivery.receiver?.customer_category || 'individual',
    first_name: props.delivery.receiver?.first_name || '',
    middle_name: props.delivery.receiver?.middle_name || '',
    last_name: props.delivery.receiver?.last_name || '',
    company_name: props.delivery.receiver?.company_name || '',
    email: props.delivery.receiver?.email || '',
    mobile: props.delivery.receiver?.mobile || '',
    phone: props.delivery.receiver?.phone || '',
    building_number: props.delivery.receiver?.building_number || '',
    street: props.delivery.receiver?.street || '',
    barangay: props.delivery.receiver?.barangay || '',
    city: props.delivery.receiver?.city || '',
    province: props.delivery.receiver?.province || '',
    zip_code: props.delivery.receiver?.zip_code || '',
    notes: props.delivery.receiver?.notes || '',
  },
  packages: props.delivery.packages?.map(pkg => ({
    id: pkg.id || null,
    item_name: pkg.item_name || '',
    description: pkg.description || '',
    category: pkg.category || '',
    height: parseFloat(pkg.height) || 0,
    width: parseFloat(pkg.width) || 0,
    length: parseFloat(pkg.length) || 0,
    weight: parseFloat(pkg.weight) || 0,
    value: parseFloat(pkg.value) || 0,
    photo_url: pkg.photo_url || '', // Handle single photo URL
  })) || [],
  pick_up_region_id: props.delivery.pick_up_region_id,
  drop_off_region_id: props.delivery.drop_off_region_id,
  payment_method: props.delivery.payment_method,
});

const priceBreakdown = ref(null);

// Options
const customerCategoryOptions = [
  { value: 'individual', label: 'Individual' },
  { value: 'company', label: 'Company' },
];

// Methods
const addPackage = () => {
  form.packages.push({
    item_name: '',
    description: '',
    category: '',
    height: 0,
    width: 0,
    length: 0,
    weight: 0,
    value: 0,
    photo_url: '',
  });
};

const removePackage = (index) => {
  if (form.packages.length > 1) {
    form.packages.splice(index, 1);
    calculatePrice();
  }
};

const calculatePrice = () => {
  if (!props.priceMatrix) return;

  const baseFee = parseFloat(props.priceMatrix.base_fee) || 0;
  let volumeFee = 0;
  let weightFee = 0;
  let packageFee = 0;

  form.packages.forEach(pkg => {
    const height = parseFloat(pkg.height) || 0;
    const width = parseFloat(pkg.width) || 0;
    const length = parseFloat(pkg.length) || 0;
    const weight = parseFloat(pkg.weight) || 0;

    // Calculate volume in cubic meters (convert cm to m)
    const volume = (height / 100) * (width / 100) * (length / 100);
    
    volumeFee += volume * (parseFloat(props.priceMatrix.volume_rate) || 0);
    weightFee += weight * (parseFloat(props.priceMatrix.weight_rate) || 0);
    packageFee += parseFloat(props.priceMatrix.package_rate) || 0;
  });

  const totalPrice = baseFee + volumeFee + weightFee + packageFee;

  priceBreakdown.value = {
    base_fee: roundToTwo(baseFee),
    volume_fee: roundToTwo(volumeFee),
    weight_fee: roundToTwo(weightFee),
    package_fee: roundToTwo(packageFee),
    total_price: roundToTwo(totalPrice),
  };
};

const roundToTwo = (num) => {
  return Math.round((num + Number.EPSILON) * 100) / 100;
};

const saveChanges = () => {
  // Remove photo_url from packages before sending to server
  const formData = {
    ...form.data(),
    packages: form.packages.map(pkg => {
      const { photo_url, ...packageData } = pkg;
      return packageData;
    })
  };

  form.put(route('deliveries.update', props.delivery.id), formData, {
    preserveScroll: true,
    onSuccess: () => {
      router.visit(route('deliveries.pending'));
    },
    onError: (errors) => {
      console.log('Form errors:', errors);
    }
  });
};

const goBack = () => {
  router.visit(route('deliveries.pending'));
};

// Watch for changes to recalculate price
watch(
  () => form.packages,
  () => {
    calculatePrice();
  },
  { deep: true }
);

onMounted(() => {
  calculatePrice();
});
</script>