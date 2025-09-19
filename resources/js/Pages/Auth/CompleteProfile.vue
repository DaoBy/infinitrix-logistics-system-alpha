<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const props = defineProps({
    initialValues: Object
});

const showValidationErrors = ref(false);
const showWelcomeModal = ref(false);
const page = usePage();

// Check if we should show the welcome modal
onMounted(() => {
  if (page.props.flash?.show_modal) {
    showWelcomeModal.value = true;
  }
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

const validateForm = () => {
    let isValid = true;
    showValidationErrors.value = true;
    
    // Reset all errors
    form.clearErrors();
    
    // Validate required fields
    if (!form.first_name.trim()) {
        form.setError('first_name', 'First name is required');
        isValid = false;
    }
    
    if (!form.last_name.trim()) {
        form.setError('last_name', 'Last name is required');
        isValid = false;
    }
    
    if (!form.email.trim()) {
        form.setError('email', 'Email is required');
        isValid = false;
    } else if (!/\S+@\S+\.\S+/.test(form.email)) {
        form.setError('email', 'Email is invalid');
        isValid = false;
    }
    
    if (!form.mobile.trim()) {
        form.setError('mobile', 'Mobile number is required');
        isValid = false;
    } else if (!/^09\d{9}$/.test(form.mobile)) {
        form.setError('mobile', 'Please enter a valid mobile number (09XXXXXXXXX)');
        isValid = false;
    }
    
    // Validate phone format if provided
    if (form.phone && !/^\d{7,9}$/.test(form.phone)) {
        form.setError('phone', 'Please enter a valid phone number (7-9 digits)');
        isValid = false;
    }
    
    // Validate company name if company is selected
    if (form.customer_category === 'company' && !form.company_name.trim()) {
        form.setError('company_name', 'Company name is required');
        isValid = false;
    }
    
    // Validate ZIP code format if provided
    if (form.zip_code && !/^\d{4}$/.test(form.zip_code)) {
        form.setError('zip_code', 'ZIP code must be 4 digits');
        isValid = false;
    }
    
    return isValid;
};

const submit = () => {
    if (validateForm()) {
        form.post(route('profile.complete'), {
            onFinish: () => form.reset(),
        });
    }
};
</script>

<template>
    <GuestLayout>
        <Head title="Complete Your Profile" />
        
        <!-- Welcome Modal for incomplete profile -->
        <div v-if="showWelcomeModal" class="fixed inset-0 bg-gray-900 bg-opacity-30 backdrop-blur-sm flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-lg">
        <div class="text-center">
            <!-- Title -->
            <h3 class="text-lg font-medium text-gray-900 mb-2">Complete Your Profile</h3>
            
            <!-- Body -->
            <p class="text-gray-600 mb-4">
                Before you can request deliveries, we need a few more details to make sure your orders are secure and accurate.
            </p>
            
            <!-- Main CTA -->
            <button 
                @click="showWelcomeModal = false" 
                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 w-full"
            >
                Complete My Profile
            </button>
            
            <!-- Maybe Later -->
            <div class="mt-3">
                <a 
                    :href="route('customer.home')" 
                    class="text-sm text-gray-500 hover:text-gray-700 underline"
                >
                    Maybe Later
                </a>
            </div>
        </div>
    </div>
</div>

        
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
                        :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': showValidationErrors && !form.customer_category }"
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
                            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': showValidationErrors && !form.first_name }"
                            v-model="form.first_name"
                            required
                            autofocus
                            @input="showValidationErrors = false"
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
                            @input="showValidationErrors = false"
                        />
                        <InputError class="mt-1" :message="form.errors.middle_name" />
                    </div>
                    
                    <div>
                        <InputLabel for="last_name" value="Last Name *" />
                        <TextInput
                            id="last_name"
                            type="text"
                            class="mt-1 block w-full"
                            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': showValidationErrors && !form.last_name }"
                            v-model="form.last_name"
                            required
                            @input="showValidationErrors = false"
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
                            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': showValidationErrors && !form.company_name }"
                            v-model="form.company_name"
                            required
                            @input="showValidationErrors = false"
                        />
                        <InputError class="mt-1" :message="form.errors.company_name" />
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="space-y-4">
                    <div>
                        <InputLabel for="email" value="Email *" />
                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1 block w-full bg-gray-100"
                            :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': showValidationErrors && !form.email }"
                            v-model="form.email"
                            required
                            readonly
                        />
                        <InputError class="mt-1" :message="form.errors.email" />
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="mobile" value="Mobile Number *" />
                            <TextInput
                                id="mobile"
                                type="tel"
                                class="mt-1 block w-full"
                                :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': showValidationErrors && !form.mobile }"
                                v-model="form.mobile"
                                required
                                maxlength="11"
                                placeholder="09XXXXXXXXX"
                                @input="showValidationErrors = false"
                            />
                            <InputError class="mt-1" :message="form.errors.mobile" />
                        </div>
                        
                        <div>
                            <InputLabel for="phone" value="Phone Number (Optional)" />
                            <TextInput
                                id="phone"
                                type="tel"
                                class="mt-1 block w-full"
                                v-model="form.phone"
                                maxlength="9"
                                placeholder="XXXXXXXXX"
                                @input="showValidationErrors = false"
                            />
                            <InputError class="mt-1" :message="form.errors.phone" />
                        </div>
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
                                @input="showValidationErrors = false"
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
                                @input="showValidationErrors = false"
                            />
                            <InputError class="mt-1" :message="form.errors.street" />
                        </div>
                    </div>
                    
                    <!-- 2x2 Grid for Barangay, City, Province, ZIP Code -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-4">
                            <div>
                                <InputLabel for="barangay" value="Barangay" />
                                <TextInput
                                    id="barangay"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.barangay"
                                    @input="showValidationErrors = false"
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
                                    @input="showValidationErrors = false"
                                />
                                <InputError class="mt-1" :message="form.errors.city" />
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <InputLabel for="province" value="Province" />
                                <TextInput
                                    id="province"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.province"
                                    @input="showValidationErrors = false"
                                />
                                <InputError class="mt-1" :message="form.errors.province" />
                            </div>
                            
                            <div>
                                <InputLabel for="zip_code" value="ZIP Code" />
                                <TextInput
                                    id="zip_code"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.zip_code"
                                    maxlength="4"
                                    placeholder="XXXX"
                                    @input="showValidationErrors = false"
                                />
                                <InputError class="mt-1" :message="form.errors.zip_code" />
                            </div>
                        </div>
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

                <!-- Security Advisory Box -->
                <div class="bg-amber-50 p-4 rounded-lg border border-amber-200 text-center">
                    <h3 class="font-medium text-amber-800 mb-2">Profile Security Notice</h3>
                    <p class="text-sm text-amber-700 mb-2">
                        For security and accountability purposes, you won't be able to edit your profile 
                        information directly after submission.
                    </p>
                    <p class="text-sm text-amber-700">
                        If you need to update your details, please contact our support team who will 
                        assist you with the changes. This ensures all profile modifications are properly 
                        verified and recorded.
                    </p>
                </div>

                <!-- Required Fields Note -->
                <div class="text-sm text-gray-500">
                    <p class="text-center"><span class="text-red-500">*</span> indicates required fields</p>
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