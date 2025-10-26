<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex flex-wrap justify-between items-center gap-4 px-4 md:px-6 w-full">
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Sticker Management
          </h2>
          <p class="text-sm text-gray-600 mt-1">
            Print and manage package stickers for delivery.
          </p>
        </div>
      </div>
    </template>

    <div class="py-6 px-2 md:px-6 w-full max-w-[95rem] mx-auto">
      <!-- Main Container -->
      <div class="bg-white shadow-sm rounded-lg border border-gray-200 mb-6 w-full">
        <div class="border-b border-gray-200">
          <nav class="-mb-px flex space-x-8 px-6">
            <button
              @click="switchTab('not_printed')"
              :class="[
                'py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 flex items-center',
                activeTab === 'not_printed'
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
            >
              🖨️ Not Printed
              <span class="ml-2 bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
                {{ getNotPrintedCount }}
              </span>
            </button>
            <button
              @click="switchTab('printed')"
              :class="[
                'py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 flex items-center',
                activeTab === 'printed'
                  ? 'border-green-500 text-green-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
            >
              ✅ Printed Stickers
              <span class="ml-2 bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                {{ getPrintedCount }}
              </span>
            </button>
          </nav>
        </div>

        <!-- Status Messages -->
        <div v-if="status || success || error" class="p-4 border-b border-gray-200">
          <div v-if="status" class="p-4 bg-blue-100 text-blue-800 rounded">{{ status }}</div>
          <div v-if="success" class="p-4 bg-green-100 text-green-800 rounded">{{ success }}</div>
          <div v-if="error" class="p-4 bg-red-100 text-red-800 rounded">{{ error }}</div>
        </div>

        <!-- Filters Section -->
        <div class="p-4 border-b border-gray-200">
          <div class="flex flex-col lg:flex-row gap-4">
            <!-- Search -->
            <div class="flex-1">
              <SearchInput
                v-model="filters.search"
                placeholder="Search by package ID, item, waybill, receiver..."
                @keyup.enter="handleFilterChange"
                @input="handleDebouncedFilter"
                class="w-full"
              />
            </div>
            
            <!-- Region Filter -->
            <div class="sm:w-48">
              <SelectInput
                v-model="filters.region_id"
                :options="regionOptions"
                placeholder="All Regions"
                @change="handleFilterChange"
              />
            </div>

            <!-- Items Per Page -->
            <div class="sm:w-32">
              <SelectInput
                v-model="filters.per_page"
                :options="perPageOptions"
                placeholder="Items per page"
                @change="handleFilterChange"
              />
            </div>

            <!-- Filter Actions -->
            <div class="flex items-center gap-2">
              <SecondaryButton @click="refreshData">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh
              </SecondaryButton>
              <SecondaryButton @click="resetFilters">
                Reset
              </SecondaryButton>
            </div>
          </div>

          <!-- Filter Info -->
          <div class="flex justify-between items-center mt-4">
            <div class="text-sm text-gray-500">
              Showing {{ getCurrentDataCount() }} {{ getItemName() }}
              <span v-if="filters.search" class="ml-2">• "{{ filters.search }}"</span>
              <span v-if="filters.region_id" class="ml-2">
                • {{ getRegionLabel(filters.region_id) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Bulk Actions -->
        <div v-if="selectedPackages.length > 0" class="p-4 border-b border-gray-200 bg-yellow-50">
          <div class="flex items-center justify-between">
            <span class="text-yellow-800 font-medium">
              {{ selectedPackages.length }} package(s) selected
            </span>
            <PrimaryButton @click="bulkPrint" :disabled="loading">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
              </svg>
              Print Selected
            </PrimaryButton>
          </div>
        </div>
      </div>

      <!-- Tab Content -->
      
      <!-- Not Printed Tab -->
      <div v-if="activeTab === 'not_printed'">
        <div class="bg-white shadow-sm rounded-lg overflow-hidden border border-gray-200 w-full">
          <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
              <div>
                <h3 class="text-lg font-medium text-gray-900">
                  Packages Ready for Sticker Printing
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                  Packages that need sticker printing
                </p>
              </div>
              <div class="text-sm text-gray-500">
                Showing {{ packages?.data?.length || 0 }} packages
              </div>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">
                    <input 
                      type="checkbox" 
                      :checked="isAllSelected"
                      @change="toggleSelectAll"
                      class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Package ID
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Item Name
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Destination
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Receiver
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Waybill
                  </th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-if="packages?.data?.length > 0" v-for="pkg in packages.data" :key="pkg.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <input 
                      type="checkbox" 
                      :checked="selectedPackages.includes(pkg.id)"
                      @change="togglePackageSelection(pkg.id)"
                      class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    />
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    <span class="font-mono">{{ pkg.item_code }}</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <div class="flex items-center">
                      <span class="truncate max-w-xs">{{ pkg.item_name }}</span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <div class="flex items-center">
                      <div
                        class="w-4 h-4 rounded-full mr-2 border border-gray-300"
                        :style="{ backgroundColor: pkg.destination_region?.color_hex }"
                      ></div>
                      <span>{{ pkg.destination_region?.name }}</span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <div class="text-sm">
                      <div class="font-medium">{{ pkg.receiver?.name }}</div>
                      <div class="text-gray-500 text-xs truncate max-w-xs">
                        {{ pkg.receiver?.address }}
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <span class="text-sm text-gray-900">
                      {{ pkg.waybill?.waybill_number || 'N/A' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex justify-end space-x-2">
                      <PrimaryButton
                        @click="printSticker(pkg)"
                        :disabled="loading"
                        size="xs"
                        class="flex items-center"
                      >
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print
                      </PrimaryButton>
                    </div>
                  </td>
                </tr>
                <tr v-else>
                  <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                    <div class="text-center py-12">
                      <svg class="h-12 w-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                      </svg>
                      <p class="text-gray-500 mt-2">No packages ready for printing</p>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <Pagination
            v-if="packages?.meta && packages.meta.last_page > 1"
            :pagination="packages.meta"
            @page-changed="(page) => handlePageChange(page)"
            class="px-4 py-3"
          />
        </div>
      </div>

      <!-- Printed Tab -->
      <div v-else-if="activeTab === 'printed'">
        <div class="bg-white shadow-sm rounded-lg overflow-hidden border border-gray-200 w-full">
          <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
              <div>
                <h3 class="text-lg font-medium text-gray-900">
                  Printed Stickers
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                  Packages with printed stickers
                </p>
              </div>
              <div class="text-sm text-gray-500">
                Showing {{ packages?.data?.length || 0 }} packages
              </div>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Package ID
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Item Name
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Destination
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Receiver
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Waybill
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Printed At
                  </th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-if="packages?.data?.length > 0" v-for="pkg in packages.data" :key="pkg.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    <span class="font-mono">{{ pkg.item_code }}</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <div class="flex items-center">
                      <span class="truncate max-w-xs">{{ pkg.item_name }}</span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <div class="flex items-center">
                      <div
                        class="w-4 h-4 rounded-full mr-2 border border-gray-300"
                        :style="{ backgroundColor: pkg.destination_region?.color_hex }"
                      ></div>
                      <span>{{ pkg.destination_region?.name }}</span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <div class="text-sm">
                      <div class="font-medium">{{ pkg.receiver?.name }}</div>
                      <div class="text-gray-500 text-xs truncate max-w-xs">
                        {{ pkg.receiver?.address }}
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <span class="text-sm text-gray-900">
                      {{ pkg.waybill?.waybill_number || 'N/A' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <div v-if="pkg.sticker_printed_at" class="text-sm">
                      <div>{{ formatDate(pkg.sticker_printed_at) }}</div>
                      <div class="text-gray-500 text-xs">
                        by {{ pkg.sticker_printed_by || 'System' }}
                      </div>
                    </div>
                    <span v-else class="text-gray-400">-</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex justify-end space-x-2">
                      <PrimaryButton
                        @click="printSticker(pkg)"
                        :disabled="loading"
                        size="xs"
                        class="flex items-center"
                      >
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Reprint
                      </PrimaryButton>
                      <SecondaryButton
                        @click="resetSticker(pkg)"
                        :disabled="loading"
                        size="xs"
                        class="flex items-center"
                      >
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Reset
                      </SecondaryButton>
                    </div>
                  </td>
                </tr>
                <tr v-else>
                  <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                    <div class="text-center py-12">
                      <ArchiveBoxIcon class="h-12 w-12 mx-auto text-gray-400" />
                      <p class="text-gray-500 mt-2">No printed stickers found</p>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <Pagination
            v-if="packages?.meta && packages.meta.last_page > 1"
            :pagination="packages.meta"
            @page-changed="(page) => handlePageChange(page)"
            class="px-4 py-3"
          />
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { reactive, ref, computed, watch } from 'vue'
import {
  ArchiveBoxIcon,
} from '@heroicons/vue/24/outline'

import SearchInput from '@/Components/SearchInput.vue'
import SelectInput from '@/Components/SelectInput.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  packages: Object,
  regions: Array,
  stats: Object,
  filters: Object,
  status: String,
  success: String,
  error: String,
})

const loading = ref(false)
const selectedPackages = ref([])

const activeTab = ref(props.filters?.tab || 'not_printed')

// Initialize filters from props
const filters = reactive({
  search: props.filters?.search || '',
  region_id: props.filters?.region_id || '',
  per_page: props.filters?.per_page || 15,
})

// Computed options for filters
const regionOptions = computed(() => [
  { value: '', label: 'All Regions' },
  ...props.regions.map(region => ({
    value: region.id,
    label: region.name
  }))
])

const perPageOptions = computed(() => [
  { value: '10', label: '10 items' },
  { value: '15', label: '15 items' },
  { value: '25', label: '25 items' },
  { value: '50', label: '50 items' }
])

// Safe computed properties for stats with fallbacks
const getNotPrintedCount = computed(() => {
  return props.stats?.not_printed_total || 0
})

const getPrintedCount = computed(() => {
  return props.stats?.printed_total || 0
})

// Computed
const isAllSelected = computed(() => {
  return props.packages?.data?.length > 0 && 
         selectedPackages.value.length === props.packages.data.length
})

// Methods
function getCurrentDataCount() {
  return props.packages?.data?.length || 0
}

function getItemName() {
  return activeTab.value === 'not_printed' ? 'packages ready for printing' : 'printed packages'
}

function switchTab(tab) {
  activeTab.value = tab
  selectedPackages.value = [] // Clear selection when switching tabs
  handleFilterChange()
}

function handleFilterChange() {
  const payload = {
    tab: activeTab.value,
    ...filters
  }
  
  // Remove empty filters
  Object.keys(payload).forEach(key => {
    if (payload[key] === '' || payload[key] === null) {
      delete payload[key]
    }
  })
  
  router.visit(route('stickers.index'), {
    data: payload,
    preserveState: true,
    preserveScroll: true,
    replace: true,
    onStart: () => loading.value = true,
    onFinish: () => loading.value = false,
  })
}

// Debounced search
let searchTimeout = null
function handleDebouncedFilter() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    if (filters.search === '' || filters.search.length >= 3) {
      handleFilterChange()
    }
  }, 500)
}

function resetFilters() {
  Object.keys(filters).forEach(key => {
    filters[key] = ''
  })
  filters.per_page = 15
  handleFilterChange()
}

function refreshData() {
  router.reload({
    only: ['packages', 'stats'],
    preserveScroll: true
  })
}

function handlePageChange(page) {
  if (page >= 1 && page <= getLastPage()) {
    const payload = {
      tab: activeTab.value,
      ...filters,
      page: page
    }
    
    // Remove empty filters
    Object.keys(payload).forEach(key => {
      if (payload[key] === '' || payload[key] === null) {
        delete payload[key]
      }
    })
    
    router.visit(route('stickers.index'), {
      data: payload,
      preserveState: true,
      preserveScroll: true,
      onStart: () => loading.value = true,
      onFinish: () => loading.value = false,
    })
  }
}

function getLastPage() {
  return props.packages?.meta?.last_page || 1
}

// Helper methods for filter labels
function getRegionLabel(regionId) {
  const region = props.regions.find(r => r.id == regionId)
  return region ? region.name : 'Unknown Region'
}

// Selection methods
function toggleSelectAll() {
  if (isAllSelected.value) {
    selectedPackages.value = []
  } else {
    selectedPackages.value = props.packages.data.map(pkg => pkg.id)
  }
}

function togglePackageSelection(packageId) {
  const index = selectedPackages.value.indexOf(packageId)
  if (index === -1) {
    selectedPackages.value.push(packageId)
  } else {
    selectedPackages.value.splice(index, 1)
  }
}

// Sticker methods
async function printSticker(pkg) {
  loading.value = true
  
  try {
    // Use bulk print endpoint even for single package
    const queryString = `package_ids[]=${pkg.id}`
    
    // Open PDF in new tab using bulk print endpoint
    const printWindow = window.open(`${route('stickers.bulk-print')}?${queryString}`, '_blank')
    
    // Check if window opened successfully
    if (printWindow) {
      printWindow.focus()
      
      // Wait for the print operation to complete, then refresh data
      setTimeout(() => {
        refreshData()
      }, 1500)
    } else {
      // Fallback: redirect to bulk print URL
      window.location.href = `${route('stickers.bulk-print')}?${queryString}`
      loading.value = false
    }
  } catch (error) {
    console.error('Print error:', error)
    loading.value = false
  }
}

async function bulkPrint() {
  if (selectedPackages.value.length === 0) return
  
  loading.value = true
  
  try {
    // Create a query string with the package IDs
    const queryString = selectedPackages.value.map(id => `package_ids[]=${id}`).join('&')
    
    // Open the bulk print URL in a new tab with the package IDs as query parameters
    const printWindow = window.open(`${route('stickers.bulk-print')}?${queryString}`, '_blank')
    
    // Check if window opened successfully
    if (printWindow) {
      printWindow.focus()
      
      // Wait for the print operation to complete, then refresh data and clear selection
      setTimeout(() => {
        refreshData()
        selectedPackages.value = []
      }, 1500)
    } else {
      // Fallback: redirect to the bulk print URL
      window.location.href = `${route('stickers.bulk-print')}?${queryString}`
      loading.value = false
      selectedPackages.value = []
    }
  } catch (error) {
    console.error('Bulk print error:', error)
    loading.value = false
  }
}

function resetSticker(pkg) {
  if (!confirm('Are you sure you want to reset this sticker? This will allow it to be printed again.')) {
    return
  }
  
  loading.value = true
  router.post(route('stickers.reset', pkg.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      refreshData()
    },
    onFinish: () => loading.value = false,
  })
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>