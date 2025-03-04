<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRoute } from "vue-router";
import axios from "axios";

const route = useRoute();
const botiga = ref(null);
const productes = ref([]);
const API_URL = "http://127.0.0.1:8000/api";

onMounted(async () => {
  const botigaId = route.params.id; // ID de la botiga passat per l'URL

  try {
    const response = await axios.get(`${API_URL}/botigues/${botigaId}`);
    botiga.value = response.data;
    productes.value = response.data.productes;
  } catch (error) {
    console.error("Error carregant la botiga:", error);
  }
});
</script>

<template>
  <div class="infobotiga-page" v-if="botiga">
    <h1>{{ botiga.nom }}</h1>
    <p><strong>Descripció:</strong> {{ botiga.descripcio }}</p>

    <h2>Productes disponibles</h2>

    <div class="product-grid">
      <div v-for="producte in productes" :key="producte.id" class="product-card">
        <router-link :to="'/producte/' + producte.id">
          <h3>{{ producte.nom }}</h3>
          <p class="price">{{ producte.preu }} €</p>
        </router-link>
      </div>
    </div>
  </div>
  <p v-else>Carregant botiga...</p>
</template>

<style scoped>
.infobotiga-page {
  min-height: 80vh; /* Ajusta segons sigui necessari */
  margin: 50px auto;
  padding: 20px;
  text-align: center;
}

/* Disseny de graella amb 5 columnes */
.product-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr); /* 5 columnes */
  gap: 20px;
  margin-top: 20px;
  padding: 10px;
}

/* Estil per a cada targeta de producte */
.product-card {
  background: white;
  border-radius: 10px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  padding: 15px;
  text-align: center;
  border: 1px solid #ddd;
  transition: 0.3s;
}

.product-card:hover {
  transform: scale(1.05);
}

.product-card h3 {
  font-size: 18px;
  margin-bottom: 8px;
}

.product-card .price {
  font-size: 16px;
  font-weight: bold;
  color: #42b983;
}
</style>
