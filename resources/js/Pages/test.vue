<template>
    <div id="app">
      <h1>Welcome to My Simple Vue Page!</h1>
      <input v-model="message" placeholder="Type something here..." />
      <button @click="alertMessage">Click me</button>
      <p>Your message: {{ message }}</p>
    </div>
  </template>
  
  <script>
  export default {
    data() {
      return {
        message: "",
      };
    },
    methods: {
      alertMessage() {
        alert(`You typed: ${this.message}`);
      },
    },
  };
  </script>
  
  <style scoped>
  h1 {
    color: #42b983;
  }
  button {
    margin-top: 10px;
    padding: 10px;
    background-color: #42b983;
    color: white;
    border: none;
    cursor: pointer;
  }
  button:hover {
    background-color: #355c4f;
  }
  </style>
  