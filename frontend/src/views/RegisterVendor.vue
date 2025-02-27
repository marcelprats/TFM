<script setup lang="ts">
import { ref } from "vue";
import { registerVendor } from "../services/authService";
import { useRouter } from "vue-router";
import AuthLayout from "../layouts/AuthLayout.vue";

const router = useRouter();
const name = ref("");
const email = ref("");
const password = ref("");
const errorMessage = ref("");

const handleRegister = async () => {
  errorMessage.value = "";
  try {
    await registerVendor({ name: name.value, email: email.value, password: password.value });
    router.push("/login-vendor");
  } catch (error) {
    errorMessage.value = "Error en el registre. Potser el correu ja està registrat.";
  }
};
</script>

<template>
  <AuthLayout title="Registre de Venedors">
    <form @submit.prevent="handleRegister">
      <input type="text" v-model="name" placeholder="Nom" required />
      <input type="email" v-model="email" placeholder="Email" required />
      <input type="password" v-model="password" placeholder="Contrasenya" required />
      <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
      <button type="submit" class="auth-button">Registrar-se com a Venedor</button>
    </form>
    <template #switch-link>
      Ja ets venedor? <router-link to="/login-vendor" class="switch-link">Inicia sessió aquí</router-link>
    </template>
  </AuthLayout>
</template>

<style scoped>
input {
  width: 100%;
  padding: 10px;
  margin: 8px 0;
  border: 1px solid #ccc;
  border-radius: 5px;
  font-size: 14px;
}

.auth-button {
  background: #42b983;
  border: none;
  color: white;
  padding: 10px;
  width: 100%;
  cursor: pointer;
  border-radius: 5px;
  font-size: 14px;
  margin-top: 10px;
}

.auth-button:hover {
  background: #368a6d;
}

.switch-link {
  color: #42b983;
  text-decoration: none;
  font-weight: bold;
}

.switch-link:hover {
  text-decoration: underline;
}

.error {
  color: red;
  margin: 10px 0;
}
</style>
