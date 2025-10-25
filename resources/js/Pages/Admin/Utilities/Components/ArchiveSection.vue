[file name]: ArchiveSection.vue
[file content begin]
<template>
  <div class="space-y-6">
    <!-- Archive Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white shadow rounded-lg border border-gray-200 p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0 p-3 bg-blue-100 rounded-lg">
            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total Archived</p>
            <p class="text-2xl font-semibold text-gray-900">{{ statistics.total }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white shadow rounded-lg border border-gray-200 p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0 p-3 bg-orange-100 rounded-lg">
            <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Manifests</p>
            <p class="text-2xl font-semibold text-gray-900">{{ statistics.manifests }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white shadow rounded-lg border border-gray-200 p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0 p-3 bg-green-100 rounded-lg">
            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Waybills</p>
            <p class="text-2xl font-semibold text-gray-900">{{ statistics.waybills }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Archive Data Card -->
    <div class="bg-white shadow rounded-lg border border-gray-200">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Archive Data</h3>
        <p class="mt-1 text-sm text-gray-500">
          Archive old manifests and waybills to keep your active records clean
        </p>
      </div>
      <div class="px-6 py-4">
        <form @submit.prevent="showArchiveConfirmModal = true" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <InputLabel value="" class="mb-2" />
              <SelectInput
                v-model="archiveForm.archive_type"
                :options="archiveTypeOptions"
                option-value="value"
                option-label="label"
                placeholder="Select data type"
                class="w-full"
              />
            </div>
            

            <div>
              <InputLabel value="Older Than (days)" class="mb-2" />
              <input
                v-model.number="archiveForm.older_than_days"
                type="number"
                min="1"
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-green-500 focus:ring-1 focus:ring-green-500"
                required
              >
            </div>

            <div class="flex items-end">
              <PrimaryButton
                type="submit"
                :disabled="loading"
                class="w-full justify-center"
              >
                <span v-if="loading">Archiving...</span>
                <span v-else>Archive Data</span>
              </PrimaryButton>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- View Archived Data -->
    <div class="bg-white shadow rounded-lg border border-gray-200">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-medium text-gray-900">Archived Data</h3>
            <p class="mt-1 text-sm text-gray-500">
              View and manage archived records
            </p>
          </div>
          <div class="text-sm text-gray-600">
            Showing {{ archivedData.length }} {{ viewType }}
          </div>
        </div>
      </div>
      <div class="px-6 py-4">
        <!-- Filters -->
        <div class="flex space-x-4 mb-4">
          <PrimaryButton
            @click="loadArchivedData('manifests')"
            :class="[
              viewType === 'manifests'
                ? 'bg-green-600 hover:bg-green-700'
                : 'bg-gray-600 hover:bg-gray-700'
            ]"
          >
            Manifests
          </PrimaryButton>
          <PrimaryButton
            @click="loadArchivedData('waybills')"
            :class="[
              viewType === 'waybills'
                ? 'bg-green-600 hover:bg-green-700'
                : 'bg-gray-600 hover:bg-gray-700'
            ]"
          >
            Waybills
          </PrimaryButton>
        </div>

        <div v-if="loadingArchived" class="text-center py-8">
          <svg class="animate-spin mx-auto h-6 w-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <p class="mt-2 text-sm text-gray-500">Loading archived data...</p>
        </div>

        <div v-else-if="archivedData.length === 0" class="text-center py-8 text-gray-500">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No archived {{ viewType }} found</h3>
          <p class="mt-1 text-sm text-gray-500">
            Archived {{ viewType }} will appear here.
          </p>
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="item in archivedData"
            :key="item.id"
            class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-green-50 transition-colors group"
          >
            <div class="flex items-center space-x-4 flex-1">
              <div class="flex-shrink-0 p-2 bg-blue-100 rounded-lg group-hover:bg-green-100 transition-colors">
                <svg class="h-5 w-5 text-blue-600 group-hover:text-green-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center space-x-2">
                  <h4 class="font-medium text-gray-900 truncate">
                    {{ viewType === 'manifests' ? item.manifest_number : item.waybill_number }}
                  </h4>
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    {{ viewType.slice(0, -1) }}
                  </span>
                </div>
                
                <div class="mt-1 space-y-1">
                  <p class="text-sm text-gray-600" v-if="viewType === 'manifests'">
                    Status: <span class="font-medium capitalize">{{ item.status }}</span>
                  </p>
                  <p class="text-sm text-gray-600" v-if="viewType === 'manifests' && item.driver">
                    Driver: {{ item.driver.name }}
                  </p>
                  <p class="text-sm text-gray-600" v-if="viewType === 'waybills' && item.generator">
                    Generated by: {{ item.generator.name }}
                  </p>
                  <div class="flex items-center space-x-4 text-xs text-gray-500">
                    <span>Created: {{ formatDate(item.created_at) }}</span>
                    <span>Archived: {{ formatDate(item.archived_at) }}</span>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="flex space-x-2">
              <SecondaryButton
                @click="viewDetails(item)"
                class="text-sm"
              >
                View Details
              </SecondaryButton>
              <PrimaryButton
                @click="openRestoreConfirmModal(item)"
                class="bg-green-600 hover:bg-green-700 text-sm"
              >
                Restore
              </PrimaryButton>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Archive Confirmation Modal -->
    <Modal :show="showArchiveConfirmModal" @close="showArchiveConfirmModal = false" maxWidth="sm">
      <div class="p-4">
        <div class="flex items-center justify-center w-12 h-12 mx-auto bg-yellow-100 rounded-full">
          <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
        </div>
        <div class="mt-3 text-center">
          <h3 class="text-lg font-medium text-gray-900">Archive Data</h3>
          <p class="mt-2 text-sm text-gray-500">
            Are you sure you want to archive {{ archiveForm.archive_type }} older than 
            <strong>{{ archiveForm.older_than_days }} days</strong>?
          </p>
        </div>
        <div class="mt-4 flex justify-center space-x-3">
          <SecondaryButton @click="showArchiveConfirmModal = false">
            Cancel
          </SecondaryButton>
          <PrimaryButton @click="confirmArchive" class="bg-green-600 hover:bg-green-700">
            Confirm Archive
          </PrimaryButton>
        </div>
      </div>
    </Modal>

    <!-- Restore Confirmation Modal -->
    <Modal :show="showRestoreConfirmModal" @close="showRestoreConfirmModal = false" maxWidth="sm">
      <div class="p-4">
        <div class="flex items-center justify-center w-12 h-12 mx-auto bg-blue-100 rounded-full">
          <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </div>
        <div class="mt-3 text-center">
          <h3 class="text-lg font-medium text-gray-900">Restore {{ viewType.slice(0, -1) }}</h3>
          <p class="mt-2 text-sm text-gray-500">
            Are you sure you want to restore 
            <strong>{{ selectedItemToRestore ? (viewType === 'manifests' ? selectedItemToRestore.manifest_number : selectedItemToRestore.waybill_number) : '' }}</strong>?
          </p>
        </div>
        <div class="mt-4 flex justify-center space-x-3">
          <SecondaryButton @click="showRestoreConfirmModal = false">
            Cancel
          </SecondaryButton>
          <PrimaryButton @click="confirmRestore" class="bg-green-600 hover:bg-green-700">
            Confirm Restore
          </PrimaryButton>
        </div>
      </div>
    </Modal>

    <!-- Details Modal -->
    <Modal :show="showDetailModal" @close="showDetailModal = false" maxWidth="lg">
      <div class="p-4">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-lg font-medium text-gray-900">
            {{ selectedItem ? (viewType === 'manifests' ? 'Manifest Details' : 'Waybill Details') : '' }}
          </h3>
          <button @click="showDetailModal = false" class="text-gray-400 hover:text-gray-500">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div v-if="selectedItem" class="space-y-3">
          <!-- Manifest Details -->
          <div v-if="viewType === 'manifests'" class="grid grid-cols-2 gap-3 text-sm">
            <div>
              <InputLabel value="Manifest Number" class="text-xs" />
              <p class="mt-1 text-gray-900 font-mono">{{ selectedItem.manifest_number }}</p>
            </div>
            <div>
              <InputLabel value="Status" class="text-xs" />
              <p class="mt-1 text-gray-900 capitalize">{{ selectedItem.status }}</p>
            </div>
            <div>
              <InputLabel value="Driver" class="text-xs" />
              <p class="mt-1 text-gray-900">{{ selectedItem.driver?.name || 'Not assigned' }}</p>
            </div>
            <div>
              <InputLabel value="Truck" class="text-xs" />
              <p class="mt-1 text-gray-900">{{ selectedItem.truck?.plate_number || 'Not assigned' }}</p>
            </div>
            <div class="col-span-2">
              <InputLabel value="Notes" class="text-xs" />
              <p class="mt-1 text-gray-900">{{ selectedItem.notes || 'No notes' }}</p>
            </div>
            <div>
              <InputLabel value="Created" class="text-xs" />
              <p class="mt-1 text-gray-900 text-xs">{{ formatDate(selectedItem.created_at) }}</p>
            </div>
            <div>
              <InputLabel value="Archived" class="text-xs" />
              <p class="mt-1 text-gray-900 text-xs">{{ formatDate(selectedItem.archived_at) }}</p>
            </div>
          </div>

          <!-- Waybill Details -->
          <div v-else class="grid grid-cols-2 gap-3 text-sm">
            <div>
              <InputLabel value="Waybill Number" class="text-xs" />
              <p class="mt-1 text-gray-900 font-mono">{{ selectedItem.waybill_number }}</p>
            </div>
            <div>
              <InputLabel value="Generated By" class="text-xs" />
              <p class="mt-1 text-gray-900">{{ selectedItem.generator?.name || 'System' }}</p>
            </div>
            <div class="col-span-2">
              <InputLabel value="Notes" class="text-xs" />
              <p class="mt-1 text-gray-900">{{ selectedItem.notes || 'No notes' }}</p>
            </div>
            <div>
              <InputLabel value="Created" class="text-xs" />
              <p class="mt-1 text-gray-900 text-xs">{{ formatDate(selectedItem.created_at) }}</p>
            </div>
            <div>
              <InputLabel value="Archived" class="text-xs" />
              <p class="mt-1 text-gray-900 text-xs">{{ formatDate(selectedItem.archived_at) }}</p>
            </div>
          </div>

          <div class="flex justify-end space-x-2 pt-3 border-t">
            <SecondaryButton @click="showDetailModal = false" class="text-sm px-3 py-1">
              Close
            </SecondaryButton>
            <PrimaryButton 
              @click="showRestoreFromDetailsModal(selectedItem)"
              class="bg-green-600 hover:bg-green-700 text-sm px-3 py-1"
            >
              Restore
            </PrimaryButton>
          </div>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import SearchInput from '@/Components/SearchInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import SelectInput from '@/Components/SelectInput.vue';
import InputLabel from '@/Components/InputLabel.vue';

// Forms and Data
const archiveForm = reactive({
  archive_type: 'manifests',
  older_than_days: 30
});

const loading = ref(false);
const loadingArchived = ref(false);
const viewType = ref('manifests');
const archivedData = ref([]);

// Modals
const showArchiveConfirmModal = ref(false);
const showRestoreConfirmModal = ref(false);
const showDetailModal = ref(false);
const selectedItem = ref(null);
const selectedItemToRestore = ref(null);

// Statistics
const statistics = reactive({
  total: 0,
  manifests: 0,
  waybills: 0
});

// Archive type options for SelectInput
const archiveTypeOptions = computed(() => [
  { value: 'manifests', label: 'Manifests' },
  { value: 'waybills', label: 'Waybills' }
]);

const archiveData = () => {
  showArchiveConfirmModal.value = true;
};

const confirmArchive = () => {
  loading.value = true;
  showArchiveConfirmModal.value = false;
  
  router.post('/admin/utilities/archive', archiveForm, {
    preserveScroll: true,
    onFinish: () => {
      loading.value = false;
      loadArchivedData(viewType.value);
      loadStatistics();
    },
  });
};

const openRestoreConfirmModal  = (item) => {
  selectedItemToRestore.value = item;
  showRestoreConfirmModal.value = true;
};

const showRestoreFromDetailsModal = (item) => {
  selectedItemToRestore.value = item;
  showDetailModal.value = false;
  showRestoreConfirmModal.value = true;
};

const confirmRestore = async () => {
  if (!selectedItemToRestore.value) return;

  const itemId = selectedItemToRestore.value.id;
  showRestoreConfirmModal.value = false;
  
  await performRestore(itemId);
  selectedItemToRestore.value = null;
};

const performRestore = (id) => {
  router.patch('/admin/utilities/archive', {
    archive_type: viewType.value,
    id: id,
    action: 'restore'
  }, {
    preserveScroll: true,
    onSuccess: () => {
      archivedData.value = archivedData.value.filter(item => item.id !== id);
      loadStatistics();
    },
    onError: (errors) => {
      alert('Failed to restore record: ' + (errors.message || 'Unknown error'));
    }
  });
};

const loadArchivedData = async (type) => {
  viewType.value = type;
  loadingArchived.value = true;
  
  try {
    const response = await fetch(`/admin/utilities/archive/data?archive_type=${type}`);
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    
    if (data.error) {
      throw new Error(data.error);
    }
    
    archivedData.value = data.archivedData || [];
  } catch (error) {
    console.error('Error loading archived data:', error);
    archivedData.value = [];
  } finally {
    loadingArchived.value = false;
  }
};

const loadStatistics = async () => {
  try {
    const [manifestsRes, waybillsRes] = await Promise.all([
      fetch('/admin/utilities/archive/data?archive_type=manifests'),
      fetch('/admin/utilities/archive/data?archive_type=waybills')
    ]);
    
    const manifestsData = await manifestsRes.json();
    const waybillsData = await waybillsRes.json();
    
    statistics.manifests = manifestsData.archivedData?.length || 0;
    statistics.waybills = waybillsData.archivedData?.length || 0;
    statistics.total = statistics.manifests + statistics.waybills;
  } catch (error) {
    console.error('Error loading statistics:', error);
  }
};

const viewDetails = (item) => {
  selectedItem.value = item;
  showDetailModal.value = true;
};

const formatDate = (dateString) => {
  if (!dateString) return 'Unknown date';
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Initialize
onMounted(() => {
  loadArchivedData('manifests');
  loadStatistics();
});
</script>
[file content end]