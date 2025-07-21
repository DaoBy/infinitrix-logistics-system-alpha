<script setup>
import NavBar from '@/Components/NavBar.vue';
import { Link } from '@inertiajs/vue3';
import { FaceSmileIcon, CameraIcon, ChatBubbleLeftRightIcon } from '@heroicons/vue/24/outline';
</script>

<template>
  <div class="flex min-h-screen flex-col bg-gray-100">
    <!-- Navigation Bar -->
    <NavBar />

    <!-- Main Content (with additional padding) -->
    <div class="flex flex-1 flex-col items-center pt-20 sm:pt-24">
      <div>
        <Link href="/">
          <ApplicationLogo class="h-20 w-20 fill-current text-gray-500" />
        </Link>
      </div>

      <div class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg">
        <slot />
      </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t mt-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <!-- Company Information -->
          <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">Infinitrix Cargo Express</h3>
            <p class="text-sm text-gray-600">
              Fast, reliable, and secure logistics services for all your delivery needs.
            </p>
            <div class="text-sm text-gray-600">
              <p>123 Logistics Street</p>
              <p>City, Province, ZIP Code</p>
              <p>Country</p>
            </div>
          </div>

          <!-- Quick Links -->
          <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">Quick Links</h3>
            <ul class="space-y-2">
              <li><Link href="/" class="text-sm text-gray-600 hover:text-gray-900">Home</Link></li>
              <li><Link href="/services" class="text-sm text-gray-600 hover:text-gray-900">Services</Link></li>
              <li><Link href="/contact" class="text-sm text-gray-600 hover:text-gray-900">Contact Us</Link></li>
              <li><Link href="/privacy-policy" class="text-sm text-gray-600 hover:text-gray-900">Privacy Policy</Link></li>
              <li><Link href="/terms" class="text-sm text-gray-600 hover:text-gray-900">Terms of Service</Link></li>
            </ul>
          </div>

          <!-- Contact Information -->
          <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">Contact Us</h3>
            <div class="text-sm text-gray-600">
              <p>Email: support@infinitrixcargo.com</p>
              <p>Phone: +1 (123) 456-7890</p>
              <p>Fax: +1 (123) 456-7891</p>
            </div>

            <!-- Social Media Icons -->
            <div class="flex space-x-4">
              <a href="#" class="text-gray-600 hover:text-gray-900">
                <FaceSmileIcon class="size-6" />
              </a>
              <a href="#" class="text-gray-600 hover:text-gray-900">
                <CameraIcon class="size-6" />
              </a>
              <a href="#" class="text-gray-600 hover:text-gray-900">
                <ChatBubbleLeftRightIcon class="size-6" />
              </a>
            </div>
          </div>
        </div>

        <!-- Copyright -->
        <div class="border-t border-gray-200 mt-8 py-4 text-center text-sm text-gray-600">
          © {{ new Date().getFullYear() }} Infinitrix Cargo Express. All Rights Reserved.
        </div>
      </div>
    </footer>
  </div>
</template>
