<template>
  <div 
    v-if="visible"
    class="fixed bottom-4 right-4 z-[99999] max-w-[480px] max-h-[60vh] overflow-auto bg-gray-800 text-white p-3 rounded-lg shadow-xl text-xs"
  >
    <div class="flex justify-between items-center mb-2">
      <strong>Manifest Debug Log</strong>
      <button 
        @click="close" 
        class="text-white hover:text-gray-300 text-lg cursor-pointer"
      >
        ×
      </button>
    </div>
    <pre class="whitespace-pre-wrap overflow-auto">{{ JSON.stringify(debugLog, null, 2) }}</pre>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
const props = defineProps({
  debugLog: {
    type: Array,
    default: () => []
  }
});
const visible = ref(true);
watch(() => props.debugLog, () => { visible.value = true; });
function close() { visible.value = false; }
</script>
