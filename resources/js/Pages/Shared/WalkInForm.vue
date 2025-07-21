<template>
    <div>
        <h1>Create Walk-In Customer</h1>

        <form @submit.prevent="submitForm">
            <div>
                <label>Full Name:</label>
                <input v-model="form.full_name" type="text" required />
            </div>

            <div>
                <label>Phone Number:</label>
                <input v-model="form.phone_number" type="text" />
            </div>

            <div>
                <label>Email (Optional):</label>
                <input v-model="form.email" type="email" />
            </div>

            <div>
                <label>Street:</label>
                <input v-model="form.street" type="text" />
            </div>

            <div>
                <label>City:</label>
                <input v-model="form.city" type="text" />
            </div>

            <div>
                <label>Province:</label>
                <input v-model="form.province" type="text" />
            </div>

            <div>
                <label>ZIP Code:</label>
                <input v-model="form.zip_code" type="text" />
            </div>

            <div>
                <label>Country:</label>
                <input v-model="form.country" type="text" placeholder="Philippines" />
            </div>

            <div>
                <label>Company Name (Optional):</label>
                <input v-model="form.company_name" type="text" />
            </div>

            <div>
                <label>Customer Type:</label>
                <select v-model="form.customer_type">
                    <option value="individual">Individual</option>
                    <option value="corporate">Corporate</option>
                </select>
            </div>

            <button type="submit">Create Walk-In</button>
        </form>
    </div>
</template>

<script>
import { useForm } from '@inertiajs/vue3';

export default {
    setup() {
        const form = useForm({
            full_name: '',
            phone_number: '',
            email: '',
            street: '',
            city: '',
            province: '',
            zip_code: '',
            country: 'Philippines',
            company_name: '',
            customer_type: 'individual',
        });

        const submitForm = () => {
            form.post(route('walkin.store'));
        };

        return { form, submitForm };
    },
};
</script>
