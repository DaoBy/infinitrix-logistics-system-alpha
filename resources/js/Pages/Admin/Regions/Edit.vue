<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Edit Region</h2>
        <SecondaryButton @click="cancel">Cancel</SecondaryButton>
      </div>
    </template>

    <div v-if="status || success || error" class="mb-6">
      <div v-if="status" class="p-4 bg-blue-100 text-blue-800 rounded">{{ status }}</div>
      <div v-if="success" class="p-4 bg-green-100 text-green-800 rounded">{{ success }}</div>
      <div v-if="error" class="p-4 bg-red-100 text-red-800 rounded">{{ error }}</div>
    </div>

    <div class="max-w-3xl mx-auto py-8">
      <div class="bg-white p-6 rounded-lg shadow-sm">
        <form @submit.prevent="submit">
          <div class="space-y-6">
            <div>
              <InputLabel for="name" value="Region Name *" />
              <TextInput 
                id="name" 
                v-model="form.name" 
                class="mt-1 block w-full"
                required 
              />
              <InputError :message="form.errors.name" />
            </div>

            <div>
              <InputLabel for="warehouse_address" value="Warehouse Address *" />
              <TextareaInput 
                id="warehouse_address" 
                v-model="form.warehouse_address" 
                class="mt-1 block w-full"
                :rows="3"
                required 
              />
              <InputError :message="form.errors.warehouse_address" />
            </div>

            <div>
              <InputLabel value="Set Region Location *" />
              <MapPicker 
                v-model="form.geographic_location" 
                :api-key="mapsApiKey"
                class="h-64 border border-gray-300 rounded-lg mt-2"
              />
              <InputError :message="form.errors['geographic_location.lat']" />
              <InputError :message="form.errors['geographic_location.lng']" />
            </div>

            <div class="flex justify-end gap-4 pt-4">
              <SecondaryButton type="button" @click="cancel">Cancel</SecondaryButton>
              <PrimaryButton type="submit" :disabled="form.processing">
                {{ form.processing ? 'Saving...' : 'Save Changes' }}
              </PrimaryButton>
            </div>
          </div>
        </form>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3'; // Add this import
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import TextareaInput from '@/Components/TextArea.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import MapPicker from '@/Components/MapPicker.vue';

const props = defineProps({
  region: Object,
  mapsApiKey: String,
  status: String,
  success: String,
  error: String,
});

const form = useForm({
  name: props.region.name,
  warehouse_address: props.region.warehouse_address,
  geographic_location: {
    lat: parseFloat(props.region.geographic_location.latitude),
    lng: parseFloat(props.region.geographic_location.longitude)
  }
});

function submit() {
  form.put(route('admin.regions.update', props.region.id), {
    preserveScroll: true
  });
}

function cancel() {
  router.get(route('admin.regions.index'));
}
</script>