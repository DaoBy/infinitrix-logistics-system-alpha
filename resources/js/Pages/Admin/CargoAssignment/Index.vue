<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-6">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Cargo Assignment Dashboard</h2>
        <div class="flex space-x-2">
          <SearchInput 
            v-model="searchTerm" 
            placeholder="Search deliveries..." 
            class="w-64"
            @search="applyFilters"
          />
          <SecondaryButton @click="refreshData" class="inline-flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Refresh
          </SecondaryButton>
        </div>
      </div>
    </template>

    <!-- Status Messages -->
    <div v-if="hasFlashMessages" class="px-6 py-2">
      <FlashMessages :flash="flash" />
    </div>

    <!-- Filters Section -->
    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
      <div class="flex flex-row items-center gap-4">
        <div class="flex-1 flex flex-row items-center gap-4">
          <SelectInput
            v-model="statusFilter"
            :options="statusOptions"
            placeholder="Filter by status"
            class="flex-1 min-w-0"
          />
          <SelectInput
            v-model="regionFilter"
            :options="regionOptions"
            placeholder="Filter by region"
            class="flex-1 min-w-0"
          />
        </div>
        <SecondaryButton @click="resetFilters" size="sm">
          Clear Filters
        </SecondaryButton>
      </div>
    </div>

    <div class="px-6 py-4">
      <!-- Delivery Orders Table -->
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 mb-6">
        <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
          <h3 class="font-medium text-gray-900 dark:text-gray-100">
            Delivery Orders
          </h3>
          <div class="text-sm text-gray-500 dark:text-gray-400">
            Showing {{ deliveries?.from ?? 0 }} to {{ deliveries?.to ?? 0 }} of {{ deliveries?.total ?? 0 }} entries
          </div>
        </div>
        
        <DataTable
          :columns="deliveryColumns"
          :data="deliveries?.data ?? []"
          :loading="loading"
          selectable
          @selection-change="handleSelectionChange"
        >
          <template #status="{ row }">
            <StatusBadge
              :status="row.status"
              :class="statusBadgeClass(row.status)"
            >
              {{ formatStatusText(row.status) }}
            </StatusBadge>
          </template>
          <template #packages="{ row }">
            {{ row.delivery_request?.packages?.length ?? 0 }}
          </template>
          <template #actions="{ row }">
            <div class="flex space-x-1">
              <SecondaryButton 
                @click.stop="viewDetails(row.id)" 
                size="xs" 
                title="Details"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
              </SecondaryButton>
              <DangerButton 
                v-if="row.status !== 'completed' && row.status !== 'cancelled'"
                @click.stop="confirmCancel(row)" 
                size="xs" 
                title="Cancel"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
              </DangerButton>
            </div>
          </template>
        </DataTable>
        
        <!-- Use the provided Pagination component for Delivery Orders -->
        <Pagination
          v-if="deliveries?.last_page > 1"
          :pagination="deliveries"
          @page-changed="handlePageChange"
        />
      </div>

      <!-- Assignment Section -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Selected Deliveries Card -->
        <div class="lg:col-span-1">
          <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
              <div class="flex justify-between items-center">
                <h3 class="font-medium text-gray-900 dark:text-gray-100">
                  Selected Deliveries ({{ selectedDeliveries?.length ?? 0 }})
                </h3>
                <button 
                  v-if="selectedDeliveries?.length > 0"
                  @click="clearSelection"
                  class="text-xs text-red-500 hover:text-red-700"
                >
                  Clear All
                </button>
              </div>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
              <template v-if="selectedDeliveries?.length > 0">
                <div 
                  v-for="delivery in selectedDeliveries" 
                  :key="delivery.id" 
                  class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50"
                >
                  <div class="flex justify-between items-start">
                    <div>
                      <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        DO-{{ delivery.id?.toString()?.padStart(6, '0') }}
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ delivery.delivery_request?.pick_up_region?.name ?? 'N/A' }} → 
                        {{ delivery.delivery_request?.drop_off_region?.name ?? 'N/A' }}
                      </p>
                      <div class="mt-1 text-xs">
                        <p>Volume: {{ calculateTotalVolume(delivery.delivery_request?.packages ?? []) }} m³</p>
                        <p>Weight: {{ calculateTotalWeight(delivery.delivery_request?.packages ?? []) }} kg</p>
                      </div>
                    </div>
                    <button 
                      @click="removeDelivery(delivery.id)"
                      class="text-red-500 hover:text-red-700"
                      title="Remove"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                      </svg>
                    </button>
                  </div>
                </div>
              </template>
              <div v-else class="p-4 text-center text-gray-500 dark:text-gray-400">
                No deliveries selected
              </div>
            </div>
            <div v-if="selectedDeliveries?.length > 0" class="p-4 border-t border-gray-200 dark:border-gray-700">
              <div class="text-sm">
                <p class="font-medium">Total Selected:</p>
                <p>Volume: {{ totalSelectedVolume }} m³</p>
                <p>Weight: {{ totalSelectedWeight }} kg</p>
              </div>
              <PrimaryButton 
                class="mt-4 w-full"
                :disabled="!selectedSet || !selectedDeliveries.length"
                @click="openAssignmentModal"
              >
                Assign Selected
              </PrimaryButton>
            </div>
          </div>
        </div>

        <!-- Driver-Truck Sets -->
        <div class="lg:col-span-2">
          <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
              <h3 class="font-medium text-gray-900 dark:text-gray-100">
                Available Driver-Truck Sets
              </h3>
            </div>
            <div class="p-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <template v-if="driverTruckSets?.length > 0">
                  <div 
                    v-for="set in driverTruckSets" 
                    :key="set.id" 
                    class="border rounded-lg p-4 hover:border-blue-500 transition-colors cursor-pointer"
                    :class="{ 
                      'border-blue-500 bg-blue-50 dark:bg-blue-900/20': selectedSet?.id === set.id,
                      'opacity-50 cursor-not-allowed': !set.is_available,
                      'cursor-pointer': set.is_available
                    }"
                    @click="set.is_available && selectSet(set)"
                  >
                    <div class="flex items-start space-x-3">
                      <!-- Driver Info -->
                      <div class="flex-1">
                        <div class="flex items-center space-x-2">
                          <div class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                            <span class="text-gray-600 dark:text-gray-300 font-medium">{{ set.driver?.initials ?? '' }}</span>
                          </div>
                          <div>
                            <p class="font-medium">{{ set.driver?.name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ set.driver?.employee_id ?? '' }}</p>
                          </div>
                        </div>
                        <div class="mt-2 text-xs">
                          <p>Current Assignments: {{ set.driver?.current_assignments ?? 0 }}</p>
                          <p>Available: 
                            <span :class="set.driver?.canAcceptNewAssignment ? 'text-green-600' : 'text-red-600'">
                              {{ set.driver?.canAcceptNewAssignment ? 'Yes' : 'No' }}
                            </span>
                          </p>
                        </div>
                      </div>
                      <!-- Truck Info -->
                      <div class="flex-1">
                        <div class="flex items-center space-x-2">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1 1 0 11-3 0 1 1 0 013 0z" />
                            <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-1a1 1 0 011-1h2a1 1 0 011 1v1a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H19a1 1 0 001-1V5a1 1 0 00-1-1H3z" />
                          </svg>
                          <div>
                            <p class="font-medium">{{ set.truck?.license_plate ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ set.truck?.make ?? '' }} {{ set.truck?.model ?? '' }}</p>
                          </div>
                        </div>
                        <div class="mt-2 space-y-2">
                          <!-- Volume Capacity -->
                          <div>
                            <p class="text-xs">Volume: {{ (set.current_volume ?? 0).toFixed(2) }} / {{ set.truck?.volume_capacity ?? 0 }} m³</p>
                            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-1.5">
                              <div 
                                class="bg-blue-600 h-1.5 rounded-full" 
                                :style="{ width: `${Math.min(100, ((set.current_volume ?? 0) / (set.truck?.volume_capacity || 1)) * 100)}%` }"
                              ></div>
                            </div>
                          </div>
                          <!-- Weight Capacity -->
                          <div>
                            <p class="text-xs">Weight: {{ (set.current_weight ?? 0).toFixed(2) }} / {{ set.truck?.weight_capacity ?? 0 }} kg</p>
                            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-1.5">
                              <div 
                                class="bg-green-600 h-1.5 rounded-full" 
                                :style="{ width: `${Math.min(100, ((set.current_weight ?? 0) / (set.truck?.weight_capacity || 1)) * 100)}%` }"
                              ></div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <!-- Current Trips -->
                    <div v-if="set.active_orders?.length > 0" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                      <p class="text-xs font-medium mb-1">Current Trips:</p>
                      <div v-for="order in set.active_orders" :key="order.id" class="text-xs">
                        <p>DO-{{ order.id }}: {{ order.status }}</p>
                      </div>
                    </div>

                    <!-- Dispatch Button -->
                    <PrimaryButton
                      class="mt-4 w-full"
                      :disabled="!set.is_available || dispatchingSetId === set.id"
                      @click.stop="openDispatchModal(set)"
                    >
                      <span v-if="dispatchingSetId === set.id">
                        <LoadingSpinner size="xs" class="mr-2" /> Dispatching...
                      </span>
                      <span v-else>
                        Dispatch
                      </span>
                    </PrimaryButton>
                  </div>
                </template>
                <div v-else class="p-4 text-center text-gray-500 dark:text-gray-400">
                  No available driver-truck sets found
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Batch Assignment Suggestions -->
    <div class="mt-8 bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
      <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
        <h3 class="font-medium text-gray-900 dark:text-gray-100">
          Batch Assignment Suggestions
        </h3>
      </div>
      <div class="p-4">
        <div v-if="batchSuggestions.length > 0" class="space-y-4">
          <div v-for="suggestion in batchSuggestions" :key="suggestion.destination_region.id" 
               class="border rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
            <div class="flex justify-between items-start">
              <div>
                <p class="font-medium">
                  To: {{ suggestion.destination_region.name }}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  {{ suggestion.delivery_requests.length }} deliveries
                </p>
                <p class="text-sm mt-2">
                  Total Volume: {{ suggestion.total_volume.toFixed(2) }} m³ | 
                  Total Weight: {{ suggestion.total_weight.toFixed(2) }} kg
                </p>
              </div>
              <PrimaryButton 
                size="sm" 
                @click="prepareBatchAssignment(suggestion)"
                :disabled="!suitableDriverTruckSets(suggestion).length"
              >
                Assign Batch
              </PrimaryButton>
            </div>
            
            <div v-if="suitableDriverTruckSets(suggestion).length" class="mt-4">
              <p class="text-sm font-medium">Suitable Driver-Truck Sets:</p>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mt-2">
                <div v-for="set in suitableDriverTruckSets(suggestion)" 
                     :key="set.id"
                     class="p-2 border rounded cursor-pointer"
                     :class="{ 'border-blue-500': selectedSet?.id === set.id }"
                     @click="selectSet(set)">
                  <p class="font-medium">{{ set.driver.name }}</p>
                  <p class="text-xs">{{ set.truck.license_plate }}</p>
                  <p class="text-xs">Available: {{ set.available_volume.toFixed(2) }}m³ {{ set.available_weight.toFixed(2) }}kg</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="text-center text-gray-500 dark:text-gray-400">
          No batch assignment suggestions available
        </div>
      </div>
    </div>

    <!-- Assignment Confirmation Modal -->
    <ConfirmationModal 
      :show="showAssignmentModal"
      @close="closeAssignmentModal"
      @confirmed="submitAssignment"
      title="Confirm Assignment"
      confirm-text="Confirm Assignment"
      :confirm-disabled="!validationSummary.isValid"
    >
      <div class="space-y-4">
        <div class="p-4 rounded-lg" :class="{
          'bg-green-50 text-green-800': validationSummary.isValid,
          'bg-red-50 text-red-800': !validationSummary.isValid
        }">
          <p class="font-medium">{{ validationSummary.message }}</p>
          <ul v-if="validationSummary.details" class="mt-2 list-disc list-inside">
            <li v-for="detail in validationSummary.details" :key="detail">{{ detail }}</li>
          </ul>
          <div v-if="validationSummary.etaWarning" class="mt-2 text-yellow-700 text-xs">
            ⚠️ {{ validationSummary.etaWarning }}
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-sm font-medium">Total Selected Volume</p>
            <p class="text-lg">{{ totalSelectedVolume }} m³</p>
            <p class="text-xs">Available: {{ validationSummary.availableVolume.toFixed(2) }} m³</p>
          </div>
          <div>
            <p class="text-sm font-medium">Total Selected Weight</p>
            <p class="text-lg">{{ totalSelectedWeight }} kg</p>
            <p class="text-xs">Available: {{ validationSummary.availableWeight.toFixed(2) }} kg</p>
          </div>
        </div>

        <div class="mt-4">
          <InputLabel value="Estimated Departure Time" />
          <DateTimeInput
            v-model="assignmentForm.estimated_departure"
            class="w-full"
            :min="minDepartureDateTime"
          />
          <div v-if="autoDepartureNote" class="text-xs text-blue-600 mt-1">
            {{ autoDepartureNote }}
          </div>
        </div>

        <div v-if="calculatedETA" class="mt-2 text-sm">
          <p>Estimated Arrival: {{ formatDateTime(calculatedETA) }}</p>
        </div>
      </div>
    </ConfirmationModal>

    <!-- Cancel Confirmation Modal -->
    <ConfirmationModal 
      :show="showCancelModal"
      @close="closeCancelModal"
      @confirmed="cancelAssignment"
      title="Cancel Assignment"
      confirm-text="Yes, Cancel"
      confirm-variant="danger"
    >
      <p>Are you sure you want to cancel this assignment?</p>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">This action cannot be undone.</p>
    </ConfirmationModal>

    <!-- Validation Modal -->
    <ConfirmationModal 
      :show="showValidationModal"
      @close="closeValidationModal"
      @confirmed="submitAssignment"
      title="Assignment Validation"
      confirm-text="Confirm Assignment"
      :confirm-disabled="!validationSummary.isValid"
    >
      <div v-if="validationSummary.value">
        <div class="space-y-4">
          <div class="p-4 rounded-lg" :class="{
            'bg-green-50 text-green-800': validationSummary.value.isValid,
            'bg-red-50 text-red-800': !validationSummary.value.isValid
          }">
            <p class="font-medium">{{ validationSummary.value.message }}</p>
            <ul v-if="validationSummary.value.details" class="mt-2 list-disc list-inside">
              <li v-for="detail in validationSummary.value.details" :key="detail">{{ detail }}</li>
            </ul>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm font-medium">Total Selected Volume</p>
              <p class="text-lg">{{ totalSelectedVolume }} m³</p>
              <p class="text-xs">Available: {{ validationSummary.value.availableVolume.toFixed(2) }} m³</p>
            </div>
            <div>
              <p class="text-sm font-medium">Total Selected Weight</p>
              <p class="text-lg">{{ totalSelectedWeight }} kg</p>
              <p class="text-xs">Available: {{ validationSummary.value.availableWeight.toFixed(2) }} kg</p>
            </div>
          </div>

          <div v-if="selectedSet" class="mt-4">
            <p class="text-sm font-medium">Driver Status</p>
            <p class="flex items-center">
              <span class="inline-block w-2 h-2 rounded-full mr-2" :class="{
                'bg-green-500': selectedSet.driver.available,
                'bg-red-500': !selectedSet.driver.available
              }"></span>
              {{ selectedSet.driver.available ? 'Available' : 'Unavailable' }}
            </p>
            <p class="text-xs">Current assignments: {{ selectedSet.driver.current_assignments }}</p>
          </div>

          <div v-if="selectedSet && selectedSet.active_orders?.length" class="mt-4">
            <p class="text-sm font-medium">Current Trips</p>
            <div v-for="order in selectedSet.active_orders" :key="order.id" class="text-xs">
              DO-{{ order.id }}: {{ order.status }}
            </div>
          </div>

          <DateTimeInput
            v-model="assignmentForm.estimated_departure"
            label="Estimated Departure"
            class="mt-4"
          />

          <div v-if="calculatedETA" class="mt-2 text-sm">
            <p>Estimated Arrival: {{ formatDateTime(calculatedETA) }}</p>
          </div>
        </div>
      </div>
    </ConfirmationModal>

    <!-- Dispatch Confirmation Modal -->
    <ConfirmationModal
      :show="showDispatchModal"
      @close="closeDispatchModal"
      @confirmed="confirmDispatch"
      title="Dispatch Driver-Truck Set"
      confirm-text="Confirm Dispatch"
      :confirm-disabled="!dispatchValidation.can_dispatch || dispatchValidation.loading"
    >
      <div class="space-y-3">
        <div v-if="dispatchValidation.loading" class="flex items-center space-x-2">
          <LoadingSpinner size="sm" />
          <span>Validating manifest and waybills...</span>
        </div>
        <div v-else>
          <div v-if="dispatchValidation.can_dispatch" class="bg-green-50 text-green-800 p-2 rounded">
            <p>All checks passed. Ready to dispatch this set.</p>
          </div>
          <div v-else class="bg-red-50 text-red-800 p-2 rounded">
            <p v-if="!dispatchValidation.has_manifest">
              Missing Manifest: Manifest must be created and finalized.
            </p>
            <p v-if="dispatchValidation.missing_waybills?.length">
              Missing waybills:
              <span>
                {{
                  dispatchValidation.missing_waybills
                    .map(id => `DO-${id.toString().padStart(6, '0')}`)
                    .join(', ')
                }}
              </span>
            </p>
            <p v-if="dispatchValidation.message && !dispatchValidation.can_dispatch && dispatchValidation.has_manifest">
              {{ dispatchValidation.message }}
            </p>
          </div>
        </div>
      </div>
    </ConfirmationModal>
  </EmployeeLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { formatDate, formatDateTime, calculateTotalVolume, calculateTotalWeight } from '@/Utils/helpers';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextArea from '@/Components/TextArea.vue';
import SelectInput from '@/Components/SelectInput.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import DataTable from '@/Components/DataTable.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import SearchInput from '@/Components/SearchInput.vue';
import DateTimeInput from '@/Components/DateTimeInput.vue';
import { useToast } from 'vue-toastification';
const toast = useToast();

const props = defineProps({
  deliveries: {
    type: Object,
    default: () => ({ data: [] })
  },
  driverTruckSets: {
    type: Array,
    default: () => []
  },
  regions: {
    type: Array,
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  flash: {
    type: Object,
    default: () => ({})
  }
});

// Use computed to always get the latest from props
const driverTruckSets = computed(() => props.driverTruckSets);
const deliveries = computed(() => props.deliveries);
const regions = computed(() => props.regions);

const loading = ref(false);
const selectedDeliveries = ref([]);
const selectedSet = ref(null);
const showCancelModal = ref(false);
const deliveryToCancel = ref(null);
const searchTerm = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const regionFilter = ref(props.filters?.region_id || '');

// Forms
const assignmentForm = useForm({
  driver_truck_assignment_id: null,
  delivery_request_ids: [],
  // Set default to 1 day (24 hours) in the future, formatted as 'YYYY-MM-DDTHH:mm'
  estimated_departure: (() => {
    const now = new Date();
    now.setDate(now.getDate() + 1);
    return now.toISOString().slice(0, 16);
  })(),
  notes: null
});

// Options
const statusOptions = [
  { value: '', label: 'All Statuses' },
  { value: 'ready', label: 'Ready' },
  { value: 'assigned', label: 'Assigned' },
  { value: 'dispatched', label: 'Dispatched' },
  { value: 'in_transit', label: 'In Transit' },
  { value: 'delivered', label: 'Delivered' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' },
  { value: 'pending_payment', label: 'Pending Payment' }
];

const regionOptions = computed(() => [
  { value: '', label: 'All Regions' },
  ...regions.value.map(region => ({
    value: region.id,
    label: region.name
  }))
]);

// Table Columns
const deliveryColumns = [
  { 
    field: 'id', 
    header: 'DO #', 
    class: 'w-24',
    sortable: true,
    formatter: value => value ? `DO-${value.toString().padStart(6, '0')}` : 'N/A'
  },
  { 
    field: 'delivery_request', 
    header: 'Route', 
    formatter: (value) => {
      if (!value) return 'N/A';
      const pickup = value.pick_up_region?.name || 'N/A';
      const dropoff = value.drop_off_region?.name || 'N/A';
      return `${pickup} → ${dropoff}`;
    },
    sortable: true
  },
  { 
    field: 'status', 
    header: 'Status', 
    slot: true,
    sortable: true
  },
  { 
    field: 'packages', // Use a slot for packages
    header: 'Packages',
    slot: true,
    class: 'text-center',
    sortable: false
  },
  { 
    field: 'estimated_departure', 
    header: 'Est. Departure', 
    formatter: value => value ? formatDate(value) : 'N/A',
    class: 'whitespace-nowrap',
    sortable: true
  },
  { 
    field: 'actions', 
    header: 'Actions', 
    slot: true,
    class: 'w-32 text-right'
  }
];

// Computed Properties
const hasFlashMessages = computed(() => {
  return props.flash && (props.flash.status || props.flash.success || props.flash.error);
});

const totalSelectedVolume = computed(() => {
  if (!selectedDeliveries.value?.length) return 0;
  return selectedDeliveries.value.reduce((sum, delivery) => {
    const packages = delivery.delivery_request?.packages || [];
    return sum + calculateTotalVolume(packages);
  }, 0).toFixed(2);
});

const totalSelectedWeight = computed(() => {
  if (!selectedDeliveries.value?.length) return 0;
  return selectedDeliveries.value.reduce((sum, delivery) => {
    const packages = delivery.delivery_request?.packages || [];
    return sum + calculateTotalWeight(packages);
  }, 0).toFixed(2);
});

const validationSummary = ref({
  isValid: false,
  message: '',
  details: null,
  totalVolume: 0,
  totalWeight: 0,
  availableVolume: 0,
  availableWeight: 0,
  etaWarning: null,
});

const regionDurations = ref([]); // [{from_region_id, to_region_id, estimated_minutes}, ...]

const calculatedETA = computed(() => {
  if (!assignmentForm.estimated_departure || !selectedDeliveries.value.length) return null;
  
  // Find the longest duration among selected deliveries
  let maxDuration = 0;
  selectedDeliveries.value.forEach(delivery => {
    const fromRegion = delivery.delivery_request.pick_up_region_id;
    const toRegion = delivery.delivery_request.drop_off_region_id;
    
    // Get duration from database or use default
    const duration = regionDurations.value.find(d => 
      d.from_region_id === fromRegion && d.to_region_id === toRegion
    )?.estimated_minutes || 1440; // default 24 hours
    
    if (duration > maxDuration) maxDuration = duration;
  });

  const departure = new Date(assignmentForm.estimated_departure);
  departure.setMinutes(departure.getMinutes() + maxDuration);
  return departure.toISOString();
});

// Minimum allowed date/time for Estimated Departure (now, rounded to nearest minute)
const minDepartureDateTime = computed(() => {
  const now = new Date();
  now.setSeconds(0, 0);
  return now.toISOString().slice(0, 16);
});

// Methods
function handleSelectionChange(selected = []) {
  selectedDeliveries.value = selected || [];
  // --- FIX: Reset selectedSet and assignmentForm when selection changes ---
  // This ensures that after clearing or changing selection, the assignment form is always in sync.
  assignmentForm.delivery_request_ids = selectedDeliveries.value
    .map(d => d.delivery_request?.id)
    .filter(Boolean);
  // Optionally, reset selectedSet if you want to force user to reselect after changing selection:
  // selectedSet.value = null;
}

function removeDelivery(id) {
  if (!selectedDeliveries.value?.length) return;
  selectedDeliveries.value = selectedDeliveries.value.filter(d => d?.id !== id);
}

function clearSelection() {
  selectedDeliveries.value = [];
}

function selectSet(set) {
  selectedSet.value = set;
  assignmentForm.driver_truck_assignment_id = set.id;
}

function resetSelection() {
  selectedDeliveries.value = [];
  selectedSet.value = null;
  assignmentForm.reset();
  // Reset to 1 day in the future on clear
  const now = new Date();
  now.setDate(now.getDate() + 1);
  assignmentForm.estimated_departure = now.toISOString().slice(0, 16);
}

function applyFilters(page = 1) {
  router.get(route('cargo-assignments.index'), {
    search: searchTerm.value,
    status: statusFilter.value,
    region_id: regionFilter.value,
    per_page: 5, // Force 5 items per page
    page
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
    only: ['deliveries', 'driverTruckSets', 'regions', 'filters', 'flash'],
    onStart: () => loading.value = true,
    onFinish: () => loading.value = false
  });
}

function resetFilters() {
  searchTerm.value = '';
  statusFilter.value = '';
  regionFilter.value = '';
}

function handlePageChange(page) {
  applyFilters(page);
}

function refreshData() {
  router.get(route('cargo-assignments.index'), {
    search: searchTerm.value,
    status: statusFilter.value,
    region_id: regionFilter.value,
    per_page: 5, // Add this
    page: deliveries.value?.current_page || 1
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
    only: ['deliveries', 'driverTruckSets', 'regions', 'filters', 'flash'],
    onStart: () => loading.value = true,
    onFinish: () => loading.value = false
  });
}

function viewDetails(deliveryId) {
  router.visit(route('cargo-assignments.show', deliveryId));
}

function confirmCancel(delivery) {
  deliveryToCancel.value = delivery;
  showCancelModal.value = true;
}

function cancelAssignment() {
  if (!deliveryToCancel.value) return;
  
  router.post(route('cargo-assignments.cancel', deliveryToCancel.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showCancelModal.value = false;
      deliveryToCancel.value = null;
      refreshData();
    },
    onError: (errors) => {
      console.error('Error cancelling assignment:', errors);
    }
  });
}

const showValidationModal = ref(false);
const showAssignmentModal = ref(false);
const batchSuggestions = ref([]);
const autoDepartureNote = ref('');

// --- Extracted function to fetch batch suggestions ---
async function fetchBatchSuggestions() {
  batchSuggestions.value = [];
  if (!regionFilter.value) return;

  loading.value = true;
  try {
    // FIX: Use the correct route name as defined in web.php
    const response = await axios.get(route('cargo-assignments.assign.suggestions'), {
      params: { 
        region_id: regionFilter.value,
        // Exclude already assigned deliveries
        exclude_assigned: true 
      }
    });

    batchSuggestions.value = Object.entries(response.data.deliveries_by_destination || {}).map(([regionId, deliveries]) => {
      // Filter out deliveries that are already assigned
      const unassignedDeliveries = deliveries.filter(d => d.status === 'ready');
      
      if (!unassignedDeliveries.length) return null;

      const total_volume = unassignedDeliveries.reduce((sum, d) => sum + (d.delivery_request?.packages?.reduce((s, p) => s + (p.volume || 0), 0) || 0), 0);
      const total_weight = unassignedDeliveries.reduce((sum, d) => sum + (d.delivery_request?.packages?.reduce((s, p) => s + (p.weight || 0), 0) || 0), 0);
      
      return {
        destination_region: unassignedDeliveries[0]?.delivery_request?.drop_off_region || { id: regionId, name: 'Unknown' },
        delivery_requests: unassignedDeliveries,
        total_volume,
        total_weight
      };
    }).filter(Boolean); // Remove null entries
  } catch (error) {
    console.error('Error fetching suggestions:', error);
    batchSuggestions.value = [];
  } finally {
    loading.value = false;
  }
}

// --- Use the new function in the watcher ---
watch(regionFilter, debounce(fetchBatchSuggestions, 300), { immediate: true });

watch(regionFilter, () => {
  applyFilters();
});

watch(statusFilter, () => {
  applyFilters();
});

// Refresh suggestions when deliveries data changes and region filter is applied
watch(() => props.deliveries, () => {
  if (regionFilter.value) {
    fetchBatchSuggestions();
  }
}, { deep: true });

// --- existing code ---
function suitableDriverTruckSets(suggestion) {
  // Prioritize sets that already have assigned orders for the same route (pickup and drop-off region)
  const setsWithSameRoute = driverTruckSets.value.filter(set => {
    return (set.active_orders || []).some(order =>
      order.status === 'assigned' &&
      order.delivery_request?.pick_up_region_id === suggestion.delivery_requests[0]?.delivery_request?.pick_up_region_id &&
      order.delivery_request?.drop_off_region_id === suggestion.destination_region.id
    ) &&
    (set.truck.volume_capacity - set.current_volume) >= suggestion.total_volume &&
    (set.truck.weight_capacity - set.current_weight) >= suggestion.total_weight &&
    set.driver.available;
  });

  if (setsWithSameRoute.length) {
    // Sort by fewest assignments, then by last assigned
    return setsWithSameRoute.sort((a, b) => {
      if (a.driver.current_assignments !== b.driver.current_assignments) {
        return a.driver.current_assignments - b.driver.current_assignments;
      }
      return new Date(a.driver.last_assigned_at) - new Date(b.driver.last_assigned_at);
    });
  }

  // Otherwise, fallback to all suitable sets as before
  return driverTruckSets.value.filter(set => {
    if (regionFilter.value && set.region?.id != regionFilter.value) {
      return false;
    }
    return (set.truck.volume_capacity - set.current_volume) >= suggestion.total_volume &&
           (set.truck.weight_capacity - set.current_weight) >= suggestion.total_weight &&
           set.driver.available;
  }).sort((a, b) => {
    if (a.driver.current_assignments !== b.driver.current_assignments) {
      return a.driver.current_assignments - b.driver.current_assignments;
    }
    return new Date(a.driver.last_assigned_at) - new Date(b.driver.last_assigned_at);
  });
}

async function prepareBatchAssignment(suggestion) {
  selectedDeliveries.value = suggestion.delivery_requests.map(dr => ({
    ...dr,
    delivery_request: dr.delivery_request
  }));

  const suitableSets = suitableDriverTruckSets(suggestion);
  if (suitableSets.length) {
    selectSet(suitableSets[0]);

    // --- Improved: Only consider 'assigned' orders for the same destination region ---
    const assignedOrdersForRegion = (suitableSets[0].active_orders || [])
      .filter(order =>
        order.status === 'assigned' &&
        order.delivery_request?.drop_off_region_id === suggestion.destination_region.id &&
        order.estimated_departure
      );

    if (assignedOrdersForRegion.length) {
      // Use the earliest estimated_departure among matching assigned orders
      const earliestDeparture = assignedOrdersForRegion
        .map(o => new Date(o.estimated_departure))
        .sort((a, b) => a - b)[0]
        .toISOString()
        .slice(0, 16);
      assignmentForm.estimated_departure = earliestDeparture;
      autoDepartureNote.value = 'Departure time auto-filled to match existing assigned trip for this region.';
    } else {
      autoDepartureNote.value = '';
    }
  } else {
    // If no suitable set, show error and do not proceed
    validationSummary.value = {
      isValid: false,
      message: 'No suitable Driver-Truck set available for this batch.',
      details: null,
      totalVolume: 0,
      totalWeight: 0,
      availableVolume: 0,
      availableWeight: 0,
      etaWarning: null,
    };
    showAssignmentModal.value = true;
    return;
  }

  await validateAssignment(); // Wait for validation to finish
  showAssignmentModal.value = true; // Open the modal after validation
}

async function validateAssignment() {
  // Defensive: must have selection and set
  if (!selectedSet.value || !selectedDeliveries.value.length) {
    validationSummary.value = {
      isValid: false,
      message: 'No deliveries or set selected',
      details: null,
      totalVolume: 0,
      totalWeight: 0,
      availableVolume: 0,
      availableWeight: 0,
      etaWarning: null,
    };
    return;
  }

  // Prepare IDs for backend validation
  const deliveryOrderIds = selectedDeliveries.value.map(d => d.id).filter(Boolean);
  const driverTruckAssignmentId = selectedSet.value.id;
  const estimatedDeparture = assignmentForm.estimated_departure;

  try {
    const { data } = await axios.post(route('cargo-assignments.assign.validate'), {
      delivery_order_ids: deliveryOrderIds,
      driver_truck_assignment_id: driverTruckAssignmentId,
      estimated_departure: estimatedDeparture,
    });

    validationSummary.value = {
      isValid: data.is_valid,
      message: data.is_valid
        ? (data.is_reassignment
            ? 'You are reassigning one or more delivery orders that are already assigned!'
            : 'Ready to assign')
        : 'Cannot assign due to:',
      details: data.errors && data.errors.length ? data.errors : null,
      totalVolume: data.total_volume,
      totalWeight: data.total_weight,
      availableVolume: data.available_volume,
      availableWeight: data.available_weight,
      etaWarning: data.eta_warning || null,
      isReassignment: !!data.is_reassignment, // <-- Add this line
    };
  } catch (error) {
    validationSummary.value = {
      isValid: false,
      message: 'Validation failed. Please try again.',
      details: [error?.response?.data?.message || error.message],
      totalVolume: 0,
      totalWeight: 0,
      availableVolume: 0,
      availableWeight: 0,
      etaWarning: null,
    };
  }
}

async function submitAssignment() {
  if (!validationSummary.value.isValid || !selectedSet.value) return;

  assignmentForm.delivery_request_ids = selectedDeliveries.value
    .map(d => d.delivery_request?.id)
    .filter(Boolean);

  try {
    await assignmentForm.post(route('cargo-assignments.assign.batch'), {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Assignment successful!');
        resetSelection();
        // Always reload with per_page: 5 after assignment
        Promise.all([
          router.get(route('cargo-assignments.index'), {
            search: searchTerm.value,
            status: statusFilter.value,
            region_id: regionFilter.value,
            per_page: 5,
            page: 1
          }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['deliveries', 'driverTruckSets', 'regions', 'filters', 'flash'],
            onStart: () => loading.value = true,
            onFinish: () => loading.value = false
          }),
          fetchBatchSuggestions()
        ]).then(() => {
          loading.value = false;
        });
      },
      onError: (errors) => {
        toast.error('Assignment failed: ' + (errors.message || 'Unknown error'));
        console.error('Assignment errors:', errors);
      }
    });
  } catch (error) {
    console.error('Assignment failed:', error);
    toast.error('Assignment failed: ' + error.message);
  } finally {
    showAssignmentModal.value = false;
  }
}

// Modal control functions
const openAssignmentModal = async () => {
  if (!selectedDeliveries.value.length) {
    validationSummary.value = {
      isValid: false,
      message: 'Please select at least one delivery order.',
      details: null,
      totalVolume: 0,
      totalWeight: 0,
      availableVolume: 0,
      availableWeight: 0,
      etaWarning: null,
    };
    showAssignmentModal.value = true;
    return;
  }

  if (!selectedSet.value) {
    toast.error('Please select an available Driver-Truck set before assigning.');
    return;
  }

  assignmentForm.delivery_request_ids = selectedDeliveries.value
    .map(d => d.delivery_request?.id)
    .filter(Boolean);

  if (selectedSet.value) {
    assignmentForm.driver_truck_assignment_id = selectedSet.value.id;

    // --- Add this block for single assignment auto-fill ---
    // Only apply if assigning a single delivery
    if (selectedDeliveries.value.length === 1) {
      const delivery = selectedDeliveries.value[0];
      const dropOffRegionId = delivery?.delivery_request?.drop_off_region_id;
      // Find assigned orders for this set and region
      const assignedOrdersForRegion = (selectedSet.value.active_orders || [])
        .filter(order =>
          order.status === 'assigned' &&
          order.delivery_request?.drop_off_region_id === dropOffRegionId &&
          order.estimated_departure
        );
      if (assignedOrdersForRegion.length) {
        const earliestDeparture = assignedOrdersForRegion
          .map(o => new Date(o.estimated_departure))
          .sort((a, b) => a - b)[0]
          .toISOString()
          .slice(0, 16);
        assignmentForm.estimated_departure = earliestDeparture;
        autoDepartureNote.value = 'Departure time auto-filled to match existing assigned trip for this region.';
      } else {
        autoDepartureNote.value = '';
      }
    }
    // --- End block ---
  }

  // If estimated_departure is in the past, set it to 1 day in the future before validation
  let dep = new Date(assignmentForm.estimated_departure);
  const now = new Date();
  if (dep < now) {
    dep = new Date();
    dep.setDate(dep.getDate() + 1);
    assignmentForm.estimated_departure = dep.toISOString().slice(0, 16);
  }

  await validateAssignment();
  showAssignmentModal.value = true;
};

const closeAssignmentModal = () => {
  showAssignmentModal.value = false;
};

const closeCancelModal = () => {
  showCancelModal.value = false;
};

const closeValidationModal = () => {
  showValidationModal.value = false;
};

const showDispatchModal = ref(false);
const dispatchingSetId = ref(null);
const setToDispatch = ref(null);
const dispatchValidation = ref({
  loading: false,
  has_manifest: false,
  missing_waybills: [],
  can_dispatch: false,
  message: '',
});

// Always fetch fresh validation when opening the modal
async function openDispatchModal(set) {
  setToDispatch.value = set;
  showDispatchModal.value = true;
  dispatchValidation.value = { loading: true, has_manifest: false, missing_waybills: [], can_dispatch: false, message: '' };

  try {
    // Always fetch latest validation from backend
    const { data } = await axios.get(route('cargo-assignments.dispatch.driver-truck-set.validate', set.id), {
      headers: { 'Cache-Control': 'no-cache' }
    });
    dispatchValidation.value = {
      ...data,
      loading: false,
      message: data.message || '',
    };
  } catch (error) {
    dispatchValidation.value = {
      loading: false,
      has_manifest: false,
      missing_waybills: [],
      can_dispatch: false,
      message: error?.response?.data?.message || error.message || 'Validation failed. Please try again.',
    };
    toast.error(dispatchValidation.value.message);
  }
}

function closeDispatchModal() {
  showDispatchModal.value = false;
  setToDispatch.value = null;
  dispatchValidation.value = {
    loading: false,
    has_manifest: false,
    missing_waybills: [],
    can_dispatch: false,
    message: '',
  };
}

async function confirmDispatch() {
  if (!setToDispatch.value || !dispatchValidation.value.can_dispatch) return;
  dispatchingSetId.value = setToDispatch.value.id;
  try {
    await axios.post(route('cargo-assignments.dispatch.driver-truck-set', setToDispatch.value.id));
    toast.success('Dispatch successful!');
    // Always reload both deliveries and driverTruckSets after dispatch
    await Promise.all([
      refreshData(),
      fetchBatchSuggestions()
    ]);
  } catch (error) {
    toast.error(error?.response?.data?.message || 'Dispatch failed.');
  } finally {
    dispatchingSetId.value = null;
    closeDispatchModal();
  }
}

function formatStatusText(status) {
  const statusMap = {
    'ready': 'Ready',
    'assigned': 'Assigned',
    'dispatched': 'Dispatched',
    'in_transit': 'In Transit',
    'delivered': 'Delivered',
    'completed': 'Completed',
    'cancelled': 'Cancelled',
    'pending_payment': 'Pending Payment'
  };
  return statusMap[status] || status;
}

// Add this function for status badge color
function statusBadgeClass(status) {
  switch (status) {
    case 'completed':
      return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200';
    case 'delivered':
      return 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-200';
    case 'returned':
      return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200';
    case 'in_transit':
    case 'loaded':
    case 'ready_for_pickup':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200';
    case 'rejected':
      return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200';
    case 'pending':
    case 'preparing':
    case 'processing':
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200';
    case 'pending_payment':
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200';
    default:
      return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
  }
}
</script>