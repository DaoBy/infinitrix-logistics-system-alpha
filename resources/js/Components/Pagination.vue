<template>
  <div v-if="pagination && pagination.last_page > 1" class="flex flex-col items-center justify-center border-t border-gray-200 px-4 py-3">
    <!-- Mobile -->
    <div class="flex flex-1 justify-between sm:hidden w-full">
      <button
        @click="changePage(prevPage)"
        :disabled="!hasPrev"
        class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-blue-50 focus:ring-2 focus:ring-blue-500 transition"
      >
        Previous
      </button>
      <button
        @click="changePage(nextPage)"
        :disabled="!hasNext"
        class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-blue-50 focus:ring-2 focus:ring-blue-500 transition"
      >
        Next
      </button>
    </div>
    <!-- Desktop -->
    <div class="hidden sm:flex sm:flex-col sm:items-center sm:justify-center w-full">
      <div>
        <p class="text-sm text-gray-700">
          Showing page <span class="font-medium">{{ pagination.current_page }}</span> of
          <span class="font-medium">{{ pagination.last_page }}</span>
        </p>
      </div>
      <div class="mt-2 flex justify-center w-full">
        <nav
          class="isolate inline-flex -space-x-px rounded-md shadow-sm"
          aria-label="Pagination"
        >
          <!-- Previous Arrow -->
          <button
            @click="changePage(prevPage)"
            :disabled="!hasPrev"
            class="relative inline-flex items-center px-2 py-2 text-sm font-semibold ring-1 ring-inset ring-blue-200 rounded-l-md hover:bg-blue-50 focus:z-20 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
            :class="{ 'opacity-50 cursor-not-allowed': !hasPrev, 'bg-white text-blue-600': hasPrev }"
            aria-label="Previous"
          >
            <span aria-hidden="true">&laquo;</span>
          </button>
          <!-- Page Numbers and Ellipsis -->
          <template v-for="(link, index) in pageLinks" :key="index">
            <button
              v-if="isPageNumber(link)"
              @click="changePage(Number(link.label))"
              :disabled="link.active"
              :class="{
                'relative z-10 inline-flex items-center bg-blue-600 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 shadow': link.active,
                'relative inline-flex items-center px-4 py-2 text-sm font-semibold text-blue-700 ring-1 ring-inset ring-blue-200 hover:bg-blue-50 focus:z-20 focus:outline-none focus:ring-2 focus:ring-blue-500 transition bg-white': !link.active,
                'opacity-50 cursor-not-allowed': link.active,
              }"
              v-html="link.label"
            ></button>
            <span
              v-else
              class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-400"
            >...</span>
          </template>
          <!-- Next Arrow -->
          <button
            @click="changePage(nextPage)"
            :disabled="!hasNext"
            class="relative inline-flex items-center px-2 py-2 text-sm font-semibold ring-1 ring-inset ring-blue-200 rounded-r-md hover:bg-blue-50 focus:z-20 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
            :class="{ 'opacity-50 cursor-not-allowed': !hasNext, 'bg-white text-blue-600': hasNext }"
            aria-label="Next"
          >
            <span aria-hidden="true">&raquo;</span>
          </button>
        </nav>
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
        links: [],
      }),
    },
  },
  emits: ['page-changed'],
  computed: {
    // Only page number and ellipsis links (skip first and last for arrows)
    pageLinks() {
      if (!Array.isArray(this.pagination.links)) return [];
      // Laravel paginator: first = prev, last = next, middle = page numbers/ellipsis
      return this.pagination.links.slice(1, this.pagination.links.length - 1);
    },
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
  },
  methods: {
    changePage(page) {
      if (page && page !== this.pagination.current_page && page >= 1 && page <= this.pagination.last_page) {
        this.$emit('page-changed', page);
      }
    },
    isPageNumber(link) {
      return link && !isNaN(Number(link.label));
    },
  },
};
</script>
