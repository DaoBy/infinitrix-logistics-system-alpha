<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Table, TableHead, TableRow, TableHeaderCell, TableBody, TableCell } from '@heroicons/vue/24/outline';

// Example transaction data 
const transactions = [
  {
    customerId: 'C001',
    customerName: 'John Doe',
    date: '2023-10-01',
    packageDetails: '2 boxes of electronics',
    servicePrice: '$50.00',
    totalPrice: '$100.00',
  },
  // Add more transactions here
];
</script>

<template>
  <Head title="Transaction History" />
  <EmployeeLayout>
    <div class="p-6">
      <h1 class="text-2xl font-semibold text-gray-900 mb-6">Transaction History</h1>
      
      <div class="overflow-x-auto bg-white rounded-lg shadow">
        <Table class="min-w-full">
          <TableHead>
            <TableRow>
              <TableHeaderCell>Customer ID</TableHeaderCell>
              <TableHeaderCell>Customer Name</TableHeaderCell>
              <TableHeaderCell>Date</TableHeaderCell>
              <TableHeaderCell>Package Details</TableHeaderCell>
              <TableHeaderCell>Service Price</TableHeaderCell>
              <TableHeaderCell>Total Price</TableHeaderCell>
            </TableRow>
          </TableHead>
          <TableBody>
            <TableRow v-for="transaction in transactions" :key="transaction.customerId">
              <TableCell>{{ transaction.customerId }}</TableCell>
              <TableCell>{{ transaction.customerName }}</TableCell>
              <TableCell>{{ transaction.date }}</TableCell>
              <TableCell>{{ transaction.packageDetails }}</TableCell>
              <TableCell>{{ transaction.servicePrice }}</TableCell>
              <TableCell>{{ transaction.totalPrice }}</TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>
  </EmployeeLayout>
</template>