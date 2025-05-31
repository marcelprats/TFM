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
    router.push("/home");
  } catch (error) {
    errorMessage.value = "Credencials incorrectes";
  }
};
</script>

<template>
  <div class="login-container">
    <h1>Iniciar Sessió</h1>
    <form @submit.prevent="handleLogin">
      <input type="email" v-model="email" placeholder="Email" required autocomplete="username" />
      <input type="password" v-model="password" placeholder="Contrasenya" required autocomplete="current-password" />

      <!-- Switch modern i bonic -->
      <div class="switch-container">
        <span :class="['role-label', !isVendor && 'active']">Comprador</span>
        <label class="switch">
          <input type="checkbox" v-model="isVendor" />
          <span class="slider"></span>
        </label>
        <span :class="['role-label', isVendor && 'active']">Venedor</span>
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
  max-width: 410px;
  width: 100%;
  margin: 32px auto;
  padding: 32px 14px 24px 14px;
  text-align: center;
  background: #fff;
  border-radius: 18px;
  /* border i shadow eliminats per aspecte més net */
}

h1 {
  font-size: 2.45rem;
  font-weight: 700;
  color: #25324d;
  margin-bottom: 22px;
  letter-spacing: -1px;
}

input[type="email"],
input[type="password"] {
  width: 100%;
  box-sizing: border-box;
  padding: 13px 14px;
  margin: 10px 0;
  background: #fff9cc;
  border: none;
  border-radius: 7px;
  font-size: 1.08rem;
  transition: box-shadow .2s;
  outline: none;
}
input[type="email"]:focus,
input[type="password"]:focus {
  box-shadow: 0 0 0 2px #42b98355;
}

.auth-button {
  background: #42b983;
  border: none;
  color: white;
  padding: 13px 0;
  width: 100%;
  cursor: pointer;
  border-radius: 7px;
  margin-top: 18px;
  font-size: 1.20rem;
  font-weight: 600;
  transition: background .2s, color .2s;
}

.auth-button:hover {
  background: #fff;
  color: #42b983;
  outline: 2px solid #42b983;
}

.switch-container {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 18px;
  margin: 19px 0 8px 0;
  user-select: none;
}

/* Etiquetes actives/desactives */
.role-label {
  font-size: 1.08rem;
  color: #aaa;
  font-weight: 500;
  transition: color .2s;
}
.role-label.active {
  color: #42b983;
}

/* SWITCH bonic */
.switch {
  position: relative;
  display: inline-block;
  width: 52px;
  height: 28px;
  vertical-align: middle;
}
.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}
.slider {
  position: absolute;
  cursor: pointer;
  background-color: #e0e0e0;
  border-radius: 20px;
  top: 0; left: 0; right: 0; bottom: 0;
  transition: background .26s;
}
.slider:before {
  position: absolute;
  content: "";
  height: 22px;
  width: 22px;
  left: 3px;
  bottom: 3px;
  background-color: #fff;
  border-radius: 50%;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  transition: transform .26s;
}
.switch input:checked + .slider {
  background-color: #42b983;
}
.switch input:checked + .slider:before {
  transform: translateX(24px);
}

.error {
  color: #e53935;
  margin: 12px 0 0 0;
  font-weight: 500;
}

/* Responsive */
@media (max-width: 550px) {
  .login-container {
    max-width: 97vw;
    padding: 18px 2vw 16px 2vw;
    border-radius: 11px;
  }
  h1 {
    font-size: 2.1rem;
  }
  .auth-button {
    font-size: 1.06rem;
    padding: 11px 0;
  }
}
</style>