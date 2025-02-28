<script setup lang="ts">
import { ref, onMounted } from "vue";
import { getUser, getUserType } from "../services/authService";

const user = ref(getUser());
const userType = ref(getUserType()); // Obtenim si és user o vendor
const role = ref("");

onMounted(() => {
  if (user.value) {
    role.value = userType.value === "vendor" ? "Venedor" : "Comprador"; // Assignem el rol en funció de la taula d'origen
  } else {
    console.error("Usuari no trobat.");
  }
});
</script>

<template>
  <div class="profile-container">
    <h1>Perfil d'Usuari</h1>
    <div v-if="user">
      <p><strong>Nom:</strong> {{ user.name }}</p>
      <p><strong>Email:</strong> {{ user.email }}</p>
      <p><strong>Rol:</strong> {{ role }}</p>
    </div>
    <div v-else>
      <p>Carregant informació...</p>
    </div>
  </div>
</template>

<style scoped>
.profile-container {
  max-width: 500px;
  margin: 50px auto;
  padding: 20px;
  background: white;
  border-radius: 10px;
  box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
  text-align: center;
}
</style>
