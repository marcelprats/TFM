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
    <ul>
      <li v-for="producte in productes" :key="producte.id">
        <router-link :to="'/producte/' + producte.id">{{ producte.nom }}</router-link> - {{ producte.preu }} €
      </li>
    </ul>
  </div>
  <p v-else>Carregant botiga...</p>
</template>

<style scoped>
.infobotiga-page {
  max-width: 800px;
  margin: 100px auto;
  padding: 20px;
  text-align: center;
}
</style>
