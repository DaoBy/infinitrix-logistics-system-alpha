<template>
  <div class="space-y-4">
    <!-- Package Info - Mobile Optimized -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-3 sm:p-4 rounded-lg">
      <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2 sm:mb-3">
        Package Details
      </h4>
      <div class="space-y-2 sm:space-y-3">
        <div class="text-xs sm:text-sm text-gray-700 dark:text-gray-300">
          <div class="flex items-center justify-between mb-2">
            <span class="font-semibold text-base">{{ package.item_code }}</span>
          </div>
          
          <!-- Mobile: Stack layout, Desktop: Grid layout -->
          <div class="sm:grid sm:grid-cols-2 sm:gap-x-3 sm:gap-y-2 space-y-2 sm:space-y-0">
            <div class="truncate">
              <span class="font-medium">Name:</span> {{ package.item_name }}
            </div>
            <div>
              <span class="font-medium">Category:</span> {{ package.category || 'General' }}
            </div>
            <div>
              <span class="font-medium">Weight:</span> {{ formatWeight(package.weight) }}
            </div>
            <div>
              <span class="font-medium">Volume:</span> {{ formatVolume(package.volume) }}
            </div>
          </div>
          
          <div class="mt-3 text-xs text-gray-600 dark:text-gray-400 space-y-1 sm:space-y-0 sm:flex sm:items-center">          
          </div>
        </div>
      </div>
    </div>

    <!-- Status Selection -->
    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Package Status *
      </label>
      <select v-model="form.status" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm px-3 py-2 sm:py-2.5">
        <option value="delivered">✅ Delivered - Package successfully delivered</option>
        <option value="damaged_in_transit">⚠️ Damaged - Package arrived but damaged</option>
        <option value="lost_in_transit">❌ Lost - Package missing/lost in transit</option>
      </select>
    </div>

    <!-- Remarks -->
    <div>
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Remarks
      </label>
      <textarea 
        v-model="form.remarks" 
        placeholder="Add any notes about the package condition..."
        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm px-3 py-2"
        rows="3"
      ></textarea>
    </div>

    <!-- Evidence Upload - Mobile Optimized -->
    <div v-if="form.status !== 'delivered'">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Evidence Photos
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
          class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm inline-flex items-center gap-1 sm:gap-2"
        >
          <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
          Upload Photos
        </button>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
          Upload photos as evidence for damaged or lost packages
        </p>
      </div>
      
      <!-- Uploaded Files Preview -->
      <div v-if="uploadedFiles.length > 0" class="mt-3 space-y-2">
        <div v-for="(file, index) in uploadedFiles" :key="index" class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 p-2 sm:p-3 rounded">
          <span class="text-xs text-gray-600 dark:text-gray-300 truncate flex-1 mr-2">{{ file.name }}</span>
          <button @click="removeFile(index)" class="text-red-500 hover:text-red-700 flex-shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Actions - Mobile Optimized -->
    <div class="flex gap-3 pt-4 sm:pt-6">
      <SecondaryButton @click="$emit('cancel')" class="flex-1 text-sm py-2.5 sm:py-2">
        Cancel
      </SecondaryButton>
      <PrimaryButton 
        @click="submit" 
        :disabled="submitting"
        class="flex-1 text-sm py-2.5 sm:py-2"
      >
        <span v-if="submitting">Updating...</span>
        <span v-else>Update Status</span>
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
  package: {
    type: Object,
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

function formatWeight(weight) {
  if (!weight || weight === 0) return 'N/A';
  return `${weight} kg`;
}

function formatVolume(volume) {
  if (!volume || volume === 0) return 'N/A';
  return `${volume} m³`;
}

function handleFileUpload(event) {
  const files = Array.from(event.target.files);
  uploadedFiles.value = [...uploadedFiles.value, ...files];
}

function removeFile(index) {
  uploadedFiles.value.splice(index, 1);
}

async function submit() {
  submitting.value = true;

  try {
    const formData = new FormData();
    formData.append('package_updates[0][package_id]', props.package.id);
    formData.append('package_updates[0][status]', form.status);
    formData.append('package_updates[0][remarks]', form.remarks || '');
    
    uploadedFiles.value.forEach((file, index) => {
      formData.append(`package_updates[0][evidence][${index}]`, file);
    });

    await router.post(route('driver.packages.update-destination-status'), formData, {
      forceFormData: true,
      onFinish: () => {
        submitting.value = false;
        emit('submitted');
      }
    });

  } catch (error) {
    console.error('Error updating status:', error);
    submitting.value = false;
  }
}
</script>