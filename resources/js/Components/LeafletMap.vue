<!-- components/LeafletMap.vue -->
<template>
  <div v-if="hasValidCoordinates" ref="mapContainer" class="w-full h-full rounded"></div>
  <div v-else class="w-full h-full bg-gray-100 rounded flex items-center justify-center text-gray-500 text-sm">
    <div class="text-center">
      <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <p>Map unavailable</p>
      <p class="text-xs">Location coordinates not available</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import L from 'leaflet';

// Fix for default markers in Vue/Leaflet
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
});

const props = defineProps({
  latitude: {
    type: Number,
    default: null
  },
  longitude: {
    type: Number,
    default: null
  },
  regionName: {
    type: String,
    default: 'Location'
  },
  zoom: {
    type: Number,
    default: 15
  },
  height: {
    type: String,
    default: '200px'
  }
});

// Check if we have valid coordinates
const hasValidCoordinates = computed(() => {
  return props.latitude !== null && props.latitude !== undefined && 
         props.longitude !== null && props.longitude !== undefined &&
         !isNaN(props.latitude) && !isNaN(props.longitude);
});

const mapContainer = ref(null);
let map = null;
let marker = null;

const initializeMap = () => {
  if (!mapContainer.value || !hasValidCoordinates.value) return;

  // Initialize map
  map = L.map(mapContainer.value).setView([props.latitude, props.longitude], props.zoom);

  // Add tile layer (OpenStreetMap)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
  }).addTo(map);

  // Add marker
  marker = L.marker([props.latitude, props.longitude])
    .addTo(map)
    .bindPopup(`<strong>${props.regionName}</strong><br>Branch Location`)
    .openPopup();
};

const updateMap = () => {
  if (map && hasValidCoordinates.value) {
    map.setView([props.latitude, props.longitude], props.zoom);
    if (marker) {
      marker.setLatLng([props.latitude, props.longitude])
        .bindPopup(`<strong>${props.regionName}</strong><br>Branch Location`)
        .openPopup();
    }
  }
};

// Watch for prop changes
watch(() => [props.latitude, props.longitude], () => {
  if (hasValidCoordinates.value) {
    updateMap();
  }
});

onMounted(() => {
  if (hasValidCoordinates.value) {
    initializeMap();
  }
});

onUnmounted(() => {
  if (map) {
    map.remove();
  }
});
</script>

<style scoped>
/* Ensure map container has proper dimensions */
.w-full.h-full {
  min-height: 200px;
}
</style>