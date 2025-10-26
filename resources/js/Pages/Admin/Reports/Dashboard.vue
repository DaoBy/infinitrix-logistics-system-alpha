<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">Reports Dashboard</h2>
          <p class="mt-1 text-sm text-gray-500">
            Comprehensive logistics insights and analytics
          </p>
        </div>
      </div>
    </template>

    <div class="px-6 py-4">
      <!-- Status Messages -->
      <div v-if="status || success || error" class="mb-4">
        <div v-if="status" class="p-3 bg-blue-100 text-blue-800 rounded">{{ status }}</div>
        <div v-if="success" class="p-3 bg-green-100 text-green-800 rounded">{{ success }}</div>
        <div v-if="error" class="p-3 bg-red-100 text-red-800 rounded">{{ error }}</div>
      </div>

      <!-- Filters Section -->
      <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
       
        <div class="flex items-center gap-3">
          
          <SelectInput 
            v-model="filters.date_filter" 
            :options="dateFilterOptions" 
            option-value="value"
            option-label="text"
            placeholder="Date Range"
            class="w-full sm:w-48"
            @change="handleFilterChange"
          />
          <div class="text-sm text-gray-500 bg-blue-50 px-3 py-1 rounded border border-blue-100 whitespace-nowrap">
            📊 Showing {{ getChartCount() }} charts
          </div>
        </div>
      </div>

      <!-- Quick Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div v-for="stat in filteredQuickStats" :key="stat.label" 
             class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-xl font-bold text-gray-900">{{ stat.value }}</div>
              <div class="text-gray-600 text-sm mt-1">{{ stat.label }}</div>
            </div>
            <div class="p-2 rounded-full bg-gradient-to-r from-blue-100 to-indigo-100">
              <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Grid -->
      <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
        <!-- Package Types Distribution -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-lg text-gray-900 flex items-center gap-2">
              <div class="p-2 bg-blue-100 rounded-lg">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
              </div>
              Package Types Distribution
            </h3>
            <span class="text-sm text-gray-500">{{ charts.package_types.data.length }} categories</span>
          </div>
          <div class="space-y-3">
            <div v-for="(item, index) in charts.package_types.data" :key="item.label" 
                 class="flex justify-between items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
              <div class="flex items-center gap-3">
                <div class="w-3 h-3 rounded-full" :class="getChartColor(index)"></div>
                <span class="font-medium text-gray-700">{{ item.label }}</span>
              </div>
              <span class="bg-white text-blue-800 px-3 py-1 rounded-full text-sm font-semibold border border-blue-200">
                {{ item.value }}
              </span>
            </div>
          </div>
        </div>

        <!-- Top Delivery Locations -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-lg text-gray-900 flex items-center gap-2">
              <div class="p-2 bg-green-100 rounded-lg">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
              </div>
              Top Delivery Locations
            </h3>
            <span class="text-sm text-gray-500">Top 5 regions</span>
          </div>
          <div class="space-y-3">
            <div v-for="(location, index) in charts.top_locations.data" :key="location.label" 
                 class="flex justify-between items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
              <div class="flex items-center gap-3">
                <span class="font-medium text-gray-700">{{ location.label }}</span>
              </div>
              <span class="bg-white text-green-800 px-3 py-1 rounded-full text-sm font-semibold border border-green-200">
                {{ location.value }}
              </span>
            </div>
          </div>
        </div>

        <!-- Delivery Volume Trend -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-lg text-gray-900 flex items-center gap-2">
              <div class="p-2 bg-purple-100 rounded-lg">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                </svg>
              </div>
              Delivery Volume Trend
            </h3>
            <span class="text-sm text-gray-500">Last 7 days</span>
          </div>
          <div class="space-y-3">
            <div v-for="day in charts.delivery_volume.data.slice(-7)" :key="day.label" 
                 class="flex justify-between items-center py-2 border-b border-gray-100 last:border-b-0">
              <div class="flex items-center gap-3">
                <div class="w-2 h-2 bg-purple-500 rounded-full"></div>
                <span class="text-sm text-gray-600">{{ day.label }}</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="font-semibold text-gray-900">{{ day.value }}</span>
                <span class="text-xs text-gray-500">deliveries</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Revenue Trend -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-lg text-gray-900 flex items-center gap-2">
              <div class="p-2 bg-green-100 rounded-lg">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
              </div>
              Revenue Trend
            </h3>
            <span class="text-sm text-gray-500">Last 7 days</span>
          </div>
          <div class="space-y-3">
            <div v-for="day in charts.revenue_trend.data.slice(-7)" :key="day.label" 
                 class="flex justify-between items-center py-2 border-b border-gray-100 last:border-b-0">
              <div class="flex items-center gap-3">
                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                <span class="text-sm text-gray-600">{{ day.label }}</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="font-semibold text-green-600">₱{{ Number(day.value).toLocaleString() }}</span>
                <span v-if="day.gross_revenue" class="text-xs text-gray-500">
                  net of ₱{{ Number(day.refunds || 0).toLocaleString() }} refunds
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Additional Analytics Section -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Refund Analytics -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-lg text-gray-900 flex items-center gap-2">
              <div class="p-2 bg-orange-100 rounded-lg">
                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              Refund Analytics
            </h3>
            <span class="text-sm text-gray-500">Processed refunds</span>
          </div>
          <div class="space-y-3">
            <div v-for="(refund, index) in charts.refund_analytics.data" :key="refund.label" 
                 class="flex justify-between items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
              <div class="flex items-center gap-3">
                <div class="w-3 h-3 rounded-full" :class="getRefundColor(index)"></div>
                <span class="font-medium text-gray-700">{{ refund.label }}</span>
              </div>
              <div class="text-right">
                <div class="text-sm font-semibold text-orange-800">{{ refund.value }} cases</div>
                <div class="text-xs text-gray-500">₱{{ Number(refund.amount).toLocaleString() }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Performance Summary -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
          <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-lg text-gray-900 flex items-center gap-2">
              <div class="p-2 bg-indigo-100 rounded-lg">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                </svg>
              </div>
              Performance Summary
            </h3>
            <span class="text-sm text-gray-500">{{ getDateRangeLabel() }}</span>
          </div>
          <div class="space-y-4">
            <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
              <span class="text-sm text-blue-800">Delivery Success Rate</span>
              <span class="font-semibold text-blue-800">{{ quickStats.success_rate?.value || '0%' }}</span>
            </div>
            <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
              <span class="text-sm text-green-800">Average Daily Deliveries</span>
              <span class="font-semibold text-green-800">{{ calculateAverageDeliveries() }}</span>
            </div>
            <div class="flex justify-between items-center p-3 bg-purple-50 rounded-lg">
              <span class="text-sm text-purple-800">Revenue per Delivery</span>
              <span class="font-semibold text-purple-800">{{ calculateRevenuePerDelivery() }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Head, router, Link } from '@inertiajs/vue3'
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import SearchInput from '@/Components/SearchInput.vue'
import SelectInput from '@/Components/SelectInput.vue'

const props = defineProps({
  quickStats: Object,
  charts: Object,
  filters: Object,
  status: String,
  success: String,
  error: String,
})

// Filters
const filters = reactive({
  search: props.filters?.search || '',
  chart_type: props.filters?.chart_type || '',
  date_filter: props.filters?.date_filter || 'this_month'
})

const loading = ref(false)

// Filter out Success Rate from quick stats
const filteredQuickStats = computed(() => {
  if (!props.quickStats) return []
  const stats = { ...props.quickStats }
  delete stats.success_rate
  return Object.values(stats)
})

// Options for selects
const chartTypeOptions = [
  { value: '', text: 'All Charts' },
  { value: 'package_types', text: 'Package Types' },
  { value: 'top_locations', text: 'Top Locations' },
  { value: 'delivery_volume', text: 'Delivery Volume' },
  { value: 'revenue_trend', text: 'Revenue Trend' },
  { value: 'refund_analytics', text: 'Refund Analytics' }
]

const dateFilterOptions = [
  { value: 'today', text: 'Today' },
  { value: 'this_week', text: 'This Week' },
  { value: 'this_month', text: 'This Month' },
  { value: 'this_year', text: 'This Year' }
]

// Methods
function loadData() {
  router.get('/admin/reports', { 
    date_filter: filters.date_filter,
    search: filters.search,
    chart_type: filters.chart_type
  }, {
    preserveState: true,
    preserveScroll: true,
    onStart: () => loading.value = true,
    onFinish: () => loading.value = false,
  })
}

function refreshData() {
  router.reload({
    only: ['quickStats', 'charts'],
    preserveScroll: true
  })
}

function getDateRangeLabel() {
  switch (filters.date_filter) {
    case 'today': return 'Today'
    case 'this_week': return 'This Week'
    case 'this_month': return 'This Month'
    case 'this_year': return 'This Year'
    default: return 'This Month'
  }
}

function getChartCount() {
  return Object.keys(props.charts || {}).length
}

function getChartColor(index) {
  const colors = ['bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-orange-500', 'bg-red-500']
  return colors[index % colors.length]
}

function getRefundColor(index) {
  const colors = ['bg-orange-500', 'bg-red-500', 'bg-amber-500', 'bg-rose-500', 'bg-yellow-500']
  return colors[index % colors.length]
}

function getLocationEmoji(index) {
  const emojis = ['🥇', '🥈', '🥉', '📍', '📌']
  return emojis[index] || '📍'
}

function calculateAverageDeliveries() {
  const totalDeliveries = props.quickStats?.total_deliveries?.value || 0
  const days = filters.date_filter === 'today' ? 1 : 
               filters.date_filter === 'this_week' ? 7 : 
               filters.date_filter === 'this_month' ? 30 : 365
  return Math.round(totalDeliveries / days)
}

function calculateRevenuePerDelivery() {
  const revenue = props.quickStats?.total_revenue?.value || '₱0'
  const totalDeliveries = props.quickStats?.total_deliveries?.value || 0
  if (totalDeliveries === 0) return '₱0'
  
  // Extract numeric value from revenue string (e.g., "₱1,234.56" -> 1234.56)
  const revenueValue = parseFloat(revenue.replace(/[^\d.]/g, ''))
  const average = revenueValue / totalDeliveries
  return `₱${average.toFixed(2)}`
}

// Server-side filtering functions
function handleFilterChange() {
  const payload = {
    ...filters
  };
  
  router.visit(route('reports.dashboard'), {
    data: payload,
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
}

// Debounced search for better performance
let searchTimeout = null;
function handleDebouncedFilter() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    handleFilterChange();
  }, 500);
}
</script>