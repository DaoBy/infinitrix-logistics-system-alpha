<template>
  <EmployeeLayout>
    <Head title="Documents" />
    
    <div class="p-6">
      <!-- Header with Back Link -->
      <div class="mb-6 flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold">📋 Documents</h1>
          <p class="text-gray-600">Manifests & Waybills</p>
        </div>
        <Link 
          :href="route('reports.dashboard')" 
          class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors"
        >
          ← Back to Reports
        </Link>
      </div>

      <!-- Tabs -->
      <div class="flex border-b mb-4">
        <button 
          @click="activeTab = 'manifests'"
          class="px-4 py-2 font-medium"
          :class="activeTab === 'manifests' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-600'"
        >
          Manifests
        </button>
        <button 
          @click="activeTab = 'waybills'"
          class="px-4 py-2 font-medium"
          :class="activeTab === 'waybills' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-gray-600'"
        >
          Waybills
        </button>
      </div>

      <!-- Manifests -->
      <div v-if="activeTab === 'manifests'">
        <div class="space-y-4">
          <div v-for="manifest in manifests.data" :key="manifest.id" class="bg-white p-4 rounded-lg shadow flex justify-between items-center">
            <div>
              <h3 class="font-semibold">{{ manifest.manifest_number }}</h3>
              <p class="text-sm text-gray-600">
                Truck: {{ manifest.truck?.license_plate }} • 
                Driver: {{ manifest.driver?.name }} • 
                Packages: {{ manifest.package_ids?.length || 0 }}
              </p>
            </div>
            <div class="space-x-2">
              <button @click="printManifest(manifest)" class="bg-blue-600 text-white px-3 py-1 rounded text-sm">
                Print
              </button>
              <Link :href="route('manifests.show', manifest.id)" class="bg-gray-200 px-3 py-1 rounded text-sm">
                View
              </Link>
            </div>
          </div>
        </div>
        <Pagination :links="manifests.links" class="mt-4" />
      </div>

      <!-- Waybills -->
      <div v-if="activeTab === 'waybills'">
        <div class="space-y-4">
          <div v-for="waybill in waybills.data" :key="waybill.id" class="bg-white p-4 rounded-lg shadow flex justify-between items-center">
            <div>
              <h3 class="font-semibold">{{ waybill.waybill_number }}</h3>
              <p class="text-sm text-gray-600">
                Request: {{ waybill.delivery_request?.reference_number }} • 
                Sender: {{ waybill.delivery_request?.sender?.name }}
              </p>
            </div>
            <div class="space-x-2">
              <button @click="printWaybill(waybill)" class="bg-blue-600 text-white px-3 py-1 rounded text-sm">
                Print
              </button>
              <Link :href="route('waybills.show', waybill.id)" class="bg-gray-200 px-3 py-1 rounded text-sm">
                View
              </Link>
            </div>
          </div>
        </div>
        <Pagination :links="waybills.links" class="mt-4" />
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  manifests: Object,
  waybills: Object
})

const activeTab = ref('manifests')

const printManifest = (manifest) => {
  window.open(route('manifests.print', manifest.id), '_blank')
}

const printWaybill = (waybill) => {
  window.open(route('waybills.preview', waybill.id), '_blank')
}
</script>