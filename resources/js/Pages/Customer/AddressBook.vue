<script setup>

import { ref } from 'vue'; // Import ref here
import { Head } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';

// Placeholder data for addresses
const addresses = ref([
  { id: 1, name: 'John Doe', street: '123 Main St', city: 'New York', province: 'NY', zip: '10001', isDefault: true },
  { id: 2, name: 'Jane Smith', street: '456 Elm St', city: 'Los Angeles', province: 'CA', zip: '90001', isDefault: false },
]);
</script>

<template>
  <Head title="Address Book" />

  <GuestLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">Address Book</h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <!-- Add New Address Button -->
            <div class="mb-6">
              <PrimaryButton @click="() => {}">Add New Address</PrimaryButton>
            </div>

            <!-- Address List -->
            <div class="space-y-6">
              <div v-for="address in addresses" :key="address.id" class="p-6 border border-gray-200 rounded-lg">
                <div class="flex justify-between items-center">
                  <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ address.name }}</h3>
                    <p class="text-sm text-gray-600">{{ address.street }}, {{ address.city }}, {{ address.province }} {{ address.zip }}</p>
                  </div>
                  <div>
                    <span v-if="address.isDefault" class="bg-green-100 text-green-800 text-sm font-medium px-2.5 py-0.5 rounded">Default</span>
                  </div>
                </div>
                <div class="mt-4 flex space-x-4">
                  <button class="text-sm text-blue-600 hover:text-blue-800">Edit</button>
                  <button class="text-sm text-red-600 hover:text-red-800">Delete</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </GuestLayout>
</template>