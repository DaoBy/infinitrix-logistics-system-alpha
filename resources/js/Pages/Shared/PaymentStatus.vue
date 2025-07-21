<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Table, TableHead, TableRow, TableHeaderCell, TableBody, TableCell } from '@heroicons/vue/24/outline';

// Example payment data 
const payments = [
  {
    customerName: 'John Doe',
    transactionId: 'T001',
    outstandingBalance: '$50.00',
    paymentStatus: 'Partially Paid',
  },
  // Add more payments here
];
</script>

<template>
  <Head title="Payment Status" />
  <EmployeeLayout>
    <div class="p-6">
      <h1 class="text-2xl font-semibold text-gray-900 mb-6">Payment Status</h1>
      <div class="overflow-x-auto bg-white rounded-lg shadow">
        <Table class="min-w-full">
          <TableHead>
            <TableRow>
              <TableHeaderCell>Customer Name</TableHeaderCell>
              <TableHeaderCell>Transaction ID</TableHeaderCell>
              <TableHeaderCell>Outstanding Balance</TableHeaderCell>
              <TableHeaderCell>Payment Status</TableHeaderCell>
            </TableRow>
          </TableHead>
          <TableBody>
            <TableRow v-for="payment in payments" :key="payment.transactionId">
              <TableCell>{{ payment.customerName }}</TableCell>
              <TableCell>{{ payment.transactionId }}</TableCell>
              <TableCell>{{ payment.outstandingBalance }}</TableCell>
              <TableCell>{{ payment.paymentStatus }}</TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>
  </EmployeeLayout>
</template>