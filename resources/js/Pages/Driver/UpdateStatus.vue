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

const props = defineProps({
    packages: {
        type: Array,
        default: () => []
    },
    regions: {
        type: Array,
        default: () => []
    },
    statusOptions: {
        type: Object,
        default: () => ({})
    },
    canReturnToBase: {
        type: Boolean,
        default: false
    },
    hasPendingReturn: {
        type: Boolean,
        default: false
    },
    activeAssignmentId: {
        type: [Number, String, null],
        default: null
    },
    allPackagesFinal: { // <-- new prop from backend
        type: Boolean,
        default: false
    }
});

const showConfirmationModal = ref(false);
const selectedPackages = ref([]);
const selectedRegion = ref(null);

const form = useForm({
    package_ids: [],
    status: '',
    remarks: '',
    region_id: ''
});

const filteredPackages = computed(() => {
    return props.packages.filter(pkg => 
        selectedRegion.value ? pkg.current_region.id === selectedRegion.value : true
    );
});

const selectedPackagesDetails = computed(() => {
    return props.packages.filter(pkg => selectedPackages.value.includes(pkg.id));
});

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

const driverRegionForm = useForm({
    region_id: null,
    update_packages: false,
    only_in_transit: true
});

const showLocationUpdateModal = ref(false);

const openLocationUpdateModal = () => {
    showLocationUpdateModal.value = true;
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

// Show only regions relevant to the driver's route
const availableRegions = computed(() => {
    return props.regions;
});

// Show region name for selected region (for confirmation modal)
const selectedRegionName = computed(() => {
    const regionId = form.region_id || driverRegionForm.region_id;
    return props.regions.find(r => r.id == regionId)?.name || '';
});

// Show summary of auto-delivered/returned/manual updates in confirmation modal
const updateSummary = computed(() => {
    const summary = {};
    if (!form.status) return summary;
    selectedPackagesDetails.value.forEach(pkg => {
        if (form.region_id && pkg.deliveryRequest && pkg.deliveryRequest.drop_off_region_id == form.region_id) {
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

// Group packages by destination and current region
const groupedPackages = computed(() => {
    const groups = {};
    // Use only active packages (not delivered/returned)
    const pkgs = props.packages.filter(pkg => !pkg.is_final_status);
    pkgs.forEach(pkg => {
        const key = `${pkg.deliveryRequest.drop_off_region_id}_${pkg.current_region.id}`;
        if (!groups[key]) {
            // Defensive: ensure dropOffRegion is an object with a name property
            let destinationObj = pkg.deliveryRequest && pkg.deliveryRequest.dropOffRegion
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

// Confirm Return to Base
const localHasPendingReturn = ref(props.hasPendingReturn);

watch(() => props.hasPendingReturn, (val) => {
    localHasPendingReturn.value = val;
});

const confirmReturnToBase = () => {
    console.log('1 - Button clicked', props.activeAssignmentId);
    if (!props.activeAssignmentId) {
        console.error('No active assignment ID');
        return;
    }
    
    router.post(
        route('driver-truck-assignments.confirm-return', { assignment: props.activeAssignmentId }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                // Immediately update local state so the button disables without reload
                localHasPendingReturn.value = true;
                // Optionally reload for backend state, but UI updates instantly
                router.reload({
                    only: ['canReturnToBase', 'hasPendingReturn', 'activeAssignmentId', 'packages', 'allPackagesFinal'],
                    onFinish: () => {}
                });
            },
            onError: (errors) => {
                console.error('4 - Error:', errors);
                // Optionally show a UI error, but do not set usePage().props.flash manually
            }
        }
    );
};

// Add computed for current location (from packages if available)
const currentLocation = computed(() => {
    // Try to get the most common current_region among all packages
    if (!props.packages || props.packages.length === 0) return null;
    const regionCounts = {};
    props.packages.forEach(pkg => {
        if (pkg.current_region && pkg.current_region.name) {
            regionCounts[pkg.current_region.name] = (regionCounts[pkg.current_region.name] || 0) + 1;
        }
    });
    // Get the region name with the highest count
    let max = 0, region = null;
    for (const [name, count] of Object.entries(regionCounts)) {
        if (count > max) {
            max = count;
            region = name;
        }
    }
    return region;
});

// Make package row clickable for selection
function togglePackageSelection(pkgId) {
    const idx = selectedPackages.value.indexOf(pkgId);
    if (idx === -1) {
        selectedPackages.value.push(pkgId);
    } else {
        selectedPackages.value.splice(idx, 1);
    }
}
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
                    <!-- Current Location Indicator -->
                    <div v-if="currentLocation" class="flex items-center mt-1 text-sm text-gray-600 dark:text-gray-300">
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

        <!-- Flash messages (safe access) -->
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
                        <!-- Location Update Section -->
                        <div class="mb-8 p-4 bg-blue-50 rounded-lg dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <h3 class="text-base font-semibold text-blue-800 dark:text-blue-200">
                                    Update Your Current Location
                                </h3>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                <select
                                    v-model="driverRegionForm.region_id"
                                    class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                >
                                    <option value="">Select New Location</option>
                                    <option 
                                        v-for="region in regions" 
                                        :key="region.id" 
                                        :value="region.id"
                                    >
                                        {{ region.name }}
                                    </option>
                                </select>
                                <PrimaryButton 
                                    @click="openLocationUpdateModal"
                                    :disabled="!driverRegionForm.region_id"
                                >
                                    Update
                                </PrimaryButton>
                            </div>
                        </div>

                        <!-- Add Complete Trip/Return to Base Section -->
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
                            class="border rounded-lg dark:border-gray-700 overflow-hidden"
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
                            Driver location will be set to <span class="font-semibold">{{ selectedRegionName }}</span>
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
                <div class="mb-4 text-xs text-gray-500 dark:text-gray-400">
                    <ul class="list-disc ml-5">
                        <li>
                            <span class="font-semibold">Also update my packages to this location</span> will set the location of your packages to match your new location.
                        </li>
                        <li>
                            <span class="font-semibold">Only update packages that are "In Transit"</span> will only affect packages currently in transit.
                        </li>
                    </ul>
                </div>
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