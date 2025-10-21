<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { ref, onMounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const trackingCode = ref('')
const loading = ref(false)
const page = usePage()

// Check if there's an error passed from the controller
onMounted(() => {
    if (page.props.error) {
        // If there's an error from controller, pre-fill the searched code
        trackingCode.value = page.props.searchedCode || ''
    }
})

const trackPackage = async () => {
    if (!trackingCode.value.trim()) {
        return
    }

    loading.value = true

    try {
        // Just navigate to the tracking page - let the controller handle validation
        router.visit(`/tracking/${trackingCode.value.trim()}`)
    } catch (err) {
        console.error('Tracking error:', err)
    } finally {
        loading.value = false
    }
}
</script>

<template>
    <GuestLayout>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Main Card -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl">
                    <div class="px-10 py-8">
                        <!-- Header -->
                        <div class="text-center mb-8">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <h1 class="text-3xl font-bold text-gray-900 mb-2">Track Your Package</h1>
                            <p class="text-gray-600">
                                Enter your tracking code to check real-time delivery status and updates
                            </p>
                        </div>

                        <!-- Tracking Form -->
                        <div class="max-w-2xl mx-auto">
                            <form @submit.prevent="trackPackage" class="space-y-5">
                                <div>
                                    <label for="trackingCode" class="block text-sm font-semibold text-gray-700 mb-2">
                                        Tracking Code
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                            </svg>
                                        </div>
                                        <input
                                            id="trackingCode"
                                            v-model="trackingCode"
                                            type="text"
                                            required
                                            placeholder="Enter tracking code (e.g., EGGA66 or PKG-10101611-EGGA66)"
                                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 text-gray-900 placeholder-gray-500"
                                        />
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    :disabled="loading"
                                    class="w-full bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed font-semibold shadow-md hover:shadow-lg"
                                >
                                    <div class="flex items-center justify-center space-x-2">
                                        <svg v-if="loading" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <svg v-else class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                        <span class="text-base">{{ loading ? 'Tracking...' : 'Track Package' }}</span>
                                    </div>
                                </button>
                            </form>

                            <!-- Error Message from Controller -->
                            <div v-if="page.props.error" class="mt-5 p-4 bg-red-50 border border-red-200 rounded-lg">
                                <div class="flex items-start space-x-3">
                                    <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <div>
                                        <p class="text-red-800 font-medium text-sm">Unable to track package</p>
                                        <p class="text-red-700 text-sm mt-1">{{ page.props.error }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Help Section -->
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <div class="flex items-start space-x-3">
                                    <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <div>
                                        <p class="text-sm text-gray-600">
                                            Can't find your tracking code? Check your email confirmation or 
                                            <a :href="route('contact.us')" class="text-green-600 hover:text-green-700 font-medium transition-colors duration-200">
                                                contact our support team
                                            </a>.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                
            </div>
        </div>
    </GuestLayout>
</template>