<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;
const showVerification = ref(false);
const emailChanged = ref(false);
const verificationCode = ref('');
const verifying = ref(false);

const form = useForm({
    name: user.name,
    email: user.email,
});

// Check if there's a success message from email verification
const successMessage = computed(() => usePage().props.flash.status);

// Watch for email changes
watch(() => form.email, (newEmail, oldEmail) => {
    if (newEmail !== oldEmail && newEmail !== user.email) {
        emailChanged.value = true;
    } else {
        emailChanged.value = false;
    }
});

const submit = () => {
    if (emailChanged.value) {
        // If email changed, we need to verify
        form.patch(route('profile.update.with-verification'), {
            onSuccess: () => {
                showVerification.value = true;
            }
        });
    } else {
        // Regular update
        form.patch(route('profile.update'));
    }
};

const verifyEmailChange = () => {
    verifying.value = true;
    
    // Use the SAME verification endpoint as the standard email verification
    router.post(route('verification.code.verify'), {
        code: verificationCode.value
    }, {
        onSuccess: () => {
            // The verification controller will handle redirect back to profile
        },
        onError: (errors) => {
            if (errors.code) {
                form.errors.code = errors.code;
            }
        },
        onFinish: () => {
            verifying.value = false;
        }
    });
};
</script>

<template>
    <section>
        <header class="mb-6">
            <h2 class="text-lg font-medium text-gray-900">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Update your account's profile information and email address.
            </p>
        </header>

        <!-- Success message for email verification -->
        <div v-if="successMessage" class="mb-6 p-4 bg-green-50 rounded-lg border border-green-200">
            <p class="text-green-800">{{ successMessage }}</p>
        </div>

        <div v-if="showVerification" class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <h3 class="font-medium text-blue-800 mb-2">Email Verification Required</h3>
            <p class="text-sm text-blue-700 mb-3">
                We've sent a verification code to your new email address. Please check your inbox and enter the code below to complete the email change.
            </p>
            
            <form @submit.prevent="verifyEmailChange" class="flex items-end gap-2">
                <div class="flex-1">
                    <InputLabel for="verificationCode" value="Verification Code" />
                    <TextInput
                        id="verificationCode"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="verificationCode"
                        maxlength="6"
                        required
                        autofocus
                        placeholder="Enter 6-digit code"
                    />
                    <InputError class="mt-1" :message="form.errors.code" />
                </div>
                <PrimaryButton type="submit" :disabled="verifying">
                    {{ verifying ? 'Verifying...' : 'Verify' }}
                </PrimaryButton>
            </form>
            
            <p class="mt-3 text-sm text-blue-600">
                Didn't receive the code?
                <button @click="$inertia.post(route('verification.send'))" class="underline ml-1">
                    Resend Code
                </button>
            </p>
        </div>

        <!-- Rest of the form remains the same -->
        <form
            @submit.prevent="submit"
            class="space-y-4"
        >
            <div>
                <InputLabel for="name" value="Name *" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-1" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email *" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    :disabled="showVerification || user.pending_email"
                />
                <InputError class="mt-1" :message="form.errors.email" />
                
                <p v-if="user.pending_email" class="mt-2 text-sm text-amber-600">
                    You have a pending email change to: {{ user.pending_email }}. 
                    <button @click="$inertia.post(route('verification.send'))" class="text-blue-600 underline">
                        Resend verification code
                    </button>
                </p>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null && !emailChanged">
                <p class="mt-2 text-sm text-gray-800">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-blue-600 underline hover:text-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <PrimaryButton :disabled="form.processing || (user.pending_email && !showVerification)">
                    {{ emailChanged ? 'Send Verification Code' : 'Save Changes' }}
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>