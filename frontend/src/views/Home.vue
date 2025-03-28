<script setup lang="ts">
import { ref, onMounted } from "vue";
import axios from "axios";

// Definim les referències per a usuaris i venedors
const users = ref<{ id: number; name: string; email: string }[]>([]);
const vendors = ref<{ id: number; name: string; email: string }[]>([]);

onMounted(async () => {
  try {
    // Carregar usuaris registrats
    const usersResponse = await axios.get("http://127.0.0.1:8000/api/users");
    users.value = usersResponse.data;

    // Carregar venedors registrats
    const vendorsResponse = await axios.get("http://127.0.0.1:8000/api/vendors");
    vendors.value = vendorsResponse.data;
  } catch (error) {
    console.error("Error carregant dades:", error);
  }
});
</script>

<template>
  <div class="home">
    <h1 class="text-3xl font-bold text-center mt-8">
      Benvingut a <span class="text-primary">Totaki</span>, la plataforma pel comerç local!
    </h1>
    <div class="lists-container">
      <!-- Llista d'Usuaris Registrats -->
      <div class="user-list">
        <h2>Usuaris Registrats</h2>
        <ul v-if="users.length">
          <li v-for="user in users" :key="user.id">
            {{ user.id }} - {{ user.name }} 
          </li>
        </ul>
        <p v-else>No hi ha usuaris registrats.</p>
      </div>

      <!-- Llista de Venedors Registrats -->
      <div class="vendor-list">
        <h2>Venedors Registrats</h2>
        <ul v-if="vendors.length">
          <li v-for="vendor in vendors" :key="vendor.id">
            {{ vendor.id }} - {{ vendor.name }}
          </li>
        </ul>
        <p v-else>No hi ha venedors registrats.</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.home {
  text-align: center;
  margin-top: 50px;
}

/* Contenidor de les dues llistes */
.lists-container {
  display: flex;
  justify-content: center;
  gap: 30px;
  margin-top: 30px;
}

/* Estil comú per a les dues llistes */
.user-list, .vendor-list {
  padding: 20px;
  border-radius: 10px;
  background: white;
  box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
  width: 300px;
  text-align: center;
}

ul {
  list-style: none;
  padding: 0;
}

li {
  padding: 10px;
  border-bottom: 1px solid #ddd;
}

li:last-child {
  border-bottom: none;
}
</style>
