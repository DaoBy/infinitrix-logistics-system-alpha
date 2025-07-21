<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Head } from '@inertiajs/vue3';
import { DocumentTextIcon, ChartBarIcon } from '@heroicons/vue/24/outline';

// Example report data 
const reports = [
  {
    type: 'Waybill Slip',
    description: 'Detailed shipment report for October 2023',
    date: '2023-10-01',
  },
  {
    type: 'Truck Manifest',
    description: 'Shipment and driver report for October 2023',
    date: '2023-10-01',
  },
  // Add more reports here
];
</script>

<template>
  <Head title="Reports" />
  <EmployeeLayout>
    <div class="p-6">
      <h1 class="text-2xl font-semibold text-gray-900 mb-6">Reports</h1>
      <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full">
          <thead>
            <tr>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-900">Report Type</th>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-900">Description</th>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-900">Date</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="report in reports" :key="report.type">
              <td class="px-6 py-4 text-sm text-gray-900">{{ report.type }}</td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ report.description }}</td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ report.date }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </EmployeeLayout>
</template>