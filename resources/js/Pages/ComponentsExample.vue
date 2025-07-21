<template>
    <div class="p-8 max-w-7xl mx-auto space-y-12 dark:bg-gray-900 dark:text-gray-100">
      <h1 class="text-3xl font-bold mb-8">Component Library Examples</h1>
  
      <!-- InputLabel -->
      <section class="space-y-4">
        <h2 class="text-2xl font-semibold">InputLabel</h2>
        <p class="text-gray-600 dark:text-gray-400">
          A label component that can display either a prop value or slot content. Useful for form inputs.
        </p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h3 class="font-medium mb-2">Using value prop</h3>
            <InputLabel value="Username" />
          </div>
          <div>
            <h3 class="font-medium mb-2">Using slot content</h3>
            <InputLabel>Email Address</InputLabel>
          </div>
        </div>
      </section>
  
      <!-- Modal -->
      <section class="space-y-4">
        <h2 class="text-2xl font-semibold">Modal</h2>
        <p class="text-gray-600 dark:text-gray-400">
          A customizable modal dialog with transitions, backdrop, and responsive sizing.
        </p>
        <div class="space-y-4">
          <PrimaryButton @click="showModal = true"> Modal</PrimaryButton>
          <Modal :show="showModal" @close="showModal = false" maxWidth="lg">
            <div class="p-6">
              <h3 class="text-lg font-medium mb-4">Modal Title</h3>
              <p class="text-gray-600 dark:text-gray-400 mb-6">
                This is modal content. Click outside or press Escape to close.
              </p>
              <div class="flex justify-between">
                <SecondaryButton @click="showModal = false">Cancel</SecondaryButton>
                 <PrimaryButton @click="showModal = false">Confirm</PrimaryButton>
                </div>
            </div>
          </Modal>
        </div>
      </section>
  
      <!-- NavLink -->
      <section class="space-y-4">
        <h2 class="text-2xl font-semibold">NavLink</h2>
        <p class="text-gray-600 dark:text-gray-400">
          A navigation link component with active state styling, typically used in desktop navigation.
        </p>
        <div class="flex space-x-4">
          <NavLink href="/dashboard" :active="false">Dashboard</NavLink>
          <NavLink href="/settings" :active="true">Settings</NavLink>
        </div>
      </section>
  
      <!-- PrimaryButton -->
      <section class="space-y-4">
        <h2 class="text-2xl font-semibold">PrimaryButton</h2>
        <p class="text-gray-600 dark:text-gray-400">
          A primary action button with hover, focus, and active states.
        </p>
        <div class="flex space-x-4">
          <PrimaryButton>Submit</PrimaryButton>
          <PrimaryButton type="submit">Form Submit</PrimaryButton>
        </div>
      </section>
  
      <!-- ResponsiveNavLink -->
      <section class="space-y-4">
        <h2 class="text-2xl font-semibold">ResponsiveNavLink</h2>
        <p class="text-gray-600 dark:text-gray-400">
          A responsive navigation link with more padding and border indicators, typically used in mobile menus.
        </p>
        <div class="max-w-xs space-y-2">
          <ResponsiveNavLink href="/dashboard" :active="false">Dashboard</ResponsiveNavLink>
          <ResponsiveNavLink href="/settings" :active="true">Settings</ResponsiveNavLink>
        </div>
      </section>
  
      <!-- SecondaryButton -->
      <section class="space-y-4">
        <h2 class="text-2xl font-semibold">SecondaryButton</h2>
        <p class="text-gray-600 dark:text-gray-400">
          A secondary action button with a border, used for less prominent actions.
        </p>
        <div class="flex space-x-4">
          <SecondaryButton>Cancel</SecondaryButton>
          <SecondaryButton type="reset">Reset Form</SecondaryButton>
        </div>
      </section>
  
      <!-- SelectInput -->
      <section class="space-y-4">
        <h2 class="text-2xl font-semibold">SelectInput</h2>
        <p class="text-gray-600 dark:text-gray-400">
          A styled select dropdown input that supports v-model binding.
        </p>
        <div class="max-w-xs">
          <InputLabel>Select an option</InputLabel>
          <SelectInput 
            v-model="selectedOption" 
            :options="['Option 1', 'Option 2', 'Option 3']" 
          />
          <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            Selected: {{ selectedOption || 'None' }}
          </p>
        </div>
      </section>
  
      <!-- TextInput -->
      <section class="space-y-4">
        <h2 class="text-2xl font-semibold">TextInput</h2>
        <p class="text-gray-600 dark:text-gray-400">
          A customizable text input with focus styles, dark mode support, and autofocus capability.
        </p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <InputLabel>Standard Input</InputLabel>
            <TextInput 
              v-model="textValue" 
              placeholder="Type something..." 
            />
          </div>
          <div>
            <InputLabel>Autofocused Input</InputLabel>
            <TextInput 
              v-model="textValue" 
              placeholder="This input autofocuses" 
              autofocus 
            />
          </div>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          Current value: {{ textValue || 'Empty' }}
        </p>
      </section>
  
      <!-- Checkbox -->
      <section class="space-y-4">
        <h2 class="text-2xl font-semibold">Checkbox</h2>
        <p class="text-gray-600 dark:text-gray-400">
          A checkbox component that supports both single and multiple selections.
        </p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-4">
            <h3 class="font-medium">Single Checkbox</h3>
            <div class="flex items-center space-x-2">
              <Checkbox v-model="singleCheckbox" :checked="singleCheckbox" />
              <InputLabel>Accept terms</InputLabel>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              Checked: {{ singleCheckbox }}
            </p>
          </div>
          <div class="space-y-4">
            <h3 class="font-medium">Multiple Checkboxes</h3>
            <div class="space-y-2">
              <div class="flex items-center space-x-2">
                <Checkbox 
                  v-model="multipleCheckboxes" 
                  :checked="multipleCheckboxes" 
                  value="option1" 
                />
                <InputLabel>Option 1</InputLabel>
              </div>
              <div class="flex items-center space-x-2">
                <Checkbox 
                  v-model="multipleCheckboxes" 
                  :checked="multipleCheckboxes" 
                  value="option2" 
                />
                <InputLabel>Option 2</InputLabel>
              </div>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">
              Selected: {{ multipleCheckboxes.length ? multipleCheckboxes : 'None' }}
            </p>
          </div>
        </div>
      </section>
  
      <!-- DangerButton -->
      <section class="space-y-4">
        <h2 class="text-2xl font-semibold">DangerButton</h2>
        <p class="text-gray-600 dark:text-gray-400">
          A button for destructive actions, styled with red colors.
        </p>
        <div class="flex space-x-4">
          <DangerButton>Delete</DangerButton>
          <DangerButton>Remove</DangerButton>
        </div>
      </section>

      
    <!-- Dropdown -->
    <section class="space-y-4">
      <h2 class="text-2xl font-semibold">Dropdown</h2>
      <p class="text-gray-600 dark:text-gray-400">
        A customizable dropdown menu with transitions, click-outside handling, and keyboard navigation.
      </p>
      <div class="space-y-6">
        <div>
          <h3 class="font-medium mb-2">Basic Dropdown</h3>
         
          <Dropdown align="center">
  <template #trigger>
    <PrimaryButton class="inline-flex items-center">
      Options
      <ChevronDownIcon class="ms-2 -me-0.5 h-4 w-4" />
    </PrimaryButton>
  </template>
  <template #content>
    <DropdownLink href="/profile">Profile</DropdownLink>
    <DropdownLink href="/settings">Settings</DropdownLink>
    <DropdownLink href="/logout">Log Out</DropdownLink>
  </template>
</Dropdown>
        </div>

        <div>
          <h3 class="font-medium mb-2">Left-aligned Dropdown</h3>
          <Dropdown align="left">
  <template #trigger>
    <SecondaryButton class="inline-flex items-center">
      Left Aligned
      <ChevronDownIcon class="ms-2 -me-0.5 h-4 w-4" />
    </SecondaryButton>
  </template>
  <template #content>
    <DropdownLink href="/action1">Action 1</DropdownLink>
    <DropdownLink href="/action2">Action 2</DropdownLink>
    <div class="border-t border-gray-200 dark:border-gray-600"></div>
    <DropdownLink href="/action3">Action 3</DropdownLink>
  </template>
</Dropdown>
        </div>
      </div>
    </section>




    <!-- DropdownLink -->
    <section class="space-y-4">
      <h2 class="text-2xl font-semibold">DropdownLink</h2>
      <p class="text-gray-600 dark:text-gray-400">
        A styled link component specifically designed for use within dropdown menus.
      </p>
      <div class="max-w-xs border rounded-lg p-4 dark:border-gray-700">
        <div class="space-y-1">
          <DropdownLink href="/account">Account Settings</DropdownLink>
          <DropdownLink href="/support">Support</DropdownLink>
          <DropdownLink href="/privacy">Privacy Policy</DropdownLink>
        </div>
      </div>
    </section>





    <!-- InputError -->
    <section class="space-y-4">
      <h2 class="text-2xl font-semibold">InputError</h2>
      <p class="text-gray-600 dark:text-gray-400">
        Displays validation error messages for form inputs. Only visible when a message is provided.
      </p>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <h3 class="font-medium mb-2">With Error Message</h3>
          <TextInput v-model="errorInput" placeholder="Enter your email" />
          <InputError message="The email field is required." />
        </div>
        <div>
          <h3 class="font-medium mb-2">Without Error Message</h3>
          <TextInput v-model="errorInput" placeholder="Enter your name" />
          <InputError :message="null" />
        </div>
      </div>
    </section>


    </div>
  </template>
  
  <script setup>
  import { ref } from 'vue';
  import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import NavLink from '@/Components/NavLink.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import InputError from '@/Components/InputError.vue';
import { ChevronDownIcon } from '@heroicons/vue/20/solid' // or 24/solid for larger icons

  const errorInput = ref('');

  // Modal state
  const showModal = ref(false);
  
  // SelectInput state
  const selectedOption = ref('');
  
  // TextInput state
  const textValue = ref('');
  
  // Checkbox states
  const singleCheckbox = ref(false);
  const multipleCheckboxes = ref([]);
  </script>
  
  <style>
  /* Optional: Add some spacing between sections */
  section {
    @apply pb-8 border-b border-gray-200 dark:border-gray-700;
  }
  section:last-child {
    @apply border-b-0;
  }
  </style>