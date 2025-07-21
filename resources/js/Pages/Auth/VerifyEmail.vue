<script setup>
import { computed } from 'vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthLogo from '@/Components/AuthLogo.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <AuthLayout>
        <Head title="Email Verification" />
        
        <div class="max-w-md w-full mx-auto p-6 bg-white rounded-lg shadow-md">
            <AuthLogo />
            
            <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">Verify Your Email</h1>
            
            <div v-if="errors.verification" class="mb-4 p-3 bg-red-50 text-red-600 rounded">
                {{ errors.verification }}
            </div>
            
            <div class="mb-6 text-sm text-gray-600">
                Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we emailed to you?
            </div>

            <div v-if="verificationLinkSent" class="mb-6 p-3 bg-green-50 text-green-600 rounded">
                A new verification link has been sent to your email address.
            </div>

          <form @submit.prevent="submit" class="flex justify-center w-full">
                <PrimaryButton :disabled="form.processing">
                    Resend Verification Email
                </PrimaryButton>
            </form>
        </div>
    </AuthLayout>
</template>