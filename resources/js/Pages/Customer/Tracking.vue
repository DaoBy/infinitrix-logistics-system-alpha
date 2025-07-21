<script setup>
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';

// Tracking input and results
const trackingCode = ref('');
const trackingData = ref(null);
const errorMessage = ref('');

// Mock tracking data (Replace this with real API call)
const mockTrackingData = {
  "ABC123": {
    status: "In Transit",
    lastLocation: "Warehouse - Manila",
    estimatedDelivery: "2025-02-25",
    history: [
      { date: "2025-02-18", location: "Warehouse - Manila", status: "Processed" },
      { date: "2025-02-19", location: "Manila Hub", status: "Dispatched" },
      { date: "2025-02-20", location: "In Transit", status: "On the way to destination" }
    ]
  }
};

// Function to fetch tracking details
const trackPackage = () => {
  if (mockTrackingData[trackingCode.value]) {
    trackingData.value = mockTrackingData[trackingCode.value];
    errorMessage.value = '';
  } else {
    trackingData.value = null;
    errorMessage.value = 'Invalid tracking code. Please try again.';
  }
};
</script>

<template>
  <GuestLayout>
    <div class="container mx-auto px-6 py-16">
      <h1 class="text-4xl font-bold text-center text-gray-900">Track Your Package</h1>
      <p class="mt-2 text-lg text-gray-600 text-center">
        Enter your tracking code to check the status of your package.
      </p>

      <!-- Track Form -->
      <div class="mt-8 max-w-lg mx-auto flex space-x-2">
        <TextInput
          v-model="trackingCode"
          placeholder="Enter Tracking Code"
          class="flex-1"
        />
        <PrimaryButton @click="trackPackage">Track</PrimaryButton>
      </div>

      <!-- Error Message -->
      <p v-if="errorMessage" class="text-red-500 text-center mt-4">{{ errorMessage }}</p>

      <!-- Tracking Results -->
      <div v-if="trackingData" class="mt-8 bg-white shadow-lg rounded-lg overflow-hidden">
        <div class="p-6">
          <h2 class="text-2xl font-bold text-gray-900">Tracking Results</h2>

          <!-- Status Overview -->
          <div class="mt-4 space-y-2">
            <p class="text-gray-700">
              <span class="font-semibold">Status:</span> {{ trackingData.status }}
            </p>
            <p class="text-gray-700">
              <span class="font-semibold">Last Location:</span> {{ trackingData.lastLocation }}
            </p>
            <p class="text-gray-700">
              <span class="font-semibold">Estimated Delivery:</span> {{ trackingData.estimatedDelivery }}
            </p>
          </div>

          <!-- Tracking History -->
          <div class="mt-6">
            <h3 class="text-xl font-semibold text-gray-900">Tracking History</h3>
            <ul class="mt-4 space-y-4">
              <li
                v-for="(item, index) in trackingData.history"
                :key="index"
                class="p-4 bg-gray-50 rounded-lg"
              >
                <div class="flex justify-between items-center">
                  <span class="font-medium text-gray-900">{{ item.date }}</span>
                  <span class="text-sm text-gray-600">{{ item.location }}</span>
                </div>
                <p class="mt-1 text-gray-700">{{ item.status }}</p>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Additional Help Section -->
      <div v-else class="mt-12 text-center">
        <p class="text-gray-600">Need help? Contact our support team at <a href="mailto:support@infinitrixcargo.com" class="text-blue-600 hover:underline">support@infinitrixcargo.com</a>.</p>
      </div>
    </div>
  </GuestLayout>
</template>