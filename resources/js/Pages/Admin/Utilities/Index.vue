<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">Utilities</h2>
          <p class="mt-1 text-sm text-gray-500">
            Manage system settings, backups, and archives
          </p>
        </div>
      </div>
    </template>

    <!-- ZOOM CONTENT WRAPPER -->
    <div class="zoom-content">
      <!-- MAIN CONTENT CONTAINER WITH PROPER PADDING -->
      <div class="px-6 py-4">
        <!-- Status Messages -->
        <div v-if="status || success || error" class="mb-4">
          <div v-if="status" class="p-3 bg-blue-100 text-blue-800 rounded">{{ status }}</div>
          <div v-if="success" class="p-3 bg-green-100 text-green-800 rounded">{{ success }}</div>
          <div v-if="error" class="p-3 bg-red-100 text-red-800 rounded">{{ error }}</div>
        </div>

        <!-- Navigation Tabs -->
        <div class="mb-6">
          <nav class="flex space-x-8 border-b border-gray-200 dark:border-gray-700">
            <button
              v-for="tab in tabs"
              :key="tab.name"
              @click="currentTab = tab.name"
              :class="[
                'py-2 px-1 border-b-2 font-medium text-sm',
                currentTab === tab.name
                  ? 'border-blue-500 text-blue-600 dark:text-blue-400 dark:border-blue-400'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
              ]"
            >
              {{ tab.label }}
            </button>
          </nav>
        </div>

        <!-- Tab Content -->
        <div>
          <!-- Settings Tab -->
          <div v-if="currentTab === 'settings'">
            <SettingsSection 
              :price-matrix="priceMatrix"
              :user-preferences="userPreferences"
            />
          </div>

          <!-- Backup & Recovery Tab -->
          <div v-if="currentTab === 'backup'">
            <BackupSection 
              :backups="backups"
            />
          </div>

          <!-- Archive Tab -->
          <div v-if="currentTab === 'archive'">
            <ArchiveSection />
          </div>
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import SettingsSection from './Components/SettingsSection.vue';
import BackupSection from './Components/BackupSection.vue';
import ArchiveSection from './Components/ArchiveSection.vue';
import { ref } from 'vue';

const props = defineProps({
  priceMatrix: Object,
  userPreferences: Object,
  backups: Array,
  status: String,
  success: String,
  error: String,
});

const currentTab = ref('settings');

const tabs = [
  { name: 'settings', label: 'Settings' },
  { name: 'backup', label: 'Backup & Recovery' },
  { name: 'archive', label: 'Archive' },
];
</script>

<style scoped>
.zoom-content {
  zoom: 0.90;
}
</style>