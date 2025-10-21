[file name]: Index.vue
[file content begin]
<template>
  <GuestLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">Notifications</h2>
          <p class="mt-1 text-sm text-gray-500">
            Manage and review your notification history
          </p>
        </div>

        <!-- Right: Buttons -->
        <div class="flex gap-2">
          <PrimaryButton 
            @click="markAllAsRead" 
            :disabled="unreadCount === 0"
            class="!px-4 !py-2 bg-green-600 hover:bg-green-700 focus:ring-green-500"
          >
            Mark All as Read
          </PrimaryButton>
        </div>
      </div>
    </template>

    <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
      <!-- Stats -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 border border-gray-200">
          <div class="flex items-center">
            <div class="rounded-full bg-green-100 p-3">
              <BellIcon class="h-6 w-6 text-green-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Total Notifications</p>
              <p class="text-2xl font-bold text-gray-900">{{ notifications.length }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4 border border-gray-200">
          <div class="flex items-center">
            <div class="rounded-full bg-yellow-100 p-3">
              <EnvelopeOpenIcon class="h-6 w-6 text-yellow-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Unread</p>
              <p class="text-2xl font-bold text-gray-900">{{ unreadCount }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4 border border-gray-200">
          <div class="flex items-center">
            <div class="rounded-full bg-blue-100 p-3">
              <CheckCircleIcon class="h-6 w-6 text-blue-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">Read</p>
              <p class="text-2xl font-bold text-gray-900">{{ readCount }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Notifications List -->
      <div class="bg-white shadow rounded-lg border border-gray-200">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
          <h3 class="text-lg font-medium text-gray-900">Your Notifications</h3>
        </div>

        <div v-if="displayedNotifications.length > 0" class="divide-y divide-gray-200">
          <div 
            v-for="notification in displayedNotifications" 
            :key="notification.id"
            class="p-4 hover:bg-gray-50 transition-colors"
            :class="{ 'bg-green-50 border-l-4 border-l-green-400': !notification.read }"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <div class="flex items-center gap-3 mb-1">
                  <div 
                    class="w-2 h-2 rounded-full flex-shrink-0 mt-1"
                    :class="getNotificationColor(notification.type)"
                  ></div>
                  <h4 class="text-sm font-medium text-gray-900">{{ notification.title }}</h4>
                  <span 
                    v-if="!notification.read" 
                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800"
                  >
                    New
                  </span>
                </div>
                <p class="text-sm text-gray-600 mb-2 leading-relaxed">{{ notification.message }}</p>
                <div class="flex items-center justify-between text-xs text-gray-500">
                  <span>{{ formatDate(notification.created_at) }}</span>
             
                </div>
              </div>
              <div class="ml-4 flex gap-2">
                <button
                  v-if="!notification.read"
                  @click="markAsRead(notification.id)"
                  class="text-green-600 hover:text-green-800 text-xs font-medium px-2 py-1 rounded hover:bg-green-50 transition-colors"
                >
                  Mark Read
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12">
          <BellIcon class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">No notifications</h3>
          <p class="mt-1 text-sm text-gray-500">You're all caught up!</p>
        </div>
      </div>

      <!-- Load More Button -->
      <div v-if="showLoadMore" class="mt-6 text-center">
        <PrimaryButton 
          @click="loadMore" 
          :disabled="loading"
          class="bg-green-600 hover:bg-green-700 focus:ring-green-500"
        >
          <span v-if="loading">Loading...</span>
          <span v-else>Load More</span>
        </PrimaryButton>
      </div>
    </div>
  </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { 
  BellIcon,
  EnvelopeOpenIcon,
  CheckCircleIcon 
} from '@heroicons/vue/24/outline';

const props = defineProps({
  notifications: {
    type: Array,
    default: () => []
  }
});

// Reactive variables
const loading = ref(false);
const displayedCount = ref(10);

// Computed properties
const unreadCount = computed(() => {
  return props.notifications.filter(n => !n.read).length;
});

const readCount = computed(() => {
  return props.notifications.filter(n => n.read).length;
});

const displayedNotifications = computed(() => {
  return props.notifications.slice(0, displayedCount.value);
});

const showLoadMore = computed(() => {
  return props.notifications.length > displayedCount.value;
});

function getNotificationColor(type) {
  const colors = {
    payment: 'bg-green-500',
    payment_issue: 'bg-red-500',
    delivery: 'bg-blue-500',
    info: 'bg-gray-500',
    warning: 'bg-yellow-500',
    success: 'bg-green-500',
    approval: 'bg-green-500',
    denial: 'bg-red-500'
  };
  return colors[type] || 'bg-gray-500';
}

function formatNotificationType(type) {
  const typeMap = {
    payment: 'Payment',
    payment_issue: 'Payment Issue',
    delivery: 'Delivery',
    info: 'Information',
    warning: 'Warning',
    success: 'Success',
    approval: 'Approval',
    denial: 'Denial'
  };
  return typeMap[type] || type;
}

function markAsRead(notificationId) {
  router.post(route('notifications.mark-as-read', notificationId), {
    preserveScroll: true,
    onSuccess: () => {
      // Optional: Show success message
    }
  });
}

function markAllAsRead() {
  router.post(route('notifications.mark-all-read'), {
    preserveScroll: true,
    onSuccess: () => {
      // Optional: Show success message
    }
  });
}

function loadMore() {
  loading.value = true;
  // Simulate loading delay
  setTimeout(() => {
    displayedCount.value += 10;
    loading.value = false;
  }, 300);
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}
</script>
[file content end]