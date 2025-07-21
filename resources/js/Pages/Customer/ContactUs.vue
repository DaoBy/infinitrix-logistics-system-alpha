<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';

// Form for contact submission
const form = useForm({
  name: '',
  email: '',
  subject: '',
  message: '',
});

// Handle form submission
const submitForm = () => {
  form.post(route('contact.submit'), {
    onSuccess: () => {
      form.reset();
      alert('Your message has been sent successfully!');
    },

    onError: () => {
      alert('There was an error submitting your message. Please try again.');
    },
  });
};
</script>

<template>
  <GuestLayout>
    <!-- Hero Section -->
    <div class="w-full bg-gray-100 py-16">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold text-gray-900">Contact Us</h1>
        <p class="mt-4 text-lg text-gray-600">
          We're here to help! Reach out to us with any questions or concerns.
        </p>
      </div>
    </div>

    <!-- Contact Form and Information -->
    <div class="w-full max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
        <!-- Contact Form -->
        <div class="bg-white p-8 rounded-lg shadow-md">
          <h2 class="text-2xl font-bold text-gray-900 mb-6">Send Us a Message</h2>
          <form @submit.prevent="submitForm">
            <!-- Name -->
            <div class="mb-6">
              <InputLabel for="name" value="Name" />
              <TextInput id="name" v-model="form.name" class="w-full" />
              <InputError :message="form.errors.name" />
            </div>

            <!-- Email -->
            <div class="mb-6">
              <InputLabel for="email" value="Email" />
              <TextInput id="email" v-model="form.email" type="email" class="w-full" />
              <InputError :message="form.errors.email" />
            </div>

            <!-- Subject -->
            <div class="mb-6">
              <InputLabel for="subject" value="Subject" />
              <TextInput id="subject" v-model="form.subject" class="w-full" />
              <InputError :message="form.errors.subject" />
            </div>

        <!-- Message -->
<div class="mb-6">
  <InputLabel for="message" value="Message" />
  <v-textarea
    id="message"
    v-model="form.message"
    rows="5"
    class="w-full"
  ></v-textarea>
  <InputError :message="form.errors.message" />
</div>


            <!-- Submit Button -->
            <div class="flex justify-end">
              <PrimaryButton type="submit" :disabled="form.processing">
                Send Message
              </PrimaryButton>
            </div>
          </form>
        </div>

        <!-- Contact Information -->
        <div class="bg-white p-8 rounded-lg shadow-md">
          <h2 class="text-2xl font-bold text-gray-900 mb-6">Contact Information</h2>
          <div class="space-y-6">
            <!-- Address -->
            <div>
              <h3 class="text-lg font-semibold text-gray-900">Address</h3>
              <p class="text-gray-600">123 Logistics Street, Naga City, Philippines</p>
            </div>

            <!-- Phone -->
            <div>
              <h3 class="text-lg font-semibold text-gray-900">Phone</h3>
              <p class="text-gray-600">+63 123 456 7890</p>
            </div>

            <!-- Email -->
            <div>
              <h3 class="text-lg font-semibold text-gray-900">Email</h3>
              <p class="text-gray-600">support@infinitrix.com</p>
            </div>

            <!-- Map -->
            <div>
              <h3 class="text-lg font-semibold text-gray-900">Location</h3>
              <div class="mt-4">
                <iframe
                  src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d123456.7890123456!2d123.12345678901234!3d13.123456789012345!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTPCsDA3JzI0LjQiTiAxMjPCsDA3JzI0LjQiRQ!5e0!3m2!1sen!2sph!4v1234567890123"
                  width="100%"
                  height="300"
                  style="border:0;"
                  allowfullscreen=""
                  loading="lazy"
                ></iframe>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </GuestLayout>
</template>