<script setup lang="ts">
import { ref, onMounted } from "vue";
import axios from "axios";

const API_URL = "http://127.0.0.1:8000/api";
const botigues = ref([]);
const token = localStorage.getItem("userToken"); // Obtenim el token desat

onMounted(async () => {
  try {
    const response = await axios.get(`${API_URL}/botigues`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });
    botigues.value = response.data;
  } catch (error) {
    console.error("Error carregant les botigues:", error);
  }
});
</script>


<template>
  <div class="info-botigues">
    <h1>Llista de Botigues</h1>
    <div class="botiga-grid">
      <router-link v-for="botiga in botigues" :key="botiga.id" :to="'/info-botiga/' + botiga.id" class="botiga-card">
        <h2>{{ botiga.nom }}</h2>
      </router-link>
    </div>
  </div>
</template>

<style scoped>
.info-botigues {
  min-height: 80vh;
  margin: 50px auto;
  padding: 20px;
  text-align: center;
  max-width: 1200px;
}

.botiga-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 20px;
  margin-top: 20px;
}

.botiga-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  text-align: center;
  text-decoration: none;
  color: black;
  border: 1px solid #ddd;
  transition: background 0.2s ease;
}

.botiga-card:hover {
  background: #f1f1f1;
}

/* Responsive optimitzat per mòbil i tablets */
@media (max-width: 768px) {
  .info-botigues {
    padding: 10px;
  }

  .botiga-card {
    padding: 16px;
  }
}
</style>
