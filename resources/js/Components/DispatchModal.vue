<template>
  <Modal :show="show" max-width="lg" @close="close">
    <div class="p-4 max-h-[90vh] overflow-y-auto"> <!-- Added max-height and scroll -->
      
      <!-- Compact Header -->
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-medium text-gray-900">
          Dispatch Driver-Truck Set
        </h3>
        <button @click="close" class="text-gray-400 hover:text-gray-600">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Compact Driver-Truck Info -->
      <div class="bg-gray-50 rounded-lg p-3 mb-4">
        <div class="flex items-center space-x-3">
          <!-- Driver -->
          <div class="flex items-center space-x-2 flex-1">
            <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
              <span class="text-blue-600 font-medium text-xs">
                {{ set?.driver?.initials || 'DR' }}
              </span>
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-gray-900 truncate">
                {{ set?.driver?.name || 'N/A' }}
              </p>
            
            </div>
          </div>

          <!-- Truck -->
          <div class="flex items-center space-x-2 flex-1">
            <svg class="h-6 w-6 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-gray-900 truncate">
                {{ set?.truck?.license_plate || 'N/A' }}
              </p>
              <p class="text-xs text-gray-500 truncate">
                {{ set?.truck?.make }} {{ set?.truck?.model }}
              </p>
            </div>
          </div>
        </div>

        <!-- Backhaul Badge -->
        <div v-if="set?.available_for_backhaul" class="mt-2 flex justify-center">
          <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-purple-100 text-purple-800 border border-purple-300">
            <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
            Backhaul Assignment
          </span>
        </div>
      </div>

      <!-- Compact Validation Section -->
      <div class="mb-4">
        <h4 class="text-sm font-medium text-gray-900 mb-2">
          Dispatch Requirements
        </h4>
        
        <!-- Loading State -->
        <div v-if="validationLoading" class="flex items-center justify-center py-4">
          <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-2"></div>
          <span class="text-sm text-gray-600">Validating requirements...</span>
        </div>

        <!-- Compact Validation Items -->
        <div v-else class="space-y-2">
          <div v-for="check in compactValidationChecks" :key="check.key"
               class="flex items-center justify-between p-2 border rounded text-sm"
               :class="check.passed ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'">
            <div class="flex items-center">
              <svg :class="check.passed ? 'text-green-500' : 'text-red-500'" 
                   class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path v-if="check.passed" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              <span>{{ check.label }}</span>
            </div>
            <span :class="check.passed ? 'text-green-700' : 'text-red-700'" class="text-xs font-medium">
              {{ check.status }}
            </span>
          </div>
        </div>

        <!-- Error Summary -->
        <div v-if="!validation.can_dispatch && !validationLoading" class="mt-3 p-2 bg-red-50 border border-red-200 rounded text-sm">
          <p class="text-red-800 font-medium mb-1">Cannot Dispatch:</p>
          <ul class="text-red-700 text-xs list-disc list-inside space-y-1">
            <li v-for="error in validation.errors" :key="error">
              {{ error }}
            </li>
            <li v-if="validation.missing_waybills?.length > 0">
              Missing waybills: {{ validation.missing_waybills.length }} orders
            </li>
            <li v-if="validation.unstickerized_packages?.length > 0">
              Missing stickers: {{ validation.unstickerized_packages.length }} packages
            </li>
            <li v-if="!validation.manifest_complete">
              Manifest not finalized
            </li>
          </ul>
        </div>
      </div>

      <!-- Compact Action Buttons -->
      <div class="flex justify-end space-x-2 pt-3 border-t">
        <SecondaryButton @click="close" :disabled="dispatching" size="sm">
          Cancel
        </SecondaryButton>
        <PrimaryButton 
          @click="dispatch" 
          :disabled="!validation.can_dispatch || dispatching"
          size="sm"
          class="min-w-24"
        >
          <span v-if="dispatching" class="flex items-center">
            <div class="animate-spin rounded-full h-3 w-3 border-b-2 border-white mr-2"></div>
            Dispatching...
          </span>
          <span v-else>
            Dispatch
          </span>
        </PrimaryButton>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue'
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

const compactValidationChecks = computed(() => [
  {
    key: 'assigned_orders',
    label: 'Assigned Deliveries',
    passed: validation.value.has_assigned_orders,
    status: validation.value.has_assigned_orders ? `${validation.value.assigned_orders_count} orders` : 'No orders'
  },
  {
    key: 'truck',
    label: 'Truck Available',
    passed: validation.value.truck_available,
    status: validation.value.truck_available ? 'Available' : 'Unavailable'
  },
  {
    key: 'driver',
    label: 'Driver Available',
    passed: validation.value.driver_available,
    status: validation.value.driver_available ? 'Available' : 'Unavailable'
  },
  {
    key: 'waybills',
    label: 'Waybills',
    passed: validation.value.waybills_complete,
    status: validation.value.waybills_complete ? 'Complete' : 'Missing'
  },
  {
    key: 'stickers',
    label: 'Package Stickers',
    passed: validation.value.stickers_complete,
    status: validation.value.stickers_complete ? 'Complete' : 'Missing'
  },
  {
    key: 'manifest',
    label: 'Manifest',
    passed: validation.value.manifest_complete,
    status: validation.value.manifest_complete ? 'Finalized' : 'Not ready'
  }
])


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