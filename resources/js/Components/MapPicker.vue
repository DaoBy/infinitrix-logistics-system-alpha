<template>
  <div ref="mapContainer" class="w-full h-64 rounded-lg border border-gray-300"></div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import * as L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ lat: 51.505, lng: -0.09 }),
  },
  readonly: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue']);
const mapContainer = ref(null);
const map = ref(null);
const marker = ref(null);

// Fix for default marker icons
const initLeafletIcons = () => {
  delete L.Icon.Default.prototype._getIconUrl;
  L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://unpkg.com/leaflet@1.7.1/dist/images/marker-icon-2x.png',
    iconUrl: 'https://unpkg.com/leaflet@1.7.1/dist/images/marker-icon.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.7.1/dist/images/marker-shadow.png',
  });
};

// Initialize the map
onMounted(() => {
  if (!mapContainer.value) return;
  
  initLeafletIcons();

  // Create the map
  map.value = L.map(mapContainer.value).setView(
    [props.modelValue.lat, props.modelValue.lng],
    13
  );

  // Add OpenStreetMap tiles
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
  }).addTo(map.value);

  // Add a draggable marker
  marker.value = L.marker([props.modelValue.lat, props.modelValue.lng], {
    draggable: !props.readonly,
  }).addTo(map.value);

  // Update position when marker is dragged
  if (!props.readonly) {
    marker.value.on('dragend', (e) => {
      const newPos = e.target.getLatLng();
      emit('update:modelValue', { lat: newPos.lat, lng: newPos.lng });
    });
  }
});

// Update marker position if modelValue changes
watch(
  () => props.modelValue,
  (newVal) => {
    if (marker.value && newVal) {
      marker.value.setLatLng([newVal.lat, newVal.lng]);
      map.value.panTo([newVal.lat, newVal.lng]);
    }
  },
  { deep: true }
);
</script>