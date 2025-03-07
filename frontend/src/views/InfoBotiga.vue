<script setup lang="ts">
import { ref, onMounted, nextTick } from "vue";
import axios from "axios";
import { useRoute } from "vue-router";
import L from "leaflet";

const API_URL = "http://127.0.0.1:8000/api";
const LOCATIONIQ_API_KEY = "pk.21503103ff47bf3d7ba293114731d792";
const route = useRoute();
const botiga = ref(null);
const productes = ref([]);
const mapRef = ref(null);
const markerRef = ref(null);
const loading = ref(true);
const errorMessage = ref("");

const fetchBotiga = async () => {
  try {
    const response = await axios.get(`${API_URL}/botigues/${route.params.id}`);
    botiga.value = response.data;
    productes.value = response.data.productes || [];
    loading.value = false;

    await nextTick(); // Esperem que el DOM es carregui abans d'inicialitzar el mapa
    initMap();
  } catch (error) {
    errorMessage.value = "Error carregant la botiga.";
    console.error("❌ Error carregant botiga:", error);
    loading.value = false;
  }
};

const initMap = () => {
  if (!botiga.value || !mapRef.value) return;

  const lat = botiga.value.latitude;
  const lng = botiga.value.longitude;

  if (!lat || !lng) {
    console.warn("⚠️ No hi ha coordenades per a aquesta botiga.");
    return;
  }

  const map = L.map(mapRef.value).setView([lat, lng], 14);
  L.tileLayer(`https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png`, {
    attribution: "&copy; OpenStreetMap contributors",
  }).addTo(map);

  const marker = L.marker([lat, lng])
    .addTo(map)

  markerRef.value = marker;

  setTimeout(() => {
    map.invalidateSize();
  }, 400);
};

const getImatgeProducte = (producte) => {
  return producte.imatge ? `/img/${producte.imatge}` : "/img/no-imatge.jpg";
};

onMounted(() => {
  fetchBotiga();
});
</script>

<template>
  <div class="botiga-container">
    <div v-if="loading" class="loading">🔄 Carregant botiga...</div>
    <div v-else-if="errorMessage" class="error">{{ errorMessage }}</div>
    <div v-else-if="botiga">
      <!-- Info + Mapa -->
      <div class="info-mapa">
        <div class="info">
          <h1 class="botiga-nom">{{ botiga.nom }}</h1>
          <p class="botiga-desc">{{ botiga.descripcio }}</p>
        </div>
        <div class="map-container">
          <div v-if="botiga.latitude && botiga.longitude" ref="mapRef" class="mapa"></div>
          <div v-else class="no-mapa">⚠️ No hi ha coordenades disponibles.</div>
        </div>
      </div>

      <!-- Productes -->
      <h2 class="section-title">🛒 Productes disponibles</h2>
      <div v-if="productes.length > 0" class="producte-grid">
        <router-link
          v-for="producte in productes"
          :key="producte.id"
          :to="`/producte/${producte.id}`"
          class="producte-card"
        >
          <img
            class="producte-img"
            :src="getImatgeProducte(producte)"
            alt="Imatge del producte"
          />
          <div class="producte-info">
            <h3>{{ producte.nom }}</h3>
            <p class="preu">{{ producte.preu }} €</p>
          </div>
        </router-link>
      </div>
      <p v-else class="no-productes">❌ Aquesta botiga encara no té productes.</p>
    </div>
  </div>
</template>

<style scoped>
.botiga-container {
  max-width: 1100px;
  margin: auto;
  padding: 20px;
}

/* INFO + MAPA */
.info-mapa {
  display: flex;
  align-items: center;
  gap: 30px;
}

.info {
  flex: 1;
}

.botiga-nom {
  font-size: 2em;
  color: #2c3e50;
  margin-bottom: 10px;
}

.botiga-desc {
  font-size: 1.2em;
  color: #555;
  margin-bottom: 20px;
}

.map-container {
  flex: 1;
}

.mapa {
  width: 100%;
  height: 300px;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.no-mapa {
  background: #f8d7da;
  color: #721c24;
  padding: 10px;
  text-align: center;
  border-radius: 10px;
}

/* PRODUCTES */
.section-title {
  font-size: 1.5em;
  margin-top: 30px;
  color: #2c3e50;
  text-align: center;
}

.producte-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  margin-top: 20px;
}

.producte-card {
  background: white;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  text-align: center;
  transition: transform 0.2s ease-in-out;
  text-decoration: none;
  color: inherit;
}

.producte-card:hover {
  transform: scale(1.05);
}

.producte-img {
  width: 100%;
  height: 160px;
  object-fit: cover;
  border-bottom: 2px solid #ddd;
}

.producte-info {
  padding: 10px;
}

.preu {
  font-weight: bold;
  color: #42b983;
  margin-top: 5px;
}

.no-productes {
  font-size: 1.2em;
  color: #888;
  margin-top: 20px;
  text-align: center;
}

.loading,
.error {
  font-size: 1.5em;
  color: #d9534f;
  margin-top: 20px;
  text-align: center;
}
</style>
