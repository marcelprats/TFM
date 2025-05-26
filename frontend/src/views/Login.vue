<script setup lang="ts">
import { ref } from "vue";
import { loginUser } from "../services/authService";
import { useRouter } from "vue-router";

const router = useRouter();
const email = ref("");
const password = ref("");
const isVendor = ref(false); // Per defecte és comprador
const errorMessage = ref("");

const handleLogin = async () => {
  errorMessage.value = "";
  try {
    const user = await loginUser(email.value, password.value, isVendor.value);
    console.log("Usuari loguejat:", user);
    router.push("/home"); // Redirigir a la pàgina d'inici
  } catch (error) {
    errorMessage.value = "Credencials incorrectes";
  }
};
</script>

<template>
  <div class="login-container">
    <h1>Iniciar Sessió</h1>
    <form @submit.prevent="handleLogin">
      <input type="email" v-model="email" placeholder="Email" required />
      <input type="password" v-model="password" placeholder="Contrasenya" required />

      <!-- Boto per escollir entre comprador o venedor -->
      <div class="switch-container">
        <label>
          <input type="checkbox" v-model="isVendor" />
          <span>{{ isVendor ? "Venedor" : "Comprador" }}</span>
        </label>
      </div>

      <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
      <button type="submit" class="auth-button">Entrar</button>
    </form>

    <p>
      No tens compte?
      <router-link to="/register">Registra't</router-link>
    </p>
  </div>
</template>

<style scoped>
.login-container {
  max-width: 400px;
  margin: auto;
  padding: 20px;
  text-align: center;
}

input {
  width: 100%;
  padding: 10px;
  margin: 8px 0;
  border: 1px solid #ccc;
  border-radius: 5px;
}

.auth-button {
  background: #42b983;
  border: none;
  color: white;
  padding: 10px;
  width: 100%;
  cursor: pointer;
  border-radius: 5px;
  margin-top: 10px;
}

.auth-button:hover {
  background: #f9f9f9;
  color: #42b983;
  outline: 2px solid #42b983;
}


.switch-container {
  display: flex;
  justify-content: center;
  margin-top: 10px;
}

.error {
  color: red;
  margin: 10px 0;
}
</style>
