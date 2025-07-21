<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthLogo from '@/Components/AuthLogo.vue';
import { Link } from '@inertiajs/vue3';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <AuthLayout>
        <Head title="Forgot Password" />
        
        <div class="max-w-md w-full mx-auto p-6 bg-white rounded-lg shadow-md">
            <AuthLogo />
            
            <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">Reset Password</h1>
            
            <div class="mb-6 text-sm text-gray-600">
                Forgot your password? No problem. Just enter your email address and we'll email you a password reset link.
            </div>

            <div v-if="status" class="mb-6 p-3 bg-green-50 text-green-600 rounded">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <InputLabel for="email" value="Email" />
                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                    />
                    <InputError class="mt-1" :message="form.errors.email" />
                </div>

                <div class="pt-4">
                    <PrimaryButton class="w-full justify-center" :disabled="form.processing">
                        Email Password Reset Link
                    </PrimaryButton>
                </div>
            </form>
            
            <div class="mt-6 text-center text-sm text-gray-600">
                Remember your password? 
                <Link :href="route('login')" class="text-indigo-600 hover:text-indigo-500">
                    Login
                </Link>
            </div>
        </div>
    </AuthLayout>
</template>