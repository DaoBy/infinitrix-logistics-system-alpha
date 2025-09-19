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
            <div class="flex flex-col items-center">
              <span>{{ row.delivery_request?.packages?.length ?? 0 }}</span>
              <!-- Show warning icon if any packages are missing stickers -->
              <span v-if="hasUnstickerizedPackages(row)" class="text-yellow-500" title="Some packages are missing stickers">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
              </span>
            </div>
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
                        <!-- Show warning if packages are missing stickers -->
                        <p v-if="hasUnstickerizedPackages(delivery)" class="text-yellow-600 dark:text-yellow-400 flex items-center">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                          </svg>
                          Missing stickers
                        </p>
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
                <!-- Show warning if any selected delivery has packages without stickers -->
                <p v-if="hasAnyUnstickerizedPackages" class="text-yellow-600 dark:text-yellow-400 font-medium">
                  ⚠️ Some packages are missing stickers
                </p>
              </div>
              <PrimaryButton 
                class="mt-4 w-full"
                :disabled="!selectedSet || !selectedDeliveries.length || hasAnyUnstickerizedPackages"
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
                From: {{ suggestion.pickup_region?.name ?? 'N/A' }} → To: {{ suggestion.destination_region.name }}
              </p>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ suggestion.delivery_requests.length }} deliveries
              </p>
              <p class="text-sm mt-2">
                Total Volume: {{ suggestion.total_volume.toFixed(2) }} m³ | 
                Total Weight: {{ suggestion.total_weight.toFixed(2) }} kg
              </p>
              <!-- Show warning if any delivery has packages without stickers -->
              <p v-if="hasUnstickerizedPackagesInSuggestion(suggestion)" class="text-yellow-600 dark:text-yellow-400 text-sm mt-1">
                ⚠️ Some deliveries have packages without stickers
              </p>
            </div>
            <PrimaryButton 
              size="sm" 
              @click="prepareBatchAssignment(suggestion)"
              :disabled="!suitableDriverTruckSets(suggestion).length || hasUnstickerizedPackagesInSuggestion(suggestion)"
            >
              Assign Batch
            </PrimaryButton>
          </div>
          
          <div v-if="suitableDriverTruckSets(suggestion).length" class="mt-4">
            <p class="text-sm font-medium">Suitable Driver-Truck Sets (Region: {{ suggestion.pickup_region?.name ?? 'N/A' }}):</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mt-2">
              <div v-for="set in suitableDriverTruckSets(suggestion)" 
                   :key="set.id"
                   class="p-2 border rounded cursor-pointer"
                   :class="{ 'border-blue-500': selectedSet?.id === set.id }"
                   @click="selectSet(set)">
                <p class="font-medium">{{ set.driver.name }}</p>
                <p class="text-xs">{{ set.truck.license_plate }}</p>
                <p class="text-xs">Available: {{ set.available_volume.toFixed(2) }}m³ {{ set.available_weight.toFixed(2) }}kg</p>
                <p class="text-xs text-gray-500">Region: {{ set.region?.name ?? 'N/A' }}</p>
              </div>
            </div>
          </div>
          <div v-else class="mt-4 text-sm text-yellow-600">
            No suitable driver-truck sets available in region: {{ suggestion.pickup_region?.name ?? 'N/A' }}
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
  if (!selectedDeliveries.value.length) return 0;
  return selectedDeliveries.value.reduce((total, delivery) => {
    const packages = delivery.delivery_request?.packages || [];
    return total + calculateTotalVolume(packages);
  }, 0);
});

const totalSelectedWeight = computed(() => {
  if (!selectedDeliveries.value.length) return 0;
  return selectedDeliveries.value.reduce((total, delivery) => {
    const packages = delivery.delivery_request?.packages || [];
    return total + calculateTotalWeight(packages);
  }, 0);
});

// Check if any selected delivery has packages without stickers
const hasAnyUnstickerizedPackages = computed(() => {
  return selectedDeliveries.value.some(delivery => hasUnstickerizedPackages(delivery));
});

// Helper function to check if a delivery has packages without stickers
const hasUnstickerizedPackages = (delivery) => {
  const packages = delivery.delivery_request?.packages || [];
  return packages.some(pkg => pkg.sticker_printed_at === null);
};

// Check if a batch suggestion has deliveries with packages without stickers
const hasUnstickerizedPackagesInSuggestion = (suggestion) => {
  return suggestion.delivery_requests.some(delivery => hasUnstickerizedPackages(delivery));
};

// Methods
const handleSelectionChange = (selectedRows) => {
  selectedDeliveries.value = selectedRows;
};

const removeDelivery = (deliveryId) => {
  selectedDeliveries.value = selectedDeliveries.value.filter(d => d.id !== deliveryId);
};

const clearSelection = () => {
  selectedDeliveries.value = [];
};

const selectSet = (set) => {
  selectedSet.value = set;
};

const refreshData = () => {
  loading.value = true;
  router.reload({
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false;
    }
  });
};

const applyFilters = debounce(() => {
  router.get(route('cargo-assignments.index'), { // Changed route name
    search: searchTerm.value,
    status: statusFilter.value,
    region_id: regionFilter.value
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
}, 300);


const resetFilters = () => {
  searchTerm.value = '';
  statusFilter.value = '';
  regionFilter.value = '';
  applyFilters();
};

const handlePageChange = (page) => {
  router.get(route('employee.assignments.index'), {
    ...props.filters,
    page
  }, {
    preserveState: true,
    preserveScroll: true
  });
};

const viewDetails = (deliveryId) => {
  router.get(route('employee.delivery-orders.show', deliveryId));
};

const confirmCancel = (delivery) => {
  deliveryToCancel.value = delivery;
  showCancelModal.value = true;
};

const closeCancelModal = () => {
  showCancelModal.value = false;
  deliveryToCancel.value = null;
};

const cancelAssignment = () => {
  if (!deliveryToCancel.value) return;
  
  router.delete(route('employee.delivery-orders.cancel', deliveryToCancel.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Delivery order cancelled successfully');
      closeCancelModal();
    },
    onError: (errors) => {
      toast.error('Failed to cancel delivery order');
    }
  });
};

const statusBadgeClass = (status) => {
  const classes = {
    ready: 'bg-blue-100 text-blue-800',
    assigned: 'bg-yellow-100 text-yellow-800',
    dispatched: 'bg-purple-100 text-purple-800',
    in_transit: 'bg-indigo-100 text-indigo-800',
    delivered: 'bg-green-100 text-green-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
    pending_payment: 'bg-orange-100 text-orange-800'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

const formatStatusText = (status) => {
  const statusMap = {
    ready: 'Ready',
    assigned: 'Assigned',
    dispatched: 'Dispatched',
    in_transit: 'In Transit',
    delivered: 'Delivered',
    completed: 'Completed',
    cancelled: 'Cancelled',
    pending_payment: 'Pending Payment'
  };
  return statusMap[status] || status;
};

// Watch for filter changes
watch([searchTerm, statusFilter, regionFilter], applyFilters);

// Batch Assignment Suggestions - ensure they only include sets that match the delivery's region
const batchSuggestions = computed(() => {
  const readyDeliveries = deliveries.value.data?.filter(d => d.status === 'ready') || [];
  const suggestions = {};
  
  readyDeliveries.forEach(delivery => {
    const regionId = delivery.delivery_request?.drop_off_region?.id;
    const pickupRegionId = delivery.delivery_request?.pick_up_region?.id;
    
    if (!regionId || !pickupRegionId) return;
    
    if (!suggestions[regionId]) {
      suggestions[regionId] = {
        destination_region: delivery.delivery_request.drop_off_region,
        pickup_region: delivery.delivery_request.pick_up_region, // Store pickup region too
        delivery_requests: [],
        total_volume: 0,
        total_weight: 0
      };
    }
    
    suggestions[regionId].delivery_requests.push(delivery);
    const packages = delivery.delivery_request?.packages || [];
    suggestions[regionId].total_volume += calculateTotalVolume(packages);
    suggestions[regionId].total_weight += calculateTotalWeight(packages);
  });
  
  return Object.values(suggestions);
});

// Suitable driver-truck sets should consider region matching even when "All Regions" is selected
const suitableDriverTruckSets = (suggestion) => {
  // Get the pickup region ID from the first delivery in the suggestion
  const pickupRegionId = suggestion.delivery_requests[0]?.delivery_request?.pick_up_region_id;
  
  if (!pickupRegionId) return [];
  
  return driverTruckSets.value.filter(set => {
    // The set must be in the same region as the delivery's pickup region
    const regionMatch = set.region?.id == pickupRegionId;
    
    const availableVolume = (set.truck?.volume_capacity || 0) - (set.current_volume || 0);
    const availableWeight = (set.truck?.weight_capacity || 0) - (set.current_weight || 0);
    
    return regionMatch && 
           set.is_available && 
           availableVolume >= suggestion.total_volume && 
           availableWeight >= suggestion.total_weight;
  });
};

const prepareBatchAssignment = (suggestion) => {
  selectedDeliveries.value = suggestion.delivery_requests;
  const suitableSets = suitableDriverTruckSets(suggestion);
  if (suitableSets.length > 0) {
    selectedSet.value = suitableSets[0];
    openAssignmentModal();
  } else {
    toast.error('No suitable driver-truck sets available for this batch');
  }
};

// Assignment Modal
const showAssignmentModal = ref(false);
const validationSummary = ref({
  isValid: false,
  message: '',
  details: [],
  availableVolume: 0,
  availableWeight: 0,
  etaWarning: ''
});

const openAssignmentModal = () => {
  if (!selectedSet.value || selectedDeliveries.value.length === 0) {
    toast.error('Please select both deliveries and a driver-truck set');
    return;
  }

  // Check if any selected delivery has packages without stickers (sticker_printed_at is null)
  if (hasAnyUnstickerizedPackages.value) {
    validationSummary.value = {
      isValid: false,
      message: 'Cannot assign deliveries with packages missing stickers',
      details: ['One or more packages have not been labeled (sticker_printed_at is null)'],
      availableVolume: selectedSet.value.truck.volume_capacity - selectedSet.value.current_volume,
      availableWeight: selectedSet.value.truck.weight_capacity - selectedSet.value.current_weight
    };
    showAssignmentModal.value = true;
    return;
  }

  // Validate capacity
  const availableVolume = selectedSet.value.truck.volume_capacity - selectedSet.value.current_volume;
  const availableWeight = selectedSet.value.truck.weight_capacity - selectedSet.value.current_weight;
  
  const volumeValid = totalSelectedVolume.value <= availableVolume;
  const weightValid = totalSelectedWeight.value <= availableWeight;
  const driverAvailable = selectedSet.value.driver.canAcceptNewAssignment;

  validationSummary.value = {
    isValid: volumeValid && weightValid && driverAvailable,
    message: volumeValid && weightValid && driverAvailable 
      ? 'Assignment is valid' 
      : 'Assignment validation failed',
    details: [
      volumeValid ? null : `Volume exceeds capacity by ${(totalSelectedVolume.value - availableVolume).toFixed(2)} m³`,
      weightValid ? null : `Weight exceeds capacity by ${(totalSelectedWeight.value - availableWeight).toFixed(2)} kg`,
      driverAvailable ? null : 'Driver cannot accept new assignments'
    ].filter(Boolean),
    availableVolume,
    availableWeight
  };

  showAssignmentModal.value = true;
};

const closeAssignmentModal = () => {
  showAssignmentModal.value = false;
};

const submitAssignment = () => {
  if (!selectedSet.value || selectedDeliveries.value.length === 0) {
    toast.error('Please select both deliveries and a driver-truck set');
    return;
  }

  assignmentForm.driver_truck_assignment_id = selectedSet.value.id;
  assignmentForm.delivery_request_ids = selectedDeliveries.value.map(d => d.id);

  // Use the correct route for batch assignment
  assignmentForm.post(route('cargo-assignments.assign.batch'), {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Assignment created successfully');
      closeAssignmentModal();
      selectedDeliveries.value = [];
      selectedSet.value = null;
    },
    onError: (errors) => {
      toast.error('Failed to create assignment');
    }
  });
};

// Dispatch Modal
const showDispatchModal = ref(false);
const dispatchingSetId = ref(null);
const dispatchValidation = ref({
  can_dispatch: false,
  has_manifest: false,
  missing_waybills: [],
  message: '',
  loading: false
});

const openDispatchModal = async (set) => {
  selectedSet.value = set;
  showDispatchModal.value = true;
  dispatchValidation.value.loading = true;
  
  try {
    // Use the correct route name from your list
    const response = await fetch(route('cargo-assignments.dispatch.driver-truck-set.validate', set.id));
    const data = await response.json();
    
    dispatchValidation.value = {
      can_dispatch: data.can_dispatch,
      has_manifest: data.has_manifest, // This might need adjustment based on actual response
      missing_waybills: data.missing_waybills || [],
      message: data.message || '',
      loading: false
    };
  } catch (error) {
    toast.error('Failed to validate dispatch');
    dispatchValidation.value.loading = false;
  }
};

const closeDispatchModal = () => {
  showDispatchModal.value = false;
  dispatchValidation.value = {
    can_dispatch: false,
    has_manifest: false,
    missing_waybills: [],
    message: '',
    loading: false
  };
};

const confirmDispatch = () => {
  if (!selectedSet.value) return;
  
  dispatchingSetId.value = selectedSet.value.id;
  
  // Use the correct route name from your list
  router.post(route('cargo-assignments.dispatch.driver-truck-set', selectedSet.value.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Driver-truck set dispatched successfully');
      closeDispatchModal();
      dispatchingSetId.value = null;
      refreshData();
    },
    onError: (errors) => {
      toast.error('Failed to dispatch driver-truck set');
      dispatchingSetId.value = null;
    }
  });
};

// Validation Modal
const showValidationModal = ref(false);

const openValidationModal = () => {
  showValidationModal.value = true;
};

const closeValidationModal = () => {
  showValidationModal.value = false;
};

// ETA Calculation
const calculatedETA = computed(() => {
  if (!assignmentForm.estimated_departure) return null;
  
  const departure = new Date(assignmentForm.estimated_departure);
  // Add average transit time (e.g., 2 days)
  departure.setDate(departure.getDate() + 2);
  
  return departure;
});

const minDepartureDateTime = computed(() => {
  const now = new Date();
  // Set minimum to current time
  return now.toISOString().slice(0, 16);
});

const autoDepartureNote = computed(() => {
  const now = new Date();
  const selected = new Date(assignmentForm.estimated_departure);
  
  // If departure is set to default (tomorrow)
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  
  if (selected.toDateString() === tomorrow.toDateString()) {
    return 'Default departure time set to tomorrow';
  }
  
  return null;
});
</script>

<style scoped>
/* Add any custom styles here */
</style>