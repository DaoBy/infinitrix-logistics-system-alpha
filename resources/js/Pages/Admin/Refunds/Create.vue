<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, watch, computed } from 'vue';

const props = defineProps({
    deliveryRequest: Object,
    reasonOptions: Object,
});

const searchQuery = ref('');
const searchResults = ref([]);
const selectedDeliveryRequest = ref(props.deliveryRequest);
const selectedPackages = ref([]);
const maxRefundable = ref(0);
const isSearching = ref(false);
const isCalculating = ref(false);
const searchTimeout = ref(null);

const form = useForm({
    delivery_request_id: null,
    refund_amount: 0,
    reason: '',
    description: '',
    refunded_packages: [],
    notes: '',
});

// Computed properties for dynamic labels
const requestType = computed(() => {
    if (!selectedDeliveryRequest.value) return 'refund';
    return selectedDeliveryRequest.value.payment_type === 'prepaid' ? 'refund' : 'adjustment';
});

const typeLabels = computed(() => {
    return {
        refund: {
            title: 'Create Refund',
            description: 'Process a refund for a prepaid delivery',
            amountLabel: 'Refund Amount *',
            maxLabel: 'Maximum Refundable',
            button: 'Process Refund',
            success: 'Refund processed successfully'
        },
        adjustment: {
            title: 'Create Invoice Adjustment',
            description: 'Adjust invoice amount for a postpaid delivery',
            amountLabel: 'Adjustment Amount *',
            maxLabel: 'Maximum Adjustable',
            button: 'Apply Adjustment',
            success: 'Invoice adjustment applied successfully'
        }
    }[requestType.value];
});

const newAmountDue = computed(() => {
    if (!selectedDeliveryRequest.value || requestType.value !== 'adjustment') return 0;
    const adjustmentAmount = Number(form.refund_amount) || 0;
    return Math.max(0, selectedDeliveryRequest.value.total_price - adjustmentAmount);
});

// Simple fetch wrapper with error handling
const apiFetch = async (url, options = {}) => {
    try {
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...options.headers,
            },
            ...options,
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    } catch (error) {
        console.error('API fetch error:', error);
        throw error;
    }
};

// Search delivery requests with debounce
const searchDeliveryRequests = async () => {
    if (!searchQuery.value.trim()) {
        searchResults.value = [];
        return;
    }

    isSearching.value = true;
    
    try {
        const data = await apiFetch(route('refunds.search-delivery-requests', {
            search: searchQuery.value
        }));
        
        console.log('Search results:', data);
        searchResults.value = data;
    } catch (error) {
        console.error('Search failed:', error);
        searchResults.value = [];
        alert('Search failed. Please try again.');
    } finally {
        isSearching.value = false;
    }
};

// Debounced search
watch(searchQuery, (newValue) => {
    if (searchTimeout.value) {
        clearTimeout(searchTimeout.value);
    }
    
    searchTimeout.value = setTimeout(() => {
        if (newValue.trim()) {
            searchDeliveryRequests();
        } else {
            searchResults.value = [];
        }
    }, 500);
});

// Select delivery request
const selectDeliveryRequest = (request) => {
    console.log('Selected request:', request);
    selectedDeliveryRequest.value = request;
    form.delivery_request_id = request.id;
    searchResults.value = [];
    searchQuery.value = request.reference_number;
    
    // Reset max refundable and calculate
    maxRefundable.value = 0;
    form.refund_amount = 0;
    calculateMaxRefundable();
};

// Calculate max refundable amount using GET
const calculateMaxRefundable = async () => {
    if (!selectedDeliveryRequest.value) {
        console.log('No delivery request selected');
        maxRefundable.value = 0;
        return;
    }

    console.log('Calculating max refundable for:', selectedDeliveryRequest.value.id);
    console.log('Selected packages:', selectedPackages.value);

    isCalculating.value = true;

    try {
        // Build query parameters for GET request
        const params = new URLSearchParams({
            delivery_request_id: selectedDeliveryRequest.value.id,
        });

        // Add package_ids as JSON string if any packages are selected
        if (selectedPackages.value.length > 0) {
            params.append('package_ids', JSON.stringify(selectedPackages.value));
        }

        const url = `${route('refunds.calculate-max-refund')}?${params.toString()}`;
        console.log('Calculation URL:', url);

        const data = await apiFetch(url);
        
        console.log('Max refundable calculation result:', data);
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        maxRefundable.value = Number(data.max_refundable) || 0;
        form.refund_amount = Math.min(Number(form.refund_amount) || 0, maxRefundable.value);
        
        console.log('Max refundable set to:', maxRefundable.value);
        
    } catch (error) {
        console.error('Calculation failed:', error);
        fallbackCalculation();
    } finally {
        isCalculating.value = false;
    }
};

// Fallback calculation if API fails
const fallbackCalculation = () => {
    if (!selectedDeliveryRequest.value) {
        maxRefundable.value = 0;
        return;
    }
    
    console.log('Using fallback calculation');
    
    // Base: Actual Delivery Cost
    let calculatedMax = Number(selectedDeliveryRequest.value.total_price) || 0;
    
    if (selectedPackages.value.length > 0 && selectedDeliveryRequest.value.packages) {
        // Add: Total Value of Selected Packages
        let selectedPackagesValue = 0;
        selectedDeliveryRequest.value.packages.forEach(pkg => {
            if (selectedPackages.value.includes(pkg.id)) {
                selectedPackagesValue += Number(pkg.value) || 0;
            }
        });
        
        calculatedMax += selectedPackagesValue;
    }
    
    maxRefundable.value = calculatedMax;
    form.refund_amount = Math.min(Number(form.refund_amount) || 0, calculatedMax);
    
    console.log('Fallback calculation result:', calculatedMax);
};

// Toggle package selection
const togglePackage = (packageId) => {
    const index = selectedPackages.value.indexOf(packageId);
    if (index > -1) {
        selectedPackages.value.splice(index, 1);
    } else {
        selectedPackages.value.push(packageId);
    }
    form.refunded_packages = selectedPackages.value;
    
    console.log('Packages after toggle:', selectedPackages.value);
    calculateMaxRefundable();
};

// Watch refund amount for validation
watch(() => form.refund_amount, (newValue) => {
    const numValue = Number(newValue) || 0;
    if (numValue > maxRefundable.value) {
        form.refund_amount = maxRefundable.value;
    }
});

// Format currency helper
const formatCurrency = (amount) => {
    const num = Number(amount) || 0;
    return '₱' + num.toFixed(2);
};

// Submit form using Inertia (this is correct for form submission)
const submit = () => {
    if (!selectedDeliveryRequest.value) {
        alert('Please select a delivery request first.');
        return;
    }
    
    const refundAmount = Number(form.refund_amount) || 0;
    
    if (refundAmount <= 0) {
        alert('Please enter a valid amount.');
        return;
    }
    
    if (refundAmount > maxRefundable.value) {
        alert(`Amount cannot exceed maximum ${requestType.value === 'adjustment' ? 'adjustable' : 'refundable'} amount: ${formatCurrency(maxRefundable.value)}`);
        return;
    }
    
    if (requestType.value === 'adjustment' && newAmountDue.value < 0) {
        alert('Adjustment cannot result in negative amount due.');
        return;
    }
    
    form.post(route('refunds.store'), {
        preserveScroll: true,
        onSuccess: () => {
            router.visit(route('refunds.index'));
        },
        onError: (errors) => {
            console.error('Form errors:', errors);
            alert('There were errors with your submission. Please check the form.');
        }
    });
};

// Clear selection
const clearSelection = () => {
    selectedDeliveryRequest.value = null;
    form.delivery_request_id = null;
    selectedPackages.value = [];
    form.refunded_packages = [];
    searchQuery.value = '';
    maxRefundable.value = 0;
    form.refund_amount = 0;
};

onMounted(() => {
    if (props.deliveryRequest) {
        selectDeliveryRequest(props.deliveryRequest);
    }
});
</script>

<template>
    <Head :title="typeLabels?.title || 'Create Refund/Adjustment'" />

    <EmployeeLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ typeLabels?.title || 'Create Refund/Adjustment' }}</h1>
                    <p class="text-gray-600 mt-1">{{ typeLabels?.description || 'Process a refund or adjustment for a completed delivery' }}</p>
                </div>
                <Link 
                    :href="route('refunds.index')" 
                    class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors"
                >
                    Back to List
                </Link>
            </div>
        </template>

        <div class="max-w-4xl mx-auto">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Delivery Request Selection -->
                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">1. Select Delivery Request</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Search Delivery Request
                            </label>
                            <div class="relative">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Enter reference number (e.g., INF-2025-000019) or sender name..."
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                />
                                <div v-if="isSearching" class="absolute right-3 top-2">
                                    <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div>
                                </div>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                Search by reference number or sender name. Only completed, eligible deliveries are shown.
                            </div>
                            
                            <!-- Search Results -->
                            <div v-if="searchResults.length > 0" class="mt-2 border border-gray-200 rounded-md max-h-60 overflow-y-auto">
                                <div
                                    v-for="request in searchResults"
                                    :key="request.id"
                                    @click="selectDeliveryRequest(request)"
                                    class="p-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer"
                                >
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="font-medium text-blue-600">{{ request.reference_number }}</div>
                                            <div class="text-sm text-gray-600">
                                                Sender: {{ request.sender?.name }} | 
                                                Amount: {{ formatCurrency(request.total_price) }} |
                                                Type: {{ request.payment_type === 'prepaid' ? 'Prepaid' : 'Postpaid' }}
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                Status: {{ request.status }} | Payment: {{ request.payment_status }}
                                            </div>
                                        </div>
                                        <span :class="request.payment_type === 'prepaid' ? 'bg-purple-100 text-purple-800' : 'bg-orange-100 text-orange-800'" 
                                              class="inline-flex px-2 py-1 text-xs font-medium rounded-full">
                                            {{ request.payment_type === 'prepaid' ? 'Refund' : 'Adjustment' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- No Results -->
                            <div v-if="searchQuery && !isSearching && searchResults.length === 0" class="mt-2 text-sm text-orange-600 bg-orange-50 p-2 rounded">
                                No eligible delivery requests found for "{{ searchQuery }}". Make sure:
                                <ul class="list-disc list-inside mt-1">
                                    <li>The reference number is correct</li>
                                    <li>The delivery is completed</li>
                                    <li>Prepaid: Payment is verified and paid</li>
                                    <li>Postpaid: Payment status is "requires_adjustment"</li>
                                    <li>No refund/adjustment already exists</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Selected Delivery Request -->
                        <div v-if="selectedDeliveryRequest" class="bg-green-50 p-4 rounded-md border border-green-200">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <h3 class="font-medium text-green-900">Selected Delivery Request:</h3>
                                        <span :class="requestType === 'refund' ? 'bg-purple-100 text-purple-800' : 'bg-orange-100 text-orange-800'" 
                                              class="inline-flex px-2 py-1 text-xs font-medium rounded-full">
                                            {{ requestType === 'refund' ? 'Refund' : 'Adjustment' }}
                                        </span>
                                        <div v-if="isCalculating" class="flex items-center gap-1">
                                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-green-600"></div>
                                            <span class="text-xs text-green-600">Calculating...</span>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                        <div><strong>Reference:</strong> {{ selectedDeliveryRequest.reference_number }}</div>
                                        <div><strong>Sender:</strong> {{ selectedDeliveryRequest.sender?.name }}</div>
                                        <div><strong>Total Amount:</strong> {{ formatCurrency(selectedDeliveryRequest.total_price) }}</div>
                                        <div><strong>Payment Type:</strong> <span class="capitalize">{{ selectedDeliveryRequest.payment_type }}</span></div>
                                        <div><strong>Payment Status:</strong> <span class="capitalize">{{ selectedDeliveryRequest.payment_status }}</span></div>
                                        <div><strong>Packages:</strong> {{ selectedDeliveryRequest.packages?.length || 0 }} items</div>
                                        <div><strong>{{ typeLabels?.maxLabel }}:</strong> {{ formatCurrency(maxRefundable) }}</div>
                                        <div v-if="requestType === 'adjustment'"><strong>New Amount Due:</strong> {{ formatCurrency(newAmountDue) }}</div>
                                    </div>
                                </div>
                                <button
                                    @click="clearSelection"
                                    type="button"
                                    class="text-red-600 hover:text-red-800 text-sm whitespace-nowrap ml-4"
                                >
                                    Change
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Package Selection (Optional) -->
                <div v-if="selectedDeliveryRequest && selectedDeliveryRequest.packages && selectedDeliveryRequest.packages.length > 0" class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">2. Select Packages to {{ requestType === 'adjustment' ? 'Adjust' : 'Refund' }} (Optional)</h2>
                    
                    <div class="space-y-3">
                        <div
                            v-for="pkg in selectedDeliveryRequest.packages"
                            :key="pkg.id"
                            @click="togglePackage(pkg.id)"
                            :class="{
                                'bg-blue-50 border-blue-200': selectedPackages.includes(pkg.id),
                                'bg-gray-50 border-gray-200': !selectedPackages.includes(pkg.id)
                            }"
                            class="p-3 border rounded-md cursor-pointer transition-colors"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="font-medium">{{ pkg.item_name }}</div>
                                    <div class="text-sm text-gray-600">
                                        Value: {{ formatCurrency(pkg.value) }} | 
                                        Category: {{ pkg.category }} |
                                        Weight: {{ pkg.weight }}kg
                                    </div>
                                    <div v-if="pkg.description" class="text-xs text-gray-500 mt-1">
                                        {{ pkg.description }}
                                    </div>
                                </div>
                                <div class="flex items-center ml-4">
                                    <input
                                        type="checkbox"
                                        :checked="selectedPackages.includes(pkg.id)"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-5 w-5"
                                        readonly
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 text-sm text-gray-600">
                        <strong>Note:</strong> Leave all packages unselected to {{ requestType === 'adjustment' ? 'adjust' : 'refund' }} the entire delivery request amount ({{ formatCurrency(selectedDeliveryRequest.total_price) }})
                    </div>
                    
                    <div v-if="selectedPackages.length > 0" class="mt-3 p-3 bg-blue-50 rounded-md">
                        <div class="text-sm text-blue-800">
                            Selected {{ selectedPackages.length }} package(s) for {{ requestType === 'adjustment' ? 'adjustment' : 'refund' }}
                        </div>
                    </div>
                </div>

                <!-- Refund/Adjustment Details -->
                <div v-if="selectedDeliveryRequest" class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">3. {{ requestType === 'adjustment' ? 'Adjustment' : 'Refund' }} Details</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Amount -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                {{ typeLabels?.amountLabel }}
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-2 text-gray-500">₱</span>
                                <input
                                    v-model="form.refund_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    :max="maxRefundable"
                                    required
                                    class="w-full border border-gray-300 rounded-md pl-8 pr-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                />
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                Maximum {{ requestType === 'adjustment' ? 'adjustable' : 'refundable' }}: {{ formatCurrency(maxRefundable) }}
                            </div>
                            <div v-if="requestType === 'adjustment'" class="text-xs text-blue-600 mt-1">
                                New amount due: {{ formatCurrency(newAmountDue) }}
                            </div>
                            <div v-if="form.errors.refund_amount" class="text-red-600 text-xs mt-1">
                                {{ form.errors.refund_amount }}
                            </div>
                        </div>

                        <!-- Refund Reason -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Reason *
                            </label>
                            <select
                                v-model="form.reason"
                                required
                                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="">Select a reason</option>
                                <option v-for="(label, value) in reasonOptions" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                            <div v-if="form.errors.reason" class="text-red-600 text-xs mt-1">
                                {{ form.errors.reason }}
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Description *
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            :placeholder="requestType === 'adjustment' ? 'Provide detailed explanation for the invoice adjustment...' : 'Provide detailed explanation for the refund...'"
                            required
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        ></textarea>
                        <div class="text-xs text-gray-500 mt-1">
                            Be specific about what went wrong and why the {{ requestType === 'adjustment' ? 'adjustment' : 'refund' }} is necessary.
                        </div>
                        <div v-if="form.errors.description" class="text-red-600 text-xs mt-1">
                            {{ form.errors.description }}
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Internal Notes (Optional)
                        </label>
                        <textarea
                            v-model="form.notes"
                            rows="2"
                            placeholder="Any additional internal notes..."
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        ></textarea>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-4">
                    <Link
                        :href="route('refunds.index')"
                        class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing || !selectedDeliveryRequest"
                        :class="{
                            'bg-blue-600 hover:bg-blue-700': !form.processing && selectedDeliveryRequest,
                            'bg-blue-400 cursor-not-allowed': form.processing || !selectedDeliveryRequest
                        }"
                        class="text-white px-6 py-2 rounded-lg transition-colors"
                    >
                        {{ form.processing ? 'Processing...' : (typeLabels?.button || 'Process') }}
                    </button>
                </div>
            </form>
        </div>
    </EmployeeLayout>
</template>