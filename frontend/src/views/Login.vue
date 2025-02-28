<script setup lang="ts">
import { ref } from "vue";
import { loginUser, loginVendor } from "../services/authService"; // 🆕 Importem els dos login
import { useRouter } from "vue-router";
import AuthLayout from "../layouts/AuthLayout.vue";

const router = useRouter();
const email = ref("");
const password = ref("");
const errorMessage = ref("");
const isVendorLogin = ref(false); // 🆕 Switch per a canviar entre usuaris i venedors

const handleLogin = async () => {
  errorMessage.value = "";

  try {
    if (isVendorLogin.value) {
      await loginVendor(email.value, password.value); // 🆕 Login de venedor
      router.push("/perfil");
    } else {
      await loginUser(email.value, password.value); // 🆕 Login de comprador
      router.push("/perfil");
    }
  } catch (error) {
    errorMessage.value = "Credencials incorrectes.";
  }
};
</script>

<template>
  <AuthLayout title="Iniciar Sessió">
    <form @submit.prevent="handleLogin">
      <input type="email" v-model="email" placeholder="Email" required />
      <input type="password" v-model="password" placeholder="Contrasenya" required />

      <!-- 🆕 Switch per canviar entre 'Comprador' i 'Venedor' -->
      <div class="switch-container">
        <label>
          <input type="checkbox" v-model="isVendorLogin" />
          Iniciar sessió com a venedor
        </label>
      </div>

      <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
      <button type="submit" class="auth-button">Iniciar Sessió</button>
    </form>

    <template #switch-link>
      No tens compte?
      <router-link to="/register" class="switch-link">Registra't aquí</router-link>
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

.switch-container {
  margin: 10px 0;
  display: flex;
  align-items: center;
  gap: 10px;
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
