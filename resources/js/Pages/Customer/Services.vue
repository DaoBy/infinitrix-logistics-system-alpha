<script setup>
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
</script>

<template>
  <GuestLayout>
    <div
      @click="toggleTruckPop"
      class="relative w-full bg-cover bg-[position:70%_30%] bg-no-repeat text-white py-32 px-6 md:px-12 lg:px-20 cursor-pointer"
      :style="`background-image: url(${services1Image})`"
    >
      <div class="absolute inset-0 bg-black/50"></div>
      <div class="absolute inset-0 from-black/90 to-transparent"></div>
      <div class="relative z-10  max-w-[90%] xl:max-w-[1280px] mx-auto">
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
    <div class="w-full bg-white py-16 px-6 md:px-12 lg:px-20">
      <div class="max-w-[1280px] mx-auto space-y-16">
        <div class="flex flex-col md:flex-row items-center gap-12">
          <!-- Text Section -->
          <div class="flex-1">
            <div class="flex flex-col md:flex-row items-center justify-between mb-6 gap-8">
  <!-- Left: Text Section -->
  <div class="w-full md:w-1/2">
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

  <!-- Right: Image Section -->
  <div class="w-full md:w-1/2">
    <img :src="truckImage" alt="Truck" class="rounded-lg shadow-lg w-full object-cover" />
  </div>
</div>
<div class="py-28">
  <div>
    <h3 class="text-4xl md:text-5xl font-extrabold text-green-700 mb-6 py-4 drop-shadow-md">
              What can we transport? Just about everything — including but not limited to:
            </h3>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 px-4 py-8">
  <!-- Box 1 -->
  <div class="h-80 bg-white p-6 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.3)] hover:shadow-[0_6px_30px_rgba(0,0,0,0.5)] transition-shadow duration-300">
    <h2 class="text-xl text-green-700 font-semibold mb-2">Assorted hardware</h2>
     <img :src="hardwareImage" alt="Warehouse and Truck" class="w-80 h-70 object-contain transform transition-transform duration-300 hover:scale-110" />
  </div>

  <!-- Box 2 -->
  <div class="h-80 bg-white p-6 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.3)] hover:shadow-[0_6px_30px_rgba(0,0,0,0.5)] transition-shadow duration-300">
    <h2 class="text-xl text-green-700 font-semibold mb-2">Paints and industrial supplies</h2>
    <img :src="paintImage" alt="Paints and Industrial Supplies" class="w-80 h-70 object-contain transform transition-transform duration-300 hover:scale-110" />
  </div>

  <!-- Box 3 -->
  <div class="h-80 bg-white p-6 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.3)] hover:shadow-[0_6px_30px_rgba(0,0,0,0.5)] transition-shadow duration-300">
    <h2 class="text-xl text-green-700 font-semibold mb-2">Motorcycle parts and accessories</h2>
    <img :src="motorImage" alt="Motorcycle Parts" class="w-80 h-70 object-contain transform transition-transform duration-300 hover:scale-110" />
   
  </div>

  <!-- Box 4 -->
  <div class="h-80 bg-white p-6 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.3)] hover:shadow-[0_6px_30px_rgba(0,0,0,0.5)] transition-shadow duration-300">
    <h2 class="text-xl text-green-700 font-semibold mb-2">Tires and tubes</h2>
    <img :src="tireImage" alt="Tires and Tubes" class="w-80 h-70 object-contain transform transition-transform duration-300 hover:scale-110" />
  </div>

  <!-- Box 5 -->
  <div class="h-80 bg-white p-6 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.3)] hover:shadow-[0_6px_30px_rgba(0,0,0,0.5)] transition-shadow duration-300">
    <h2 class="text-xl text-green-700 font-semibold mb-2">Electrical wires and equipment</h2>
    <img :src="wires" alt="Electrical Wires and Equipment" class="w-80 h-70 object-contain transform transition-transform duration-300 hover:scale-110" />
  </div>

  <!-- Box 6 -->
  <div class="h-80 bg-white p-6 rounded-xl shadow-[0_4px_20px_rgba(0,0,0,0.3)] hover:shadow-[0_6px_30px_rgba(0,0,0,0.5)] transition-shadow duration-300">
    <h2 class="text-xl text-green-700 font-semibold mb-2">Welding tools and more</h2>
    <img :src="welding" alt="Welding Tools" class="w-80 h-70 object-contain transform transition-transform duration-300 hover:scale-110" />

  </div>
</div>
<p class="text-gray-700 text-lg leading-relaxed mb-6 drop-shadow-lg">
              Wherever your cargo fits in the supply chain, we’ve got the wheels and the know-how to get it there safely.
            </p>

            <p class="text-gray-700 text-lg leading-relaxed mb-6">
              Located at <strong>13 Platinum St., Goldendale Subdivision, Tinajeros, Malabon, Metro Manila</strong>, <strong>INFINITRIX EXPRESS CARGO (C)</strong> offers more than just transport. Our full-service logistics operation includes warehousing, inventory management, order fulfillment, and secure distribution — all backed by a highly trained team.
            </p>

            <p class="text-gray-700 text-lg leading-relaxed">
              Whether you're a small business or a large enterprise & local, we deliver logistics solutions that scale with you.
            </p>
</div>
            


          

            

            
            <div class="mt-12 bg-gray-100 p-8 rounded-lg flex flex-col lg:flex-row items-center gap-8">
  <!-- Image -->
  <div class="flex-1">
    <img :src="contact1Image" alt="Truck" class="rounded-lg shadow-lg w-full object-cover" />
  </div>

  <!-- Text Content -->
  <div class="flex-1">
    <h2 class="text-3xl font-bold text-green-800 mb-4">INFINITRIX EXPRESS CARGO (C) – Your partner in movement, storage, and success.</h2>
    <p class="text-gray-700 mb-4">
      we offer a wide range of services including storage, inventory management, order fulfillment, and distribution. Our team of experienced professionals are dedicated to providing personalized solutions to meet the unique needs of each client. Whether you are a small business looking for short-term storage or a large company in need of comprehensive logistics services, we have the expertise and resources to help you succeed. Contact us today to learn more about how INFINITRIX EXPRESS CARGO (C) can streamline your warehousing and logistics operations.
    </p>
    
    <a :href="route('contact.us')" class="inline-block bg-green-700 hover:bg-green-600 text-white font-bold py-3 px-6 rounded transition">
      CONTACT US
    </a>
  </div>
</div>

          </div>

          <!-- Image Section -->
          
        </div>
      </div>
    </div>
  </GuestLayout>
</template>
