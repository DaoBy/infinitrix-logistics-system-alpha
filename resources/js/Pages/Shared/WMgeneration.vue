<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Head } from '@inertiajs/vue3';
import { DocumentTextIcon, TruckIcon } from '@heroicons/vue/24/outline';

// Example waybill and manifest data 
const waybills = [
  {
    waybillNumber: 'WB001',
    customerName: 'John Doe',
    origin: 'City A',
    destination: 'City B',
    date: '2023-10-01',
    servicePrice: '$100.00',
  },
  // Add more waybills here
];

const manifests = [
  {
    truckPlateNumber: 'ABC123',
    date: '2023-10-01',
    packageInfo: '2 boxes of electronics',
    recipientInfo: 'Jane Doe',
    waybillReference: 'WB001',
  },
  // Add more manifests here
];
</script>

<template>
  <Head title="Waybill & Manifest Generation" />
  <EmployeeLayout>
    <div class="p-6">
      <h1 class="text-2xl font-semibold text-gray-900 mb-6">Waybill & Manifest Generation</h1>

      <!-- Waybill Section -->
      <div class="mb-8">
        <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
          <DocumentTextIcon class="size-6 mr-2" /> Waybill Slips
        </h2>
        <div class="overflow-x-auto bg-white rounded-lg shadow">
          <table class="min-w-full">
            <thead>
              <tr>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-900">Waybill Number</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-900">Customer Name</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-900">Origin</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-900">Destination</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-900">Date</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-900">Service Price</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="waybill in waybills" :key="waybill.waybillNumber">
                <td class="px-6 py-4 text-sm text-gray-900">{{ waybill.waybillNumber }}</td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ waybill.customerName }}</td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ waybill.origin }}</td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ waybill.destination }}</td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ waybill.date }}</td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ waybill.servicePrice }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Manifest Section -->
      <div>
        <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
          <TruckIcon class="size-6 mr-2" /> Truck Manifests
        </h2>
        <div class="overflow-x-auto bg-white rounded-lg shadow">
          <table class="min-w-full">
            <thead>
              <tr>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-900">Truck Plate Number</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-900">Date</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-900">Package Info</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-900">Recipient Info</th>
                <th class="px-6 py-3 text-left text-sm font-medium text-gray-900">Waybill Reference</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="manifest in manifests" :key="manifest.truckPlateNumber">
                <td class="px-6 py-4 text-sm text-gray-900">{{ manifest.truckPlateNumber }}</td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ manifest.date }}</td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ manifest.packageInfo }}</td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ manifest.recipientInfo }}</td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ manifest.waybillReference }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>