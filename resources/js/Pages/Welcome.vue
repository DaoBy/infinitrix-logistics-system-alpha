<template>
    <div class="min-h-screen flex flex-col justify-between bg-gray-100">
      <!-- Navbar -->
      <header class="bg-white shadow-md py-4">
        <div class="container mx-auto flex justify-between items-center px-6">
          <h1 class="text-2xl font-bold text-gray-800">Logistics Management</h1>
          <div>
            <a :href="route('customer.login')" class="text-blue-600 hover:underline mr-4">Login</a>
            <a :href="route('customer.register')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Register</a>
          </div>
        </div>
      </header>
  
      <!-- Main Content -->
      <main class="flex-1 flex items-center justify-center">
        <div class="text-center">
          <h2 class="text-4xl font-semibold text-gray-900 mb-4">Welcome to Our Logistics System</h2>
          <p class="text-lg text-gray-700 mb-6">Efficient, Reliable, and Secure Cargo Management.</p>
          <a :href="route('customer.register')" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700">Get Started</a>
        </div>
      </main>
  
      <!-- Footer -->
      <footer class="bg-gray-800 text-white py-4 text-center">
        <p>&copy; 2025 Logistics Management. All rights reserved.</p>
      </footer>
    </div>
  </template>
  
  <script setup>
  import { usePage } from '@inertiajs/vue3';
  
  const { props } = usePage();
  </script>
  