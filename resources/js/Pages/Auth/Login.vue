<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthLogo from '@/Components/AuthLogo.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: '',
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthLayout>
        <Head title="Log in" />
        
        <div class="max-w-md w-full mx-auto p-6 bg-white rounded-lg shadow-md">
            <AuthLogo />
            
            <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">Welcome Back</h1>
            
            <!-- Error Messages -->
            <div v-if="errors.verification" class="mb-4 p-3 bg-red-50 text-red-600 rounded">
                {{ errors.verification }}
            </div>
            
            <div v-if="status" class="mb-4 p-3 bg-green-50 text-green-600 rounded">
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

                <div>
                    <InputLabel for="password" value="Password" />
                    <TextInput
                        id="password"
                        type="password"
                        class="mt-1 block w-full"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                    />
                    <InputError class="mt-1" :message="form.errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <Checkbox name="remember" v-model:checked="form.remember" />
                        <span class="ml-2 text-sm text-gray-600">Remember me</span>
                    </label>

                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-sm text-indigo-600 hover:text-indigo-500"
                    >
                        Forgot password?
                    </Link>
                </div>

                <div class="pt-4">
                    <PrimaryButton class="w-full justify-center" :disabled="form.processing">
                        Log in
                    </PrimaryButton>
                </div>
            </form>
            
            <div class="mt-6 text-center text-sm text-gray-600">
                Don't have an account? 
                <Link :href="route('customer.register')" class="text-indigo-600 hover:text-indigo-500">
                    Register
                </Link>
            </div>
        </div>
    </AuthLayout>
</template>