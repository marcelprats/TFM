<script setup lang="ts">
import { ref, onMounted } from "vue";
import { isLoggedIn, getUser, logout, fetchUser } from "../services/authService";

const user = ref(getUser());

onMounted(async () => {
  if (isLoggedIn() && !user.value) {
    user.value = await fetchUser();
  }
});

const handleLogout = () => {
  logout();
};
</script>

<template>
  <div class="home">
    <h1>Benvingut a l'aplicació!</h1>

    <div v-if="user">
      <p>Estàs loguejat com <strong>{{ user.email }}</strong></p>
      <button @click="handleLogout">Tancar Sessió</button>
    </div>

    <div v-else>
      <p>Per començar, fes login o registra't.</p>
      <button @click="$router.push('/login')">Iniciar Sessió</button>
      <button @click="$router.push('/register')">Registrar-se</button>
    </div>
  </div>
</template>

<style scoped>
.home {
  text-align: center;
  margin-top: 50px;
}

button {
  margin: 10px;
  padding: 10px;
  border: none;
  background: #42b983;
  color: white;
  cursor: pointer;
  border-radius: 5px;
}

button:hover {
  background: #368a6d;
}
</style>
