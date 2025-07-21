<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-6">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
          Components - {{ truck.make }} {{ truck.model }} ({{ truck.license_plate }})
        </h2>
        <div class="flex space-x-2">
          <SecondaryButton @click="router.get(route('admin.trucks.show', truck.id))">
            Back to Truck
          </SecondaryButton>
          <PrimaryButton @click="createComponent">
            Add New Component
          </PrimaryButton>
        </div>
      </div>
    </template>


    <div class="px-6 sm:px-8">
      <div v-if="status || success || error" class="mb-6">
        <div v-if="status" class="p-4 bg-blue-100 text-blue-800 rounded">{{ status }}</div>
        <div v-if="success" class="p-4 bg-green-100 text-green-800 rounded">{{ success }}</div>
        <div v-if="error" class="p-4 bg-red-100 text-red-800 rounded">{{ error }}</div>
      </div>

      <DataTable
        :columns="columns"
        :data="components"
        class="w-full"
      >
        <template #name="{ row }">
          <span class="truncate block">{{ row.name || 'N/A' }}</span>
        </template>
        <template #type="{ row }">
          <span class="truncate block capitalize">{{ row.type.replace('_', ' ') || 'N/A' }}</span>
        </template>
        <template #condition="{ row }">
          <span 
            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full capitalize"
            :class="conditionClasses[row.condition]"
          >
            {{ conditionLabels[row.condition] }}
          </span>
        </template>
        <template #installation_date="{ row }">
          {{ formatDate(row.installation_date) || 'N/A' }}
        </template>
        <template #actions="{ row }">
          <div class="flex space-x-2">
            <SecondaryButton @click="viewComponent(row.id)">View</SecondaryButton>
            <PrimaryButton @click="editComponent(row.id)">Edit</PrimaryButton>
            <DangerButton @click="confirmDeleteComponent(row)">Delete</DangerButton>
          </div>
        </template>
      </DataTable>
    </div>

    <Modal :show="showDeleteModal" @close="closeDeleteModal">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">Delete Component?</h2>
        <p class="mt-1 text-sm text-gray-600">
          Are you sure you want to delete <strong>{{ componentToDelete?.name }}</strong>?
          This action cannot be undone.
        </p>
        <div class="mt-6 flex justify-end space-x-4">
          <SecondaryButton @click="closeDeleteModal">Cancel</SecondaryButton>
          <DangerButton @click="handleDelete" :disabled="isDeleting">
            <span v-if="isDeleting">Deleting...</span>
            <span v-else>Delete</span>
          </DangerButton>
        </div>
      </div>
    </Modal>
  </EmployeeLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
  truck: {
    type: Object,
    required: true
  },
  components: {
    type: Array,
    default: () => []
  },
  status: {
    type: String,
    default: ''
  },
  success: {
    type: String,
    default: ''
  },
  error: {
    type: String,
    default: ''
  },
});

const columns = [
  { field: 'name', header: 'Name', sortable: true },
  { field: 'type', header: 'Type', sortable: true },
  { field: 'serial_number', header: 'Serial Number', sortable: true },
  { field: 'condition', header: 'Condition', sortable: true },
  { field: 'installation_date', header: 'Installation Date', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false }
];

const conditionLabels = {
  new: 'New',
  good: 'Good',
  fair: 'Fair',
  poor: 'Poor',
  needs_replacement: 'Needs Replacement'
};

const conditionClasses = {
  new: 'bg-green-100 text-green-800',
  good: 'bg-blue-100 text-blue-800',
  fair: 'bg-yellow-100 text-yellow-800',
  poor: 'bg-orange-100 text-orange-800',
  needs_replacement: 'bg-red-100 text-red-800'
};

const showDeleteModal = ref(false);
const componentToDelete = ref(null);
const isDeleting = ref(false);

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString();
};

const createComponent = () => {
  router.get(route('admin.trucks.components.create', props.truck.id));
};

const viewComponent = (id) => {
  router.get(route('admin.trucks.components.show', {
    truck: props.truck.id,
    component: id
  }));
};

const editComponent = (id) => {
  router.get(route('admin.trucks.components.edit', {
    truck: props.truck.id,
    component: id
  }));
};

const confirmDeleteComponent = (component) => {
  componentToDelete.value = component;
  showDeleteModal.value = true;
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  setTimeout(() => {
    componentToDelete.value = null;
    isDeleting.value = false;
  }, 300);
};

const handleDelete = async () => {
  if (!componentToDelete.value) return;
  isDeleting.value = true;
  try {
    await router.delete(route('admin.trucks.components.destroy', {
      truck: props.truck.id,
      component: componentToDelete.value.id
    }), {
      preserveScroll: true
    });
    closeDeleteModal();
  } catch {
    closeDeleteModal();
    alert('Failed to delete component. Please try again.');
  } finally {
    isDeleting.value = false;
  }
};
</script>