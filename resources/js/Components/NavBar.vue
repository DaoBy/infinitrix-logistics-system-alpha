<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import NavLink from '@/Components/NavLink.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import NotificationBell from '@/Components/NotificationBell.vue';

// Get authenticated user
const authUser = computed(() => usePage().props.auth.user);
const notifications = computed(() => usePage().props.notifications || []);

// Mobile menu state
const isMobileMenuOpen = ref(false);

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

// Determine Home Link (Public Home vs. Dashboard)
const homeLink = computed(() => {
  return authUser.value ? route('customer.home') : route('customer.home');
});
</script>

<template>
  <nav class="bg-white shadow-md w-full fixed top-0 z-50">
    <div class="w-full max-w-[90%] xl:max-w-[1280px] mx-auto flex justify-between items-center px-6 lg:px-12 py-4">
      <!-- Logo -->
      <a :href="route('customer.home')" class="flex items-center space-x-2 text-2xl font-bold">
        <img src="@/assets/logo.jpg" alt="Infinitrix Logo" class="h-12 w-auto" />
        <span class="text-green-700">Infinitrix</span>
      </a>

      <!-- Desktop Navigation -->
      <div class="hidden md:flex space-x-8">
        <NavLink :href="route('customer.home')">Home</NavLink>
        <NavLink :href="route('services')">Services</NavLink>
        <NavLink :href="route('about.us')">About Us</NavLink>
        <NavLink :href="route('contact.us')">Contact Us</NavLink>
      </div>

      <!-- Auth & User Dropdown -->
      <div v-if="authUser" class="hidden md:flex items-center space-x-4">
        <!-- Notification Bell Component -->
        <NotificationBell :notifications="notifications" />

        <!-- User Dropdown -->
        <Dropdown>
          <template #trigger>
            <button class="flex items-center space-x-2 focus:outline-none">
              <!-- Profile Icon -->
              <svg
                class="w-8 h-8 text-gray-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                />
              </svg>
              <span class="text-gray-700">{{ authUser.name }}</span>
              <svg
                class="-me-0.5 ms-2 h-4 w-4"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                  clip-rule="evenodd"
                />
              </svg>
            </button>
          </template>
          <template #content>
            <div class="px-4 py-3 border-b border-gray-200 text-center min-w-[200px]">
              <div class="text-sm font-medium text-gray-800 break-words">{{ authUser.name }}</div>
              <div class="text-sm text-gray-500 break-words mt-1">{{ authUser.email }}</div>
            </div>
            <DropdownLink :href="route('profile.edit')" class="text-center">Account Settings</DropdownLink>
            <DropdownLink :href="route('customer.profile.update')" class="text-center">Delivery Information</DropdownLink>
            <DropdownLink :href="route('customer.delivery-requests.create')" class="text-center">Request Delivery</DropdownLink>
            <DropdownLink :href="route('tracking.landing')" class="text-center">Track Package</DropdownLink>
            <DropdownLink :href="route('customer.delivery-requests.index')" class="text-center">Request History</DropdownLink>
            <DropdownLink :href="route('logout')" method="post" as="button" class="text-center text-red-600 hover:bg-red-50">Logout</DropdownLink>
          </template>
        </Dropdown>
      </div>

      <!-- Guest Links -->
      <div v-else class="hidden md:flex space-x-4">
        <NavLink :href="route('login')">
          <SecondaryButton>Sign In</SecondaryButton>
        </NavLink>
        <NavLink :href="route('customer.register')">
          <PrimaryButton variant="dark" class="bg-green-600">Sign Up</PrimaryButton>
        </NavLink>
      </div>

      <!-- Mobile Header Right Section -->
      <div class="md:hidden flex items-center space-x-4">
        <!-- Notification Bell for Mobile (Facebook style) -->
        <div v-if="authUser" class="flex items-center">
          <NotificationBell :notifications="notifications" />
        </div>
        
        <!-- Mobile Menu Button -->
        <button @click="toggleMobileMenu" class="p-2 rounded focus:outline-none">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- Mobile Navigation Menu -->
    <div v-if="isMobileMenuOpen" class="md:hidden bg-white shadow-md p-4 space-y-4">
      <!-- General Links -->
      <div class="flex flex-col space-y-2 px-6 justify-center">
        <NavLink :href="homeLink" class="text-lg font-medium text-gray-700 hover:bg-gray-100 rounded-lg py-2 justify-center">
          Home
        </NavLink>
        <NavLink :href="route('services')" class="text-lg font-medium text-gray-700 hover:bg-gray-100 rounded-lg py-2 justify-center">
          Services
        </NavLink>
        <NavLink :href="route('about.us')" class="text-lg font-medium text-gray-700 hover:bg-gray-100 rounded-lg py-2 justify-center">
          About US
        </NavLink>
        <NavLink :href="route('contact.us')" class="text-lg font-medium text-gray-700 hover:bg-gray-100 rounded-lg py-2 justify-center">
          Contact Us
        </NavLink>
      </div>

      <!-- Authenticated User Menu -->
      <div v-if="authUser" class="border-t pt-4 mt-4 px-6 items-center flex flex-col">
        <div class="pb-4 text-center">
          <div class="text-base font-semibold text-gray-800 break-words">{{ authUser.name }}</div>
          <div class="text-sm text-gray-500 break-words mt-1">{{ authUser.email }}</div>
        </div>

        <div class="flex flex-col space-y-2 justify-center w-full">
          <NavLink :href="route('profile.edit')" class="text-lg font-medium text-gray-700 hover:bg-gray-100 rounded-lg py-2 justify-center">
            My Profile
          </NavLink>
          <NavLink :href="route('customer.profile.update')" class="text-lg font-medium text-gray-700 hover:bg-gray-100 rounded-lg py-2 justify-center">
            Update Profile Request
          </NavLink>
          <NavLink :href="route('customer.delivery-requests.create')" class="text-lg font-medium text-gray-700 hover:bg-gray-100 rounded-lg py-2 justify-center">
            Request Delivery
          </NavLink>
          <NavLink :href="route('tracking.landing')" class="text-lg font-medium text-gray-700 hover:bg-gray-100 rounded-lg py-2 justify-center">
            Track Package
          </NavLink>
          <NavLink :href="route('customer.delivery-requests.index')" class="text-lg font-medium text-gray-700 hover:bg-gray-100 rounded-lg py-2 justify-center">
            Request History
          </NavLink>
          <NavLink :href="route('customer.transactions.index')" class="text-lg font-medium text-gray-700 hover:bg-gray-100 rounded-lg py-2 justify-center">
            Transaction History
          </NavLink>
          <NavLink :href="route('logout')" method="post" as="button" class="text-lg font-medium text-red-600 hover:bg-gray-100 rounded-lg py-2 justify-center">
            Logout
          </NavLink>
        </div>
      </div>

      <!-- Guest Links -->
      <div v-else class="border-t pt-4 space-y-2">
        <NavLink :href="route('login')">
          <SecondaryButton class="w-full">Sign In</SecondaryButton>
        </NavLink>
        <NavLink :href="route('customer.register')">
          <PrimaryButton class="w-full">Sign Up</PrimaryButton>
        </NavLink>
      </div>
    </div>
  </nav>
</template>
