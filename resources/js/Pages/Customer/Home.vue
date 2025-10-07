<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3'; // Added router import
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import NavLink from '@/Components/NavLink.vue';
import homeImage from '@/assets/home.jpg';
import truck2Image from '@/assets/truck2.jpg';
import transportationImage from '@/assets/transportation.png';
import packageImage from '@/assets/package.png';
import weightImage from '@/assets/weight.png';

// Add your case study image imports
import caseStudy1 from '@/assets/caseStudy1.jpg';
import caseStudy2 from '@/assets/caseStudy2.jpg';
import caseStudy3 from '@/assets/caseStudy3.jpg';
import caseStudy4 from '@/assets/caseStudy4.jpg';
import caseStudy5 from '@/assets/caseStudy5.jpg';
import caseStudy6 from '@/assets/caseStudy6.jpg';
import caseStudy7 from '@/assets/caseStudy7.jpg';

const faqs = ref([
  { question: 'How can I track my package?', answer: 'You can track your package by entering your tracking ID on our tracking page.', open: false },
  { question: 'What areas do you deliver to?', answer: 'We deliver to all major cities and regions across the country.', open: false },
  { question: 'What happens if my package is delayed?', answer: 'We will notify you immediately if there are any delays and work to resolve the issue.', open: false },
  { question: 'How do I request a delivery?', answer: 'Simply visit our delivery request page and provide the necessary information.', open: false },
  { question: 'Is my package insured?', answer: 'Yes, all shipments are insured against damage or loss during transit.', open: false },
  { question: 'Can I change my delivery address?', answer: 'You can update your address before the package is out for delivery.', open: false },
  { question: 'What are your delivery hours?', answer: 'Our deliveries are made between 8 AM and 8 PM from Monday to Saturday.', open: false },
  { question: 'Do you offer express delivery?', answer: 'Yes, we offer same-day and next-day delivery options for urgent shipments.', open: false },
]);

const toggleFAQ = (index) => {
  faqs.value[index].open = !faqs.value[index].open;
};

const showTruckPop = ref(false);
const toggleTruckPop = () => {
  showTruckPop.value = !showTruckPop.value;
};

// Carousel functionality
const currentSlide = ref(0);
const carousel = ref(null);
let autoSlideInterval = null;

// Updated caseStudies array with your local images
const caseStudies = ref([
  { title: 'Box Packing and Organizing', image: caseStudy1 },
  { title: 'Big Item Loading', image: caseStudy2 },
  { title: 'Sack Delivery for Relief', image: caseStudy3 },
  { title: 'Night-Time Cargo Drop', image: caseStudy4 },
  { title: 'Drum Storage Area', image: caseStudy5 },
  { title: 'Island Cargo Transfer', image: caseStudy6 },
  { title: 'Truck Dispatch Operations', image: caseStudy7 },
]);

const getVisibleCards = () => {
  if (window.innerWidth > 1200) return 6;
  if (window.innerWidth > 768) return 4;
  if (window.innerWidth > 480) return 2;
  return 1;
};

const maxSlides = () => Math.max(0, caseStudies.value.length - getVisibleCards());

const slideCarousel = (direction) => {
  currentSlide.value += direction;
  
  if (currentSlide.value < 0) {
    currentSlide.value = maxSlides();
  } else if (currentSlide.value > maxSlides()) {
    currentSlide.value = 0;
  }
  
  updateCarouselPosition();
};

const updateCarouselPosition = () => {
  if (carousel.value) {
    const cardWidth = carousel.value.children[0].offsetWidth + 20; // card width + gap
    const translateX = -currentSlide.value * cardWidth;
    carousel.value.style.transform = `translateX(${translateX}px)`;
  }
};

const startAutoSlide = () => {
  autoSlideInterval = setInterval(() => {
    slideCarousel(1);
  }, 4000);
};

const stopAutoSlide = () => {
  if (autoSlideInterval) {
    clearInterval(autoSlideInterval);
    autoSlideInterval = null;
  }
};

onMounted(() => {
  startAutoSlide();
  window.addEventListener('resize', () => {
    currentSlide.value = 0;
    updateCarouselPosition();
  });
});

onUnmounted(() => {
  stopAutoSlide();
});

const showProfileModal = ref(false);
const page = usePage();

// Show modal if flash warning is present
if (page.props.flash?.warning && page.props.flash.warning.includes('complete your profile')) {
    showProfileModal.value = true;
}

const handleDeliveryRequestClick = (event) => {
    if (page.props.auth?.user && !page.props.auth.user.customer?.is_profile_complete) {
        event.preventDefault();
        showProfileModal.value = true;
        return false;
    }
    return true;
};

const navigateToCompleteProfile = () => {
    showProfileModal.value = false;
    router.visit(route('profile.complete'));
};
</script>

<template>
  <GuestLayout>
    <!-- Profile Completion Modal -->
    <div v-if="showProfileModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="text-center">
          <h3 class="text-lg font-medium text-gray-900 mb-2">Profile Incomplete</h3>
          <p class="text-gray-600 mb-4">
            You need to complete your profile before you can request deliveries.
          </p>
          <div class="flex justify-center space-x-4">
            <button 
              @click="showProfileModal = false" 
              class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400"
            >
              Cancel
            </button>
            <button 
              @click="navigateToCompleteProfile" 
              class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
            >
              Complete Profile
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Hero Section -->
    <div
      @click="toggleTruckPop"
      class="relative w-full bg-cover bg-center bg-no-repeat text-white py-48 px-6 md:px-12 lg:px-20 cursor-pointer"
      :style="`background-image: url(${homeImage})`"
    >
      <div class="absolute inset-0 bg-black/50"></div>
      <div class="absolute inset-0 bg-gradient-to-b from-black/90 to-transparent"></div>
      <div class="relative z-10 text-center max-w-[90%] xl:max-w-[1280px] mx-auto">
        <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl xl:text-8xl font-extrabold tracking-tight drop-shadow-lg text-center"> 
          <span class="text-green-600">Sending a package?</span> We've got you covered.
        </h1>
        <p class="mt-6 text-xl md:text-2xl font-medium drop-shadow">
          Shipping made simple for businesses and individuals.
        </p>

        <div class="mt-10 flex flex-col md:flex-row justify-center md:space-x-6 space-y-4 md:space-y-0">
          <NavLink :href="route('customer.delivery-requests.create')">
            <PrimaryButton variant="dark" class="text-lg w-full md:w-auto shadow bg-green-600">
              Request Delivery
            </PrimaryButton>
          </NavLink>

          <NavLink :href="route('tracking')">
            <PrimaryButton variant="dark" class="text-lg w-full md:w-auto shadow bg-green-600">
              Track Package
            </PrimaryButton>
          </NavLink>
        </div>
      </div>
    </div>

    <!-- Goowell Case Section -->
    <div class="w-full bg-gradient-to-br from-gray-50 to-gray-100 py-16 px-6 md:px-12 lg:px-20">
      <div class="max-w-[90%] xl:max-w-[1280px] mx-auto">
        <!-- Header -->
        <div class="text-center mb-12">
          <h2 class="text-4xl md:text-5xl font-bold text-green-700 uppercase tracking-wide mb-4">
            Our Story
          </h2>
          <p class="text-gray-600 text-lg max-w-2xl mx-auto">
            Explore our successful delivery projects and see how we handle packages of all sizes with care and efficiency.
          </p>
        </div>

        <!-- Carousel Container -->
        <div class="relative overflow-hidden mb-8">
          <div 
            ref="carousel"
            class="flex transition-transform duration-500 ease-in-out gap-5"
            @mouseenter="stopAutoSlide"
            @mouseleave="startAutoSlide"
          >
            <div
              v-for="(caseStudy, index) in caseStudies"
              :key="index"
              class="flex-shrink-0 w-full sm:w-1/2 md:w-1/3 lg:w-1/4 xl:w-1/6 bg-white rounded-xl overflow-hidden shadow-lg hover:shadow-xl transition-all duration-300 cursor-pointer group"
              @click="() => console.log('Case study clicked:', caseStudy.title)"
            >
              <div class="relative h-48 overflow-hidden">
                <img 
                  :src="caseStudy.image" 
                  :alt="caseStudy.title"
                  class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
                >
                <div class="absolute inset-0 bg-gradient-to-t from-green-600/80 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
              </div>
              <div class="p-4">
                <h3 class="text-sm font-semibold text-gray-800 text-center leading-tight">
                  {{ caseStudy.title }}
                </h3>
              </div>
            </div>
          </div>

          <!-- Navigation Buttons -->
          <button
            @click="slideCarousel(-1)"
            class="absolute left-0 top-1/2 transform -translate-y-1/2 -translate-x-4 bg-white/90 hover:bg-white rounded-full p-3 shadow-lg hover:shadow-xl transition-all duration-300 z-10"
          >
            <svg class="w-6 h-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
          </button>

          <button
            @click="slideCarousel(1)"
            class="absolute right-0 top-1/2 transform -translate-y-1/2 translate-x-4 bg-white/90 hover:bg-white rounded-full p-3 shadow-lg hover:shadow-xl transition-all duration-300 z-10"
          >
            <svg class="w-6 h-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
          </button>
        </div>

        <!-- Read More Button -->
        <div class="w-full flex justify-center">
          <NavLink :href="route('about.us')" class="inline-block">
            <PrimaryButton class="bg-green-700 hover:bg-green-800 text-white px-8 py-3 rounded-full transition-all duration-300 shadow-lg hover:shadow-xl">
              Read More
            </PrimaryButton>
          </NavLink>
        </div>
      </div>
    </div>

   

    <!-- SERVICES Section -->
    <div class="w-full bg-white py-12 px-6 md:px-12 lg:px-20">
      <div class="max-w-[90%] xl:max-w-[1280px] mx-auto space-y-12">
        <div class="space-y-6">
          <div class="text-center mb-12">
          <h2 class="text-4xl md:text-5xl font-bold text-green-700 uppercase tracking-wide mb-4">
            Services
          </h2>
            <p class="text-gray-700 text-lg leading-relaxed mt-4">
              At the core of our operations is a commitment to ensuring that every shipment begins its journey with reliability and care. We specialize in handling the crucial first stages of the supply chain, starting with the secure pick-up of goods and continuing through their safe and timely delivery to our warehouse hub. Through well-organized processes, careful coordination, and a hands-on approach, we minimize delays and reduce risks that can impact later stages of delivery. Our scalable operations allow us to adapt to different client needs, whether handling small volumes or large shipments. </p>
          </div>

          <div
            @click="toggleTruckPop"
            class="relative w-full bg-cover bg-center bg-no-repeat text-white py-80 px-6 md:px-12 lg:px-20 cursor-pointer"
            :style="`background-image: url(${truck2Image})`"
          >
            <div class="absolute inset-0 bg-black/50"></div>
            <div class="absolute inset-0 from-black/90 to-transparent"></div>
            <!-- Removed button from here -->
          </div>

          <!-- Button moved here, outside the image container -->
          <div class="w-full flex justify-center mt-6">
      <NavLink :href="route('services')">
        <PrimaryButton class="text-sm bg-green-700 inline-block">
          Learn More →
        </PrimaryButton>
      </NavLink>
    </div>
        </div>
      </div>
    </div>

    <!-- What Our Logistics Service Means Section -->
     
    <div class="pt-4 pb-14 px-6 md:px-12 lg:px-20 text-center"> 
      <h2 class="leading-normal font-semibold text-5xl md:text-md text-green-700 pb-3 text-center mt-15">
        What Our Logistics Service Means for Your Business
      </h2>
      <p class="text-gray-700 max-w-4xl mx-auto mb-12 text-xl">
        At Infinitrix, we specialize in the crucial first stages of your supply chain—from reliable pick-up to safe delivery at our warehouse hub. While we focus on these key steps, our smart systems and scalable operations ensure your shipments start their journey smoothly and securely.
      </p>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-10 text-center">
        <!-- Item 1 -->
        <div class="flex flex-col items-center space-y-4">
          <img :src="packageImage" alt="Warehouse and Truck" class="w-50 h-50 object-contain" />
          <h3 class="font-bold text-4xl text-green-700 text-md mb-2">Transportation</h3>
          <p class="text-gray-700 max-w-4xl mx-auto mb-12 text-xl">
           We handle the critical early stages of the logistics process—from pick-up to delivery at our central warehouse—ensuring your goods move efficiently and securely.
          </p>
        </div>

        <!-- Item 2 -->
        <div class="flex flex-col items-center space-y-4">
          <img :src="transportationImage" alt="Boxes and Ninja" class="w-50 h-50 object-contain" />
          <h3 class="font-bold text-4xl text-green-700 text-md mb-2">Lightweight package delivery</h3>
          <p class="text-gray-700 max-w-4xl mx-auto mb-12 text-xl">
            Our fleet includes a range of trucks, each designed for different load capacities. If your package is lightweight, 
    you can be confident that we have the right vehicle to handle it efficiently.
          </p>
        </div>

        <!-- Item 3 -->
        <div class="flex flex-col items-center space-y-4">
          <img :src="weightImage" alt="Nationwide Map" class="w-50 h-50 object-contain" />
          <h3 class="font-bold text-4xl text-green-700 text-md mb-2">Heavyweight</h3>
          <p class="text-gray-700 max-w-4xl mx-auto mb-12 text-xl">
            Our fleet also includes trucks built to handle large and heavy loads. No matter how bulky your package is, 
    you can trust that we have the right vehicle to transport it safely and efficiently.
          </p>
        </div>
      </div>
    </div>

    <!-- FAQ Section -->
    <div class="w-full py-12 px-6 md:px-12 lg:px-20">
      <div class="max-w-[90%] xl:max-w-[1280px] mx-auto">
        <h2 class="text-3xl font-semibold text-green-700 text-center mb-8">Frequently Asked Questions</h2>
        <div class="space-y-4">
          <div
            v-for="(faq, index) in faqs"
            :key="index"
            class="border border-gray-300 rounded-lg"
          >
            <button
              @click="toggleFAQ(index)"
              class="w-full p-4 text-left flex justify-between items-center focus:outline-none"
              :aria-expanded="faq.open.toString()"
              :aria-controls="'faq-' + index"
              :id="'faq-btn-' + index"
            >
              <span class="text-lg text-gray-900">{{ faq.question }}</span>
              <span aria-hidden="true" class="text-2xl">{{ faq.open ? '−' : '+' }}</span>
            </button>
            <div
              v-if="faq.open"
              class="p-4 border-t text-gray-600"
              :id="'faq-' + index"
              role="region"
              :aria-labelledby="'faq-btn-' + index"
            >
              {{ faq.answer }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </GuestLayout>
</template>