<template>
  <div class="user-details">
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

<script setup lang="ts">
const { user, role } = defineProps<{
  user: { name: string; email: string } | null;
  role: string;
}>();
</script>

<style scoped>
.user-details {
  text-align: center;
  color: #444;
}
.user-details h1 {
  margin-bottom: 20px;
}
.user-details p {
  margin: 5px 0;
}
</style>
