<template>
  <EmployeeLayout>
    <Head title="Reports" />
    
    <div class="p-6">
      <!-- Header with Documents Link -->
      <div class="mb-6 flex justify-between items-center">
        <div>
          <h1 class="text-2xl font-bold">📊 Reports</h1>
          <p class="text-gray-600">Quick logistics insights</p>
        </div>
        <Link 
          :href="route('reports.documents')" 
          class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
        >
          📋 View Documents
        </Link>
      </div>

      <!-- Date Filter -->
      <div class="bg-white p-4 rounded-lg shadow mb-6">
        <label class="block text-sm font-medium mb-2">Date Range:</label>
        <select v-model="dateFilter" @change="loadData" class="border rounded p-2">
          <option value="today">Today</option>
          <option value="this_week">This Week</option>
          <option value="this_month" selected>This Month</option>
          <option value="this_year">This Year</option>
        </select>
      </div>

      <!-- Quick Stats -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div v-for="stat in quickStats" :key="stat.label" class="bg-white p-4 rounded-lg shadow border-l-4 border-blue-500">
          <div class="text-2xl font-bold">{{ stat.value }}</div>
          <div class="text-gray-600 text-sm">{{ stat.label }}</div>
        </div>
      </div>

      <!-- Simple Data Display -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Package Types -->
        <div class="bg-white p-4 rounded-lg shadow">
          <h3 class="font-semibold mb-4">📦 Package Types</h3>
          <div class="space-y-2">
            <div v-for="item in charts.package_types.data" :key="item.label" class="flex justify-between border-b pb-2">
              <span>{{ item.label }}</span>
              <span class="font-semibold">{{ item.value }}</span>
            </div>
          </div>
        </div>

        <!-- Top Locations -->
        <div class="bg-white p-4 rounded-lg shadow">
          <h3 class="font-semibold mb-4">📍 Top Locations</h3>
          <div class="space-y-2">
            <div v-for="location in charts.top_locations.data" :key="location.label" class="flex justify-between border-b pb-2">
              <span>{{ location.label }}</span>
              <span class="font-semibold">{{ location.value }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
</EmployeeLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router, Link } from '@inertiajs/vue3'  // ← Added Link import
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'

const props = defineProps({
  quickStats: Object,
  charts: Object,
  filters: Object
})

const dateFilter = ref(props.filters.date_filter || 'this_month')

const loadData = () => {
  router.get('/admin/reports', { date_filter: dateFilter.value })
}
</script>