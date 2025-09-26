<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-4 md:px-6">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ region.name }}</h2>
          <p class="mt-1 text-sm text-gray-500">
            Warehouse details and location information
          </p>
        </div>

        <!-- Right: Buttons -->
        <div class="flex gap-2">
          <SecondaryButton @click="backToList">Back to List</SecondaryButton>
        </div>
      </div>
    </template>

    <div class="py-6 px-2 md:px-6">
      <div class="max-w-screen-xl mx-auto">
        <div v-if="status || success || error" class="mb-6">
          <div v-if="status" class="p-4 bg-blue-100 text-blue-800 rounded">{{ status }}</div>
          <div v-if="success" class="p-4 bg-green-100 text-green-800 rounded">{{ success }}</div>
          <div v-if="error" class="p-4 bg-red-100 text-red-800 rounded">{{ error }}</div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
          <!-- Main Content (3/4 width) -->
          <div class="lg:col-span-3">
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 space-y-8">
              <!-- Region Information -->
              <div class="border-b border-gray-200 pb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Region Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <InputLabel value="Region Name" />
                    <p class="mt-1 text-gray-900 font-medium">{{ region.name }}</p>
                  </div>
                  
                  <div>
                    <InputLabel value="Region Color" />
                    <div class="flex items-center mt-1">
                      <div 
                        class="h-6 w-6 rounded border border-gray-300 mr-2" 
                        :style="{ backgroundColor: region.color_hex || '#CCCCCC' }"
                      ></div>
                      <span class="text-gray-900 font-mono">{{ region.color_hex || '#CCCCCC' }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Address Details -->
              <div class="border-b border-gray-200 pb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Warehouse Address</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div class="md:col-span-2">
                    <InputLabel value="Full Address" />
                    <p class="mt-1 text-gray-600 whitespace-pre-line">{{ region.warehouse_address }}</p>
                  </div>

                  <div>
                    <InputLabel value="Street Address/Building" />
                    <p class="mt-1 text-gray-600">{{ getAddressPart(0) }}</p>
                  </div>

                  <div>
                    <InputLabel value="Barangay" />
                    <p class="mt-1 text-gray-600">{{ getAddressPart(1) }}</p>
                  </div>

                  <div>
                    <InputLabel value="City/Municipality" />
                    <p class="mt-1 text-gray-600">{{ getAddressPart(2) }}</p>
                  </div>

                  <div>
                    <InputLabel value="Province" />
                    <p class="mt-1 text-gray-600">{{ getAddressPart(3) }}</p>
                  </div>

                  <div>
                    <InputLabel value="Postal Code" />
                    <p class="mt-1 text-gray-600">{{ getAddressPart(4) }}</p>
                  </div>
                </div>
              </div>

              <!-- Location Map -->
              <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Location on Map</h3>
                <div>
                  <InputLabel value="Region Location" />
                  <p class="text-sm text-gray-600 mb-2">Exact warehouse location coordinates</p>
                  <MapPicker 
                    :model-value="{
                      lat: region.geographic_location.latitude,
                      lng: region.geographic_location.longitude
                    }"
                    :readonly="true"
                    class="h-96 border border-gray-300 rounded-lg mt-2"
                  />
                  <div class="mt-2 text-sm text-gray-500">
                    Coordinates: {{ region.geographic_location.latitude.toFixed(6) }}, {{ region.geographic_location.longitude.toFixed(6) }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Status Sidebar (1/4 width) -->
          <div class="lg:col-span-1">
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
              <h3 class="font-semibold text-gray-800 mb-3">📊 Region Status</h3>
              
              <div class="space-y-4">
                <div>
                  <span class="text-sm font-medium text-gray-700">Status:</span>
                  <span 
                    :class="[
                      'ml-2 px-2 py-1 text-xs font-medium rounded-full',
                      region.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                    ]"
                  >
                    {{ region.is_active ? 'Active' : 'Archived' }}
                  </span>
                </div>
                
                <div>
                  <span class="text-sm font-medium text-gray-700">Created:</span>
                  <p class="text-sm text-gray-600 mt-1">{{ formatDate(region.created_at) }}</p>
                </div>
                
                <div>
                  <span class="text-sm font-medium text-gray-700">Last Updated:</span>
                  <p class="text-sm text-gray-600 mt-1">{{ formatDate(region.updated_at) }}</p>
                </div>
                
                <div class="pt-4 border-t border-gray-200">
                  <div class="flex flex-col gap-2">
                    <PrimaryButton @click="editRegion" class="w-full justify-center">Edit Region</PrimaryButton>
                    <DangerButton 
                      v-if="region.is_active" 
                      @click="archive" 
                      class="w-full justify-center"
                    >
                      Archive Region
                    </DangerButton>
                    <PrimaryButton 
                      v-else 
                      @click="restore" 
                      class="w-full justify-center"
                    >
                      Restore Region
                    </PrimaryButton>
                  </div>
                </div>
              </div>
            </div>
          </div>
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
import InputLabel from '@/Components/InputLabel.vue';
import MapPicker from '@/Components/MapPicker.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  region: Object,
  status: String,
  success: String,
  error: String,
});

// Helper function to parse address parts
const getAddressPart = (index) => {
  if (!props.region.warehouse_address) return '-';
  const parts = props.region.warehouse_address.split(',').map(part => part.trim());
  return parts[index] || '-';
};

// Helper function to format dates
const formatDate = (dateString) => {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

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