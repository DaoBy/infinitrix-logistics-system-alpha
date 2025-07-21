<script setup>
import { computed } from 'vue';  // <-- Add this line!

const props = defineProps({
  label: {
    type: String,
    required: true,
  },
  status: {
    type: String,
    required: true,
  },
});

const badgeColorClass = computed(() => {
  switch (props.status.toLowerCase()) {
    case 'pending':
      return 'bg-yellow-100 text-yellow-800';
    case 'approved':
    case 'completed':
      return 'bg-green-100 text-green-800';
    case 'cancelled':
    case 'rejected':
      return 'bg-red-100 text-red-800';
    case 'in-transit':
      return 'bg-blue-100 text-blue-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
});
</script>
