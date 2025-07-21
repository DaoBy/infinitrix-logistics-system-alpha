<template>
  <div class="container mx-auto px-6 py-12 flex gap-12">
    <!-- Progress Indicator (Left Side) - Updated for 6 steps -->
    <div class="w-1/4 space-y-8">
      <h2 class="text-2xl font-bold">Progress</h2>
      <div class="flex flex-col gap-4">
        <div v-for="(step, index) in ['Sender Info', 'Receiver Info', 'Package Type', 'Package Details', 'Payment', 'Confirmation']" 
             :key="index" 
             class="flex items-center gap-4 cursor-pointer"
             @click="currentStep > index + 1 ? currentStep = index + 1 : null">
          <div :class="[
              'w-10 h-10 rounded-full flex items-center justify-center',
              currentStep === index + 1 ? 'bg-black text-white' : 
              currentStep > index + 1 ? 'bg-green-500 text-white' : 'bg-gray-300 text-gray-600'
            ]">
            {{ index + 1 }}
          </div>
          <span :class="[
            currentStep === index + 1 ? 'font-semibold' : 
            currentStep > index + 1 ? 'text-green-600' : 'text-gray-500'
          ]">
            {{ step }}
          </span>
        </div>
      </div>
    </div>

    <!-- Form Content (Right Side) -->
    <div class="w-3/4 space-y-8">
      <h1 class="text-3xl font-bold">Edit Delivery Request</h1>

      <!-- Status Messages -->
      <div v-if="status || success || error" class="mb-6">
        <div v-if="status" class="p-4 bg-blue-100 text-blue-800 rounded">
          {{ status }}
        </div>
        <div v-if="success" class="p-4 bg-green-100 text-green-800 rounded">
          {{ success }}
        </div>
        <div v-if="error" class="p-4 bg-red-100 text-red-800 rounded">
          {{ error }}
        </div>
      </div>

      <!-- Step 1: Sender Details -->
      <div v-if="currentStep === 1" class="space-y-6">
        <h2 class="text-xl font-semibold">Sender Details</h2>

        <!-- Sender Type -->
        <div>
          <InputLabel value="Sender Type" />
          <SelectInput
            v-model="form.sender.customer_category"
            :options="customerCategoryOptions"
            option-value="value"
            option-label="label"
            class="mt-1 block w-full"
            :error="!!form.errors['sender.customer_category']"
          />
          <InputError :message="form.errors['sender.customer_category']" />
        </div>

        <!-- Name Fields -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <InputLabel for="senderFirstName" value="First Name *" />
            <TextInput 
              id="senderFirstName" 
              v-model="form.sender.first_name" 
              class="w-full"
              :error="!!form.errors['sender.first_name']"
              placeholder="First name"
            />
            <InputError :message="form.errors['sender.first_name']" />
          </div>
          
          <div>
            <InputLabel for="senderMiddleName" value="Middle Name" />
            <TextInput 
              id="senderMiddleName" 
              v-model="form.sender.middle_name" 
              class="w-full"
              :error="!!form.errors['sender.middle_name']"
              placeholder="Middle name"
            />
            <InputError :message="form.errors['sender.middle_name']" />
          </div>
          
          <div>
            <InputLabel for="senderLastName" value="Last Name *" />
            <TextInput 
              id="senderLastName" 
              v-model="form.sender.last_name" 
              class="w-full"
              :error="!!form.errors['sender.last_name']"
              placeholder="Last name"
            />
            <InputError :message="form.errors['sender.last_name']" />
          </div>
        </div>

        <!-- Company Name (Conditional) -->
        <div v-if="form.sender.customer_category === 'company'" class="space-y-2">
          <InputLabel for="senderCompanyName" value="Company Name *" />
          <TextInput 
            id="senderCompanyName" 
            v-model="form.sender.company_name" 
            class="w-full"
            :error="!!form.errors['sender.company_name']"
            placeholder="Official company name"
          />
          <InputError :message="form.errors['sender.company_name']" />
        </div>

        <!-- Contact Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <InputLabel for="senderEmail" value="Email *" />
            <TextInput 
              id="senderEmail" 
              v-model="form.sender.email" 
              type="email" 
              class="w-full"
              :error="!!form.errors['sender.email']"
              placeholder="contact@example.com"
            />
            <InputError :message="form.errors['sender.email']" />
          </div>

          <div>
            <InputLabel for="senderMobile" value="Mobile *" />
            <TextInput 
              id="senderMobile" 
              v-model="form.sender.mobile" 
              type="tel" 
              class="w-full"
              :error="!!form.errors['sender.mobile']"
              placeholder="09123456789"
              maxlength="11"
            />
            <InputError :message="form.errors['sender.mobile']" />
          </div>

          <div>
            <InputLabel for="senderPhone" value="Phone (Landline)" />
            <TextInput 
              id="senderPhone" 
              v-model="form.sender.phone" 
              type="tel" 
              class="w-full"
              :error="!!form.errors['sender.phone']"
              placeholder="021234567"
              maxlength="9"
            />
            <InputError :message="form.errors['sender.phone']" />
          </div>
        </div>

        <!-- Address Information -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <InputLabel for="senderBuildingNumber" value="Building Number" />
            <TextInput 
              id="senderBuildingNumber" 
              v-model="form.sender.building_number" 
              class="w-full"
              :error="!!form.errors['sender.building_number']"
              placeholder="Building/Unit number"
            />
            <InputError :message="form.errors['sender.building_number']" />
          </div>

          <div>
            <InputLabel for="senderStreet" value="Street *" />
            <TextInput 
              id="senderStreet" 
              v-model="form.sender.street" 
              class="w-full"
              :error="!!form.errors['sender.street']"
              placeholder="Street name"
            />
            <InputError :message="form.errors['sender.street']" />
          </div>

          <div>
            <InputLabel for="senderBarangay" value="Barangay" />
            <TextInput 
              id="senderBarangay" 
              v-model="form.sender.barangay" 
              class="w-full"
              :error="!!form.errors['sender.barangay']"
              placeholder="Barangay/District"
            />
            <InputError :message="form.errors['sender.barangay']" />
          </div>

          <div>
            <InputLabel for="senderCity" value="City/Municipality *" />
            <TextInput 
              id="senderCity" 
              v-model="form.sender.city" 
              class="w-full"
              :error="!!form.errors['sender.city']"
              placeholder="City/Municipality"
            />
            <InputError :message="form.errors['sender.city']" />
          </div>

          <div>
            <InputLabel for="senderProvince" value="Province *" />
            <TextInput 
              id="senderProvince" 
              v-model="form.sender.province" 
              class="w-full"
              :error="!!form.errors['sender.province']"
              placeholder="Province"
            />
            <InputError :message="form.errors['sender.province']" />
          </div>

          <div>
            <InputLabel for="senderZipCode" value="ZIP Code *" />
            <TextInput 
              id="senderZipCode" 
              v-model="form.sender.zip_code" 
              maxlength="4" 
              class="w-full"
              :error="!!form.errors['sender.zip_code']"
              placeholder="1234"
            />
            <InputError :message="form.errors['sender.zip_code']" />
          </div>
        </div>

        <!-- Drop-off Region -->
        <div>
          <InputLabel for="dropOffRegion" value="Drop-off Region *" />
          <SelectInput
            id="dropOffRegion"
            v-model="form.drop_off_region_id"
            :options="regions.map(r => ({ value: r.id, label: r.name }))"
            option-value="value"
            option-label="label"
            class="mt-1 block w-full"
            :error="!!form.errors.drop_off_region_id"
          />
          <InputError :message="form.errors.drop_off_region_id" />
        </div>

        <!-- Notes -->
        <div>
          <InputLabel for="senderNotes" value="Notes" />
          <TextArea 
            id="senderNotes" 
            v-model="form.sender.notes" 
            class="w-full" 
            :rows="3"
            :error="form.errors['sender.notes'] || ''"
            placeholder="Additional delivery instructions, landmarks, or special requirements"
          />
          <InputError :message="form.errors['sender.notes']" />
        </div>

        <!-- Navigation Buttons -->
        <div class="flex justify-center mt-6">
          <PrimaryButton @click="nextStep" :disabled="isLoading">
            Next
          </PrimaryButton>
        </div>
      </div>

      <!-- Step 2: Receiver Details -->
      <div v-if="currentStep === 2" class="space-y-6">
        <h2 class="text-xl flex items-center justify-center font-semibold">Receiver Details</h2>

        <!-- Receiver Type -->
        <div>
          <InputLabel value="Receiver Type" />
          <SelectInput
            v-model="form.receiver.customer_category"
            :options="customerCategoryOptions"
            option-value="value"
            option-label="label"
            class="mt-1 block w-full"
            :error="!!form.errors['receiver.customer_category']"
          />
          <InputError :message="form.errors['receiver.customer_category']" />
        </div>

        <!-- Name Fields -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <InputLabel for="receiverFirstName" value="First Name *" />
            <TextInput 
              id="receiverFirstName" 
              v-model="form.receiver.first_name" 
              class="w-full"
              :error="!!form.errors['receiver.first_name']"
              placeholder="First name"
            />
            <InputError :message="form.errors['receiver.first_name']" />
          </div>
          
          <div>
            <InputLabel for="receiverMiddleName" value="Middle Name" />
            <TextInput 
              id="receiverMiddleName" 
              v-model="form.receiver.middle_name" 
              class="w-full"
              :error="!!form.errors['receiver.middle_name']"
              placeholder="Middle name"
            />
            <InputError :message="form.errors['receiver.middle_name']" />
          </div>
          
          <div>
            <InputLabel for="receiverLastName" value="Last Name *" />
            <TextInput 
              id="receiverLastName" 
              v-model="form.receiver.last_name" 
              class="w-full"
              :error="!!form.errors['receiver.last_name']"
              placeholder="Last name"
            />
            <InputError :message="form.errors['receiver.last_name']" />
          </div>
        </div>

        <!-- Company Name (Conditional) -->
        <div v-if="form.receiver.customer_category === 'company'" class="space-y-2">
          <InputLabel for="receiverCompanyName" value="Company Name *" />
          <TextInput 
            id="receiverCompanyName" 
            v-model="form.receiver.company_name" 
            class="w-full"
            :error="!!form.errors['receiver.company_name']"
            placeholder="Official company name"
          />
          <InputError :message="form.errors['receiver.company_name']" />
        </div>

        <!-- Contact Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <InputLabel for="receiverEmail" value="Email *" />
            <TextInput 
              id="receiverEmail" 
              v-model="form.receiver.email" 
              type="email" 
              class="w-full"
              :error="!!form.errors['receiver.email']"
              placeholder="contact@example.com"
            />
            <InputError :message="form.errors['receiver.email']" />
          </div>

          <div>
            <InputLabel for="receiverMobile" value="Mobile *" />
            <TextInput 
              id="receiverMobile" 
              v-model="form.receiver.mobile" 
              type="tel" 
              class="w-full"
              :error="!!form.errors['receiver.mobile']"
              placeholder="09123456789"
              maxlength="11"
            />
            <InputError :message="form.errors['receiver.mobile']" />
          </div>

          <div>
            <InputLabel for="receiverPhone" value="Phone (Landline)" />
            <TextInput 
              id="receiverPhone" 
              v-model="form.receiver.phone" 
              type="tel" 
              class="w-full"
              :error="!!form.errors['receiver.phone']"
              placeholder="021234567"
              maxlength="9"
            />
            <InputError :message="form.errors['receiver.phone']" />
          </div>
        </div>

        <!-- Address Information -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <InputLabel for="receiverBuildingNumber" value="Building Number" />
            <TextInput 
              id="receiverBuildingNumber" 
              v-model="form.receiver.building_number" 
              class="w-full"
              :error="!!form.errors['receiver.building_number']"
              placeholder="Building/Unit number"
            />
            <InputError :message="form.errors['receiver.building_number']" />
          </div>

          <div>
            <InputLabel for="receiverStreet" value="Street *" />
            <TextInput 
              id="receiverStreet" 
              v-model="form.receiver.street" 
              class="w-full"
              :error="!!form.errors['receiver.street']"
              placeholder="Street name"
            />
            <InputError :message="form.errors['receiver.street']" />
          </div>

          <div>
            <InputLabel for="receiverBarangay" value="Barangay" />
            <TextInput 
              id="receiverBarangay" 
              v-model="form.receiver.barangay" 
              class="w-full"
              :error="!!form.errors['receiver.barangay']"
              placeholder="Barangay/District"
            />
            <InputError :message="form.errors['receiver.barangay']" />
          </div>

          <div>
            <InputLabel for="receiverCity" value="City/Municipality *" />
            <TextInput 
              id="receiverCity" 
              v-model="form.receiver.city" 
              class="w-full"
              :error="!!form.errors['receiver.city']"
              placeholder="City/Municipality"
            />
            <InputError :message="form.errors['receiver.city']" />
          </div>

          <div>
            <InputLabel for="receiverProvince" value="Province *" />
            <TextInput 
              id="receiverProvince" 
              v-model="form.receiver.province" 
              class="w-full"
              :error="!!form.errors['receiver.province']"
              placeholder="Province"
            />
            <InputError :message="form.errors['receiver.province']" />
          </div>

          <div>
            <InputLabel for="receiverZipCode" value="ZIP Code *" />
            <TextInput 
              id="receiverZipCode" 
              v-model="form.receiver.zip_code" 
              maxlength="4" 
              class="w-full"
              :error="!!form.errors['receiver.zip_code']"
              placeholder="1234"
            />
            <InputError :message="form.errors['receiver.zip_code']" />
          </div>
        </div>

        <!-- Pick-up Region -->
        <div>
          <InputLabel for="pickUpRegion" value="Pick-up Region *" />
          <SelectInput
            id="pickUpRegion"
            v-model="form.pick_up_region_id"
            :options="regions.map(r => ({ value: r.id, label: r.name }))"
            option-value="value"
            option-label="label"
            class="mt-1 block w-full"
            :error="!!form.errors.pick_up_region_id"
          />
          <InputError :message="form.errors.pick_up_region_id" />
        </div>

        <!-- Notes -->
        <div>
          <InputLabel for="receiverNotes" value="Notes" />
          <TextArea 
            id="receiverNotes" 
            v-model="form.receiver.notes" 
            class="w-full" 
            :rows="3"
            :error="form.errors['receiver.notes'] || ''"
            placeholder="Additional delivery instructions, landmarks, or special requirements"
          />
          <InputError :message="form.errors['receiver.notes']" />
        </div>

        <!-- Navigation Buttons -->
        <div class="flex justify-between mt-6">
          <PrimaryButton @click="prevStep" :disabled="isLoading">
            Back
          </PrimaryButton>
          <PrimaryButton @click="nextStep" :disabled="isLoading">
            Next
          </PrimaryButton>
        </div>
      </div>

      <!-- Step 3: Package Type Selection -->
      <div v-if="currentStep === 3" class="space-y-6">
        <h2 class="text-xl flex items-center justify-center font-semibold">Package Type Selection</h2>

        <!-- Advisory Message -->
        <div class="bg-yellow-100 text-yellow-800 p-4 rounded-lg">
          📢 <strong>Advisory:</strong> We only carry packages with a maximum volume of
          <strong>10 cubic meters</strong> (when converted from cm: height × width × length / 1,000,000) 
          and a weight of <strong>100 kg</strong> per package. Ensure your items meet these requirements.
        </div>

        <!-- Dynamic Package Input -->
        <div v-for="(pkg, index) in form.packages" :key="index" class="border p-4 rounded-lg space-y-4">
          <div class="flex justify-between items-center">
            <h3 class="text-lg font-semibold">📦 Package {{ index + 1 }}</h3>
            <div class="flex space-x-2">
              <SecondaryButton 
                @click="duplicatePackage(index)"
                :disabled="isLoading"
                class="!px-3 !py-1.5 text-sm"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                Duplicate
              </SecondaryButton>
              <DangerButton 
                v-if="form.packages.length > 1" 
                @click="removePackage(index)"
                :disabled="isLoading"
                class="!px-3 !py-1.5 text-sm"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Remove
              </DangerButton>
            </div>
          </div>

          <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <InputLabel :for="`preset-${index}`" value="Package Type *" />
                <SelectInput
                  :id="`preset-${index}`"
                  v-model="pkg.preset"
                  :options="containerPresets"
                  option-value="value"
                  option-label="label"
                  class="mt-1 block w-full"
                  @update:modelValue="(val) => handlePresetChange(val, index)"
                />
              </div>
              
              <!-- Package Type Description -->
              <div v-if="pkg.preset" class="bg-gray-50 p-4 rounded-lg">
                <h4 class="font-semibold mb-2">About this package type:</h4>

                <div v-if="pkg.preset === 'small_pouch'">
                  <p>Perfect for small, flat items like documents, letters, or small accessories.</p>
                  <p class="mt-1 text-sm text-gray-600">Example: Passport, jewelry, small electronics</p>
                </div>

                <div v-else-if="pkg.preset === 'medium_box'">
                  <p>Standard box for books, small electronics, or medium-sized items.</p>
                  <p class="mt-1 text-sm text-gray-600">Example: Shoes, small appliances, books</p>
                </div>

                <div v-else-if="pkg.preset === 'large_box'">
                  <p>Great for larger items that need sturdy packaging.</p>
                  <p class="mt-1 text-sm text-gray-600">Example: Clothing, kitchenware, medium electronics</p>
                </div>

                <div v-else-if="pkg.preset === 'xl_box'">
                  <p>For bulky items that require extra space.</p>
                  <p class="mt-1 text-sm text-gray-600">Example: Lamps, small furniture, large toys</p>
                </div>

                <div v-else-if="pkg.preset === 'large_sack'">
                  <p>Best for loose, non-fragile items that can be packed together.</p>
                  <p class="mt-1 text-sm text-gray-600">Example: Clothing, fabrics, plush toys</p>
                </div>

                <div v-else-if="pkg.preset === 'standard_roll'">
                  <p>Good for compact, rollable items that aren't too long.</p>
                  <p class="mt-1 text-sm text-gray-600">Example: Blueprints, wrapping paper, yoga mats</p>
                </div>

                <div v-else-if="pkg.preset === 'bundle_roll'">
                  <p>Designed for long, narrow items that can be rolled.</p>
                  <p class="mt-1 text-sm text-gray-600">Example: Posters, carpets, banners</p>
                </div>

                <div v-else-if="pkg.preset === 'custom'">
                  <p>Create your own package dimensions for unique items.</p>
                  <p class="mt-1 text-sm text-gray-600">Example: Oversized items, irregular shapes</p>
                </div>
              </div>
            </div>

            <!-- Preset Image Gallery with Navigation Arrows -->
            <div v-if="pkg.preset && pkg.preset !== 'custom'" class="relative">
              <div class="relative w-full h-56 bg-gray-100 rounded-lg overflow-hidden shadow-md flex flex-col justify-center items-center pb-12">
                <button 
                  @click="cyclePreset(index, -1)"
                  class="absolute left-2 top-1/2 transform -translate-y-1/2 z-10 bg-white bg-opacity-70 rounded-full p-2 hover:bg-opacity-100 transition-all"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                  </svg>
                </button>
                
                <img 
                  :src="containerPresets.find(p => p.value === pkg.preset)?.image" 
                  :alt="containerPresets.find(p => p.value === pkg.preset)?.label"
                  class="max-h-44 max-w-full object-contain mx-auto"
                />
                
                <button 
                  @click="cyclePreset(index, 1)"
                  class="absolute right-2 top-1/2 transform -translate-y-1/2 z-10 bg-white bg-opacity-70 rounded-full p-2 hover:bg-opacity-100 transition-all"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </button>
                
                <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-70 text-white p-2 text-center text-sm">
                  {{ containerPresets.find(p => p.value === pkg.preset)?.label }}
                  <div class="text-xs opacity-80">
                    {{ pkg.preset === 'custom' ? 'Custom dimensions' : 
                      `${containerPresets.find(p => p.value === pkg.preset)?.dimensions.length}cm × 
                      ${containerPresets.find(p => p.value === pkg.preset)?.dimensions.width}cm × 
                      ${containerPresets.find(p => p.value === pkg.preset)?.dimensions.height}cm` }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Add Package Button -->
        <div class="flex justify-center pt-2">
          <PrimaryButton @click="addPackage" :disabled="isLoading" class="!px-4 !py-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Add Another Package
          </PrimaryButton>
        </div>

        <!-- Navigation Buttons -->
        <div class="flex justify-between pt-4 border-t">
          <PrimaryButton @click="currentStep = 2" :disabled="isLoading" class="!px-4 !py-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back
          </PrimaryButton>
          <PrimaryButton 
            @click="currentStep = 4" 
            :disabled="isLoading || !form.packages.every(p => p.preset)"
            class="!px-4 !py-2"
          >
            Next
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </PrimaryButton>
        </div>
      </div>

      <!-- Step 4: Package Details -->
      <div v-if="currentStep === 4" class="space-y-6">
        <h2 class="text-xl flex items-center justify-center font-semibold">Package Details</h2>

        <!-- Dynamic Package Input -->
        <div v-for="(pkg, index) in form.packages" :key="index" class="border p-4 rounded-lg space-y-4">
          <div class="flex justify-between items-center">
            <h3 class="text-lg font-semibold">📦 Package {{ index + 1 }} - {{ pkg.preset ? containerPresets.find(p => p.value === pkg.preset)?.label : 'Custom Package' }}</h3>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <InputLabel :for="`item_name-${index}`" value="Package Name *" />
              <TextInput
                :id="`item_name-${index}`"
                v-model="pkg.item_name"
                class="w-full"
                :error="!!form.errors[`packages.${index}.item_name`]"
                placeholder="e.g. Document, Electronics, Clothing"
              />
              <InputError :message="form.errors[`packages.${index}.item_name`]" />
            </div>

            <div>
              <InputLabel :for="`category-${index}`" value="Package Category *" />
              <SelectInput
                :id="`category-${index}`"
                v-model="pkg.category"
                :options="[
                  { value: 'piece', label: 'Piece' },
                  { value: 'carton', label: 'Carton' },
                  { value: 'sack', label: 'Sack' },
                  { value: 'bundle', label: 'Bundle' },
                  { value: 'roll', label: 'Roll' },
                  { value: 'B/R', label: 'B/R' },
                  { value: 'C/S', label: 'C/S' }
                ]"
                option-value="value"
                option-label="label"
                class="mt-1 block w-full"
                :error="!!form.errors[`packages.${index}.category`]"
                :disabled="pkg.preset && pkg.preset !== 'custom'"
              />
              <InputError :message="form.errors[`packages.${index}.category`]" />
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <InputLabel :for="`description-${index}`" value="Special Instructions" />
              <TextArea
                :id="`description-${index}`"
                v-model="pkg.description"
                class="w-full"
                :rows="3"
                placeholder="Package contents, special handling instructions, etc."
                :error="form.errors[`packages.${index}.description`] || ''"
              />
              <InputError :message="form.errors[`packages.${index}.description`]" />
            </div>

            <div>
              <InputLabel :for="`photo-${index}`" value="Package Photo (Optional)" />
              <input
                :id="`photo-${index}`"
                type="file"
                accept="image/*"
                @change="(e) => handlePhotoUpload(e, index)"
                class="mt-1 block w-full text-sm text-gray-500
                  file:mr-4 file:py-2 file:px-4
                  file:rounded-md file:border-0
                  file:text-sm file:font-semibold
                  file:bg-blue-50 file:text-blue-700
                  hover:file:bg-blue-100"
              />
              <InputError :message="form.errors[`packages.${index}.photo`]" />
              <div v-if="pkg.photo" class="mt-2 text-sm text-gray-600">
                Selected: {{ pkg.photo.name }}
              </div>
              <div v-if="pkg.photo_url" class="mt-2">
                <img :src="pkg.photo_url" class="h-20 object-cover rounded shadow" />
              </div>
              <div v-if="pkg.photo_path" class="mt-2">
                <img :src="pkg.photo_path" class="h-20 object-cover rounded shadow" />
              </div>
            </div>
          </div>

          <!-- Dimensions & Package Value -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <InputLabel :for="`value-${index}`" value="Package Value (₱) *" />
              <TextInput
                :id="`value-${index}`"
                v-model.number="pkg.value"
                type="number"
                min="0"
                step="0.01"
                class="w-full"
                :error="!!form.errors[`packages.${index}.value`]"
                placeholder="Declared value in ₱"
              />
              <InputError :message="form.errors[`packages.${index}.value`]" />
            </div>

            <!-- Custom: Show editable dimensions and weight -->
            <template v-if="pkg.preset === 'custom'">
              <div>
                <InputLabel :for="`height-${index}`" value="Height (cm) *" />
                <TextInput
                  :id="`height-${index}`"
                  v-model.number="pkg.height"
                  type="number"
                  min="0.01"
                  step="0.01"
                  class="w-full"
                  :error="!!form.errors[`packages.${index}.height`]"
                  placeholder="0.00"
                />
                <InputError :message="form.errors[`packages.${index}.height`]" />
              </div>
              <div>
                <InputLabel :for="`width-${index}`" value="Width (cm) *" />
                <TextInput
                  :id="`width-${index}`"
                  v-model.number="pkg.width"
                  type="number"
                  min="0.01"
                  step="0.01"
                  class="w-full"
                  :error="!!form.errors[`packages.${index}.width`]"
                  placeholder="0.00"
                />
                <InputError :message="form.errors[`packages.${index}.width`]" />
              </div>
              <div>
                <InputLabel :for="`length-${index}`" value="Length (cm) *" />
                <TextInput
                  :id="`length-${index}`"
                  v-model.number="pkg.length"
                  type="number"
                  min="0.01"
                  step="0.01"
                  class="w-full"
                  :error="!!form.errors[`packages.${index}.length`]"
                  placeholder="0.00"
                />
                <InputError :message="form.errors[`packages.${index}.length`]" />
              </div>
              <div>
                <InputLabel :for="`weight-${index}`" value="Weight (kg) *" />
                <TextInput
                  :id="`weight-${index}`"
                  v-model.number="pkg.weight"
                  type="number"
                  min="0.01"
                  step="0.01"
                  class="w-full"
                  :error="!!form.errors[`packages.${index}.weight`]"
                  placeholder="0.00"
                />
                <InputError :message="form.errors[`packages.${index}.weight`]" />
              </div>
            </template>

            <!-- Preset: Hide dimensions, show weight input capped to preset max -->
            <template v-else>
              <div>
                <InputLabel :for="`weight-${index}`" :value="`Weight (kg) (max ${containerPresets.find(p => p.value === pkg.preset)?.weight || 0}kg)`" />
                <TextInput
                  :id="`weight-${index}`"
                  v-model.number="pkg.weight"
                  type="number"
                  min="0.01"
                  :max="containerPresets.find(p => p.value === pkg.preset)?.weight || 0"
                  step="0.01"
                  class="w-full"
                  :error="!!form.errors[`packages.${index}.weight`]"
                  :placeholder="`Max ${containerPresets.find(p => p.value === pkg.preset)?.weight || 0} kg`"
                />
                <InputError :message="form.errors[`packages.${index}.weight`]" />
                <div class="text-xs text-gray-500 mt-1">
                  Max allowed: {{ containerPresets.find(p => p.value === pkg.preset)?.weight || 0 }} kg
                </div>
              </div>
            </template>
          </div>
        </div>

        <!-- Navigation Buttons: Only show once after all packages -->
        <div class="flex justify-between pt-4">
          <PrimaryButton @click="currentStep = 3" :disabled="isLoading" class="!px-4 !py-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back
          </PrimaryButton>
          <PrimaryButton 
            @click="currentStep = 5" 
            :disabled="isLoading"
            class="!px-4 !py-2"
          >
            Next
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </PrimaryButton>
        </div>
      </div>

      <!-- Step 5: Payment (formerly step 4) -->
      <div v-if="currentStep === 5" class="space-y-6">
        <h2 class="text-xl flex items-center justify-center font-semibold">Payment Price Calculator</h2>

        <!-- Pricing Info -->
        <div class="bg-blue-100 text-blue-800 p-4 rounded-lg" v-if="priceMatrix">
          💰 <strong>Pricing Policy:</strong><br>
          - Base Fee: <strong>₱{{ formatCurrency(priceMatrix.base_fee) }}</strong><br>
          - ₱{{ formatCurrency(priceMatrix.volume_rate) }} per cubic meter<br>
          - ₱{{ formatCurrency(priceMatrix.weight_rate) }} per kilogram<br>
          - ₱{{ formatCurrency(priceMatrix.package_rate) }} per package
        </div>

        <!-- Package Summary -->
        <div class="p-4 bg-gray-100 rounded-lg space-y-4">
          <template v-for="(pkg, index) in form.packages" :key="index">
            <p>📦 <strong>Package {{ index + 1 }}:</strong></p>
            <p>📏 Dimensions: {{ pkg.height }}cm (H) × {{ pkg.width }}cm (W) × {{ pkg.length }}cm (L)</p>
            <p>📦 Volume: {{ ((pkg.height / 100) * (pkg.width / 100) * (pkg.length / 100)).toFixed(3) }} m³ ({{ pkg.height / 100 }}m × {{ pkg.width / 100 }}m × {{ pkg.length / 100 }}m)</p>
            <p>⚖️ Weight: {{ pkg.weight }} kg</p>
           
            <hr />
          </template>

          <div v-if="form.priceBreakdown" class="space-y-2">
            <p>🧾 <strong>Price Breakdown:</strong></p>
            <p>- Base Fee: ₱{{ formatCurrency(form.priceBreakdown.base_fee) }}</p>
            <p>- Volume Fee: ₱{{ formatCurrency(form.priceBreakdown.volume_fee) }}</p>
            <p>- Weight Fee: ₱{{ formatCurrency(form.priceBreakdown.weight_fee) }}</p>
            <p>- Package Fee: ₱{{ formatCurrency(form.priceBreakdown.package_fee) }}</p>
            <p v-if="form.priceBreakdown.distance_multiplier > 1">
              - Distance Multiplier: {{ form.priceBreakdown.distance_multiplier }}x
            </p>
            <p class="text-lg font-semibold">💵 <strong>Total Price:</strong> ₱{{ formatCurrency(form.total_price) }}</p>
          </div>
        </div>

        <!-- Payment Method -->
        <div>
          <InputLabel for="paymentMethod" value="Payment Method *" />
          <SelectInput
            id="paymentMethod"
            v-model="form.payment_method"
            :options="paymentMethodOptions"
            option-value="value"
            option-label="label"
            class="mt-1 block w-full"
            :error="!!form.errors.payment_method"
          />
          <InputError :message="form.errors.payment_method" />
        </div>

        <!-- Navigation Buttons -->
        <div class="flex justify-between pt-4">
          <PrimaryButton @click="prevStep" :disabled="isLoading">
            Back
          </PrimaryButton>
          <PrimaryButton 
            @click="submitForm" 
            :disabled="isLoading"
            :class="{ 'opacity-75 cursor-not-allowed': isLoading }"
          >
            <span v-if="isLoading">Updating...</span>
            <span v-else>Update Request</span>
          </PrimaryButton>
        </div>
      </div>

      <!-- Step 6: Confirmation (formerly step 5) -->
      <div v-if="currentStep === 6" class="space-y-6">
        <h2 class="text-xl font-semibold">Request Updated Successfully</h2>

        <!-- Success Message -->
        <div class="bg-green-100 text-green-800 p-4 rounded-lg text-center">
          <p class="text-lg font-semibold">✅ Your delivery request has been updated successfully!</p>
          <p class="mt-2">Sender: {{ form.sender.first_name }} {{ form.sender.last_name }}</p>
          <p class="mt-2">Receiver: {{ form.receiver.first_name }} {{ form.receiver.last_name }}</p>
          <p class="mt-2">Total Price: ₱{{ formatCurrency(form.total_price) }}</p>
        </div>

        <!-- Navigation Buttons -->
        <div class="flex justify-center pt-6">
          <PrimaryButton
            @click="
              props.context === 'admin'
                ? $inertia.visit(route('deliveries.pending'))
                : $inertia.visit(route('customer.delivery-requests.index'))
            "
          >
            Back to Pending Requests
          </PrimaryButton>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextArea from '@/Components/TextArea.vue';
import { useForm, router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  delivery: Object,
  regions: Array,
  categories: Array,
  status: String,
  success: String,
  error: String,
  priceMatrix: Object,
  context: String
});

// Container Presets
const containerPresets = [
  {
    label: 'Small Pouch (25x15x1 cm, max 0.5kg)',
    value: 'small_pouch',
    dimensions: { height: 1, width: 15, length: 25 },
    weight: 0.5,
    category: 'piece',
    image: '/images/presets/small_pouch.png'
  },
  {
    label: 'Medium Box (30x25x20 cm, max 5kg)',
    value: 'medium_box',
    dimensions: { height: 20, width: 25, length: 30 },
    weight: 5,
    category: 'carton',
    image: '/images/presets/medium_box.png'
  },
  {
    label: 'Large Box (50x40x35 cm, max 10kg)',
    value: 'large_box',
    dimensions: { height: 35, width: 40, length: 50 },
    weight: 10,
    category: 'carton',
    image: '/images/presets/large_box.png'
  },
  {
    label: 'Extra Large Box (70x50x50 cm, max 15kg)',
    value: 'xl_box',
    dimensions: { height: 50, width: 50, length: 70 },
    weight: 15,
    category: 'carton',
    image: '/images/presets/xl_box.png'
  },
  {
    label: 'Large Sack (60x40x40 cm, max 20kg)',
    value: 'large_sack',
    dimensions: { height: 40, width: 40, length: 60 },
    weight: 20,
    category: 'sack',
    image: '/images/presets/large_sack.png'
  },
  {
    label: 'Standard Roll (50x10x10 cm, max 1.5kg)',
    value: 'standard_roll',
    dimensions: { height: 10, width: 10, length: 50 },
    weight: 1.5,
    category: 'roll',
    image: '/images/presets/standard_roll.png' 
  },
  {
    label: 'Bundle Roll (100x10x10 cm, max 3kg)',
    value: 'bundle_roll',
    dimensions: { height: 10, width: 10, length: 100 },
    weight: 3,
    category: 'B/R',
    image: '/images/presets/bundle_roll.png'
  },
  {
    label: 'Custom Size',
    value: 'custom',
    category: 'C/S',
    image: '/images/presets/custom.png'
  }
];

// Form Step Management
const currentStep = ref(1);
const isLoading = ref(false);

// For dropdown options 
const customerCategoryOptions = [
  { value: 'individual', label: 'Individual' },
  { value: 'company', label: 'Company' }
];

const paymentMethodOptions = [
  { value: 'cash', label: 'Cash' },
  { value: 'card', label: 'Credit/Debit Card' },
  { value: 'online', label: 'Online Payment' }
];

// Form initialization
const form = useForm({
  id: props.delivery?.id || null,
  sender: {
    customer_category: props.delivery?.sender?.customer_category || 'individual',
    first_name: props.delivery?.sender?.first_name || '',
    middle_name: props.delivery?.sender?.middle_name || '',
    last_name: props.delivery?.sender?.last_name || '',
    company_name: props.delivery?.sender?.company_name || '',
    email: props.delivery?.sender?.email || '',
    mobile: props.delivery?.sender?.mobile || '',
    phone: props.delivery?.sender?.phone || '',
    building_number: props.delivery?.sender?.building_number || '',
    street: props.delivery?.sender?.street || '',
    barangay: props.delivery?.sender?.barangay || '',
    city: props.delivery?.sender?.city || '',
    province: props.delivery?.sender?.province || '',
    zip_code: props.delivery?.sender?.zip_code || '',
    notes: props.delivery?.sender?.notes || ''
  },
  receiver: {
    customer_category: props.delivery?.receiver?.customer_category || 'individual',
    first_name: props.delivery?.receiver?.first_name || '',
    middle_name: props.delivery?.receiver?.middle_name || '',
    last_name: props.delivery?.receiver?.last_name || '',
    company_name: props.delivery?.receiver?.company_name || '',
    email: props.delivery?.receiver?.email || '',
    mobile: props.delivery?.receiver?.mobile || '',
    phone: props.delivery?.receiver?.phone || '',
    building_number: props.delivery?.receiver?.building_number || '',
    street: props.delivery?.receiver?.street || '',
    barangay: props.delivery?.receiver?.barangay || '',
    city: props.delivery?.receiver?.city || '',
    province: props.delivery?.receiver?.province || '',
    zip_code: props.delivery?.receiver?.zip_code || '',
    notes: props.delivery?.receiver?.notes || ''
  },
  pick_up_region_id: props.delivery?.pick_up_region_id || '',
  drop_off_region_id: props.delivery?.drop_off_region_id || '',
  payment_method: props.delivery?.payment_method || '',
  total_price: props.delivery?.total_price || 0,
  priceBreakdown: null,
  packages: props.delivery?.packages?.map(pkg => ({
    id: pkg.id,
    item_name: pkg.item_name || '',
    description: pkg.description || '',
    category: pkg.category || 'piece',
    height: pkg.height || '',
    width: pkg.width || '',
    length: pkg.length || '',
    weight: pkg.weight || '',
    value: pkg.value || 0,
    photo: null,
    photo_url: pkg.photo_path ? `/storage/${pkg.photo_path}` : null,
    photo_path: pkg.photo_path || null,
    preset: 'custom' // Default to custom for existing packages
  })) || [{
    item_name: '',
    description: '',
    category: 'piece',
    height: '',
    width: '',
    length: '',
    weight: '',
    value: 0,
    photo: null,
    photo_url: null,
    photo_path: null,
    preset: ''
  }]
});

// Format currency
const formatCurrency = (value) => {
  const num = Number(value);
  return isNaN(num) ? '0.00' : num.toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

// Handle preset changes
const handlePresetChange = (presetValue, index) => {
  if (presetValue === 'custom') {
    form.packages[index] = {
      ...form.packages[index],
      height: '',
      width: '',
      length: '',
      weight: '',
      category: 'C/S',
      preset: 'custom'
    };
  } else {
    const preset = containerPresets.find(p => p.value === presetValue);
    if (preset) {
      form.packages[index] = {
        ...form.packages[index],
        height: preset.dimensions.height,
        width: preset.dimensions.width,
        length: preset.dimensions.length,
        weight: preset.weight,
        category: preset.category,
        preset: preset.value
      };
    }
  }
};

// Cycle through presets
const cyclePreset = (packageIndex, direction) => {
  const currentPreset = form.packages[packageIndex].preset;
  if (!currentPreset) return;

  const currentIndex = containerPresets.findIndex(p => p.value === currentPreset);
  if (currentIndex === -1) return;

  let newIndex = currentIndex + direction;
  
  if (newIndex < 0) {
    newIndex = containerPresets.length - 1;
  } else if (newIndex >= containerPresets.length) {
    newIndex = 0;
  }

  if (containerPresets[newIndex].value === 'custom' && newIndex !== 0 && newIndex !== containerPresets.length - 1) {
    newIndex += direction;
    if (newIndex < 0) newIndex = containerPresets.length - 1;
    if (newIndex >= containerPresets.length) newIndex = 0;
  }

  handlePresetChange(containerPresets[newIndex].value, packageIndex);
};

// Package Management
const addPackage = () => {
  form.packages.push({
    item_name: '',
    description: '',
    category: '',
    height: '',
    width: '',
    length: '',
    weight: '',
    value: 0,
    photo: null,
    photo_url: null,
    photo_path: null,
    preset: ''
  });
};

const duplicatePackage = (index) => {
  const packageCopy = JSON.parse(JSON.stringify(form.packages[index]));
  form.packages.splice(index + 1, 0, packageCopy);
};

const removePackage = (index) => {
  if (form.packages.length > 1) {
    form.packages.splice(index, 1);
  }
};

// Handle photo upload
const handlePhotoUpload = (event, index) => {
  const file = event.target.files[0];
  if (file) {
    if (!file.type.match('image.*')) {
      form.setError(`packages.${index}.photo`, 'Only image files are allowed');
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      form.setError(`packages.${index}.photo`, 'File size must be less than 5MB');
      return;
    }
    
    form.packages[index].photo = file;
    form.clearErrors(`packages.${index}.photo`);
    form.packages[index].photo_url = URL.createObjectURL(file);
  }
};

// Navigation
const nextStep = () => {
  if (validateStep()) {
    currentStep.value++;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};

const prevStep = () => {
  currentStep.value--;
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

// Validation functions
const isValidEmail = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
const isValidMobile = (mobile) => /^(09|\+639)\d{9}$/.test(mobile);
const isValidPhone = (phone) => phone === '' || /^[0-9]{7,15}$/.test(phone);
const isValidZipCode = (zip) => /^[0-9]{4}$/.test(zip);

// Validate Current Step
const validateStep = () => {
  form.clearErrors();
  let isValid = true;

  if (currentStep.value === 1) {
    // Sender validation
    if (!form.sender.first_name.trim()) {
      form.setError('sender.first_name', 'First name is required.');
      isValid = false;
    }
    if (!form.sender.last_name.trim()) {
      form.setError('sender.last_name', 'Last name is required.');
      isValid = false;
    }
    if (form.sender.customer_category === 'company' && !form.sender.company_name.trim()) {
      form.setError('sender.company_name', 'Company Name is required for companies.');
      isValid = false;
    }
    if (!form.sender.mobile.trim()) {
      form.setError('sender.mobile', 'Mobile number is required.');
      isValid = false;
    } else if (!isValidMobile(form.sender.mobile)) {
      form.setError('sender.mobile', 'Please enter a valid Philippine mobile number (e.g. 09123456789).');
      isValid = false;
    }
    if (!form.sender.email.trim()) {
      form.setError('sender.email', 'Email is required.');
      isValid = false;
    } else if (!isValidEmail(form.sender.email)) {
      form.setError('sender.email', 'Please enter a valid email address.');
      isValid = false;
    }
    if (form.sender.phone && !isValidPhone(form.sender.phone)) {
      form.setError('sender.phone', 'Please enter a valid phone number.');
      isValid = false;
    }
    if (!form.sender.street.trim()) {
      form.setError('sender.street', 'Street is required.');
      isValid = false;
    }
    if (!form.sender.city.trim()) {
      form.setError('sender.city', 'City is required.');
      isValid = false;
    }
    if (!form.sender.province.trim()) {
      form.setError('sender.province', 'Province is required.');
      isValid = false;
    }
    if (!form.sender.zip_code.trim()) {
      form.setError('sender.zip_code', 'ZIP Code is required.');
      isValid = false;
    } else if (!isValidZipCode(form.sender.zip_code)) {
      form.setError('sender.zip_code', 'Please enter a valid 4-digit ZIP code.');
      isValid = false;
    }
    if (!form.drop_off_region_id) {
      form.setError('drop_off_region_id', 'Drop-off region is required.');
      isValid = false;
    }
  }

  if (currentStep.value === 2) {
    // Receiver validation
    if (!form.receiver.first_name.trim()) {
      form.setError('receiver.first_name', 'First name is required.');
      isValid = false;
    }
    if (!form.receiver.last_name.trim()) {
      form.setError('receiver.last_name', 'Last name is required.');
      isValid = false;
    }
    if (form.receiver.customer_category === 'company' && !form.receiver.company_name.trim()) {
      form.setError('receiver.company_name', 'Company Name is required for companies.');
      isValid = false;
    }
    if (!form.receiver.mobile.trim()) {
      form.setError('receiver.mobile', 'Mobile number is required.');
      isValid = false;
    } else if (!isValidMobile(form.receiver.mobile)) {
      form.setError('receiver.mobile', 'Please enter a valid Philippine mobile number (e.g. 09123456789).');
      isValid = false;
    }
    if (!form.receiver.email.trim()) {
      form.setError('receiver.email', 'Email is required.');
      isValid = false;
    } else if (!isValidEmail(form.receiver.email)) {
      form.setError('receiver.email', 'Please enter a valid email address.');
      isValid = false;
    }
    if (form.receiver.phone && !isValidPhone(form.receiver.phone)) {
      form.setError('receiver.phone', 'Please enter a valid phone number.');
      isValid = false;
    }
    if (!form.receiver.street.trim()) {
      form.setError('receiver.street', 'Street is required.');
      isValid = false;
    }
    if (!form.receiver.city.trim()) {
      form.setError('receiver.city', 'City is required.');
      isValid = false;
    }
    if (!form.receiver.province.trim()) {
      form.setError('receiver.province', 'Province is required.');
      isValid = false;
    }
    if (!form.receiver.zip_code.trim()) {
      form.setError('receiver.zip_code', 'ZIP Code is required.');
      isValid = false;
    } else if (!isValidZipCode(form.receiver.zip_code)) {
      form.setError('receiver.zip_code', 'Please enter a valid 4-digit ZIP code.');
      isValid = false;
    }
    if (!form.pick_up_region_id) {
      form.setError('pick_up_region_id', 'Pick-up region is required.');
      isValid = false;
    }
  }
  
  if (currentStep.value === 3) {
    // Package validation
    form.packages.forEach((pkg, index) => {
      if (!pkg.item_name.trim()) {
        form.setError(`packages.${index}.item_name`, 'Package name is required.');
        isValid = false;
      }
      
      const height = parseFloat(pkg.height) || 0;
      const width = parseFloat(pkg.width) || 0;
      const length = parseFloat(pkg.length) || 0;
      const weight = parseFloat(pkg.weight) || 0;
      const value = parseFloat(pkg.value) || 0;
      
      if (height <= 0) {
        form.setError(`packages.${index}.height`, 'Height must be greater than 0.');
        isValid = false;
      }
      if (width <= 0) {
        form.setError(`packages.${index}.width`, 'Width must be greater than 0.');
        isValid = false;
      }
      if (length <= 0) {
        form.setError(`packages.${index}.length`, 'Length must be greater than 0.');
        isValid = false;
      }
      if (weight <= 0) {
        form.setError(`packages.${index}.weight`, 'Weight must be greater than 0.');
        isValid = false;
      }
      if (value < 0) {
        form.setError(`packages.${index}.value`, 'Package value cannot be negative.');
        isValid = false;
      }
      
      const volume = (height / 100) * (width / 100) * (length / 100);
      if (volume > 10) {
          form.setError(`packages.${index}.height`, 'Package volume exceeds 10 m³.');
          isValid = false;
      }
      if (weight > 100) {
          form.setError(`packages.${index}.weight`, 'Package weight exceeds 100 kg.');
          isValid = false;
      }
    });
  }
  
  if (currentStep.value === 5) {
    if (!form.payment_method) {
      form.setError('payment_method', 'Payment method is required.');
      isValid = false;
    }
  }

  return isValid;
};

// Price Calculation
const calculatePrice = async () => {
  if (!form.packages.length || !form.pick_up_region_id || !form.drop_off_region_id) return;

  try {
    const packagesData = form.packages.map(pkg => ({
      height: parseFloat(pkg.height) || 0,
      width: parseFloat(pkg.width) || 0,
      length: parseFloat(pkg.length) || 0,
      weight: parseFloat(pkg.weight) || 0
    }));

    const response = await axios.post(route('customer.delivery-requests.calculate-price'), {
      packages: packagesData,
      pick_up_region_id: form.pick_up_region_id,
      drop_off_region_id: form.drop_off_region_id
    });

    form.priceBreakdown = response.data.breakdown;
    form.total_price = response.data.total_price;
  } catch (error) {
    console.error('Failed to calculate price:', error);
    form.priceBreakdown = null;
    form.total_price = 0;
  }
};

// Watch for package changes to recalculate price
watch(() => [
  ...form.packages.map(pkg => [
    pkg.height,
    pkg.width,
    pkg.length,
    pkg.weight
  ]),
  form.pick_up_region_id,
  form.drop_off_region_id
], () => {
  calculatePrice();
}, { deep: true });

// Submit form
const submitForm = () => {
  if (validateStep()) {
    isLoading.value = true;
    form.total_price = form.priceBreakdown ? form.total_price : 0;

    const formData = new FormData();

    // Add all form data to FormData object
    formData.append('_method', 'PUT'); // For PUT request
    formData.append('id', form.id);

    // Receiver fields
    formData.append('receiver[first_name]', form.receiver.first_name);
    formData.append('receiver[middle_name]', form.receiver.middle_name || '');
    formData.append('receiver[last_name]', form.receiver.last_name || '');
    formData.append('receiver[company_name]', form.receiver.company_name || '');
    formData.append('receiver[email]', form.receiver.email);
    formData.append('receiver[mobile]', form.receiver.mobile || '');
    formData.append('receiver[phone]', form.receiver.phone || '');
    formData.append('receiver[building_number]', form.receiver.building_number || '');
    formData.append('receiver[street]', form.receiver.street || '');
    formData.append('receiver[barangay]', form.receiver.barangay || '');
    formData.append('receiver[city]', form.receiver.city || '');
    formData.append('receiver[province]', form.receiver.province || '');
    formData.append('receiver[zip_code]', form.receiver.zip_code || '');
    formData.append('receiver[notes]', form.receiver.notes || '');

    // Sender notes
    formData.append('sender[notes]', form.sender.notes || '');

    // Region fields
    formData.append('pick_up_region_id', form.pick_up_region_id);
    formData.append('drop_off_region_id', form.drop_off_region_id);

    // Payment method
    formData.append('payment_method', form.payment_method);

    // Packages
    form.packages.forEach((pkg, index) => {
      formData.append(`packages[${index}][id]`, pkg.id || '');
      formData.append(`packages[${index}][item_name]`, pkg.item_name);
      formData.append(`packages[${index}][category]`, pkg.category);
      formData.append(`packages[${index}][description]`, pkg.description || '');
      formData.append(`packages[${index}][value]`, pkg.value || 0);
      formData.append(`packages[${index}][height]`, pkg.height);
      formData.append(`packages[${index}][width]`, pkg.width);
      formData.append(`packages[${index}][length]`, pkg.length);
      formData.append(`packages[${index}][weight]`, pkg.weight);
      
      if (pkg.photo instanceof File) {
        formData.append(`packages[${index}][photo]`, pkg.photo);
      }
      
      if (pkg.photo_path) {
        formData.append(`packages[${index}][photo_path]`, pkg.photo_path);
      }
    });

    form.post(route('deliveries.update', { delivery: form.id }), {
      data: formData,
      forceFormData: true,
      preserveScroll: true,
      headers: {
        'Content-Type': 'multipart/form-data'
      },
      onSuccess: () => {
        currentStep.value = 6;
        isLoading.value = false;
      },
      onError: (errors) => {
        console.error('Submission failed:', errors);
        isLoading.value = false;

        if (errors.sender?.mobile) form.setError('sender.mobile', errors.sender.mobile);
        if (errors.sender?.email) form.setError('sender.email', errors.sender.email);
        if (errors.receiver?.mobile) form.setError('receiver.mobile', errors.receiver.mobile);
        if (errors.receiver?.email) form.setError('receiver.email', errors.receiver.email);

        const firstError = Object.keys(errors)[0];
        if (firstError) {
          const element = document.querySelector(`[name="${firstError}"]`);
          if (element) element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      },
    });
  }
};
</script>