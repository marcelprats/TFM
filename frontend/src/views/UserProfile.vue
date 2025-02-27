<script setup lang="ts">
import { ref, onMounted } from "vue";
import { getUser, isLoggedIn, fetchUser } from "../services/authService";
import { useRouter } from "vue-router";

const router = useRouter();
const user = ref(getUser()); // Inicialitzem amb el que tenim guardat

onMounted(async () => {
  if (!isLoggedIn()) {
    router.push("/login"); // Redirigir si no està logejat
  } else {
    user.value = await fetchUser(); // ⬅️ Forcem la càrrega de l'usuari
  }
});
</script>

<template>
  <div class="profile-container">
    <h1>Perfil d'Usuari</h1>

    <div v-if="user">
      <p><strong>Nom:</strong> {{ user.name }}</p>
      <p><strong>Email:</strong> {{ user.email }}</p>
    </div>

    <div v-else>
      <p>Carregant informació...</p>
    </div>
  </div>
</template>

<style scoped>
.profile-container {
  max-width: 500px;
  margin: 80px auto;
  padding: 20px;
  background: white;
  border-radius: 10px;
  box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
  text-align: center;
}
</style>
