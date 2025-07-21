<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ region.name }}</h2>
        <div class="flex gap-2">
          <SecondaryButton @click="backToList">Back to List</SecondaryButton>
          <PrimaryButton @click="editRegion">Edit</PrimaryButton>
          <DangerButton v-if="region.is_active" @click="archive">Archive</DangerButton>
          <PrimaryButton v-else @click="restore">Restore</PrimaryButton>
        </div>
      </div>
    </template>

    <div v-if="status || success || error" class="mb-6">
      <div v-if="status" class="p-4 bg-blue-100 text-blue-800 rounded">{{ status }}</div>
      <div v-if="success" class="p-4 bg-green-100 text-green-800 rounded">{{ success }}</div>
      <div v-if="error" class="p-4 bg-red-100 text-red-800 rounded">{{ error }}</div>
    </div>

    <div class="max-w-3xl mx-auto py-8">
      <div class="bg-white p-6 rounded-lg shadow-sm space-y-6">
        <div>
          <h3 class="font-medium text-gray-900 mb-2">Warehouse Address</h3>
          <p class="text-gray-600 whitespace-pre-line">{{ region.warehouse_address }}</p>
        </div>

        <div>
          <h3 class="font-medium text-gray-900 mb-2">Location</h3>
          <MapPicker 
            :model-value="{
              lat: region.geographic_location.latitude,
              lng: region.geographic_location.longitude
            }"
            :api-key="mapsApiKey"
            :readonly="true"
            class="h-96 border border-gray-300 rounded-lg"
          />
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import MapPicker from '@/Components/MapPicker.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  region: Object,
  mapsApiKey: String,
  status: String,
  success: String,
  error: String,
});

function backToList() {
  router.get(route('admin.regions.index'));
}

function editRegion() {
  router.get(route('admin.regions.edit', props.region.id));
}

function archive() {
  if (confirm('Are you sure you want to archive this region?')) {
    router.put(route('admin.regions.archive', props.region.id), {
      preserveScroll: true,
      onSuccess: () => router.reload()
    });
  }
}

function restore() {
  if (confirm('Are you sure you want to restore this region?')) {
    router.put(route('admin.regions.restore', props.region.id), {
      preserveScroll: true,
      onSuccess: () => router.reload()
    });
  }
}
</script>