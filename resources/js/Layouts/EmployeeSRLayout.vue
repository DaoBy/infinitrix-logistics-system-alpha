<script setup>
import { usePage } from '@inertiajs/vue3';

// Access current page info (for future dynamic use)
const page = usePage();
</script>

<template>
  <v-app class="employee-layout">
    <!-- Main Content -->
    <v-main class="main-content">
      <v-container fluid class="content-container">
        <slot />
      </v-container>
    </v-main>

    <!-- Footer -->
    <v-footer app class="footer">
      <v-container class="text-center">
        <p>© {{ new Date().getFullYear() }} Infinitrix Express Cargo. All rights reserved.</p>
      </v-container>
    </v-footer>
  </v-app>
</template>

<style scoped>
/* Force full-page layout without overflow */
.employee-layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh; /* Full viewport height */
  overflow: hidden; /* Prevent sneaky scrolling */
}

/* Ensure main content takes remaining space */
.main-content {
  flex: 1; /* Fills space between header and footer */
  display: flex;
  flex-direction: column;
  overflow-y: auto; /* Allow scrolling within main content */
}

/* Main content styling */
.content-container {
  flex: 1; /* Ensures content fills available space */
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 1rem;
}

/* Footer styling */
.footer {
  background-color: #f8f9fa;
  color: #555;
  font-size: 0.9rem;
  flex-shrink: 0; /* Prevent footer from shrinking */
}
</style>