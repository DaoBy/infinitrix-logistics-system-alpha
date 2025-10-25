[file name]: BackupSection.vue
[file content begin]
<template>
  <div class="space-y-6">
    <!-- Status Messages -->
    <div v-if="$page.props.flash.success" class="bg-green-50 border border-green-200 rounded-lg p-4">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-green-600" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium text-green-800">
            {{ $page.props.flash.success }}
          </p>
        </div>
      </div>
    </div>

    <div v-if="$page.props.flash.error" class="bg-red-50 border border-red-200 rounded-lg p-4">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-red-600" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium text-red-800">
            {{ $page.props.flash.error }}
          </p>
        </div>
      </div>
    </div>

<!-- Backup Statistics -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
  <div class="bg-white shadow rounded-lg border border-gray-200 p-6">
    <div class="flex items-center">
      <div class="flex-shrink-0 p-3 bg-green-100 rounded-lg">
        <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
        </svg>
      </div>
      <div class="ml-4">
        <p class="text-sm font-medium text-gray-500">Total Backups</p>
        <p class="text-2xl font-semibold text-gray-900">{{ backups.length }}</p>
      </div>
    </div>
  </div>

  <div class="bg-white shadow rounded-lg border border-gray-200 p-6">
    <div class="flex items-center">
      <div class="flex-shrink-0 p-3 bg-blue-100 rounded-lg">
        <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <div class="ml-4">
        <p class="text-sm font-medium text-gray-500">Last Backup</p>
        <p class="text-2xl font-semibold text-gray-900">{{ lastBackupTime }}</p>
      </div>
    </div>
  </div>
</div>

    <!-- Create Backup Card -->
    <div class="bg-white shadow rounded-lg border border-gray-200">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Create Backup</h3>
        <p class="mt-1 text-sm text-gray-500">
          Create a complete copy of your database for safekeeping
        </p>
      </div>
      <div class="px-6 py-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-600">
              Click the button to create a new database backup
            </p>
          </div>
          <PrimaryButton
            @click="createBackup"
            :disabled="loading"
            class="min-w-[160px] justify-center"
          >
            <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <svg v-else class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <span v-if="loading">Creating...</span>
            <span v-else>Create New Backup</span>
          </PrimaryButton>
        </div>
      </div>
    </div>

   <div class="bg-white shadow rounded-lg border border-gray-200 p-6">
  <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
    <div class="w-full sm:flex-1">
      <InputLabel value="Search Backups" class="mb-2" />
      <SearchInput
        v-model="searchQuery"
        placeholder="Search backups..."
      />
    </div>
    <div class="w-full sm:w-48">
      <InputLabel value="Sort By" class="mb-2" />
      <SelectInput
        v-model="sortBy"
        :options="sortOptions"
        option-value="value"
        option-label="label"
        placeholder="Sort by"
        class="w-full"
      />
    </div>
  </div>
</div>

    <!-- Existing Backups -->
    <div class="bg-white shadow rounded-lg border border-gray-200">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Existing Backups</h3>
        <p class="mt-1 text-sm text-gray-500">
          Manage, download, and restore your database backups
        </p>
      </div>
      <div class="px-6 py-4">
        <div v-if="filteredBackups.length === 0" class="text-center py-8">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No backups found</h3>
          <p class="mt-1 text-sm text-gray-500">
            {{ searchQuery ? 'No backups match your search.' : 'Get started by creating your first database backup.' }}
          </p>
          <SecondaryButton 
            v-if="searchQuery" 
            @click="clearSearch"
            class="mt-4"
          >
            Clear Search
          </SecondaryButton>
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="(backup, index) in filteredBackups"
            :key="backup.filename"
            class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-green-50 transition-colors duration-150 group"
          >
            <div class="flex items-center space-x-4 flex-1">
              <div class="flex-shrink-0 p-2 bg-blue-100 rounded-lg group-hover:bg-green-100 transition-colors">
                <svg class="h-5 w-5 text-blue-600 group-hover:text-green-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <h4 class="font-medium text-gray-900 truncate">{{ backup.filename }}</h4>
                <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4 mt-1 text-sm text-gray-500">
                  <span class="flex items-center">
                    <svg class="flex-shrink-0 mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ backup.created_at }}
                  </span>
                  <span class="flex items-center">
                    <svg class="flex-shrink-0 mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                    </svg>
                    {{ backup.size }}
                  </span>
                </div>
              </div>
            </div>
            
            <div class="flex space-x-2">
              <SecondaryButton
                @click="downloadBackup(backup.filename)"
                class="flex items-center text-sm"
                title="Download backup file to your computer"
              >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Download
              </SecondaryButton>
              <PrimaryButton
                @click="restoreBackup(backup.filename)"
                class="flex items-center text-sm"
                title="Restore this backup to replace current database"
              >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Restore
              </PrimaryButton>
             <button
  @click="openDeleteModal(backup.filename)"
  class="flex items-center px-3 py-2 bg-red-600 text-white text-sm rounded hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors duration-200"
  title="Permanently delete this backup"
>
  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
  </svg>
  Delete
</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Backup Information -->
    <div class="bg-green-50 border border-green-200 rounded-lg p-6">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-green-600" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-green-800">
            About Database Backups
          </h3>
          <div class="mt-2 text-sm text-green-700 space-y-1">
            <p>• <strong>Download</strong>: Save backup files to your computer for thesis demonstration</p>
            <p>• <strong>Backups</strong>: Complete copies of your SQLite database (.sqlite files)</p>
            <p>• <strong>Restore</strong>: Replace current database with selected backup</p>
            <p>• <strong>Safety</strong>: Temporary backup created automatically before restoration</p>
            <p>• <strong>File Format</strong>: Actual SQLite database files that can be opened with DB Browser for SQLite</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirmation Modal -->
 <Modal :show="showRestoreModal" @close="closeRestoreModal" maxWidth="md">
  <div class="p-6">
        <div class="flex items-center justify-center w-12 h-12 mx-auto bg-yellow-100 rounded-full">
          <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
        </div>
        <div class="mt-4 text-center">
          <h3 class="text-lg font-medium text-gray-900">Restore Backup</h3>
          <p class="mt-2 text-sm text-gray-500">
            Are you sure you want to restore backup <strong>"{{ selectedBackup }}"</strong>? 
            This will replace your current database. A temporary safety backup will be created automatically.
          </p>
        </div>
        <div class="mt-6 flex justify-center space-x-3">
      <SecondaryButton @click="closeRestoreModal">
        Cancel
      </SecondaryButton>
      <PrimaryButton @click="confirmRestore" class="bg-green-600 hover:bg-green-700">
        Confirm Restore
      </PrimaryButton>
    </div>
  </div>
</Modal>
<!-- Delete Confirmation Modal -->
<Modal :show="showDeleteModal" @close="closeDeleteModal" maxWidth="md">
  <div class="p-6">
    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full">
      <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z" />
      </svg>
    </div>
    <div class="mt-4 text-center">
      <h3 class="text-lg font-medium text-gray-900">Delete Backup</h3>
      <p class="mt-2 text-sm text-gray-500">
        Are you sure you want to delete backup <strong>"{{ selectedBackupToDelete }}"</strong>? 
        This action cannot be undone.
      </p>
    </div>
    <div class="mt-6 flex justify-center space-x-3">
      <SecondaryButton @click="closeDeleteModal">
        Cancel
      </SecondaryButton>
      <PrimaryButton @click="confirmDelete" class="bg-red-600 hover:bg-red-700">
        Confirm Delete
      </PrimaryButton>
    </div>
  </div>
</Modal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import SearchInput from '@/Components/SearchInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import SelectInput from '@/Components/SelectInput.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
  backups: Array,
});

const loading = ref(false);
const downloading = ref(false);
const searchQuery = ref('');
const sortBy = ref('newest');
const showRestoreModal = ref(false);
const selectedBackup = ref('');
const showDeleteModal = ref(false);
const selectedBackupToDelete = ref('');

const lastBackupTime = computed(() => {
  if (props.backups.length === 0) {
    return 'Never';
  }
  return props.backups[0].created_at;
});

const databaseInfo = computed(() => {
  return 'database.sqlite';
});

const sortOptions = computed(() => [
  { value: 'newest', label: 'Newest First' },
  { value: 'oldest', label: 'Oldest First' },
  { value: 'size', label: 'File Size' }
]);

const filteredBackups = computed(() => {
  let backups = [...props.backups];
  
  // Filter by search query
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    backups = backups.filter(backup => 
      backup.filename.toLowerCase().includes(query) ||
      backup.created_at.toLowerCase().includes(query)
    );
  }
  
  // Sort backups
  switch (sortBy.value) {
    case 'oldest':
      return backups.reverse();
    case 'size':
      return backups.sort((a, b) => {
        const sizeA = parseFloat(a.size);
        const sizeB = parseFloat(b.size);
        return sizeB - sizeA;
      });
    case 'newest':
    default:
      return backups;
  }
});

const closeRestoreModal = () => {
  showRestoreModal.value = false;
  selectedBackup.value = '';
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  selectedBackupToDelete.value = '';
};

const createBackup = () => {
  loading.value = true;
  router.post('/admin/utilities/backup', {}, {
    preserveScroll: true,
    onFinish: () => {
      loading.value = false;
      router.reload({ only: ['backups'] });
    },
  });
};

const downloadBackup = (filename) => {
  downloading.value = true;
  
  // Create a temporary link to download the file
  const link = document.createElement('a');
  link.href = `/admin/utilities/backup/download?filename=${encodeURIComponent(filename)}`;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  
  downloading.value = false;
};

const restoreBackup = (filename) => {
  selectedBackup.value = filename;
  showRestoreModal.value = true;
};

const confirmRestore = () => {
  router.post('/admin/utilities/restore', { filename: selectedBackup.value }, {
    preserveScroll: true,
    onFinish: () => {
      showRestoreModal.value = false;
      router.reload({ only: ['backups'] });
    },
  });
};

const openDeleteModal = (filename) => {
  selectedBackupToDelete.value = filename;
  showDeleteModal.value = true;
};

const confirmDelete = () => {
  showDeleteModal.value = false; // Close modal immediately
  
  router.delete('/admin/utilities/backup', { 
    data: { filename: selectedBackupToDelete.value } 
  }, {
    preserveScroll: true,
    onFinish: () => {
      router.reload({ only: ['backups'] });
    },
  });
};

const clearSearch = () => {
  searchQuery.value = '';
};
</script>