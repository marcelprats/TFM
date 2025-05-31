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
  max-width: 420px;
  background: #fafbfc;
  margin: 40px auto;
  padding: 32px 24px 24px 24px;
  border-radius: 14px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
  text-align: center;
  color: #444;
}
.user-details h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #212c3a;
  margin-bottom: 20px;
}
.user-details p {
  margin: 12px 0;
  font-size: 1.08rem;
  color: #222;
}
.user-details strong {
  color: #28a745;
}
</style>
