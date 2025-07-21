<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Head, router } from '@inertiajs/vue3';

// Example collector payment data
const collectorPayments = [
  {
    customerName: 'Blase Mariano',
    transactionId: 'T001',
    paymentStatus: 'Unpaid',
    outstandingBalance: '₱1,500.00',
    paymentType: 'Cash',
  },
  // Add more payments here
];

const goToDetails = (transactionId) => {
  router.get(`/collector/payment-status/${transactionId}`);
};

const confirmPayment = (transactionId) => {
  alert(`Confirming payment for Transaction ID: ${transactionId}`);
};
</script>

<template>
  <Head title="Collector Payment Status" />
  <EmployeeLayout>
    <div class="p-6">
      <h1 class="text-2xl font-semibold text-gray-900 mb-6">Collector Payment Status</h1>
      <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full border-collapse">
          <thead class="bg-gray-200">
            <tr>
              <th class="p-3 text-left">Customer Name</th>
              <th class="p-3 text-left">Transaction ID</th>
              <th class="p-3 text-left">Payment Status</th>
              <th class="p-3 text-left">Outstanding Balance</th>
              <th class="p-3 text-left">Payment Type</th>
              <th class="p-3 text-left">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="payment in collectorPayments" :key="payment.transactionId" class="border-b">
              <td class="p-3 text-sm">{{ payment.customerName }}</td>
              <td class="p-3 text-sm">{{ payment.transactionId }}</td>
              <td class="p-3 text-sm">
                <span class="px-2 py-1 text-xs font-semibold text-red-700 bg-gray-300 rounded">
                  {{ payment.paymentStatus }}
                </span>
              </td>
              <td class="p-3 text-sm">{{ payment.outstandingBalance }}</td>
              <td class="p-3 text-sm">{{ payment.paymentType }}</td>
              <td class="p-3 flex space-x-2">
                <!-- Confirm Payment Button -->
                <button
                  @click="confirmPayment(payment.transactionId)"
                  class="px-4 py-2 text-sm text-white bg-green-600 rounded hover:bg-gray-700"
                >
                  Confirm Payment
                </button>

                <!-- View Details Button -->
                <button
                  @click="goToDetails(payment.transactionId)"
                  class="px-4 py-2 text-sm text-white bg-red-500 rounded hover:bg-gray-600"
                >
                  View Details
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </EmployeeLayout>
</template>