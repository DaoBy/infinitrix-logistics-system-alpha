<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const page = usePage();

// Form for contact submission
const form = useForm({
  name: '',
  email: '',
  subject: '',
  message: '',
});

// Notification states
const showSuccess = ref(false);
const showError = ref(false);
const notificationMessage = ref('');

// Handle form submission
const submitForm = () => {
  form.post(route('contact.submit'), {
    onSuccess: () => {
      form.reset();
      showSuccessNotification('Your message has been sent successfully! We will get back to you soon.');
    },
    onError: () => {
      showErrorNotification('Sorry, there was an error sending your message. Please try again.');
    },
  });
};

// Notification functions
const showSuccessNotification = (message) => {
  notificationMessage.value = message;
  showSuccess.value = true;
  showError.value = false;
  
  // Auto hide after 5 seconds
  setTimeout(() => {
    showSuccess.value = false;
  }, 5000);
};

const showErrorNotification = (message) => {
  notificationMessage.value = message;
  showError.value = true;
  showSuccess.value = false;
  
  // Auto hide after 5 seconds
  setTimeout(() => {
    showError.value = false;
  }, 5000);
};

// Manual close function
const closeNotification = () => {
  showSuccess.value = false;
  showError.value = false;
};

// Watch for flash messages from server
watch(() => page.props.flash, (flash) => {
  if (flash.success) {
    showSuccessNotification(flash.success);
  }
  if (flash.error) {
    showErrorNotification(flash.error);
  }
}, { deep: true });
</script>

<template>
  <GuestLayout>
    <!-- Success Notification -->
    <div 
      v-if="showSuccess" 
      class="fixed top-4 right-4 z-50 max-w-sm w-full bg-green-50 border border-green-200 rounded-lg shadow-lg transition-all duration-300 transform translate-x-0"
      v-motion-pop
    >
      <div class="flex items-center p-4">
        <div class="flex-shrink-0">
          <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3 flex-1">
          <p class="text-sm font-medium text-green-800">
            {{ notificationMessage }}
          </p>
        </div>
        <button 
          @click="closeNotification" 
          class="ml-auto flex-shrink-0 text-green-600 hover:text-green-800 transition-colors"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Error Notification -->
    <div 
      v-if="showError" 
      class="fixed top-4 right-4 z-50 max-w-sm w-full bg-red-50 border border-red-200 rounded-lg shadow-lg transition-all duration-300 transform translate-x-0"
      v-motion-pop
    >
      <div class="flex items-center p-4">
        <div class="flex-shrink-0">
          <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3 flex-1">
          <p class="text-sm font-medium text-red-800">
            {{ notificationMessage }}
          </p>
        </div>
        <button 
          @click="closeNotification" 
          class="ml-auto flex-shrink-0 text-red-600 hover:text-red-800 transition-colors"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Hero Section -->
    <div class="w-full bg-gray-100 py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-4xl md:text-5xl font-bold text-green-700 uppercase tracking-wide mb-4 drop-shadow-md" v-motion-slide-visible-right>
            Contact Us
          </h2>
        <p class="mt-4 text-lg text-gray-600">
          We're here to help! Reach out to us with any questions or concerns.
        </p>
      </div>
    </div>

    <!-- Contact Form and Information -->
    <div class="w-full max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-start">
        <!-- Contact Form - Made more compact -->
        <div class="bg-white p-6 rounded-lg shadow-md">
          <h2 class="text-2xl font-bold text-green-700 mb-6">Send Us a Message</h2>
          <form @submit.prevent="submitForm">
            <!-- Name -->
            <div class="mb-4">
              <InputLabel for="name" value="Name" />
              <TextInput 
                id="name" 
                v-model="form.name" 
                class="w-full" 
                required 
                :class="{ 'border-red-500': form.errors.name }"
              />
              <InputError :message="form.errors.name" />
            </div>

            <!-- Email -->
            <div class="mb-4">
              <InputLabel for="email" value="Email" />
              <TextInput 
                id="email" 
                v-model="form.email" 
                type="email" 
                class="w-full" 
                required 
                :class="{ 'border-red-500': form.errors.email }"
              />
              <InputError :message="form.errors.email" />
            </div>

            <!-- Subject -->
            <div class="mb-4">
              <InputLabel for="subject" value="Subject" />
              <TextInput 
                id="subject" 
                v-model="form.subject" 
                class="w-full" 
                required 
                :class="{ 'border-red-500': form.errors.subject }"
              />
              <InputError :message="form.errors.subject" />
            </div>

            <!-- Message -->
            <div class="mb-6">
              <InputLabel for="message" value="Message" />
              <textarea
                id="message"
                v-model="form.message"
                rows="4"
                class="w-full border-gray-300 focus:border-green-500 focus:ring focus:ring-green-200 focus:ring-opacity-50 rounded-md shadow-sm transition-colors duration-200"
                :class="{ 'border-red-500': form.errors.message }"
                required
              ></textarea>
              <InputError :message="form.errors.message" />
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
              <PrimaryButton 
                type="submit" 
                :disabled="form.processing" 
                class="bg-green-600 hover:bg-green-700 transition-colors duration-200"
                :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
              >
                <div class="flex items-center space-x-2">
                  <span v-if="form.processing" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Sending...
                  </span>
                  <span v-else class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                    Send Message
                  </span>
                </div>
              </PrimaryButton>
            </div>
          </form>
        </div>

        <!-- Contact Information -->
        <div class="bg-white p-6 rounded-lg shadow-md">
          <h2 class="text-2xl font-bold text-green-700 mb-6">Contact Information</h2>
          <div class="space-y-6">
            <!-- Address -->
            <div>
              <h3 class="text-lg font-semibold text-gray-900">Address</h3>
              <p class="text-gray-600">MANILA - 13 Platinum Ave, Malabon, Metro Manila</p>
              <p class="text-gray-600">LEGAZPI - Camelo Building, Tahao Rd, Barangay Cruzada, Legazpi City, Albay</p>
            </div>

            <!-- Phone -->
            <div>
              <h3 class="text-lg font-semibold text-gray-900">Phone</h3>
              <p class="text-gray-600">+63 917 543 7005</p>
            </div>

            <!-- Email -->
            <div>
              <h3 class="text-lg font-semibold text-gray-900">Email</h3>
              <p class="text-gray-600">infinitrixexpress@gmail.com</p>
            </div>

            <!-- Map -->
            <div>
              <h3 class="text-lg font-semibold text-gray-900">Location</h3>
              <div class="mt-4">
                <iframe
                  src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3860.258501366455!2d120.96485997599805!3d14.65812477618628!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b5f5d9c7f7c5%3A0x4d4b4b4b4b4b4b4b!2s13%20Platinum%20Ave%2C%20Malabon%2C%20Metro%20Manila!5e0!3m2!1sen!2sph!4v1690000000000!5m2!1sen!2sph"
                  width="100%"
                  height="250"
                  style="border:0;"
                  allowfullscreen=""
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade"
                ></iframe>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </GuestLayout>
</template>