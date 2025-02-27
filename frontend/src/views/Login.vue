<template>
  <div class="auth-container">
    <div class="auth-box">
      <h2>Iniciar Sessió</h2>
      <form @submit.prevent="handleLogin">
        <label for="email">Email</label>
        <input type="email" id="email" v-model="email" required />

        <label for="password">Contrasenya</label>
        <input type="password" id="password" v-model="password" required />

        <button type="submit">Entrar</button>
      </form>
      <p class="switch-link">No tens compte? <router-link to="/register">Registra't aquí</router-link></p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { loginUser } from '../services/authService';

const email = ref('');
const password = ref('');
const router = useRouter();

const handleLogin = async () => {
  try {
    await loginUser(email.value, password.value);
    router.push('/');
  } catch (error) {
    alert('Error al iniciar sessió. Revisa les credencials.');
  }
};
</script>

<style scoped>
.auth-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 80vh;
}

.auth-box {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
  text-align: center;
  width: 300px;
}

h2 {
  margin-bottom: 15px;
}

input {
  width: 100%;
  padding: 8px;
  margin-bottom: 10px;
  border: 1px solid #ccc;
  border-radius: 4px;
}

button {
  width: 100%;
  padding: 10px;
  background: #42b983;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

button:hover {
  background: #368a6d;
}

.switch-link {
  margin-top: 10px;
  font-size: 0.9em;
}

.switch-link a {
  color: #42b983;
  text-decoration: none;
  font-weight: bold;
}
</style>
