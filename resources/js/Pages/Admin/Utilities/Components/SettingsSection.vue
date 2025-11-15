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

    <!-- Package Categories Management -->
    <div class="bg-white shadow rounded-lg border border-gray-200">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-lg font-medium text-gray-900">Package Categories</h3>
            <p class="mt-1 text-sm text-gray-500">
              Manage package types and their default dimensions
            </p>
          </div>
          <PrimaryButton
            @click="showAddCategoryModal = true"
            class="px-4 py-2"
          >
            + Add Category
          </PrimaryButton>
        </div>
      </div>
      <div class="px-6 py-4">
        <!-- Categories Information -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-green-600" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-green-800">
                Package Categories Guide
              </h3>
              <div class="mt-2 text-sm text-green-700 space-y-2">
                <p><strong>Categories define the package types customers can select in delivery requests.</strong></p>
                <div class="space-y-1">
                  <p>• <strong>Name</strong>: Display name shown to customers</p>
                  <p>• <strong>Code</strong>: Unique identifier used in the system</p>
                  <p>• <strong>Dimensions</strong>: Default measurements (in centimeters)</p>
                  <p>• <strong>Image</strong>: Visual representation of the package type</p>
                </div>
                <p class="text-xs"><strong>Note:</strong> Categories with dimensions will auto-fill measurements. "Custom Size" allows manual entry.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Categories List -->
        <div v-if="categoriesLoading" class="text-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mx-auto"></div>
          <p class="mt-2 text-sm text-gray-500">Loading categories...</p>
        </div>

        <div v-else-if="packageCategories.length === 0" class="text-center py-8 border-2 border-dashed border-gray-300 rounded-lg">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No package categories</h3>
          <p class="mt-1 text-sm text-gray-500">Get started by creating your first package category.</p>
          <div class="mt-6">
            <PrimaryButton @click="showAddCategoryModal = true">
              + Add Category
            </PrimaryButton>
          </div>
        </div>

        <div v-else class="space-y-4">
          <!-- Sortable Categories List -->
          <div 
            v-for="category in packageCategories" 
            :key="category.id"
            class="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition-colors duration-200"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-4 flex-1">
                <!-- Drag Handle -->
                <button 
                  type="button"
                  class="cursor-move text-gray-400 hover:text-gray-600"
                  @mousedown="startDrag(category)"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                  </svg>
                </button>

                <!-- Category Image -->
                <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                  <img 
                    v-if="category.image_url" 
                    :src="category.image_url" 
                    :alt="category.name"
                    class="w-10 h-10 object-contain"
                  >
                  <svg v-else class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                  </svg>
                </div>

                <!-- Category Details -->
                <div class="flex-1">
                  <div class="flex items-center space-x-2">
                    <h4 class="text-lg font-medium text-gray-900">{{ category.name }}</h4>
                    <span 
                      :class="[
                        'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                        category.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                      ]"
                    >
                      {{ category.is_active ? 'Active' : 'Inactive' }}
                    </span>
                  </div>
                  <p class="text-sm text-gray-500 mt-1">{{ category.description }}</p>
                  <div class="flex items-center space-x-4 mt-2 text-xs text-gray-600">
                    <span><strong>Code:</strong> {{ category.code }}</span>
                    <span v-if="category.dimensions?.length">
                      <strong>Dimensions:</strong> L{{ category.dimensions.length }} × H{{ category.dimensions.height }} × W{{ category.dimensions.width }}cm
                    </span>
                    <span v-else class="text-orange-600">Custom dimensions</span>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex items-center space-x-2">
                <button
                  @click="editCategory(category)"
                  class="text-indigo-600 hover:text-indigo-900 p-2 rounded-lg hover:bg-indigo-50 transition-colors duration-200"
                  title="Edit category"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button
                  @click="deleteCategory(category)"
                  class="text-red-600 hover:text-red-900 p-2 rounded-lg hover:bg-red-50 transition-colors duration-200"
                  title="Delete category"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Save Order Button -->
          <div v-if="hasOrderChanged" class="flex justify-end pt-4 border-t border-gray-200">
            <PrimaryButton
              @click="saveCategoryOrder"
              :disabled="orderLoading"
              class="px-4 py-2"
            >
              <span v-if="orderLoading">Saving Order...</span>
              <span v-else>Save Category Order</span>
            </PrimaryButton>
          </div>
        </div>
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

    <!-- Add/Edit Category Modal -->
    <div v-if="showAddCategoryModal || showEditCategoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-4 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            {{ showEditCategoryModal ? 'Edit Package Category' : 'Add Package Category' }}
          </h3>
          
          <form @submit.prevent="submitCategoryForm" class="space-y-4">
            <!-- Name -->
            <div>
              <InputLabel value="Category Name *" />
              <TextInput
                v-model="categoryForm.name"
                type="text"
                class="mt-1 block w-full"
                placeholder="e.g., Small Box, Large Sack"
                required
              />
            </div>

            <!-- Code - Changed to Dropdown -->
            <div>
              <InputLabel value="Category Code *" />
              <SelectInput
                v-model="categoryForm.code"
                :options="categoryCodeOptions"
                option-value="value"
                option-label="label"
                placeholder="Select category code"
                class="mt-1 block w-full"
                required
              />
              <p class="text-xs text-gray-500 mt-1">Unique identifier for the package type</p>
            </div>

            <!-- Description -->
            <div>
              <InputLabel value="Description" />
             <TextArea
  v-model="categoryForm.description"
  class="mt-1 block w-full"
  placeholder="Brief description of this package type..."
  :rows="3"
/>
            </div>

            <!-- Dimensions -->
            <div class="grid grid-cols-3 gap-3">
              <div>
                <InputLabel value="Length (cm)" />
                <TextInput
                  v-model="categoryForm.length"
                  type="number"
                  step="0.1"
                  min="0"
                  class="mt-1 block w-full"
                  placeholder="0.0"
                />
              </div>
              <div>
                <InputLabel value="Height (cm)" />
                <TextInput
                  v-model="categoryForm.height"
                  type="number"
                  step="0.1"
                  min="0"
                  class="mt-1 block w-full"
                  placeholder="0.0"
                />
              </div>
              <div>
                <InputLabel value="Width (cm)" />
                <TextInput
                  v-model="categoryForm.width"
                  type="number"
                  step="0.1"
                  min="0"
                  class="mt-1 block w-full"
                  placeholder="0.0"
                />
              </div>
            </div>

            <!-- Image Upload -->
            <div>
              <InputLabel value="Category Image" />
              <input
                type="file"
                @change="handleImageUpload"
                accept="image/*"
                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
              />
              <p class="text-xs text-gray-500 mt-1">Optional: Upload an image representing this package type</p>
            </div>

            <!-- Active Status -->
            <div class="flex items-center">
              <input
                v-model="categoryForm.is_active"
                type="checkbox"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
              <InputLabel value="Active Category" class="ml-2" />
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-3 pt-4">
              <SecondaryButton
                type="button"
                @click="closeCategoryModal"
                class="px-4 py-2"
              >
                Cancel
              </SecondaryButton>
              <PrimaryButton
                type="submit"
                :disabled="categoryLoading"
                class="px-4 py-2"
              >
                <span v-if="categoryLoading">Saving...</span>
                <span v-else>{{ showEditCategoryModal ? 'Update' : 'Create' }} Category</span>
              </PrimaryButton>
            </div>
          </form>
        </div>
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
import TextInput from '@/Components/TextInput.vue';
import TextArea from '@/Components/TextArea.vue';
// Use relative path
import { usePreferences } from '../../../../stores/preferences.js';

const { preferences, saveGlobalPreferences, applyGlobalPreferences } = usePreferences();
const loading = ref(false);
const priceLoading = ref(false);
const categoriesLoading = ref(false);
const categoryLoading = ref(false);
const orderLoading = ref(false);

// Package Categories
const packageCategories = ref([]);
const originalOrder = ref([]);
const showAddCategoryModal = ref(false);
const showEditCategoryModal = ref(false);

// Category Form
const categoryForm = reactive({
  name: '',
  code: '',
  description: '',
  length: '',
  height: '',
  width: '',
  is_active: true,
  image: null
});

const editingCategoryId = ref(null);

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

// Category Code Options - Using the new container presets structure
const categoryCodeOptions = computed(() => [
  { value: 'piece', label: 'Piece' },
  { value: 'carton', label: 'Carton' },
  { value: 'sack', label: 'Sack' },
  { value: 'roll', label: 'Roll' },
  { value: 'bundle', label: 'Bundle' },
  { value: 'custom', label: 'Custom Size' }
]);

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

// Check if order has changed
const hasOrderChanged = computed(() => {
  return JSON.stringify(packageCategories.value.map(c => c.id)) !== JSON.stringify(originalOrder.value);
});

// Load data
onMounted(async () => {
  // Load current preferences when component mounts
  Object.assign(preferencesForm, preferences.value);

  // Load price matrix from props
  if (props.priceMatrix) {
    Object.assign(priceForm, props.priceMatrix);
  }

  // Load package categories
  await loadPackageCategories();
});

// Load Package Categories
const loadPackageCategories = async () => {
  categoriesLoading.value = true;
  try {
    const response = await fetch('/admin/utilities/package-categories');
    const data = await response.json();
    packageCategories.value = data.categories || [];
    originalOrder.value = packageCategories.value.map(c => c.id);
  } catch (error) {
    console.error('Error loading package categories:', error);
  } finally {
    categoriesLoading.value = false;
  }
};

// Price Matrix Methods - ADDED PAGE RELOAD
const updatePriceMatrix = () => {
  priceLoading.value = true;
  router.post('/admin/utilities/price-matrix', priceForm, {
    preserveScroll: true,
    onSuccess: () => {
      // Force page reload to show changes
      window.location.reload();
    },
    onFinish: () => {
      priceLoading.value = false;
    },
  });
};

// Font Preferences Methods - ADDED PAGE RELOAD
const savePreferences = async () => {
  loading.value = true;
  
  try {
    await saveGlobalPreferences(preferencesForm);
    // Force page reload to show changes
    window.location.reload();
  } catch (error) {
    console.error('Error saving preferences:', error);
  } finally {
    loading.value = false;
  }
};

const getCsrfToken = () => {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
         document.querySelector('input[name="_token"]')?.value;
};

// Reset to default - ADDED PAGE RELOAD
const resetToDefault = () => {
  preferencesForm.font_style = 'inter';
  preferencesForm.font_size = 'medium';
  savePreferences();
};

// Package Category Methods
const startDrag = (category) => {
  // Simple drag implementation - you might want to use a proper drag library
  console.log('Start dragging:', category.name);
};

const editCategory = (category) => {
  Object.assign(categoryForm, {
    name: category.name,
    code: category.code,
    description: category.description || '',
    length: category.dimensions?.length || '',
    height: category.dimensions?.height || '',
    width: category.dimensions?.width || '',
    is_active: category.is_active,
    image: null
  });
  editingCategoryId.value = category.id;
  showEditCategoryModal.value = true;
};

// Delete Category - ADDED PAGE RELOAD
const deleteCategory = async (category) => {
  if (!confirm(`Are you sure you want to delete "${category.name}"? This action cannot be undone.`)) {
    return;
  }

  try {
    // Use Inertia's post method for delete with _method
    router.post(`/admin/utilities/package-categories/${category.id}/delete`, {}, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        // Force page reload to show changes
        window.location.reload();
      },
      onError: (errors) => {
        alert('Failed to delete category: ' + (errors.message || 'Unknown error'));
      }
    });
  } catch (error) {
    console.error('Error deleting category:', error);
    alert('Error deleting category: ' + error.message);
  }
};

const handleImageUpload = (event) => {
  const file = event.target.files[0];
  if (file) {
    categoryForm.image = file;
  }
};

// Submit Category Form - ADDED PAGE RELOAD
const submitCategoryForm = async () => {
  categoryLoading.value = true;

  const formData = {
    name: categoryForm.name,
    code: categoryForm.code, // No need to uppercase since it's from dropdown
    description: categoryForm.description,
    length: categoryForm.length || null,
    height: categoryForm.height || null,
    width: categoryForm.width || null,
    is_active: categoryForm.is_active,
    image: categoryForm.image
  };

  const url = showEditCategoryModal.value 
    ? `/admin/utilities/package-categories/${editingCategoryId.value}`
    : '/admin/utilities/package-categories';

  try {
    if (showEditCategoryModal.value) {
      // Use POST with _method for update
      router.post(url, { ...formData, _method: 'PUT' }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          // Force page reload to show changes
          window.location.reload();
        },
        onError: (errors) => {
          console.error('Form errors:', errors);
          const errorMessages = Object.values(errors).flat().join(', ');
          alert('Failed to save category: ' + errorMessages);
        },
        onFinish: () => {
          categoryLoading.value = false;
        }
      });
    } else {
      // For create, use regular POST
      router.post(url, formData, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          // Force page reload to show changes
          window.location.reload();
        },
        onError: (errors) => {
          console.error('Form errors:', errors);
          const errorMessages = Object.values(errors).flat().join(', ');
          alert('Failed to save category: ' + errorMessages);
        },
        onFinish: () => {
          categoryLoading.value = false;
        }
      });
    }
  } catch (error) {
    console.error('Error saving category:', error);
    alert('Error saving category: ' + error.message);
    categoryLoading.value = false;
  }
};

const closeCategoryModal = () => {
  showAddCategoryModal.value = false;
  showEditCategoryModal.value = false;
  editingCategoryId.value = null;
  
  // Reset form
  Object.assign(categoryForm, {
    name: '',
    code: '',
    description: '',
    length: '',
    height: '',
    width: '',
    is_active: true,
    image: null
  });
};

// Save Category Order - ADDED PAGE RELOAD
const saveCategoryOrder = async () => {
  orderLoading.value = true;
  
  try {
    router.post('/admin/utilities/package-categories/reorder', {
      categories: packageCategories.value.map((category, index) => ({
        id: category.id,
        sort_order: index
      }))
    }, {
      preserveScroll: true,
      onSuccess: () => {
        // Force page reload to show changes
        window.location.reload();
      },
      onError: (errors) => {
        alert('Failed to save order: ' + (errors.message || 'Unknown error'));
      },
      onFinish: () => {
        orderLoading.value = false;
      }
    });
  } catch (error) {
    console.error('Error saving category order:', error);
    alert('Error saving category order');
    orderLoading.value = false;
  }
};

// Define props
const props = defineProps({
  priceMatrix: Object
});
</script>