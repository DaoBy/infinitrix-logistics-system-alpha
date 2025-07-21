<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import NavLink from '@/Components/NavLink.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

// Get authenticated user
const authUser = computed(() => usePage().props.auth.user);
const notifications = computed(() => usePage().props.notifications || []);

// Mobile menu state
const isMobileMenuOpen = ref(false);
const isNotificationOpen = ref(false);
const unreadCount = computed(() => notifications.value.filter(n => !n.read).length);

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

const toggleNotifications = () => {
  isNotificationOpen.value = !isNotificationOpen.value;
};

const markAsRead = async (notification) => {
  if (!notification.read) {
    await router.put(route('notifications.markAsRead', notification.id));
  }
};

const markAllAsRead = async () => {
  await router.put(route('notifications.markAllAsRead'));
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
        <!-- Notification Bell -->
        <div class="relative">
          <button @click="toggleNotifications" class="p-1 rounded-full hover:bg-gray-100 focus:outline-none relative">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span v-if="unreadCount > 0" class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full">
              {{ unreadCount }}
            </span>
          </button>
          
          <!-- Notification Dropdown -->
          <div v-if="isNotificationOpen" class="absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg overflow-hidden z-50">
            <div class="p-4 border-b border-gray-200 flex justify-between items-center">
              <h3 class="text-lg font-medium text-gray-900">Notifications</h3>
              <button @click="markAllAsRead" class="text-sm text-blue-500 hover:text-blue-700">
                Mark all as read
              </button>
            </div>
            
            <div class="max-h-96 overflow-y-auto">
              <div v-if="notifications.length === 0" class="p-4 text-center text-gray-500">
                No notifications
              </div>
              
              <div v-for="notification in notifications" :key="notification.id" 
                   @click="markAsRead(notification)"
                   class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0"
                   :class="{ 'bg-gray-50': !notification.read }">
                <div class="flex items-start">
                  <div class="flex-shrink-0 pt-0.5">
                    <div class="h-8 w-8 rounded-full flex items-center justify-center" 
                         :class="{
                           'bg-blue-100 text-blue-600': notification.type === 'info',
                           'bg-green-100 text-green-600': notification.type === 'approval',
                           'bg-red-100 text-red-600': notification.type === 'denial'
                         }">
                      <template v-if="notification.type === 'approval'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                      </template>
                      <template v-else-if="notification.type === 'denial'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                      </template>
                      <template v-else>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2h-1V9z" clip-rule="evenodd" />
                        </svg>
                      </template>
                    </div>
                  </div>
                  <div class="ml-3 flex-1">
                    <p class="text-sm font-medium text-gray-900">{{ notification.title }}</p>
                    <p class="text-sm text-gray-500">{{ notification.message }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ new Date(notification.created_at).toLocaleString() }}</p>
                  </div>
                  <div v-if="!notification.read" class="ml-2 flex-shrink-0">
                    <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                  </div>
                </div>
              </div>
            </div>
            
            <div v-if="notifications.length > 0" class="p-2 border-t border-gray-200 text-center">
              <a :href="route('customer.notifications.index')" class="text-sm text-blue-500 hover:text-blue-700">
                View all notifications
              </a>
            </div>
          </div>
        </div>

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
            <div class="px-4 py-2 border-b border-gray-200">
              <div class="text-sm font-medium text-gray-800">{{ authUser.name }}</div>
              <div class="text-sm text-gray-500">{{ authUser.email }}</div>
            </div>
            <DropdownLink :href="route('profile.edit')">My Profile</DropdownLink>
            <DropdownLink :href="route('address.book')">Address Book</DropdownLink>
            <DropdownLink :href="route('customer.delivery-requests.create')">Request Delivery</DropdownLink>
            <DropdownLink :href="route('tracking')">Track Package</DropdownLink>
            <DropdownLink :href="route('customer.delivery-requests.index')">Request History</DropdownLink>
            <DropdownLink :href="route('logout')" method="post" as="button" class="text-lg font-medium text-red-600 hover:bg-gray-100 rounded-lg py-2"> Logout </DropdownLink>
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

      <!-- Mobile Menu Button -->
      <button @click="toggleMobileMenu" class="md:hidden p-2 rounded focus:outline-none">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
        </svg>
      </button>
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
        <div class="pb-4">
          <div class="text-base font-semibold text-gray-800 text-center">{{ authUser.name }}</div>
          <div class="text-sm text-gray-500 text-center">{{ authUser.email }}</div>
        </div>

        <!-- Mobile Notifications -->
        <div class="w-full mb-4">
          <div class="flex justify-between items-center mb-2">
            <h3 class="text-lg font-medium text-gray-900">Notifications</h3>
            <button @click="markAllAsRead" class="text-sm text-blue-500 hover:text-blue-700">
              Mark all as read
            </button>
          </div>
          
          <div class="max-h-64 overflow-y-auto border rounded-lg">
            <div v-if="notifications.length === 0" class="p-4 text-center text-gray-500">
              No notifications
            </div>
            
            <div v-for="notification in notifications" :key="notification.id" 
                 @click="markAsRead(notification)"
                 class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0"
                 :class="{ 'bg-gray-50': !notification.read }">
              <div class="flex items-start">
                <div class="flex-shrink-0 pt-0.5">
                  <div class="h-8 w-8 rounded-full flex items-center justify-center" 
                       :class="{
                         'bg-blue-100 text-blue-600': notification.type === 'info',
                         'bg-green-100 text-green-600': notification.type === 'approval',
                         'bg-red-100 text-red-600': notification.type === 'denial'
                       }">
                    <template v-if="notification.type === 'approval'">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                      </svg>
                    </template>
                    <template v-else-if="notification.type === 'denial'">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                      </svg>
                    </template>
                    <template v-else>
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2h-1V9z" clip-rule="evenodd" />
                      </svg>
                    </template>
                  </div>
                </div>
                <div class="ml-3 flex-1">
                  <p class="text-sm font-medium text-gray-900">{{ notification.title }}</p>
                  <p class="text-sm text-gray-500">{{ notification.message }}</p>
                  <p class="text-xs text-gray-400 mt-1">{{ new Date(notification.created_at).toLocaleString() }}</p>
                </div>
                <div v-if="!notification.read" class="ml-2 flex-shrink-0">
                  <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                </div>
              </div>
            </div>
          </div>
          
          <div v-if="notifications.length > 0" class="mt-2 text-center">
            <a :href="route('customer.notifications.index')" class="text-sm text-blue-500 hover:text-blue-700">
              View all notifications
            </a>
          </div>
        </div>

        <div class="flex flex-col space-y-2 justify-center w-full">
          <NavLink :href="route('profile.edit')" class="text-lg font-medium text-gray-700 hover:bg-gray-100 rounded-lg py-2 justify-center">
            My Profile
          </NavLink>
          <NavLink :href="route('address.book')" class="text-lg font-medium text-gray-700 hover:bg-gray-100 rounded-lg py-2 justify-center">
            Address Book
          </NavLink>
          <NavLink :href="route('customer.delivery-requests.create')" class="text-lg font-medium text-gray-700 hover:bg-gray-100 rounded-lg py-2 justify-center">
            Request Delivery
          </NavLink>
          <NavLink :href="route('tracking')" class="text-lg font-medium text-gray-700 hover:bg-gray-100 rounded-lg py-2 justify-center">
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