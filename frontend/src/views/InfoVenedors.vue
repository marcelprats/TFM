<script setup lang="ts">
import { ref, onMounted } from "vue";
import axios from "axios";

const API_URL = "http://127.0.0.1:8000/api";
const venedors = ref([]);

onMounted(async () => {
  try {
    const response = await axios.get(`${API_URL}/vendors`);
    venedors.value = response.data;
  } catch (error) {
    console.error("Error carregant els venedors:", error);
  }
});
</script>

<template>
  <div class="info-venedors">
    <h1>Llista de Venedors</h1>
    <div class="venedor-grid">
      <router-link
        v-for="venedor in venedors"
        :key="venedor.id"
        :to="'/info-venedor/' + venedor.id"
        class="venedor-card"
      >
        <h2>{{ venedor.name }}</h2>
        <p>Email: {{ venedor.email }}</p>

        <p><strong>Botigues:</strong></p>
        <ul>
          <li v-if="venedor.botigues?.length > 0" v-for="botiga in venedor.botigues" :key="botiga.id">
            {{ botiga.nom }}
          </li>
          <li v-else>No té cap botiga</li>
        </ul>
      </router-link>
    </div>
  </div>
</template>

<style scoped>
.info-venedors {
  min-height: 80vh;
  margin: 50px auto;
  padding: 20px;
  text-align: center;
}

.venedor-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 20px;
  margin-top: 20px;
}

.venedor-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  text-align: center;
  text-decoration: none;
  color: black;
  border: 1px solid #ddd;
}

.venedor-card:hover {
  background: #f1f1f1;
}

.venedor-card ul {
  padding-left: 0;
  list-style: none;
}

.venedor-card li {
  margin: 4px 0;
}
</style>
