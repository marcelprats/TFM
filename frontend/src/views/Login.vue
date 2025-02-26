<template>
  <div class="auth-container">
    <h2>Iniciar Sessió</h2>
    <form @submit.prevent="login">
      <div>
        <label>Email</label>
        <input v-model="email" type="email" required />
      </div>
      <div>
        <label>Contrasenya</label>
        <input v-model="password" type="password" required />
      </div>
      <button type="submit">Entrar</button>
      <p v-if="error" class="error">{{ error }}</p>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import axios from "axios";
import { useRouter } from "vue-router";

const email = ref("");
const password = ref("");
const error = ref("");
const router = useRouter();

const login = async () => {
  error.value = "";
  try {
    const response = await axios.post("http://127.0.0.1:8000/api/login", {
      email: email.value,
      password: password.value,
    });

    localStorage.setItem("token", response.data.token); // Guarda el token
    router.push("/"); // Redirigeix a la pàgina principal després de l'inici de sessió
  } catch (err) {
    error.value = "Credencials incorrectes. Torna-ho a intentar.";
  }
};
</script>

<style scoped>
.auth-container {
  max-width: 400px;
  margin: auto;
  text-align: center;
}

input {
  width: 100%;
  padding: 8px;
  margin-top: 5px;
}

button {
  margin-top: 10px;
}

.error {
  color: red;
  margin-top: 10px;
}
</style>
