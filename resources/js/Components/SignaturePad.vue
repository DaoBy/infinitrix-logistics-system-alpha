<template>
  <div class="border rounded-md p-4 bg-white">
    <canvas 
      ref="canvas"
      class="border w-full h-32 touch-none"
      @mousedown="beginDrawing"
      @mousemove="draw"
      @mouseup="endDrawing"
      @touchstart="beginDrawing"
      @touchmove="draw"
      @touchend="endDrawing"
    ></canvas>
    <div class="flex justify-end mt-2">
      <button 
        type="button" 
        @click="clearSignature"
        class="text-sm text-red-600 hover:text-red-800"
      >
        Clear
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const canvas = ref(null);
const isDrawing = ref(false);
const ctx = ref(null);

onMounted(() => {
  ctx.value = canvas.value.getContext('2d');
  ctx.value.lineWidth = 2;
  ctx.value.lineCap = 'round';
  ctx.value.strokeStyle = '#000';
});

const beginDrawing = (e) => {
  isDrawing.value = true;
  const rect = canvas.value.getBoundingClientRect();
  ctx.value.beginPath();
  ctx.value.moveTo(
    (e.clientX || e.touches[0].clientX) - rect.left,
    (e.clientY || e.touches[0].clientY) - rect.top
  );
};

const draw = (e) => {
  if (!isDrawing.value) return;
  const rect = canvas.value.getBoundingClientRect();
  ctx.value.lineTo(
    (e.clientX || e.touches[0].clientX) - rect.left,
    (e.clientY || e.touches[0].clientY) - rect.top
  );
  ctx.value.stroke();
};

const endDrawing = () => {
  isDrawing.value = false;
};

const clearSignature = () => {
  ctx.value.clearRect(0, 0, canvas.value.width, canvas.value.height);
};

const toDataURL = () => {
  return canvas.value.toDataURL();
};

defineExpose({ toDataURL });
</script>