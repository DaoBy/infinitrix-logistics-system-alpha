<template>
  <GuestLayout>
    <div class="max-w-4xl w-full mx-auto p-6 bg-white rounded-lg shadow-md">
      <!-- Success Modal -->
      <div v-if="showSuccessModal" class="fixed inset-0 bg-gray-900 bg-opacity-30 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-lg">
          <div class="text-center">
            <!-- Success Icon -->
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
              <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>
            
            <!-- Title -->
            <h3 class="text-lg font-medium text-gray-900 mb-2">
              {{ canEditDirectly ? 'Profile Updated Successfully' : 'Request Submitted Successfully' }}
            </h3>
            
            <!-- Body -->
            <p class="text-gray-600 mb-4" v-if="!canEditDirectly">
              Your delivery information update request has been submitted for approval. Our team will review your request and you'll be notified once it's processed.
            </p>
            <p class="text-gray-600 mb-4" v-else>
              Your profile has been updated successfully. All changes have been logged for security purposes.
            </p>
            
            <!-- Main CTA -->
            <button 
              @click="redirectToDashboard" 
              class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 w-full"
            >
              Return to Dashboard
            </button>
          </div>
        </div>
      </div>

      <div class="mb-6">
        <h1 class="text-2xl font-bold text-center text-gray-800 mb-2">
          {{ canEditDirectly ? 'Update Profile' : 'Update Delivery Information' }}
        </h1>
        <p class="text-gray-600 text-center">
          {{ canEditDirectly ? 
            'Update your profile information directly' : 
            'Submit changes to your delivery information for approval' 
          }}
        </p>
      </div>

      <!-- Existing Request Status -->
      <div v-if="existingRequest" class="mb-6">
        <div v-if="existingRequest.status === 'pending'" class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-yellow-800">Request Pending Approval</h3>
              <div class="mt-2 text-sm text-yellow-700">
                <p>You have a pending update request submitted on {{ formatDate(existingRequest.created_at) }}.</p>
                <p class="mt-1 font-medium">You cannot submit another request until this one is processed.</p>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="existingRequest.status === 'rejected'" class="bg-red-50 p-4 rounded-lg border border-red-200">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800">Request Rejected</h3>
              <div class="mt-2 text-sm text-red-700">
                <p>Your update request submitted on {{ formatDate(existingRequest.created_at) }} was rejected.</p>
                <div v-if="existingRequest.admin_notes" class="mt-2 p-3 bg-red-100 rounded border border-red-200">
                  <p class="font-medium">Reason for rejection:</p>
                  <p class="mt-1">{{ existingRequest.admin_notes }}</p>
                </div>
                <p class="mt-2">You can submit a new request with corrected information.</p>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="existingRequest.status === 'approved'" class="bg-green-50 p-4 rounded-lg border border-green-200">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-green-800">Request Approved</h3>
              <div class="mt-2 text-sm text-green-700">
                <p>Your update request submitted on {{ formatDate(existingRequest.created_at) }} was approved and processed.</p>
                <p class="mt-1">You can submit a new request if you need to make additional changes.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Blocked Notice - Active/Unpaid Deliveries -->
      <div v-if="!canRequestChanges" class="bg-red-50 p-4 rounded-lg border border-red-200 mb-6">
        <h3 class="font-medium text-red-800 mb-2">Updates Currently Unavailable</h3>
        <p class="text-sm text-red-700 mb-2">
          You cannot update your delivery information while you have active deliveries or unpaid payments.
        </p>
        <div class="text-xs text-red-600">
          <p class="font-medium">Active Deliveries: {{ activeDeliveriesCount }}</p>
          <p class="font-medium">Unpaid Payments: {{ unpaidDeliveriesCount }}</p>
          <p class="mt-2">Please complete all deliveries and payments before requesting changes to your delivery information.</p>
        </div>
      </div>
<!-- Approval Required Notice - Has Delivery History but can request changes -->
<div v-else-if="hasDeliveryHistory && canRequestChanges && !hasPendingRequest" class="bg-amber-50 p-3 rounded-lg border border-amber-200 mb-4">
  <div class="flex items-start space-x-3">
    <div class="flex-shrink-0 mt-0.5">
      <svg class="h-5 w-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z" />
      </svg>
    </div>
    <div class="flex-1">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-medium text-amber-800">Approval Required for Changes</h3>     
      </div>
      <p class="text-xs text-amber-700 mt-1">
        Due to delivery history, changes to critical fields require staff approval for delivery accuracy.
        Look for the "Requires staff approval" notice below each field.
      </p>
    </div>
  </div>
</div>

      <!-- Direct Updates Allowed - First Time Customer -->
      <div v-else-if="canEditDirectly && !hasPendingRequest" class="bg-green-50 p-4 rounded-lg border border-green-200 mb-6">
        <h3 class="font-medium text-green-800 mb-2">Direct Profile Updates</h3>
        <p class="text-sm text-green-700">
          As a first-time customer, you can update all fields directly. All changes will be logged for security purposes.
        </p>
      </div>

      <!-- Email Information Note -->
      <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 mb-6">
        <h3 class="font-medium text-blue-800 mb-2">Note About Email Updates</h3>
        <p class="text-sm text-blue-700">
          To change your login email address, please visit the 
          <a :href="route('profile.edit')" class="text-blue-600 hover:text-blue-800 underline">Account Settings</a> 
          page. This form is for updating your delivery contact information only.
        </p>
      </div>

      <form @submit.prevent="submit" class="space-y-6" :class="{ 'opacity-50 pointer-events-none': !canSubmitRequest }">
        <!-- Basic Information Section -->
        <div>
          <h2 class="text-lg font-medium text-gray-800 mb-4">Personal Information</h2>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <InputLabel for="first_name" value="First Name" />
              <TextInput
                id="first_name"
                type="text"
                class="mt-1 block w-full"
                :class="{ 'bg-gray-100 cursor-not-allowed': !canSubmitRequest }"
                v-model="form.first_name"
                :placeholder="customer.first_name || 'First name'"
                :disabled="!canSubmitRequest"
              />
              <InputError class="mt-1" :message="form.errors.first_name" />
              <!-- Field Approval Notice -->
              <div v-if="hasDeliveryHistory && canRequestChanges && !hasPendingRequest" class="text-xs text-amber-600 mt-1 flex items-center">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Requires staff approval (due to delivery history)
              </div>
              <div class="text-xs text-gray-500 mt-1">
                Current: {{ customer.first_name || 'Not set' }}
              </div>
            </div>
            
            <div>
              <InputLabel for="middle_name" value="Middle Name" />
              <TextInput
                id="middle_name"
                type="text"
                class="mt-1 block w-full"
                :class="{ 'bg-gray-100 cursor-not-allowed': !canSubmitRequest }"
                v-model="form.middle_name"
                :placeholder="customer.middle_name || 'Middle name'"
                :disabled="!canSubmitRequest"
              />
              <InputError class="mt-1" :message="form.errors.middle_name" />
              <!-- Field Approval Notice -->
              <div v-if="hasDeliveryHistory && canRequestChanges && !hasPendingRequest" class="text-xs text-amber-600 mt-1 flex items-center">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Requires staff approval (due to delivery history)
              </div>
              <div class="text-xs text-gray-500 mt-1">
                Current: {{ customer.middle_name || 'Not set' }}
              </div>
            </div>
            
            <div>
              <InputLabel for="last_name" value="Last Name" />
              <TextInput
                id="last_name"
                type="text"
                class="mt-1 block w-full"
                :class="{ 'bg-gray-100 cursor-not-allowed': !canSubmitRequest }"
                v-model="form.last_name"
                :placeholder="customer.last_name || 'Last name'"
                :disabled="!canSubmitRequest"
              />
              <InputError class="mt-1" :message="form.errors.last_name" />
              <!-- Field Approval Notice -->
              <div v-if="hasDeliveryHistory && canRequestChanges && !hasPendingRequest" class="text-xs text-amber-600 mt-1 flex items-center">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Requires staff approval (due to delivery history)
              </div>
              <div class="text-xs text-gray-500 mt-1">
                Current: {{ customer.last_name || 'Not set' }}
              </div>
            </div>
          </div>
        </div>

        <!-- Contact Information Section -->
        <div>
          <h2 class="text-lg font-medium text-gray-800 mb-4">Contact Information</h2>
          
          <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <InputLabel for="mobile" value="Mobile Number" />
                <TextInput
                  id="mobile"
                  type="tel"
                  class="mt-1 block w-full"
                  :class="{ 'bg-gray-100 cursor-not-allowed': !canSubmitRequest }"
                  v-model="form.mobile"
                  :placeholder="customer.mobile || '09XXXXXXXXX'"
                  maxlength="11"
                  :disabled="!canSubmitRequest"
                />
                <InputError class="mt-1" :message="form.errors.mobile" />
                <!-- Field Approval Notice -->
                <div v-if="hasDeliveryHistory && canRequestChanges && !hasPendingRequest" class="text-xs text-amber-600 mt-1 flex items-center">
                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                  Requires staff approval (due to delivery history)
                </div>
                <div class="text-xs text-gray-500 mt-1">
                  Current: {{ customer.mobile || 'Not set' }}
                </div>
              </div>
              
              <div>
                <InputLabel for="phone" value="Phone Number (Optional)" />
                <TextInput
                  id="phone"
                  type="tel"
                  class="mt-1 block w-full"
                  :class="{ 'bg-gray-100 cursor-not-allowed': !canSubmitRequest }"
                  v-model="form.phone"
                  :placeholder="customer.phone || 'XXXXXXXXX'"
                  maxlength="9"
                  :disabled="!canSubmitRequest"
                />
                <InputError class="mt-1" :message="form.errors.phone" />
                <!-- Field Approval Notice -->
                <div v-if="hasDeliveryHistory && canRequestChanges && !hasPendingRequest" class="text-xs text-amber-600 mt-1 flex items-center">
                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                  Requires staff approval (due to delivery history)
                </div>
                <div class="text-xs text-gray-500 mt-1">
                  Current: {{ customer.phone || 'Not set' }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Address Information Section -->
        <div>
          <h2 class="text-lg font-medium text-gray-800 mb-4">Delivery Address</h2>
          
          <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <InputLabel for="building_number" value="Building/House Number" />
                <TextInput
                  id="building_number"
                  type="text"
                  class="mt-1 block w-full"
                  :class="{ 'bg-gray-100 cursor-not-allowed': !canSubmitRequest }"
                  v-model="form.building_number"
                  :placeholder="customer.building_number || 'Building number'"
                  :disabled="!canSubmitRequest"
                />
                <InputError class="mt-1" :message="form.errors.building_number" />
                <!-- Field Approval Notice -->
                <div v-if="hasDeliveryHistory && canRequestChanges && !hasPendingRequest" class="text-xs text-amber-600 mt-1 flex items-center">
                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                  Requires staff approval (due to delivery history)
                </div>
                <div class="text-xs text-gray-500 mt-1">
                  Current: {{ customer.building_number || 'Not set' }}
                </div>
              </div>
              
              <div>
                <InputLabel for="street" value="Street" />
                <TextInput
                  id="street"
                  type="text"
                  class="mt-1 block w-full"
                  :class="{ 'bg-gray-100 cursor-not-allowed': !canSubmitRequest }"
                  v-model="form.street"
                  :placeholder="customer.street || 'Street name'"
                  :disabled="!canSubmitRequest"
                />
                <InputError class="mt-1" :message="form.errors.street" />
                <!-- Field Approval Notice -->
                <div v-if="hasDeliveryHistory && canRequestChanges && !hasPendingRequest" class="text-xs text-amber-600 mt-1 flex items-center">
                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                  </svg>
                  Requires staff approval (due to delivery history)
                </div>
                <div class="text-xs text-gray-500 mt-1">
                  Current: {{ customer.street || 'Not set' }}
                </div>
              </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-4">
                <div>
                  <InputLabel for="barangay" value="Barangay" />
                  <TextInput
                    id="barangay"
                    type="text"
                    class="mt-1 block w-full"
                    :class="{ 'bg-gray-100 cursor-not-allowed': !canSubmitRequest }"
                    v-model="form.barangay"
                    :placeholder="customer.barangay || 'Barangay'"
                    :disabled="!canSubmitRequest"
                  />
                  <InputError class="mt-1" :message="form.errors.barangay" />
                  <!-- Field Approval Notice -->
                  <div v-if="hasDeliveryHistory && canRequestChanges && !hasPendingRequest" class="text-xs text-amber-600 mt-1 flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    Requires staff approval (due to delivery history)
                  </div>
                  <div class="text-xs text-gray-500 mt-1">
                    Current: {{ customer.barangay || 'Not set' }}
                  </div>
                </div>
                
                <div>
                  <InputLabel for="city" value="City" />
                  <TextInput
                    id="city"
                    type="text"
                    class="mt-1 block w-full"
                    :class="{ 'bg-gray-100 cursor-not-allowed': !canSubmitRequest }"
                    v-model="form.city"
                    :placeholder="customer.city || 'City'"
                    :disabled="!canSubmitRequest"
                  />
                  <InputError class="mt-1" :message="form.errors.city" />
                  <!-- Field Approval Notice -->
                  <div v-if="hasDeliveryHistory && canRequestChanges && !hasPendingRequest" class="text-xs text-amber-600 mt-1 flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    Requires staff approval (due to delivery history)
                  </div>
                  <div class="text-xs text-gray-500 mt-1">
                    Current: {{ customer.city || 'Not set' }}
                  </div>
                </div>
              </div>
              
              <div class="space-y-4">
                <div>
                  <InputLabel for="province" value="Province" />
                  <TextInput
                    id="province"
                    type="text"
                    class="mt-1 block w-full"
                    :class="{ 'bg-gray-100 cursor-not-allowed': !canSubmitRequest }"
                    v-model="form.province"
                    :placeholder="customer.province || 'Province'"
                    :disabled="!canSubmitRequest"
                  />
                  <InputError class="mt-1" :message="form.errors.province" />
                  <!-- Field Approval Notice -->
                  <div v-if="hasDeliveryHistory && canRequestChanges && !hasPendingRequest" class="text-xs text-amber-600 mt-1 flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    Requires staff approval (due to delivery history)
                  </div>
                  <div class="text-xs text-gray-500 mt-1">
                    Current: {{ customer.province || 'Not set' }}
                  </div>
                </div>
                
                <div>
                  <InputLabel for="zip_code" value="ZIP Code" />
                  <TextInput
                    id="zip_code"
                    type="text"
                    class="mt-1 block w-full"
                    :class="{ 'bg-gray-100 cursor-not-allowed': !canSubmitRequest }"
                    v-model="form.zip_code"
                    :placeholder="customer.zip_code || 'XXXX'"
                    maxlength="4"
                    :disabled="!canSubmitRequest"
                  />
                  <InputError class="mt-1" :message="form.errors.zip_code" />
                  <!-- Field Approval Notice -->
                  <div v-if="hasDeliveryHistory && canRequestChanges && !hasPendingRequest" class="text-xs text-amber-600 mt-1 flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    Requires staff approval (due to delivery history)
                  </div>
                  <div class="text-xs text-gray-500 mt-1">
                    Current: {{ customer.zip_code || 'Not set' }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Reason Section (only show and require if customer has delivery history) -->
        <div v-if="hasDeliveryHistory && canRequestChanges && !hasPendingRequest">
          <h2 class="text-lg font-medium text-gray-800 mb-4">Reason for Changes</h2>
          
          <div>
            <InputLabel for="reason" value="Please explain why you need to update your delivery information *" />
            <textarea
              id="reason"
              rows="4"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              v-model="form.reason"
              :required="hasDeliveryHistory"
              :disabled="!canSubmitRequest"
              placeholder="Please provide a detailed explanation for the changes to your delivery information. This helps our team process your request faster."
            ></textarea>
            <InputError class="mt-1" :message="form.errors.reason" />
          </div>
        </div>

        <!-- Required Fields Note -->
        <div class="text-sm text-gray-500">
          <p class="text-center">
            <span class="text-red-500">*</span> indicates required fields
            <span v-if="hasDeliveryHistory && canRequestChanges && !hasPendingRequest" class="ml-4">
              <span class="text-amber-500">🔄</span> All fields require staff approval
            </span>
            <span v-if="!canRequestChanges" class="ml-4">
              <span class="text-red-500">🔒</span> Updates currently blocked due to active/unpaid deliveries
            </span>
            <span v-if="hasPendingRequest" class="ml-4">
              <span class="text-yellow-500">⏳</span> Pending request - form disabled
            </span>
            <span v-if="canEditDirectly && !hasPendingRequest" class="ml-4">
              <span class="text-green-500">✏️</span> Direct updates allowed
            </span>
          </p>
        </div>

        <!-- Buttons -->
        <div class="flex justify-end space-x-4 pt-2">
          <SecondaryButton 
            @click="resetForm" 
            type="button"
            :disabled="!canSubmitRequest"
          >
            Reset Changes
          </SecondaryButton>
          <PrimaryButton 
            type="submit" 
            :disabled="form.processing || !hasChanges || !canSubmitRequest"
          >
            <span v-if="hasPendingRequest">
              Request Pending
            </span>
            <span v-else>
              {{ canEditDirectly ? 'Update Profile' : 'Submit for Approval' }}
            </span>
          </PrimaryButton>
        </div>
      </form>
    </div>
  </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
  customer: Object,
  lockedFields: Array,
  editableFields: Array,
  hasDeliveryHistory: Boolean,
  canRequestChanges: Boolean,
  activeDeliveriesCount: Number,
  unpaidDeliveriesCount: Number,
  existingRequest: Object, // Add this prop to get existing request data
});

const showSuccessModal = ref(false);

// Compute if customer can edit directly (no delivery history)
const canEditDirectly = computed(() => {
  return !props.hasDeliveryHistory && props.canRequestChanges;
});

// Check if there's a pending request
const hasPendingRequest = computed(() => {
  return props.existingRequest && props.existingRequest.status === 'pending';
});

// Check if customer can submit a request
const canSubmitRequest = computed(() => {
  return props.canRequestChanges && !hasPendingRequest.value;
});

// Critical fields list (for display purposes)
const criticalFields = [
  'first_name', 'last_name', 'mobile',
  'building_number', 'street', 'barangay', 'city', 'province', 'zip_code'
];

// Initialize form with current customer values
const form = useForm({
  first_name: props.customer.first_name || '',
  middle_name: props.customer.middle_name || '',
  last_name: props.customer.last_name || '',
  mobile: props.customer.mobile || '',
  phone: props.customer.phone || '',
  building_number: props.customer.building_number || '',
  street: props.customer.street || '',
  barangay: props.customer.barangay || '',
  city: props.customer.city || '',
  province: props.customer.province || '',
  zip_code: props.customer.zip_code || '',
  reason: props.hasDeliveryHistory ? '' : 'Direct profile update',
});

// Track which fields have been modified from their original values
const modifiedFields = ref(new Set());

// Check if form has any changes
const hasChanges = computed(() => {
  return modifiedFields.value.size > 0 || (props.hasDeliveryHistory && form.reason.trim() !== '');
});

// Watch for changes in form fields
const watchFieldChanges = () => {
  const fields = ['first_name', 'middle_name', 'last_name', 'mobile', 'phone', 'building_number', 'street', 'barangay', 'city', 'province', 'zip_code'];
  
  fields.forEach(field => {
    watch(() => form[field], (newValue, oldValue) => {
      const originalValue = props.customer[field] || '';
      const currentValue = newValue || '';
      
      // Check if the field has been modified from its original value
      if (currentValue !== originalValue) {
        modifiedFields.value.add(field);
      } else {
        modifiedFields.value.delete(field);
      }
    });
  });
};

const submit = () => {
  if (!hasChanges.value || !canSubmitRequest.value) {
    return;
  }

  // Create a payload with only modified fields and their new values
  const payload = {};

  // Only include reason if there are locked fields or if it's provided
  if (props.hasDeliveryHistory || form.reason) {
    payload.reason = form.reason || null;
  }

  // Only include fields that were actually modified
  modifiedFields.value.forEach(field => {
    // Send the new value (even if it's empty string to clear the field)
    payload[field] = form[field] || null;
  });

  // If no fields were modified and no reason provided, don't submit
  if (Object.keys(payload).length === 0 || (Object.keys(payload).length === 1 && payload.reason === '')) {
    return;
  }

  // FIXED: Use the correct route name
  form.transform((data) => ({
    ...payload,
    _method: 'post'
  })).post(route('customer.profile.update.store'), {
    onSuccess: () => {
      showSuccessModal.value = true;
    },
    onError: (errors) => {
      console.error('Form submission errors:', errors);
    },
    preserveScroll: true
  });
};

const resetForm = () => {
  // Reset form to original customer values
  form.first_name = props.customer.first_name || '';
  form.middle_name = props.customer.middle_name || '';
  form.last_name = props.customer.last_name || '';
  form.mobile = props.customer.mobile || '';
  form.phone = props.customer.phone || '';
  form.building_number = props.customer.building_number || '';
  form.street = props.customer.street || '';
  form.barangay = props.customer.barangay || '';
  form.city = props.customer.city || '';
  form.province = props.customer.province || '';
  form.zip_code = props.customer.zip_code || '';
  form.reason = props.hasDeliveryHistory ? '' : 'Direct profile update';
  
  // Clear modified fields
  modifiedFields.value.clear();
};

const redirectToDashboard = () => {
  window.location.href = route('customer.home');
};

const formatDate = (date) => {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Initialize field watching when component mounts
onMounted(() => {
  watchFieldChanges();
});
</script>