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
    <Head title="Profile" />

    <!-- Dynamic Layout -->
    <component :is="LayoutComponent">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200 text-center">
                Profile
            </h2>
        </template>

        <div class="py-12 flex items-center justify-center min-h-[80vh]">
            <div class="w-full max-w-4xl space-y-8 sm:px-6 lg:px-8">
                <!-- Update Profile Information -->
                <div class="bg-white p-6 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl mx-auto"
                    />
                </div>

                <!-- Update Password -->
                <div class="bg-white p-6 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                    <UpdatePasswordForm class="max-w-xl mx-auto" />
                </div>

                <!-- Delete User -->
                <div class="bg-white p-6 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
                    <DeleteUserForm class="max-w-xl mx-auto" />
                </div>
            </div>
        </div>
    </component>
</template>
