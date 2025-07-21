<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthLogo from '@/Components/AuthLogo.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: String,
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    code: '',
});

const submit = () => {
    form.post(route('verification.code.verify'), {
        onFinish: () => form.reset('code'),
    });
};
</script>

<template>
    <AuthLayout>
        <Head title="Enter Verification Code" />
        <div class="max-w-md w-full mx-auto p-6 bg-white rounded-lg shadow-md">
            <AuthLogo />
            <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">Verify Your Email</h1>
            <div class="mb-4 text-gray-600 text-sm">
                Please enter the 6-digit code sent to your email address.
            </div>
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <InputLabel for="code" value="Verification Code" />
                    <TextInput
                        id="code"
                        type="text"
                        class="mt-1 block w-full text-center tracking-widest text-2xl"
                        v-model="form.code"
                        maxlength="6"
                        required
                        autofocus
                        autocomplete="one-time-code"
                    />
                    <InputError class="mt-1" :message="form.errors.code" />
                </div>
                <div class="pt-4">
                    <PrimaryButton class="w-full justify-center" :disabled="form.processing">
                        Verify
                    </PrimaryButton>
                </div>
            </form>
            <div class="mt-4 text-center text-sm text-gray-600">
                Didn't receive the code?
                <form @submit.prevent="() => $inertia.post(route('verification.send'))" class="inline">
                    <button type="submit" class="text-indigo-600 hover:underline ml-1">Resend Code</button>
                </form>
            </div>
            <div v-if="status" class="mt-4 p-3 bg-green-50 text-green-600 rounded">
                {{ status }}
            </div>
        </div>
    </AuthLayout>
</template>