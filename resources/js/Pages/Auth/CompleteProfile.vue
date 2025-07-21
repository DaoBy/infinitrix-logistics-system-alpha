<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    initialValues: Object
});

const form = useForm({
    // Name fields
    first_name: props.initialValues.first_name || '',
    middle_name: props.initialValues.middle_name || '',
    last_name: props.initialValues.last_name || '',
    
    // Contact info
    email: props.initialValues.email || '',
    mobile: props.initialValues.mobile || '',
    phone: props.initialValues.phone || '',
    
    // Company info
    customer_category: props.initialValues.customer_category || 'individual',
    company_name: props.initialValues.company_name || '',
    
    // Address fields
    building_number: props.initialValues.building_number || '',
    street: props.initialValues.street || '',
    barangay: props.initialValues.barangay || '',
    city: props.initialValues.city || '',
    province: props.initialValues.province || '',
    zip_code: props.initialValues.zip_code || '',
});

const customerCategories = [
    { value: 'individual', label: 'Individual' },
    { value: 'company', label: 'Company' },
];

const submit = () => {
    form.post(route('profile.complete'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Complete Your Profile" />
        
        <div class="max-w-2xl w-full mx-auto p-6 bg-white rounded-lg shadow-md">
            <div class="mb-6">
                <div class="w-full bg-gray-200 rounded-full h-2.5 mb-4">
                    <div class="bg-green-600 h-2.5 rounded-full" style="width: 50%"></div>
                </div>
                
                <h1 class="text-2xl font-bold text-center text-gray-800 mb-2">Complete Your Profile</h1>
                <p class="text-gray-600 text-center">
                    Just a few more details to complete your account setup
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- Customer Type -->
                <div>
                    <InputLabel for="customer_category" value="Customer Type *" />
                    <SelectInput
                        id="customer_category"
                        class="mt-1 block w-full"
                        v-model="form.customer_category"
                        :options="customerCategories"
                        required
                    />
                    <InputError class="mt-1" :message="form.errors.customer_category" />
                </div>

                <!-- Name Fields -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <InputLabel for="first_name" value="First Name *" />
                        <TextInput
                            id="first_name"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.first_name"
                            required
                            autofocus
                        />
                        <InputError class="mt-1" :message="form.errors.first_name" />
                    </div>
                    
                    <div>
                        <InputLabel for="middle_name" value="Middle Name" />
                        <TextInput
                            id="middle_name"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.middle_name"
                        />
                        <InputError class="mt-1" :message="form.errors.middle_name" />
                    </div>
                    
                    <div>
                        <InputLabel for="last_name" value="Last Name *" />
                        <TextInput
                            id="last_name"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.last_name"
                            required
                        />
                        <InputError class="mt-1" :message="form.errors.last_name" />
                    </div>
                </div>

                <!-- Company Fields -->
                <div v-if="form.customer_category === 'company'" class="space-y-4">
                    <div>
                        <InputLabel for="company_name" value="Company Name *" />
                        <TextInput
                            id="company_name"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.company_name"
                            required
                        />
                        <InputError class="mt-1" :message="form.errors.company_name" />
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="email" value="Email *" />
                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1 block w-full bg-gray-100"
                            v-model="form.email"
                            required
                            readonly
                        />
                        <InputError class="mt-1" :message="form.errors.email" />
                    </div>
                    
                    <div>
                        <InputLabel for="mobile" value="Mobile Number *" />
                        <TextInput
                            id="mobile"
                            type="tel"
                            class="mt-1 block w-full"
                            v-model="form.mobile"
                            required
                            maxlength="11"
                            placeholder="09XXXXXXXXX"
                        />
                        <InputError class="mt-1" :message="form.errors.mobile" />
                    </div>
                    
                    <div>
                        <InputLabel for="phone" value="Phone Number" />
                        <TextInput
                            id="phone"
                            type="tel"
                            class="mt-1 block w-full"
                            v-model="form.phone"
                            maxlength="9"
                            placeholder="XXXXXXXXX"
                        />
                        <InputError class="mt-1" :message="form.errors.phone" />
                    </div>
                </div>

                <!-- Address Information -->
                <div class="space-y-4">
                    <h2 class="text-lg font-medium text-gray-800">Address Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="building_number" value="Building/House Number" />
                            <TextInput
                                id="building_number"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.building_number"
                            />
                            <InputError class="mt-1" :message="form.errors.building_number" />
                        </div>
                        
                        <div>
                            <InputLabel for="street" value="Street" />
                            <TextInput
                                id="street"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.street"
                            />
                            <InputError class="mt-1" :message="form.errors.street" />
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel for="barangay" value="Barangay" />
                            <TextInput
                                id="barangay"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.barangay"
                            />
                            <InputError class="mt-1" :message="form.errors.barangay" />
                        </div>
                        
                        <div>
                            <InputLabel for="city" value="City" />
                            <TextInput
                                id="city"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.city"
                            />
                            <InputError class="mt-1" :message="form.errors.city" />
                        </div>
                        
                        <div>
                            <InputLabel for="province" value="Province" />
                            <TextInput
                                id="province"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.province"
                            />
                            <InputError class="mt-1" :message="form.errors.province" />
                        </div>
                    </div>
                    
                    <div class="w-1/3">
                        <InputLabel for="zip_code" value="ZIP Code" />
                        <TextInput
                            id="zip_code"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.zip_code"
                            maxlength="4"
                            placeholder="XXXX"
                        />
                        <InputError class="mt-1" :message="form.errors.zip_code" />
                    </div>
                </div>

                <!-- Information Box -->
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 text-center">
                    <h3 class="font-medium text-blue-800 mb-2">Why we need this information?</h3>
                    <p class="text-sm text-blue-700">
                        We use your contact details to notify you about deliveries and your address 
                        information to ensure accurate package routing. Your name helps us verify 
                        your identity for secure transactions.
                    </p>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <PrimaryButton class="w-full justify-center" :disabled="form.processing">
                        Complete Profile
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>