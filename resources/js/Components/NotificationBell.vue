[file name]: NotificationBell.vue
[file content begin]
<template>
  <div class="relative">
    <button 
      @click="toggleDropdown"
      class="relative p-2 text-gray-600 rounded-full transition-all duration-200 hover:ring-2 hover:ring-green-500 hover:ring-opacity-50 focus:outline-none focus:ring-2 focus:ring-green-500"
    >
      <BellIcon class="h-6 w-6" />
      <span 
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-green-600 text-xs font-medium text-white ring-2 ring-white"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown -->
    <div 
      v-if="dropdownOpen" 
      class="bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50"
      :class="{
        'absolute right-0 mt-2 w-80': !isMobileView,
        'fixed top-20 left-4 right-4 mx-auto max-w-md': isMobileView
      }"
    >
      <!-- Header -->
      <div class="p-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900">Notifications</h3>
          <button 
            @click="markAllAsRead"
            class="text-sm text-green-600 hover:text-green-800 font-medium"
            :disabled="unreadCount === 0"
            :class="{ 'opacity-50 cursor-not-allowed': unreadCount === 0 }"
          >
            Mark all read
          </button>
        </div>
      </div>

      <!-- Notifications List -->
      <div class="max-h-96 overflow-y-auto">
        <div v-if="notifications.length > 0" class="divide-y divide-gray-100">
          <div 
            v-for="notification in notifications" 
            :key="notification.id"
            class="p-4 hover:bg-gray-50 cursor-pointer"
            :class="{ 'bg-green-50': !notification.read }"
            @click="markAsRead(notification)"
          >
            <div class="flex items-start">
              <div class="flex-shrink-0 pt-0.5 mr-3">
                <div class="h-10 w-10 rounded-full flex items-center justify-center" 
                     :class="getNotificationIconClass(notification.type)">
                  <component :is="getNotificationIcon(notification.type)" class="h-5 w-5" />
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between">
                  <p class="text-sm font-medium text-gray-900 truncate pr-2">
                    {{ notification.title }}
                  </p>
                  <span 
                    v-if="!notification.read" 
                    class="flex-shrink-0 h-2 w-2 rounded-full bg-green-500 mt-1"
                  ></span>
                </div>
                <p class="text-sm text-gray-600 mt-1 line-clamp-2">
                  {{ notification.message }}
                </p>
                <p class="text-xs text-gray-500 mt-2">
                  {{ formatTime(notification.created_at) }}
                </p>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="p-8 text-center text-gray-500">
          <BellIcon class="mx-auto h-8 w-8 text-gray-400 mb-2" />
          <p class="text-sm">No notifications</p>
          <p class="text-xs text-gray-400 mt-1">You're all caught up!</p>
        </div>
      </div>

      <!-- Footer -->
      <div v-if="notifications.length > 0" class="p-4 border-t border-gray-200">
        <Link 
          :href="route('notifications.index')"
          class="block text-center text-sm font-medium text-green-600 hover:text-green-800"
        >
          View all notifications
        </Link>
      </div>

      <!-- Mobile Close Button -->
      <div v-if="isMobileView" class="p-4 border-t border-gray-200 bg-white">
        <button 
          @click="dropdownOpen = false"
          class="w-full text-center text-sm font-medium text-gray-600 hover:text-gray-800 py-2 rounded-lg border border-gray-300"
        >
          Close
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { 
  BellIcon,
  CheckCircleIcon,
  XCircleIcon,
  InformationCircleIcon,
  ExclamationTriangleIcon,
  TruckIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  notifications: {
    type: Array,
    default: () => []
  }
});

const dropdownOpen = ref(false);
const isMobileView = ref(false);

// Check screen size
const checkScreenSize = () => {
  isMobileView.value = window.innerWidth < 768; // md breakpoint
};

const unreadCount = computed(() => {
  return props.notifications.filter(n => !n.read).length;
});

const notifications = computed(() => {
  return props.notifications.slice(0, 5); // Show last 5 notifications
});

function toggleDropdown() {
  dropdownOpen.value = !dropdownOpen.value;
}

function markAsRead(notification) {
  if (!notification.read) {
    router.post(route('notifications.mark-as-read', notification.id));
  }
  dropdownOpen.value = false;
}

function markAllAsRead() {
  router.post(route('notifications.mark-all-read'), {
    onSuccess: () => {
      dropdownOpen.value = false;
    }
  });
}

function getNotificationIconClass(type) {
  const classes = {
    payment: 'bg-green-100 text-green-600',
    payment_issue: 'bg-red-100 text-red-600',
    delivery: 'bg-blue-100 text-blue-600',
    info: 'bg-gray-100 text-gray-600',
    warning: 'bg-yellow-100 text-yellow-600',
    success: 'bg-green-100 text-green-600',
    approval: 'bg-green-100 text-green-600',
    denial: 'bg-red-100 text-red-600'
  };
  return classes[type] || 'bg-gray-100 text-gray-600';
}

function getNotificationIcon(type) {
  const icons = {
    payment: CheckCircleIcon,
    payment_issue: XCircleIcon,
    delivery: TruckIcon,
    info: InformationCircleIcon,
    warning: ExclamationTriangleIcon,
    success: CheckCircleIcon,
    approval: CheckCircleIcon,
    denial: XCircleIcon
  };
  return icons[type] || InformationCircleIcon;
}

function formatTime(dateString) {
  const now = new Date();
  const date = new Date(dateString);
  const diffInHours = (now - date) / (1000 * 60 * 60);
  
  if (diffInHours < 1) {
    const diffInMinutes = Math.floor(diffInHours * 60);
    return `${diffInMinutes}m ago`;
  } else if (diffInHours < 24) {
    return `${Math.floor(diffInHours)}h ago`;
  } else {
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric'
    });
  }
}

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  const bellComponent = event.target.closest('.relative');
  if (!bellComponent) {
    dropdownOpen.value = false;
  }
};

// Handle escape key
const handleEscapeKey = (event) => {
  if (event.key === 'Escape') {
    dropdownOpen.value = false;
  }
};

onMounted(() => {
  checkScreenSize();
  window.addEventListener('resize', checkScreenSize);
  document.addEventListener('click', handleClickOutside);
  document.addEventListener('keydown', handleEscapeKey);
});

onUnmounted(() => {
  window.removeEventListener('resize', checkScreenSize);
  document.removeEventListener('click', handleClickOutside);
  document.removeEventListener('keydown', handleEscapeKey);
});
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
[file content end]