<template>
  <div v-if="pagination && pagination.last_page > 1" class="flex flex-col items-center justify-center border-t border-gray-200 px-4 py-6">
    <!-- Mobile -->
    <div class="flex flex-1 justify-between sm:hidden w-full">
      <button
        @click="changePage(prevPage)"
        :disabled="!hasPrev"
        class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-green-50 focus:ring-2 focus:ring-green-500 transition disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Prev
      </button>
      <button
        @click="changePage(nextPage)"
        :disabled="!hasNext"
        class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-green-50 focus:ring-2 focus:ring-green-500 transition disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Next
      </button>
    </div>
    
    <!-- Desktop -->
    <div class="hidden sm:flex sm:flex-col sm:items-center sm:justify-center w-full">
      <div class="mb-4">
        <p class="text-sm text-gray-600">
          Showing <span class="font-medium text-gray-900">{{ pagination.from || 0 }}</span> to 
          <span class="font-medium text-gray-900">{{ pagination.to || 0 }}</span> of
          <span class="font-medium text-gray-900">{{ pagination.total }}</span> results
        </p>
      </div>
      
      <div class="flex items-center space-x-6">
        <!-- Prev Button -->
        <button
          @click="changePage(prevPage)"
          :disabled="!hasPrev"
          class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-green-50 hover:border-green-300 transition-colors disabled:opacity-50 disabled:cursor-not-allowed min-w-[80px] justify-center"
        >
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          Prev
        </button>

        <!-- Page Numbers -->
        <div class="flex items-center space-x-1">
          <button
            v-for="page in visiblePages"
            :key="page"
            @click="changePage(page)"
            :disabled="page === pagination.current_page"
            class="inline-flex items-center justify-center w-10 h-10 text-sm font-medium border transition-colors"
            :class="{
              'bg-green-600 text-white border-green-600': page === pagination.current_page,
              'bg-white text-gray-700 border-gray-300 hover:bg-green-50 hover:border-green-300': page !== pagination.current_page,
              'opacity-50 cursor-not-allowed': page === pagination.current_page
            }"
          >
            {{ page }}
          </button>
          
          <span v-if="showEllipsis" class="px-2 text-gray-500">...</span>
        </div>

        <!-- Next Button -->
        <button
          @click="changePage(nextPage)"
          :disabled="!hasNext"
          class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-green-50 hover:border-green-300 transition-colors disabled:opacity-50 disabled:cursor-not-allowed min-w-[80px] justify-center"
        >
          Next
          <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    pagination: {
      type: Object,
      required: true,
      default: () => ({
        current_page: 1,
        last_page: 1,
        total: 0,
        from: 0,
        to: 0,
      }),
    },
  },
  emits: ['page-changed'],
  computed: {
    hasPrev() {
      return this.pagination.current_page > 1;
    },
    hasNext() {
      return this.pagination.current_page < this.pagination.last_page;
    },
    prevPage() {
      return this.hasPrev ? this.pagination.current_page - 1 : null;
    },
    nextPage() {
      return this.hasNext ? this.pagination.current_page + 1 : null;
    },
    visiblePages() {
      const current = this.pagination.current_page;
      const last = this.pagination.last_page;
      const delta = 2; // Number of pages to show on each side of current page
      const range = [];
      
      for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
        range.push(i);
      }
      
      if (current - delta > 2) {
        range.unshift('...');
      }
      if (current + delta < last - 1) {
        range.push('...');
      }
      
      range.unshift(1);
      if (last > 1) {
        range.push(last);
      }
      
      return range;
    },
    showEllipsis() {
      return this.pagination.last_page > 7;
    }
  },
  methods: {
    changePage(page) {
      if (page && page !== '...' && page !== this.pagination.current_page && page >= 1 && page <= this.pagination.last_page) {
        this.$emit('page-changed', page);
      }
    },
  },
};
</script>

<style scoped>
/* Custom green color scheme */
:deep(.bg-green-600) {
  background-color: #16a250;
}
:deep(.border-green-600) {
  border-color: #16a250;
}
:deep(.hover\\:bg-green-50:hover) {
  background-color: #f0f9f4;
}
:deep(.hover\\:border-green-300:hover) {
  border-color: #86efac;
}
:deep(.focus\\:ring-green-500:focus) {
  ring-color: #16a250;
}
</style>