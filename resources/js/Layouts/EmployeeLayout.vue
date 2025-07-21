<script setup>
import ESidebar from '@/Components/ESidebar.vue';
import { usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const page = usePage();

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

      <!-- Main Content (margin-left matches sidebar width) -->
      <v-main
        class="flex-1 flex flex-col"
        :style="{
          overflowY: 'auto',
          marginLeft: sidebarWidth + 'px',
          transition: 'margin-left 0.3s cubic-bezier(0.4,0,0.2,1)'
        }"
      >
        <!-- Header Slot -->
        <header v-if="$slots.header" class="bg-white shadow-sm">
          <v-container fluid class="px-6 py-4">
            <slot name="header" />
          </v-container>
        </header>

        <!-- Main Content Area -->
        <v-container fluid class="flex-1 w-full pa-6">
          <slot />
        </v-container>

        <!-- Simplified Footer -->
        <v-footer class="bg-white border-t mt-4">
          <v-container>
            <v-row justify="center">
              <v-col cols="12" class="text-center">
                <p class="text-sm text-gray-600">
                  © {{ new Date().getFullYear() }} LogiSys. All rights reserved.
                </p>
                <p class="text-sm text-gray-600 mt-2">
                  Employee Portal - Designed for efficient logistics management.
                </p>
              </v-col>
            </v-row>
          </v-container>
        </v-footer>
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
.v-main {
  overflow-y: auto;
}
header {
  border-bottom: 1px solid #e5e7eb;
}
</style>