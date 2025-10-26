<template>
  <!-- Desktop Sidebar (Persistent) -->
  <div
    v-if="!isMobile"
    :class="[
      'bg-gray-900 text-white flex flex-col transition-all duration-300 h-screen',
      isSidebarOpen ? 'w-64' : 'w-20',
    ]"
  >
    <!-- Logo (Clickable to Toggle Sidebar) -->
    <div
      class="p-4 flex items-center space-x-2 border-b border-gray-700 cursor-pointer"
      @click="toggleSidebar"
    >
      <ApplicationLogo :class="isSidebarOpen ? 'w-12 h-11' : 'w-12 h-11'" />
      <div v-if="isSidebarOpen" class="flex items-center space-x-2">
        <h1 class="text-lg font-bold">{{ capitalizedRole }}</h1>
        <p class="text-lg font-bold text-gray-400"></p>
      </div>
    </div>

    <!-- User Info Section -->
    <div v-if="authUser" class="p-4 border-b border-gray-700">
      <p v-if="isSidebarOpen" class="text-base font-semibold">{{ authUser.name }}</p>
      <p v-if="isSidebarOpen" class="text-sm text-gray-100">{{ authUser.email }}</p>
      <NavLink
        :href="route('profile.edit')"
        class="flex items-center p-3 rounded hover:bg-green-600 hover:text-white mt-3 w-full"
        :class="{ 'bg-green-600 text-white': route().current('profile.edit') }"
      >
        <v-icon :size="isSidebarOpen ? '24px' : '20px'">mdi-account</v-icon>
        <span v-if="isSidebarOpen" class="ml-4">My Profile</span>
      </NavLink>
    </div>

    <!-- Navigation Links (Role-Specific & Stacked) -->
    <nav class="flex-1 p-4 space-y-4 flex flex-col overflow-y-auto">
     <NavLink
  v-for="link in filteredLinks"
  :key="link.name"
  :href="link.href"
  :active="false"
  class="flex items-center p-3 rounded hover:bg-green-600 hover:text-white w-full"
  :class="{ 'bg-green-600 text-white': route().current(link.route) }"
>
        <v-icon>{{ link.icon }}</v-icon>
        <span v-if="isSidebarOpen" class="ml-4">{{ link.name }}</span>
      </NavLink>
    </nav>

    <!-- Logout Button -->
    <div class="p-4 border-t border-gray-700">
      <DangerButton @click="logout" class="w-full">
        <v-icon>mdi-logout</v-icon>
        <span v-if="isSidebarOpen" class="ml-2">Logout</span>
      </DangerButton>
    </div>
  </div>

  <!-- Mobile Sidebar (Overlay) -->
  <div v-else>
    <!-- Mobile Sidebar Content -->
    <div
      :class="[
        'bg-gray-900 text-white flex flex-col h-screen fixed left-0 top-0 z-50 transform transition-transform duration-300',
        isMobileOpen ? 'translate-x-0 w-64' : '-translate-x-full w-64'
      ]"
    >
      <!-- Mobile Header -->
      <div class="p-4 border-b border-gray-700 flex items-center justify-between">
        <div class="flex items-center space-x-2">
          <ApplicationLogo class="w-12 h-11" />
          <div class="flex items-center space-x-2">
            <h1 class="text-lg font-bold">{{ capitalizedRole }}</h1>
          </div>
        </div>
        <button 
          @click="closeMobileSidebar"
          class="p-2 text-gray-400 hover:text-white"
        >
          <v-icon>mdi-close</v-icon>
        </button>
      </div>

      <!-- User Info Section -->
      <div v-if="authUser" class="p-4 border-b border-gray-700">
        <p class="text-base font-semibold">{{ authUser.name }}</p>
        <p class="text-sm text-gray-100">{{ authUser.email }}</p>
        <NavLink
          :href="route('profile.edit')"
          class="flex items-center p-3 rounded hover:bg-green-600 hover:text-white mt-3 w-full"
          :class="{ 'bg-green-600 text-white': route().current('profile.edit') }"
          @click="closeMobileSidebar"
        >
          <v-icon size="24px">mdi-account</v-icon>
          <span class="ml-4">My Profile</span>
        </NavLink>
      </div>

      <!-- Navigation Links -->
      <nav class="flex-1 p-4 space-y-4 flex flex-col overflow-y-auto">
        <NavLink
          v-for="link in filteredLinks"
          :key="link.name"
          :href="link.href"
          :active="route().current(link.route)"
          class="flex items-center p-3 rounded hover:bg-green-600 hover:text-white w-full"
          :class="{ 'bg-green-600 text-white': route().current(link.route) }"
          @click="closeMobileSidebar"
        >
          <v-icon>{{ link.icon }}</v-icon>
          <span class="ml-4">{{ link.name }}</span>
        </NavLink>
      </nav>

      <!-- Logout Button -->
      <div class="p-4 border-t border-gray-700">
        <DangerButton @click="logout" class="w-full">
          <v-icon>mdi-logout</v-icon>
          <span class="ml-2">Logout</span>
        </DangerButton>
      </div>
    </div>

    <!-- Mobile Menu Button - Top Right -->
    <button
      v-if="!isMobileOpen"
      @click="openMobileSidebar"
      class="fixed top-4 right-4 z-40 p-3 bg-gray-900 text-white rounded-lg shadow-lg hover:bg-green-600 transition-colors duration-200"
    >
      <v-icon>mdi-menu</v-icon>
    </button>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import NavLink from '@/Components/NavLink.vue';
import DangerButton from '@/Components/DangerButton.vue';

const { props } = usePage();

// Define emits
const emit = defineEmits(['sidebar-toggle']);

// Sidebar State
const isSidebarOpen = ref(true);
const isMobileOpen = ref(false);
const isMobile = ref(false);

// Check if mobile
const checkMobile = () => {
  isMobile.value = window.innerWidth < 1024;
};

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value;
  localStorage.setItem('sidebarOpen', isSidebarOpen.value);
  emit('sidebar-toggle', isSidebarOpen.value);
};

const openMobileSidebar = () => {
  isMobileOpen.value = true;
};

const closeMobileSidebar = () => {
  isMobileOpen.value = false;
};

// Handle window resize
const handleResize = () => {
  checkMobile();
  if (!isMobile.value) {
    isMobileOpen.value = false;
  }
};

onMounted(() => {
  const savedState = localStorage.getItem('sidebarOpen');
  isSidebarOpen.value = savedState === null || savedState === 'true';
  emit('sidebar-toggle', isSidebarOpen.value);

  checkMobile();
  window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
  window.removeEventListener('resize', handleResize);
});

// User Info
const authUser = props.auth?.user;

// User Role
const role = authUser?.role ?? 'guest';

// Capitalized Role for Display
const capitalizedRole = computed(() => {
  return role.charAt(0).toUpperCase() + role.slice(1);
});

// Navigation Links by Role
const navLinks = {
  admin: [
    // Core Workflow (in your specified order)
    { name: 'Pending Requests', href: '/deliveries/pending', route: 'deliveries.pending', icon: 'mdi-clock-alert' },
    { name: 'Payments', href: '/staff/payments', route: 'staff.payments.dashboard', icon: 'mdi-cash-multiple' },
    { name: 'Refunds', href: '/refunds', route: 'refunds.index', icon: 'mdi-cash-refund' },
    { name: 'Manifest', href: '/admin/manifests', route: 'manifests.index', icon: 'mdi-file-tree' },
    { name: 'Waybills', href: '/waybills', route: 'waybills.index', icon: 'mdi-file-document' },
    { name: 'Driver-Truck Sets', href: '/driver-truck-assignments', route: 'driver-truck-assignments.index', icon: 'mdi-account-switch' },
    
    // Management Sections
    { name: 'Employees', href: '/admin/employees', route: 'admin.employees', icon: 'mdi-account-group' },
    { name: 'Customers', href: '/admin/customers', route: 'admin.customers', icon: 'mdi-account-box' },
    { name: 'Profile Requests', href: '/admin/customer-update-requests', route: 'admin.customer-update-requests.index', icon: 'mdi-account-edit' },
    { name: 'Regions', href: '/admin/regions', route: 'admin.regions.index', icon: 'mdi-map-marker-multiple' },
    { name: 'Trucks', href: '/admin/trucks', route: 'admin.trucks', icon: 'mdi-truck' },
    { name: 'Package Management', href: '/admin/packages', route: 'admin.packages.index', icon: 'mdi-package' },
    
    // The rest
    { name: 'Reports', href: '/admin/reports', route: 'reports.dashboard', icon: 'mdi-chart-bar' },
    { name: 'Utilities', href: '/admin/utilities', route: 'admin.utilities.index', icon: 'mdi-tools' },
  ],

  staff: [
    // Core Workflow (in your specified order)
    { name: 'Pending Requests', href: '/deliveries/pending', route: 'deliveries.pending', icon: 'mdi-clock-alert' },
    { name: 'Payments', href: '/staff/payments', route: 'staff.payments.index', icon: 'mdi-cash-multiple' },
    { name: 'Waybills', href: '/waybills', route: 'waybills.index', icon: 'mdi-file-document' },
    { name: 'Stickers', href: '/stickers', route: 'stickers.index', icon: 'mdi-label' },
    { name: 'Manifest', href: '/admin/manifests', route: 'manifests.index', icon: 'mdi-file-tree' },
    { name: 'Driver-Truck Sets', href: '/driver-truck-assignments', route: 'driver-truck-assignments.index', icon: 'mdi-account-switch' },
    { name: 'Cargo Assignment', href: '/cargo-assignments', route: 'cargo-assignments.index', icon: 'mdi-package-variant' },
    { name: 'Release Package', href: '/cargo-assignments/delivery-completion/ready-for-completion', route: 'cargo-assignments.delivery-completion.ready-for-completion', icon: 'mdi-package-up' },
    
    // The rest
    { name: 'Package Management', href: '/admin/packages', route: 'admin.packages.index', icon: 'mdi-package' },
    { name: 'Trucks', href: '/admin/trucks', route: 'admin.trucks', icon: 'mdi-truck' },
  ],

  driver: [
    { name: 'Dashboard', href: '/driver/dashboard', route: 'driver.dashboard', icon: 'mdi-home' },
    { name: 'Assigned Deliveries', href: '/driver/deliveries/assigned', route: 'driver.assigned-deliveries', icon: 'mdi-package-variant' },
    { name: 'Update Location', href: '/driver/packages/status-update', route: 'driver.status-update', icon: 'mdi-map-marker' },
  ],

  collector: [
    { name: 'Collection Dashboard', href: '/collector/payments/dashboard', route: 'collector.payments.dashboard', icon: 'mdi-view-dashboard' },
    { name: 'Pending Collections', href: '/collector/payments/pending', route: 'collector.payments.pending', icon: 'mdi-clock-alert' },
    { name: 'My Collections', href: '/collector/payments', route: 'collector.payments.index', icon: 'mdi-cash' },
  
  ],

  guest: [],
};

// Filter Links by User Role
const filteredLinks = navLinks[role] || [];

// Logout Function
const logout = () => {
  router.post('/logout');
};
</script>

<style scoped>
/* Smooth Transition for Sidebar */
div {
  transition: all 0.3s ease;
}

/* Ensure the sidebar doesn't overflow */
.overflow-y-auto {
  overflow-y: auto;
}

/* Adjust padding for collapsed state */
.w-20 .p-4 {
  padding: 0.9rem;
}

/* Ensure the logo and text are aligned properly */
.flex.items-center.space-x-2 {
  display: flex;
  align-items: center;
  gap: 0.8rem;
}
</style>