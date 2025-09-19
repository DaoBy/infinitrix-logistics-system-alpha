<template>
  <v-container>
    <v-card>
      <v-card-title class="d-flex justify-space-between align-center">
        <span>Delivery Route Tracking</span>
        <v-btn
          color="primary"
          @click="refreshRoute"
          :loading="loading"
        >
          <v-icon left>mdi-refresh</v-icon>
          Refresh
        </v-btn>
      </v-card-title>
      
      <v-card-text>
        <!-- Map Container -->
        <div 
          ref="mapContainer" 
          class="route-map"
          style="height: 500px; width: 100%;"
        ></div>
        
        <!-- Route Progress -->
        <v-card class="mt-4">
          <v-card-title>Route Progress</v-card-title>
          <v-card-text>
            <v-timeline dense>
              <v-timeline-item
                v-for="(region, index) in route"
                :key="region.id"
                :color="getStatusColor(region.status)"
                small
              >
                <template v-slot:opposite>
                  <span :class="`text--${getStatusColor(region.status)}`">
                    {{ formatTime(region.eta_from_start) }}
                  </span>
                </template>
                
                <v-card>
                  <v-card-title class="text-h6">
                    {{ region.name }}
                    <v-chip
                      small
                      :color="getStatusColor(region.status)"
                      class="ml-2"
                    >
                      {{ getStatusText(region.status) }}
                    </v-chip>
                    <v-chip
                      v-if="region.isNext"
                      small
                      color="orange"
                      class="ml-2"
                    >
                      Next Stop
                    </v-chip>
                  </v-card-title>
                  
                  <v-card-text>
                    <div v-if="region.status === 'current'">
                      <p>You are currently here</p>
                      <v-btn
                        v-if="hasNextStop"
                        color="primary"
                        @click="markArrival(nextRegion.id)"
                        :loading="markingArrival"
                      >
                        <v-icon left>mdi-check</v-icon>
                        Mark Next Stop as Arrived
                      </v-btn>
                    </div>
                    
                    <div v-else-if="region.status === 'upcoming' && region.isNext">
                      <p>Next stop on your route</p>
                      <p v-if="region.eta_from_previous">
                        Estimated travel time: {{ formatTime(region.eta_from_previous) }}
                      </p>
                    </div>
                    
                    <div v-else-if="region.status === 'visited'">
                      <p>Completed at {{ formatTime(region.eta_from_start) }}</p>
                    </div>
                  </v-card-text>
                </v-card>
              </v-timeline-item>
            </v-timeline>
          </v-card-text>
        </v-card>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script>
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

export default {
  setup() {
    const mapContainer = ref(null);
    const map = ref(null);
    const route = ref([]);
    const loading = ref(false);
    const markingArrival = ref(false);
    const markers = ref([]);
    const polylines = ref([]);
    
    // Status colors
    const statusColors = {
      visited: 'success',
      current: 'primary',
      upcoming: 'grey',
    };
    
    // Status text
    const statusText = {
      visited: 'Visited',
      current: 'Current Location',
      upcoming: 'Upcoming',
    };
    
    // Get next region
    const nextRegion = computed(() => {
      return route.value.find(region => region.isNext);
    });
    
    // Check if there's a next stop
    const hasNextStop = computed(() => {
      return nextRegion.value !== undefined;
    });
    
    // Format time (minutes to HH:MM)
    const formatTime = (minutes) => {
      const hours = Math.floor(minutes / 60);
      const mins = minutes % 60;
      return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
    };
    
    // Get status color
    const getStatusColor = (status) => {
      return statusColors[status] || 'grey';
    };
    
    // Get status text
    const getStatusText = (status) => {
      return statusText[status] || status;
    };
    
    // Initialize the map
    const initMap = () => {
      if (!mapContainer.value) return;
      
      map.value = L.map(mapContainer.value).setView([0, 0], 2);
      
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
      }).addTo(map.value);
    };
    
    // Update map with route data
    const updateMap = () => {
      if (!map.value || route.value.length === 0) return;
      
      // Clear existing markers and polylines
      markers.value.forEach(marker => map.value.removeLayer(marker));
      polylines.value.forEach(line => map.value.removeLayer(line));
      markers.value = [];
      polylines.value = [];
      
      // Create markers for each region
      route.value.forEach(region => {
        const marker = L.marker([region.latitude, region.longitude], {
          icon: L.divIcon({
            className: `map-marker map-marker-${region.status}`,
            html: `<div class="marker-content">
                    <span class="marker-text">${region.position + 1}</span>
                  </div>`,
            iconSize: [32, 32],
          })
        }).addTo(map.value);
        
        marker.bindPopup(`
          <b>${region.name}</b><br>
          Status: ${getStatusText(region.status)}<br>
          ${region.eta_from_previous ? `Travel time: ${formatTime(region.eta_from_previous)}` : ''}
        `);
        
        markers.value.push(marker);
      });
      
      // Create polylines between regions
      const lineCoords = route.value.map(region => [region.latitude, region.longitude]);
      const polyline = L.polyline(lineCoords, {
        color: '#3f51b5',
        weight: 3,
        opacity: 0.7,
        dashArray: '5, 5',
      }).addTo(map.value);
      
      polylines.value.push(polyline);
      
      // Fit map to bounds
      const bounds = L.latLngBounds(lineCoords);
      map.value.fitBounds(bounds, { padding: [50, 50] });
    };
    
    // Fetch route data
    const fetchRoute = async () => {
      try {
        loading.value = true;
        const response = await axios.get('/api/driver/route-with-status');
        route.value = response.data.route;
        updateMap();
      } catch (error) {
        console.error('Error fetching route:', error);
      } finally {
        loading.value = false;
      }
    };
    
    // Mark arrival at a region
    const markArrival = async (regionId) => {
      try {
        markingArrival.value = true;
        await axios.post('/api/driver/mark-arrival', { region_id: regionId });
        await fetchRoute(); // Refresh data
      } catch (error) {
        console.error('Error marking arrival:', error);
      } finally {
        markingArrival.value = false;
      }
    };
    
    // Refresh route
    const refreshRoute = () => {
      fetchRoute();
    };
    
    // Initialize on mount
    onMounted(() => {
      initMap();
      fetchRoute();
    });
    
    return {
      mapContainer,
      route,
      loading,
      markingArrival,
      nextRegion,
      hasNextStop,
      formatTime,
      getStatusColor,
      getStatusText,
      markArrival,
      refreshRoute,
    };
  },
};
</script>

<style>
.route-map {
  border-radius: 4px;
  border: 1px solid #e0e0e0;
}

.map-marker {
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  text-align: center;
}

.map-marker-visited .marker-content {
  background-color: #4caf50;
  color: white;
}

.map-marker-current .marker-content {
  background-color: #2196f3;
  color: white;
}

.map-marker-upcoming .marker-content {
  background-color: #9e9e9e;
  color: white;
}

.marker-content {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid white;
}

.marker-text {
  font-weight: bold;
  font-size: 12px;
}
</style>
