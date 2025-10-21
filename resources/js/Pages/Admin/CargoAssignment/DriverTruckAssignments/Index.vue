<template>
  <EmployeeLayout>
    <template #header>
       <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded" v-if="selectedAssignment">
    <p class="text-sm text-red-800 mb-2">Debug: Test cancellation eligibility</p>
    <SecondaryButton 
      @click="testCancellationEligibility" 
      size="xs"
    >
      Test Cancellation Eligibility
    </SecondaryButton>
  </div>
      <div class="flex flex-wrap justify-between items-center gap-4 px-4 md:px-6 max-w-screen-xl mx-auto w-full">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
          Driver-Truck Assignments & Monitoring
        </h2>
        <div class="flex space-x-2 mb-4">
          <PrimaryButton 
            @click="$inertia.visit(route('driver-truck-assignments.backhaul-eligible.show'))"
            class="flex items-center"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd" />
            </svg>
            Backhaul Eligible
          </PrimaryButton>
          
          <PrimaryButton @click="openCreateModal" class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Create New Assignment
          </PrimaryButton>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <SearchInput
            v-model="filters.search"
            placeholder="Search by Driver, Truck, or Region"
            @keyup.enter="handleFilterChange"
            @input="handleFilterChange"
            class="w-64"
          />
          <SecondaryButton @click="resetFilters">
            Reset
          </SecondaryButton>
        </div>
      </div>
    </template>

    <div class="py-6 px-2 md:px-6">
      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6 text-center mb-6">
        <svg class="mx-auto h-12 w-12 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-red-800">Failed to load assignments</h3>
        <p class="mt-1 text-sm text-red-600">{{ error }}</p>
        <PrimaryButton @click="loadData" class="mt-4">
          Try Again
        </PrimaryButton>
      </div>

      <!-- Main Content -->
      <template v-else>
        <!-- Metrics Dashboard -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 max-w-screen-xl mx-auto">
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
              <div class="rounded-full bg-blue-100 p-3 mr-4">
                <TruckIcon class="h-6 w-6 text-blue-600" />
              </div>
              <div>
                <p class="text-sm font-medium text-gray-600">Total Assignments</p>
                <p class="text-2xl font-bold text-gray-900">{{ metrics.total_assignments || 0 }}</p>
              </div>
            </div>
          </div>
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
              <div class="rounded-full bg-purple-100 p-3 mr-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-600">Backhaul Eligible</p>
                <p class="text-2xl font-bold text-purple-600">{{ metrics.backhaul_eligible || 0 }}</p>
              </div>
            </div>
          </div>
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
              <div class="rounded-full bg-yellow-100 p-3 mr-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-600">In Cooldown</p>
                <p class="text-2xl font-bold text-yellow-600">{{ metrics.in_cooldown || 0 }}</p>
              </div>
            </div>
          </div>
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
              <div class="rounded-full bg-green-100 p-3 mr-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-600">Active</p>
                <p class="text-2xl font-bold text-green-600">{{ metrics.active_assignments || 0 }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden border border-gray-200 max-w-screen-xl mx-auto">
          <div class="px-4 py-5 sm:px-6 border-b border-gray-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <h3 class="text-lg font-medium leading-6 text-gray-900">
                Driver-Truck Assignments & Monitoring
              </h3>
              <p class="mt-1 text-sm text-gray-500">
                Monitor and manage driver-truck assignments across regions.
              </p>
            </div>
            <div class="flex flex-wrap items-center gap-2 md:ml-auto">
              <SelectInput
                v-model="filters.region_id"
                :options="regionOptions"
                placeholder="Filter by Region"
                class="w-44"
                @change="handleFilterChange"
              />
              <SelectInput
                v-model="filters.status"
                :options="[
                  { value: 'active', label: 'Active' },
                  { value: 'inactive', label: 'Inactive' },
                  { value: 'all', label: 'All' }
                ]"
                class="w-32"
                placeholder="Status"
                @change="handleFilterChange"
              />
              <!-- Driver Status Filter -->
              <SelectInput
                v-model="filters.driver_status"
                :options="driverStatusOptions"
                class="w-40"
                placeholder="Driver Status"
                @change="handleFilterChange"
              />
              <!-- Cooldown Filter -->
              <SelectInput
                v-model="filters.cooldown_completed"
                :options="cooldownOptions"
                class="w-48"
                placeholder="Cooldown Status"
                @change="handleFilterChange"
              />
            </div>
          </div>

          <!-- Assignments Table -->
          <div class="overflow-x-auto px-2 md:px-4">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Driver</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Truck</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Region</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assignment Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assignment Type</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned At</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Region</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="row in assignments.data" :key="row.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    <div class="flex items-center">
                      <UserCircleIcon class="h-5 w-5 text-gray-400 mr-2" />
                      <div>
                        <span>{{ row.driver?.name || 'N/A' }}</span>
                        <div class="text-xs text-gray-500">
                          {{ row.driver?.employee_profile?.employee_id || 'No ID' }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <div class="flex items-center">
                      <TruckIcon class="h-5 w-5 text-gray-400 mr-2" />
                      <div>
                        <div>{{ row.truck?.license_plate || 'N/A' }}</div>
                        <div class="text-xs text-gray-500">
                          {{ row.truck ? `${row.truck.make} ${row.truck.model}` : '' }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <div>
                      <div>{{ row.region?.name || 'N/A' }}</div>
                      <!-- Show current region for backhaul assignments -->
                      <div v-if="row.current_status === 'backhaul_eligible' && row.current_region" 
                          class="text-xs text-purple-600">
                        Current: {{ row.current_region.name }}
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <!-- Assignment Status Badge -->
                    <span
                      :class="[
                        'px-2 py-1 rounded-full text-xs font-semibold',
                        getAssignmentStatusColor(row.current_status)
                      ]"
                    >
                      {{ getAssignmentStatusLabel(row.current_status) }}
                    </span>
                    <!-- Cooldown timer and final cooldown indicator -->
                    <div v-if="row.current_status === 'cooldown'" class="text-xs mt-1">
                      <div class="text-yellow-600">
                        Ends: {{ formatTimeRemaining(row.cooldown_ends_at) }}
                        <span v-if="isCooldownFinished(row)" class="text-green-600 font-semibold ml-1">
                          (Ready)
                        </span>
                      </div>
                      <div v-if="row.is_final_cooldown" class="text-red-600 font-semibold">
                        Final Cooldown
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <!-- Assignment Type Badge -->
                    <span
                      :class="[
                        'px-2 py-1 rounded-full text-xs font-semibold',
                        row.current_status === 'backhaul_eligible'
                          ? 'bg-purple-100 text-purple-800 border border-purple-300'
                          : 'bg-blue-100 text-blue-800 border border-blue-300'
                      ]"
                    >
                      <span v-if="row.current_status === 'backhaul_eligible'" class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd" />
                        </svg>
                        Backhaul
                      </span>
                      <span v-else class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                          <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1 1 0 11-3 0 1 1 0 013 0z" />
                          <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-1a1 1 0 011-1h2a1 1 0 011 1v1a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H19a1 1 0 001-1V5a1 1 0 00-1-1H3z" />
                        </svg>
                        Regular
                      </span>
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <span class="text-gray-400">{{ formatDate(row.assigned_at) }}</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <div>
                      <div>{{ getCurrentRegionName(row) }}</div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex justify-end space-x-2">
                      <SecondaryButton 
                        @click="viewAssignment(row)"
                        size="xs"
                        title="View Details"
                      >
                        <EyeIcon class="w-3 h-3" />
                      </SecondaryButton>
                      
                      <!-- REMOVED: Status Management Buttons -->
                      
                      <!-- View Status Timeline Button -->
                      <SecondaryButton 
                        @click="viewStatusTimeline(row)"
                        size="xs"
                        title="View Status History"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                        </svg>
                      </SecondaryButton>

                      <!-- Cancel Assignment button for staff mistakes -->
                      <DangerButton 
                        v-if="canBeCancelled(row)"
                        @click="openCancelAssignmentModal(row)" 
                        size="xs"
                        title="Cancel Assignment"
                      >
                        <TrashIcon class="w-3 h-3" />
                      </DangerButton>
                    </div>
                  </td>
                </tr>
                <tr v-if="!assignments.data || assignments.data.length === 0">
                  <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                    No assignments found
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="bg-white px-4 py-3 flex items-center justify-center border-t border-gray-200 sm:px-6">
            <div class="flex items-center space-x-2">
              <button
                @click="handlePageChange(assignments.current_page - 1)"
                :disabled="assignments.current_page <= 1"
                class="px-3 py-1 rounded border text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
              >Previous</button>
              <span>Page {{ assignments.current_page }} of {{ assignments.last_page }}</span>
              <button
                @click="handlePageChange(assignments.current_page + 1)"
                :disabled="assignments.current_page >= assignments.last_page"
                class="px-3 py-1 rounded border text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
              >Next</button>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Create Assignment Modal -->
    <Modal :show="showCreateModal" @close="closeCreateModal">
      <div class="p-6">
        <h2 class="text-lg font-medium mb-4">
          Create New Assignment
        </h2>
        
        <div class="space-y-4">
          <SelectInput
            label="Region"
            v-model="form.region_id"
            :options="regionOptions.filter(r => r.value)"
            required
            @update:modelValue="(value) => {
              console.log('🔧 SelectInput modelValue updated:', value)
              form.region_id = value
              handleRegionChange(value)
            }"
          />
          <SelectInput
            label="Driver"
            v-model="form.driver_id"
            :options="driverOptions"
            required
            :disabled="!form.region_id"
            :loading="loadingDrivers"
          />
          <SelectInput
            label="Truck"
            v-model="form.truck_id"
            :options="truckOptions"
            required
            :disabled="!form.region_id"
            :loading="loadingTrucks"
          />
        </div>
        <div class="mt-6 flex justify-end space-x-4">
          <SecondaryButton @click="closeCreateModal">
            Cancel
          </SecondaryButton>
          <PrimaryButton 
            @click="submit" 
            :disabled="form.processing || !form.region_id"
            :loading="form.processing"
          >
            Create Assignment
          </PrimaryButton>
        </div>
      </div>

      <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded">
        <p class="text-sm text-yellow-800 mb-2">Debug: Test resource loading</p>
        <SecondaryButton 
          @click="testLoadResources" 
          size="xs"
        >
          Test Load Resources
        </SecondaryButton>
      </div>
    </Modal>

    <!-- Assignment Detail Modal -->
    <Modal :show="showDetailModal" @close="showDetailModal = false" max-width="2xl">
      <div class="p-6" v-if="selectedAssignment">
        <h2 class="text-xl font-semibold mb-6 text-gray-800">Assignment Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Driver Information -->
          <div class="bg-gray-50 rounded-lg p-4 shadow-sm border">
            <h3 class="font-semibold text-gray-700 mb-3 border-b pb-2">Driver Information</h3>
            <div class="space-y-2">
              <div class="flex justify-between">
                <span class="text-gray-500">Name</span>
                <span class="font-medium text-gray-900">{{ selectedAssignment.driver?.name || 'N/A' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Employee ID</span>
                <span class="font-medium text-gray-900">{{ selectedAssignment.driver?.employee_profile?.employee_id || 'N/A' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Home Region</span>
                <span class="font-medium text-gray-900">{{ selectedAssignment.region?.name || 'N/A' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Current Region</span>
                <span class="font-medium text-gray-900">{{ getCurrentRegionName(selectedAssignment) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Driver Status</span>
                <span 
                  :class="[
                    'font-medium px-2 py-1 rounded text-xs',
                    getAssignmentStatusColor(selectedAssignment.current_status)
                  ]"
                >
                  {{ getAssignmentStatusLabel(selectedAssignment.current_status) }}
                </span>
              </div>
              <div class="flex justify-between" v-if="selectedAssignment.current_status === 'cooldown' && selectedAssignment.cooldown_ends_at">
                <span class="text-gray-500">Cooldown Ends</span>
                <span class="font-medium text-yellow-600">{{ formatDateTime(selectedAssignment.cooldown_ends_at) }}</span>
                <span v-if="isCooldownFinished(selectedAssignment)" class="text-green-600 font-semibold">
                  (Ready for action)
                </span>
              </div>
              <div class="flex justify-between" v-if="selectedAssignment.is_final_cooldown">
                <span class="text-gray-500">Cooldown Type</span>
                <span class="font-medium text-red-600">Final Cooldown</span>
              </div>
            </div>
          </div>
          <!-- Truck Information -->
          <div class="bg-gray-50 rounded-lg p-4 shadow-sm border">
            <h3 class="font-semibold text-gray-700 mb-3 border-b pb-2">Truck Information</h3>
            <div class="space-y-2">
              <div class="flex justify-between">
                <span class="text-gray-500">License Plate</span>
                <span class="font-medium text-gray-900">{{ selectedAssignment.truck?.license_plate || 'N/A' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Make/Model</span>
                <span class="font-medium text-gray-900">{{ `${selectedAssignment.truck?.make || ''} ${selectedAssignment.truck?.model || ''}`.trim() || 'N/A' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Status</span>
                <span 
                  :class="[
                    'font-medium px-2 py-1 rounded text-xs',
                    getTruckStatusColor(selectedAssignment.truck?.status)
                  ]"
                >
                  {{ getTruckStatusLabel(selectedAssignment.truck?.status) }}
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Current Load</span>
                <span class="font-medium text-gray-900">{{ `${selectedAssignment.truck?.current_volume ?? 0}m³ / ${selectedAssignment.truck?.volume_capacity ?? 0}m³` }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- REMOVED: Status Management Actions Section -->

        <!-- Workflow Information -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
          <h3 class="font-semibold text-blue-800 mb-2">Current Workflow Status</h3>
          <div class="text-sm text-blue-700 space-y-1">
            <div v-if="selectedAssignment.current_status === 'cooldown' && selectedAssignment.is_final_cooldown">
              <p><strong>Final Cooldown Period Active</strong></p>
              <p>Driver will complete cooldown to finish assignment and free up driver/truck.</p>
            </div>
            <div v-else-if="selectedAssignment.current_status === 'cooldown'">
              <p><strong>Regular Cooldown Period Active</strong></p>
              <p>Driver can choose:</p>
              <ul class="ml-4 list-disc">
                <li><strong>Option A:</strong> Skip cooldown to become backhaul eligible immediately</li>
                <li><strong>Option B:</strong> Return without backhaul to home region</li>
                <li><strong>Wait:</strong> Cooldown will automatically finish and become backhaul eligible</li>
              </ul>
            </div>
            <div v-else-if="selectedAssignment.current_status === 'backhaul_eligible'">
              <p><strong>Backhaul Eligible</strong></p>
              <p>Driver is waiting for backhaul assignments or can choose to return without backhaul.</p>
            </div>
            <div v-else-if="selectedAssignment.current_status === 'returning'">
              <p><strong>Returning to Home Region (Option B)</strong></p>
              <p>Driver is returning to home base without backhaul. Will enter final cooldown upon arrival.</p>
            </div>
            <div v-else-if="selectedAssignment.current_status === 'active'">
              <p><strong>Active Assignment</strong></p>
              <p>Driver is actively working on their primary assignment.</p>
            </div>
          </div>
        </div>

        <div class="mt-6 flex justify-end">
          <SecondaryButton @click="showDetailModal = false">
            Close
          </SecondaryButton>
        </div>
      </div>
    </Modal>

    <!-- REMOVED: Force Return Modal -->

    <!-- REMOVED: Force Complete Assignment Modal -->

    <!-- Cancel Assignment Modal -->
    <Modal :show="showCancelModal" @close="showCancelModal = false">
      <div class="p-6">
        <h2 class="text-lg font-medium mb-4 text-red-600">
          Cancel Assignment
        </h2>
        <p class="text-gray-600 mb-4">
          This will cancel the assignment and free up both driver and truck for new assignments.
          Please provide a reason for cancellation.
        </p>
        
        <SelectInput
          label="Cancellation Reason"
          v-model="cancelReason"
          :options="[
            { value: 'driver_unavailable', label: 'Driver Unavailable' },
            { value: 'truck_maintenance', label: 'Truck Maintenance' },
            { value: 'reassigned', label: 'Reassigned' },
            { value: 'other', label: 'Other Reason' }
          ]"
          required
        />
        
        <TextInput
          v-if="cancelReason === 'other'"
          label="Additional Remarks"
          v-model="cancelRemarks"
          placeholder="Please specify the reason..."
          class="mt-4"
        />
        
        <div class="mt-6 flex justify-end space-x-4">
          <SecondaryButton @click="showCancelModal = false">
            Cancel
          </SecondaryButton>
          <DangerButton 
            @click="cancelAssignment"
            :loading="cancellingAssignment === cancelAssignmentData?.id"
          >
            Cancel Assignment
          </DangerButton>
        </div>
      </div>
    </Modal>

    <!-- REMOVED: Status Update Modal -->
  </EmployeeLayout>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import Modal from '@/Components/Modal.vue'
import SelectInput from '@/Components/SelectInput.vue'
import SearchInput from '@/Components/SearchInput.vue'
import TextInput from '@/Components/TextInput.vue'
import { TruckIcon, UserCircleIcon, EyeIcon, TrashIcon } from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  assignments: Object,
  regions: Array,
  metrics: Object,
  filters: Object
})

// Reactive state
const loading = ref(false)
const error = ref(null)
const showCreateModal = ref(false)
const showDetailModal = ref(false)
const showCancelModal = ref(false)
const selectedAssignment = ref(null)
const cancelAssignmentData = ref(null)
const loadingDrivers = ref(false)
const loadingTrucks = ref(false)
const driverOptions = ref([])
const truckOptions = ref([])
const cancelReason = ref('')

// Loading states for specific actions
const cancellingAssignment = ref(null)

// Form data
const form = reactive({
  region_id: null,
  driver_id: null,
  truck_id: null,
  processing: false
})

// Filters
const filters = reactive({
  search: props.filters?.search || '',
  region_id: props.filters?.region_id || '',
  status: props.filters?.status || 'all',
  driver_status: props.filters?.driver_status || '',
  cooldown_completed: props.filters?.cooldown_completed || ''
})

// Computed
const regionOptions = computed(() => [
  { value: '', label: 'All Regions' },
  ...(props.regions?.map(region => ({
    value: region.id,
    label: region.name
  })) || [])
])

const driverStatusOptions = computed(() => [
  { value: '', label: 'All Statuses' },
  { value: 'active', label: 'Active' },
  { value: 'cooldown', label: 'Cooldown' },
  { value: 'backhaul_eligible', label: 'Backhaul Eligible' },
  { value: 'returning', label: 'Returning' },
  { value: 'completed', label: 'Completed' }
])

const cooldownOptions = computed(() => [
  { value: '', label: 'All Cooldown Status' },
  { value: 'pending', label: 'Cooldown Pending' },
  { value: 'completed', label: 'Cooldown Completed' },
  { value: 'final', label: 'Final Cooldown' }
])

// Methods
const loadData = async () => {
  loading.value = true
  error.value = null
  try {
    await router.reload({
      data: filters,
      preserveState: true,
      preserveScroll: true
    })
  } catch (err) {
    error.value = err.message || 'Failed to load assignments'
  } finally {
    loading.value = false
  }
}

const handleFilterChange = () => {
  loadData()
}

const resetFilters = () => {
  filters.search = ''
  filters.region_id = ''
  filters.status = 'all'
  filters.driver_status = ''
  filters.cooldown_completed = ''
  loadData()
}

const handlePageChange = (page) => {
  if (page >= 1 && page <= props.assignments.last_page) {
    router.get(route('driver-truck-assignments.index'), {
      ...filters,
      page
    }, {
      preserveState: true,
      preserveScroll: true
    })
  }
}

const openCreateModal = () => {
  console.log('🆕 Opening create modal')
  showCreateModal.value = true
  form.region_id = null
  form.driver_id = null
  form.truck_id = null
  
  // Test: manually trigger after a short delay to see if modal is working
  setTimeout(() => {
    console.log('⏰ Modal should be open now')
  }, 1000)
}

const closeCreateModal = () => {
  showCreateModal.value = false
}

const loadAvailableResources = async () => {
  console.log('🎯 loadAvailableResources CALLED')
  
  if (!form.region_id) {
    console.log('❌ No region_id - aborting')
    driverOptions.value = []
    truckOptions.value = []
    return
  }

  console.log('✅ Region ID found:', form.region_id)

  try {
    loadingDrivers.value = true
    loadingTrucks.value = true

    console.log('🔄 Starting fetch request...')
    
    // Use GET method and query param
    const url = route('driver-truck-assignments.available-resources') + `?region_id=${form.region_id}`
    console.log('📡 Fetch URL:', url)

    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })

    console.log('📡 Response status:', response.status)
    console.log('📡 Response OK:', response.ok)

    if (!response.ok) {
      console.error('❌ Response not OK')
      const errorText = await response.text()
      console.error('❌ Response text:', errorText)
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const data = await response.json()
    console.log('✅ Received data:', data)

    // Process drivers - safely handle both arrays and objects
    driverOptions.value = Array.isArray(data.drivers) 
      ? data.drivers.map(driver => ({
          value: driver.id,
          label: `${driver.name} (${driver.employee_profile?.employee_id || 'No ID'})`
      }))
      : Object.values(data.drivers || {}).map(driver => ({
          value: driver.id,
          label: `${driver.name} (${driver.employee_profile?.employee_id || 'No ID'})`
      }));

    console.log('👥 Final driver options:', driverOptions.value)

    // Process trucks - safely handle both arrays and objects
    const trucksArray = Array.isArray(data.trucks) 
      ? data.trucks 
      : Object.values(data.trucks || {});

    truckOptions.value = trucksArray.map(truck => ({
      value: truck.id,
      label: `${truck.license_plate} - ${truck.make} ${truck.model}`
    }));

    console.log('🚛 Final truck options:', truckOptions.value)

  } catch (err) {
    console.error('❌ Error in loadAvailableResources:', err)
    console.error('❌ Error stack:', err.stack)
    alert('Failed to load available drivers and trucks: ' + err.message)
  } finally {
    console.log('🏁 Finally block - setting loading to false')
    loadingDrivers.value = false
    loadingTrucks.value = false
  }
}

const testCancellationEligibility = async () => {
  if (!selectedAssignment.value) return;
  
  console.log('🧪 TESTING CANCELLATION ELIGIBILITY', selectedAssignment.value);
  
  try {
    const response = await fetch(`/debug-cancel-assignment/${selectedAssignment.value.id}`);
    const data = await response.json();
    console.log('🧪 ELIGIBILITY TEST RESULT:', data);
    alert(`Can be cancelled: ${data.assignment.canBeCancelled}\nHas active deliveries: ${data.assignment.hasActiveDeliveries}`);
  } catch (error) {
    console.error('🧪 ELIGIBILITY TEST FAILED:', error);
  }
}

const submit = () => {
  form.processing = true
  router.post(route('driver-truck-assignments.store'), form, {
    onSuccess: () => {
      showCreateModal.value = false
      form.region_id = null
      form.driver_id = null
      form.truck_id = null
      loadData()
    },
    onError: (errors) => {
      console.error('Failed to create assignment:', errors)
    },
    onFinish: () => {
      form.processing = false
    }
  })
}

const viewAssignment = (assignment) => {
  selectedAssignment.value = assignment
  showDetailModal.value = true
}

// REMOVED: skipCooldown, completeCooldown, disableBackhaul, forceReturn, forceCompleteAssignment, updateDriverStatus methods

const handleRegionChange = (event) => {
  console.log('🎯 Region changed:', form.region_id)
  console.log('🎯 Event:', event)
  loadAvailableResources()
}

const openCancelAssignmentModal = (assignment) => {
  cancelAssignmentData.value = assignment
  cancelReason.value = ''
  showCancelModal.value = true
}

const testLoadResources = async () => {
  console.log('🧪 Manual test triggered')
  console.log('🧪 Current region_id:', form.region_id)
  
  if (!form.region_id) {
    console.log('❌ No region selected')
    alert('Please select a region first')
    return
  }
  
  console.log('🧪 Calling loadAvailableResources with region:', form.region_id)
  await loadAvailableResources()
}

const cancelAssignment = async () => {
  if (!cancelAssignmentData.value || !cancelReason.value.trim()) {
    alert('Please provide a cancellation reason')
    return
  }

  cancellingAssignment.value = cancelAssignmentData.value.id
  
  console.log('🔄 STARTING CANCELLATION PROCESS', {
    assignmentId: cancelAssignmentData.value.id,
    assignment: cancelAssignmentData.value,
    reason: cancelReason.value
  })
  
  router.post(route('driver-truck-assignments.cancel', { 
    assignment: cancelAssignmentData.value.id 
  }), {
    reason: cancelReason.value,
    remarks: cancelReason.value
  }, {
    onSuccess: (page) => {
      console.log('✅ CANCELLATION SUCCESS - Response:', page)
      console.log('🔄 Reloading data...')
      loadData()
      showCancelModal.value = false
      if (showDetailModal.value) {
        showDetailModal.value = false
      }
      
      // Check for success message
      if (page.props.flash.success) {
        console.log('🎉 Success message:', page.props.flash.success)
      }
    },
    onError: (errors) => {
      console.error('❌ CANCELLATION FAILED - Errors:', errors)
      
      // Extract error message from various possible locations
      let errorMessage = 'Unknown error occurred'
      
      if (typeof errors === 'string') {
        errorMessage = errors
      } else if (errors.message) {
        errorMessage = errors.message
      } else if (errors.reason && Array.isArray(errors.reason)) {
        errorMessage = errors.reason[0]
      } else if (errors.remarks && Array.isArray(errors.remarks)) {
        errorMessage = errors.remarks[0]
      } else if (page?.props?.errors) {
        // Check Inertia page errors
        const pageErrors = page.props.errors
        errorMessage = pageErrors.message || Object.values(pageErrors)[0]?.[0] || 'Validation error'
      }
      
      console.error('❌ Extracted error message:', errorMessage)
      alert('Failed to cancel assignment: ' + errorMessage)
    },
    onFinish: () => {
      console.log('🏁 CANCELLATION PROCESS FINISHED')
      cancellingAssignment.value = null
      cancelAssignmentData.value = null
    }
  })
}

const viewStatusTimeline = (assignment) => {
  router.visit(route('driver-truck-assignments.status-timeline.show', { assignment: assignment.id }))
}

// Helper methods
const getAssignmentStatusColor = (status) => {
  const colors = {
    active: 'bg-green-100 text-green-800 border border-green-300',
    cooldown: 'bg-yellow-100 text-yellow-800 border border-yellow-300',
    backhaul_eligible: 'bg-purple-100 text-purple-800 border border-purple-300',
    returning: 'bg-blue-100 text-blue-800 border border-blue-300',
    completed: 'bg-gray-100 text-gray-800 border border-gray-300',
    cancelled: 'bg-red-100 text-red-800 border border-red-300'
  }
  return colors[status] || 'bg-gray-100 text-gray-800 border border-gray-300'
}

const getAssignmentStatusLabel = (status) => {
  const labels = {
    active: 'Active',
    cooldown: 'Cooldown',
    backhaul_eligible: 'Backhaul Eligible',
    returning: 'Returning',
    completed: 'Completed',
    cancelled: 'Cancelled'
  }
  return labels[status] || status
}

const getTruckStatusColor = (status) => {
  const colors = {
    available: 'bg-green-100 text-green-800',
    assigned: 'bg-blue-100 text-blue-800',
    maintenance: 'bg-red-100 text-red-800',
    unavailable: 'bg-gray-100 text-gray-800'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const getTruckStatusLabel = (status) => {
  const labels = {
    available: 'Available',
    assigned: 'Assigned',
    maintenance: 'Maintenance',
    unavailable: 'Unavailable'
  }
  return labels[status] || status
}

const getCurrentRegionName = (assignment) => {
  if (assignment.current_region) {
    return assignment.current_region.name
  }
  return assignment.region?.name || 'N/A'
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString()
}

const formatDateTime = (dateString) => {
  return new Date(dateString).toLocaleString()
}

const formatTimeRemaining = (dateString) => {
  const now = new Date()
  const end = new Date(dateString)
  const diffMs = end - now
  
  if (diffMs <= 0) return 'Finished'
  
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMins / 60)
  const remainingMins = diffMins % 60
  
  if (diffHours > 0) {
    return `${diffHours}h ${remainingMins}m`
  }
  return `${remainingMins}m`
}

const isCooldownFinished = (assignment) => {
  if (assignment.current_status !== 'cooldown' || !assignment.cooldown_ends_at) {
    return false
  }
  return new Date(assignment.cooldown_ends_at) <= new Date()
}

const canBeCancelled = (assignment) => {
  // Only allow cancellation for active assignments or if there was a staff mistake
  return assignment.is_active && 
         (assignment.current_status === 'active' || 
          assignment.current_status === 'cooldown' || 
          assignment.current_status === 'backhaul_eligible')
}

// Lifecycle
onMounted(() => {
  // Set up periodic refresh for cooldown timers
  setInterval(() => {
    const hasCooldownAssignments = props.assignments?.data?.some(
      assignment => assignment.current_status === 'cooldown' && !isCooldownFinished(assignment)
    )
    if (hasCooldownAssignments) {
      loadData()
    }
  }, 30000) // Refresh every 30 seconds
})
</script>