<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const { props } = usePage();
const status = computed(() => props.value?.status || null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    console.log('Submitting form:', form.data());
    form.post(route('customer.register'), {
        onSuccess: () => {
            console.log('Registration successful');
            form.reset('password', 'password_confirmation');
        },
        onError: (errors) => {
            console.log('Registration errors:', errors);
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />
        
        <div class="max-w-md w-full mx-auto p-6 bg-white rounded-lg shadow-md">
            <div class="flex justify-center mb-">
                <img src="@/assets/logo.jpg" alt="Infinitrix Logo" class="h-36 w-auto" />
            </div>
            
            <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">Create a new account</h1>
            
            <div v-if="status" class="mb-4 p-3 bg-green-50 text-green-600 rounded">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <InputLabel for="name" value="Username" />
                    <TextInput
                        id="name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="username"
                    />
                    <InputError class="mt-1" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="email" value="Email" />
                    <TextInput
                        id="email"
                        type="email"
                        class="mt-1 block w-full"
                        v-model="form.email"
                        required
                        autocomplete="email"
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
                        Register
                    </PrimaryButton>
                </div>
            </form>
            
            <div class="mt-6 text-center text-sm text-gray-600">
                Already have an account? 
                <Link :href="route('login')" class="text-indigo-600 hover:text-indigo-500">
                    Login
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>