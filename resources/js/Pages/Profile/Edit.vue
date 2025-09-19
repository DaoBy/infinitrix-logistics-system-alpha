<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

// Props from the server (Laravel Inertia)
const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    auth: {
        type: Object,
        required: true,
    },
});

// Determine user's role (with fallback to 'guest')
const role = props.auth?.user?.role ?? 'guest';

// Select layout based on role
const getLayout = () => {
    if (role === 'customer') return GuestLayout; // Customer layout
    if (['admin', 'staff', 'collector', 'driver'].includes(role)) return EmployeeLayout; // Employee roles
};

const LayoutComponent = getLayout();
</script>

<template>
    <Head title="Account Settings" />

    <!-- Dynamic Layout -->
    <component :is="LayoutComponent">
        <div class="max-w-4xl w-full mx-auto p-6 bg-white rounded-lg shadow-md">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-center text-gray-800 mb-2">Account Settings</h1>
                <p class="text-gray-600 text-center">
                    Manage your account information and security settings
                </p>
            </div>

            <!-- Information Box -->
            <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 mb-6 text-center">
                <h3 class="font-medium text-blue-800 mb-2">Account Management</h3>
                <p class="text-sm text-blue-700">
                    This page allows you to update your account information. For delivery address changes, 
                    please use the separate delivery information update form which requires staff validation.
                </p>
            </div>

            <div class="space-y-6">
                <!-- Update Profile Information -->
                <div class="bg-white p-6 rounded-lg border border-gray-200">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                    />
                </div>

                <!-- Update Password -->
                <div class="bg-white p-6 rounded-lg border border-gray-200">
                    <UpdatePasswordForm />
                </div>

                <!-- Delete Account -->
                <div class="bg-white p-6 rounded-lg border border-gray-200">
                    <DeleteUserForm />
                </div>
            </div>
        </div>
    </component>
</template>