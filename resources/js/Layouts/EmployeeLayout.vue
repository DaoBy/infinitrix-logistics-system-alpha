<script setup>
import ESidebar from '@/Components/ESidebar.vue';
import { usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const page = usePage();

// Get the Google Maps API key from Inertia shared props
const googleMapsApiKey = page.props.googleMapsApiKey;

// Sidebar width state (sync with ESidebar)
const sidebarWidth = ref(256); // default open: 256px (w-64), closed: 80px (w-20)

function updateSidebarWidth(isOpen) {
  sidebarWidth.value = isOpen ? 256 : 80;
}

// Listen for sidebar toggle event from ESidebar
function onSidebarToggle(isOpen) {
  updateSidebarWidth(isOpen);
}

// On mount, sync with localStorage (if ESidebar uses it)
onMounted(() => {
  const savedState = localStorage.getItem('sidebarOpen');
  updateSidebarWidth(savedState === null || savedState === 'true');

  // Inject Google Maps API script with the actual API key
  if (googleMapsApiKey && !document.getElementById('google-maps-api')) {
    const script = document.createElement('script');
    script.id = 'google-maps-api';
    script.src = `https://maps.googleapis.com/maps/api/js?key=${googleMapsApiKey}&libraries=places&loading=async`;
    script.async = true;
    script.defer = true;
    document.head.appendChild(script);
    
    console.log('Google Maps API script loaded with key:', googleMapsApiKey);
  } else if (!googleMapsApiKey) {
    console.error('Google Maps API key is missing');
  }
});
</script>

<template>
  <v-app>
    <v-layout style="height: 100vh; overflow: hidden;">
      <!-- Sidebar -->
      <div
        :style="{
          position: 'fixed',
          top: 0,
          left: 0,
          height: '100vh',
          zIndex: 1100,
          width: sidebarWidth + 'px',
          transition: 'width 0.3s cubic-bezier(0.4,0,0.2,1)'
        }"
        class="hidden lg:block"
      >
        <ESidebar @sidebar-toggle="onSidebarToggle" />
      </div>

      <!-- Mobile Sidebar Overlay -->
      <div class="lg:hidden">
        <ESidebar @sidebar-toggle="onSidebarToggle" />
      </div>

      <!-- Main Content Area -->
      <v-main
        class="flex flex-col"
        :style="{
          marginLeft: sidebarWidth + 'px',
          width: `calc(100vw - ${sidebarWidth}px)`,
          height: '100vh',
          overflow: 'hidden',
          transition: 'all 0.3s cubic-bezier(0.4,0,0.2,1)'
        }"
      >
        <!-- Header Slot -->
        <header v-if="$slots.header" class="bg-white shadow-sm shrink-0 border-b border-gray-200">
          <div class="px-4 sm:px-6 lg:px-8 py-4">
            <slot name="header" />
          </div>
        </header>

        <!-- Scrollable Main Content -->
        <div class="flex-1 overflow-y-auto overflow-x-hidden">
          <div class="w-full p-4 sm:p-6">
            <slot />
          </div>
        </div>

        <!-- Fixed Footer -->
        <footer class="bg-white border-t border-gray-200 py-3 px-4 sm:px-6 shrink-0">
          <div class="w-full flex flex-col sm:flex-row justify-between items-center gap-2">
            <!-- Copyright -->
            <p class="text-xs text-gray-500 text-center sm:text-left">
              © {{ new Date().getFullYear() }} LogiSys. All rights reserved.
            </p>
            
            <!-- Version/Status -->
            <p class="text-xs text-gray-400 text-center sm:text-right">
              Employee Portal v1.0 • 
              <span class="text-green-600 font-medium">Online</span>
            </p>
          </div>
        </footer>
      </v-main>
    </v-layout>
  </v-app>
</template>

<style scoped>
.v-application {
  height: 100vh;
}
.v-layout {
  height: 100%;
}

/* Ensure v-main takes full height and doesn't overflow */
.v-main {
  display: flex;
  flex-direction: column;
}

/* Scrollable content area */
.flex-1 {
  flex: 1;
  min-height: 0;
}

.overflow-y-auto {
  overflow-y: auto;
}

/* Fixed footer */
footer {
  flex-shrink: 0;
}

header {
  border-bottom: 1px solid #e5e7eb;
  flex-shrink: 0;
}

/* Mobile responsiveness */
@media (max-width: 1023px) {
  .v-main {
    margin-left: 0 !important;
    width: 100vw !important;
  }
}
</style>