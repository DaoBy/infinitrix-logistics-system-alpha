<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import AuthLogo from '@/Components/AuthLogo.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const profileComplete = computed(() => usePage().props.auth.user?.customer?.first_name);
</script>

<template>
    <AuthLayout>
        <Head title="Email Verified" />
        
        <div class="max-w-md w-full mx-auto p-6 bg-white rounded-lg shadow-md text-center">
            <AuthLogo />
            
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Email Verified!</h1>
                
                <template v-if="!profileComplete">
                    <div class="mb-4">
                        <div class="w-full bg-gray-200 rounded-full h-2.5 mb-2">
                            <div class="bg-green-600 h-2.5 rounded-full" style="width: 50%"></div>
                        </div>
                        <p class="text-sm text-gray-600">Profile completion: 50%</p>
                    </div>
                    
                    <p class="text-gray-600 mb-4">
                        You're almost there! Please complete your profile to access all features.
                    </p>
                    
                 <Link :href="route('profile.complete')" class="block mb-4">
                    <PrimaryButton class="w-full justify-center bg-green-600 hover:bg-green-700 border-green-700">
                        Complete Your Profile
                    </PrimaryButton>
                </Link>
                </template>
                
                <template v-else>
                    <p class="text-gray-600 mb-4">
                        Your email has been successfully verified. You can now access all features.
                    </p>
                    
                    <Link 
                        :href="route('customer.home')"
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
                    >
                        Go to Home Page
                    </Link>
                </template>
            </div>
        </div>
    </AuthLayout>
</template>