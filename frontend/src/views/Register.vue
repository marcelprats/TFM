<script setup lang="ts">
import { ref } from 'vue';
import { registerUser } from '../services/authService';

const name = ref('');
const email = ref('');
const password = ref('');
const message = ref('');

async function handleRegister() {
    try {
        const response = await registerUser(name.value, email.value, password.value);
        message.value = 'Usuari registrat correctament!';
        console.log('Resposta:', response);
    } catch (error: any) {
        message.value = error.message || 'Error en el registre.';
    }
}
</script>

<template>
  <div>
    <h2>Registra't</h2>
    <form @submit.prevent="handleRegister">
      <label>Nom:</label>
      <input v-model="name" type="text" required />
      
      <label>Email:</label>
      <input v-model="email" type="email" required />
      
      <label>Contrasenya:</label>
      <input v-model="password" type="password" required />
      
      <button type="submit">Registrar</button>
    </form>
    <p v-if="message">{{ message }}</p>
  </div>
</template>

<style scoped>
  div {
    max-width: 300px;
    margin: auto;
  }
  input {
    display: block;
    margin-bottom: 10px;
    width: 100%;
    padding: 5px;
  }
</style>
