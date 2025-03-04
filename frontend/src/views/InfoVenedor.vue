<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRoute } from "vue-router";
import axios from "axios";

const route = useRoute();
const venedor = ref(null);
const botigues = ref([]);
const API_URL = "http://127.0.0.1:8000/api";

onMounted(async () => {
  const venedorId = route.params.id; // ID del venedor passat per l'URL

  try {
    const response = await axios.get(`${API_URL}/vendors/${venedorId}`);
    console.log("Dades rebudes de l'API:", response.data); // 🔍 Debugging

    venedor.value = response.data;
    botigues.value = response.data.botigues || []; // Assegurar que sigui un array
  } catch (error) {
    console.error("Error carregant el venedor:", error);
  }
});
</script>

<template>
  <div class="info-venedor-page" v-if="venedor">
    <h1>{{ venedor.name }}</h1>
    <p><strong>Email:</strong> {{ venedor.email }}</p>

    <h2>Botigues associades</h2>
    <div class="botiga-grid">
      <div v-for="botiga in botigues" :key="botiga.id" class="botiga-card">
        <router-link :to="'/info-botiga/' + botiga.id">
          <h3>{{ botiga.nom }}</h3>
        </router-link>
        <p>{{ botiga.descripcio }}</p>

        <h4>Productes disponibles:</h4>
        <ul>
          <li v-for="producte in botiga.productes" :key="producte.id">
            <router-link :to="'/producte/' + producte.id">
              {{ producte.nom }} - {{ producte.preu }} €
            </router-link>
          </li>
        </ul>
      </div>
    </div>
  </div>
  <p v-else>Carregant venedor...</p>
</template>

<style scoped>
.info-venedor-page {
  min-height: 80vh; /* Ajusta segons sigui necessari */
  margin: 80px auto;
  padding: 20px;
  text-align: center;
}

.botiga-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
  margin-top: 20px;
}

.botiga-card {
  background: white;
  border-radius: 10px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  padding: 20px;
  text-align: center;
  border: 1px solid #ddd;
  transition: 0.3s;
  text-decoration: none;
  color: inherit;
}

.botiga-card:hover {
  transform: scale(1.05);
}
</style>
