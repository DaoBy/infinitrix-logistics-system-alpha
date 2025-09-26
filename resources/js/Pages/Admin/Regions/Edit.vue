<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-4 md:px-6">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">Edit Warehouse</h2>
          <p class="mt-1 text-sm text-gray-500">
            Update and configure the warehouse with precise location details.
          </p>
        </div>

        <!-- Right: Button -->
        <SecondaryButton @click="cancel">View Warehouses</SecondaryButton>
      </div>
    </template>

    <div class="py-6 px-2 md:px-6">
      <div class="max-w-screen-xl mx-auto">
        <div v-if="status || success || error" class="mb-6">
          <div v-if="status" class="p-4 bg-blue-100 text-blue-800 rounded">{{ status }}</div>
          <div v-if="success" class="p-4 bg-green-100 text-green-800 rounded">{{ success }}</div>
          <div v-if="error" class="p-4 bg-red-100 text-red-800 rounded">{{ error }}</div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
          <!-- Main Form Content (3/4 width) -->
          <div class="lg:col-span-3">
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
              <form @submit.prevent="submit">
                <div class="space-y-8">
                  <!-- Step 1: Region Basic Info -->
                  <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Step 1: Region Information</h3>
                    <div class="grid grid-cols-1 gap-6">
                      <div>
                        <InputLabel for="name" value="Region Name *" />
                        <TextInput 
                          id="name" 
                          v-model="form.name" 
                          class="mt-1 block w-full"
                          placeholder="e.g., Metro Manila, Central Luzon, Bataan Warehouse"
                          required 
                        />
                        <InputError :message="form.errors.name" />
                      </div>

                      <div>
                        <InputLabel for="color_hex" value="Region Color *" />
                        <div class="flex items-center mt-1">
                          <input 
                            type="color" 
                            id="color_hex"
                            v-model="form.color_hex"
                            class="h-10 w-10 rounded border border-gray-300 mr-2"
                          />
                          <TextInput 
                            v-model="form.color_hex"
                            class="block w-32"
                            placeholder="#FF0000"
                            required
                            pattern="^#[0-9A-Fa-f]{6}$"
                          />
                        </div>
                        <InputError :message="form.errors.color_hex" />
                        <p class="text-sm text-gray-500 mt-1">Choose a color to represent this region on maps and charts</p>
                      </div>
                    </div>
                  </div>

                  <!-- Step 2: Location Search -->
                  <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Step 2: Find General Location</h3>
                    <div>
                      <InputLabel for="address-search" value="Search for City/Municipality/Landmark *" />
                      <div class="mt-1 relative">
                        <TextInput 
                          id="address-search"
                          v-model="addressQuery"
                          @input="searchAddress"
                          class="block w-full pr-10"
                          placeholder="Type city, municipality, or landmark (e.g., Balanga City, Bataan)"
                          autocomplete="off"
                        />
                        <div v-if="isSearching" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                          <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                          </svg>
                        </div>
                        <div v-if="addressSuggestions.length > 0 && addressQuery" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto">
                          <div v-for="(suggestion, index) in addressSuggestions" :key="index" 
                               @click="selectAddress(suggestion)"
                               @mousedown.prevent
                               class="px-4 py-2 cursor-pointer hover:bg-gray-100 border-b border-gray-100 last:border-b-0">
                            <div class="font-medium">{{ suggestion.main_text }}</div>
                            <div class="text-sm text-gray-600">{{ suggestion.secondary_text }}</div>
                          </div>
                        </div>
                      </div>
                      <p class="text-sm text-gray-500 mt-1">Search for the general area first. You'll specify the exact warehouse address in the next step.</p>
                    </div>
                  </div>

                  <!-- Step 3: Detailed Address -->
                  <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Step 3: Specify Exact Warehouse Address</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div class="md:col-span-2">
                        <InputLabel for="street_address" value="Street Address/Building Name *" />
                        <TextInput 
                          id="street_address" 
                          v-model="form.street_address" 
                          class="mt-1 block w-full"
                          placeholder="e.g., 4th Floor, The Bunker, Capitol Compound"
                          required 
                        />
                      </div>

                      <div>
                        <InputLabel for="barangay" value="Barangay" />
                        <TextInput 
                          id="barangay" 
                          v-model="form.barangay" 
                          class="mt-1 block w-full"
                          placeholder="e.g., San Jose"
                        />
                      </div>

                      <div>
                        <InputLabel for="city_municipality" value="City/Municipality *" />
                        <TextInput 
                          id="city_municipality" 
                          v-model="form.city_municipality" 
                          class="mt-1 block w-full"
                          placeholder="e.g., Balanga City"
                          required 
                        />
                      </div>

                      <div>
                        <InputLabel for="province" value="Province *" />
                        <TextInput 
                          id="province" 
                          v-model="form.province" 
                          class="mt-1 block w-full"
                          placeholder="e.g., Bataan"
                          required 
                        />
                      </div>

                      <div>
                        <InputLabel for="postal_code" value="Postal Code" />
                        <TextInput 
                          id="postal_code" 
                          v-model="form.postal_code" 
                          class="mt-1 block w-full"
                          placeholder="e.g., 2100"
                        />
                      </div>

                      <div class="md:col-span-2">
                        <InputLabel for="warehouse_address" value="Full Warehouse Address (Auto-generated) *" />
                        <TextareaInput 
                          id="warehouse_address" 
                          v-model="form.warehouse_address" 
                          class="mt-1 block w-full"
                          :rows="2"
                          required 
                          readonly
                          placeholder="This will be automatically generated from the fields above"
                        />
                        <InputError :message="form.errors.warehouse_address" />
                      </div>
                    </div>
                    <div class="mt-4">
                      <button type="button" @click="geocodeAddressManually" class="text-sm text-blue-600 hover:text-blue-800">
                        🔄 Update map location based on address above
                      </button>
                    </div>
                  </div>

                  <!-- Step 4: Map Verification -->
                  <div class="pb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Step 4: Verify Location on Map</h3>
                    <div>
                      <InputLabel value="Region Location *" />
                      <p class="text-sm text-gray-600 mb-2">Drag the marker to the exact warehouse location. The map will update when you search or enter an address.</p>
                      <MapPicker 
                        ref="mapPickerRef"
                        v-model="form.geographic_location" 
                        class="h-64 border border-gray-300 rounded-lg mt-2"
                        @marker-updated="onMarkerManuallyUpdated"
                      />
                      <div class="mt-2 text-sm text-gray-500">
                        Coordinates: {{ form.geographic_location.lat.toFixed(6) }}, {{ form.geographic_location.lng.toFixed(6) }}
                      </div>
                      <InputError :message="form.errors['geographic_location.lat']" />
                      <InputError :message="form.errors['geographic_location.lng']" />
                    </div>
                  </div>

                  <div class="flex justify-between items-center pt-4 px-2">
                    <SecondaryButton type="button" @click="cancel">View Warehouses</SecondaryButton>
                    <PrimaryButton type="submit" :disabled="form.processing">
                      {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </PrimaryButton>
                  </div>
                </div>
              </form>
            </div>
          </div>

          <!-- Guide Sidebar (1/4 width) -->
          <div class="lg:col-span-1">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <div class="flex justify-between items-start mb-3">
                <h3 class="font-semibold text-blue-800">📝 Quick Guide</h3>
                <button @click="showGuide = !showGuide" class="text-blue-500 hover:text-blue-700 text-sm">
                  {{ showGuide ? 'Hide' : 'Show' }}
                </button>
              </div>
                      
              <div v-if="showGuide" class="space-y-3">
                <div class="text-sm text-blue-700">
                  <h4 class="font-medium mb-1">Step 1: Region Info</h4>
                  <p class="text-xs">Name your region and choose a color</p>
                </div>
                
                <div class="text-sm text-blue-700">
                  <h4 class="font-medium mb-1">Step 2: General Search</h4>
                  <p class="text-xs">Find the city or municipality first</p>
                </div>
                
                <div class="text-sm text-blue-700">
                  <h4 class="font-medium mb-1">Step 3: Exact Address</h4>
                  <p class="text-xs">Fill in the specific warehouse details</p>
                </div>
                
                <div class="text-sm text-blue-700">
                  <h4 class="font-medium mb-1">Step 4: Map Verify</h4>
                  <p class="text-xs">Drag the pin to the exact location</p>
                </div>
                
                <div class="mt-3 p-2 bg-blue-100 rounded border border-blue-200">
                  <p class="text-xs text-blue-600 font-medium">💡 Pro Tip</p>
                  <p class="text-xs text-blue-600 mt-1">
                    Can't find the exact address? Search for the nearest city first, then manually enter the full warehouse address below.
                  </p>
                </div>
              </div>
              
              <div v-else class="text-center py-2">
                <button @click="showGuide = true" class="text-blue-600 hover:text-blue-800 text-sm">
                  Show Guide
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import TextareaInput from '@/Components/TextArea.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import MapPicker from '@/Components/MapPicker.vue';
import { ref, onMounted, watch } from 'vue';

const props = defineProps({
  region: Object,
  status: String,
  success: String,
  error: String,
});

// Parse the existing address into components (you might need to adjust this based on your data structure)
const parseExistingAddress = (fullAddress) => {
  // Simple parsing - you might want to implement more sophisticated parsing
  const parts = fullAddress.split(',').map(part => part.trim());
  
  return {
    street_address: parts[0] || '',
    barangay: parts[1] || '',
    city_municipality: parts[2] || '',
    province: parts[3] || '',
    postal_code: parts[4] || ''
  };
};

const addressComponents = parseExistingAddress(props.region.warehouse_address);

const form = useForm({
  name: props.region.name,
  warehouse_address: props.region.warehouse_address,
  street_address: addressComponents.street_address,
  barangay: addressComponents.barangay,
  city_municipality: addressComponents.city_municipality,
  province: addressComponents.province,
  postal_code: addressComponents.postal_code,
  color_hex: props.region.color_hex || '#CCCCCC',
  geographic_location: { 
    lat: parseFloat(props.region.geographic_location.latitude),
    lng: parseFloat(props.region.geographic_location.longitude)
  }
});

const addressQuery = ref('');
const addressSuggestions = ref([]);
const isSearching = ref(false);
const isGoogleMapsLoaded = ref(false);
const mapPickerRef = ref(null);
const showGuide = ref(true);
let autocompleteService = null;
let geocoder = null;

// Custom debounce function
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

// Wait for Google Maps to load
const waitForGoogleMaps = () => {
  return new Promise((resolve) => {
    if (window.google && window.google.maps) {
      isGoogleMapsLoaded.value = true;
      resolve();
      return;
    }

    const checkInterval = setInterval(() => {
      if (window.google && window.google.maps) {
        clearInterval(checkInterval);
        isGoogleMapsLoaded.value = true;
        initGoogleServices();
        resolve();
      }
    }, 100);
  });
};

function initGoogleServices() {
  if (window.google && window.google.maps) {
    autocompleteService = new google.maps.places.AutocompleteService();
    geocoder = new google.maps.Geocoder();
  }
}

onMounted(async () => {
  await waitForGoogleMaps();
});

// Debounced search function using custom debounce
const searchAddress = debounce(async (event) => {
  const query = event.target.value;
  
  if (!query || query.length < 3) {
    addressSuggestions.value = [];
    return;
  }

  if (!isGoogleMapsLoaded.value || !autocompleteService) {
    console.warn('Google Maps not loaded yet');
    return;
  }

  isSearching.value = true;
  
  try {
    const request = {
      input: query,
      types: ['geocode', 'establishment'],
      componentRestrictions: { country: 'ph' }
    };

    autocompleteService.getPlacePredictions(request, (predictions, status) => {
      isSearching.value = false;
      
      if (status === google.maps.places.PlacesServiceStatus.OK && predictions) {
        addressSuggestions.value = predictions.map(prediction => ({
          place_id: prediction.place_id,
          main_text: prediction.structured_formatting.main_text,
          secondary_text: prediction.structured_formatting.secondary_text,
          description: prediction.description
        }));
      } else {
        addressSuggestions.value = [];
      }
    });
  } catch (error) {
    isSearching.value = false;
    console.error('Address search error:', error);
  }
}, 300);

async function selectAddress(suggestion) {
  addressQuery.value = suggestion.description;
  addressSuggestions.value = [];
  
  if (!isGoogleMapsLoaded.value || !geocoder) {
    console.warn('Google Maps not loaded yet');
    return;
  }
  
  try {
    const result = await new Promise((resolve, reject) => {
      geocoder.geocode({ placeId: suggestion.place_id }, (results, status) => {
        if (status === 'OK' && results[0]) {
          resolve(results[0]);
        } else {
          reject(new Error(`Geocoding failed: ${status}`));
        }
      });
    });

    // Parse address components to fill individual fields
    parseAddressComponents(result.address_components);
    
    const newCoords = {
      lat: result.geometry.location.lat(),
      lng: result.geometry.location.lng()
    };
    
    form.geographic_location = newCoords;
    form.warehouse_address = suggestion.description;
    
    // Update the map
    if (mapPickerRef.value && typeof mapPickerRef.value.onMarkerManuallyUpdated === 'function') {
      mapPickerRef.value.onMarkerManuallyUpdated(newCoords);
    }
    
  } catch (error) {
    console.error('Error selecting address:', error);
    form.warehouse_address = suggestion.description;
  }
}

// Parse Google Maps address components to fill individual fields
function parseAddressComponents(addressComponents) {
  const address = {
    street: '',
    barangay: '',
    city: '',
    province: '',
    postal: ''
  };

  addressComponents.forEach(component => {
    const types = component.types;
    
    if (types.includes('street_number')) {
      address.street = component.long_name + ' ' + address.street;
    } else if (types.includes('route')) {
      address.street += component.long_name;
    } else if (types.includes('sublocality_level_1') || types.includes('sublocality')) {
      address.barangay = component.long_name;
    } else if (types.includes('locality')) {
      address.city = component.long_name;
    } else if (types.includes('administrative_area_level_2')) {
      address.province = component.long_name;
    } else if (types.includes('administrative_area_level_1')) {
      if (!address.province) address.province = component.long_name;
    } else if (types.includes('postal_code')) {
      address.postal = component.long_name;
    }
  });

  // Update form fields
  form.street_address = address.street.trim();
  form.barangay = address.barangay;
  form.city_municipality = address.city;
  form.province = address.province;
  form.postal_code = address.postal;
}

// Build full address from individual components
function buildFullAddress() {
  const parts = [
    form.street_address,
    form.barangay,
    form.city_municipality,
    form.province,
    form.postal_code
  ].filter(part => part && part.trim() !== '');
  
  form.warehouse_address = parts.join(', ');
}

// Watch individual address fields and build full address
watch([() => form.street_address, () => form.barangay, () => form.city_municipality, () => form.province, () => form.postal_code], () => {
  buildFullAddress();
});

async function geocodeAddressManually() {
  if (!isGoogleMapsLoaded.value || !geocoder || !form.warehouse_address) {
    return;
  }

  try {
    const result = await new Promise((resolve, reject) => {
      geocoder.geocode({ address: form.warehouse_address }, (results, status) => {
        if (status === 'OK' && results[0]) {
          resolve(results[0]);
        } else {
          reject(new Error(`Geocoding failed: ${status}`));
        }
      });
    });

    parseAddressComponents(result.address_components);
    
    const newCoords = {
      lat: result.geometry.location.lat(),
      lng: result.geometry.location.lng()
    };
    
    form.geographic_location = newCoords;
    
    // Update the map
    if (mapPickerRef.value && typeof mapPickerRef.value.onMarkerManuallyUpdated === 'function') {
      mapPickerRef.value.onMarkerManuallyUpdated(newCoords);
    }
    
  } catch (error) {
    console.error('Manual geocoding failed:', error);
  }
}

function onMarkerManuallyUpdated(newCoords) {
  form.geographic_location = newCoords;
}

function submit() {
  // Ensure warehouse_address is built from individual components
  buildFullAddress();
  form.put(route('admin.regions.update', props.region.id), {
    preserveScroll: true
  });
}

function cancel() {
  router.get(route('admin.regions.index'));
}
</script>