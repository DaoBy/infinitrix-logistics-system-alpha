<template>
  <GuestLayout>
    <div class="max-w-4xl w-full mx-auto p-6 bg-white rounded-lg shadow-md">
      <!-- Success Modal -->
      <div v-if="showSuccessModal" class="fixed inset-0 bg-gray-900 bg-opacity-30 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-lg">
          <div class="text-center">
            <!-- Success Icon -->
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
              <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            
            <!-- Title -->
            <h3 class="text-lg font-medium text-gray-900 mb-2">Request Submitted Successfully</h3>
            
            <!-- Body -->
            <p class="text-gray-600 mb-4">
              Your delivery information update request has been submitted for approval. Our team will review your request and you'll be notified once it's processed.
            </p>
            
            <!-- Main CTA -->
            <button 
              @click="redirectToDashboard" 
              class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 w-full"
            >
              Return to Dashboard
            </button>
          </div>
        </div>
      </div>

      <div class="mb-6">
        <h1 class="text-2xl font-bold text-center text-gray-800 mb-2">Update Delivery Information</h1>
        <p class="text-gray-600 text-center">
          Submit changes to your delivery information for staff validation
        </p>
      </div>

      <!-- Profile Update Process Section -->
      <div class="bg-amber-50 p-4 rounded-lg border border-amber-200 mb-6 text-center">
        <h3 class="font-medium text-amber-800 mb-2">Delivery Information Update Process</h3>
        <p class="text-sm text-amber-700 mb-2">
          For security and delivery accuracy, changes to your delivery information require staff validation.
        </p>
        <p class="text-sm text-amber-700">
          Your request will be reviewed by our team. You'll receive a notification once your changes are approved. 
          This process ensures all delivery information modifications are properly verified for accurate package delivery.
        </p>
      </div>

      <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 mb-6 text-center">
        <h3 class="font-medium text-blue-800 mb-2">Note About Account Information</h3>
        <p class="text-sm text-blue-700">
          This form is for updating your delivery information only. To change your account settings 
          (password, email, login preferences), please visit your Account Settings page.
        </p>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Basic Information Section -->
        <div>
          <h2 class="text-lg font-medium text-gray-800 mb-4">Personal Information</h2>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <InputLabel for="first_name" value="First Name" />
              <TextInput
                id="first_name"
                type="text"
                class="mt-1 block w-full"
                v-model="form.first_name"
                :placeholder="customer.first_name || 'First name'"
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
                :placeholder="customer.middle_name || 'Middle name'"
              />
              <InputError class="mt-1" :message="form.errors.middle_name" />
            </div>
            
            <div>
              <InputLabel for="last_name" value="Last Name" />
              <TextInput
                id="last_name"
                type="text"
                class="mt-1 block w-full"
                v-model="form.last_name"
                :placeholder="customer.last_name || 'Last name'"
              />
              <InputError class="mt-1" :message="form.errors.last_name" />
            </div>
          </div>
        </div>

        <!-- Contact Information Section -->
        <div>
          <h2 class="text-lg font-medium text-gray-800 mb-4">Contact Information</h2>
          
          <div class="space-y-4">
            <div>
              <InputLabel for="email" value="Email" />
              <TextInput
                id="email"
                type="email"
                class="mt-1 block w-full"
                v-model="form.email"
                :placeholder="customer.email || 'Email address'"
              />
              <InputError class="mt-1" :message="form.errors.email" />
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <InputLabel for="mobile" value="Mobile Number" />
                <TextInput
                  id="mobile"
                  type="tel"
                  class="mt-1 block w-full"
                  v-model="form.mobile"
                  :placeholder="customer.mobile || '09XXXXXXXXX'"
                  maxlength="11"
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
                  :placeholder="customer.phone || 'XXXXXXXXX'"
                  maxlength="9"
                />
                <InputError class="mt-1" :message="form.errors.phone" />
              </div>
            </div>
          </div>
        </div>

        <!-- Address Information Section -->
        <div>
          <h2 class="text-lg font-medium text-gray-800 mb-4">Delivery Address</h2>
          
          <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <InputLabel for="building_number" value="Building/House Number" />
                <TextInput
                  id="building_number"
                  type="text"
                  class="mt-1 block w-full"
                  v-model="form.building_number"
                  :placeholder="customer.building_number || 'Building number'"
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
                  :placeholder="customer.street || 'Street name'"
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
                    :placeholder="customer.barangay || 'Barangay'"
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
                    :placeholder="customer.city || 'City'"
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
                    :placeholder="customer.province || 'Province'"
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
                    :placeholder="customer.zip_code || 'XXXX'"
                    maxlength="4"
                  />
                  <InputError class="mt-1" :message="form.errors.zip_code" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Reason Section -->
        <div>
          <h2 class="text-lg font-medium text-gray-800 mb-4">Reason for Changes</h2>
          
          <div>
            <InputLabel for="reason" value="Please explain why you need to update your delivery information *" />
            <textarea
              id="reason"
              rows="4"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              v-model="form.reason"
              required
              placeholder="Please provide a detailed explanation for the changes to your delivery information"
            ></textarea>
            <InputError class="mt-1" :message="form.errors.reason" />
          </div>
        </div>

        <!-- Required Fields Note -->
        <div class="text-sm text-gray-500">
          <p class="text-center"><span class="text-red-500">*</span> indicates required fields</p>
        </div>

        <!-- Buttons -->
        <div class="flex justify-end space-x-4 pt-2">
        
          <PrimaryButton type="submit" :disabled="form.processing">
            Submit Request
          </PrimaryButton>
        </div>
      </form>
    </div>
  </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  customer: Object,
});

const showSuccessModal = ref(false);

const form = useForm({
  first_name: '',
  middle_name: '',
  last_name: '',
  email: '',
  mobile: '',
  phone: '',
  building_number: '',
  street: '',
  barangay: '',
  city: '',
  province: '',
  zip_code: '',
  reason: '',
});

const submit = () => {
  form.post(route('customer.profile-update.store'), {
    onSuccess: () => {
      showSuccessModal.value = true;
    }
  });
};

const redirectToDashboard = () => {
  showSuccessModal.value = false;
  window.location.href = route('customer.dashboard');
};
</script>