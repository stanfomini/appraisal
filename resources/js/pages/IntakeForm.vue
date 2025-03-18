<!-- resources/js/Pages/IntakeForm.vue -->
<template>
    <div class="intake-page">
      <h1>Sell Your Car - Intake Form</h1>
      <SellYourCarForm @submitted="handleSubmission" />
    </div>
  </template>
  
  <script setup>
  import SellYourCarForm from '../components/SellYourCarForm.vue';
  
  const handleSubmission = (data) => {
    console.log('Form submitted with data:', data);
    // Here you could redirect or show a success message
  };
  </script>
  
  <style scoped>
  .intake-page {
    max-width: 800px;
    margin: 2rem auto;
    padding: 1rem;
  }
  h1 {
    font-size: 2rem;
    margin-bottom: 1rem;
  }
  </style>