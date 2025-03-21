<script setup lang="ts">
import { ref, onMounted, nextTick, computed } from "vue";
import axios from "axios";
import { useRoute } from "vue-router";
import L from "leaflet";

const API_URL = "http://127.0.0.1:8000/api";
const route = useRoute();
const botiga = ref(null);
const productes = ref([]);
const horaris = ref([]);
const mapRef = ref(null);
const loading = ref(true);
const errorMessage = ref("");

const diesSetmana = ["Dilluns", "Dimarts", "Dimecres", "Dijous", "Divendres", "Dissabte", "Diumenge"];
const hores = Array.from({ length: 24 }, (_, i) => i);

const showModal = ref(false);

const getHourSegment = (dia, hora, segment) => {
  const franges = horaris.value.filter(h => h.dia.toLowerCase() === dia.toLowerCase());
  const minInSegment = hora * 60 + segment * 15;
  const maxInSegment = minInSegment + 15;

  return franges.some(h => {
    const o = parseInt(h.obertura.slice(0, 2)) * 60 + parseInt(h.obertura.slice(3, 5));
    const t = parseInt(h.tancament.slice(0, 2)) * 60 + parseInt(h.tancament.slice(3, 5));
    return minInSegment < t && maxInSegment > o;
  });
};


// 📌 Carregar botiga i horaris
const fetchBotiga = async () => {
  try {
    const response = await axios.get(`${API_URL}/botigues/${route.params.id}`);
    botiga.value = response.data;
    productes.value = response.data.productes || [];
    horaris.value = response.data.horaris || [];
    loading.value = false;
    await nextTick();
    initMap();
  } catch (error) {
    errorMessage.value = "Error carregant la botiga.";
    console.error("❌ Error carregant botiga:", error);
    loading.value = false;
  }
};

// 📌 Agrupar horaris per dia
const horarisPerDia = computed(() => {
  const result = {};
  diesSetmana.forEach((dia) => {
    result[dia] = horaris.value
      .filter((h) => h.dia.toLowerCase() === dia.toLowerCase())
      .map((h) => `${h.obertura.slice(0, 5)} - ${h.tancament.slice(0, 5)}`)
      .join(", ");
  });
  return result;
});

// 📌 Inicialitzar mapa amb "Com arribar"
const initMap = () => {
  if (!botiga.value || !mapRef.value) return;
  const lat = botiga.value.latitude;
  const lng = botiga.value.longitude;
  if (!lat || !lng) return;

  const map = L.map(mapRef.value).setView([lat, lng], 14);
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors",
  }).addTo(map);

  const marker = L.marker([lat, lng]).addTo(map);
  marker.bindPopup(`
    <b>${botiga.value.nom}</b><br>
    <button onclick="window.open('https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}')" 
    class="btn-maps">📍 Com arribar</button>
  `).openPopup();
  setTimeout(() => {
    map.invalidateSize();
  }, 400);
};

// 📌 Carregar dades
onMounted(() => {
  fetchBotiga();
});
</script>

<template>
  <div class="botiga-container">
    <div v-if="loading" class="loading">🔄 Carregant botiga...</div>
    <div v-else-if="errorMessage" class="error">{{ errorMessage }}</div>
    <div v-else-if="botiga">
              <h1 class="botiga-nom">{{ botiga.nom }}</h1>

      <!-- 📍 Dues columnes -->
      <div class="grid-layout">
        <!-- 🏪 Info + Mapa -->
        <div class="col-left">
          <p class="botiga-desc">{{ botiga.descripcio }}</p>

        </div>

        <!-- 🕒 Horaris -->
        <div class="col-right">
          <h2 class="section-title">📍 Direcció</h2>
                    <div class="map-container">
            <div v-if="botiga.latitude && botiga.longitude" ref="mapRef" class="mapa"></div>
            <div v-else class="no-mapa">⚠️ No hi ha coordenades disponibles.</div>
          </div>
          <h2 class="section-title">
            ⏰ Horari
            <button class="graella-btn" @click="showModal = true">🗓️ Format Graella</button>
          </h2>

          <table class="horari-taula">
            <thead>
              <tr>
                <th>Dia</th>
                <th>Horari</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="dia in diesSetmana" :key="dia">
                <td class="dia">{{ dia }}</td>
                <td class="horari">{{ horarisPerDia[dia] || "Tancat" }}</td>
              </tr>
            </tbody>
          </table>

          <!-- 📊 Modal Graella -->
          <div class="modal-backdrop" v-if="showModal" @click.self="showModal = false">
            <div class="modal-graella">
              <h3>Horari en format graella</h3>
              <button class="close-btn" @click="showModal = false">Tancar</button>
              <table class="horari-taula-graella">
                <thead>
                  <tr>
                    <th>Dia</th>
                    <th v-for="hora in hores" :key="hora" colspan="4">{{ hora }}h</th>
                    <th>Horari</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="dia in diesSetmana" :key="dia">
                    <td class="dia">{{ dia }}</td>
                    <td v-for="hora in hores" :key="hora" colspan="4">
                      <div class="hour-segment" v-for="seg in 4" :key="seg" :class="{ open: getHourSegment(dia, hora, seg - 1) }"></div>
                    </td>
                    <td class="horari">{{ horarisPerDia[dia] || "Tancat" }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

        </div>
      </div>

      <!-- 🛒 Productes -->
      <h2 class="section-title">🛒 Productes disponibles</h2>
      <div v-if="productes.length > 0" class="producte-grid">
        <router-link v-for="producte in productes" :key="producte.id" :to="`/producte/${producte.id}`" class="producte-card">
          <img :src="producte.imatge ? `/img/${producte.imatge}` : '/img/no-imatge.jpg'" class="producte-img" />
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
/* 🔹 Disseny vertical amb dues columnes */
.grid-layout {
  display: flex;
  flex-direction: row;
  gap: 30px;
  margin-bottom: 30px;
}

.col-left, .col-right {
  flex: 1;
}

/* 📍 Mapa */
.map-container {
  width: 100%;
  height: 250px;
}

.mapa {
  width: 100%;
  height: 250px;
  border-radius: 10px;
}

/* 🕒 Taula d'horaris */
.horari-taula {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
  font-size: 1em;
}

.horari-taula th, .horari-taula td {
  border: 1px solid #ddd;
  padding: 8px;
  text-align: center;
}

.horari-taula th {
  background-color: #f8f9fa;
  font-weight: bold;
}

.dia {
  font-weight: bold;
  text-align: left;
}

/* 🛒 Productes */
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

/* 🔹 Responsive */
@media (max-width: 768px) {
  .grid-layout {
    flex-direction: column;
  }

  .col-left, .col-right {
    width: 100%;
  }

  .producte-grid {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  }
}

.graella-btn {
  font-size: 0.8em;
  margin-left: 10px;
  padding: 5px 10px;
  background: #eee;
  border: 1px solid #ccc;
  border-radius: 5px;
  cursor: pointer;
}

.graella-btn:hover {
  background: #ddd;
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.4);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-graella {
  background: white;
  padding: 20px;
  width: 95%;
  max-width: 1000px;
  max-height: 90vh;
  overflow-y: auto;
  border-radius: 10px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.3);
  position: relative;
}

.close-btn {
  position: absolute;
  top: 10px;
  right: 15px;
  background: #d9534f;
  color: white;
  border: none;
  padding: 5px 10px;
  cursor: pointer;
}

.horari-taula-graella .hour-segment {
  width: 100%;
  height: 8px;
  margin: 1px 0;
  background: #eee;
  border-radius: 2px;
}

.horari-taula-graella .hour-segment.open {
  background: #42b983;
}

</style>
