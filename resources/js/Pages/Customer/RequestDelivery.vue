<script setup>
import { ref, computed, onMounted, watch, onUnmounted } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextArea from '@/Components/TextArea.vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import axios from 'axios';
import LeafletMap from '@/Components/LeafletMap.vue';


const downpaymentOptions = [
    { value: 'gcash', label: 'GCash' },
    { value: 'bank', label: 'Bank Transfer' }
];


// Add these to your refs
const pastReceivers = ref([]);
const selectedReceiver = ref('');

// Add this method to fetch past receivers
const fetchPastReceivers = async () => {
  try {
    const response = await axios.get(route('customer.delivery-requests.past-receivers'));
    pastReceivers.value = response.data;
    console.log('📋 Loaded past receivers:', pastReceivers.value.length);
  } catch (error) {
    console.error('Failed to fetch past receivers:', error);
    pastReceivers.value = [];
  }
};

// Add this method to handle receiver selection
const handleReceiverSelect = (receiverId) => {
  if (!receiverId) {
    // Clear form if "Select receiver" is chosen
    form.receiver = {
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
    };
    return;
  }


   const receiver = pastReceivers.value.find(r => r.id == receiverId);
  if (receiver) {
    form.receiver = {
      customer_category: receiver.customer_category || 'individual',
      first_name: receiver.first_name || '',
      middle_name: receiver.middle_name || '',
      last_name: receiver.last_name || '',
      company_name: receiver.company_name || '',
      email: receiver.email || '',
      mobile: receiver.mobile || '',
      phone: receiver.phone || '',
      building_number: receiver.building_number || '',
      street: receiver.street || '',
      barangay: receiver.barangay || '',
      city: receiver.city || '',
      province: receiver.province || '',
      zip_code: receiver.zip_code || '',
      notes: ''
    };
    
    console.log('✅ Prefilled receiver details:', receiver.display_name);
  }
};

router.on('before', (event) => {
  console.log('🔄 Inertia BEFORE event:', event);
});

router.on('start', (event) => {
  console.log('🚀 Inertia START event:', event);
});

router.on('progress', (event) => {
  console.log('📊 Inertia PROGRESS event:', event);
});

router.on('success', (event) => {
  console.log('✅ Inertia SUCCESS event:', event);
  console.log('   Page:', event.detail.page);
  console.log('   URL:', window.location.href);
});

router.on('error', (errors) => {
  console.log('❌ Inertia ERROR event:', errors);
});

router.on('finish', (event) => {
  console.log('🏁 Inertia FINISH event:', event);
  console.log('   Current URL:', window.location.href);
});

// Also log any route changes
window.addEventListener('popstate', (event) => {
  console.log('🧭 Browser history changed:', event);
  console.log('   Current URL:', window.location.href);
});

const clearPhotoErrors = (index) => {
  form.clearErrors(`packages.${index}.photos`);
  showSizeWarning.value[index] = false;
};

const props = defineProps({
    authCustomer: Object,
    categories: Array,
    regions: Array,
    priceMatrix: Object
});

const packageStep = ref(1);

// HARDCODED PICKUP REGIONS (Easily editable)
const PICKUP_REGIONS = ref([]);

const showPackageNotices = ref(true);
const showAdvisoryNotice = ref(true);
const showPhotoRequirements = ref(true);
const showPackageGuide = ref(true);
const showSizeWarning = ref({});

// DYNAMIC PACKAGE CATEGORIES - REPLACED HARDCODED PRESETS
const packageCategories = ref([]);

// Computed property to format categories for frontend
const formattedPackageCategories = computed(() => {
  const categories = packageCategories.value.map(category => {
    const hasDimensions = category.dimensions && 
                         category.dimensions.length && 
                         category.dimensions.height && 
                         category.dimensions.width;
    
    const label = hasDimensions 
      ? `${category.name} (L ${category.dimensions.length}cm × H ${category.dimensions.height}cm × W ${category.dimensions.width}cm)`
      : category.name;

    return {
      label: label,
      value: category.id, // Use category ID instead of code for uniqueness
      dimensions: category.dimensions || null,
      category: category.code, // Keep code for display/grouping if needed
      image: category.image_url || '/images/presets/default.png',
      is_custom: category.code === 'custom',
      original_category: category // Keep the original category data
    };
  });

  // Add "Custom Size" option if not already in database
  if (!categories.find(cat => cat.is_custom)) {
    categories.push({
      label: 'Custom Size',
      value: 'custom', // Keep 'custom' as value for custom size
      dimensions: null,
      category: 'C/S',
      image: '/images/presets/custom.png',
      is_custom: true
    });
  }

  return categories;
});



const calculatePackageVolume = (pkg) => {
  const length = parseFloat(pkg.length) / 100; // Convert cm to m
  const height = parseFloat(pkg.height) / 100;
  const width = parseFloat(pkg.width) / 100;
  const quantity = parseInt(pkg.quantity) || 1;
  
  return (length * height * width) * quantity;
};

const calculateTotalVolume = () => {
  return form.packages.reduce((total, pkg) => {
    return total + calculatePackageVolume(pkg);
  }, 0);
};

const calculateTotalWeight = () => {
  return form.packages.reduce((total, pkg) => {
    const weight = parseFloat(pkg.weight) || 0;
    const quantity = parseInt(pkg.quantity) || 1;
    return total + (weight * quantity);
  }, 0);
};

const hasMultipleWeightRanges = () => {
  if (form.packages.length <= 1) return false;
  
  const weights = form.packages.map(pkg => pkg.weight);
  const uniqueWeights = [...new Set(weights)];
  return uniqueWeights.length > 1;
};

// Method to fetch package categories from API
const fetchPackageCategories = async () => {
  try {
    const response = await axios.get('/package-categories');
    packageCategories.value = response.data.categories || [];
    
    console.log('📦 Loaded package categories:', packageCategories.value.length);
  } catch (error) {
    console.error('Failed to fetch package categories:', error);
    packageCategories.value = getDefaultCategories();
  }
};

const getDefaultCategories = () => {
  return [
    {
      id: 1,
      name: 'Small Pouch',
      code: 'piece', // Changed from 'small_pouch' to 'piece'
      description: 'Small documents and letters',
      dimensions: { length: 25, height: 1, width: 15 },
      image_url: '/images/presets/small_pouch.png',
      is_active: true,
      sort_order: 1
    },
    {
      id: 2,
      name: 'Medium Box',
      code: 'carton', // Changed from 'medium_box' to 'carton'
      description: 'Standard medium-sized boxes',
      dimensions: { length: 30, height: 20, width: 25 },
      image_url: '/images/presets/medium_box.png',
      is_active: true,
      sort_order: 2
    },
    {
      id: 3,
      name: 'Large Box',
      code: 'carton', // Changed from 'large_box' to 'carton'
      description: 'Large boxes for bigger items',
      dimensions: { length: 50, height: 35, width: 40 },
      image_url: '/images/presets/large_box.png',
      is_active: true,
      sort_order: 3
    },
    {
      id: 4,
      name: 'Extra Large Box',
      code: 'carton', // Changed from 'xl_box' to 'carton'
      description: 'Extra large boxes for bulky items',
      dimensions: { length: 70, height: 50, width: 50 },
      image_url: '/images/presets/xl_box.png',
      is_active: true,
      sort_order: 4
    },
    {
      id: 5,
      name: 'Large Sack',
      code: 'sack', // Changed from 'large_sack' to 'sack'
      description: 'Large sacks for loose items',
      dimensions: { length: 60, height: 40, width: 40 },
      image_url: '/images/presets/large_sack.png',
      is_active: true,
      sort_order: 5
    },
    {
      id: 6,
      name: 'Standard Roll',
      code: 'roll', // Changed from 'standard_roll' to 'roll'
      description: 'Standard rolls for cylindrical items',
      dimensions: { length: 50, height: 10, width: 10 },
      image_url: '/images/presets/standard_roll.png',
      is_active: true,
      sort_order: 6
    },
    {
      id: 7,
      name: 'Bundle Roll',
      code: 'bundle', // Changed from 'bundle_roll' to 'bundle'
      description: 'Bundle rolls for larger cylindrical items',
      dimensions: { length: 100, height: 10, width: 10 },
      image_url: '/images/presets/bundle_roll.png',
      is_active: true,
      sort_order: 7
    }
  ];
};

// Weight range options (charging upper limit)
const weightRangeOptions = [
  { value: 5, label: '0-5 kg' },
  { value: 10, label: '6-10 kg' },
  { value: 15, label: '11-15 kg' },
  { value: 20, label: '16-20 kg' },
  { value: 25, label: '21-25 kg' },
  { value: 30, label: '26-30 kg' },
  { value: 40, label: '31-40 kg' },
  { value: 50, label: '41-50 kg' },
  { value: 60, label: '51-60 kg' },
  { value: 70, label: '61-70 kg' },
  { value: 80, label: '71-80 kg' },
  { value: 90, label: '81-90 kg' },
  { value: 100, label: '91-100 kg' }
];

// Package value options for dropdown
const packageValueOptions = [
  { value: 500, label: 'Value: ₱500 or less' },
  { value: 1000, label: 'Value: ₱1,000 or less' },
  { value: 2000, label: 'Value: ₱2,000 or less' },
  { value: 4000, label: 'Value: ₱4,000 or less' },
  { value: 6000, label: 'Value: ₱6,000 or less' },
  { value: 10000, label: 'Value: ₱10,000 or less' },
  { value: 20000, label: 'Value: ₱20,000 or less' },
  { value: 30000, label: 'Value: ₱30,000 or less' },
  { value: 50000, label: 'Value: ₱50,000 or less' },
  { value: 70000, label: 'Value: ₱70,000 or less' },
  { value: 100000, label: 'Value: ₱100,000 or less' },
];

// Form Step Management
const currentStep = ref(1);
const isLoading = ref(false);
const isUsingMyInfo = ref(false);
const deliveryRequestId = ref(null);
const showSuccessScreen = ref(false);

// For dropdown options 
const branches = ref([]);
const priceMatrix = ref({
  base_fee: 0,
  volume_rate: 0,
  weight_rate: 0,
  package_rate: 0
});

// Region Information
const selectedRegionInfo = ref(null);
const selectedDropoffRegionInfo = ref(null); 

const fetchRegions = async () => {
  try {
    const response = await axios.get('/api/delivery/regions');
    const regionsData = response.data.data || response.data;
    
    if (!Array.isArray(regionsData)) return;

    // Format regions with capitalized labels
    branches.value = regionsData.map(region => ({
      value: region.id,
      label: region.name ? region.name.replace(/\b\w/g, char => char.toUpperCase()) : ''
    }));

    // Also populate PICKUP_REGIONS with the same formatted data
    PICKUP_REGIONS.value = branches.value;

    regionsData.forEach(region => {
      regionsDataMap.value[region.id] = {
        id: region.id,
        name: region.name ? region.name.replace(/\b\w/g, char => char.toUpperCase()) : '',
        warehouse_address: region.warehouse_address ? region.warehouse_address.replace(/\b\w/g, char => char.toUpperCase()) : '',
        latitude: region.latitude,
        longitude: region.longitude,
        is_active: region.is_active,
        color_hex: region.color_hex
      };
    });

    // Set default regions if available
    if (regionsData.length > 0) {
      form.pick_up_region_id = regionsData[0].id;
      selectedRegionInfo.value = regionsDataMap.value[regionsData[0].id];
      
      // Set default drop-off region if different from first
      if (regionsData.length > 1) {
        form.drop_off_region_id = regionsData[1].id;
        selectedDropoffRegionInfo.value = regionsDataMap.value[regionsData[1].id];
      }
    }

  } catch (error) {
    branches.value = [];
    PICKUP_REGIONS.value = [];
  }
};

const regionsDataMap = ref({});

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

priceMatrix.value = getDefaultPriceMatrix();

const fetchPriceMatrix = async () => {
  try {
    const response = await axios.get('/api/price-matrix', {
      timeout: 5000,
      headers: {
        'Cache-Control': 'no-cache'
      }
    });

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

    Object.entries(priceMatrix.value).forEach(([key, value]) => {
      if (isNaN(value) || value < 0) {
        priceMatrix.value[key] = getDefaultRate(key);
      }
    });

  } catch (error) {
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

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const calculateTotalSize = (files) => {
  if (!files || !files.length) return '0 B';
  const totalBytes = files.reduce((total, file) => total + (file.size || 0), 0);
  return formatFileSize(totalBytes);
};

const customerCategoryOptions = [
  { value: 'individual', label: 'Individual' },
  { value: 'company', label: 'Company' }
];

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
    pick_up_region_id: '',
    drop_off_region_id: '',
    payment_type: '',
    payment_method: '',
    payment_terms: '',
    total_price: 0,
       downpayment_method: '',
    downpayment_reference: '',
    downpayment_receipt: null,
    downpayment_receipt_url: null,
    priceBreakdown: null,
    packages: [
      {
        item_name: '',
        description: '',
        category: '',
        length: '',
        height: '',
        width: '',
        weight: '',
        value: '',
        quantity: 1,
        photos: [],
        photo_urls: [],
        preset: ''
      },
    ],
});

const handleDownpaymentReceiptUpload = async (event) => {
    const file = event.target.files[0];
    if (file) {
        // Validate file
        if (!file.type.match('image.*')) {
            form.setError('downpayment_receipt', 'Only image files are allowed');
            event.target.value = '';
            return;
        }
        
        if (file.size > 5 * 1024 * 1024) {
            form.setError('downpayment_receipt', 'File size must be less than 5MB');
            event.target.value = '';
            return;
        }
        
        // Compress and process image
        try {
            const compressedFile = await compressImage(file, 1200, 0.7);
            form.downpayment_receipt = compressedFile;
            form.downpayment_receipt_url = URL.createObjectURL(compressedFile);
            form.clearErrors('downpayment_receipt');
            
            console.log('📄 Downpayment receipt processed:', {
                name: compressedFile.name,
                size: formatFileSize(compressedFile.size),
                type: compressedFile.type
            });
        } catch (error) {
            console.error('❌ Failed to process downpayment receipt:', error);
            form.setError('downpayment_receipt', 'Failed to process image. Please try another file.');
            event.target.value = '';
        }
    } else {
        form.downpayment_receipt = null;
        form.downpayment_receipt_url = null;
        form.clearErrors('downpayment_receipt');
    }
};

const removeDownpaymentReceipt = () => {
    if (form.downpayment_receipt_url) {
        URL.revokeObjectURL(form.downpayment_receipt_url);
    }
    form.downpayment_receipt = null;
    form.downpayment_receipt_url = null;
    form.clearErrors('downpayment_receipt');
    
    // Clear the file input
    const fileInput = document.querySelector('input[type="file"][accept="image/*"]');
    if (fileInput) {
        fileInput.value = '';
    }
};

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

// Calculate total quantity of all packages
const totalPackageQuantity = computed(() => {
  return form.packages.reduce((total, pkg) => {
    return total + (parseInt(pkg.quantity) || 1);
  }, 0);
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
    let totalPackages = 0;
    const packagesData = [];

    form.packages.forEach(pkg => {
      const quantity = parseInt(pkg.quantity) || 1;
      totalPackages += quantity;
      
      for (let i = 0; i < quantity; i++) {
        packagesData.push({
          height: parseFloat(pkg.height) || 0,
          width: parseFloat(pkg.width) || 0,
          length: parseFloat(pkg.length) || 0,
          weight: parseFloat(pkg.weight) || 0
        });
      }
    });

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
    form.priceBreakdown = null;
    form.total_price = 0;
  } finally {
    isLoading.value = false;
  }
};

const handlePresetChange = (selectedValue, index) => {
  // If it's custom size
  if (selectedValue === 'custom') {
    form.packages[index] = {
      ...form.packages[index],
      length: '',
      height: '', 
      width: '',
      category: 'C/S',
      preset: 'custom'
    };
    return;
  }

  // Find the category by ID (selectedValue is now the category ID)
  const category = packageCategories.value.find(cat => cat.id == selectedValue);
  
  if (!category) return;

  form.packages[index] = {
    ...form.packages[index],
    length: category.dimensions?.length || '',
    height: category.dimensions?.height || '',
    width: category.dimensions?.width || '',
    category: category.code, // Use the code for category type
    preset: category.id // Store the category ID as preset
  };
};

const cyclePreset = (packageIndex, direction) => {
  const currentPreset = form.packages[packageIndex].preset;
  if (!currentPreset) return;

  const currentIndex = formattedPackageCategories.value.findIndex(p => p.value === currentPreset);
  if (currentIndex === -1) return;

  let newIndex = currentIndex + direction;
  
  if (newIndex < 0) {
    newIndex = formattedPackageCategories.value.length - 1;
  } else if (newIndex >= formattedPackageCategories.value.length) {
    newIndex = 0;
  }

  handlePresetChange(formattedPackageCategories.value[newIndex].value, packageIndex);
};

const autoCapitalize = (text) => {
  return text.replace(/\b\w/g, char => char.toUpperCase());
};

// Handle name input with auto-capitalization
const handleNameInputWithCapitalize = (event, field, packageIndex = null) => {
  let value = event.target.value;
  value = sanitizeName(value);
  value = autoCapitalize(value);
  
  if (packageIndex !== null) {
    form.packages[packageIndex][field] = value;
  } else if (field.includes('.')) {
    const [parent, child] = field.split('.');
    form[parent][child] = value;
  } else {
    form[field] = value;
  }
};

// Handle address input with auto-capitalization
const handleAddressInputWithCapitalize = (event, field, packageIndex = null) => {
  let value = event.target.value;
  value = sanitizeAddress(value);
  value = autoCapitalize(value);
  
  if (packageIndex !== null) {
    form.packages[packageIndex][field] = value;
  } else if (field.includes('.')) {
    const [parent, child] = field.split('.');
    form[parent][child] = value;
  } else {
    form[field] = value;
  }
};

// Handle text input with auto-capitalization for package names
const handleTextInputWithCapitalize = (event, field, packageIndex = null) => {
  let value = event.target.value;
  value = sanitizeText(value);
  value = autoCapitalize(value);
  
  if (packageIndex !== null) {
    form.packages[packageIndex][field] = value;
  } else if (field.includes('.')) {
    const [parent, child] = field.split('.');
    form[parent][child] = value;
  } else {
    form[field] = value;
  }
};

// Update filteredDropoffRegions to exclude selected pick-up region
const filteredDropoffRegions = computed(() => {
  if (!form.pick_up_region_id) return branches.value;
  return branches.value.filter(region => region.value !== form.pick_up_region_id);
});

// ENHANCED VALIDATION FUNCTIONS
const isValidEmail = (email) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
const isValidMobile = (mobile) => /^(09|\+639)\d{9}$/.test(mobile);
const isValidPhone = (phone) => phone === '' || /^[0-9]{7,15}$/.test(phone);
const isValidZipCode = (zip) => /^[0-9]{4}$/.test(zip);
const isValidName = (name) => /^[A-Za-z\sÑñ.-]+$/.test(name);
const isValidText = (text) => /^[A-Za-z0-9\sÑñ.,!?@#$%^&*()_+-=]+$/.test(text);
const isValidAddress = (address) => /^[A-Za-z0-9\sÑñ.,#-]+$/.test(address);
const isOnlyNumbers = (value) => /^\d*$/.test(value);

// INPUT SANITIZATION FUNCTIONS
const sanitizeNumbersOnly = (value) => value.replace(/[^\d]/g, '');
const sanitizeName = (value) => value.replace(/[^A-Za-z\sÑñ.-]/g, '');
const sanitizeAddress = (value) => value.replace(/[^A-Za-z0-9\sÑñ.,#-]/g, '');
const sanitizeText = (value) => value.replace(/[^A-Za-z0-9\sÑñ.,!?@#$%^&*()_+-=]/g, '');

// INPUT HANDLERS FOR VALIDATION
const handleNumberInput = (event, field, packageIndex = null) => {
  let value = event.target.value;
  value = sanitizeNumbersOnly(value);
  
  if (packageIndex !== null) {
    form.packages[packageIndex][field] = value;
  } else if (field.includes('.')) {
    const [parent, child] = field.split('.');
    form[parent][child] = value;
  } else {
    form[field] = value;
  }
};

const handleNameInput = (event, field, packageIndex = null) => {
  let value = event.target.value;
  value = sanitizeName(value);
  
  if (packageIndex !== null) {
    form.packages[packageIndex][field] = value;
  } else if (field.includes('.')) {
    const [parent, child] = field.split('.');
    form[parent][child] = value;
  } else {
    form[field] = value;
  }
};

const handleAddressInput = (event, field, packageIndex = null) => {
  let value = event.target.value;
  value = sanitizeAddress(value);
  
  if (packageIndex !== null) {
    form.packages[packageIndex][field] = value;
  } else if (field.includes('.')) {
    const [parent, child] = field.split('.');
    form[parent][child] = value;
  } else {
    form[field] = value;
  }
};

const handleTextInput = (event, field, packageIndex = null) => {
  let value = event.target.value;
  value = sanitizeText(value);
  
  if (packageIndex !== null) {
    form.packages[packageIndex][field] = value;
  } else if (field.includes('.')) {
    const [parent, child] = field.split('.');
    form[parent][child] = value;
  } else {
    form[field] = value;
  }
};

// ENHANCED VALIDATION FUNCTION
const validateStep = () => {
  form.clearErrors();
  let isValid = true;

  if (currentStep.value === 1) {
    // Customer Category validation
    if (!form.sender.customer_category) {
      form.setError('sender.customer_category', 'Customer category is required.');
      isValid = false;
    }

    // Name validations
    if (!form.sender.first_name.trim()) {
      form.setError('sender.first_name', 'First name is required.');
      isValid = false;
    } else if (!isValidName(form.sender.first_name)) {
      form.setError('sender.first_name', 'First name can only contain letters, spaces, dots, and hyphens.');
      isValid = false;
    }

    if (form.sender.middle_name.trim() && !isValidName(form.sender.middle_name)) {
      form.setError('sender.middle_name', 'Middle name can only contain letters, spaces, dots, and hyphens.');
      isValid = false;
    }

    if (!form.sender.last_name.trim()) {
      form.setError('sender.last_name', 'Last name is required.');
      isValid = false;
    } else if (!isValidName(form.sender.last_name)) {
      form.setError('sender.last_name', 'Last name can only contain letters, spaces, dots, and hyphens.');
      isValid = false;
    }

    // Company name validation for companies
    if (form.sender.customer_category === 'company' && !form.sender.company_name.trim()) {
      form.setError('sender.company_name', 'Company Name is required for companies.');
      isValid = false;
    }

    // Contact information validations
    if (!form.sender.email.trim()) {
      form.setError('sender.email', 'Email is required.');
      isValid = false;
    } else if (!isValidEmail(form.sender.email)) {
      form.setError('sender.email', 'Please enter a valid email address.');
      isValid = false;
    }

    if (!form.sender.mobile.trim()) {
      form.setError('sender.mobile', 'Mobile number is required.');
      isValid = false;
    } else if (!isValidMobile(form.sender.mobile)) {
      form.setError('sender.mobile', 'Please enter a valid Philippine mobile number (e.g. 09123456789).');
      isValid = false;
    }

    if (form.sender.phone && !isValidPhone(form.sender.phone)) {
      form.setError('sender.phone', 'Please enter a valid phone number (7-15 digits).');
      isValid = false;
    }

    // Address validations
    if (form.sender.building_number.trim() && !isValidAddress(form.sender.building_number)) {
      form.setError('sender.building_number', 'Building number can only contain letters, numbers, spaces, and basic punctuation.');
      isValid = false;
    }

    if (!form.sender.street.trim()) {
      form.setError('sender.street', 'Street is required.');
      isValid = false;
    } else if (!isValidAddress(form.sender.street)) {
      form.setError('sender.street', 'Street can only contain letters, numbers, spaces, and basic punctuation.');
      isValid = false;
    }

    if (!form.sender.barangay.trim()) {
      form.setError('sender.barangay', 'Barangay is required.');
      isValid = false;
    } else if (!isValidAddress(form.sender.barangay)) {
      form.setError('sender.barangay', 'Barangay can only contain letters, numbers, spaces, and basic punctuation.');
      isValid = false;
    }

    if (!form.sender.city.trim()) {
      form.setError('sender.city', 'City is required.');
      isValid = false;
    } else if (!isValidAddress(form.sender.city)) {
      form.setError('sender.city', 'City can only contain letters, numbers, spaces, and basic punctuation.');
      isValid = false;
    }

    if (!form.sender.province.trim()) {
      form.setError('sender.province', 'Province is required.');
      isValid = false;
    } else if (!isValidAddress(form.sender.province)) {
      form.setError('sender.province', 'Province can only contain letters, numbers, spaces, and basic punctuation.');
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
    }
  }

  if (currentStep.value === 2) {
    // Customer Category validation
    if (!form.receiver.customer_category) {
      form.setError('receiver.customer_category', 'Customer category is required.');
      isValid = false;
    }

    // Name validations
    if (!form.receiver.first_name.trim()) {
      form.setError('receiver.first_name', 'First name is required.');
      isValid = false;
    } else if (!isValidName(form.receiver.first_name)) {
      form.setError('receiver.first_name', 'First name can only contain letters, spaces, dots, and hyphens.');
      isValid = false;
    }

    if (form.receiver.middle_name.trim() && !isValidName(form.receiver.middle_name)) {
      form.setError('receiver.middle_name', 'Middle name can only contain letters, spaces, dots, and hyphens.');
      isValid = false;
    }

    if (!form.receiver.last_name.trim()) {
      form.setError('receiver.last_name', 'Last name is required.');
      isValid = false;
    } else if (!isValidName(form.receiver.last_name)) {
      form.setError('receiver.last_name', 'Last name can only contain letters, spaces, dots, and hyphens.');
      isValid = false;
    }

    // Company name validation for companies
    if (form.receiver.customer_category === 'company' && !form.receiver.company_name.trim()) {
      form.setError('receiver.company_name', 'Company Name is required for companies.');
      isValid = false;
    }

    // Contact information validations
    if (!form.receiver.email.trim()) {
      form.setError('receiver.email', 'Email is required.');
      isValid = false;
    } else if (!isValidEmail(form.receiver.email)) {
      form.setError('receiver.email', 'Please enter a valid email address.');
      isValid = false;
    }

    if (!form.receiver.mobile.trim()) {
      form.setError('receiver.mobile', 'Mobile number is required.');
      isValid = false;
    } else if (!isValidMobile(form.receiver.mobile)) {
      form.setError('receiver.mobile', 'Please enter a valid Philippine mobile number (e.g. 09123456789).');
      isValid = false;
    }

    if (form.receiver.phone && !isValidPhone(form.receiver.phone)) {
      form.setError('receiver.phone', 'Please enter a valid phone number (7-15 digits).');
      isValid = false;
    }

    // Address validations
    if (form.receiver.building_number.trim() && !isValidAddress(form.receiver.building_number)) {
      form.setError('receiver.building_number', 'Building number can only contain letters, numbers, spaces, and basic punctuation.');
      isValid = false;
    }

    if (!form.receiver.street.trim()) {
      form.setError('receiver.street', 'Street is required.');
      isValid = false;
    } else if (!isValidAddress(form.receiver.street)) {
      form.setError('receiver.street', 'Street can only contain letters, numbers, spaces, and basic punctuation.');
      isValid = false;
    }

    if (!form.receiver.barangay.trim()) {
      form.setError('receiver.barangay', 'Barangay is required.');
      isValid = false;
    } else if (!isValidAddress(form.receiver.barangay)) {
      form.setError('receiver.barangay', 'Barangay can only contain letters, numbers, spaces, and basic punctuation.');
      isValid = false;
    }

    if (!form.receiver.city.trim()) {
      form.setError('receiver.city', 'City is required.');
      isValid = false;
    } else if (!isValidAddress(form.receiver.city)) {
      form.setError('receiver.city', 'City can only contain letters, numbers, spaces, and basic punctuation.');
      isValid = false;
    }

    if (!form.receiver.province.trim()) {
      form.setError('receiver.province', 'Province is required.');
      isValid = false;
    } else if (!isValidAddress(form.receiver.province)) {
      form.setError('receiver.province', 'Province can only contain letters, numbers, spaces, and basic punctuation.');
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
    }
  }
  
  if (currentStep.value === 3) {
    form.packages.forEach((pkg, index) => {
      // Package name validation
      if (!pkg.item_name.trim()) {
        form.setError(`packages.${index}.item_name`, 'Package name is required.');
        isValid = false;
      } else if (!isValidText(pkg.item_name)) {
        form.setError(`packages.${index}.item_name`, 'Package name contains invalid characters.');
        isValid = false;
      }

      // Description validation (optional)
      if (pkg.description.trim() && !isValidText(pkg.description)) {
        form.setError(`packages.${index}.description`, 'Description contains invalid characters.');
        isValid = false;
      }

      // Package type validation
      if (!pkg.preset) {
        form.setError(`packages.${index}.preset`, 'Package type is required.');
        isValid = false;
      }

      // Dimension validations (only required for custom packages)
      if (pkg.preset === 'custom') {
        const height = sanitizeNumber(pkg.height) / 100;
        const width = sanitizeNumber(pkg.width) / 100;
        const length = sanitizeNumber(pkg.length) / 100;
        
        if (!pkg.height || height <= 0) {
          form.setError(`packages.${index}.height`, 'Height must be greater than 0 for custom packages.');
          isValid = false;
        }
        if (!pkg.width || width <= 0) {
          form.setError(`packages.${index}.width`, 'Width must be greater than 0 for custom packages.');
          isValid = false;
        }
        if (!pkg.length || length <= 0) {
          form.setError(`packages.${index}.length`, 'Length must be greater than 0 for custom packages.');
          isValid = false;
        }

        const volume = length * height * width;
        if (volume > 10) {
            form.setError(`packages.${index}.height`, 'Package volume exceeds 10 m³.');
            isValid = false;
        }
      }

      // Weight validation
      const weight = sanitizeNumber(pkg.weight);
      if (!pkg.weight || weight <= 0) {
        form.setError(`packages.${index}.weight`, 'Weight range is required.');
        isValid = false;
      } else if (weight > 100) {
          form.setError(`packages.${index}.weight`, 'Package weight exceeds 100 kg.');
          isValid = false;
      }

      // Value validation
      const value = sanitizeNumber(pkg.value);
      if (!pkg.value || value < 0) {
        form.setError(`packages.${index}.value`, 'Package value is required.');
        isValid = false;
      }

      // Quantity validation
      const quantity = parseInt(pkg.quantity) || 1;
      if (quantity < 1) {
        form.setError(`packages.${index}.quantity`, 'Quantity must be at least 1.');
        isValid = false;
      } else if (quantity > 100) {
        form.setError(`packages.${index}.quantity`, 'Quantity cannot exceed 100.');
        isValid = false;
      }

      // Photo validation
      if (!pkg.photos || pkg.photos.length === 0) {
  form.setError(`packages.${index}.photos`, 'Package photos are required. Minimum 6 photos for all packages.');
  isValid = false;
} else if (pkg.photos.length < 6) {
  // Always require 6 photos regardless of quantity
  form.setError(`packages.${index}.photos`, 
    `Minimum 6 photos required for all packages. You have ${pkg.photos.length}/6 photos.` +
    (pkg.quantity > 1 ? ' Must include group photo and weighing scale.' : ' Must include all sides and weighing scale.')
  );
  isValid = false;
}
    });
  }
  
    if (currentStep.value === 4) {
        // Downpayment validation (always required)
        if (!form.downpayment_method) {
            form.setError('downpayment_method', 'Processing fee payment method is required');
            isValid = false;
        }
        
        if (!form.downpayment_reference) {
            form.setError('downpayment_reference', 'Reference number is required');
            isValid = false;
        }
        
        if (!form.downpayment_receipt) {
            form.setError('downpayment_receipt', 'Payment receipt is required');
            isValid = false;
        }

        // Existing payment validation
        if (!form.payment_type) {
            form.setError('payment_type', 'Payment type is required');
            isValid = false;
        }

        if (form.payment_type === 'prepaid' && !form.payment_method) {
            form.setError('payment_method', 'Payment method is required for prepaid');
            isValid = false;
        }

        if (form.payment_type === 'postpaid' && !form.payment_terms) {
            form.setError('payment_terms', 'Payment terms are required for postpaid');
            isValid = false;
        }
    }

    return isValid;
};

const compressImage = (file, maxWidth = 1200, quality = 0.7) => {
  return new Promise((resolve) => {
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = (event) => {
      const img = new Image();
      img.src = event.target.result;
      img.onload = () => {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        // Calculate new dimensions
        let width = img.width;
        let height = img.height;
        
        if (width > maxWidth) {
          height = (height * maxWidth) / width;
          width = maxWidth;
        }
        
        canvas.width = width;
        canvas.height = height;
        
        ctx.drawImage(img, 0, 0, width, height);
        
        canvas.toBlob(
          (blob) => {
            resolve(new File([blob], file.name, {
              type: 'image/jpeg',
              lastModified: Date.now()
            }));
          },
          'image/jpeg',
          quality
        );
      };
    };
  });
};

const handlePhotoUpload = async (event, index) => {
  const newFiles = Array.from(event.target.files);
  if (newFiles && newFiles.length > 0) {
    form.clearErrors(`packages.${index}.photos`);
    
    // Check for any large files first - BEFORE processing
    const oversizedFile = newFiles.find(file => file.size > 5 * 1024 * 1024);
    if (oversizedFile) {
      form.setError(`packages.${index}.photos`, `File "${oversizedFile.name}" exceeds 5MB limit. Please choose a smaller image.`);
      event.target.value = ''; // Clear the input
      return; // Stop processing immediately
    }
    
    // Get existing files and URLs
    const existingFiles = form.packages[index].photos || [];
    const existingUrls = form.packages[index].photo_urls || [];
    
    const validFiles = [...existingFiles];
    const fileUrls = [...existingUrls];
    
    let hasLargeFiles = false;
    let uploadError = null;

    for (let i = 0; i < newFiles.length; i++) {
      const file = newFiles[i];
      
      if (!file.type.match('image.*')) {
        uploadError = 'Only image files are allowed';
        break;
      }
      
      // Check for duplicate files (by name and size)
      const isDuplicate = validFiles.some(existingFile => 
        existingFile.name === file.name && existingFile.size === file.size
      );
      
      if (isDuplicate) {
        console.log(`Skipping duplicate file: ${file.name}`);
        continue;
      }
      
      // Check if file is large (for compression warning)
      if (file.size > 1024 * 1024) {
        hasLargeFiles = true;
      }
      
      try {
        let processedFile = file;
        
        // Compress image if it's larger than 1MB
        if (file.size > 1024 * 1024) {
          processedFile = await compressImage(file);
          console.log(`Compressed ${file.name}: ${formatFileSize(file.size)} → ${formatFileSize(processedFile.size)}`);
        }
        
        validFiles.push(processedFile);
        fileUrls.push(URL.createObjectURL(processedFile));
        
      } catch (error) {
        console.error('Image processing failed:', error);
        uploadError = 'Failed to process some images. Please try again.';
        break;
      }
    }
    
    if (uploadError) {
      form.setError(`packages.${index}.photos`, uploadError);
    } else {
      // Only update if no errors occurred
      form.packages[index].photos = validFiles;
      form.packages[index].photo_urls = fileUrls;
      showSizeWarning.value[index] = hasLargeFiles;
      
      // Clear size warning after 5 seconds
      if (hasLargeFiles) {
        setTimeout(() => {
          showSizeWarning.value[index] = false;
        }, 5000);
      }
    }
    
    // Clear the file input to allow selecting the same files again
    event.target.value = '';
  }
};

const debugPhotos = (index) => {
  const pkg = form.packages[index];
  console.log('📸 Photo Debug for Package', index + 1);
  console.log('Total photos:', pkg.photos?.length || 0);
  console.log('Photo details:', pkg.photos?.map((photo, i) => ({
    index: i,
    name: photo.name,
    size: formatFileSize(photo.size),
    type: photo.type
  })) || 'No photos');
};
const removePhoto = (packageIndex, photoIndex) => {
  if (form.packages[packageIndex].photo_urls && form.packages[packageIndex].photo_urls[photoIndex]) {
    // Revoke the object URL to prevent memory leaks
    URL.revokeObjectURL(form.packages[packageIndex].photo_urls[photoIndex]);
  }
  
  // Remove the photo from both arrays
  form.packages[packageIndex].photos.splice(photoIndex, 1);
  form.packages[packageIndex].photo_urls.splice(photoIndex, 1);
  
  console.log(`🗑️ Removed photo ${photoIndex + 1} from package ${packageIndex + 1}`);
};


const addPackage = () => {
  form.packages.push({
    item_name: '',
    description: '',
    category: '',
    length: '',
    height: '',
    width: '',
    weight: '',
    value: '',
    quantity: 1,
    photos: [],
    photo_urls: [],
    preset: ''
  });
};

const duplicatePackage = (index) => {
  // Get the original package
  const originalPackage = form.packages[index];
  
  // Create a new package with all data EXCEPT photos
  const packageCopy = {
    item_name: originalPackage.item_name,
    description: originalPackage.description,
    category: originalPackage.category,
    length: originalPackage.length,
    height: originalPackage.height,
    width: originalPackage.width,
    weight: originalPackage.weight,
    value: originalPackage.value,
    quantity: originalPackage.quantity,
    preset: originalPackage.preset,
    // Don't copy photos and photo_urls - start fresh
    photos: [],
    photo_urls: []
  };
  
  // Insert the duplicated package after the original
  form.packages.splice(index + 1, 0, packageCopy);
  
  console.log('📦 Package duplicated (photos not copied)');
};

const removePackage = (index) => {
  if (form.packages.length > 1) {
    if (form.packages[index].photo_urls) {
      form.packages[index].photo_urls.forEach(url => {
        URL.revokeObjectURL(url);
      });
    }
    form.packages.splice(index, 1);
  }
};

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
  
  form.packages.forEach(pkg => {
    if (pkg.photo_urls) {
      pkg.photo_urls.forEach(url => URL.revokeObjectURL(url));
    }
  });
  
  form.reset();
  form.packages = [{
    item_name: '',
    description: '',
    category: '',
    length: '',
    height: '',
    width: '',
    weight: '',
    value: '',
    quantity: 1,
    photos: [],
    photo_urls: [],
    preset: ''
  }];
  form.priceBreakdown = null;
  form.total_price = 0;
  isUsingMyInfo.value = false;
};

const expandPackagesByQuantity = () => {
  const expandedPackages = [];
  
  form.packages.forEach(pkg => {
    const quantity = parseInt(pkg.quantity) || 1;
    
    for (let i = 0; i < quantity; i++) {
      const packageCopy = { ...pkg };
      delete packageCopy.quantity;
      delete packageCopy.photo_urls;
      
      expandedPackages.push(packageCopy);
    }
  });
  
  return expandedPackages;
};

const uploadPhotosSequentially = async (files, packageIndex) => {
  const uploadedPaths = [];
  
  for (const file of files) {
    try {
      const formData = new FormData();
      formData.append('photos[]', file);
      
      const response = await axios.post(
        route('customer.delivery-requests.upload-photos'), 
        formData,
        {
          headers: {
            'Content-Type': 'multipart/form-data',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest'
          },
          timeout: 30000 // 30 second timeout
        }
      );
      
      if (response.data.photo_paths && response.data.photo_paths.length > 0) {
        uploadedPaths.push(...response.data.photo_paths);
        console.log(`✅ Uploaded: ${file.name} (${formatFileSize(file.size)})`);
      }
    } catch (error) {
      console.error(`❌ Failed to upload ${file.name}:`, error);
      throw new Error(`Failed to upload ${file.name}: ${error.message}`);
    }
  }
  
  return uploadedPaths;
};

const submitRequest = async () => {
    console.log('🔍 SUBMIT REQUEST STARTED - currentStep:', currentStep.value);
    
    if (!validateStep()) {
        console.log('❌ Validation failed - form errors:', form.errors);
        return;
    }
    
    console.log('✅ Validation passed');
    isLoading.value = true;

    const formData = new window.FormData();

    // Add CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    console.log('🔐 CSRF Token:', csrfToken ? 'Found' : 'NOT FOUND');

    // Add form data
    Object.entries(form.sender).forEach(([key, value]) => {
        formData.append(`sender[${key}]`, value ?? '');
    });

    Object.entries(form.receiver).forEach(([key, value]) => {
        formData.append(`receiver[${key}]`, value ?? '');
    });

    formData.append('pick_up_region_id', form.pick_up_region_id);
    formData.append('drop_off_region_id', form.drop_off_region_id);

    formData.append('payment_type', form.payment_type || 'prepaid');
    formData.append('payment_method', form.payment_method || '');
    formData.append('payment_terms', form.payment_terms || '');

    // Add downpayment data
    formData.append('downpayment_method', form.downpayment_method);
    formData.append('downpayment_reference', form.downpayment_reference);

    if (form.priceBreakdown) {
        formData.append('total_price', form.total_price);
        formData.append('base_fee', form.priceBreakdown.base_fee);
        formData.append('volume_fee', form.priceBreakdown.volume_fee);
        formData.append('weight_fee', form.priceBreakdown.weight_fee);
        formData.append('package_fee', form.priceBreakdown.package_fee);
        formData.append('price_breakdown', JSON.stringify(form.priceBreakdown));
    }

    try {
        // Upload downpayment receipt first
        let downpaymentReceiptPath = null;
        if (form.downpayment_receipt instanceof File) {
            console.log('📄 Uploading downpayment receipt...');
            
            const receiptFormData = new FormData();
            receiptFormData.append('receipt', form.downpayment_receipt);
            
            const receiptResponse = await axios.post(
                route('customer.delivery-requests.upload-downpayment-receipt'), 
                receiptFormData, 
                {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    timeout: 30000
                }
            );
            
            if (receiptResponse.data.success) {
                downpaymentReceiptPath = receiptResponse.data.file_path;
                formData.append('downpayment_receipt', downpaymentReceiptPath);
                console.log('✅ Downpayment receipt uploaded:', downpaymentReceiptPath);
            } else {
                throw new Error('Failed to upload downpayment receipt');
            }
        } else if (typeof form.downpayment_receipt === 'string') {
            // If it's already a base64 string (from direct conversion)
            formData.append('downpayment_receipt', form.downpayment_receipt);
            console.log('✅ Using existing downpayment receipt data');
        } else {
            throw new Error('Downpayment receipt is required');
        }

        const uploadedPhotoPaths = {};

        // Sequential photo upload for each package
        for (let originalIndex = 0; originalIndex < form.packages.length; originalIndex++) {
            const originalPkg = form.packages[originalIndex];
            
            if (originalPkg.photos && originalPkg.photos.length > 0) {
                console.log(`📸 Uploading ${originalPkg.photos.length} photos for package ${originalIndex}`);
                
                // Use sequential upload
                uploadedPhotoPaths[originalIndex] = await uploadPhotosSequentially(originalPkg.photos, originalIndex);
                
                console.log(`✅ Successfully uploaded ${uploadedPhotoPaths[originalIndex].length} photos for package ${originalIndex}`);
            } else {
                uploadedPhotoPaths[originalIndex] = [];
                console.log(`ℹ️ No photos to upload for package ${originalIndex}`);
            }
        }

        // Expand packages and add to formData
        const expandedPackages = [];
        
        form.packages.forEach((originalPkg, originalIndex) => {
            const quantity = parseInt(originalPkg.quantity) || 1;
            const photoPaths = uploadedPhotoPaths[originalIndex] || [];
            
            for (let i = 0; i < quantity; i++) {
                const packageCopy = {
                    item_name: originalPkg.item_name,
                    category: originalPkg.category,
                    description: originalPkg.description || '',
                    value: originalPkg.value || 0,
                    height: originalPkg.height,
                    width: originalPkg.width,
                    length: originalPkg.length,
                    weight: originalPkg.weight,
                    preset: originalPkg.preset || '',
                    photo_path: [...photoPaths]
                };
                
                expandedPackages.push(packageCopy);
            }
        });

        expandedPackages.forEach((pkg, index) => {
            formData.append(`packages[${index}][item_name]`, pkg.item_name);
            formData.append(`packages[${index}][category]`, pkg.category);
            formData.append(`packages[${index}][description]`, pkg.description || '');
            formData.append(`packages[${index}][value]`, pkg.value || 0);
            formData.append(`packages[${index}][height]`, pkg.height);
            formData.append(`packages[${index}][width]`, pkg.width);
            formData.append(`packages[${index}][length]`, pkg.length);
            formData.append(`packages[${index}][weight]`, pkg.weight);
            formData.append(`packages[${index}][preset]`, pkg.preset || '');
            
            if (pkg.photo_path && pkg.photo_path.length > 0) {
                pkg.photo_path.forEach((photoPath, pathIndex) => {
                    formData.append(`packages[${index}][photo_path][${pathIndex}]`, photoPath);
                });
            }
        });

        console.log('🚀 Making final submission with CSRF token...');
        console.log('📦 Packages to submit:', expandedPackages.length);
        console.log('💰 Downpayment method:', form.downpayment_method);
        console.log('🔢 Downpayment reference:', form.downpayment_reference);
        console.log('💳 Payment type:', form.payment_type);
        console.log('💸 Payment method:', form.payment_method);
        
        // Final submission
        const response = await axios.post(route('customer.delivery-requests.store'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            timeout: 60000 // 60 second timeout for large uploads
        });

        console.log('✅ Success response:', response.data);
        deliveryRequestId.value = response.data.delivery_request_id;
        showSuccessScreen.value = true;
        isLoading.value = false;

        // Clean up object URLs to prevent memory leaks
        form.packages.forEach(pkg => {
            if (pkg.photo_urls) {
                pkg.photo_urls.forEach(url => {
                    URL.revokeObjectURL(url);
                });
            }
        });

        if (form.downpayment_receipt_url) {
            URL.revokeObjectURL(form.downpayment_receipt_url);
        }

    } catch (error) {
        console.error('❌ Error response:', error.response?.data || error.message);
        isLoading.value = false;
        
        // Handle validation errors
        if (error.response?.data?.errors) {
            form.errors = error.response.data.errors;
            console.log('📋 Form errors:', form.errors);
        } else if (error.code === 'NETWORK_ERROR' || error.code === 'ECONNABORTED') {
            form.setError('submit', 'Network error. Please check your connection and try again.');
        } else {
            // Show generic error for upload issues
            const errorMessage = error.response?.data?.message || error.message || 'Failed to submit request. Please try again.';
            form.setError('submit', errorMessage);
        }
        
        // Scroll to top to show errors
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
};

const generateRequestId = () => {
  return 'DR-' + Date.now().toString().slice(-6);
};

const createSimilarDelivery = () => {
  // Keep sender/receiver info, reset packages
  form.packages = [{
    item_name: '',
    description: '',
    category: '',
    length: '',
    height: '',
    width: '',
    weight: '',
    value: '',
    quantity: 1,
    photos: [],
    photo_urls: [],
    preset: ''
  }];
  form.priceBreakdown = null;
  form.total_price = 0;
  showSuccessScreen.value = false;
  currentStep.value = 3; // Go back to package step
};

onMounted(() => {
  fetchRegions();
  fetchPriceMatrix();
  fetchPackageCategories();
  fetchPastReceivers(); // Add this line
});


onUnmounted(() => {
  // Clean up all object URLs to prevent memory leaks
  form.packages.forEach(pkg => {
    if (pkg.photo_urls) {
      pkg.photo_urls.forEach(url => {
        URL.revokeObjectURL(url);
      });
    }
  });
});
watch(() => form.pick_up_region_id, (newRegionId) => {
  if (newRegionId && regionsDataMap.value[newRegionId]) {
    selectedRegionInfo.value = regionsDataMap.value[newRegionId];
  } else {
    selectedRegionInfo.value = null;
  }
});

watch(() => form.drop_off_region_id, (newRegionId) => {
  if (newRegionId && regionsDataMap.value[newRegionId]) {
    selectedDropoffRegionInfo.value = regionsDataMap.value[newRegionId];
  } else {
    selectedDropoffRegionInfo.value = null;
  }
});

watch(() => [
  form.pick_up_region_id,
  form.drop_off_region_id,
  ...form.packages.map(pkg => [
    pkg.height, pkg.width, pkg.length, pkg.weight, pkg.quantity
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

watch(() => form.payment_type, (val) => {
  form.payment_terms = '';
  form.payment_method = '';

  if (val === 'postpaid' && !isPostpaidEligible.value) {
    form.payment_type = 'prepaid';
    form.setError('payment_type', 'Postpaid is only available after 3 completed deliveries.');
  }
  if (val === 'postpaid') {
    form.payment_method = 'postpaid';
  }
});
</script>

<template>
  <GuestLayout>
    <div class="min-h-screen bg-gray-50 py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Success Message -->
        <div v-if="$page.props.flash.success" class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
          {{ $page.props.flash.success }}
        </div>

        <!-- Header -->
        <div class="text-center mb-8">
          <h1 class="text-3xl font-bold text-gray-900">Request Delivery</h1>
          <p class="text-gray-600 mt-2">Fill out the form below to schedule your delivery</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
          <!-- Main Form Content -->
          <div :class="['lg:col-span-4', currentStep === 3 ? 'lg:col-span-4' : 'lg:col-span-4']">
            <!-- Progress Steps -->
            <div class="mb-8">
              <div class="flex items-center justify-between">
                <div v-for="step in 4" :key="step" class="flex items-center">
                  <div
                    :class="[
                      'w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium',
                      currentStep > step
                        ? 'bg-green-600 text-white'
                        : currentStep === step
                        ? 'bg-green-500 text-white ring-4 ring-green-100'
                        : 'bg-gray-300 text-gray-600'
                    ]"
                  >
                    {{ step }}
                  </div>
                  <div
                    v-if="step < 4"
                    :class="[
                      'flex-1 h-1 mx-2',
                      currentStep > step ? 'bg-green-600' : 'bg-gray-300'
                    ]"
                  ></div>
                </div>
              </div>
              <div class="flex justify-between mt-2 text-sm text-gray-600">
                <span :class="{ 'text-green-600 font-medium': currentStep >= 1 }">Sender</span>
                <span :class="{ 'text-green-600 font-medium': currentStep >= 2 }">Receiver</span>
                <span :class="{ 'text-green-600 font-medium': currentStep >= 3 }">Package</span>
                <span :class="{ 'text-green-600 font-medium': currentStep >= 4 }">Payment</span>
              </div>
            </div>

            <!-- Step Information - Below Progress Bar -->
            <div class="mb-8">
              <!-- Step 1: Sender Information Notice -->
              <div v-if="currentStep === 1" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-800 flex items-center">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  Sender Information
                </h3>
                <p class="text-sm text-blue-700 mt-1">
                  Your sender information is pulled from your profile. To update your details, please visit your 
                  <a :href="route('customer.profile.update')" class="text-blue-800 font-semibold underline hover:text-blue-600 transition-colors">
                    Delivery Information Page
                  </a>. This ensures all profile modifications are properly verified and recorded.
                </p>
              </div>

              <!-- Step 2: Receiver Information Notice -->
              <div v-if="currentStep === 2" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-800 flex items-center">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                  </svg>
                  Receiver Information
                </h3>
                <p class="text-sm text-blue-700 mt-1">
                  Accurate receiver information is crucial for successful delivery. Please double-check all contact details and address information.
                </p>
              </div>

              <!-- Step 3: Package Information Notice -->
              <div v-if="currentStep === 3" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-800 flex items-center">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                  </svg>
                  Package Information
                </h3>
                <p class="text-sm text-blue-700 mt-1">
                  Provide accurate package details including dimensions, weight, and clear photos for proper documentation and pricing.
                </p>
              </div>
              
              <!-- Step 4: Payment Information Notice -->
              <div v-if="currentStep === 4 && !showSuccessScreen" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="font-semibold text-blue-800 flex items-center">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  Payment Information
                </h3>
                <p class="text-sm text-blue-700 mt-1">
                  Review your delivery summary and select your preferred payment method. All prices are final and include applicable fees.
                </p>
              </div>
            </div>

            <!-- Form Steps -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">              
              <!-- Step 1: Sender Information -->
              <div v-if="currentStep === 1" class="space-y-6">
                <div class="flex items-center justify-between">
                  <h2 class="text-xl font-semibold text-gray-900">Sender Information</h2>
                  <div class="text-sm text-gray-500">
                    Your information is pre-filled from your profile
                  </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                  <!-- Left Column - Form Fields -->
                  <div class="lg:col-span-2 space-y-6">
                    <!-- Customer Category -->
                    <div>
                      <InputLabel value="Customer Category *" />
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                        <label
                          :class="[
                            'border rounded-lg p-4 cursor-pointer transition-all duration-200 bg-gray-100',
                            form.sender.customer_category === 'individual'
                              ? 'border-green-500 bg-green-50 ring-2 ring-green-200'
                              : 'border-gray-300'
                          ]"
                        >
                          <div class="flex items-center">
                            <input
                              type="radio"
                              v-model="form.sender.customer_category"
                              value="individual"
                              class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300"
                              disabled
                            />
                            <span class="ml-3 text-sm font-medium text-gray-900">Individual</span>
                          </div>
                        </label>
                        <label
                          :class="[
                            'border rounded-lg p-4 cursor-pointer transition-all duration-200 bg-gray-100',
                            form.sender.customer_category === 'company'
                              ? 'border-green-500 bg-green-50 ring-2 ring-green-200'
                              : 'border-gray-300'
                          ]"
                        >
                          <div class="flex items-center">
                            <input
                              type="radio"
                              v-model="form.sender.customer_category"
                              value="company"
                              class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300"
                              disabled
                            />
                            <span class="ml-3 text-sm font-medium text-gray-900">Company</span>
                          </div>
                        </label>
                      </div>
                      <InputError :message="form.errors['sender.customer_category']" />
                    </div>

                    <!-- Name Fields -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                      <div>
                        <InputLabel value="First Name *" />
                        <TextInput
                          v-model="form.sender.first_name"
                          type="text"
                          class="mt-1 block w-full bg-gray-100"
                          :class="{ 'border-red-500': form.errors['sender.first_name'] }"
                          readonly
                          @input="(e) => handleNameInput(e, 'sender.first_name')"
                        />
                        <InputError :message="form.errors['sender.first_name']" />
                      </div>
                      
                      <div>
                        <InputLabel value="Middle Name" />
                        <TextInput
                          v-model="form.sender.middle_name"
                          type="text"
                          class="mt-1 block w-full bg-gray-100"
                          readonly
                          @input="(e) => handleNameInput(e, 'sender.middle_name')"
                        />
                        <InputError :message="form.errors['sender.middle_name']" />
                      </div>
                      
                      <div>
                        <InputLabel value="Last Name *" />
                        <TextInput
                          v-model="form.sender.last_name"
                          type="text"
                          class="mt-1 block w-full bg-gray-100"
                          :class="{ 'border-red-500': form.errors['sender.last_name'] }"
                          readonly
                          @input="(e) => handleNameInput(e, 'sender.last_name')"
                        />
                        <InputError :message="form.errors['sender.last_name']" />
                      </div>
                    </div>

                    <!-- Company Name (Conditional) -->
                    <div v-if="form.sender.customer_category === 'company'" class="space-y-2">
                      <InputLabel value="Company Name *" />
                      <TextInput
                        v-model="form.sender.company_name"
                        type="text"
                        class="mt-1 block w-full bg-gray-100"
                        :class="{ 'border-red-500': form.errors['sender.company_name'] }"
                        readonly
                        @input="(e) => handleTextInput(e, 'sender.company_name')"
                      />
                      <InputError :message="form.errors['sender.company_name']" />
                    </div>

                    <!-- Contact Information -->
                    <div class="space-y-6">
                      <!-- Email Address - Full Width -->
                      <div>
                        <InputLabel value="Email Address *" />
                        <TextInput
                          v-model="form.sender.email"
                          type="email"
                          class="mt-1 block w-full bg-gray-100"
                          :class="{ 'border-red-500': form.errors['sender.email'] }"
                          readonly
                        />
                        <InputError :message="form.errors['sender.email']" />
                      </div>

                      <!-- Mobile and Phone - Side by Side -->
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                          <InputLabel value="Mobile Number *" />
                          <TextInput
                            v-model="form.sender.mobile"
                            type="tel"
                            class="mt-1 block w-full bg-gray-100"
                            :class="{ 'border-red-500': form.errors['sender.mobile'] }"
                            readonly
                            @input="(e) => handleNumberInput(e, 'sender.mobile')"
                            maxlength="11"
                          />
                          <InputError :message="form.errors['sender.mobile']" />
                        </div>

                        <div>
                          <InputLabel value="Phone Number (Optional)" />
                          <TextInput
                            v-model="form.sender.phone"
                            type="tel"
                            class="mt-1 block w-full bg-gray-100"
                            :class="{ 'border-red-500': form.errors['sender.phone'] }"
                            readonly
                            @input="(e) => handleNumberInput(e, 'sender.phone')"
                            maxlength="15"
                          />
                          <InputError :message="form.errors['sender.phone']" />
                        </div>
                      </div>
                    </div>

                    <!-- Address Information -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                      <div>
                        <InputLabel value="Building Number" />
                        <TextInput
                          v-model="form.sender.building_number"
                          type="text"
                          class="mt-1 block w-full bg-gray-100"
                          readonly
                          @input="(e) => handleAddressInput(e, 'sender.building_number')"
                        />
                        <InputError :message="form.errors['sender.building_number']" />
                      </div>

                    <div>
                        <InputLabel value="Street *" />
                        <TextInput
                          v-model="form.sender.street"
                          type="text"
                          class="mt-1 block w-full bg-gray-100"
                          :class="{ 'border-red-500': form.errors['sender.street'] }"
                          readonly
                          @input="(e) => handleAddressInput(e, 'sender.street')"
                        />
                        <InputError :message="form.errors['sender.street']" />
                      </div>

                      <div>
                        <InputLabel value="Barangay" />
                        <TextInput
                          v-model="form.sender.barangay"
                          type="text"
                          class="mt-1 block w-full bg-gray-100"
                          readonly
                          @input="(e) => handleAddressInput(e, 'sender.barangay')"
                        />
                        <InputError :message="form.errors['sender.barangay']" />
                      </div>

                      <div>
                        <InputLabel value="City *" />
                        <TextInput
                          v-model="form.sender.city"
                          type="text"
                          class="mt-1 block w-full bg-gray-100"
                          :class="{ 'border-red-500': form.errors['sender.city'] }"
                          readonly
                          @input="(e) => handleAddressInput(e, 'sender.city')"
                        />
                        <InputError :message="form.errors['sender.city']" />
                      </div>

                      <div>
                        <InputLabel value="Province *" />
                        <TextInput
                          v-model="form.sender.province"
                          type="text"
                          class="mt-1 block w-full bg-gray-100"
                          :class="{ 'border-red-500': form.errors['sender.province'] }"
                          readonly
                          @input="(e) => handleAddressInput(e, 'sender.province')"
                        />
                        <InputError :message="form.errors['sender.province']" />
                      </div>

                      <div>
                        <InputLabel value="ZIP Code *" />
                        <TextInput
                          v-model="form.sender.zip_code"
                          type="text"
                          class="mt-1 block w-full bg-gray-100"
                          :class="{ 'border-red-500': form.errors['sender.zip_code'] }"
                          maxlength="4"
                          readonly
                          @input="(e) => handleNumberInput(e, 'sender.zip_code')"
                        />
                        <InputError :message="form.errors['sender.zip_code']" />
                      </div>
                    </div>
                  </div>

                <!-- Right Column - Pick-up Region & Map -->
          <div class="lg:col-span-1 space-y-6">
            <!-- Pick-up Region -->
            <div>
              <InputLabel value="Select Pick-up Region *" />
              <SelectInput
                v-model="form.pick_up_region_id"
                class="mt-1 block w-full"
                :class="{ 'border-red-500': form.errors.pick_up_region_id }"
                :options="PICKUP_REGIONS"
                placeholder=""
              />
              <InputError :message="form.errors.pick_up_region_id" />
            </div>

                    <!-- Region Information Display -->
            <div v-if="selectedRegionInfo" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <h3 class="font-semibold text-blue-800 mb-3 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                {{ selectedRegionInfo.name || 'Branch' }} Information
              </h3>
              <div class="space-y-2">
                <p class="text-sm text-blue-700">
                  <strong class="text-blue-800">Address:</strong><br>
                  {{ selectedRegionInfo.warehouse_address || 'Address information not available' }}
                </p>
                <p class="text-sm text-blue-700" v-if="selectedRegionInfo.latitude && selectedRegionInfo.longitude">
                  <strong class="text-blue-800">Coordinates:</strong><br>
                  {{ selectedRegionInfo.latitude }}, {{ selectedRegionInfo.longitude }}
                </p>
                <p class="text-sm text-blue-700" v-else>
                  <strong class="text-blue-800">Coordinates:</strong><br>
                  <span class="text-orange-600">Coordinates not available - please contact administrator</span>
                </p>
                <div class="pt-2">
                  <p class="text-xs text-blue-600">
                    <strong>Note:</strong> This is your selected pick-up location. 
                    Please bring your packages to this branch address.
                  </p>
                </div>
              </div>
              <div class="mt-4">
                <LeafletMap
                  v-if="selectedRegionInfo.latitude && selectedRegionInfo.longitude"
                  :latitude="selectedRegionInfo.latitude"
                  :longitude="selectedRegionInfo.longitude"
                  :region-name="selectedRegionInfo.name"
                  :zoom="15"
                  height="200px"
                />
                <div v-else class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 h-48 flex items-center justify-center">
                  <div class="text-center">
                    <svg class="w-8 h-8 mx-auto mb-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p class="text-yellow-700 font-medium">Map Unavailable</p>
                    <p class="text-yellow-600 text-sm">Location coordinates not configured</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

                <!-- Navigation -->
                <div class="flex justify-end pt-6">
                  <PrimaryButton @click="nextStep" class="px-6">
                    Continue to Receiver
                  </PrimaryButton>
                </div>
              </div>

          <!-- Step 2: Receiver Information -->
<div v-if="currentStep === 2" class="space-y-6">
  <div class="flex items-center justify-between">
    <h2 class="text-xl font-semibold text-gray-900">Receiver Information</h2>
    <div class="text-sm text-gray-500">
      Enter receiver details
    </div>
  </div>

  <!-- Past Receivers Dropdown -->
  <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
    <div class="flex items-center justify-between">
      <h3 class="font-semibold text-blue-800 flex items-center text-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
        Quick Select from Past Receivers
      </h3>
      <button 
        @click="fetchPastReceivers" 
        type="button"
        class="text-blue-600 hover:text-blue-700 text-sm font-medium"
      >
        Refresh
      </button>
    </div>
    <div class="mt-2">
      <select
        v-model="selectedReceiver"
        @change="handleReceiverSelect(selectedReceiver)"
        class="block w-full rounded-md border border-blue-300 bg-white py-2 px-3 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm"
      >
        <option value="">Select a past receiver...</option>
        <option 
          v-for="receiver in pastReceivers" 
          :key="receiver.id" 
          :value="receiver.id"
          class="text-sm"
        >
          {{ receiver.display_name }} - {{ receiver.email }} ({{ receiver.created_at }})
        </option>
      </select>
      <p class="text-xs text-blue-600 mt-1">
        Select a receiver from your past delivery requests to prefill their details
      </p>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Left Column - Form Fields -->
    <div class="lg:col-span-2 space-y-6">
      <!-- Customer Category -->
      <div>
        <InputLabel value="Customer Category *" />
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
          <label
            v-for="option in customerCategoryOptions"
            :key="option.value"
            :class="[
              'border rounded-lg p-4 cursor-pointer transition-all duration-200',
              form.receiver.customer_category === option.value
                ? 'border-green-500 bg-green-50 ring-2 ring-green-200'
                : 'border-gray-300 hover:border-gray-400'
            ]"
          >
            <div class="flex items-center">
              <input
                type="radio"
                v-model="form.receiver.customer_category"
                :value="option.value"
                class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300"
              />
              <span class="ml-3 text-sm font-medium text-gray-900">{{ option.label }}</span>
            </div>
          </label>
        </div>
        <InputError :message="form.errors['receiver.customer_category']" />
      </div>

      <!-- Name Fields -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <InputLabel value="First Name *" />
          <TextInput
            v-model="form.receiver.first_name"
            type="text"
            class="mt-1 block w-full"
            :class="{ 'border-red-500': form.errors['receiver.first_name'] }"
            placeholder="Enter first name"
            @input="(e) => handleNameInputWithCapitalize(e, 'receiver.first_name')"
          />
          <InputError :message="form.errors['receiver.first_name']" />
        </div>
        
        <div>
          <InputLabel value="Middle Name" />
          <TextInput
            v-model="form.receiver.middle_name"
            type="text"
            class="mt-1 block w-full"
            placeholder="Enter middle name"
            @input="(e) => handleNameInputWithCapitalize(e, 'receiver.middle_name')"
          />
          <InputError :message="form.errors['receiver.middle_name']" />
        </div>
        
        <div>
          <InputLabel value="Last Name *" />
          <TextInput
            v-model="form.receiver.last_name"
            type="text"
            class="mt-1 block w-full"
            :class="{ 'border-red-500': form.errors['receiver.last_name'] }"
            placeholder="Enter last name"
            @input="(e) => handleNameInputWithCapitalize(e, 'receiver.last_name')"
          />
          <InputError :message="form.errors['receiver.last_name']" />
        </div>
      </div>

      <!-- Company Name (Conditional) -->
      <div v-if="form.receiver.customer_category === 'company'" class="space-y-2">
        <InputLabel value="Company Name *" />
        <TextInput
          v-model="form.receiver.company_name"
          type="text"
          class="mt-1 block w-full"
          :class="{ 'border-red-500': form.errors['receiver.company_name'] }"
          placeholder="Enter company name"
          @input="(e) => handleTextInput(e, 'receiver.company_name')"
        />
        <InputError :message="form.errors['receiver.company_name']" />
      </div>

      <!-- Contact Information -->
      <div class="space-y-6">
        <!-- Email Address - Full Width -->
        <div>
          <InputLabel value="Email Address *" />
          <TextInput
            v-model="form.receiver.email"
            type="email"
            class="mt-1 block w-full"
            :class="{ 'border-red-500': form.errors['receiver.email'] }"
            placeholder="receiver.email@example.com"
          />
          <InputError :message="form.errors['receiver.email']" />
        </div>

        <!-- Mobile and Phone - Side by Side -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <InputLabel value="Mobile Number *" />
            <TextInput
              v-model="form.receiver.mobile"
              type="tel"
              class="mt-1 block w-full"
              :class="{ 'border-red-500': form.errors['receiver.mobile'] }"
              placeholder="09123456789"
              @input="(e) => handleNumberInput(e, 'receiver.mobile')"
              maxlength="11"
            />
            <InputError :message="form.errors['receiver.mobile']" />
          </div>

          <div>
            <InputLabel value="Phone Number (Optional)" />
            <TextInput
              v-model="form.receiver.phone"
              type="tel"
              class="mt-1 block w-full"
              :class="{ 'border-red-500': form.errors['receiver.phone'] }"
              placeholder="Enter landline number"
              @input="(e) => handleNumberInput(e, 'receiver.phone')"
              maxlength="15"
            />
            <InputError :message="form.errors['receiver.phone']" />
          </div>
        </div>
      </div>

      <!-- Address Information -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <InputLabel value="Building Number" />
          <TextInput
            v-model="form.receiver.building_number"
            type="text"
            class="mt-1 block w-full"
            placeholder="e.g., 123"
            @input="(e) => handleAddressInput(e, 'receiver.building_number')"
          />
          <InputError :message="form.errors['receiver.building_number']" />
        </div>

        <div>
          <InputLabel value="Street *" />
          <TextInput
            v-model="form.receiver.street"
            type="text"
            class="mt-1 block w-full"
            :class="{ 'border-red-500': form.errors['receiver.street'] }"
            placeholder="e.g., Main Street"
            @input="(e) => handleAddressInputWithCapitalize(e, 'receiver.street')"
          />
          <InputError :message="form.errors['receiver.street']" />
        </div>

        <div>
          <InputLabel value="Barangay" />
          <TextInput
            v-model="form.receiver.barangay"
            type="text"
            class="mt-1 block w-full"
            placeholder="Enter barangay"
            @input="(e) => handleAddressInputWithCapitalize(e, 'receiver.barangay')"
          />
          <InputError :message="form.errors['receiver.barangay']" />
        </div>

        <div>
          <InputLabel value="City *" />
          <TextInput
            v-model="form.receiver.city"
            type="text"
            class="mt-1 block w-full"
            :class="{ 'border-red-500': form.errors['receiver.city'] }"
            placeholder="Enter city"
            @input="(e) => handleAddressInputWithCapitalize(e, 'receiver.city')"
          />
          <InputError :message="form.errors['receiver.city']" />
        </div>

        <div>
          <InputLabel value="Province *" />
          <TextInput
            v-model="form.receiver.province"
            type="text"
            class="mt-1 block w-full"
            :class="{ 'border-red-500': form.errors['receiver.province'] }"
            placeholder="Enter province"
            @input="(e) => handleAddressInputWithCapitalize(e, 'receiver.province')"
          />
          <InputError :message="form.errors['receiver.province']" />
        </div>

        <div>
          <InputLabel value="ZIP Code *" />
          <TextInput
            v-model="form.receiver.zip_code"
            type="text"
            class="mt-1 block w-full"
            :class="{ 'border-red-500': form.errors['receiver.zip_code'] }"
            placeholder="e.g., 1000"
            maxlength="4"
            @input="(e) => handleNumberInput(e, 'receiver.zip_code')"
          />
          <InputError :message="form.errors['receiver.zip_code']" />
        </div>
      </div>
    </div>

    <!-- Right Column - Drop-off Region & Map -->
    <div class="lg:col-span-1 space-y-6">
      <!-- Drop-off Region -->
      <div>
        <InputLabel value="Select Drop-off Region *" />
        <SelectInput
          v-model="form.drop_off_region_id"
          class="mt-1 block w-full"
          :class="{ 'border-red-500': form.errors.drop_off_region_id }"
          :options="filteredDropoffRegions"
          placeholder=""
        />
        <InputError :message="form.errors.drop_off_region_id" />
      </div>
      
      <!-- Drop-off Region Information Display -->
      <div v-if="selectedDropoffRegionInfo" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 class="font-semibold text-blue-800 mb-3 flex items-center">
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          {{ selectedDropoffRegionInfo.name || 'Branch' }} Information
        </h3>
        <div class="space-y-2">
          <p class="text-sm text-blue-700">
            <strong class="text-blue-800">Address:</strong><br>
            {{ selectedDropoffRegionInfo.warehouse_address || 'Address information not available' }}
          </p>
          <p class="text-sm text-blue-700" v-if="selectedDropoffRegionInfo.latitude && selectedDropoffRegionInfo.longitude">
            <strong class="text-blue-800">Coordinates:</strong><br>
            {{ selectedDropoffRegionInfo.latitude }}, {{ selectedDropoffRegionInfo.longitude }}
          </p>
          <p class="text-sm text-blue-700" v-else>
            <strong class="text-blue-800">Coordinates:</strong><br>
            <span class="text-orange-600">Coordinates not available - please contact administrator</span>
          </p>
          <div class="pt-2">
            <p class="text-xs text-blue-600">
              <strong>Note:</strong> This is the selected drop-off location for your packages.
            </p>
          </div>
        </div>
        <div class="mt-4">
          <LeafletMap
            v-if="selectedDropoffRegionInfo.latitude && selectedDropoffRegionInfo.longitude"
            :latitude="selectedDropoffRegionInfo.latitude"
            :longitude="selectedDropoffRegionInfo.longitude"
            :region-name="selectedDropoffRegionInfo.name"
            :zoom="15"
            height="200px"
          />
          <div v-else class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 h-48 flex items-center justify-center">
            <div class="text-center">
              <svg class="w-8 h-8 mx-auto mb-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
              </svg>
              <p class="text-yellow-700 font-medium">Map Unavailable</p>
              <p class="text-yellow-600 text-sm">Location coordinates not configured</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Navigation -->
  <div class="flex justify-between pt-6">
    <SecondaryButton @click="prevStep" class="px-6">
      Back
    </SecondaryButton>
    <PrimaryButton @click="nextStep" class="px-6">
      Continue to Package
    </PrimaryButton>
  </div>
</div>

             <!-- Step 3: Package Information -->
<div v-if="currentStep === 3" class="space-y-6">
  <div class="flex items-center justify-between">
    <h2 class="text-xl font-semibold text-gray-900">Package Information</h2>
    <SecondaryButton @click="addPackage" class="text-sm">
      + Add Package
    </SecondaryButton>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
    <!-- Left Column - Package Notices (Individually Collapsible) -->
    <div class="lg:col-span-1 space-y-6">
      <!-- Advisory Notice -->
      <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex items-center justify-between cursor-pointer" @click="showAdvisoryNotice = !showAdvisoryNotice">
          <h3 class="font-semibold text-yellow-800 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            Advisory Notice
          </h3>
          <svg :class="['w-4 h-4 transition-transform', showAdvisoryNotice ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </div>
        <div v-if="showAdvisoryNotice" class="text-sm text-yellow-700 mt-2">
          <p>
            We only carry individual packages up to 10 cubic meters in volume (calculated as Length × Height × Width in cm ÷ 1,000,000) 
            and 100 kg in weight. Items exceeding these limits require special freight arrangements. 
            Please ensure your items meet these requirements before booking.
          </p>
        </div>
      </div>

      <!-- Photo Requirements -->
<div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
  <div class="flex items-center justify-between cursor-pointer" @click="showPhotoRequirements = !showPhotoRequirements">
    <h3 class="font-semibold text-blue-800 flex items-center">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      Photo Requirements
    </h3>
    <svg :class="['w-4 h-4 transition-transform', showPhotoRequirements ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
    </svg>
  </div>
  <div v-if="showPhotoRequirements" class="text-sm text-blue-700 mt-2 space-y-2">
    <p><strong>Package photos are mandatory for documentation and verification purposes.</strong></p>
    
    <div class="ml-4 space-y-1">
      <p>• <strong>All Packages:</strong> Minimum 6 images required regardless of quantity</p>
      <p>• <strong>Single Package (quantity = 1):</strong> Front, back, left, right, top, bottom views, plus weighing scale</p>
      <p>• <strong>Multiple Packages (quantity > 1):</strong> Must include 1 group photo showing all packages together, 1 weighing scale photo, plus 4 detailed photos</p>
      <p>• <strong>File Size:</strong> Maximum 5MB per image (automatically compressed if larger)</p>
      <p>• <strong>Format:</strong> JPEG, PNG, GIF</p>
    </div>
    
    <p class="font-semibold mt-2">Ensure photos are clear, well-lit, and show all packages properly for documentation.</p>
  </div>
</div>

      <!-- Package Type Guide -->
      <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="flex items-center justify-between cursor-pointer" @click="showPackageGuide = !showPackageGuide">
          <h3 class="font-semibold text-green-800 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            Package Type Guide
          </h3>
          <svg :class="['w-4 h-4 transition-transform', showPackageGuide ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </div>
        <div v-if="showPackageGuide" class="text-sm text-green-700 mt-2 space-y-2">
          <p><strong>Select the appropriate package type for your items:</strong></p>
          
          <div class="space-y-1">
            <p>• <strong>Small Pouch:</strong> Documents, letters, compact tools</p>
            <p>• <strong>Medium Box:</strong> Books, shoes, small appliances</p>
            <p>• <strong>Large Box:</strong> Safety gear, toolkits, clothing</p>
            <p>• <strong>XL Box:</strong> Power tools, helmets, small furniture</p>
            <p>• <strong>Large Sack:</strong> Work gloves, fabric, plastic fittings</p>
            <p>• <strong>Standard Roll:</strong> Blueprints, rolls of wire</p>
            <p>• <strong>Bundle Roll:</strong> Metal pipes, PVC tubes, carpets</p>
            <p>• <strong>Custom Size:</strong> Generators, concrete molds, oversized items</p>
          </div>
        </div>
      </div>

      <!-- Quick Tips (Always visible) -->
      <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <h4 class="font-semibold text-gray-800 text-sm mb-2">Quick Tips</h4>
        <ul class="text-xs text-gray-700 space-y-1">
          <li>• Measure in centimeters</li>
          <li>• Use accurate weights</li>
          <li>• Take clear photos</li>
          <li>• Select appropriate package type</li>
        </ul>
      </div>
    </div>

    <!-- Right Column - Package Form (3/4 width) -->
    <div class="lg:col-span-3 space-y-6">
      <!-- Package List -->
      <div v-for="(pkg, index) in form.packages" :key="index" class="border border-gray-200 rounded-lg p-6 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-medium text-gray-900">Package {{ index + 1 }}</h3>
          <div class="flex space-x-2">
           
            <button
              v-if="form.packages.length > 1"
              @click="removePackage(index)"
              type="button"
              class="text-red-600 hover:text-red-700 text-sm font-medium"
            >
              Remove
            </button>
          </div>
        </div>

        <!-- Package Name and Description -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
  <InputLabel value="Package Name *" />
  <TextInput
    v-model="pkg.item_name"
    type="text"
    class="mt-1 block w-full"
    :class="{ 'border-red-500': form.errors[`packages.${index}.item_name`] }"
    placeholder="e.g., Documents, Electronics, Clothes"
    @input="(e) => handleTextInputWithCapitalize(e, 'item_name', index)"
  />
  <p class="text-xs text-gray-500 mt-1">Descriptive name for identification and tracking</p>
  <InputError :message="form.errors[`packages.${index}.item_name`]" />
</div>
          <div>
            <InputLabel value="Special Handling Instructions (Optional)" />
            <TextInput
              v-model="pkg.description"
              type="text"
              class="mt-1 block w-full"
              placeholder="For fragile items, perishable goods, etc."
              @input="(e) => handleTextInput(e, 'description', index)"
            />
            <p class="text-xs text-gray-500 mt-1">Special instructions or handling requirements</p>
            <InputError :message="form.errors[`packages.${index}.description`]" />
          </div>
        </div>

        <!-- Container Preset Selection with Images -->
        <div>
          <InputLabel value="Package Type *" />
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-2">
  <label
    v-for="preset in formattedPackageCategories"
    :key="preset.value"
    :class="[
      'border rounded-lg p-3 cursor-pointer transition-all duration-200 text-center',
      pkg.preset === preset.value // Compare with value (ID)
        ? 'border-green-500 bg-green-50 ring-2 ring-green-200'
        : 'border-gray-300 hover:border-gray-400'
    ]"
  >
    <input
      type="radio"
      v-model="pkg.preset"
      :value="preset.value" 
      @change="handlePresetChange(preset.value, index)"
      class="sr-only"
    />
    <div class="flex flex-col items-center">
      <div class="w-12 h-12 mb-2 flex items-center justify-center">
        <img
          v-if="preset.image"
          :src="preset.image"
          :alt="preset.label"
          class="max-w-full max-h-full object-contain"
        />
        <div v-else class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
          <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
          </svg>
        </div>
      </div>
      <div class="text-xs font-medium text-gray-900">{{ preset.label.split(' (')[0] }}</div>
      <div class="text-xs text-gray-500 mt-1">{{ preset.category }}</div>
    </div>
  </label>
          </div>
          <InputError :message="form.errors[`packages.${index}.preset`]" />
          <p class="text-xs text-gray-500 mt-2">Select the container type that matches your item</p>
        </div>

        <!-- Package Dimensions -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <InputLabel value="Length (cm) *" />
            <TextInput
              v-model="pkg.length"
              type="text"
              step="0.1"
              min="0.1"
              class="mt-1 block w-full"
              :class="[
                { 'border-red-500': form.errors[`packages.${index}.length`] },
                pkg.preset !== 'custom' ? 'bg-gray-100 text-gray-600' : ''
              ]"
              placeholder="0.0"
              :readonly="pkg.preset !== 'custom'"
              @input="(e) => handleNumberInput(e, 'length', index)"
              maxlength="6"
            />
            <p class="text-xs text-gray-500 mt-1">Longest side measurement in centimeters</p>
            <InputError :message="form.errors[`packages.${index}.length`]" />
            <p class="text-xs text-gray-500 mt-1" v-if="pkg.preset !== 'custom'">Preset dimension</p>
          </div>
          <div>
            <InputLabel value="Height (cm) *" />
            <TextInput
              v-model="pkg.height"
              type="text"
              step="0.1"
              min="0.1"
              class="mt-1 block w-full"
              :class="[
                { 'border-red-500': form.errors[`packages.${index}.height`] },
                pkg.preset !== 'custom' ? 'bg-gray-100 text-gray-600' : ''
              ]"
              placeholder="0.0"
              :readonly="pkg.preset !== 'custom'"
              @input="(e) => handleNumberInput(e, 'height', index)"
              maxlength="6"
            />
            <p class="text-xs text-gray-500 mt-1">Vertical measurement in centimeters</p>
            <InputError :message="form.errors[`packages.${index}.height`]" />
            <p class="text-xs text-gray-500 mt-1" v-if="pkg.preset !== 'custom'">Preset dimension</p>
          </div>
          <div>
            <InputLabel value="Width (cm) *" />
            <TextInput
              v-model="pkg.width"
              type="text"
              step="0.1"
              min="0.1"
              class="mt-1 block w-full"
              :class="[
                { 'border-red-500': form.errors[`packages.${index}.width`] },
                pkg.preset !== 'custom' ? 'bg-gray-100 text-gray-600' : ''
              ]"
              placeholder="0.0"
              :readonly="pkg.preset !== 'custom'"
              @input="(e) => handleNumberInput(e, 'width', index)"
              maxlength="6"
            />
            <p class="text-xs text-gray-500 mt-1">Side-to-side measurement in centimeters</p>
            <InputError :message="form.errors[`packages.${index}.width`]" />
            <p class="text-xs text-gray-500 mt-1" v-if="pkg.preset !== 'custom'">Preset dimension</p>
          </div>
          <div>
            <InputLabel value="Weight Range (kg) *" />
            <SelectInput
              v-model="pkg.weight"
              class="mt-1 block w-full"
              :class="{ 'border-red-500': form.errors[`packages.${index}.weight`] }"
              :options="weightRangeOptions"
              placeholder=""
            />
            <p class="text-xs text-gray-500 mt-1">Select weight range - charged at upper limit</p>
            <InputError :message="form.errors[`packages.${index}.weight`]" />
          </div>
        </div>

        <!-- Package Value and Quantity -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <InputLabel value="Package Value (₱) *" />
            <SelectInput
              v-model="pkg.value"
              class="mt-1 block w-full"
              :class="{ 'border-red-500': form.errors[`packages.${index}.value`] }"
              :options="packageValueOptions"
              placeholder=""
            />
            <p class="text-xs text-gray-500 mt-1">Select the estimated value range for insurance purposes</p>
            <InputError :message="form.errors[`packages.${index}.value`]" />
          </div>
          <div>
            <InputLabel value="Quantity *" />
            <TextInput
              v-model="pkg.quantity"
              type="text"
              min="1"
              max="100"
              class="mt-1 block w-full"
              :class="{ 'border-red-500': form.errors[`packages.${index}.quantity`] }"
              placeholder="1"
              @input="(e) => handleNumberInput(e, 'quantity', index)"
              maxlength="3"
            />
            <p class="text-xs text-gray-500 mt-1">Number of identical packages</p>
            <InputError :message="form.errors[`packages.${index}.quantity`]" />
          </div>
        </div>

        <!-- Photo Upload -->
       <div>
  <InputLabel 
    value="Package Photos * (Minimum 6 photos required for all packages)" 
  />
  <div class="mt-2">
    <input
      type="file"
      @change="handlePhotoUpload($event, index)"
      multiple
      accept="image/*"
      class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100"
    />
    <p class="text-xs text-gray-500 mt-1">
      Required: Minimum 6 photos for all packages. 
      <span v-if="pkg.quantity > 1" class="font-semibold">Must include group photo and weighing scale.</span>
      <span v-else class="font-semibold">Must include all sides and weighing scale.</span>
      Max 5MB each, will be automatically compressed.
    </p>
    <InputError :message="form.errors[`packages.${index}.photos`]" />
    
    <!-- File Size Warning -->
    <div v-if="showSizeWarning[index]" class="mt-2 bg-yellow-50 border border-yellow-200 rounded-lg p-3">
      <div class="flex items-center">
        <svg class="w-4 h-4 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <p class="text-sm text-yellow-700">
          Large images detected. They will be compressed automatically for faster upload.
        </p>
      </div>
    </div>
  </div>
  
  <!-- Display uploaded photos with file sizes and remove buttons -->
<div v-if="pkg.photo_urls && pkg.photo_urls.length > 0" class="mt-3">
  <div class="flex flex-wrap gap-3 mb-3">
    <div
      v-for="(photoUrl, photoIndex) in pkg.photo_urls"
      :key="photoIndex"
      class="relative group"
    >
      <img
        :src="photoUrl"
        :alt="`Package ${index + 1} photo ${photoIndex + 1}`"
        class="w-24 h-24 object-cover rounded-lg border-2 border-gray-300 shadow-sm transition-all duration-200 group-hover:border-red-400"
      />
      <!-- File size overlay -->
      <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-70 text-white text-xs p-1 text-center rounded-b-lg">
        {{ formatFileSize(pkg.photos[photoIndex]?.size) }}
      </div>
      <!-- Enhanced Remove button - Always visible but more prominent on hover -->
      <button
        type="button"
        @click="removePhoto(index, photoIndex)"
        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold shadow-lg transition-all duration-200 hover:bg-red-600 hover:scale-110 hover:shadow-xl border-2 border-white"
        title="Remove this photo"
      >
        ×
      </button>
    
    </div>
  </div>
  
  <!-- Photo management controls -->
  <div class="flex items-center justify-between text-sm text-gray-600 bg-gray-50 rounded-lg p-3">
    <div class="flex items-center space-x-2">
      <span class="font-semibold">Total: {{ pkg.photo_urls.length }} photo(s)</span>
      <span class="text-gray-400">•</span>
      <span class="text-blue-600 font-medium">{{ calculateTotalSize(pkg.photos) }}</span>
    </div>
    <div class="flex gap-3">
      <button
        type="button"
        @click="form.packages[index].photos = []; form.packages[index].photo_urls = []; clearPhotoErrors(index);"
        class="text-red-600 hover:text-red-700 font-semibold text-sm bg-red-50 hover:bg-red-100 px-3 py-1 rounded-md transition-colors duration-200"
      >
        Remove All
      </button>
    </div>
  </div>
</div>

<!-- ADD THE MULTIPLE PACKAGE CHECKLIST HERE -->
<div v-if="pkg.quantity > 1 && pkg.photo_urls && pkg.photo_urls.length > 0" class="mt-2 bg-blue-50 border border-blue-200 rounded-lg p-2">
  <p class="text-xs text-blue-700">
    💡 <strong>Multiple Package Checklist:</strong> 
    Make sure your 6+ photos include:
    <span class="block ml-2 mt-1">
      • 1 group photo showing all {{ pkg.quantity }} packages together<br>
      • 1 weighing scale photo<br>
      • 4+ detailed photos of individual packages
    </span>
  </p>
</div>

  </div>
      </div>
      <!-- Price Preview -->
      <div v-if="form.priceBreakdown" class="bg-green-50 border border-green-200 rounded-lg p-4 mt-6">
        <h3 class="text-lg font-semibold text-green-800 mb-3">Price Estimate</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
          <div>
            <p class="text-green-600">Base Fee</p>
            <p class="font-semibold text-green-800">₱{{ formatCurrency(form.priceBreakdown.base_fee) }}</p>
          </div>
          <div>
            <p class="text-green-600">Volume Fee</p>
            <p class="font-semibold text-green-800">₱{{ formatCurrency(form.priceBreakdown.volume_fee) }}</p>
          </div>
          <div>
            <p class="text-green-600">Weight Fee</p>
            <p class="font-semibold text-green-800">₱{{ formatCurrency(form.priceBreakdown.weight_fee) }}</p>
          </div>
          <div>
            <p class="text-green-600">Package Fee</p>
            <p class="font-semibold text-green-800">₱{{ formatCurrency(form.priceBreakdown.package_fee) }}</p>
          </div>
        </div>
        <div class="mt-3 pt-3 border-t border-green-200">
          <div class="flex justify-between items-center">
            <p class="text-lg font-semibold text-green-800">Total Estimated Cost</p>
            <p class="text-xl font-bold text-green-800">₱{{ formatCurrency(form.total_price) }}</p>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <div class="flex justify-between pt-6">
        <SecondaryButton @click="prevStep" class="px-6">
          Back
        </SecondaryButton>
        <PrimaryButton @click="nextStep" class="px-6">
          Continue to Payment
        </PrimaryButton>
      </div>
    </div>
  </div>
</div>

<!-- Step 4: Payment Information OR Success Screen -->
<div v-if="currentStep === 4">
  <!-- Success Screen -->
  <div v-if="showSuccessScreen" class="space-y-6">
    <!-- Success Notice -->
    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
      <h3 class="font-semibold text-green-800 flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
        </svg>
        Request Submitted Successfully!
      </h3>
      <p class="text-sm text-green-700 mt-1">
        Your delivery request has been submitted and is pending approval. Check your email for confirmation.
      </p>
    </div>

    <!-- Success Content -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <div class="text-center py-8">
        <!-- Success Icon -->
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
          <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
        </div>
        
        <!-- Success Message -->
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Delivery Request Submitted!</h2>
        <p class="text-lg text-gray-600 mb-2">Your request has been received and is pending approval</p>
        <p class="text-gray-500 mb-8">Request ID: #{{ deliveryRequestId || generateRequestId() }}</p>

        <!-- Key Details - Consistent with Payment Section -->
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-6 mb-8 text-left max-w-4xl mx-auto">
          <h3 class="text-lg font-semibold text-green-800 mb-4">Request Summary</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-lg p-4 border border-green-100">
              <div class="flex items-center space-x-3">
                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                <div>
                  <p class="text-sm text-gray-600">Pick-up From</p>
                  <p class="font-semibold text-green-700">
                    {{ PICKUP_REGIONS.find(r => r.value == form.pick_up_region_id)?.label || 'Not selected' }}
                  </p>
                </div>
              </div>
            </div>
            <div class="bg-white rounded-lg p-4 border border-green-100">
              <div class="flex items-center space-x-3">
                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                <div>
                  <p class="text-sm text-gray-600">Deliver To</p>
                  <p class="font-semibold text-green-700">
                    {{ filteredDropoffRegions.find(r => r.value == form.drop_off_region_id)?.label || 'Not selected' }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Package Summary -->
          <div class="bg-white rounded-lg p-4 border border-green-100 mb-6">
            <h4 class="font-semibold text-green-700 mb-3">Package Summary</h4>
            <div class="space-y-2">
              <div class="flex justify-between items-center py-2">
                <div>
                  <p class="font-medium text-gray-900">Total Packages</p>
                  <p class="text-sm text-gray-600">{{ totalPackageQuantity }} items across {{ form.packages.length }} package type(s)</p>
                </div>
                <span class="text-green-600 font-semibold">Qty: {{ totalPackageQuantity }}</span>
              </div>
              <div v-for="(pkg, index) in form.packages" :key="index" class="flex justify-between items-center py-2 border-t border-green-50">
                <div>
                  <p class="font-medium text-gray-900">{{ pkg.item_name || `Package ${index + 1}` }}</p>
                  <p class="text-sm text-gray-600">
                    {{ pkg.length }}cm × {{ pkg.height }}cm × {{ pkg.width }}cm • {{ pkg.weight }}kg
                  </p>
                </div>
                <span class="text-green-600 font-semibold">Qty: {{ pkg.quantity || 1 }}</span>
              </div>
            </div>
          </div>

          <!-- Price Breakdown -->
        <div class="bg-white rounded-lg p-4 border border-green-100">
  <h4 class="font-semibold text-green-700 mb-3">Price Breakdown</h4>
  <div class="space-y-3" v-if="form.priceBreakdown">
    <!-- Base Fee -->
    <div class="flex justify-between text-sm">
      <div>
        <span class="text-gray-600">Base Delivery Fee</span>
        <p class="text-xs text-gray-500">
          Standard delivery charge
          <span class="block text-purple-600 font-semibold">
            Rate: ₱{{ formatCurrency(priceMatrix.base_fee) }}
          </span>
        </p>
      </div>
      <div class="text-right">
        <span class="font-medium">₱{{ formatCurrency(form.priceBreakdown.base_fee) }}</span>
        <p class="text-xs text-gray-500">1 × ₱{{ formatCurrency(priceMatrix.base_fee) }}</p>
      </div>
    </div>
    
    <!-- Volume Fee -->
    <div class="flex justify-between text-sm">
      <div>
        <span class="text-gray-600">Volume Charge</span>
        <p class="text-xs text-gray-500">
          Total volume × volume rate
          <span class="block text-purple-600 font-semibold">
            Rate: ₱{{ formatCurrency(priceMatrix.volume_rate) }} per m³
          </span>
          <span v-if="form.packages.length > 1" class="block text-green-600 font-semibold">
            ({{ form.packages.length }} package types)
          </span>
        </p>
      </div>
      <div class="text-right">
        <span class="font-medium">₱{{ formatCurrency(form.priceBreakdown.volume_fee) }}</span>
        <p class="text-xs text-gray-500">
          {{ formatCurrency(calculateTotalVolume()) }}m³ × ₱{{ formatCurrency(priceMatrix.volume_rate) }}
        </p>
      </div>
    </div>
    
    <!-- Weight Fee -->
    <div class="flex justify-between text-sm">
      <div>
        <span class="text-gray-600">Weight Charge</span>
        <p class="text-xs text-gray-500">
          Total weight × weight rate
          <span class="block text-purple-600 font-semibold">
            Rate: ₱{{ formatCurrency(priceMatrix.weight_rate) }} per kg
          </span>
          <span v-if="hasMultipleWeightRanges()" class="block text-orange-600 font-semibold">
            (Different weight ranges)
          </span>
        </p>
      </div>
      <div class="text-right">
        <span class="font-medium">₱{{ formatCurrency(form.priceBreakdown.weight_fee) }}</span>
        <p class="text-xs text-gray-500">
          {{ formatCurrency(calculateTotalWeight()) }}kg × ₱{{ formatCurrency(priceMatrix.weight_rate) }}
        </p>
      </div>
    </div>
    
    <!-- Package Fee -->
    <div class="flex justify-between text-sm">
      <div>
        <span class="text-gray-600">Package Handling Fee</span>
        <p class="text-xs text-gray-500">
          Packages × handling rate
          <span class="block text-purple-600 font-semibold">
            Rate: ₱{{ formatCurrency(priceMatrix.package_rate) }} per package
          </span>
          <span v-if="totalPackageQuantity > 1" class="block text-blue-600 font-semibold">
            ({{ totalPackageQuantity }} individual packages)
          </span>
        </p>
      </div>
      <div class="text-right">
        <span class="font-medium">₱{{ formatCurrency(form.priceBreakdown.package_fee) }}</span>
        <p class="text-xs text-gray-500">
          {{ totalPackageQuantity }} × ₱{{ formatCurrency(priceMatrix.package_rate) }}
        </p>
      </div>
    </div>
    
    <!-- Detailed Package Breakdown -->
    <div v-if="form.packages.length > 1" class="bg-gray-50 rounded-lg p-3 mt-2 border border-gray-200">
      <h5 class="font-semibold text-gray-700 text-xs mb-2">Package Details:</h5>
      <div class="space-y-2 text-xs">
        <div v-for="(pkg, index) in form.packages" :key="index" class="flex justify-between items-center border-b border-gray-200 pb-2 last:border-0">
          <div class="flex-1">
            <span class="font-medium text-gray-700">{{ pkg.item_name || `Package ${index + 1}` }}</span>
            <p class="text-gray-500">
              {{ pkg.length }}×{{ pkg.height }}×{{ pkg.width }}cm • {{ pkg.weight }}kg
            </p>
          </div>
          <div class="text-right">
            <span class="font-semibold text-blue-600">Qty: {{ pkg.quantity || 1 }}</span>
            <p class="text-gray-500">
              Vol: {{ formatCurrency(calculatePackageVolume(pkg)) }}m³
            </p>
          </div>
        </div>
        <div class="border-t border-gray-300 pt-2 mt-2 flex justify-between font-semibold text-gray-700">
          <span>Total:</span>
          <span>{{ totalPackageQuantity }} packages</span>
        </div>
      </div>
    </div>

    <!-- Calculation Summary -->
    <div class="bg-blue-50 rounded-lg p-3 border border-blue-200 mt-3">
      <h5 class="font-semibold text-blue-700 text-xs mb-2">Calculation Summary:</h5>
      <div class="grid grid-cols-2 gap-2 text-xs">
        <div class="text-blue-600">
          <span class="font-semibold">Total Volume:</span><br>
          {{ formatCurrency(calculateTotalVolume()) }} m³
        </div>
        <div class="text-blue-600">
          <span class="font-semibold">Total Weight:</span><br>
          {{ formatCurrency(calculateTotalWeight()) }} kg
        </div>
        <div class="text-blue-600">
          <span class="font-semibold">Total Packages:</span><br>
          {{ totalPackageQuantity }} units
        </div>
        <div class="text-blue-600">
          <span class="font-semibold">Package Types:</span><br>
          {{ form.packages.length }} types
        </div>
      </div>
    </div>
    
    <!-- Total Amount -->
    <div class="border-t border-green-200 pt-3 mt-3">
      <div class="flex justify-between items-center">
        <div>
          <span class="text-lg font-semibold text-green-800">Total Amount</span>
          <p class="text-xs text-gray-600" v-if="totalPackageQuantity > 1">
            For {{ totalPackageQuantity }} packages across {{ form.packages.length }} types
          </p>
        </div>
        <div class="text-right">
          <span class="text-xl font-bold text-green-800">₱{{ formatCurrency(form.total_price) }}</span>
          <p class="text-xs text-gray-500">
            Base + Volume + Weight + Handling
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
        </div>

       <!-- Enhanced "What Happens Next" Section -->
<div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8 text-left max-w-4xl mx-auto">
  <h3 class="font-semibold text-blue-800 mb-4 flex items-center">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    What Happens Next
  </h3>
  
  <div class="space-y-4 text-sm text-blue-700">
    <!-- Approval Process -->
    <div class="flex items-start">
      <div class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0">1</div>
      <div>
        <strong>Request Approval:</strong> Your delivery request is now pending approval. You'll receive email and website notifications within 24 business hours.
      </div>
    </div>

    <!-- Package Handover -->
    <div class="flex items-start">
      <div class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0">2</div>
      <div>
        <strong>Package Handover:</strong> After approval, bring your packages to the selected drop-off branch location.
      </div>
    </div>

    <!-- Payment Process -->
    <div class="flex items-start" v-if="form.payment_type === 'prepaid' && form.payment_method === 'cash'">
      <div class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0">3</div>
      <div>
        <strong>Payment Process:</strong> Pay the total amount in cash at the branch counter when submitting your packages.
      </div>
    </div>

    <!-- GCash Payment Options -->
    <div class="flex items-start" v-else-if="form.payment_type === 'prepaid' && form.payment_method === 'gcash'">
      <div class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0">3</div>
      <div>
        <strong>Payment Options:</strong> 
        <br>- Pay via GCash through your My Delivery dashboard (preferred)
        <br>- OR pay in cash at the branch counter when submitting packages
      </div>
    </div>

    <!-- Bank Transfer Payment Options -->
    <div class="flex items-start" v-else-if="form.payment_type === 'prepaid' && form.payment_method === 'bank'">
      <div class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0">3</div>
      <div>
        <strong>Payment Options:</strong> 
        <br>- Pay via Bank Transfer through your My Delivery dashboard (preferred)
        <br>- OR pay in cash at the branch counter when submitting packages
      </div>
    </div>

    <!-- Postpaid Package Processing -->
    <div class="flex items-start" v-else-if="form.payment_type === 'postpaid'">
      <div class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0">3</div>
      <div>
        <strong>Package Processing:</strong> Your packages will be processed and prepared for delivery.
      </div>
    </div>

    <!-- Default Payment Selection -->
    <div class="flex items-start" v-else>
      <div class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0">3</div>
      <div>
        <strong>Payment Selection:</strong> Complete your payment method selection to proceed with package processing.
      </div>
    </div>

    <!-- Payment Verification for Digital Payments -->
    <div class="flex items-start" v-if="form.payment_type === 'prepaid' && (form.payment_method === 'gcash' || form.payment_method === 'bank')">
      <div class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0">4</div>
      <div>
        <strong>Payment Verification:</strong> If paying digitally, submit payment screenshot and reference number for verification.
      </div>
    </div>

    <!-- Package Processing for Prepaid -->
    <div class="flex items-start" v-if="form.payment_type === 'prepaid'">
      <div :class="['w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0', (form.payment_method === 'gcash' || form.payment_method === 'bank') ? '4' : '4']">
        {{ (form.payment_method === 'gcash' || form.payment_method === 'bank') ? '5' : '4' }}
      </div>
      <div>
        <strong>Package Processing:</strong> Once payment is verified, your packages will be processed and prepared for delivery.
      </div>
    </div>

    <!-- Postpaid Delivery & Payment -->
    <div class="flex items-start" v-if="form.payment_type === 'postpaid'">
      <div class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0">4</div>
      <div>
        <strong>Delivery & Payment:</strong> 
        <span v-if="form.payment_terms === 'net_7'">Payment due within 7 days after delivery completion. Our collector will visit your location.</span>
        <span v-else-if="form.payment_terms === 'net_15'">Payment due within 15 days after delivery completion. Our collector will visit your location.</span>
        <span v-else-if="form.payment_terms === 'net_30'">Payment due within 30 days after delivery completion. Our collector will visit your location.</span>
        <span v-else-if="form.payment_terms === 'cnd'">Cash payment on next delivery visit. Our collector will collect payment during the next delivery.</span>
      </div>
    </div>

    <!-- Early Payment Option for Postpaid -->
    <div class="flex items-start" v-if="form.payment_type === 'postpaid'">
      <div class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0">5</div>
      <div>
        <strong>Early Payment Option:</strong> You can pay earlier via GCash or Bank Transfer through your My Delivery dashboard.
      </div>
    </div>

    <!-- Package Processing for Default -->
    <div class="flex items-start" v-if="!form.payment_type">
      <div class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0">4</div>
      <div>
        <strong>Package Processing:</strong> Once payment is arranged, your packages will be processed and prepared for delivery.
      </div>
    </div>

    <!-- Tracking Step -->
    <div class="flex items-start">
      <div :class="['w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0', 
        form.payment_type === 'postpaid' ? '6' : 
        (form.payment_type === 'prepaid' && (form.payment_method === 'gcash' || form.payment_method === 'bank')) ? '6' :
        form.payment_type === 'prepaid' ? '5' : '5'
      ]">
        {{ 
          form.payment_type === 'postpaid' ? '6' : 
          (form.payment_type === 'prepaid' && (form.payment_method === 'gcash' || form.payment_method === 'bank')) ? '6' :
          form.payment_type === 'prepaid' ? '5' : '5'
        }}
      </div>
      <div>
        <strong>Tracking & Updates:</strong> Monitor your delivery status in the "My Deliveries" section. You'll receive email and website updates at each major milestone.
      </div>
    </div>
  </div>

  <!-- Additional Information -->
  <div class="mt-4 pt-4 border-t border-blue-200">
    <div class="flex items-start text-sm text-blue-600">
      <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <div>
        <strong>Need to make changes?</strong> Contact our support team immediately if you need to modify your request before approval.
      </div>
    </div>
  </div>
</div>

        <!-- Action Buttons - Consistent Green Theme -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <PrimaryButton 
            @click="createSimilarDelivery" 
            class="px-8 py-3 text-base"
          >
            Create Similar Delivery
          </PrimaryButton>
          <Link 
            :href="route('customer.deliveries.index')" 
            class="px-8 py-3 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150 text-base text-center"
          >
            View My Deliveries
          </Link>
          <a 
            :href="route('contact.us')" 
            class="px-8 py-3 border border-green-600 text-green-600 rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150 text-base text-center"
          >
            Contact Support
          </a>
        </div>

        <!-- Support Info -->
        <div class="mt-8 text-sm text-gray-500">
          <p>Questions? Contact us at <a href="mailto:support@example.com" class="text-green-600 hover:text-green-700 font-medium">support@example.com</a></p>
          <p class="mt-1">Expected approval: Within 24 business hours</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Original Payment Form (shown when NOT in success state) -->
  <div v-else class="space-y-6">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column - Information Sections (2/3 width) -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Payment Summary Card -->
          <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-green-800 mb-4">Delivery Summary</h3>
            
            <!-- Route Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div class="bg-white rounded-lg p-4 border border-green-100">
                <div class="flex items-center space-x-3">
                  <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                  <div>
                    <p class="text-sm text-gray-600">Pick-up From</p>
                    <p class="font-semibold text-green-700">
                      {{ PICKUP_REGIONS.find(r => r.value == form.pick_up_region_id)?.label || 'Not selected' }}
                    </p>
                  </div>
                </div>
              </div>
              <div class="bg-white rounded-lg p-4 border border-green-100">
                <div class="flex items-center space-x-3">
                  <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                  <div>
                    <p class="text-sm text-gray-600">Deliver To</p>
                    <p class="font-semibold text-green-700">
                      {{ filteredDropoffRegions.find(r => r.value == form.drop_off_region_id)?.label || 'Not selected' }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Package Summary -->
            <div class="bg-white rounded-lg p-4 border border-green-100 mb-6">
              <h4 class="font-semibold text-green-700 mb-3">Package Summary</h4>
              <div class="space-y-2">
                <div class="flex justify-between items-center py-2">
                  <div>
                    <p class="font-medium text-gray-900">Total Packages</p>
                    <p class="text-sm text-gray-600">{{ totalPackageQuantity }} items across {{ form.packages.length }} package type(s)</p>
                  </div>
                  <span class="text-green-600 font-semibold">Qty: {{ totalPackageQuantity }}</span>
                </div>
                <div v-for="(pkg, index) in form.packages" :key="index" class="flex justify-between items-center py-2 border-t border-green-50">
                  <div>
                    <p class="font-medium text-gray-900">{{ pkg.item_name || `Package ${index + 1}` }}</p>
                    <p class="text-sm text-gray-600">
                      {{ pkg.quantity || 1 }} × {{ pkg.length }}cm × {{ pkg.height }}cm × {{ pkg.width }}cm • {{ pkg.weight }}kg
                    </p>
                  </div>
                  <span class="text-green-600 font-semibold">Qty: {{ pkg.quantity || 1 }}</span>
                </div>
              </div>
            </div>

            <!-- Enhanced Price Breakdown with Processing Fee -->
            <div class="bg-white rounded-lg p-4 border border-green-100">
              <h4 class="font-semibold text-green-700 mb-3">Price Breakdown</h4>
              <div class="space-y-3" v-if="form.priceBreakdown">
                <!-- Base Fee -->
                <div class="flex justify-between text-sm">
                  <div>
                    <span class="text-gray-600">Base Delivery Fee</span>
                    <p class="text-xs text-gray-500">
                      Standard delivery charge
                      <span class="block text-purple-600 font-semibold">
                        Rate: ₱{{ formatCurrency(priceMatrix.base_fee) }}
                      </span>
                    </p>
                  </div>
                  <div class="text-right">
                    <span class="font-medium">₱{{ formatCurrency(form.priceBreakdown.base_fee) }}</span>
                    <p class="text-xs text-gray-500">1 × ₱{{ formatCurrency(priceMatrix.base_fee) }}</p>
                  </div>
                </div>
                
                <!-- Volume Fee -->
                <div class="flex justify-between text-sm">
                  <div>
                    <span class="text-gray-600">Volume Charge</span>
                    <p class="text-xs text-gray-500">
                      Total volume × volume rate
                      <span class="block text-purple-600 font-semibold">
                        Rate: ₱{{ formatCurrency(priceMatrix.volume_rate) }} per m³
                      </span>
                    </p>
                  </div>
                  <div class="text-right">
                    <span class="font-medium">₱{{ formatCurrency(form.priceBreakdown.volume_fee) }}</span>
                    <p class="text-xs text-gray-500">
                      {{ formatCurrency(calculateTotalVolume()) }}m³ × ₱{{ formatCurrency(priceMatrix.volume_rate) }}
                    </p>
                  </div>
                </div>
                
                <!-- Weight Fee -->
                <div class="flex justify-between text-sm">
                  <div>
                    <span class="text-gray-600">Weight Charge</span>
                    <p class="text-xs text-gray-500">
                      Total weight × weight rate
                      <span class="block text-purple-600 font-semibold">
                        Rate: ₱{{ formatCurrency(priceMatrix.weight_rate) }} per kg
                      </span>
                    </p>
                  </div>
                  <div class="text-right">
                    <span class="font-medium">₱{{ formatCurrency(form.priceBreakdown.weight_fee) }}</span>
                    <p class="text-xs text-gray-500">
                      {{ formatCurrency(calculateTotalWeight()) }}kg × ₱{{ formatCurrency(priceMatrix.weight_rate) }}
                    </p>
                  </div>
                </div>
                
                <!-- Package Fee -->
                <div class="flex justify-between text-sm">
                  <div>
                    <span class="text-gray-600">Package Handling Fee</span>
                    <p class="text-xs text-gray-500">
                      Packages × handling rate
                      <span class="block text-purple-600 font-semibold">
                        Rate: ₱{{ formatCurrency(priceMatrix.package_rate) }} per package
                      </span>
                    </p>
                  </div>
                  <div class="text-right">
                    <span class="font-medium">₱{{ formatCurrency(form.priceBreakdown.package_fee) }}</span>
                    <p class="text-xs text-gray-500">
                      {{ totalPackageQuantity }} × ₱{{ formatCurrency(priceMatrix.package_rate) }}
                    </p>
                  </div>
                </div>

               <!-- In the price breakdown section -->
<div class="border-t border-green-200 pt-3 mt-3">
    <div class="flex justify-between text-sm mb-2">
        <div>
            <span class="text-gray-600">Processing Fee</span>
            <p class="text-xs text-gray-500">
                One-time fee to secure your request
                <span class="block text-blue-600 font-semibold">
                    Required for all delivery requests
                </span>
            </p>
        </div>
        <div class="text-right">
            <span class="font-medium">-₱200.00</span>
            <p class="text-xs text-gray-500">Deducted from total</p>
        </div>
    </div>
    
    <!-- Final Amount -->
    <div class="flex justify-between items-center pt-3 border-t border-green-200">
        <div>
            <span class="text-lg font-semibold text-green-800">Final Amount Due</span>
            <p class="text-xs text-gray-600">After processing fee deduction</p>
        </div>
        <div class="text-right">
            <span class="text-xl font-bold text-green-800">₱{{ formatCurrency(form.total_price - 200) }}</span>
            <p class="text-xs text-gray-500">
                ₱{{ formatCurrency(form.total_price) }} - ₱200.00
            </p>
        </div>
    </div>
</div>
              </div>
            </div>
          </div>

          <!-- Downpayment Section -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="font-semibold text-blue-800 flex items-center mb-4">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Processing Fee Payment Required
            </h3>
            
            <div class="bg-white rounded-lg p-4 border border-blue-100 mb-4">
              <div class="flex justify-between items-center mb-3">
                <span class="text-lg font-semibold text-blue-800">Processing Fee</span>
                <span class="text-lg font-bold text-blue-800">₱200.00</span>
              </div>
              <p class="text-sm text-blue-600">
                This one-time fee secures your delivery request and will be deducted from your final payment. 
                Required for all delivery requests to prevent spam. No admin verification needed.
              </p>
            </div>

            <!-- Downpayment Method Selection -->
            <div class="mb-4">
              <InputLabel value="Payment Method for Processing Fee *" />
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                <label
                  v-for="option in downpaymentOptions"
                  :key="option.value"
                  :class="[
                    'border rounded-lg p-4 cursor-pointer transition-all duration-200',
                    form.downpayment_method === option.value
                      ? 'border-green-500 bg-green-50 ring-2 ring-green-200'
                      : 'border-gray-300 hover:border-gray-400'
                  ]"
                >
                  <div class="flex items-center">
                    <input
                      type="radio"
                      v-model="form.downpayment_method"
                      :value="option.value"
                      class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300"
                    />
                    <span class="ml-3 text-sm font-medium text-gray-900">{{ option.label }}</span>
                  </div>
                </label>
              </div>
              <InputError :message="form.errors.downpayment_method" />
            </div>

            <!-- Reference Number -->
            <div class="mb-4">
              <InputLabel value="Reference Number *" />
              <TextInput
                v-model="form.downpayment_reference"
                type="text"
                class="mt-1 block w-full"
                :class="{ 'border-red-500': form.errors.downpayment_reference }"
                placeholder="Enter transaction reference number"
              />
              <p class="text-xs text-gray-500 mt-1">Reference number from your GCash or Bank transaction</p>
              <InputError :message="form.errors.downpayment_reference" />
            </div>

            <!-- Receipt Upload -->
            <div>
              <InputLabel value="Payment Receipt Screenshot *" />
              <input
                type="file"
                @change="handleDownpaymentReceiptUpload"
                accept="image/*"
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 mt-1"
              />
              <p class="text-xs text-gray-500 mt-1">Upload screenshot of your payment confirmation (Max 5MB)</p>
              <InputError :message="form.errors.downpayment_receipt" />
              
              <!-- Preview uploaded receipt -->
              <div v-if="form.downpayment_receipt_url" class="mt-3">
                <img :src="form.downpayment_receipt_url" alt="Payment receipt" class="w-32 h-32 object-cover rounded-lg border border-gray-300" />
                <button
                  type="button"
                  @click="removeDownpaymentReceipt"
                  class="mt-2 text-red-600 hover:text-red-700 text-sm font-medium"
                >
                  Remove Receipt
                </button>
              </div>
            </div>
          </div>

          <!-- Contextual Payment Guides -->
          <!-- Prepaid Cash Guide -->
          <div v-if="form.payment_type === 'prepaid' && form.payment_method === 'cash'" class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="font-semibold text-blue-800 flex items-center mb-4">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              Cash Payment Process
            </h3>
            <div class="space-y-4 text-sm text-blue-700">
              <div class="flex items-start">
                <div class="w-2 h-2 bg-blue-500 rounded-full mt-1.5 mr-3 flex-shrink-0"></div>
                <div>
                  <strong>Step 1:</strong> Bring your packages to the selected drop-off location
                </div>
              </div>
              <div class="flex items-start">
                <div class="w-2 h-2 bg-blue-500 rounded-full mt-1.5 mr-3 flex-shrink-0"></div>
                <div>
                  <strong>Step 2:</strong> Present your delivery request details at the counter
                </div>
              </div>
              <div class="flex items-start">
                <div class="w-2 h-2 bg-blue-500 rounded-full mt-1.5 mr-3 flex-shrink-0"></div>
                <div>
                  <strong>Step 3:</strong> Pay the total amount in cash to the cashier
                </div>
              </div>
              <div class="flex items-start">
                <div class="w-2 h-2 bg-blue-500 rounded-full mt-1.5 mr-3 flex-shrink-0"></div>
                <div>
                  <strong>Step 4:</strong> Receive your payment receipt and tracking information
                </div>
              </div>
              <div class="bg-white rounded-lg p-3 border border-blue-100 mt-3">
                <p class="font-semibold text-blue-800">💡 Tip: Bring exact change for faster processing</p>
              </div>
            </div>
          </div>

          <!-- Prepaid Digital Guide -->
          <div v-if="form.payment_type === 'prepaid' && (form.payment_method === 'gcash' || form.payment_method === 'bank')" class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="font-semibold text-blue-800 flex items-center mb-4">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
              </svg>
              Digital Payment Process
            </h3>
            <div class="space-y-4 text-sm text-blue-700">
              <div class="flex items-start">
                <div class="w-2 h-2 bg-blue-500 rounded-full mt-1.5 mr-3 flex-shrink-0"></div>
                <div>
                  <strong>Step 1:</strong> Wait for your delivery request to be approved
                </div>
              </div>
              <div class="flex items-start">
                <div class="w-2 h-2 bg-blue-500 rounded-full mt-1.5 mr-3 flex-shrink-0"></div>
                <div>
                  <strong>Step 2:</strong> Go to "My Deliveries" in your account dashboard
                </div>
              </div>
              <div class="flex items-start">
                <div class="w-2 h-2 bg-blue-500 rounded-full mt-1.5 mr-3 flex-shrink-0"></div>
                <div>
                  <strong>Step 3:</strong> Follow the payment instructions for your selected method
                </div>
              </div>
              <div class="flex items-start">
                <div class="w-2 h-2 bg-blue-500 rounded-full mt-1.5 mr-3 flex-shrink-0"></div>
                <div>
                  <strong>Step 4:</strong> Take a screenshot of the successful payment
                </div>
              </div>
              <div class="flex items-start">
                <div class="w-2 h-2 bg-blue-500 rounded-full mt-1.5 mr-3 flex-shrink-0"></div>
                <div>
                  <strong>Step 5:</strong> Submit the screenshot and reference number for verification
                </div>
              </div>
              <div class="bg-white rounded-lg p-3 border border-blue-100 mt-3">
                <p class="font-semibold text-blue-800">💡 Alternative: You can still pay in cash at the drop-off location</p>
              </div>
            </div>
          </div>

          <!-- Prepaid General Guide (when payment type is prepaid but no method selected) -->
          <div v-if="form.payment_type === 'prepaid' && !form.payment_method" class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="font-semibold text-blue-800 flex items-center mb-4">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Prepaid Payment Options
            </h3>
            <div class="space-y-4 text-sm text-blue-700">
              <div class="bg-white rounded-lg p-4 border border-blue-100">
                <h4 class="font-semibold text-blue-800 mb-2">Cash Payment</h4>
                <p>Pay directly at the drop-off location counter when submitting your packages</p>
              </div>
              <div class="bg-white rounded-lg p-4 border border-blue-100">
                <h4 class="font-semibold text-blue-800 mb-2">Digital Payment (GCash/Bank)</h4>
                <p>Pay online after request approval or pay cash at location with verification</p>
              </div>
            </div>
          </div>

          <!-- Postpaid Guide -->
          <div v-if="form.payment_type === 'postpaid'" class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="font-semibold text-blue-800 flex items-center mb-4">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              Postpaid Payment Process
            </h3>
            <div class="space-y-4 text-sm text-blue-700">
              <div class="flex items-start">
                <div class="w-2 h-2 bg-blue-500 rounded-full mt-1.5 mr-3 flex-shrink-0"></div>
                <div>
                  <strong>Standard Process:</strong> Our collector will visit your location according to your payment terms
                </div>
              </div>
              <div class="flex items-start">
                <div class="w-2 h-2 bg-blue-500 rounded-full mt-1.5 mr-3 flex-shrink-0"></div>
                <div>
                  <strong>Early Payment Option:</strong> You can pay earlier via GCash or Bank Transfer
                </div>
              </div>
              <div class="flex items-start">
                <div class="w-2 h-2 bg-blue-500 rounded-full mt-1.5 mr-3 flex-shrink-0"></div>
                <div>
                  <strong>Payment Terms:</strong> 
                  <span v-if="form.payment_terms === 'net_7'">Payment due within 7 days after delivery</span>
                  <span v-else-if="form.payment_terms === 'net_15'">Payment due within 15 days after delivery</span>
                  <span v-else-if="form.payment_terms === 'net_30'">Payment due within 30 days after delivery</span>
                  <span v-else-if="form.payment_terms === 'cnd'">Cash payment on next delivery visit</span>
                  <span v-else>Select payment terms to see details</span>
                </div>
              </div>
              <div class="bg-white rounded-lg p-3 border border-blue-100 mt-3">
                <p class="font-semibold text-blue-800">💡 Available only for trusted customers with 3+ completed deliveries</p>
              </div>
            </div>
          </div>

          <!-- Default Guide (when no selection) -->
          <div v-if="!form.payment_type" class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="font-semibold text-blue-800 flex items-center mb-4">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Payment Options Overview
            </h3>
            <div class="space-y-4 text-sm text-blue-700">
              <div class="bg-white rounded-lg p-4 border border-blue-100">
                <h4 class="font-semibold text-blue-800 mb-2">Prepaid Options</h4>
                <p><strong>Cash:</strong> Pay directly at the drop-off location counter</p>
                <p><strong>GCash/Bank Transfer:</strong> Pay online after request approval</p>
              </div>
              <div class="bg-white rounded-lg p-4 border border-blue-100">
                <h4 class="font-semibold text-blue-800 mb-2">Postpaid Options</h4>
                <p><strong>Available for trusted customers</strong> with 3+ completed deliveries</p>
                <p>Flexible payment terms with collector visits or early digital payment</p>
              </div>
            </div>
          </div>

          <!-- Important Notes -->
          <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">Important Notes</h3>
                <div class="mt-2 text-sm text-yellow-700">
                  <ul class="list-disc list-inside space-y-1">
                    <li>By submitting this request, you agree to our terms and conditions</li>
                    <li>Delivery time may vary based on route and weather conditions</li>
                    <li>Additional charges may apply for special handling requirements</li>
                    <li>Package value declaration is for insurance purposes only</li>
                    <li>Online payments require admin verification before delivery processing</li>
                    <li>Keep your payment reference numbers for tracking purposes</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>

          <!-- Right Column - Payment Selection (1/3 width) -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 sticky top-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Select Payment Options</h3>
            
            <!-- Payment Type -->
            <div class="mb-6">
              <InputLabel value="Payment Type *" />
              <div class="space-y-3 mt-2">
                <label
                  v-for="option in paymentTypeOptions"
                  :key="option.value"
                  :class="[
                    'border rounded-lg p-3 cursor-pointer transition-all duration-200 block w-full',
                    form.payment_type === option.value
                      ? 'border-green-500 bg-green-50 ring-2 ring-green-200'
                      : 'border-gray-300 hover:border-gray-400'
                  ]"
                >
                  <div class="flex items-start w-full">
                    <input
                      type="radio"
                      v-model="form.payment_type"
                      :value="option.value"
                      class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 mt-0.5 flex-shrink-0"
                    />
                    <div class="ml-3 flex-1 min-w-0">
                      <span class="text-sm font-medium text-gray-900 block">{{ option.label }}</span>
                      <p v-if="option.value === 'postpaid'" class="text-xs text-green-600 mt-1">
                        Available for customers with 3+ completed deliveries
                      </p>
                      <p v-else class="text-xs text-gray-500 mt-1">
                        Pay now before delivery processing
                      </p>
                    </div>
                  </div>
                </label>
              </div>
              <InputError :message="form.errors.payment_type" />
            </div>

            <!-- Payment Method (Conditional) -->
            <div v-if="form.payment_type === 'prepaid'" class="mb-6">
              <InputLabel value="Payment Method *" />
              <div class="space-y-3 mt-2">
                <label
                  v-for="option in paymentMethodOptions"
                  :key="option.value"
                  :class="[
                    'border rounded-lg p-3 cursor-pointer transition-all duration-200 block w-full',
                    form.payment_method === option.value
                      ? 'border-green-500 bg-green-50 ring-2 ring-green-200'
                      : 'border-gray-300 hover:border-gray-400'
                  ]"
                >
                  <div class="flex items-start w-full">
                    <input
                      type="radio"
                      v-model="form.payment_method"
                      :value="option.value"
                      class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 mt-0.5 flex-shrink-0"
                    />
                    <div class="ml-3 flex-1 min-w-0">
                      <span class="text-sm font-medium text-gray-900 block">{{ option.label }}</span>
                      <p class="text-xs text-gray-500 mt-1">
                        <span v-if="option.value === 'cash'">Pay at drop-off location counter</span>
                        <span v-else-if="option.value === 'gcash'">Pay online or at location with verification</span>
                        <span v-else-if="option.value === 'bank'">Bank transfer with proof of payment</span>
                      </p>
                    </div>
                  </div>
                </label>
              </div>
              <InputError :message="form.errors.payment_method" />
            </div>

            <!-- Payment Terms (for postpaid) -->
            <div v-if="form.payment_type === 'postpaid'" class="mb-6">
              <InputLabel value="Payment Terms *" />
              <div class="space-y-3 mt-2">
                <label
                  v-for="option in paymentTermsOptions"
                  :key="option.value"
                  :class="[
                    'border rounded-lg p-3 cursor-pointer transition-all duration-200 block w-full',
                    form.payment_terms === option.value
                      ? 'border-green-500 bg-green-50 ring-2 ring-green-200'
                      : 'border-gray-300 hover:border-gray-400'
                  ]"
                >
                  <div class="flex items-start w-full">
                    <input
                      type="radio"
                      v-model="form.payment_terms"
                      :value="option.value"
                      class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 mt-0.5 flex-shrink-0"
                    />
                    <div class="ml-3 flex-1 min-w-0">
                      <span class="text-sm font-medium text-gray-900 block">{{ option.label.split(' (')[0] }}</span>
                      <p class="text-xs text-gray-500 mt-1">
                        <span v-if="option.value === 'net_7'">Payment due within 7 days after delivery</span>
                        <span v-else-if="option.value === 'net_15'">Payment due within 15 days after delivery</span>
                        <span v-else-if="option.value === 'net_30'">Payment due within 30 days after delivery</span>
                        <span v-else-if="option.value === 'cnd'">Cash payment on next delivery visit</span>
                      </p>
                    </div>
                  </div>
                </label>
              </div>
              <InputError :message="form.errors.payment_terms" />
            </div>
          </div>
        </div>
      </div>

  <!-- Navigation -->
      <div class="flex justify-between pt-6">
        <SecondaryButton @click="prevStep" class="px-6">
          Back
        </SecondaryButton>
        <PrimaryButton 
          @click="submitRequest" 
          :disabled="form.processing || isLoading"
          class="px-8"
        >
          <span v-if="form.processing || isLoading" class="flex items-center">
            <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Processing...
          </span>
          <span v-else>Submit Delivery Request</span>
        </PrimaryButton>
      </div>
    </div>
  </div>
</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </GuestLayout>
</template>