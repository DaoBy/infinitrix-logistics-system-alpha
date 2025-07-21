<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthLogo from '@/Components/AuthLogo.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <AuthLayout>
        <Head title="Confirm Password" />
        
        <div class="max-w-md w-full mx-auto p-6 bg-white rounded-lg shadow-md">
            <AuthLogo />
            
            <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">Confirm Password</h1>
            
            <div class="mb-6 text-sm text-gray-600">
                This is a secure area of the application. Please confirm your password before continuing.
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <InputLabel for="password" value="Password" />
                    <TextInput
                        id="password"
                        type="password"
                        class="mt-1 block w-full"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        autofocus
                    />
                    <InputError class="mt-1" :message="form.errors.password" />
                </div>

                <div class="pt-4">
                    <PrimaryButton class="w-full justify-center" :disabled="form.processing">
                        Confirm
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AuthLayout>
</template>