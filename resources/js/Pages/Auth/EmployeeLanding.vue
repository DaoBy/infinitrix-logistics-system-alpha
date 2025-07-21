<template>
  <EmployeeSRLayout>
    <v-container class="landing-page" fluid>
      <!-- Centered Content -->
      <v-row justify="center" align="center" class="center-container" style="gap: 2rem;">
        <!-- Header -->
        <v-col cols="12" class="text-center">
          <h1 class="title">🚚 Infinitrix Express Cargo</h1>
          <p class="subtitle">Delivering... when we feel like it. 📦</p>
        </v-col>

        <!-- Left: Register Card -->
        <v-col cols="12" md="5" lg="4" class="fixed-card">
          <v-card class="action-card" elevation="8">
            <v-card-title class="card-title">🚀 Join the Chaos</v-card-title>
            <v-card-text>
              Become part of the **chaotic delivery crew** and send random packages to random places.
            </v-card-text>
            <v-card-actions>
              <Link :href="route('employee.register')" as="button" class="cta-button">
                <PrimaryButton>
                  Register 📤
                </PrimaryButton>
              </Link>
            </v-card-actions>
          </v-card>
        </v-col>

        <!-- Right: Sign In Card -->
        <v-col cols="12" md="5" lg="4" class="fixed-card">
          <v-card class="action-card" elevation="8">
            <v-card-title class="card-title">🔐 Already Lost?</v-card-title>
            <v-card-text>
              Sign in and figure out if that box you shipped last week is still moving.
            </v-card-text>
            <v-card-actions>
              <Link :href="route('login')" as="button" class="cta-button">
                <SecondaryButton>
                  Sign In 📦
                </SecondaryButton>
              </Link>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </EmployeeSRLayout>
</template>

<script setup>
import EmployeeSRLayout from '@/Layouts/EmployeeSRLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Link } from '@inertiajs/vue3'; // Import Inertia's Link component
</script>

<style scoped>
/* Root container: Full height and no scroll */
.landing-page {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh; /* Full viewport height */
  padding: 1rem; /* Add padding for better spacing on mobile */
}

/* Centering container */
.center-container {
  width: 100%;
  max-width: 1200px; /* Limit maximum width for better readability */
  display: flex;
  flex-wrap: wrap; /* Allow wrapping on smaller screens */
  justify-content: center; /* Center cards on smaller screens */
  gap: 2rem; /* Consistent gap between cards */
  text-align: center;
}

/* Header styling */
.title {
  font-size: 2.5rem; /* Smaller for mobile */
  font-weight: bold;
  margin-bottom: 0.5rem;
}

.subtitle {
  font-size: 1.2rem; /* Smaller for mobile */
  margin-bottom: 2rem; /* Reduced margin for mobile */
  color: #555;
}

/* Force the cards to stay apart */
.fixed-card {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 80%; /* Full width on mobile */
  max-width: 450px; /* Consistent card size */
}

/* Individual Card */
.action-card {
  border-radius: 16px;
  background-color: rgb(255, 255, 255);
  padding: 1.5rem; /* Slightly reduced padding for mobile */
  transition: box-shadow 0.2s;
  width: 100%; /* Full width */
  min-height: 300px; /* Ensure consistent card height */
  display: flex;
  flex-direction: column;
  justify-content: space-between; /* Distribute space evenly */
}

/* Hover effect */
.action-card:hover {
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
}

/* Card Title */
.card-title {
  font-size: 1.5rem; /* Smaller for mobile */
  font-weight: bold;
  margin-bottom: 1rem;
}

/* Card Text */
.card-text {
  flex-grow: 1; /* Allow text to grow and fill available space */
}

/* CTA Button - Adjusted size */
.cta-button {
  font-size: 2rem; /* Smaller for mobile */
  width: 100%;
  padding: 0.8rem 0;
  min-height: 90px; /* Ensure consistent button height */
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none; /* Remove underline from Link */
}


</style>