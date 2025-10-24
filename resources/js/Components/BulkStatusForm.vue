<template>
  <div class="space-y-4">
    <!-- Selected Packages Summary -->
    <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg">
      <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">
        Selected Packages ({{ packages.length }})
      </h4>
      <div class="max-h-48 overflow-y-auto space-y-2">
        <div 
          v-for="pkg in packages" 
          :key="pkg.id"
          class="text-xs bg-white dark:bg-gray-800 p-2 rounded border border-blue-100 dark:border-blue-800"
        >
          <!-- Package Header -->
          <div class="flex items-center justify-between mb-1">
            <span class="font-semibold text-blue-700 dark:text-blue-300 truncate">
              {{ pkg.item_code }}
            </span>
            <span class="text-blue-600 dark:text-blue-400 ml-2 text-xs">
              {{ pkg.deliveryRequest?.reference_number }}
            </span>
          </div>
          
          <!-- Package Details -->
          <div class="grid grid-cols-2 gap-x-2 gap-y-1 text-gray-600 dark:text-gray-400">
            <div class="truncate">
              <span class="font-medium">Name:</span> {{ pkg.item_name }}
            </div>
            <div>
              <span class="font-medium">Category:</span> {{ pkg.category || 'N/A' }}
            </div>
            <div>
              <span class="font-medium">Weight:</span> {{ formatWeight(pkg.weight) }}
            </div>
            <div>
              <span class="font-medium">Volume:</span> {{ formatVolume(pkg.volume) }}
            </div>
          </div>
          
          <!-- Current Location -->
          <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            <span class="font-medium">Current:</span> {{ pkg.current_region?.name }}
            <span class="mx-1">•</span>
            <span class="font-medium">Destination:</span> {{ pkg.deliveryRequest?.dropOffRegion }}
          </div>
        </div>
      </div>
    </div>

    <!-- Status Selection -->
    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Status for All Selected Packages *
      </label>
      <select v-model="form.status" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
        <option value="delivered">✅ Delivered - Packages successfully delivered</option>
        <option value="damaged_in_transit">⚠️ Damaged - Packages arrived but damaged</option>
        <option value="lost_in_transit">❌ Lost - Packages missing/lost in transit</option>
      </select>
    </div>

    <!-- Remarks -->
    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Remarks (Applies to all packages)
      </label>
      <textarea 
        v-model="form.remarks" 
        placeholder="Add notes about the packages' condition..."
        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm"
        rows="3"
      ></textarea>
    </div>

    <!-- Evidence Upload -->
    <div v-if="form.status !== 'delivered'">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Evidence Photos (Applies to all packages)
      </label>
      <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center">
        <input 
          type="file" 
          ref="fileInput"
          multiple 
          accept="image/*"
          @change="handleFileUpload"
          class="hidden"
        />
        <button 
          @click="$refs.fileInput.click()"
          class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm"
        >
          <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
          Upload Photos
        </button>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
          Upload photos as evidence for damaged or lost packages
        </p>
      </div>
      
      <!-- Uploaded Files Preview -->
      <div v-if="uploadedFiles.length > 0" class="mt-3 space-y-2">
        <div v-for="(file, index) in uploadedFiles" :key="index" class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 p-2 rounded">
          <span class="text-xs text-gray-600 dark:text-gray-300 truncate">{{ file.name }}</span>
          <button @click="removeFile(index)" class="text-red-500 hover:text-red-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-3 pt-4">
      <SecondaryButton @click="$emit('cancel')" class="flex-1 text-sm">
        Cancel
      </SecondaryButton>
      <PrimaryButton 
        @click="submit" 
        :disabled="submitting"
        class="flex-1 text-sm"
      >
        <span v-if="submitting">Updating {{ packages.length }} packages...</span>
        <span v-else>Update All Packages</span>
      </PrimaryButton>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
  packageIds: {
    type: Array,
    required: true
  },
  packages: {
    type: Array,
    required: true
  }
});

const emit = defineEmits(['submitted', 'cancel']);

const submitting = ref(false);
const uploadedFiles = ref([]);

const form = reactive({
  status: 'delivered',
  remarks: '',
  evidence: []
});

function handleFileUpload(event) {
  const files = Array.from(event.target.files);
  uploadedFiles.value = [...uploadedFiles.value, ...files];
}

function removeFile(index) {
  uploadedFiles.value.splice(index, 1);
}

function formatWeight(weight) {
  if (!weight || weight === 0) return 'N/A';
  return `${weight} kg`;
}

function formatVolume(volume) {
  if (!volume || volume === 0) return 'N/A';
  return `${volume} m³`;
}

async function submit() {
  submitting.value = true;

  try {
    const formData = new FormData();
    
    // Format data for the backend endpoint - FIXED VERSION
    props.packageIds.forEach((packageId, index) => {
      formData.append(`package_updates[${index}][package_id]`, packageId);
      formData.append(`package_updates[${index}][status]`, form.status);
      formData.append(`package_updates[${index}][remarks]`, form.remarks || '');
    });
    
    // Add evidence files once for all packages
    uploadedFiles.value.forEach((file, fileIndex) => {
      formData.append(`evidence[${fileIndex}]`, file);
    });

    // Add the count of packages for backend validation
    formData.append('package_count', props.packageIds.length);

    await router.post(route('driver.packages.update-destination-status'), formData, {
      forceFormData: true,
      onFinish: () => {
        submitting.value = false;
        emit('submitted');
      },
      onError: (errors) => {
        console.error('Bulk update errors:', errors);
        submitting.value = false;
      }
    });

  } catch (error) {
    console.error('Error updating bulk status:', error);
    submitting.value = false;
  }
}
</script>