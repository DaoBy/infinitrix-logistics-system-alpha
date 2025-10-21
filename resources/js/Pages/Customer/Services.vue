<script setup>
import { ref } from 'vue'

import GuestLayout from '@/Layouts/GuestLayout.vue';
import truckImage from '@/assets/truck2.jpg';
import services1Image from '@/assets/services1.png';
import contact1Image from '@/assets/contact1.jpg';
import hardwareImage from '@/assets/hardware.png';
import paintImage from '@/assets/paint.png';
import motorImage from '@/assets/motor.png';
import tireImage from '@/assets/tire.png';
import wires from '@/assets/wires.png';
import welding from '@/assets/welding.png';

// --- Flip Card Logic ---
const cards = ref([
  {
    title: "Assorted Hardware",
    image: hardwareImage,
    description: "Nuts, bolts, tools, and all kinds of general hardware.",
  },
  {
    title: "Paints & Industrial Supplies",
    image: paintImage,
    description: "Paints, coatings, and other industrial materials.",
  },
  {
    title: "Motorcycle Parts",
    image: motorImage,
    description: "Quality motorcycle components and accessories.",
  },
  {
    title: "Tires & Tubes",
    image: tireImage,
    description: "Durable tires, tubes, and related equipment.",
  },
  {
    title: "Electrical Wires & Equipment",
    image: wires,
    description: "Power cables, fittings, and electrical hardware.",
  },
  {
    title: "Welding Tools & More",
    image: welding,
    description: "Industrial-grade welding tools and accessories.",
  },
]);

const flippedCards = ref(Array(cards.value.length).fill(false));

function toggleFlip(index) {
  flippedCards.value[index] = !flippedCards.value[index];
}
</script>

<template>
  <GuestLayout>
    <!-- HERO -->
    <div
      v-motion-fade-visible
      :duration="600"
      class="relative w-full bg-cover bg-[position:70%_30%] bg-no-repeat text-white py-32 px-6 md:px-12 lg:px-20 cursor-pointer"
      :style="`background-image: url(${services1Image})`"
    >
      <div class="absolute inset-0 bg-black/50"></div>
      <div class="relative z-10 max-w-[90%] xl:max-w-[1280px] mx-auto">
        <motion preset="slideVisibleLeft" :duration="900">
          <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl xl:text-8xl font-extrabold tracking-tight drop-shadow-lg">
            Road <span class="text-green-500">Freight</span> & Transport
          </h1>
        </motion>
        <p class="mt-6 text-xl md:text-2xl font-medium drop-shadow">
          Built to Move Your Business Forward.
        </p>
      </div>
    </div>

    <!-- CONTENT SECTION -->
    <div class="w-full bg-white py-16 px-6 md:px-12 lg:px-20">
      <div class="max-w-[1280px] mx-auto space-y-16">

        <!-- TEXT + IMAGE -->
        <div class="flex flex-col md:flex-row items-center gap-12">
          <div class="flex-1">
            <h1 class="text-4xl md:text-5xl font-extrabold text-green-700 mb-6 drop-shadow-md">
              No Load Too Large or Too Small – We Deliver with Precision and Care
            </h1>
            <p class="text-gray-700 text-lg leading-relaxed mb-6">
              At <strong>INFINITRIX EXPRESS CARGO (C)</strong>, we believe logistics should be simple, flexible, and built around your needs.
            </p>
            <p class="text-gray-700 text-lg leading-relaxed mb-6">
              No need to deal with complex transport processes. From route planning to cargo handling, our expert team takes care of it all. And when it comes to your goods, we don’t just move them — we move them like they’re our own.
            </p>
          </div>

          <!-- IMAGE -->
          <div class="flex-1">
            <img :src="truckImage" alt="Truck" class="rounded-lg shadow-lg w-full object-cover" />
          </div>
        </div>

<!-- FLIP CARD SECTION -->
<section class="py-28">
  <h3 class="text-4xl md:text-5xl font-extrabold text-green-700 mb-10 text-center drop-shadow-md">
    What can we transport? Just about everything — including but not limited to:
  </h3>

  <div class="flex flex-wrap justify-center gap-8">
    <!-- Card Loop -->
    <div
      v-for="(card, index) in cards"
      :key="index"
      @click="toggleFlip(index)"
      class="relative w-72 h-96 cursor-pointer [perspective:1000px]"
    >
      <!-- Inner -->
      <div
        class="absolute inset-0 transition-transform duration-700 [transform-style:preserve-3d]"
        :style="{
          transform: flippedCards[index]
            ? 'rotateY(180deg)'
            : 'rotateY(0deg)',
        }"
      >
        <!-- Front -->
        <div
          class="absolute inset-0 bg-white rounded-2xl shadow-xl flex flex-col items-center p-6 text-center"
          style="backface-visibility: hidden;"
        >
          <img
            :src="card.image"
            :alt="card.title"
            class="w-32 h-32 object-contain mb-4"
          />
          <h2 class="text-2xl font-bold text-green-700">
            {{ card.title }}
          </h2>

          <!-- pushes text to bottom -->
          <div class="flex-grow"></div>

          <p class="text-sm italic text-gray-500 opacity-80 mt-auto mb-2">
            Click to learn more
          </p>
        </div>

        <!-- Back - CENTERED CONTENT -->
        <div
          class="absolute inset-0 bg-green-700 text-white rounded-2xl shadow-xl flex flex-col items-center justify-center p-6 text-center"
          style="transform: rotateY(180deg); backface-visibility: hidden;"
        >
          <h2 class="text-2xl font-bold mb-2">{{ card.title }}</h2>
          <p class="text-sm mb-4">{{ card.description }}</p>
          <p class="text-sm italic opacity-80">
            Click to flip back
          </p>
        </div>
      </div>
    </div>
  </div>
</section>




        <!-- ADDITIONAL TEXT -->
        <p class="text-gray-700 text-lg leading-relaxed drop-shadow-lg">
          Wherever your cargo fits in the supply chain, we’ve got the wheels and the know-how to get it there safely.
        </p>
        <p class="text-gray-700 text-lg leading-relaxed">
          Located at <strong>Camelo Building, Tahao Rd, Barangay Cruzada, Legazpi City, 4500 Albay, Philippines</strong>, <strong>INFINITRIX EXPRESS CARGO (C)</strong> offers more than just transport. Our full-service logistics operation includes warehousing, inventory management, order fulfillment, and secure distribution — all backed by a highly trained team.
        </p>
        <p class="text-gray-700 text-lg leading-relaxed">
          Whether you're a small business or a large enterprise, we deliver logistics solutions that scale with you.
        </p>

        <!-- CONTACT SECTION -->
        <div class="mt-12 bg-gray-100 p-8 rounded-lg flex flex-col lg:flex-row items-center gap-8">
          <div class="flex-1">
            <img :src="contact1Image" alt="Truck" class="rounded-lg shadow-lg w-full object-cover" />
          </div>

          <div class="flex-1">
            <h2 class="text-3xl font-bold text-green-800 mb-4 drop-shadow-md">
              INFINITRIX EXPRESS CARGO (C) – Your partner in movement, storage, and success.
            </h2>
            <p class="text-gray-700 mb-4">
              We offer a wide range of services including storage, inventory management, order fulfillment, and distribution. Our experienced team provides personalized solutions tailored to your needs.
            </p>

            <a
              :href="route('contact.us')"
              class="inline-block bg-green-700 hover:bg-green-600 text-white font-bold py-3 px-6 rounded transition"
            >
              CONTACT US
            </a>
          </div>
        </div>

      </div>
    </div>
  </GuestLayout>
</template>
