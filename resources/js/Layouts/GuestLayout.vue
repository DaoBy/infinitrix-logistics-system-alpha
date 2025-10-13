<script setup>
import NavLink from '@/Components/NavLink.vue';
import NavBar from '@/Components/NavBar.vue';
import { usePage } from '@inertiajs/vue3';
import { FaceSmileIcon, CameraIcon, ChatBubbleLeftRightIcon } from '@heroicons/vue/24/outline';

const page = usePage();
</script>

<template>
  <div class="min-h-screen bg-gray-100 flex flex-col">
    <!-- Navigation Bar -->
    <NavBar />

    <!-- Content area -->
    <div class="flex-1 flex flex-col pt-20"> <!-- 80px top padding for navbar spacing -->
      <!-- Header Slot -->
      <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto py-6 px-6 lg:px-8">
          <slot name="header" />
        </div>
      </header>

      <!-- Main Content -->
      <main class="flex-1 w-full max-w-8xl mx-auto px-6 py-10">
        <slot />
      </main>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t">
      <div class="max-w-7xl mx-auto px-6 py-8 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <!-- Company Information -->
          <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">Infinitrix Cargo Express</h3>
            <p class="text-sm text-gray-600">
              Fast, reliable, and secure logistics services for all your delivery needs.
            </p>
            <div class="text-sm text-gray-600">
              <p>Camelo Building, Tahao Rd, Barangay</p>
              <p>Cruzada, Legazpi City, 4500 Albay</p>
              <p>Philippines</p>
            </div>
          </div>

          <!-- Quick Links -->
          <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">Quick Links</h3>
            <ul class="space-y-2">
              <li>
                <NavLink :href="route('customer.home')" class="text-l text-gray-600 hover:text-gray-900">Home</NavLink>
              </li>
              <li>
                <NavLink :href="route('services')" class="text-l text-gray-600 hover:text-gray-900">Services</NavLink>
              </li>
              <li>
                <NavLink :href="route('contact.us')" class="text-l text-gray-600 hover:text-gray-900">Contact Us</NavLink>
              </li>
              <li>
                <NavLink :href="route('about.us')" class="text-l text-gray-600 hover:text-gray-900">About Us</NavLink>
              </li>
            </ul>
          </div>

          <!-- Contact Information -->
          <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">Contact Us</h3>
            <div class="text-sm text-gray-600">
              <p>Email: support@infinitrixcargo.com</p>
              <p>Phone: +63 912 345 6789</p>
        
            </div>
            <div class="flex space-x-4">
              <a href="#" class="text-gray-600 hover:text-gray-900">
                <FaceSmileIcon class="h-6 w-6" />
              </a>
              <a href="#" class="text-gray-600 hover:text-gray-900">
                <CameraIcon class="h-6 w-6" />
              </a>
              <a href="#" class="text-gray-600 hover:text-gray-900">
                <ChatBubbleLeftRightIcon class="h-6 w-6" />
              </a>
            </div>
          </div>
        </div>

        <!-- Copyright -->
        <div class="border-t border-gray-200 mt-6 py-4 text-center text-sm text-gray-600">
          © {{ new Date().getFullYear() }} Infinitrix Cargo Express. All Rights Reserved.
        </div>
      </div>
    </footer>
  </div>
</template>
