<template>
  <div class="overflow-hidden rounded-lg border border-gray-200 shadow-sm">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <!-- Add checkbox for select all if selectable -->
            <th v-if="selectable" class="px-4 py-3 w-12">
              <input
                type="checkbox"
                :checked="allSelected"
                @change="toggleSelectAll"
                aria-label="Select all"
              />
            </th>
            <th
              v-for="column in columns"
              :key="column.field"
              scope="col"
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              :class="{ 
                'cursor-pointer hover:bg-gray-100': column.sortable,
                'text-right': column.field === 'actions'
              }"
              @click="column.sortable ? handleSortClick(column.field) : null"
            >
              <div class="flex items-center" :class="{ 'justify-end': column.field === 'actions' }">
                {{ column.header }}
                <span v-if="sortField === column.field" class="ml-1">
                  <ArrowUpIcon
                    v-if="sortDirection === 'asc'"
                    class="h-4 w-4 text-gray-400"
                  />
                  <ArrowDownIcon
                    v-else
                    class="h-4 w-4 text-gray-400"
                  />
                </span>
              </div>
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr
            v-for="(row, index) in sortedData"
            :key="index"
            class="hover:bg-gray-50 transition-colors duration-100"
            :class="{ 'bg-blue-50': selectable && isSelected(row) }"
            @click="selectable ? toggleRow(row) : undefined"
            :style="{ cursor: selectable ? 'pointer' : 'default' }"
          >
            <!-- Row checkbox if selectable -->
            <td v-if="selectable" class="px-4 py-4 w-12">
              <input
                type="checkbox"
                :checked="isSelected(row)"
                @change.stop="toggleRow(row)"
                aria-label="Select row"
              />
            </td>
            <td
              v-for="column in columns"
              :key="column.field"
              class="px-6 py-4 text-sm"
              :class="{
                'whitespace-nowrap': column.field !== 'warehouse_address',
                'text-gray-900': !column.field.endsWith('_at'),
                'text-gray-500': column.field.endsWith('_at'),
                'text-right': column.field === 'actions'
              }"
            >
              <template v-if="column.field === 'actions'">
                <div class="flex space-x-2 justify-end">
                  <slot name="actions" :row="row"></slot>
                </div>
              </template>
              <template v-else-if="column.field === 'status' && column.formatter">
                <span :class="column.formatter(row[column.field]).class">
                  {{ column.formatter(row[column.field]).text }}
                </span>
              </template>
              <template v-else>
                <slot :name="column.field" :row="row">
                  {{ column.formatter ? column.formatter(row[column.field]) : row[column.field] }}
                </slot>
              </template>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty state fallback slot -->
    <div v-if="sortedData.length === 0" class="px-6 py-12 text-center text-gray-500 bg-white">
      <slot name="empty">No data available</slot>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { ArrowUpIcon, ArrowDownIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
  columns: {
    type: Array,
    required: true,
    validator: (columns) =>
      columns.every((column) => 'field' in column && 'header' in column),
  },
  data: {
    type: Array,
    required: true,
  },
  selectable: {
    type: Boolean,
    default: false,
  },
  sortField: {
    type: String,
    default: ''
  },
  sortDirection: {
    type: String,
    default: 'asc'
  }
});

const emit = defineEmits(['selection-change', 'sort']);

// Use provided sort values from parent
const internalSortField = ref(props.sortField);
const internalSortDirection = ref(props.sortDirection);

// Sort data based on current sort field/direction
const sortedData = computed(() => {
  if (!internalSortField.value) return props.data;

  return [...props.data].sort((a, b) => {
    let aValue = a[internalSortField.value];
    let bValue = b[internalSortField.value];
    
    // Handle null/undefined values
    if (aValue == null) aValue = '';
    if (bValue == null) bValue = '';
    
    // Convert to string for case-insensitive comparison
    aValue = String(aValue).toLowerCase();
    bValue = String(bValue).toLowerCase();

    let modifier = internalSortDirection.value === 'desc' ? -1 : 1;

    if (aValue < bValue) return -1 * modifier;
    if (aValue > bValue) return 1 * modifier;
    return 0;
  });
});

// Handle sort click - emit to parent and update internal state
const handleSortClick = (field) => {
  let newDirection = 'asc';
  
  if (internalSortField.value === field) {
    // Toggle direction if clicking the same column
    newDirection = internalSortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    // Default to ascending when clicking a new column
    newDirection = 'asc';
  }
  
  // Update internal state
  internalSortField.value = field;
  internalSortDirection.value = newDirection;
  
  // Emit to parent component
  emit('sort', { field, direction: newDirection });
};

// Selection logic
const selectedRows = ref([]);

const isSelected = (row) => {
  return selectedRows.value.includes(row);
};

const toggleRow = (row) => {
  const idx = selectedRows.value.indexOf(row);
  if (idx === -1) {
    selectedRows.value.push(row);
  } else {
    selectedRows.value.splice(idx, 1);
  }
  emit('selection-change', [...selectedRows.value]);
};

const allSelected = computed(() => {
  return sortedData.value.length > 0 && selectedRows.value.length === sortedData.value.length;
});

const toggleSelectAll = () => {
  if (allSelected.value) {
    selectedRows.value = [];
  } else {
    selectedRows.value = [...sortedData.value];
  }
  emit('selection-change', [...selectedRows.value]);
};

// Reset selection if data changes
watch(
  () => props.data,
  () => {
    selectedRows.value = [];
    emit('selection-change', []);
  }
);

// Watch for external sort changes from parent
watch(
  () => props.sortField,
  (newVal) => {
    if (newVal !== internalSortField.value) {
      internalSortField.value = newVal;
    }
  }
);

watch(
  () => props.sortDirection,
  (newVal) => {
    if (newVal !== internalSortDirection.value) {
      internalSortDirection.value = newVal;
    }
  }
);
</script>