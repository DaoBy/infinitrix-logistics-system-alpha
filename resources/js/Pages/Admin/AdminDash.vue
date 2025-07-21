<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ChartBarIcon, TruckIcon, ClockIcon } from '@heroicons/vue/24/outline';

// Example dashboard data 
const dashboardData = {
  totalActiveDeliveries: 15,
  pendingPayments: '₱ 1,200.00',
  recentDeliveryRequests: [
    { id: 'D001', customer: 'Mark Angelo', status: 'Pending' },
    { id: 'D002', customer: 'Patrick Duban', status: 'In Transit' },
  ],
  driverStatuses: [
    { id: 'DR001', name: 'Driver A', status: 'On Route', location: 'City A' },
    { id: 'DR002', name: 'Driver B', status: 'Idle', location: 'Warehouse' },
  ],
};
</script>

<template>
  <Head title="Admin Dashboard" />
  <EmployeeLayout>
    <div class="p-6">
      <h1 class="text-2xl font-semibold text-gray-900 mb-6">Admin Dashboard</h1>

      <!-- Metrics Section -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow">
          <div class="flex items-center">
            <TruckIcon class="size-6 text-blue-500 mr-2" />
            <h3 class="text-lg font-semibold text-gray-900">Active Deliveries</h3>
          </div>
          <p class="text-2xl font-bold text-gray-900 mt-2">{{ dashboardData.totalActiveDeliveries }}</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
          <div class="flex items-center">
                        <h3 class="text-lg font-semibold text-gray-900">Pending Payments</h3>
          </div>
          <p class="text-2xl font-bold text-gray-900 mt-2">{{ dashboardData.pendingPayments }}</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
          <div class="flex items-center">
            <ClockIcon class="size-6 text-yellow-500 mr-2" />
            <h3 class="text-lg font-semibold text-gray-900">Recent Requests</h3>
          </div>
          <p class="text-2xl font-bold text-gray-900 mt-2">{{ dashboardData.recentDeliveryRequests.length }}</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
          <div class="flex items-center">
            <ChartBarIcon class="size-6 text-purple-500 mr-2" />
            <h3 class="text-lg font-semibold text-gray-900">Driver Activity</h3>
          </div>
          <p class="text-2xl font-bold text-gray-900 mt-2">{{ dashboardData.driverStatuses.length }}</p>
        </div>
      </div>

      <!-- Recent Delivery Requests -->
      <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Recent Delivery Requests</h2>
        <table class="min-w-full">
          <thead>
            <tr>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-900">Request ID</th>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-900">Customer</th>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-900">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="request in dashboardData.recentDeliveryRequests" :key="request.id">
              <td class="px-6 py-4 text-sm text-gray-900">{{ request.id }}</td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ request.customer }}</td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ request.status }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Driver Statuses -->
      <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Driver Statuses</h2>
        <table class="min-w-full">
          <thead>
            <tr>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-900">Driver ID</th>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-900">Name</th>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-900">Status</th>
              <th class="px-6 py-3 text-left text-sm font-medium text-gray-900">Location</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="driver in dashboardData.driverStatuses" :key="driver.id">
              <td class="px-6 py-4 text-sm text-gray-900">{{ driver.id }}</td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ driver.name }}</td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ driver.status }}</td>
              <td class="px-6 py-4 text-sm text-gray-900">{{ driver.location }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </EmployeeLayout>
</template>