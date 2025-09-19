<template>
  <table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
      <tr>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receiver</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pickup Region</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Drop-off Region</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Packages</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Price</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
      </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
      <tr v-for="request in requests.data" :key="request.id">
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ request.id }}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
          {{ request.receiver?.name || 'N/A' }}
          <span v-if="request.receiver?.company_name" class="text-xs text-gray-500 block">
            {{ request.receiver.company_name }}
          </span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ request.pick_up_region?.name || 'N/A' }}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ request.drop_off_region?.name || 'N/A' }}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ request.package_count }}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₱{{ parseFloat(request.total_price).toFixed(2) }}</td>
        <td class="px-6 py-4 whitespace-nowrap">
          <span :class="`inline-flex px-3 py-1 text-xs font-semibold leading-5 rounded-full ${statusClass(request.status)}`">
            {{ formatStatus(request.status) }}
          </span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(request.created_at) }}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
          <div class="flex space-x-2">
            <button @click="$emit('view', request.id)" class="text-indigo-600 hover:text-indigo-900 px-2 py-1 rounded-md hover:bg-indigo-50 transition-colors text-xs">
              View
            </button>
            <button v-if="request.status === 'draft'" @click="$emit('edit', request.id)" class="text-green-600 hover:text-green-900 px-2 py-1 rounded-md hover:bg-green-50 transition-colors text-xs">
              Edit
            </button>
          </div>
        </td>
      </tr>
      <tr v-if="requests.data.length === 0">
        <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500">No delivery requests found.</td>
      </tr>
    </tbody>
  </table>
</template>

<script setup>
defineProps({
  requests: Object
});

defineEmits(['view', 'edit']);

const statusClass = (status) => {
  switch(status) {
    case 'pending':
      return 'bg-yellow-100 text-yellow-800';
    case 'draft':
      return 'bg-gray-100 text-gray-800';
    case 'approved':
      return 'bg-blue-100 text-blue-800';
    case 'completed':
      return 'bg-green-100 text-green-800';
    case 'rejected':
      return 'bg-red-100 text-red-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
};

const formatStatus = (status) => {
  if (!status || typeof status !== 'string') return '';
  return status.charAt(0).toUpperCase() + status.slice(1);
};

const formatDate = (dateString) => {
  if (!dateString) return '—';
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return '—';
  return date.toLocaleDateString();
};
</script>