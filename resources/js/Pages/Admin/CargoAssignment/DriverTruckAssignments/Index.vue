<template>
  <EmployeeLayout>
    <template #header>
<div class="flex flex-wrap justify-between items-center gap-4 px-4 md:px-6 w-full">        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Driver-Truck Assignments
          </h2>
          <p class="text-sm text-gray-600 mt-1">
            Manage active assignments and view assignment history
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <PrimaryButton 
            @click="openCreateModal" 
            class="flex items-center"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            New Assignment
          </PrimaryButton>
        </div>
      </div>
    </template>

    <div class="py-6 px-2 md:px-6">
      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6 text-center mb-6 max-w-screen-xl mx-auto">
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
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 max-w-screen-xl mx-auto">
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
              <div class="rounded-full bg-blue-100 p-3 mr-4">
                <TruckIcon class="h-6 w-6 text-blue-600" />
              </div>
              <div>
                <p class="text-sm font-medium text-gray-600">Active Assignments</p>
                <p class="text-2xl font-bold text-gray-900">{{ metrics.active_assignments || 0 }}</p>
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
          <!-- REMOVED: Total History Metric -->
        </div>

        <!-- Tabs Navigation -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 mb-6 max-w-screen-xl mx-auto">
          <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6">
              <button
                @click="switchTab('active')"
                :class="[
                  'py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
                  activeTab === 'active'
                    ? 'border-blue-500 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                ]"
                :disabled="tabLoading"
              >
                Active Assignments
                <!-- REMOVED: Count badge -->
              </button>
              <button
                @click="switchTab('history')"
                :class="[
                  'py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
                  activeTab === 'history'
                    ? 'border-green-500 text-green-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                ]"
                :disabled="tabLoading"
              >
                Assignment History
                <!-- REMOVED: Count badge -->
              </button>
            </nav>
          </div>

          <!-- Filters Section -->
          <div class="p-4 border-b border-gray-200">
            <div class="flex flex-col lg:flex-row gap-4">
              <!-- Search -->
              <div class="flex-1">
                <SearchInput
                  v-model="filters.search"
                  :placeholder="activeTab === 'active' ? 'Search active assignments...' : 'Search assignment history...'"
                  @keyup.enter="handleFilterChange"
                  @input="handleDebouncedFilter"
                  class="w-full"
                  :disabled="tabLoading"
                />
              </div>
              
              <!-- Filter Actions -->
              <div class="flex items-center gap-2">
                <SecondaryButton @click="refreshData" :loading="refreshing" :disabled="tabLoading">
                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                  </svg>
                  Refresh
                </SecondaryButton>
                <SecondaryButton @click="resetFilters" :disabled="tabLoading">
                  Reset
                </SecondaryButton>
              </div>
              
            </div>

            <!-- Advanced Filters -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
              <SelectInput
                v-model="filters.region_id"
                :options="regionOptions"
                placeholder="Filter by Region"
                @change="handleFilterChange"
                :disabled="tabLoading"
              />
              
              <!-- Active Tab Filters -->
              <template v-if="activeTab === 'active'">
                <SelectInput
                  v-model="filters.driver_status"
                  :options="driverStatusOptions"
                  placeholder="Driver Status"
                  @change="handleFilterChange"
                  :disabled="tabLoading"
                />
                <SelectInput
                  v-model="filters.cooldown_completed"
                  :options="cooldownOptions"
                  placeholder="Cooldown Status"
                  @change="handleFilterChange"
                  :disabled="tabLoading"
                />
              </template>
              
              <!-- History Tab Filters -->
              <template v-else-if="activeTab === 'history'">
                <SelectInput
                  v-model="filters.history_status"
                  :options="historyStatusOptions"
                  placeholder="History Status"
                  @change="handleFilterChange"
                  :disabled="tabLoading"
                />
              </template>
            </div>

            <!-- Filter Info -->
            <div class="flex justify-between items-center mt-4">
              <div class="text-sm text-gray-500">
                Showing {{ props.assignments?.data?.length || 0 }} assignments
                <span v-if="filters.region_id" class="ml-2">
                  in {{ getSelectedRegionName(filters.region_id) }}
                </span>
                <span v-if="filters.driver_status && activeTab === 'active'" class="ml-2">
                  • {{ getDriverStatusLabel(filters.driver_status) }}
                </span>
                <span v-if="filters.history_status && activeTab === 'history'" class="ml-2">
                  • {{ getHistoryStatusLabel(filters.history_status) }}
                </span>
              </div>
              <div v-if="activeTab === 'active'" class="flex items-center space-x-4 text-xs text-gray-600">
                <span class="flex items-center">
                  <div class="w-3 h-3 bg-green-500 rounded-full mr-1"></div>
                  Available (&lt;60%)
                </span>
                <span class="flex items-center">
                  <div class="w-3 h-3 bg-yellow-500 rounded-full mr-1"></div>
                  Moderate (60-85%)
                </span>
                <span class="flex items-center">
                  <div class="w-3 h-3 bg-red-500 rounded-full mr-1"></div>
                  Limited (&gt;85%)
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab Loading State -->
        <div v-if="tabLoading" class="flex justify-center items-center py-12 max-w-screen-xl mx-auto">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <span class="ml-3 text-gray-600">Loading {{ activeTab === 'active' ? 'active' : 'history' }} assignments...</span>
        </div>

        <!-- Active Assignments - Cards View -->
        <div v-else-if="activeTab === 'active' && !tabLoading">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-w-screen-xl mx-auto">
            <div 
              v-for="assignment in props.assignments.data" 
              :key="assignment.id"
              :class="[
                'bg-white rounded-lg shadow-sm border hover:shadow-md transition-shadow p-4',
                assignment.current_status === 'backhaul_eligible' 
                  ? 'border-purple-300 bg-purple-50' 
                  : 'border-gray-200'
              ]"
            >
              <!-- Header with Driver & Status -->
            <div class="flex justify-between items-start mb-3">
  <div class="flex items-center">
    <div class="flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center"
         :class="assignment.current_status === 'backhaul_eligible' ? 'bg-purple-100' : 'bg-gray-100'">
      <span class="text-sm font-medium" 
            :class="assignment.current_status === 'backhaul_eligible' ? 'text-purple-800' : 'text-gray-600'">
        {{ getInitials(assignment.driver?.name) }}
      </span>
    </div>
    <div class="ml-3">
      <div class="text-sm font-medium text-gray-900">{{ assignment.driver?.name }}</div>
      <div class="text-xs text-gray-500">{{ assignment.driver?.employee_profile?.employee_id }}</div>
    </div>
  </div>
  <span
    :class="[
      'px-2 py-1 rounded-full text-xs font-semibold',
      getAssignmentStatusColor(assignment.current_status)
    ]"
  >
    {{ getAssignmentStatusLabel(assignment.current_status) }}
  </span>
</div>

              <!-- Truck Info -->
              <div class="mb-3">
                <div class="text-sm font-medium text-gray-900">{{ assignment.truck?.license_plate }}</div>
                <div class="text-xs text-gray-500">{{ assignment.truck?.make }} {{ assignment.truck?.model }}</div>
              </div>

              <!-- Region Info -->
              <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                <div>
                  <div class="text-gray-500 text-xs">Home Region</div>
                  <div class="font-medium">{{ assignment.region?.name }}</div>
                </div>
                <div>
                  <div class="text-gray-500 text-xs">Current Region</div>
                  <div :class="assignment.current_status === 'backhaul_eligible' ? 'text-purple-600 font-medium' : 'font-medium'">
                    {{ getCurrentRegionName(assignment) }}
                  </div>
                </div>
              </div>

              <!-- Capacity - Volume & Weight -->
              <div class="mb-4 space-y-3">
                <!-- Volume Capacity -->
                <div>
                  <div class="flex justify-between text-xs text-gray-600 mb-1">
                    <span>Volume Capacity</span>
                    <span>{{ getVolumePercentage(assignment.truck) }}%</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div 
                      class="h-2 rounded-full" 
                      :style="{ width: `${getVolumePercentage(assignment.truck)}%` }"
                      :class="{
                        'bg-green-500': getVolumePercentage(assignment.truck) <= 60,
                        'bg-yellow-500': getVolumePercentage(assignment.truck) > 60 && getVolumePercentage(assignment.truck) <= 85,
                        'bg-red-500': getVolumePercentage(assignment.truck) > 85
                      }"
                    ></div>
                  </div>
                  <div class="text-xs text-gray-500 mt-1 text-right">
                    {{ assignment.truck?.current_volume?.toFixed(1) }}m³ / {{ assignment.truck?.volume_capacity }}m³
                  </div>
                </div>

                <!-- Weight Capacity -->
                <div>
                  <div class="flex justify-between text-xs text-gray-600 mb-1">
                    <span>Weight Capacity</span>
                    <span>{{ getWeightPercentage(assignment.truck) }}%</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div 
                      class="h-2 rounded-full" 
                      :style="{ width: `${getWeightPercentage(assignment.truck)}%` }"
                      :class="{
                        'bg-green-500': getWeightPercentage(assignment.truck) <= 60,
                        'bg-yellow-500': getWeightPercentage(assignment.truck) > 60 && getWeightPercentage(assignment.truck) <= 85,
                        'bg-red-500': getWeightPercentage(assignment.truck) > 85
                      }"
                    ></div>
                  </div>
                  <div class="text-xs text-gray-500 mt-1 text-right">
                    {{ assignment.truck?.current_weight?.toFixed(1) }}kg / {{ assignment.truck?.weight_capacity }}kg
                  </div>
                </div>
              </div>

              <!-- Status Details -->
              <div class="text-xs text-gray-600 mb-3 space-y-1">
                <div>Assigned: {{ formatDate(assignment.assigned_at) }}</div>
                <div v-if="assignment.current_status === 'cooldown' && assignment.cooldown_ends_at">
                  Cooldown: {{ formatTimeRemaining(assignment.cooldown_ends_at) }}
                  <span v-if="isCooldownFinished(assignment)" class="text-green-600 font-semibold ml-1">
                    (Ready)
                  </span>
                </div>
                <div v-if="assignment.is_final_cooldown" class="text-red-600 font-semibold">
                  Final Cooldown
                </div>
                <div v-if="assignment.current_status === 'backhaul_eligible' && assignment.backhaul_eligible_at" class="text-purple-600 font-semibold">
                  Eligible since: {{ formatDateTime(assignment.backhaul_eligible_at) }}
                </div>
              </div>

            <div class="flex space-x-2">
  <SecondaryButton 
    @click="viewStatusTimeline(assignment)"
    size="xs"
    class="flex-1"
  >
    View Timeline
  </SecondaryButton>
  <DangerButton 
    v-if="canBeCancelled(assignment)"
    @click="openCancelAssignmentModal(assignment)" 
    size="xs"
    title="Cancel Assignment"
    :disabled="assignment.has_finalized_manifest"
  >
    <TrashIcon class="w-3 h-3" />
  </DangerButton>
</div>

<!-- Add manifest status indicator -->
<div v-if="assignment.has_finalized_manifest" class="text-xs text-blue-600 mt-1 text-center">
  <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
  </svg>
  Manifest Finalized
</div>
            </div>
          </div>

          <!-- Empty State for Active -->
          <div v-if="props.assignments.data.length === 0" class="text-center py-12 max-w-screen-xl mx-auto">
            <div class="text-gray-400 mb-4">
              <TruckIcon class="h-12 w-12 mx-auto" />
            </div>
            <p class="text-gray-500">No active assignments found</p>
            <PrimaryButton @click="openCreateModal" class="mt-4">
              Create New Assignment
            </PrimaryButton>
          </div>
        </div>

        <!-- History Assignments - Table View -->
        <div v-else-if="activeTab === 'history' && !tabLoading" class="max-w-screen-xl mx-auto">
          <div class="bg-white shadow-sm rounded-lg overflow-hidden border border-gray-200">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Driver</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Truck</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Region</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed/Cancelled</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="assignment in props.assignments.data" :key="assignment.id" class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10 bg-gray-100 rounded-full flex items-center justify-center">
                          <span class="text-gray-600 font-medium text-sm">
                            {{ getInitials(assignment.driver?.name) }}
                          </span>
                        </div>
                        <div class="ml-4">
                          <div class="text-sm font-medium text-gray-900">{{ assignment.driver?.name }}</div>
                          <div class="text-sm text-gray-500">{{ assignment.driver?.employee_profile?.employee_id }}</div>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      <div>{{ assignment.truck?.license_plate }}</div>
                      <div class="text-xs text-gray-500">
                        {{ assignment.truck ? `${assignment.truck.make} ${assignment.truck.model}` : '' }}
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ assignment.region?.name }}
                    </td>
                   <td class="px-6 py-4 whitespace-nowrap text-sm">
  <span
    :class="[
      'px-2 py-1 rounded-full text-xs font-semibold',
      getAssignmentStatusColor(assignment.current_status)
    ]"
  >
    {{ getAssignmentStatusLabel(assignment.current_status) }}
  </span>
  <div v-if="assignment.deleted_reason" class="text-xs text-gray-500 mt-1">
    Reason: {{ assignment.deleted_reason }}
  </div>
</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ formatDateTime(assignment.assigned_at) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      <div v-if="assignment.completed_at">{{ formatDateTime(assignment.completed_at) }}</div>
                      <div v-if="assignment.deleted_at">{{ formatDateTime(assignment.deleted_at) }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ calculateAssignmentDuration(assignment) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div class="flex justify-end space-x-2">
                        <SecondaryButton 
                          @click="viewStatusTimeline(assignment)"
                          size="xs"
                          title="View Status History"
                        >
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                          </svg>
                        </SecondaryButton>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Empty State for History -->
            <div v-if="props.assignments.data.length === 0" class="text-center py-12">
              <div class="text-gray-400 mb-4">
                <TruckIcon class="h-12 w-12 mx-auto" />
              </div>
              <p class="text-gray-500">No assignment history found</p>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="props.assignments.data.length > 0 && !tabLoading" class="bg-white px-4 py-3 flex items-center justify-center border-t border-gray-200 sm:px-6 mt-6 max-w-screen-xl mx-auto rounded-lg">
          <div class="flex items-center space-x-2">
            <button
              @click="handlePageChange(props.assignments.current_page - 1)"
              :disabled="props.assignments.current_page <= 1 || tabLoading"
              class="px-3 py-1 rounded border text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
            >Previous</button>
            <span>Page {{ props.assignments.current_page }} of {{ props.assignments.last_page }}</span>
            <button
              @click="handlePageChange(props.assignments.current_page + 1)"
              :disabled="props.assignments.current_page >= props.assignments.last_page || tabLoading"
              class="px-3 py-1 rounded border text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
            >Next</button>
          </div>
        </div>
      </template>
    </div>

    <!-- Create Assignment Modal -->
    <Modal :show="showCreateModal" @close="closeCreateModal" max-width="5xl">
      <div class="p-6">
        <h2 class="text-xl font-semibold mb-6 text-gray-800">Create New Assignment</h2>
        
        <div class="grid grid-cols-3 gap-6">
          <!-- Region Selection -->
          <div>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Select Region</h3>
            <div class="space-y-3 max-h-80 overflow-y-auto">
              <div
                v-for="region in regions"
                :key="region.id"
                @click="selectRegion(region.id)"
                :class="[
                  'border-2 rounded-lg p-4 cursor-pointer transition-all hover:shadow-md',
                  form.region_id === region.id
                    ? 'border-blue-500 bg-blue-50 shadow-md'
                    : 'border-gray-200 bg-white hover:border-gray-300'
                ]"
              >
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                    <span class="text-blue-600 font-medium text-sm">{{ getInitials(region.name) }}</span>
                  </div>
                  <div class="ml-3">
                    <div class="text-sm font-medium text-gray-900">{{ region.name }}</div>
                    <div class="text-xs text-gray-500">{{ region.warehouse_address }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Driver Selection -->
          <div v-if="form.region_id">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-medium text-gray-900">Select Driver</h3>
              <div class="text-sm text-gray-500">
                {{ driverOptions.length }} available
              </div>
            </div>
            
            <div v-if="loadingDrivers" class="text-center py-8">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
              <p class="text-sm text-gray-500 mt-2">Loading drivers...</p>
            </div>
            
            <div v-else-if="driverOptions.length === 0" class="text-center py-8 bg-gray-50 rounded-lg">
              <UserCircleIcon class="h-12 w-12 text-gray-400 mx-auto" />
              <p class="text-gray-500 mt-2">No available drivers</p>
            </div>
            
            <div v-else class="space-y-3 max-h-80 overflow-y-auto">
              <div
                v-for="driver in driverOptions"
                :key="driver.value"
                @click="selectDriver(driver.value)"
                :class="[
                  'border-2 rounded-lg p-4 cursor-pointer transition-all hover:shadow-md',
                  form.driver_id === driver.value
                    ? 'border-green-500 bg-green-50 shadow-md'
                    : 'border-gray-200 bg-white hover:border-gray-300'
                ]"
              >
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                    <span class="text-green-600 font-medium text-sm">{{ getInitials(driver.label.split(' (')[0]) }}</span>
                  </div>
                  <div class="ml-3 flex-1">
                    <div class="text-sm font-medium text-gray-900">{{ driver.label.split(' (')[0] }}</div>
                    <div class="text-xs text-gray-500">{{ driver.label.split(' (')[1]?.replace(')', '') }}</div>
                  </div>
                  <div v-if="form.driver_id === driver.value" class="text-green-500">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Truck Selection -->
          <div v-if="form.region_id && form.driver_id">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-medium text-gray-900">Select Truck</h3>
              <div class="text-sm text-gray-500">
                {{ truckOptions.length }} available
              </div>
            </div>
            
            <div v-if="loadingTrucks" class="text-center py-8">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
              <p class="text-sm text-gray-500 mt-2">Loading trucks...</p>
            </div>
            
            <div v-else-if="truckOptions.length === 0" class="text-center py-8 bg-gray-50 rounded-lg">
              <TruckIcon class="h-12 w-12 text-gray-400 mx-auto" />
              <p class="text-gray-500 mt-2">No available trucks</p>
            </div>
            
            <div v-else class="space-y-3 max-h-80 overflow-y-auto">
              <div
                v-for="truck in truckOptions"
                :key="truck.value"
                @click="selectTruck(truck.value)"
                :class="[
                  'border-2 rounded-lg p-4 cursor-pointer transition-all hover:shadow-md',
                  form.truck_id === truck.value
                    ? 'border-orange-500 bg-orange-50 shadow-md'
                    : 'border-gray-200 bg-white hover:border-gray-300'
                ]"
              >
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10 bg-orange-100 rounded-full flex items-center justify-center">
                    <TruckIcon class="h-6 w-6 text-orange-600" />
                  </div>
                  <div class="ml-3 flex-1">
                    <div class="text-sm font-medium text-gray-900">{{ truck.label.split(' - ')[0] }}</div>
                    <div class="text-xs text-gray-500">{{ truck.label.split(' - ')[1] }}</div>
                  </div>
                  <div v-if="form.truck_id === truck.value" class="text-orange-500">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Selected Assignment Summary -->
        <div v-if="form.region_id && form.driver_id && form.truck_id" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6">
          <h3 class="text-lg font-medium text-blue-800 mb-3">Assignment Summary</h3>
          <div class="grid grid-cols-3 gap-4">
            <div class="bg-white rounded-lg p-3 border border-blue-200">
              <div class="text-xs text-blue-600 font-medium">Region</div>
              <div class="text-sm font-medium text-gray-900 truncate">{{ getSelectedRegionName() }}</div>
            </div>
            <div class="bg-white rounded-lg p-3 border border-green-200">
              <div class="text-xs text-green-600 font-medium">Driver</div>
              <div class="text-sm font-medium text-gray-900 truncate">{{ getSelectedDriverName() }}</div>
            </div>
            <div class="bg-white rounded-lg p-3 border border-orange-200">
              <div class="text-xs text-orange-600 font-medium">Truck</div>
              <div class="text-sm font-medium text-gray-900 truncate">{{ getSelectedTruckName() }}</div>
            </div>
          </div>
        </div>

        <div class="mt-6 flex justify-end space-x-4">
          <SecondaryButton @click="closeCreateModal">
            Cancel
          </SecondaryButton>
          <PrimaryButton 
            @click="submit" 
            :disabled="form.processing || !form.region_id || !form.driver_id || !form.truck_id"
            :loading="form.processing"
          >
            Create Assignment
          </PrimaryButton>
        </div>
      </div>
    </Modal>

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
  </EmployeeLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import Modal from '@/Components/Modal.vue'
import SelectInput from '@/Components/SelectInput.vue'
import SearchInput from '@/Components/SearchInput.vue'
import TextInput from '@/Components/TextInput.vue'
import { TruckIcon, UserCircleIcon, TrashIcon } from '@heroicons/vue/24/outline'

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
const refreshing = ref(false)
const showCreateModal = ref(false)
const showCancelModal = ref(false)
const selectedAssignment = ref(null)
const cancelAssignmentData = ref(null)
const tabLoading = ref(false) // NEW: Separate loading for tab switching

// Use the activeTab from props or default to 'active'
const activeTab = ref(props.filters?.activeTab || 'active')

// Create Assignment Modal State
const loadingDrivers = ref(false)
const loadingTrucks = ref(false)
const driverOptions = ref([])
const truckOptions = ref([])
const cancelReason = ref('')
const cancelRemarks = ref('')
const cancellingAssignment = ref(null)

// Form data
const form = reactive({
  region_id: null,
  driver_id: null,
  truck_id: null,
  processing: false
})

// Initialize filters from props - FIXED: Use empty string instead of null
const filters = reactive({
  search: props.filters?.search || '',
  region_id: props.filters?.region_id || '',
  driver_status: props.filters?.driver_status || '',
  cooldown_completed: props.filters?.cooldown_completed || '',
  history_status: props.filters?.history_status || ''
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
  { value: 'returning', label: 'Returning' }
])

const cooldownOptions = computed(() => [
  { value: '', label: 'All Cooldown Status' },
  { value: 'active', label: 'Cooldown Active' },
  { value: 'completed', label: 'Cooldown Completed' }
])

const historyStatusOptions = computed(() => [
  { value: '', label: 'All History' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' }
])

// Watch for tab changes and trigger filter update
watch(activeTab, (newTab, oldTab) => {
  if (newTab !== oldTab) {
    switchTab(newTab)
  }
})

// Methods
async function loadData() {
  loading.value = true
  error.value = null
  try {
    const payload = {
      ...filters,
      activeTab: activeTab.value
    }
    
    await router.reload({
      data: payload,
      preserveState: true,
      preserveScroll: true
    })
  } catch (err) {
    error.value = err.message || 'Failed to load assignments'
  } finally {
    loading.value = false
  }
}

async function checkManifestStatus(assignmentId) {
  try {
    const response = await axios.get(`/driver-truck-assignments/${assignmentId}/check-manifest-status`);
    return response.data.has_finalized_manifest;
  } catch (error) {
    console.error('Failed to check manifest status:', error);
    return false;
  }
}

// NEW: Optimized tab switching
async function switchTab(tab) {
  if (tab === activeTab.value || tabLoading.value) return
  
  tabLoading.value = true
  activeTab.value = tab
  
  try {
    const payload = {
      ...filters,
      activeTab: tab
    }
    
    await router.visit(route('driver-truck-assignments.index'), {
      data: payload,
      preserveState: true,
      preserveScroll: true,
      replace: true,
      only: ['assignments', 'filters'],
      onFinish: () => {
        tabLoading.value = false
      }
    })
  } catch (err) {
    error.value = 'Failed to switch tabs'
    tabLoading.value = false
  }
}

async function refreshData() {
  refreshing.value = true
  try {
    await router.reload({
      only: ['assignments'],
      onSuccess: () => {
        error.value = null
      }
    })
  } catch (err) {
    error.value = 'Failed to refresh data'
  } finally {
    refreshing.value = false
  }
}

function handleFilterChange() {
  const payload = {
    ...filters,
    activeTab: activeTab.value
  }
  
  // Use Inertia's visit method for reliable parameter passing
  router.visit(route('driver-truck-assignments.index'), {
    data: payload,
    preserveState: true,
    preserveScroll: true,
    replace: true,
    only: ['assignments', 'filters']
  })
}

// Debounced search
let searchTimeout = null
function handleDebouncedFilter() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    handleFilterChange()
  }, 500)
}

function resetFilters() {
  filters.search = ''
  filters.region_id = ''
  filters.driver_status = ''
  filters.cooldown_completed = ''
  filters.history_status = ''
  handleFilterChange()
}

function handlePageChange(page) {
  if (page >= 1 && page <= props.assignments.last_page) {
    const payload = {
      ...filters,
      activeTab: activeTab.value,
      page
    }
    
    router.visit(route('driver-truck-assignments.index'), {
      data: payload,
      preserveState: true,
      preserveScroll: true
    })
  }
}

// Create Assignment Functions
function openCreateModal() {
  showCreateModal.value = true
  form.region_id = null
  form.driver_id = null
  form.truck_id = null
  driverOptions.value = []
  truckOptions.value = []
}

function closeCreateModal() {
  showCreateModal.value = false
}

function selectRegion(regionId) {
  form.region_id = regionId
  form.driver_id = null
  form.truck_id = null
  driverOptions.value = []
  truckOptions.value = []
  if (regionId) {
    loadAvailableResources()
  }
}

function selectDriver(driverId) {
  form.driver_id = driverId
}

function selectTruck(truckId) {
  form.truck_id = truckId
}



function getSelectedDriverName() {
  const driver = driverOptions.value.find(d => d.value === form.driver_id)
  return driver ? driver.label.split(' (')[0] : 'Not selected'
}

function getSelectedTruckName() {
  const truck = truckOptions.value.find(t => t.value === form.truck_id)
  return truck ? truck.label.split(' - ')[0] : 'Not selected'
}

const loadAvailableResources = async () => {
  if (!form.region_id) {
    driverOptions.value = []
    truckOptions.value = []
    return
  }

  try {
    loadingDrivers.value = true
    loadingTrucks.value = true

    const url = route('driver-truck-assignments.available-resources') + `?region_id=${form.region_id}`
    
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const data = await response.json()

    // Process drivers
    driverOptions.value = Array.isArray(data.drivers) 
      ? data.drivers.map(driver => ({
          value: driver.id,
          label: `${driver.name} (${driver.employee_profile?.employee_id || 'No ID'})`
      }))
      : Object.values(data.drivers || {}).map(driver => ({
          value: driver.id,
          label: `${driver.name} (${driver.employee_profile?.employee_id || 'No ID'})`
      }));

    // Process trucks
    const trucksArray = Array.isArray(data.trucks) 
      ? data.trucks 
      : Object.values(data.trucks || {});

    truckOptions.value = trucksArray.map(truck => ({
      value: truck.id,
      label: `${truck.license_plate} - ${truck.make} ${truck.model}`
    }));

  } catch (err) {
    console.error('Error loading resources:', err)
    alert('Failed to load available drivers and trucks: ' + err.message)
  } finally {
    loadingDrivers.value = false
    loadingTrucks.value = false
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
      form.processing = false
      loadData()
    },
    onError: (errors) => {
      console.error('Failed to create assignment:', errors)
      form.processing = false
    }
  })
}

function openCancelAssignmentModal(assignment) {
  // Additional safety check
  if (assignment.has_finalized_manifest) {
    alert('Cannot cancel assignment. Manifest has been finalized and this assignment is ready for dispatch.');
    return;
  }
  
  cancelAssignmentData.value = assignment
  cancelReason.value = ''
  cancelRemarks.value = ''
  showCancelModal.value = true
}

const cancelAssignment = async () => {
  if (!cancelAssignmentData.value || !cancelReason.value.trim()) {
    alert('Please provide a cancellation reason')
    return
  }

  cancellingAssignment.value = cancelAssignmentData.value.id
  
  router.post(route('driver-truck-assignments.cancel', { 
    assignment: cancelAssignmentData.value.id 
  }), {
    reason: cancelReason.value,
    remarks: cancelRemarks.value || cancelReason.value
  }, {
    onSuccess: () => {
      loadData()
      showCancelModal.value = false
    },
    onError: (errors) => {
      console.error('Cancellation failed:', errors)
      let errorMessage = 'Unknown error occurred'
      
      if (typeof errors === 'string') {
        errorMessage = errors
      } else if (errors.message) {
        errorMessage = errors.message
      } else if (errors.reason && Array.isArray(errors.reason)) {
        errorMessage = errors.reason[0]
      }
      
      alert('Failed to cancel assignment: ' + errorMessage)
    },
    onFinish: () => {
      cancellingAssignment.value = null
    }
  })
}

function viewStatusTimeline(assignment) {
  router.visit(route('driver-truck-assignments.status-timeline.show', { assignment: assignment.id }))
}

function calculateAssignmentDuration(assignment) {
  const start = new Date(assignment.assigned_at)
  const end = assignment.completed_at ? new Date(assignment.completed_at) : 
              assignment.deleted_at ? new Date(assignment.deleted_at) : new Date()
  
  const diffMs = end - start
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))
  const diffHours = Math.floor((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
  
  if (diffDays > 0) {
    return `${diffDays}d ${diffHours}h`
  }
  return `${diffHours}h`
}

// Helper Methods
function getInitials(name) {
  if (!name) return '??'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2)
}

function getVolumePercentage(truck) {
  if (!truck || !truck.volume_capacity) return 0
  const currentVolume = truck.current_volume || 0
  return Math.round((currentVolume / truck.volume_capacity) * 100)
}

function getWeightPercentage(truck) {
  if (!truck || !truck.weight_capacity) return 0
  const currentWeight = truck.current_weight || 0
  return Math.round((currentWeight / truck.weight_capacity) * 100)
}

function getAssignmentStatusColor(status) {
  const colors = {
    active: 'bg-green-100 text-green-800 border border-green-300',
    cooldown: 'bg-yellow-100 text-yellow-800 border border-yellow-300',
    backhaul_eligible: 'bg-purple-100 text-purple-800 border border-purple-300',
    returning: 'bg-blue-100 text-blue-800 border border-blue-300',
    in_transit: 'bg-indigo-100 text-indigo-800 border border-indigo-300', // Distinct color for in_transit
    completed: 'bg-gray-100 text-gray-800 border border-gray-300',
    cancelled: 'bg-red-100 text-red-800 border border-red-300'
  }
  return colors[status] || 'bg-gray-100 text-gray-800 border border-gray-300'
}

function getAssignmentStatusLabel(status) {
  const labels = {
    active: 'Active',
    cooldown: 'Cooldown',
    backhaul_eligible: 'Backhaul Eligible',
    returning: 'Returning',
    completed: 'Completed',
    cancelled: 'Cancelled',
    in_transit: 'In Transit' // Add this line - properly capitalized
  }
  return labels[status] || status
}

function getCurrentRegionName(assignment) {
  if (assignment.current_region) {
    return assignment.current_region.name
  }
  return assignment.region?.name || 'N/A'
}

function formatDate(dateString) {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString()
}

function formatDateTime(dateString) {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleString()
}

function formatTimeRemaining(dateString) {
  if (!dateString) return 'N/A'
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

function isCooldownFinished(assignment) {
  if (assignment.current_status !== 'cooldown' || !assignment.cooldown_ends_at) {
    return false
  }
  return new Date(assignment.cooldown_ends_at) <= new Date()
}

// Update the canBeCancelled method
// Update the canBeCancelled method
function canBeCancelled(assignment) {
  // Do not allow cancellation for these statuses
  const excludedStatuses = ['in_transit', 'backhaul_eligible'];
  
  return assignment.is_active && 
         !excludedStatuses.includes(assignment.current_status) &&
         (assignment.current_status === 'active' || 
          assignment.current_status === 'cooldown') &&
         !assignment.has_finalized_manifest; // Add this check
}

// Helper methods for filter labels
function getSelectedRegionName(regionId) {
  const region = props.regions.find(r => r.id == regionId)
  return region?.name || 'Selected Region'
}

function getDriverStatusLabel(status) {
  const labels = {
    'active': 'Active',
    'cooldown': 'Cooldown', 
    'backhaul_eligible': 'Backhaul Eligible',
    'returning': 'Returning'
  }
  return labels[status] || status
}

function getHistoryStatusLabel(status) {
  const labels = {
    'completed': 'Completed',
    'cancelled': 'Cancelled'
  }
  return labels[status] || status
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