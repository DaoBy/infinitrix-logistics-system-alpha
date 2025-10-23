<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import services1Image from '@/assets/services1.png';
import sack1Image from '@/assets/sack1.png';
import truck2Image from '@/assets/truck2.jpg';
import customerfocus from '@/assets/customerfocus.png';
import missionIcon from '@/assets/missionIcon.png';
import operationfocus from '@/assets/operationfocus.png';
import visionIcon from '@/assets/visionIcon.png';
import trusticon from '@/assets/trusticon.png';
import peopleicon from '@/assets/peopleicon.png';

</script>

<template>
  <GuestLayout>
  <div
    v-motion-fade-visible :duration="600"
      @click="toggleTruckPop"
      class="relative w-full bg-cover bg-[position:70%_30%] bg-no-repeat text-white py-32 px-6 md:px-12 lg:px-20 cursor-pointer"
      :style="`background-image: url(${services1Image})`"
    >
      <div class="absolute inset-0 bg-black/50"></div>
      <div class="absolute inset-0 from-black/90 to-transparent"></div>
      <div class="relative z-10  max-w-[90%] xl:max-w-[1280px] mx-auto">
      <motion preset="slideVisibleLeft" :duration="900">
        <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl xl:text-8xl font-extrabold tracking-tight drop-shadow-lg">
          We <span class="text-green-500">simplify </span> your logistics.
        </h1>
        </motion>
        <p class="mt-6 text-xl md:text-2xl font-medium drop-shadow">
          Skip the hassle — let our trucks do the heavy lifting.
        </p>

      </div>
    </div>

    

  <!-- About Infinitrix  -->
<div class="mt-24 max-w-7xl mx-auto px-6 md:px-12 lg:px-20 grid grid-cols-1 md:grid-cols-2 gap-12 items-start">
  <!-- Text Content -->
  <div>
    <h3 class="text-4xl font-extrabold text-green-700 mb-4 drop-shadow-md">
      | About Infinitrix
    </h3>
    <p class="text-xl text-gray-700 mb-6">
      Speed, reliability, and innovation—Infinitrix leads modern logistics with purpose.
    </p>
    <p class="text-gray-700 text-base leading-relaxed mb-4">
      Infinitrix is a logistics company built for speed, reliability, and modern delivery solutions. We integrate advanced technology and exceptional service to ensure your goods—and your business—keep moving forward.
    </p>
    <p class="text-gray-700 text-base leading-relaxed">
      Whether you're shipping across cities or continents, our network and experience provide a trusted backbone for growth.
    </p>
  </div>

  <!-- Image -->
  <div v-motion-fade-visible :duration="600">
    <img :src="truck2Image" alt="Infinitrix Overview Image" class="w-full rounded shadow-md object-cover" />
  </div>
</div>

<!-- Our Story  -->
<div class="mt-24 mb-24 max-w-7xl mx-auto px-6 md:px-12 lg:px-20 grid grid-cols-1 md:grid-cols-2 gap-12 items-start">
  <!-- Image -->
  <div v-motion-fade-visible :duration="600">
    <img :src="sack1Image" alt="sack1 Image" class="w-full rounded shadow-md object-cover" />
  </div>

  <!-- Text Content -->
  <div>
    <h3 class="text-4xl font-extrabold text-green-700 mb-4 drop-shadow-md">
      | Our Story
    </h3>
    <p class="text-xl text-gray-700 mb-6">
      Infinitrix Express Cargo began with a simple vision: to create a reliable and efficient solution for moving goods.
    </p>
    <p class="text-gray-700 text-base leading-relaxed mb-4">
      It was founded out of a need to bridge distances and support growing communities through dependable transport. Starting with a few resources and a strong commitment to service, the company took its first steps toward building a lasting presence in the industry.
    </p>
    <p class="text-gray-700 text-base leading-relaxed">
      What began as a small effort steadily grew through hard work, trust, and a focus on meeting everyday needs.
    </p>
  </div>
</div>

<div class="relative flex flex-col items-center justify-center text-center pt-1 pb-[15rem] sm:pb-[20rem] md:pb-[20rem] lg:pb-[30rem] overflow-hidden"> 
  <p class="max-w-3xl text-gray-700 text-base sm:text-lg md:text-xl mb-5 px-4 md:px-6"> 
    "Infinitrix Express Cargo is committed to delivering reliable land freight services that ensure every package reaches its destination safely and on time. We aim to simplify logistics through digital solutions, strengthen customer trust through transparency and care, and continuously improve our operations to support the growing needs of our clients." 
  </p> 
  <h1 class="font-black text-[5rem] sm:text-[6rem] md:text-[9rem] lg:text-[11rem] xl:text-[12rem] leading-none bg-gradient-to-t from-green-900 to-green-300 bg-clip-text text-transparent select-none z-0"> 
    OUR MISSION 
  </h1> 
  <img :src="missionIcon" alt="Our Mission Icon" class="absolute bottom-10 sm:bottom-16 md:bottom-20 h-[15rem] sm:h-[20rem] md:h-[25rem] lg:h-[30rem] z-10 pointer-events-none mb-0 sm:mb-10 md:mb-20 lg:mb-10" /> 
</div> 
<div class="relative flex flex-col items-center justify-center text-center pt-1 pb-[15rem] sm:pb-[20rem] md:pb-[20rem] lg:pb-[30rem] overflow-hidden"> 
  <p class="max-w-3xl text-gray-700 text-base sm:text-lg md:text-xl mb-5 px-4 md:px-6"> "To become a trusted logistics partner in the Philippines by providing fast, secure, and convenient cargo delivery services that connect communities and businesses through innovation and dedication." </p> 
  <h1 class="font-black text-[6rem] sm:text-[6rem] md:text-[9rem] lg:text-[11rem] xl:text-[12rem] leading-none bg-gradient-to-t from-green-900 to-green-300 bg-clip-text text-transparent select-none z-0"> OUR VISION </h1> 
  <img :src="visionIcon" alt="Our Vision Icon" class="absolute bottom-10 sm:bottom-16 md:bottom-20 h-[15rem] sm:h-[20rem] md:h-[25rem] lg:h-[30rem] z-10 pointer-events-none mb-0 sm:mb-10 md:mb-20 lg:mb-10" /> 
</div>

<!-- Section Title -->
<h2
  class="text-3xl sm:text-4xl md:text-5xl font-bold text-green-700 uppercase tracking-wide mb-6 md:mb-10 drop-shadow-md text-center"
  v-motion-slide-visible-right
  :duration="600"
>
  Our Core Values
</h2>

<!-- Cards Container -->
<div class="flex flex-wrap justify-center items-center gap-6 sm:gap-8 md:gap-10 bg-gray-100 py-10">

  <!-- Card 1 -->
  <div
    v-motion
    :initial="{ rotateY: 0 }"
    :hovered="{ rotateY: 180 }"
    :transition="{ duration: 5.0, ease: 'easeInOut' }"
    class="relative w-72 sm:w-80 h-[25rem] sm:h-[28rem] md:h-[30rem] [transform-style:preserve-3d] cursor-pointer"
  >
    <!-- Front -->
    <div class="absolute inset-0 flex flex-col items-center justify-center bg-white rounded-xl shadow-lg [backface-visibility:hidden] p-4">
      <img :src="customerfocus" alt="Customer Focus Icon" class="h-34 sm:h-42 md:h-50 object-contain" />
      <h2 class="absolute bottom-16 text-lg sm:text-xl md:text-2xl font-semibold text-green-700 drop-shadow-md">Customer-Centered</h2>
      <h2 class="absolute bottom-8 text-lg sm:text-xl md:text-2xl font-semibold text-green-700 drop-shadow-md">Innovation</h2>
    </div>

    <!-- Back -->
    <div class="absolute inset-0 flex items-center justify-center bg-green-600 rounded-xl text-white [backface-visibility:hidden] [transform:rotateY(180deg)] p-4">
      <p class="text-center text-sm sm:text-base md:text-lg">We relentlessly innovate, always with our customers' evolving needs at the forefront, to deliver solutions that simplify and enhance their logistics experience.</p>
    </div>
  </div>

  <!-- Card 2 -->
  <div
    v-motion
    :initial="{ rotateY: 0 }"
    :hovered="{ rotateY: 180 }"
    :transition="{ duration: 5.0, ease: 'easeInOut' }"
    class="relative w-72 sm:w-80 h-[25rem] sm:h-[28rem] md:h-[30rem] [transform-style:preserve-3d] cursor-pointer"
  >
    <div class="absolute inset-0 flex flex-col items-center justify-center bg-white rounded-xl shadow-lg [backface-visibility:hidden] p-4">
      <img :src="operationfocus" alt="Operational Excellence Icon" class="h-34 sm:h-42 md:h-50 object-contain" />
      <h2 class="absolute bottom-12 text-lg sm:text-xl md:text-2xl font-semibold text-green-700 drop-shadow-md">Operational Excellence</h2>
    </div>

    <div class="absolute inset-0 flex items-center justify-center bg-green-600 rounded-xl text-white [backface-visibility:hidden] [transform:rotateY(180deg)] p-4">
      <p class="text-center text-sm sm:text-base md:text-lg">We commit to the highest standards in every operation, ensuring effectiveness and continuous improvement across our entire network.</p>
    </div>
  </div>

  <!-- Card 3 -->
  <div
    v-motion
    :initial="{ rotateY: 0 }"
    :hovered="{ rotateY: 180 }"
    :transition="{ duration: 5.0, ease: 'easeInOut' }"
    class="relative w-72 sm:w-80 h-[25rem] sm:h-[28rem] md:h-[30rem] [transform-style:preserve-3d] cursor-pointer"
  >
    <div class="absolute inset-0 flex flex-col items-center justify-center bg-white rounded-xl shadow-lg [backface-visibility:hidden] p-4">
      <img :src="trusticon" alt="Transparency and Trust Icon" class="h-34 sm:h-42 md:h-50 object-contain" />
      <h2 class="absolute bottom-12 text-lg sm:text-xl md:text-2xl font-semibold text-green-700 drop-shadow-md">Transparency & Trust</h2>
    </div>

    <div class="absolute inset-0 flex items-center justify-center bg-green-600 rounded-xl text-white [backface-visibility:hidden] [transform:rotateY(180deg)] p-4">
      <p class="text-center text-sm sm:text-base md:text-lg">Open communication and integrity build strong, lasting relationships founded on trust.</p>
    </div>
  </div>

  <!-- Card 4 -->
  <div
    v-motion
    :initial="{ rotateY: 0 }"
    :hovered="{ rotateY: 180 }"
    :transition="{ duration: 5.0, ease: 'easeInOut' }"
    class="relative w-72 sm:w-80 h-[25rem] sm:h-[28rem] md:h-[30rem] [transform-style:preserve-3d] cursor-pointer"
  >
    <div class="absolute inset-0 flex flex-col items-center justify-center bg-white rounded-xl shadow-lg [backface-visibility:hidden] p-4">
      <img :src="peopleicon" alt="Team Culture Icon" class="h-34 sm:h-42 md:h-50 object-contain" />
      <h2 class="absolute bottom-16 text-lg sm:text-xl md:text-2xl font-semibold text-green-700 drop-shadow-md">Team Culture with</h2>
      <h2 class="absolute bottom-8 text-lg sm:text-xl md:text-2xl font-semibold text-green-700 drop-shadow-md">Personality</h2>
    </div>

    <div class="absolute inset-0 flex items-center justify-center bg-green-600 rounded-xl text-white [backface-visibility:hidden] [transform:rotateY(180deg)] p-4">
      <p class="text-center text-sm sm:text-base md:text-lg">We create a positive, inclusive team environment where collaboration and creativity thrive.</p>
    </div>
  </div>
</div>


    







        <!-- Team Section -->
        <div class="mt-20 text-center">
          <h3 class="text-3xl font-bold text-green-700">Meet the Leadership Team</h3>
          <p class="mt-4 text-lg text-gray-600 max-w-xl mx-auto">
            Our team blends industry experience with creative thinking, ensuring your deliveries run on time—and with a touch of personality.
          </p>

          <div class="flex justify-center items-center mt-10 mx-auto gap-20 flex-wrap">
            <!-- Team Member 1 -->
            <div class="text-center">
              <img :src="deliveryImage" alt="CEO" class="mx-auto h-32 w-32 rounded-full shadow-md object-cover" />
              <h4 class="mt-4 text-lg font-semibold text-gray-900">Carlos Teng</h4>
              <p class="text-sm text-gray-600">Chief Executive Officer</p>
            </div>

            <!-- Team Member 2 -->
            <div class="text-center">
              <img :src="deliveryImage" alt="COO" class="mx-auto h-32 w-32 rounded-full shadow-md object-cover" />
              <h4 class="mt-4 text-lg font-semibold text-gray-900">Nesty Teng</h4>
              <p class="text-sm text-gray-600">Operation Manager</p>
            </div> 
          </div>
        </div>
      

      
  </GuestLayout>
</template>


