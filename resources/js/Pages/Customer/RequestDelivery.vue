<script setup>

import { ref, computed, onMounted, watch } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
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
    authCustomer: Object,
    categories: Array,
    regions: Array,
    priceMatrix: Object
});

const packageStep = ref(1);

// HARDCODED PICKUP REGIONS (Easily editable)
const PICKUP_REGIONS = [
  { value: 1, label: 'Metro Manila' },
  { value: 2, label: 'Cebu City' }
];

// Container Presets
const containerPresets = [
  {
    label: 'Small Pouch (25x15x1 cm, max 0.5kg)',
    value: 'small_pouch',
    dimensions: { height: 1, width: 15, length: 25 },
    weight: 0.5,
    category: 'piece', // <-- FIXED
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
const isUsingMyInfo = ref(false);

// For dropdown options 
const branches = ref([]);
const priceMatrix = ref({
  base_fee: 0,
  volume_rate: 0,
  weight_rate: 0,
  package_rate: 0
});

const fetchRegions = async () => {
  try {
    const response = await axios.get('/api/delivery/regions');
    const regionsData = Array.isArray(response.data) ? response.data : 
                      response.data.data || response.data.regions || [];
    
    branches.value = regionsData.map(region => ({
      value: region.id,
      label: region.name
    }));
  } catch (error) {
    console.error('Failed to fetch regions:', error);
    branches.value = [];
  }
};

const getDefaultRate = (key) => {
  const defaults = {
    base_fee: 50.00,
    volume_rate: 10.00,
    weight_rate: 5.00,
    package_rate: 2.00
  };
  return defaults[key] || 0;
};

const getDefaultPriceMatrix = () => ({
  base_fee: 50.00,
  volume_rate: 10.00,
  weight_rate: 5.00,
  package_rate: 2.00
});

// Initialize with defaults immediately
priceMatrix.value = getDefaultPriceMatrix();

const fetchPriceMatrix = async () => {
  try {
    const response = await axios.get('/api/price-matrix', {
      timeout: 5000, // Add timeout
      headers: {
        'Cache-Control': 'no-cache' // Prevent caching issues
      }
    });

    // More thorough data validation
    const matrixData = response.data?.data || response.data;

    if (!matrixData) {
      throw new Error('Invalid price matrix response');
    }

    priceMatrix.value = {
      base_fee: Number(matrixData.base_fee) || 50.00,
      volume_rate: Number(matrixData.volume_rate) || 10.00,
      weight_rate: Number(matrixData.weight_rate) || 5.00,
      package_rate: Number(matrixData.package_rate) || 2.00
    };

    // Validate all rates are positive numbers
    Object.entries(priceMatrix.value).forEach(([key, value]) => {
      if (isNaN(value) || value < 0) {
        console.error(`Invalid ${key} value in price matrix`);
        priceMatrix.value[key] = getDefaultRate(key);
      }
    });

  } catch (error) {
    console.error('Failed to fetch price matrix:', error);
    // Use safe defaults
    priceMatrix.value = getDefaultPriceMatrix();
  }
};

const formatCurrency = (value) => {
  const num = Number(value);
  return isNaN(num) ? '0.00' : num.toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
};

// Initialize data
onMounted(() => {
  fetchRegions();
  fetchPriceMatrix();
});

const customerCategoryOptions = [
  { value: 'individual', label: 'Individual' },
  { value: 'company', label: 'Company' }
];

// Add payment terms options
const paymentTermsOptions = [
  { value: 'net_7', label: 'Net 7 (Pay within 7 days)' },
  { value: 'net_15', label: 'Net 15 (Pay within 15 days)' },
  { value: 'net_30', label: 'Net 30 (Pay within 30 days)' },
  { value: 'cnd', label: 'CND (Cash Next Delivery)' }
];

const form = useForm({
    sender: {
        customer_category: props.authCustomer?.customer_category || 'individual',
        first_name: props.authCustomer?.first_name || '',
        middle_name: props.authCustomer?.middle_name || '',
        last_name: props.authCustomer?.last_name || '',
        company_name: props.authCustomer?.company_name || '',
        email: props.authCustomer?.email || '',
        mobile: props.authCustomer?.mobile || '',
        phone: props.authCustomer?.phone || '',
        building_number: props.authCustomer?.building_number || '',
        street: props.authCustomer?.street || '',
        barangay: props.authCustomer?.barangay || '',
        city: props.authCustomer?.city || '',
        province: props.authCustomer?.province || '',
        zip_code: props.authCustomer?.zip_code || '',
        notes: ''
    },
    receiver: {
        customer_category: 'individual',
        first_name: '',
        middle_name: '',
        last_name: '',
        company_name: '',
        email: '',
        mobile: '',
        phone: '',
        building_number: '',
        street: '',
        barangay: '',
        city: '',
        province: '',
        zip_code: '',
        notes: ''
    },
    pick_up_region_id: '',   // Step 1: Sender Details
    drop_off_region_id: '',  // Step 2: Receiver Details
    payment_type: '', // <-- Add this line to ensure payment_type is tracked and submitted
    payment_method: '',
    payment_terms: '', // <-- add this line
    total_price: 0,
    priceBreakdown: null,
    packages: [
        {
            item_name: '',
            description: '',
            category: '',
            height: '',
            width: '',
            length: '',
            weight: '',
            value: '',
            photo: null,
            photo_url: null,
            preset: ''
        },
    ],
});

const paymentTypeOptions = computed(() => {
  const options = [
    { value: 'prepaid', label: 'Prepaid (Pay now)' }
  ];
  if (isPostpaidEligible.value) {
    options.push({ value: 'postpaid', label: 'Postpaid (Pay after delivery)' });
  }
  return options;
});

const paymentMethodOptions = [
  { value: 'cash', label: 'Cash' },
  { value: 'gcash', label: 'GCash' },
  { value: 'bank', label: 'Bank Transfer' }
];

const isPostpaidEligible = computed(() => {
  return props.authCustomer?.completed_deliveries_count >= 3;
});

const useMyInfo = () => {
    if (props.authCustomer) {
        form.sender = {
            ...form.sender,
            customer_category: props.authCustomer.customer_category,
            first_name: props.authCustomer.first_name,
            middle_name: props.authCustomer.middle_name,
            last_name: props.authCustomer.last_name,
            company_name: props.authCustomer.company_name,
            email: props.authCustomer.email,
            mobile: props.authCustomer.mobile,
            phone: props.authCustomer.phone,
            building_number: props.authCustomer.building_number,
            street: props.authCustomer.street,
            barangay: props.authCustomer.barangay,
            city: props.authCustomer.city,
            province: props.authCustomer.province,
            zip_code: props.authCustomer.zip_code,
        };
        isUsingMyInfo.value = true;
    }
};

// Price Calculation - Updated version without quantity
const calculatePrice = async () => {
  isLoading.value = true;

  if (
    !priceMatrix.value ||
    !form.pick_up_region_id ||
    !form.drop_off_region_id ||
    !form.packages.every(pkg =>
      parseFloat(pkg.height) > 0 &&
      parseFloat(pkg.width) > 0 &&
      parseFloat(pkg.length) > 0 &&
      parseFloat(pkg.weight) > 0
    )
  ) {
    form.priceBreakdown = null;
    form.total_price = 0;
    isLoading.value = false;
    return;
  }

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

    if (response.data && response.data.breakdown && typeof response.data.total_price !== 'undefined') {
      form.priceBreakdown = response.data.breakdown;
      form.total_price = response.data.total_price;
    } else {
      form.priceBreakdown = null;
      form.total_price = 0;
    }
  } catch (error) {
    console.error('Failed to calculate price:', error);
    form.priceBreakdown = null;
    form.total_price = 0;
  } finally {
    isLoading.value = false;
  }
};

const handlePresetChange = (presetValue, index) => {
  if (presetValue === 'custom') {
    form.packages[index] = {
      ...form.packages[index],
      height: '',
      width: '',
      length: '',
      weight: '',
      category: 'C/S', // Default for custom
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
        category: preset.category, // Use preset category
        preset: preset.value
      };
    }
  }
};

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

// Watch for package changes (without quantity)
watch(() => [
  form.pick_up_region_id,
  form.drop_off_region_id,
  ...form.packages.map(pkg => [
    pkg.height, pkg.width, pkg.length, pkg.weight
  ]).flat()
], () => {
  const allValid = form.packages.every(pkg =>
    parseFloat(pkg.height) > 0 &&
    parseFloat(pkg.width) > 0 &&
    parseFloat(pkg.length) > 0 &&
    parseFloat(pkg.weight) > 0
  );

  if (form.pick_up_region_id && form.drop_off_region_id && allValid) {
    calculatePrice();
  }
});

// Computed property for filtered dropoff regions
const filteredDropoffRegions = computed(() => {
  if (!form.pick_up_region_id) return branches.value;
  return branches.value.filter(region => region.value !== form.pick_up_region_id);
});

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
    // Sender validation (keep existing)
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
    if (!form.pick_up_region_id) {
      form.setError('pick_up_region_id', 'Pick-up region is required.');
      isValid = false;
    } else {
      form.clearErrors('pick_up_region_id');
    }
  }

  if (currentStep.value === 2) {
    // Receiver validation (keep existing)
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
    if (!form.drop_off_region_id) {
      form.setError('drop_off_region_id', 'Drop-off region is required.');
      isValid = false;
    } else {
      form.clearErrors('drop_off_region_id');
    }
  }
  
  if (currentStep.value === 3) {
    // Package validation without quantity
    form.packages.forEach((pkg, index) => {
      if (!pkg.item_name.trim()) {
        form.setError(`packages.${index}.item_name`, 'Package name is required.');
        isValid = false;
      }
      
      const height = sanitizeNumber(pkg.height) / 100;
      const width = sanitizeNumber(pkg.width) / 100;
      const length = sanitizeNumber(pkg.length) / 100;
      const weight = sanitizeNumber(pkg.weight);
      const value = sanitizeNumber(pkg.value);
      
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
      
      const volume = height * width * length;
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
  
  if (currentStep.value === 4) {
    if (!form.payment_method) {
      form.setError('payment_method', 'Payment method is required.');
      isValid = false;
    }
  }

  return isValid;
};

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
    value: '',
    photo: null,
    photo_url: null,
    preset: ''
  });
};

const duplicatePackage = (index) => {
  const packageCopy = JSON.parse(JSON.stringify(form.packages[index]));
  form.packages.splice(index + 1, 0, packageCopy);
};

const removePackage = (index) => {
  if (form.packages.length > 1) {
    if (form.packages[index].photo_url) {
      URL.revokeObjectURL(form.packages[index].photo_url);
    }
    form.packages.splice(index, 1);
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

const sanitizeNumber = (value) => {
  const num = parseFloat(value);
  return isNaN(num) ? 0 : num;
};

const resetForm = () => {
  currentStep.value = 1;
  form.reset();
  form.packages = [{
    item_name: '',
    description: '',
    category: '',
    height: '',
    width: '',
    length: '',
    weight: '',
    value: '',
    photo: null,
    photo_url: null,
    preset: ''
  }];
  form.priceBreakdown = null;
  form.total_price = 0;
  isUsingMyInfo.value = false;
};

const submitRequest = () => {
  if (!validateStep()) return;
  isLoading.value = true;
  form.total_price = form.priceBreakdown ? form.total_price : 0;

  const formData = new window.FormData();

  // Sender fields
  Object.entries(form.sender).forEach(([key, value]) => {
    formData.append(`sender[${key}]`, value ?? '');
  });

  // Receiver fields
  Object.entries(form.receiver).forEach(([key, value]) => {
    formData.append(`receiver[${key}]`, value ?? '');
  });

  // Region fields
  formData.append('pick_up_region_id', form.pick_up_region_id);
  formData.append('drop_off_region_id', form.drop_off_region_id);

  // Payment fields - updated to include payment_terms
  formData.append('payment_type', form.payment_type || 'prepaid');
  formData.append('payment_method', form.payment_type === 'prepaid' ? (form.payment_method || '') : '');
  formData.append('payment_terms', form.payment_terms || '');

  // Pricing fields
  if (form.priceBreakdown) {
    formData.append('total_price', form.total_price);
    formData.append('base_fee', form.priceBreakdown.base_fee);
    formData.append('volume_fee', form.priceBreakdown.volume_fee);
    formData.append('weight_fee', form.priceBreakdown.weight_fee);
    formData.append('package_fee', form.priceBreakdown.package_fee);
    formData.append('price_breakdown', JSON.stringify(form.priceBreakdown));
  }

  // Packages
  form.packages.forEach((pkg, index) => {
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
  });

  form.post(route('customer.delivery-requests.store'), {
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
};

// Add watcher for payment_type
watch(() => form.payment_type, (val) => {
  // Always reset payment_terms and payment_method on change
  form.payment_terms = '';
  form.payment_method = '';

  if (val === 'postpaid' && !isPostpaidEligible.value) {
    form.payment_type = 'prepaid';
    form.setError('payment_type', 'Postpaid is only available after 3 completed deliveries.');
  }
  // Prefill payment_method for postpaid
  if (val === 'postpaid') {
    form.payment_method = 'postpaid';
  }
  // No need for else if for prepaid, already reset above
});

// Add package value options for dropdown
const packageValueOptions = [
  { value: 350, label: '₱200 - ₱500' },
  { value: 750, label: '₱500 - ₱1000' },
  { value: 1500, label: '₱1000 - ₱2000' },
  { value: 3000, label: '₱2000 - ₱4000' },
  { value: 5000, label: '₱4000 - ₱6000' },
  { value: 8000, label: '₱6000 - ₱10000' },
  { value: 15000, label: '₱10000 - ₱20000' },
  { value: 25000, label: '₱20000 - ₱30000' },
  { value: 40000, label: '₱30000 - ₱50000' },
  { value: 60000, label: '₱50000 - ₱70000' },
  { value: 85000, label: '₱70000 - ₱100000' },
];
</script>

<template>
  <GuestLayout>
    <div class="container mx-auto px-6 py-12 flex gap-12">
      <!-- Progress Indicator (Left Side) -->
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
        <h1 class="text-3xl font-bold">Request a Delivery</h1>

  <!-- Step 1: Sender Details -->
<div v-if="currentStep === 1" class="space-y-6">
  <!-- Centered Header -->
  <h2 class="text-xl flex items-center justify-center font-semibold">Sender Details</h2>

  <!-- Updated Important Information -->
  <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
    <h3 class="font-semibold text-blue-800 flex items-center">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      Important Information
    </h3>
    <p class="text-sm text-blue-700 mt-1">
      Your sender information is pulled from your profile. To update your details, please visit your 
      <a :href="route('customer.profile-update.create')" class="text-blue-800 font-semibold underline hover:text-blue-600 transition-colors">
        Delivery Information Page
      </a>. This ensures all profile modifications are properly verified and recorded.
    </p>
  </div>

  <!-- Sender Type -->
  <div>
    <InputLabel value="Sender Type" />
    <SelectInput
      v-model="form.sender.customer_category"
      :options="customerCategoryOptions"
      option-value="value"
      option-label="label"
      class="mt-1 block w-full bg-gray-100 text-gray-600"
      disabled
    />
  </div>

  <!-- Name Fields -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div>
      <InputLabel for="senderFirstName" value="First Name *" />
      <TextInput 
        id="senderFirstName" 
        v-model="form.sender.first_name" 
        class="w-full bg-gray-100 text-gray-600"
        :error="!!form.errors['sender.first_name']"
        placeholder="First name"
        readonly
      />
      <InputError :message="form.errors['sender.first_name']" />
    </div>
    
    <div>
      <InputLabel for="senderMiddleName" value="Middle Name" />
      <TextInput 
        id="senderMiddleName" 
        v-model="form.sender.middle_name" 
        class="w-full bg-gray-100 text-gray-600"
        :error="!!form.errors['sender.middle_name']"
        placeholder="Middle name"
        readonly
      />
      <InputError :message="form.errors['sender.middle_name']" />
    </div>
    
    <div>
      <InputLabel for="senderLastName" value="Last Name *" />
      <TextInput 
        id="senderLastName" 
        v-model="form.sender.last_name" 
        class="w-full bg-gray-100 text-gray-600"
        :error="!!form.errors['sender.last_name']"
        placeholder="Last name"
        readonly
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
      class="w-full bg-gray-100 text-gray-600"
      :error="!!form.errors['sender.company_name']"
      placeholder="Official company name"
      readonly
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
        class="w-full bg-gray-100 text-gray-600"
        :error="!!form.errors['sender.email']"
        placeholder="contact@example.com"
        readonly
      />
      <InputError :message="form.errors['sender.email']" />
    </div>

    <div>
      <InputLabel for="senderMobile" value="Mobile *" />
      <TextInput 
        id="senderMobile" 
        v-model="form.sender.mobile" 
        type="tel" 
        class="w-full bg-gray-100 text-gray-600"
        :error="!!form.errors['sender.mobile']"
        placeholder="09123456789"
        maxlength="11"
        readonly
      />
      <InputError :message="form.errors['sender.mobile']" />
    </div>

    <div>
      <InputLabel for="senderPhone" value="Phone (Landline)" />
      <TextInput 
        id="senderPhone" 
        v-model="form.sender.phone" 
        type="tel" 
        class="w-full bg-gray-100 text-gray-600"
        :error="!!form.errors['sender.phone']"
        placeholder="021234567"
        maxlength="9"
        readonly
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
        class="w-full bg-gray-100 text-gray-600"
        :error="!!form.errors['sender.building_number']"
        placeholder="Building/Unit number"
        readonly
      />
      <InputError :message="form.errors['sender.building_number']" />
    </div>

    <div>
      <InputLabel for="senderStreet" value="Street *" />
      <TextInput 
        id="senderStreet" 
        v-model="form.sender.street" 
        class="w-full bg-gray-100 text-gray-600"
        :error="!!form.errors['sender.street']"
        placeholder="Street name"
        readonly
      />
      <InputError :message="form.errors['sender.street']" />
    </div>

    <div>
      <InputLabel for="senderBarangay" value="Barangay" />
      <TextInput 
        id="senderBarangay" 
        v-model="form.sender.barangay" 
        class="w-full bg-gray-100 text-gray-600"
        :error="!!form.errors['sender.barangay']"
        placeholder="Barangay/District"
        readonly
      />
      <InputError :message="form.errors['sender.barangay']" />
    </div>

    <div>
      <InputLabel for="senderCity" value="City/Municipality *" />
      <TextInput 
        id="senderCity" 
        v-model="form.sender.city" 
        class="w-full bg-gray-100 text-gray-600"
        :error="!!form.errors['sender.city']"
        placeholder="City/Municipality"
        readonly
      />
      <InputError :message="form.errors['sender.city']" />
    </div>

    <div>
      <InputLabel for="senderProvince" value="Province *" />
      <TextInput 
        id="senderProvince" 
        v-model="form.sender.province" 
        class="w-full bg-gray-100 text-gray-600"
        :error="!!form.errors['sender.province']"
        placeholder="Province"
        readonly
      />
      <InputError :message="form.errors['sender.province']" />
    </div>

    <div>
      <InputLabel for="senderZipCode" value="ZIP Code *" />
      <TextInput 
        id="senderZipCode" 
        v-model="form.sender.zip_code" 
        maxlength="4" 
        class="w-full bg-gray-100 text-gray-600"
        :error="!!form.errors['sender.zip_code']"
        placeholder="1234"
        readonly
      />
      <InputError :message="form.errors['sender.zip_code']" />
    </div>
  </div>

  <!-- Pick-up Region -->
  <div>
    <InputLabel for="pickUpRegion" value="Pick-up Region *" />
    <SelectInput
      id="pickUpRegion"
      v-model="form.pick_up_region_id"
      :options="PICKUP_REGIONS"
      option-value="value"
      option-label="label"
      class="mt-1 block w-full"
      :error="!!form.errors.pick_up_region_id"
    />
    <InputError :message="form.errors.pick_up_region_id" />
  </div>

  <!-- Navigation Buttons -->
  <div class="flex justify-center mt-6">
    <PrimaryButton @click="nextStep" :disabled="isLoading">
      Proceed to Receiver Details
    </PrimaryButton>
  </div>
</div>
        <!-- Step 2: Receiver Details -->
        <div v-if="currentStep === 2" class="space-y-6">
          <h2 class="text-xl flex items-center justify-center font-semibold">Receiver Details</h2>

          <!-- Important Information -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="font-semibold text-blue-800 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Important Information
            </h3>
            <p class="text-sm text-blue-700 mt-1">
              Accurate receiver information is crucial for successful delivery. Please double-check all contact details and address information.
            </p>
          </div>

          <!-- Receiver Type -->
          <div>
            <InputLabel value="Receiver Type" />
            <SelectInput
              v-model="form.receiver.customer_category"
              :options="customerCategoryOptions"
              option-value="value"
              option-label="label"
              class="mt-1 block w-full"
            />
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

          <!-- Drop-off Region -->
          <div>
            <InputLabel for="dropOffRegion" value="Drop-off Region *" />
            <SelectInput
              id="dropOffRegion"
              v-model="form.drop_off_region_id"
              :options="filteredDropoffRegions"
              option-value="value"
              option-label="label"
              class="mt-1 block w-full"
              :error="!!form.errors.drop_off_region_id"
            />
            <InputError :message="form.errors.drop_off_region_id" />
          </div>

          <!-- Navigation Buttons -->
          <div class="flex justify-between mt-6">
            <PrimaryButton @click="prevStep" :disabled="isLoading">
              Return to Sender Details
            </PrimaryButton>
            <PrimaryButton @click="nextStep" :disabled="isLoading">
              Continue to Package Selection
            </PrimaryButton>
          </div>
        </div>

    <!-- Step 3: Package Type Selection -->
<div v-if="currentStep === 3" class="space-y-6">
  <h2 class="text-xl flex items-center justify-center font-semibold">Package Type Selection</h2>

  <!-- Updated Advisory Message with consistent styling -->
  <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
    <h3 class="font-semibold text-yellow-800 flex items-center">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
      </svg>
      Advisory Notice
    </h3>
    <p class="text-sm text-yellow-700 mt-1">
      We only carry individual packages up to 10 cubic meters in volume (calculated as height × width × length in cm ÷ 1,000,000) 
      and 100 kg in weight. Items exceeding these limits require special freight arrangements. 
      Please ensure your items meet these requirements before booking.
    </p>
  </div>

  <!-- Important Information -->
  <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
    <h3 class="font-semibold text-blue-800 flex items-center">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      Important Information
    </h3>
    <p class="text-sm text-blue-700 mt-1">
      Select the appropriate package type for your items. Each preset has specific dimension and weight limits. Choose "Custom Size" for irregular items.
    </p>
  </div>

  <!-- Package Grid - Centered for single package, grid for multiple -->
  <div :class="['gap-6', form.packages.length === 1 ? 'flex justify-center' : 'grid grid-cols-1 md:grid-cols-2']">
    <!-- Dynamic Package Input -->
    <div v-for="(pkg, index) in form.packages" :key="index" 
         :class="['border p-4 rounded-lg space-y-4 bg-white shadow-sm hover:shadow-md transition-shadow', 
                  form.packages.length === 1 ? 'w-full md:w-2/3' : '']">
      <div class="flex justify-between items-center">
        <h3 class="text-lg font-semibold flex items-center">
          <span class="bg-green-100 text-green-800 rounded-full w-7 h-7 flex items-center justify-center mr-2">{{ index + 1 }}</span>
          Package {{ index + 1 }}
        </h3>
        <DangerButton 
          v-if="form.packages.length > 1" 
          @click="removePackage(index)"
          :disabled="isLoading"
          class="!px-3 !py-1.5 text-sm"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </DangerButton>
      </div>

      <div class="space-y-4">
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
        <div v-if="pkg.preset" class="bg-gray-50 p-3 rounded-lg text-sm">
          <h4 class="font-semibold mb-1">About this package type:</h4>
          
          <div v-if="pkg.preset === 'small_pouch'">
            <p class="text-xs">Perfect for small, flat items like documents, letters, or compact tools.</p>
            <p class="mt-1 text-xs text-gray-600">Example: Blueprints, measuring tapes, passports</p>
          </div>

          <div v-else-if="pkg.preset === 'medium_box'">
            <p class="text-xs">Standard box for books, handheld tools, or medium-sized items.</p>
            <p class="mt-1 text-xs text-gray-600">Example: Shoes, drills, small appliances, books</p>
          </div>

          <div v-else-if="pkg.preset === 'large_box'">
            <p class="text-xs">Great for larger items that need sturdy packaging.</p>
            <p class="mt-1 text-xs text-gray-600">Example: Safety gear, toolkits, clothing, kitchenware</p>
          </div>

          <div v-else-if="pkg.preset === 'xl_box'">
            <p class="text-xs">For bulky items that require extra space.</p>
            <p class="mt-1 text-xs text-gray-600">Example: Power tools, helmets, small furniture</p>
          </div>

          <div v-else-if="pkg.preset === 'large_sack'">
            <p class="text-xs">Best for loose, non-fragile items that can be packed together.</p>
            <p class="mt-1 text-xs text-gray-600">Example: Work gloves, fabric, plastic fittings</p>
          </div>

          <div v-else-if="pkg.preset === 'standard_roll'">
            <p class="text-xs">Good for compact, rollable items that aren't too long.</p>
            <p class="mt-1 text-xs text-gray-600">Example: Blueprints, rolls of wire, wrapping paper</p>
          </div>

          <div v-else-if="pkg.preset === 'bundle_roll'">
            <p class="text-xs">Designed for long, narrow items that can be rolled or bundled.</p>
            <p class="mt-1 text-xs text-gray-600">Example: Metal pipes, PVC tubes, banners, carpets</p>
          </div>

          <div v-else-if="pkg.preset === 'custom'">
            <p class="text-xs">Create your own package dimensions for unique or irregular items.</p>
            <p class="mt-1 text-xs text-gray-600">Example: Generators, concrete molds, oversized tools</p>
          </div>
        </div>

        <!-- Preset Image Gallery with Navigation Arrows -->
        <div v-if="pkg.preset" class="relative">
          <div class="relative w-full h-40 bg-gray-100 rounded-lg overflow-hidden shadow-sm flex flex-col justify-center items-center pb-8">
            <button 
              v-if="pkg.preset !== 'custom'"
              @click="cyclePreset(index, -1)"
              class="absolute left-2 top-1/2 transform -translate-y-1/2 z-10 bg-white bg-opacity-70 rounded-full p-1 hover:bg-opacity-100 transition-all"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            
            <img 
              v-if="containerPresets.find(p => p.value === pkg.preset)?.image"
              :src="containerPresets.find(p => p.value === pkg.preset)?.image" 
              :alt="containerPresets.find(p => p.value === pkg.preset)?.label"
              class="max-h-32 max-w-full object-contain mx-auto"
            />
            <div v-else class="flex flex-col items-center justify-center h-full text-gray-400">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
              </svg>
              <span class="mt-2 text-sm">Custom Package</span>
            </div>
            
            <button 
              v-if="pkg.preset !== 'custom'"
              @click="cyclePreset(index, 1)"
              class="absolute right-2 top-1/2 transform -translate-y-1/2 z-10 bg-white bg-opacity-70 rounded-full p-1 hover:bg-opacity-100 transition-all"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>
            
            <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-70 text-white p-2 text-center text-xs">
              {{ containerPresets.find(p => p.value === pkg.preset)?.label }}
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
      Return to Receiver Details
    </PrimaryButton>
    <PrimaryButton 
      @click="currentStep = 4" 
      :disabled="isLoading || !form.packages.every(p => p.preset)"
      class="!px-4 !py-2"
    >
      Proceed to Package Details
    </PrimaryButton>
  </div>
</div>

    <!-- Step 4: Package Details -->
<div v-if="currentStep === 4" class="space-y-6">
  <h2 class="text-xl flex items-center justify-center font-semibold">Package Details</h2>

  <!-- Important Information -->
  <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
    <h3 class="font-semibold text-blue-800 flex items-center">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      Important Information
    </h3>
    <p class="text-sm text-blue-700 mt-1">
      Please provide accurate details for each package. This information helps us handle your items safely and calculate the correct shipping costs.
    </p>
  </div>

  <!-- Dynamic Package Input -->
  <div v-for="(pkg, index) in form.packages" :key="index" class="border p-4 rounded-lg space-y-4 bg-white shadow-sm">
    <div class="flex justify-between items-center">
      <h3 class="text-lg font-semibold flex items-center">
        <span class="bg-green-100 text-green-800 rounded-full w-7 h-7 flex items-center justify-center mr-2">{{ index + 1 }}</span>
        Package {{ index + 1 }} - {{ pkg.preset ? containerPresets.find(p => p.value === pkg.preset)?.label : 'Custom Package' }}
      </h3>
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
        <p class="text-xs text-gray-500 mt-1">Give your package a descriptive name</p>
      </div>

      <!-- Removed Package Category dropdown but kept spacing -->
      <div>
        <InputLabel value="Package Type" />
        <div class="mt-1 p-2 bg-gray-100 rounded text-sm text-gray-600">
          {{ pkg.preset ? containerPresets.find(p => p.value === pkg.preset)?.label : 'Custom Package' }}
        </div>
        <p class="text-xs text-gray-500 mt-1">Package type is determined by your selection</p>
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
        <p class="text-xs text-gray-500 mt-1">Include any special handling requirements</p>
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
        <div v-if="pkg.photo" class="mt-2 text-sm text-gray-600 truncate">
          Selected: {{ pkg.photo.name }}
        </div>
        <div v-if="pkg.photo_url" class="mt-2 flex justify-center">
          <img :src="pkg.photo_url" class="h-20 object-cover rounded shadow mx-auto" />
        </div>
        <p class="text-xs text-gray-500 mt-1">Upload a photo to help identify your package</p>
      </div>
    </div>

    <!-- Dimensions & Package Value -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <InputLabel :for="`value-${index}`" value="Package Value (₱) *" />
        <SelectInput
          :id="`value-${index}`"
          v-model="pkg.value"
          :options="packageValueOptions"
          option-value="value"
          option-label="label"
          class="w-full"
          :error="!!form.errors[`packages.${index}.value`]"
          placeholder="Select package value range"
        />
        <InputError :message="form.errors[`packages.${index}.value`]" />
        <p class="text-xs text-gray-500 mt-1">Select the estimated value range for insurance purposes</p>
      </div>

      <!-- Custom: Show editable dimensions and weight in 2x2 grid -->
      <template v-if="pkg.preset === 'custom'">
        <div>
          <InputLabel value="Package Dimensions *" />
          <div class="grid grid-cols-2 gap-4 mt-1">
            <div>
              <InputLabel :for="`height-${index}`" value="Height (cm)" class="text-xs" />
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
              <InputLabel :for="`width-${index}`" value="Width (cm)" class="text-xs" />
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
              <InputLabel :for="`length-${index}`" value="Length (cm)" class="text-xs" />
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
              <InputLabel :for="`weight-${index}`" value="Weight (kg)" class="text-xs" />
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
          </div>
          <p class="text-xs text-gray-500 mt-2">Enter accurate dimensions for proper pricing</p>
        </div>
      </template>

      <!-- Preset: Show weight input with greyed out style -->
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
            class="w-full bg-gray-100 text-gray-600"
            :error="!!form.errors[`packages.${index}.weight`]"
            :placeholder="`Max ${containerPresets.find(p => p.value === pkg.preset)?.weight || 0} kg`"
            readonly
            disabled
          />
          <InputError :message="form.errors[`packages.${index}.weight`]" />
          <p class="text-xs text-gray-500 mt-1">Weight is predetermined for this package type</p>
        </div>
      </template>
    </div>
  </div>

  <!-- Navigation Buttons: Only show once after all packages -->
  <div class="flex justify-between pt-4">
    <PrimaryButton @click="currentStep = 3" :disabled="isLoading" class="!px-4 !py-2">
      Return to Package Types
    </PrimaryButton>
    <PrimaryButton 
      @click="currentStep = 5" 
      :disabled="isLoading"
      class="!px-4 !py-2"
    >
      Continue to Payment
    </PrimaryButton>
  </div>
</div>

        <!-- Step 5: Payment -->
        <div v-if="currentStep === 5" class="space-y-6">
          <h2 class="text-xl flex items-center justify-center font-semibold">Payment Price Calculator</h2>

          <!-- Important Information -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="font-semibold text-blue-800 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Important Information
            </h3>
            <p class="text-sm text-blue-700 mt-1">
              Review the calculated price based on your package details. Select your preferred payment method to complete your delivery request.
            </p>
          </div>

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

            <!-- Updated price breakdown display -->
      <div v-if="form.priceBreakdown" class="space-y-2 mt-4 bg-white p-4 rounded-lg shadow">
      <h3 class="font-semibold text-lg">Price Breakdown</h3>
      <div class="grid grid-cols-2 gap-2">
        <div>Base Fee:</div>
        <div class="text-right">₱{{ formatCurrency(form.priceBreakdown.base_fee) }}</div>
        
        <div>Volume Fee ({{ form.priceBreakdown.metrics?.total_volume?.toFixed(3) || 0 }} m³):</div>
        <div class="text-right">₱{{ formatCurrency(form.priceBreakdown.volume_fee) }}</div>
        
        <div>Weight Fee ({{ form.priceBreakdown.metrics?.total_weight?.toFixed(2) || 0 }} kg):</div>
        <div class="text-right">₱{{ formatCurrency(form.priceBreakdown.weight_fee) }}</div>
        
        <div>Package Fee ({{ form.packages.length }} items):</div>
        <div class="text-right">₱{{ formatCurrency(form.priceBreakdown.package_fee) }}</div>
      </div>
      <div class="border-t pt-2 mt-2 grid grid-cols-2 font-semibold">
        <div>Total:</div>
        <div class="text-right">₱{{ formatCurrency(form.total_price) }}</div>
      </div>
    </div>
          </div>

          <!-- Payment Type Selection -->
          <div>
            <InputLabel for="paymentType" value="Payment Type *" />
            <SelectInput
              id="paymentType"
              v-model="form.payment_type"
              :options="paymentTypeOptions"
              option-value="value"
              option-label="label"
              class="mt-1 block w-full"
              :error="!!form.errors.payment_type"
            />
            <InputError :message="form.errors.payment_type" />
            <div v-if="!isPostpaidEligible" class="text-sm text-gray-500 mt-1">
              Postpaid option only available after 3 completed deliveries
            </div>
          </div>

          <!-- Add Payment Terms Selection (only for postpaid) -->
          <div v-if="form.payment_type === 'postpaid'">
            <InputLabel for="paymentTerms" value="Payment Terms *" />
            <SelectInput
              id="paymentTerms"
              v-model="form.payment_terms"
              :options="paymentTermsOptions"
              option-value="value"
              option-label="label"
              class="mt-1 block w-full"
              :error="!!form.errors.payment_terms"
            />
            <InputError :message="form.errors.payment_terms" />
            <div class="mt-3 p-3 rounded bg-blue-50 rounded-xl text-blue-900 border border-blue-200 shadow">
              <strong>Note:</strong> For postpaid payment terms, our company will contact you via phone call or text message to discuss and finalize the payment agreement before your delivery request is accepted.
            </div>
          </div>

          <!-- Payment Method Selection (only for prepaid) -->
          <div v-if="form.payment_type === 'prepaid'">
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
              Return to Package Details
            </PrimaryButton>
            <PrimaryButton 
              @click="submitRequest" 
              :disabled="isLoading"
              :class="{ 'opacity-75 cursor-not-allowed': isLoading }"
            >
              <span v-if="isLoading">Submitting...</span>
              <span v-else>Submit Delivery Request</span>
            </PrimaryButton>
          </div>
        </div>

        <!-- Step 6: Confirmation -->
        <div v-if="currentStep === 6" class="space-y-6">
          <h2 class="text-2xl flex items-center justify-center font-bold text-green-800">Submission Successful</h2>

          <!-- Important Information -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="font-semibold text-blue-800 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Important Information
            </h3>
            <p class="text-sm text-blue-700 mt-1">
              Thank you for your submission! Please note the next steps for your delivery request based on your selected payment method.
            </p>
          </div>

          <div class="bg-green-100 text-green-900 p-6 rounded-xl text-center shadow-lg border border-green-200">
            <p class="text-2xl font-bold mb-2">✅ Your delivery request has been submitted successfully!</p>
            <p class="mt-2 text-lg font-semibold">Request ID: <span class="text-blue-700">DR-{{ $page.props.deliveryRequest?.id?.toString().padStart(6, '0') || 'Pending' }}</span></p>
            
            <!-- Postpaid success message -->
            <div v-if="form.payment_type === 'postpaid'" class="mt-6 p-6 bg-yellow-50 rounded-xl text-yellow-900 border border-yellow-200 shadow">
              <p class="font-bold text-lg mb-3">Postpaid Payment Selected</p>
              <ol class="list-decimal list-inside text-left mt-2 space-y-2 text-base text-gray-800 pl-4">
                <li class="leading-relaxed">Wait for an approval notification via notifications on your account.</li>
                <li class="leading-relaxed">Once approved, you will receive a reference number for your delivery.</li>
                <li class="leading-relaxed">Bring the package to the nearest branch and present your reference number.</li>
                <li class="leading-relaxed">Your payment will be collected later by a company collector.</li>
              </ol>
            </div>
            
                       
            <!-- Prepaid success message -->
            <div v-if="form.payment_type === 'prepaid' && ['cash','gcash','bank'].includes(form.payment_method)" class="mt-6 p-6 bg-blue-50 rounded-xl border border-blue-200 shadow">
              <p class="font-bold text-lg mb-3">Next Steps:</p>
              <ol class="list-decimal list-inside text-left mt-2 space-y-2 text-base text-gray-800 pl-4">
                <li class="leading-relaxed">Wait for an approval notification via email or SMS.</li>
                <li class="leading-relaxed">Once approved, you will receive payment instructions.</li>
                <li class="leading-relaxed">Proceed to your selected branch or payment method to complete the payment.</li>
              </ol>
            </div>
          </div>

          <!-- Navigation Buttons -->
          <div class="flex justify-center pt-8">
            <PrimaryButton @click="resetForm" class="text-lg px-8 py-3 rounded-lg">
              Submit Another Request
            </PrimaryButton>
          </div>
        </div>
      </div>
    </div>
  </GuestLayout>
</template>