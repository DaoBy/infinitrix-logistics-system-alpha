<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthLogo from '@/Components/AuthLogo.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.update'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthLayout>
        <Head title="Reset Password" />
        
        <div class="max-w-md w-full mx-auto p-6 bg-white rounded-lg shadow-md">
            <AuthLogo />
            
            <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">Create New Password</h1>
            
            <div v-if="errors.reset" class="mb-4 p-3 bg-red-50 text-red-600 rounded">
                {{ errors.reset }}
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
                        readonly
                    />
                </div>

                <div>
                    <InputLabel for="password" value="New Password" />
                    <TextInput
                        id="password"
                        type="password"
                        class="mt-1 block w-full"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                    />
                    <InputError class="mt-1" :message="form.errors.password" />
                </div>

                <div>
                    <InputLabel for="password_confirmation" value="Confirm Password" />
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        class="mt-1 block w-full"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                    />
                    <InputError class="mt-1" :message="form.errors.password_confirmation" />
                </div>

                <div class="pt-4">
                    <PrimaryButton class="w-full justify-center" :disabled="form.processing">
                        Reset Password
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AuthLayout>
</template>