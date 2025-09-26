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
      <!-- Sidebar (fixed in place, does not overlay content) -->
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
      >
        <ESidebar @sidebar-toggle="onSidebarToggle" />
      </div>

      <!-- Main Content Area - Using v-main but with custom structure inside -->
      <v-main
        class="flex flex-col"
        :style="{
          marginLeft: sidebarWidth + 'px',
          height: '100vh',
          overflow: 'hidden', // Prevent main from scrolling
          transition: 'margin-left 0.3s cubic-bezier(0.4,0,0.2,1)'
        }"
      >
        <!-- Header Slot -->
        <header v-if="$slots.header" class="bg-white shadow-sm shrink-0">
          <v-container fluid class="px-6 py-3"> <!-- Reduced py-4 to py-3 -->
            <slot name="header" />
          </v-container>
        </header>

        <!-- Scrollable Main Content -->
        <div class="flex-1 overflow-y-auto">
          <v-container fluid class="w-full pa-4"> <!-- Reduced pa-6 to pa-4 -->
            <slot />
          </v-container>
        </div>

        <!-- Fixed Footer -->
        <footer class="bg-white border-t border-gray-200 py-2 px-6 shrink-0"> <!-- Reduced py-3 to py-2 -->
          <div class="w-full flex flex-col sm:flex-row justify-between items-center gap-1"> <!-- Reduced gap-2 to gap-1 -->
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

/* Sidebar fixed and does not overlay content */
.v-layout > div:first-child {
  position: fixed;
  left: 0;
  top: 0;
  height: 100vh;
  background: inherit;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  pointer-events: auto;
}

/* Ensure v-main takes full height and doesn't overflow */
.v-main {
  display: flex;
  flex-direction: column;
}

/* Scrollable content area */
.flex-1 {
  flex: 1;
  min-height: 0; /* Important for flexbox scrolling */
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

/* Ensure containers don't cause horizontal overflow */
.v-container {
  max-width: 100%;
}
</style>