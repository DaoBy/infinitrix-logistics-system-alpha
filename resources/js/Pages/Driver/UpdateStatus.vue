<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import { ChevronDownIcon } from '@heroicons/vue/20/solid';
import { router } from '@inertiajs/vue3';

// Leaflet Imports
import { LMap, LTileLayer, LMarker, LPolyline, LPopup, LControlZoom } from '@vue-leaflet/vue-leaflet';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';

// Fix Leaflet marker icons
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://unpkg.com/leaflet@1.7.1/dist/images/marker-icon-2x.png',
  iconUrl: 'https://unpkg.com/leaflet@1.7.1/dist/images/marker-icon.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.7.1/dist/images/marker-shadow.png',
});

const props = defineProps({
    packages: Array,
    regions: Array,
    statusOptions: Object,
    canReturnToBase: Boolean,
    hasPendingReturn: Boolean,
    activeAssignmentId: [Number, String, null],
    allPackagesFinal: Boolean,
    routeData: {
        type: Object,
        default: () => ({
            current_region: null,
            route: []
        })
    }
});

// State Management
const showConfirmationModal = ref(false);
const showLocationUpdateModal = ref(false);
const selectedPackages = ref([]);
const selectedRegion = ref(null);
const localHasPendingReturn = ref(props.hasPendingReturn);

// Forms
const form = useForm({
    package_ids: [],
    status: '',
    remarks: '',
    region_id: ''
});

const driverRegionForm = useForm({
    region_id: null,
    update_packages: false,
    only_in_transit: true
});

// Map Refs and State
const mapRef = ref(null);
const center = ref([13.1399, 123.7448]);
const zoom = ref(11);

// Computed Properties
const currentRegionId = computed(() => props.routeData.current_region?.id || null);
const currentLocation = computed(() => {
    if (!props.packages?.length) return null;
    
    const regionCounts = props.packages.reduce((acc, pkg) => {
        if (pkg.current_region?.name) {
            acc[pkg.current_region.name] = (acc[pkg.current_region.name] || 0) + 1;
        }
        return acc;
    }, {});

    return Object.entries(regionCounts).reduce((max, [name, count]) => 
        count > max.count ? { name, count } : max, 
        { name: null, count: 0 }
    ).name;
});

const routeCoordinates = computed(() => {
    return props.routeData.route
        .map(step => {
            if (!step.region || step.region.latitude === undefined || step.region.longitude === undefined) return null;
            const lat = parseFloat(step.region.latitude);
            const lng = parseFloat(step.region.longitude);
            return isNaN(lat) || isNaN(lng) ? null : [lat, lng];
        })
        .filter(coord => coord !== null);
});

const mapBounds = computed(() => {
    if (routeCoordinates.value.length < 2) return null;
    try {
        return L.latLngBounds(routeCoordinates.value);
    } catch (e) {
        console.error('Invalid coordinates for bounds:', e);
        return null;
    }
});

const totalRouteTime = computed(() => 
    props.routeData.route.reduce((sum, step) => sum + (step.estimated_minutes || 0), 0)
);

const filteredPackages = computed(() => 
    props.packages.filter(pkg => 
        selectedRegion.value ? pkg.current_region.id === selectedRegion.value : true
    )
);

const selectedPackagesDetails = computed(() => 
    props.packages.filter(pkg => selectedPackages.value.includes(pkg.id))
);

const groupedPackages = computed(() => {
    const groups = {};
    const pkgs = props.packages.filter(pkg => !pkg.is_final_status);
    
    pkgs.forEach(pkg => {
        const key = `${pkg.deliveryRequest?.drop_off_region_id}_${pkg.current_region.id}`;
        if (!groups[key]) {
            const destinationObj = pkg.deliveryRequest?.dropOffRegion 
                ? (typeof pkg.deliveryRequest.dropOffRegion === 'object'
                    ? pkg.deliveryRequest.dropOffRegion
                    : { name: String(pkg.deliveryRequest.dropOffRegion) })
                : { name: 'Unknown' };

            groups[key] = {
                destination: destinationObj,
                currentRegion: pkg.current_region.name,
                packages: []
            };
        }
        groups[key].packages.push(pkg);
    });
    
    return Object.values(groups);
});

const updateSummary = computed(() => {
    const summary = {};
    if (!form.status) return summary;
    
    selectedPackagesDetails.value.forEach(pkg => {
        if (form.region_id && pkg.deliveryRequest?.drop_off_region_id == form.region_id) {
            summary['delivered'] = (summary['delivered'] || 0) + 1;
        } else if (form.region_id && typeof pkg.shouldMarkAsReturn === 'function' && pkg.shouldMarkAsReturn(form.region_id)) {
            summary['returned'] = (summary['returned'] || 0) + 1;
        } else {
            summary[form.status] = (summary[form.status] || 0) + 1;
        }
    });
    
    summary.manual = selectedPackagesDetails.value.length - (summary.delivered + summary.returned || 0);
    return summary;
});

// Map Functions
const onMapReady = () => {
    if (mapRef.value?.leafletObject && routeCoordinates.value.length > 0) {
        mapRef.value.leafletObject.flyTo(center.value, zoom.value);
    }
};

const getMarkerIcon = (step, index) => {
    if (isDestination(step.region.id)) {
        return createDivIcon('bg-green-500', index);
    }
    if (step.region.id === currentRegionId.value) {
        return createDivIcon('bg-blue-500', index);
    }
    return createDivIcon('bg-gray-500', index);
};

const createDivIcon = (bgColor, index) => L.divIcon({
    html: `<div class="w-8 h-8 rounded-full ${bgColor} border-2 border-white flex items-center justify-center text-white font-bold">${index + 1}</div>`,
    className: '',
    iconSize: [32, 32],
    iconAnchor: [16, 16]
});

const isDestination = (regionId) => 
    props.packages.some(pkg => pkg.deliveryRequest?.drop_off_region_id === regionId);

const filteredRouteSteps = computed(() => 
    props.routeData.route?.filter(step => {
        if (!step?.region) return false;
        const lat = Number(step.region.latitude);
        const lng = Number(step.region.longitude);
        return !isNaN(lat) && !isNaN(lng);
    }) || []
);

// Helper Functions
const statusClasses = (status) => {
    const base = 'px-2 py-1 text-xs rounded-full';
    switch (status) {
        case 'loaded': return `${base} bg-blue-100 text-blue-800`;
        case 'in_transit': return `${base} bg-yellow-100 text-yellow-800`;
        case 'delivered': return `${base} bg-green-100 text-green-800`;
        case 'returned': return `${base} bg-red-100 text-red-800`;
        default: return `${base} bg-gray-100 text-gray-800`;
    }
};

const togglePackageSelection = (pkgId) => {
    const idx = selectedPackages.value.indexOf(pkgId);
    if (idx === -1) {
        selectedPackages.value.push(pkgId);
    } else {
        selectedPackages.value.splice(idx, 1);
    }
};

// Form Handlers
const openConfirmationModal = () => {
    if (selectedPackages.value.length === 0) {
        form.errors.package_ids = 'Please select at least one package';
        return;
    }
    
    if (!form.status) {
        form.errors.status = 'Please select a status';
        return;
    }
    
    form.package_ids = selectedPackages.value;
    showConfirmationModal.value = true;
};

const openLocationUpdateModal = () => {
    if (!driverRegionForm.region_id) {
        driverRegionForm.errors.region_id = 'Please select a location';
        return;
    }
    showLocationUpdateModal.value = true;
};

const submitForm = () => {
    form.post(route('driver.bulk-update-status'), {
        preserveScroll: true,
        onSuccess: () => {
            showConfirmationModal.value = false;
            selectedPackages.value = [];
            form.reset('status', 'remarks');
        }
    });
};

const updateDriverLocation = () => {
    driverRegionForm.post(route('driver.update-region'), {
        preserveScroll: true,
        onSuccess: () => {
            showLocationUpdateModal.value = false;
            driverRegionForm.reset();
        }
    });
};

const confirmReturnToBase = () => {
    if (!props.activeAssignmentId) return;
    
    router.post(
        route('driver-truck-assignments.confirm-return', { assignment: props.activeAssignmentId }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                localHasPendingReturn.value = true;
                router.reload();
            }
        }
    );
};

// Watchers
watch(routeCoordinates, (newCoords) => {
    if (newCoords.length > 0) {
        center.value = newCoords[0];
        zoom.value = newCoords.length > 1 ? 9 : 11;
        if (mapRef.value?.leafletObject) {
            mapRef.value.leafletObject.flyTo(center.value, zoom.value);
        }
    }
}, { immediate: true });

watch(() => props.hasPendingReturn, (val) => {
    localHasPendingReturn.value = val;
});
</script>

<template>
    <Head title="Update Package Status" />

    <EmployeeLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h2 class="text-2xl font-bold leading-tight text-gray-900 dark:text-gray-100">
                        Update Package Status
                    </h2>
                    <div class="flex items-center mt-1 text-sm text-gray-600 dark:text-gray-300">
                        <svg class="h-4 w-4 mr-1 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 11c1.104 0 2-.896 2-2s-.896-2-2-2-2 .896-2 2 .896 2 2 2zm0 0c-4 0-7 2-7 4v2h14v-2c0-2-3-4-7-4z"/>
                        </svg>
                        <span>Current Location: <span class="font-semibold">{{ currentLocation }}</span></span>
                    </div>
                </div>
                <SecondaryButton
                    @click="router.visit(route('driver.dashboard'))"
                    class="ml-0 sm:ml-4 mt-3 sm:mt-0"
                >
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h2l.4 2M7 13h10l4-8H5.4" />
                    </svg>
                    Back to Dashboard
                </SecondaryButton>
            </div>
        </template>

        <!-- Flash Messages -->
        <div v-if="$page.props.flash?.success" class="mb-4 p-4 bg-green-50 text-green-800 rounded-lg dark:bg-green-900/20 dark:text-green-200">
            {{ $page.props.flash.success }}
        </div>
        <div v-if="$page.props.flash?.error" class="mb-4 p-4 bg-red-50 text-red-800 rounded-lg dark:bg-red-900/20 dark:text-red-200">
            {{ $page.props.flash.error }}
        </div>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-xl dark:bg-gray-800">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <!-- Delivery Route Map Section -->
                        <div class="mb-8 p-4 bg-white rounded-lg shadow-sm dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                            <div class="flex items-center gap-2 mb-4">
                                <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                </svg>
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                                    Delivery Route Map
                                </h3>
                            </div>
                            
                            <div class="h-96 rounded-md overflow-hidden relative z-0 border border-gray-200 dark:border-gray-700">
                                <l-map 
                                    ref="mapRef" 
                                    :zoom="zoom" 
                                    :center="center" 
                                    :bounds="mapBounds"
                                    :options="{ zoomControl: false }"
                                    @ready="onMapReady"
                                >
                                    <l-tile-layer
                                        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                                        attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                                    />
                                    
                                    <l-marker
                                        v-for="(step, idx) in filteredRouteSteps"
                                        :key="`route-step-${idx}-${step.region?.id || 'undefined'}`"
                                        :lat-lng="[Number(step.region.latitude), Number(step.region.longitude)]"
                                        :icon="getMarkerIcon(step, idx)"
                                    >
                                        <l-popup>
                                            <div class="font-medium text-gray-900">
                                                {{ step.region?.name || 'Unknown' }}
                                            </div>
                                            <div v-if="isDestination(step.region.id)" class="text-xs text-green-600">
                                                Delivery Destination
                                            </div>
                                            <div v-else-if="step.region.id === currentRegionId" class="text-xs text-blue-600">
                                                Current Location
                                            </div>
                                            <div v-else class="text-xs text-gray-600">
                                                Stop {{ idx + 1 }}
                                                <span v-if="step.estimated_minutes > 0">
                                                    • ETA: {{ step.estimated_minutes }} mins
                                                </span>
                                            </div>
                                        </l-popup>
                                    </l-marker>
                                    
                                    <l-control-zoom position="topright"></l-control-zoom>
                                </l-map>
                            </div>
                            
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 rounded-full bg-blue-500 mr-2"></div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Route Stops</span>
                                    </div>
                                    <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                        {{ routeData.route.length }} stops total
                                    </div>
                                </div>
                                
                                <div class="bg-green-50 dark:bg-green-900/20 p-3 rounded-lg">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Estimated Time</span>
                                    </div>
                                    <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                        Total: {{ totalRouteTime }} minutes
                                    </div>
                                </div>
                                
                                <div class="bg-purple-50 dark:bg-purple-900/20 p-3 rounded-lg">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 text-purple-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Next Stop</span>
                                    </div>
                                    <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                        {{ routeData.route[1]?.region?.name || 'None' }} 
                                        <span v-if="routeData.route[1]?.estimated_minutes">({{ routeData.route[1].estimated_minutes }} mins)</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Location Update Section -->
                        <div class="mb-8 p-4 bg-blue-50 rounded-lg dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800">
                            <div class="flex items-center gap-2 mb-4">
                                <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <h3 class="text-base font-semibold text-blue-800 dark:text-blue-200">
                                    Your Delivery Route
                                </h3>
                            </div>
                            
                            <!-- Route Visualization -->
                            <div class="mb-4">
                                <div class="flex items-center justify-center overflow-x-auto py-2 px-4">
                                    <div v-for="(step, index) in routeData.route" :key="index" class="flex items-center">
                                        <div class="flex flex-col items-center">
                                            <div class="relative">
                                                <div :class="[
                                                    'w-10 h-10 rounded-full flex items-center justify-center border-2',
                                                    step.region.id === currentRegionId ? 'bg-blue-100 border-blue-500' : 
                                                    (index < routeData.route.findIndex(r => r.region.id === currentRegionId) ? 'bg-gray-100 border-gray-300' : 'bg-white border-gray-300 dark:bg-gray-700 dark:border-gray-500'),
                                                    driverRegionForm.region_id === step.region.id ? 'ring-2 ring-blue-400' : ''
                                                ]">
                                                    <span class="text-sm font-medium">
                                                        {{ index + 1 }}
                                                    </span>
                                                </div>
                                                
                                                <div v-if="index < routeData.route.length - 1" class="absolute top-1/2 right-0 transform translate-x-1/2 -translate-y-1/2">
                                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <span class="mt-2 text-xs font-medium text-center max-w-[80px] truncate">
                                                {{ step.region.name }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Current Location Selector -->
                            <div class="relative">
                                <select
                                    v-model="driverRegionForm.region_id"
                                    class="block w-full px-4 py-3 pr-10 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                >
                                    <option value="">Select your current location</option>
                                    <optgroup label="Your Route">
                                        <option 
                                            v-for="step in routeData.route" 
                                            :key="step.region.id" 
                                            :value="step.region.id"
                                            :disabled="step.region.id === currentRegionId"
                                        >
                                            {{ step.region.name }} 
                                            <template v-if="step.region.id === currentRegionId">(Current)</template>
                                        </option>
                                    </optgroup>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700 dark:text-gray-300">
                                    <ChevronDownIcon class="h-5 w-5" />
                                </div>
                            </div>
                            
                            <div class="mt-3 flex justify-end">
                                <PrimaryButton 
                                    @click="openLocationUpdateModal"
                                    :disabled="!driverRegionForm.region_id"
                                    class="w-full sm:w-auto"
                                >
                                    Update Location
                                </PrimaryButton>
                            </div>
                        </div>

                        <!-- Complete Trip/Return to Base Section -->
                        <div v-if="allPackagesFinal && activeAssignmentId">
                            <div v-if="!localHasPendingReturn" class="mb-8 p-4 bg-orange-50 rounded-lg dark:bg-orange-900/20 border border-orange-100 dark:border-orange-800">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="h-5 w-5 text-orange-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <h3 class="text-base font-semibold text-orange-800 dark:text-orange-200">
                                        Complete Trip
                                    </h3>
                                </div>
                                <p class="text-sm text-orange-700 dark:text-orange-300 mb-3">
                                    Mark yourself as returned to your home base.
                                </p>
                                <PrimaryButton 
                                    @click="confirmReturnToBase"
                                    :disabled="!activeAssignmentId"
                                    class="w-full"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Confirm Return to Base
                                </PrimaryButton>
                            </div>
                            <div v-else class="mb-8 p-4 bg-orange-50 rounded-lg dark:bg-orange-900/20 border border-orange-100 dark:border-orange-800">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="h-5 w-5 text-orange-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <h3 class="text-base font-semibold text-orange-800 dark:text-orange-200">
                                        Complete Trip
                                    </h3>
                                </div>
                                <p class="text-sm text-orange-700 dark:text-orange-300 mb-3">
                                    Your return to base is pending verification by staff.
                                </p>
                                <PrimaryButton disabled class="w-full opacity-60 cursor-not-allowed">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Return Pending Verification
                                </PrimaryButton>
                            </div>
                        </div>

                        <!-- Reminder for delivered packages -->
                        <div
                            v-if="allPackagesFinal"
                            class="mb-8 p-4 bg-yellow-50 border-l-4 border-yellow-400 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-100 rounded"
                        >
                            <strong>Reminder:</strong> All your assigned packages have been delivered. Your truck status will automatically update to <span class="font-semibold">Returning</span>.
                        </div>

                        <!-- Status Update Form -->
                        <div v-if="selectedPackages.length > 0" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Update Status
                                </label>
                                <select
                                    v-model="form.status"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                >
                                    <option value="">Select Status</option>
                                    <option value="in_transit">In Transit</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="returned">Returned to Sender Branch</option>
                                </select>
                                <InputError :message="form.errors.status" class="mt-1" />
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Choose the new status for the selected packages.
                                </div>
                            </div>

                            <div v-if="form.status === 'in_transit' || form.status === 'delivered'">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Destination Region
                                </label>
                                <select
                                    v-model="form.region_id"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                >
                                    <option value="">Select Destination</option>
                                    <option 
                                        v-for="region in regions" 
                                        :key="region.id" 
                                        :value="region.id"
                                    >
                                        {{ region.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.region_id" class="mt-1" />
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Select the region you are delivering to or passing through.
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Remarks
                                </label>
                                <textarea
                                    v-model="form.remarks"
                                    rows="3"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    placeholder="Optional notes about this status update"
                                ></textarea>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Add any additional notes for this update (optional).
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <PrimaryButton @click="openConfirmationModal">
                                    Update Status
                                </PrimaryButton>
                            </div>
                        </div>

                        <!-- Grouped Packages Section -->
                        <div class="mt-10 space-y-6">
                            <div
                                v-for="(group, index) in groupedPackages"
                                :key="index"
                                class="border rounded-lg mb-6"
                            >
                                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 flex justify-between items-center">
                                    <div>
                                        <h3 class="font-medium text-gray-900 dark:text-white">
                                            To: {{ group.destination.name }}
                                        </h3>
                                        <div class="flex items-center mt-1 text-xs text-gray-500 dark:text-gray-300">
                                            <span class="inline-flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Current: {{ group.currentRegion }}
                                            </span>
                                            <span class="mx-2">•</span>
                                            <span>{{ group.packages.length }} package(s)</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <div
                                        v-for="pkg in group.packages"
                                        :key="pkg.id"
                                        class="flex items-center justify-between p-4 hover:bg-blue-50 dark:hover:bg-blue-900/30 cursor-pointer transition"
                                        :class="{
                                            'border-blue-200 bg-blue-50 dark:bg-blue-900/30': selectedPackages.includes(pkg.id),
                                            'border-gray-200 dark:border-gray-700': !selectedPackages.includes(pkg.id)
                                        }"
                                        @click="togglePackageSelection(pkg.id)"
                                    >
                                        <div class="flex items-center space-x-4">
                                            <Checkbox
                                                v-model:checked="selectedPackages"
                                                :value="pkg.id"
                                                :checked="selectedPackages.includes(pkg.id)"
                                                @click.stop
                                            />
                                            <div>
                                                <div class="font-medium text-gray-900 dark:text-white">
                                                    {{ pkg.item_code }}
                                                </div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ pkg.item_name }}
                                                </div>
                                                <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                                    Status: <span class="font-semibold">{{ statusOptions[pkg.status] || pkg.status }}</span>
                                                    <span class="mx-2">|</span>
                                                    Current Region: <span class="font-semibold">{{ pkg.current_region.name }}</span>
                                                </div>
                                                <div v-if="pkg.deliveryRequest" class="text-xs text-gray-400 dark:text-gray-500">
                                                    Destination: <span class="font-semibold">{{ pkg.deliveryRequest.dropOffRegion }}</span>
                                                    <span class="mx-2">|</span>
                                                    Pickup: <span class="font-semibold">{{ pkg.deliveryRequest.pickUpRegion }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-end space-y-1">
                                            <span :class="statusClasses(pkg.status)">
                                                {{ statusOptions[pkg.status] || pkg.status }}
                                            </span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                Code: {{ pkg.item_code }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <Modal :show="showConfirmationModal" @close="showConfirmationModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    Update Summary
                </h2>

                <div class="mb-6 space-y-3">
                    <div v-if="updateSummary.delivered > 0" class="flex items-start">
                        <svg class="h-5 w-5 text-green-500 mt-0.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <p class="text-gray-700 dark:text-gray-300">
                            <span class="font-semibold">{{ updateSummary.delivered }}</span> package(s) will be automatically marked as <span class="font-semibold">Delivered</span> at destination
                        </p>
                    </div>

                    <div v-if="updateSummary.returned > 0" class="flex items-start">
                        <svg class="h-5 w-5 text-red-500 mt-0.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <p class="text-gray-700 dark:text-gray-300">
                            <span class="font-semibold">{{ updateSummary.returned }}</span> package(s) will be automatically marked as <span class="font-semibold">Returned</span>
                        </p>
                    </div>

                    <div v-if="updateSummary.manual > 0" class="flex items-start">
                        <svg class="h-5 w-5 text-blue-500 mt-0.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-gray-700 dark:text-gray-300">
                            <span class="font-semibold">{{ updateSummary.manual }}</span> package(s) will be updated to <span class="font-semibold">{{ statusOptions[form.status] || form.status }}</span>
                        </p>
                    </div>

                    <div v-if="form.region_id" class="flex items-start pt-2 mt-2 border-t border-gray-200 dark:border-gray-700">
                        <svg class="h-5 w-5 text-gray-500 mt-0.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <p class="text-gray-700 dark:text-gray-300">
                            Driver location will be set to <span class="font-semibold">{{ regions.find(r => r.id == form.region_id)?.name || '' }}</span>
                        </p>
                    </div>
                </div>

                <div class="mb-6" v-if="selectedPackagesDetails.length > 0">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Selected Packages:
                    </h3>
                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                        <li v-for="pkg in selectedPackagesDetails.slice(0, 5)" :key="pkg.id">
                            {{ pkg.item_code }} - {{ pkg.item_name }}
                        </li>
                        <li v-if="selectedPackagesDetails.length > 5" class="text-gray-500">
                            + {{ selectedPackagesDetails.length - 5 }} more...
                        </li>
                    </ul>
                </div>

                <div class="flex justify-end space-x-4">
                    <SecondaryButton @click="showConfirmationModal = false">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton @click="submitForm" :disabled="form.processing">
                        Confirm Update
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Location Update Modal -->
        <Modal :show="showLocationUpdateModal" @close="showLocationUpdateModal = false">
            <div class="p-6">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Update Your Location
                    </h2>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    You are about to update your current location in the system. This helps the logistics team and system track your progress and route.
                    <br>
                    <span class="font-semibold text-blue-700 dark:text-blue-300">
                        New Location:
                    </span>
                    <span class="font-medium">
                        {{ regions.find(r => r.id === driverRegionForm.region_id)?.name || 'Not selected' }}
                    </span>
                </p>
                
                <div class="space-y-4">
                    <label class="flex items-center cursor-pointer select-none">
                        <Checkbox v-model="driverRegionForm.update_packages" :checked="driverRegionForm.update_packages" class="mr-2" />
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            Also update my packages to this location
                        </span>
                    </label>
                    
                    <div v-if="driverRegionForm.update_packages" class="ml-6">
                        <label class="flex items-center cursor-pointer select-none">
                            <Checkbox v-model="driverRegionForm.only_in_transit" :checked="driverRegionForm.only_in_transit" class="mr-2" />
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                Only update packages that are "In Transit"
                            </span>
                        </label>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4 mt-6">
                    <SecondaryButton @click="showLocationUpdateModal = false">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton @click="updateDriverLocation">
                        Confirm Update
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </EmployeeLayout>
</template>

<style scoped>
.leaflet-container {
  height: 100%;
  width: 100%;
  z-index: 0;
}
.leaflet-control {
  z-index: 0 !important;
}
</style>