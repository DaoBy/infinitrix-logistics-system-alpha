<template>
  <EmployeeLayout>
    <Head title="Region Travel Durations" />
    
    <div class="py-6 px-4 sm:px-6 lg:px-8">
      <div class="max-w-7xl mx-auto">
        <div class="bg-white shadow rounded-lg p-6 mb-6">
          <h1 class="text-2xl font-bold text-gray-900 mb-6">Region Travel Durations</h1>
          
          <!-- Add New Form -->
          <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div>
              <InputLabel for="from_region_id" value="From Region" />
              <select id="from_region_id" v-model="form.from_region_id" class="mt-1 block w-full">
                <option value="">Select Region</option>
                <option v-for="region in regions" :key="region.id" :value="region.id">
                  {{ region.name }}
                </option>
              </select>
              <InputError :message="form.errors.from_region_id" />
            </div>
            
            <div>
              <InputLabel for="to_region_id" value="To Region" />
              <select id="to_region_id" v-model="form.to_region_id" class="mt-1 block w-full">
                <option value="">Select Region</option>
                <option v-for="region in regions" :key="region.id" :value="region.id">
                  {{ region.name }}
                </option>
              </select>
              <InputError :message="form.errors.to_region_id" />
            </div>
            
            <div>
              <InputLabel for="estimated_minutes" value="Minutes" />
              <TextInput 
                id="estimated_minutes" 
                v-model="form.estimated_minutes" 
                type="number" 
                min="1" 
                class="mt-1 block w-full" 
              />
              <InputError :message="form.errors.estimated_minutes" />
            </div>
            
            <div class="flex items-end">
              <PrimaryButton type="submit" :disabled="form.processing">
                Add Duration
              </PrimaryButton>
            </div>
          </form>
          
          <!-- Durations Table -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">From</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">To</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration (minutes)</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="duration in durationList" :key="duration.id">
                  <td class="px-6 py-4 whitespace-nowrap">
                    {{ duration.from_region?.name ?? '-' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    {{ duration.to_region?.name ?? '-' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <TextInput 
                      v-model="duration.estimated_minutes" 
                      type="number" 
                      min="1"
                      @change="updateDuration(duration)"
                      class="w-24"
                    />
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button 
                      @click="deleteDuration(duration)"
                      class="text-red-600 hover:text-red-900"
                    >
                      Delete
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'

const props = defineProps({
  durations: Object,
  regions: Array,
  errors: Object
})

const durationList = computed(() => props.durations.data ?? [])

const form = useForm({
  from_region_id: '',
  to_region_id: '',
  estimated_minutes: 60
})

// For update/delete feedback
const updateError = ref('')
const deleteError = ref('')

const submit = () => {
  form.post(route('region-durations.store'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  })
}

const updateDuration = (duration) => {
  updateError.value = ''
  useForm({ estimated_minutes: duration.estimated_minutes }).put(
    route('region-durations.update', { region_duration: duration.id }),
    {
      preserveScroll: true,
      onError: (errors) => {
        updateError.value = errors.estimated_minutes || 'Failed to update duration.'
      }
    }
  )
}

const deleteDuration = (duration) => {
  deleteError.value = ''
  if (confirm('Are you sure you want to delete this duration?')) {
    useForm({}).delete(
      route('region-durations.destroy', { region_duration: duration.id }),
      {
        preserveScroll: true,
        onError: (errors) => {
          deleteError.value = errors.route || 'Failed to delete duration.'
        }
      }
    )
  }
}
</script>