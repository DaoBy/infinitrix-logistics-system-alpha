<template>
  <Modal :show="show" max-width="2xl" @close="close">
    <div class="p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
          Dispatch Driver-Truck Set
        </h3>
        <button
          @click="close"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Driver-Truck Set Information -->
      <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 mb-6">
        <div class="flex items-center space-x-4">
          <!-- Driver Info -->
          <div class="flex-1">
            <div class="flex items-center space-x-3">
              <div class="h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                <span class="text-blue-600 dark:text-blue-300 font-medium text-sm">
                  {{ set?.driver?.initials || 'DR' }}
                </span>
              </div>
              <div>
                <p class="font-medium text-gray-900 dark:text-gray-100">
                  {{ set?.driver?.name || 'N/A' }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{ set?.driver?.employee_id || '' }}
                </p>
                <!-- Backhaul Badge -->
                <span 
                  v-if="set?.available_for_backhaul" 
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 mt-1"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd" />
                  </svg>
                  Backhaul Assignment
                </span>
              </div>
            </div>
          </div>

          <!-- Truck Info -->
          <div class="flex-1">
            <div class="flex items-center space-x-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-500" viewBox="0 0 20 20" fill="currentColor">
                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1 1 0 11-3 0 1 1 0 013 0z" />
                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-1a1 1 0 011-1h2a1 1 0 011 1v1a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H19a1 1 0 001-1V5a1 1 0 00-1-1H3z" />
              </svg>
              <div>
                <p class="font-medium text-gray-900 dark:text-gray-100">
                  {{ set?.truck?.license_plate || 'N/A' }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{ set?.truck?.make || '' }} {{ set?.truck?.model || '' }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Current Load Information -->
        <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
          <div>
            <p class="text-gray-600 dark:text-gray-400">Current Volume</p>
            <p class="font-medium">{{ (set?.current_volume || 0).toFixed(2) }} / {{ set?.truck?.volume_capacity || 0 }} m³</p>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-1">
              <div 
                class="bg-blue-600 h-2 rounded-full" 
                :style="{ width: `${Math.min(100, ((set?.current_volume || 0) / (set?.truck?.volume_capacity || 1)) * 100)}%` }"
              ></div>
            </div>
          </div>
          <div>
            <p class="text-gray-600 dark:text-gray-400">Current Weight</p>
            <p class="font-medium">{{ (set?.current_weight || 0).toFixed(2) }} / {{ set?.truck?.weight_capacity || 0 }} kg</p>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-1">
              <div 
                class="bg-green-600 h-2 rounded-full" 
                :style="{ width: `${Math.min(100, ((set?.current_weight || 0) / (set?.truck?.weight_capacity || 1)) * 100)}%` }"
              ></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Dispatch Validation -->
      <div class="mb-6">
        <h4 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-3">
          Dispatch Requirements
        </h4>
        
        <!-- Loading State -->
        <div v-if="validationLoading" class="flex items-center justify-center py-8">
          <LoadingSpinner size="lg" class="mr-3" />
          <span class="text-gray-600 dark:text-gray-400">Validating dispatch requirements...</span>
        </div>

        <!-- Validation Results -->
        <div v-else class="space-y-3">
          <!-- Assigned Orders Check -->
          <div class="flex items-center justify-between p-3 border rounded-lg" :class="
            validation.has_assigned_orders 
              ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20' 
              : 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20'
          ">
            <div class="flex items-center">
              <svg 
                v-if="validation.has_assigned_orders" 
                class="w-5 h-5 text-green-500 mr-2" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <svg 
                v-else 
                class="w-5 h-5 text-red-500 mr-2" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              <span class="font-medium">Assigned Deliveries</span>
            </div>
            <span class="text-sm" :class="validation.has_assigned_orders ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'">
              {{ validation.has_assigned_orders ? `${validation.assigned_orders_count} orders` : 'No orders' }}
            </span>
          </div>

          <!-- Truck Availability Check -->
          <div class="flex items-center justify-between p-3 border rounded-lg" :class="
            validation.truck_available 
              ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20' 
              : 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20'
          ">
            <div class="flex items-center">
              <svg 
                v-if="validation.truck_available" 
                class="w-5 h-5 text-green-500 mr-2" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <svg 
                v-else 
                class="w-5 h-5 text-red-500 mr-2" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              <span class="font-medium">Truck Available</span>
            </div>
            <span class="text-sm" :class="validation.truck_available ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'">
              {{ validation.truck_available ? 'Available' : 'Unavailable' }}
            </span>
          </div>

          <!-- Driver Availability Check -->
          <div class="flex items-center justify-between p-3 border rounded-lg" :class="
            validation.driver_available 
              ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20' 
              : 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20'
          ">
            <div class="flex items-center">
              <svg 
                v-if="validation.driver_available" 
                class="w-5 h-5 text-green-500 mr-2" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <svg 
                v-else 
                class="w-5 h-5 text-red-500 mr-2" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              <span class="font-medium">Driver Available</span>
            </div>
            <span class="text-sm" :class="validation.driver_available ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'">
              {{ validation.driver_available ? 'Available' : 'Unavailable' }}
            </span>
          </div>

          <!-- Waybills Check -->
          <div class="flex items-center justify-between p-3 border rounded-lg" :class="
            validation.waybills_complete 
              ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20' 
              : 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20'
          ">
            <div class="flex items-center">
              <svg 
                v-if="validation.waybills_complete" 
                class="w-5 h-5 text-green-500 mr-2" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <svg 
                v-else 
                class="w-5 h-5 text-red-500 mr-2" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              <span class="font-medium">Waybills Generated</span>
            </div>
            <span class="text-sm" :class="validation.waybills_complete ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'">
              {{ validation.waybills_complete ? 'Complete' : `${validation.missing_waybills?.length || 0} missing` }}
            </span>
          </div>

          <!-- Package Stickers Check -->
          <div class="flex items-center justify-between p-3 border rounded-lg" :class="
            validation.stickers_complete 
              ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20' 
              : 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20'
          ">
            <div class="flex items-center">
              <svg 
                v-if="validation.stickers_complete" 
                class="w-5 h-5 text-green-500 mr-2" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <svg 
                v-else 
                class="w-5 h-5 text-red-500 mr-2" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              <span class="font-medium">Package Stickers</span>
            </div>
            <span class="text-sm" :class="validation.stickers_complete ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'">
              {{ validation.stickers_complete ? 'Complete' : `${validation.unstickerized_packages?.length || 0} missing` }}
            </span>
          </div>

          <!-- Manifest Check -->
          <div class="flex items-center justify-between p-3 border rounded-lg" :class="
            validation.manifest_complete 
              ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20' 
              : 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20'
          ">
            <div class="flex items-center">
              <svg 
                v-if="validation.manifest_complete" 
                class="w-5 h-5 text-green-500 mr-2" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <svg 
                v-else 
                class="w-5 h-5 text-red-500 mr-2" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              <span class="font-medium">Manifest Finalized</span>
            </div>
            <span class="text-sm" :class="validation.manifest_complete ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'">
              {{ validation.manifest_complete ? 'Complete' : 'Missing or invalid' }}
            </span>
          </div>

          <!-- Overall Status -->
          <div class="mt-4 p-4 rounded-lg" :class="
            validation.can_dispatch 
              ? 'bg-green-50 border border-green-200 dark:bg-green-900/20 dark:border-green-800' 
              : 'bg-red-50 border border-red-200 dark:bg-red-900/20 dark:border-red-800'
          ">
            <div class="flex items-center">
              <svg 
                v-if="validation.can_dispatch" 
                class="w-6 h-6 text-green-500 mr-3" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <svg 
                v-else 
                class="w-6 h-6 text-red-500 mr-3" 
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div>
                <p class="font-medium" :class="validation.can_dispatch ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200'">
                  {{ validation.can_dispatch ? 'Ready to Dispatch' : 'Cannot Dispatch' }}
                </p>
                <p class="text-sm mt-1" :class="validation.can_dispatch ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'">
                  {{ validation.message || 'All requirements must be met before dispatch' }}
                </p>
              </div>
            </div>
          </div>

          <!-- Manifest Details -->
          <div v-if="validation.manifest_check && !validation.manifest_complete" class="mt-3">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Manifest Issues:</p>
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3">
              <div class="text-sm text-red-700 dark:text-red-300 space-y-2">
                <div v-if="!validation.manifest_check.has_manifest">
                  <p>❌ No finalized manifest found for this driver-truck set.</p>
                </div>
                <div v-else-if="!validation.manifest_check.manifest_valid">
                  <p>❌ Manifest is missing {{ validation.manifest_check.missing_package_count }} packages from current assignments.</p>
                  <p class="text-xs mt-1">Manifest ID: {{ validation.manifest_check.manifest_id }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Missing Waybills Details -->
          <div v-if="validation.missing_waybills?.length > 0" class="mt-3">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Missing Waybills:</p>
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3">
              <ul class="text-sm text-red-700 dark:text-red-300 list-disc list-inside space-y-1">
                <li v-for="orderId in validation.missing_waybills" :key="orderId">
                  Delivery Order DO-{{ orderId.toString().padStart(6, '0') }}
                </li>
              </ul>
            </div>
          </div>

          <!-- Missing Stickers Details -->
          <div v-if="validation.unstickerized_packages?.length > 0" class="mt-3">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Packages Missing Stickers:</p>
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3">
              <ul class="text-sm text-red-700 dark:text-red-300 list-disc list-inside space-y-1">
                <li v-for="packageInfo in validation.unstickerized_packages" :key="packageInfo.package_id">
                  Package {{ packageInfo.item_code }} (DO-{{ packageInfo.delivery_order_id }})
                </li>
              </ul>
            </div>
          </div>

          <!-- Validation Errors -->
          <div v-if="validation.errors?.length > 0" class="mt-3">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Validation Errors:</p>
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3">
              <ul class="text-sm text-red-700 dark:text-red-300 list-disc list-inside space-y-1">
                <li v-for="error in validation.errors" :key="error">
                  {{ error }}
                </li>
              </ul>
            </div>
          </div>

          <!-- Warnings -->
          <div v-if="validation.warnings?.length > 0" class="mt-3">
            <p class="text-sm font-medium text-yellow-700 dark:text-yellow-300 mb-2">Warnings:</p>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
              <ul class="text-sm text-yellow-700 dark:text-yellow-300 list-disc list-inside space-y-1">
                <li v-for="warning in validation.warnings" :key="warning">
                  {{ warning }}
                </li>
              </ul>
            </div>
          </div>

          <!-- Capacity Information -->
          <div v-if="validation.total_volume > 0 || validation.total_weight > 0" class="mt-3">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Load Information:</p>
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
              <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <p class="text-blue-700 dark:text-blue-300">Total Volume</p>
                  <p class="font-medium">{{ validation.total_volume.toFixed(2) }} m³</p>
                </div>
                <div>
                  <p class="text-blue-700 dark:text-blue-300">Total Weight</p>
                  <p class="font-medium">{{ validation.total_weight.toFixed(2) }} kg</p>
                </div>
              </div>
              <div v-if="validation.capacity_percentage > 0" class="mt-2">
                <p class="text-xs text-blue-600 dark:text-blue-400">
                  Capacity Utilization: {{ validation.capacity_percentage.toFixed(1) }}%
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex justify-end space-x-3">
        <SecondaryButton @click="close" :disabled="dispatching">
          Cancel
        </SecondaryButton>
        <PrimaryButton 
          @click="dispatch" 
          :disabled="!validation.can_dispatch || dispatching"
          class="min-w-32"
        >
          <span v-if="dispatching" class="flex items-center">
            <LoadingSpinner size="sm" class="mr-2" />
            Dispatching...
          </span>
          <span v-else>
            Confirm Dispatch
          </span>
        </PrimaryButton>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import LoadingSpinner from '@/Components/LoadingSpinner.vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  set: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'dispatched'])

// Reactive state
const validation = ref({
  can_dispatch: false,
  has_assigned_orders: false,
  assigned_orders_count: 0,
  truck_available: false,
  driver_available: false,
  waybills_complete: false,
  stickers_complete: false,
  manifest_complete: false,
  missing_waybills: [],
  unstickerized_packages: [],
  errors: [],
  warnings: [],
  message: '',
  total_volume: 0,
  total_weight: 0,
  capacity_percentage: 0,
  manifest_check: null
})

const validationLoading = ref(false)
const dispatching = ref(false)

// Methods
const close = () => {
  emit('close')
}

const dispatch = async () => {
  if (!props.set?.id || !validation.value.can_dispatch) return

  dispatching.value = true

  try {
    await router.post(route('cargo-assignments.dispatch.driver-truck-set', props.set.id), {}, {
      onSuccess: () => {
        emit('dispatched')
        close()
      },
      onError: (errors) => {
        console.error('Dispatch failed:', errors)
        alert('Dispatch failed. Please check the requirements and try again.')
      },
      onFinish: () => {
        dispatching.value = false
      }
    })
  } catch (error) {
    console.error('Dispatch error:', error)
    dispatching.value = false
    alert('An error occurred during dispatch. Please try again.')
  }
}

const validateDispatch = async () => {
  if (!props.set?.id) return

  validationLoading.value = true

  try {
    const response = await fetch(route('cargo-assignments.dispatch.validate', props.set.id))
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    const data = await response.json()

    // Extract manifest validation from the response
    const manifestCheck = data.manifest_check || {}
    const manifestComplete = manifestCheck.has_manifest && manifestCheck.manifest_valid

    validation.value = {
      can_dispatch: data.is_valid || false,
      has_assigned_orders: data.assigned_orders_count > 0,
      assigned_orders_count: data.assigned_orders_count || 0,
      truck_available: !data.errors?.some(error => error.includes('Truck')),
      driver_available: !data.errors?.some(error => error.includes('Driver')),
      waybills_complete: (data.missing_waybills?.length || 0) === 0,
      stickers_complete: (data.unstickerized_packages?.length || 0) === 0,
      manifest_complete: manifestComplete,
      missing_waybills: data.missing_waybills || [],
      unstickerized_packages: data.unstickerized_packages || [],
      errors: data.errors || [],
      warnings: data.warnings || [],
      message: data.message || '',
      total_volume: data.total_volume || 0,
      total_weight: data.total_weight || 0,
      capacity_percentage: data.capacity_percentage || 0,
      manifest_check: manifestCheck
    }
  } catch (error) {
    console.error('Validation error:', error)
    validation.value = {
      can_dispatch: false,
      has_assigned_orders: false,
      assigned_orders_count: 0,
      truck_available: false,
      driver_available: false,
      waybills_complete: false,
      stickers_complete: false,
      manifest_complete: false,
      missing_waybills: [],
      unstickerized_packages: [],
      errors: ['Failed to validate dispatch requirements: ' + error.message],
      warnings: [],
      message: 'Failed to validate dispatch requirements',
      total_volume: 0,
      total_weight: 0,
      capacity_percentage: 0,
      manifest_check: null
    }
  } finally {
    validationLoading.value = false
  }
}

// Watchers
watch(() => props.show, (newVal) => {
  if (newVal && props.set?.id) {
    validateDispatch()
  }
})

watch(() => props.set, (newSet) => {
  if (props.show && newSet?.id) {
    validateDispatch()
  }
})

// Lifecycle
onMounted(() => {
  if (props.show && props.set?.id) {
    validateDispatch()
  }
})
</script>

<style scoped>
/* Custom styles if needed */
</style>