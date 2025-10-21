<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Status Timeline - {{ assignment.driver?.name }} & {{ assignment.truck?.license_plate }}
          </h2>
          <p class="text-sm text-gray-600 mt-1">
            Complete history of status changes for this driver-truck assignment
          </p>
        </div>
        <SecondaryButton @click="$inertia.visit(route('driver-truck-assignments.index'))">
          Back to Assignments
        </SecondaryButton>
      </div>
    </template>

    <div class="py-6 max-w-4xl mx-auto">
      <!-- Assignment Summary -->
      <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <h3 class="font-semibold text-gray-700 mb-2">Driver Information</h3>
            <div class="space-y-1 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-500">Name:</span>
                <span class="font-medium">{{ assignment.driver?.name }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Employee ID:</span>
                <span class="font-medium">{{ assignment.driver?.employee_profile?.employee_id }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Current Region:</span>
                <span class="font-medium">{{ assignment.current_region?.name || 'Unknown' }}</span>
              </div>
            </div>
          </div>
          <div>
            <h3 class="font-semibold text-gray-700 mb-2">Truck & Assignment Information</h3>
            <div class="space-y-1 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-500">License Plate:</span>
                <span class="font-medium">{{ assignment.truck?.license_plate }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Make/Model:</span>
                <span class="font-medium">{{ assignment.truck?.make }} {{ assignment.truck?.model }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">Home Region:</span>
                <span class="font-medium">{{ assignment.region?.name }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-gray-500">Current Status:</span>
                <span :class="['px-2 py-1 rounded text-xs font-semibold', getStatusColor(assignment.current_status)]">
                  {{ getStatusLabel(assignment.current_status) }}
                </span>
              </div>
              <div v-if="assignment.current_status === 'cooldown' && assignment.cooldown_ends_at" class="flex justify-between">
                <span class="text-gray-500">Cooldown Ends:</span>
                <span class="font-medium">{{ formatDateTime(assignment.cooldown_ends_at) }}</span>
              </div>
              <div v-if="assignment.is_final_cooldown" class="flex justify-between">
                <span class="text-gray-500">Cooldown Type:</span>
                <span class="font-medium text-red-600">Final Cooldown</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Timeline -->
      <div class="bg-white shadow-sm rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Status History</h3>
          <p class="text-sm text-gray-600 mt-1">
            Showing all status changes for Driver-Truck Set: {{ assignment.driver?.name }} & {{ assignment.truck?.license_plate }}
          </p>
        </div>
        
        <div class="p-6">
          <div v-if="timeline.length > 0" class="space-y-6">
            <div v-for="(log, index) in timeline" :key="log.id" class="flex">
              <!-- Timeline line -->
              <div class="flex flex-col items-center mr-4">
                <div :class="['w-3 h-3 rounded-full', index === 0 ? 'bg-green-500' : 'bg-blue-500']"></div>
                <div v-if="index < timeline.length - 1" class="w-0.5 h-full bg-gray-300 mt-2"></div>
              </div>
              
              <!-- Content -->
              <div class="flex-1 pb-6">
                <div class="flex justify-between items-start mb-2">
                  <div class="flex items-center space-x-2">
                    <span :class="['px-2 py-1 rounded text-xs font-semibold', getStatusColor(log.new_status)]">
                      {{ getStatusLabel(log.new_status) }}
                    </span>
                    <span v-if="log.previous_status" class="text-sm text-gray-500">
                      from {{ getStatusLabel(log.previous_status) }}
                    </span>
                    <span v-else class="text-sm text-gray-500">
                      Initial Status
                    </span>
                  </div>
                  <span class="text-sm text-gray-500">{{ formatDateTime(log.changed_at) }}</span>
                </div>
                
                <p v-if="log.remarks" class="text-sm text-gray-700 bg-gray-50 p-3 rounded mt-2">
                  {{ log.remarks }}
                </p>
                
                <div class="text-xs text-gray-400 mt-1">
                  Log ID: {{ log.id }}
                </div>
              </div>
            </div>
          </div>
          
          <div v-else class="text-center py-8 text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No status history</h3>
            <p class="mt-1 text-sm text-gray-500">No status changes have been recorded for this assignment yet.</p>
          </div>
        </div>
      </div>

      <!-- Assignment Details -->
      <div class="bg-white shadow-sm rounded-lg p-6 mt-6">
        <h3 class="font-semibold text-gray-700 mb-4">Assignment Details</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
          <div>
            <span class="text-gray-500">Assignment ID:</span>
            <span class="font-medium ml-2">{{ assignment.id }}</span>
          </div>
          <div>
            <span class="text-gray-500">Assigned At:</span>
            <span class="font-medium ml-2">{{ assignment.assigned_at ? formatDateTime(assignment.assigned_at) : 'N/A' }}</span>
          </div>
          <div>
            <span class="text-gray-500">Completed At:</span>
            <span class="font-medium ml-2">{{ assignment.completed_at ? formatDateTime(assignment.completed_at) : 'N/A' }}</span>
          </div>
          <div>
            <span class="text-gray-500">Active:</span>
            <span :class="['font-medium ml-2', assignment.is_active ? 'text-green-600' : 'text-red-600']">
              {{ assignment.is_active ? 'Yes' : 'No' }}
            </span>
          </div>
          <div>
            <span class="text-gray-500">Backhaul Eligible:</span>
            <span :class="['font-medium ml-2', assignment.available_for_backhaul ? 'text-green-600' : 'text-red-600']">
              {{ assignment.available_for_backhaul ? 'Yes' : 'No' }}
            </span>
          </div>
          <div>
            <span class="text-gray-500">Final Cooldown:</span>
            <span :class="['font-medium ml-2', assignment.is_final_cooldown ? 'text-red-600' : 'text-gray-600']">
              {{ assignment.is_final_cooldown ? 'Yes' : 'No' }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

defineProps({
  assignment: Object,
  timeline: Array
});

function getStatusLabel(status) {
  const statusMap = {
    'active': 'Active',
    'backhaul_eligible': 'Backhaul Eligible',
    'returning': 'Returning to Base',
    'cooldown': 'In Cooldown',
    'completed': 'Completed',
    'in_transit': 'In Transit',
    'cancelled': 'Cancelled'
  };
  return statusMap[status] || status || 'Unknown';
}

function getStatusColor(status) {
  const colorMap = {
    'active': 'bg-green-100 text-green-800 border border-green-200',
    'backhaul_eligible': 'bg-purple-100 text-purple-800 border border-purple-200',
    'returning': 'bg-orange-100 text-orange-800 border border-orange-200',
    'cooldown': 'bg-yellow-100 text-yellow-800 border border-yellow-200',
    'completed': 'bg-blue-100 text-blue-800 border border-blue-200',
    'in_transit': 'bg-indigo-100 text-indigo-800 border border-indigo-200',
    'cancelled': 'bg-red-100 text-red-800 border border-red-200'
  };
  return colorMap[status] || 'bg-gray-100 text-gray-800 border border-gray-200';
}

function formatDateTime(dateString) {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}
</script>