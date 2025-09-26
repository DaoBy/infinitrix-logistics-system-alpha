<template>
  <div ref="mapContainer" class="w-full h-64 rounded-lg border border-gray-300"></div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: Object,
    // Change from Singapore to Philippines (Manila coordinates)
    default: () => ({ lat: 14.5995, lng: 120.9842 }),
  },
  readonly: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue', 'marker-updated']);
const mapContainer = ref(null);
let map = null;
let marker = null;

const initMap = () => {
  if (!window.google || !window.google.maps) {
    console.error('Google Maps not loaded');
    return;
  }

  if (!mapContainer.value) return;

  map = new google.maps.Map(mapContainer.value, {
    center: props.modelValue,
    zoom: 13,
  });

  marker = new google.maps.Marker({
    position: props.modelValue,
    map: map,
    draggable: !props.readonly,
  });

  if (!props.readonly) {
    marker.addListener('dragend', (event) => {
      const newCoords = {
        lat: event.latLng.lat(),
        lng: event.latLng.lng()
      };
      emit('update:modelValue', newCoords);
      emit('marker-updated', newCoords);
    });

    map.addListener('click', (event) => {
      marker.setPosition(event.latLng);
      const newCoords = {
        lat: event.latLng.lat(),
        lng: event.latLng.lng()
      };
      emit('update:modelValue', newCoords);
      emit('marker-updated', newCoords);
    });
  }
};

onMounted(() => {
  // Wait for Google Maps to load
  if (window.google && window.google.maps) {
    initMap();
  } else {
    const checkInterval = setInterval(() => {
      if (window.google && window.google.maps) {
        clearInterval(checkInterval);
        initMap();
      }
    }, 100);
  }
});

watch(
  () => props.modelValue,
  (newVal) => {
    if (marker && newVal) {
      marker.setPosition(newVal);
      if (map) map.panTo(newVal);
    }
  }
);
</script>