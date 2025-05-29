<template>
  <div class="botiga-container">
    <div v-if="loading" class="loading">🔄 Carregant botiga...</div>
    <div v-else-if="errorMessage" class="error">{{ errorMessage }}</div>
    <div v-else>
      <h1 class="botiga-nom">{{ botiga.nom }}</h1>

      <div class="grid-layout">
        <!-- Columna esquerra: resum i descripció -->
        <div class="col-left">
          <section class="store-summary">
            <h2>Valoracions</h2>
            <ul>
              <li>Ambient: {{ summary.ambient > 0 ? summary.ambient.toFixed(2) + '★' : '-' }}</li>
              <li>Personal: {{ summary.personal > 0 ? summary.personal.toFixed(2) + '★' : '-' }}</li>
              <li>Recollida: {{ summary.recollida > 0 ? summary.recollida.toFixed(2) + '★' : '-' }}</li>
            </ul>
          </section>

          <h2>Descripció</h2>
          <p class="botiga-desc">{{ botiga.descripcio }}</p>
        </div>

        <!-- Columna dreta: mapa i horaris -->
        <div class="col-right">
          <h2 class="section-title">📍 Direcció</h2>
          <div class="map-container">
            <div
              v-if="botiga.latitude && botiga.longitude"
              ref="mapRef"
              class="mapa"
            ></div>
            <div v-else class="no-mapa">⚠️ No hi ha coordenades disponibles.</div>
          </div>

          <h2 class="section-title">
            ⏰ Horari
            <button class="graella-btn" @click="showModal = true">
              🗓️ Format Graella
            </button>
          </h2>
          <table class="horari-taula">
            <thead>
              <tr><th>Dia</th><th>Horari</th></tr>
            </thead>
            <tbody>
              <tr v-for="dia in diesSetmana" :key="dia">
                <td class="dia">{{ dia }}</td>
                <td class="horari">{{ horarisPerDia[dia] || 'Tancat' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Productes -->
      <h2 class="section-title">🛒 Productes disponibles</h2>
      <div v-if="productes.length" class="producte-grid">
        <router-link
          v-for="p in productes"
          :key="p.id"
          :to="`/producte/${p.id}`"
          class="producte-card"
        >
          <img
            :src="getImageSrc(p.imatge)"
            class="producte-img"
            alt="Imatge producte"
          />
          <div class="producte-info">
            <h3>{{ p.nom }}</h3>
            <p class="preu">{{ p.preu }} €</p>
          </div>
        </router-link>
      </div>
      <p v-else class="no-productes">
        ❌ Aquesta botiga encara no té productes.
      </p>

      <!-- Modal horari graella -->
      <div
        class="modal-backdrop"
        v-if="showModal"
        @click.self="showModal = false"
      >
        <div class="modal-graella">
          <h3>Horari en format graella</h3>
          <button class="close-btn" @click="showModal = false">
            Tancar
          </button>
          <table class="horari-taula-graella">
            <thead>
              <tr>
                <th>Dia</th>
                <th v-for="h in hores" :key="h" colspan="4">{{ h }}h</th>
                <th>Horari</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="dia in diesSetmana" :key="dia">
                <td class="dia">{{ dia }}</td>
                <td v-for="hora in hores" :key="hora" colspan="4">
                  <div
                    v-for="seg in 4"
                    :key="seg"
                    class="hour-segment"
                    :class="{ open: getHourSegment(dia, hora, seg - 1) }"
                  />
                </td>
                <td class="horari">{{ horarisPerDia[dia] || 'Tancat' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick, computed } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import L from 'leaflet'

// Reactivity
const route = useRoute()
const botiga = ref<any>(null)
const summary = ref({ ambient: 0, personal: 0, recollida: 0 })
const productes = ref<any[]>([])
const horaris = ref<any[]>([])
const mapRef = ref<HTMLElement | null>(null)
const loading = ref(true)
const errorMessage = ref('')
const showModal = ref(false)

// Consts
const diesSetmana = ['Dilluns','Dimarts','Dimecres','Dijous','Divendres','Dissabte','Diumenge']
const hores = Array.from({ length: 24 }, (_, i) => i)

// Computed: agrupa horaris per dia en text
const horarisPerDia = computed<Record<string,string>>(() => {
  const out: Record<string,string> = {}
  for (const dia of diesSetmana) {
    const franjes = horaris.value
      .filter(h => h.dia.toLowerCase() === dia.toLowerCase())
      .map(h => `${h.obertura.slice(0,5)} - ${h.tancament.slice(0,5)}`)
    out[dia] = franjes.join(', ')
  }
  return out
})

// Comprova si un segment de 15 min està obert
function getHourSegment(dia: string, hora: number, seg: number): boolean {
  const minSeg = hora * 60 + seg * 15
  const maxSeg = minSeg + 15
  return horaris.value.some(h => {
    const o = parseInt(h.obertura.slice(0,2))*60 + parseInt(h.obertura.slice(3,5))
    const t = parseInt(h.tancament.slice(0,2))*60 + parseInt(h.tancament.slice(3,5))
    return minSeg < t && maxSeg > o
  })
}

// Retorna URL completa de la imatge
function getImageSrc(path: string|null): string {
  const base = import.meta.env.VITE_BACKEND_URL || 'http://127.0.0.1:8000'
  if (!path) return '/img/no-imatge.jpg'
  return path.startsWith('/') ? base + path : base + '/uploads/' + path
}

// Inicialitza el mapa Leaflet
function initMap() {
  if (!botiga.value || !mapRef.value) return
  const { latitude: lat, longitude: lng } = botiga.value
  if (!lat || !lng) return

  const map = L.map(mapRef.value).setView([lat, lng], 14)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map)

  L.marker([lat, lng])
    .addTo(map)
    .bindPopup(`<b>${botiga.value.nom}</b>`)
    .openPopup()

  setTimeout(() => map.invalidateSize(), 300)
}

// Carrega dades de la botiga i del seu resum
async function fetchBotiga() {
  loading.value = true
  try {
    const id = route.params.id
    const [bRes, sRes] = await Promise.all([
      axios.get(`/botigues/${id}`),
      axios.get(`/botigues/${id}/store-summary`)
    ])
    botiga.value = bRes.data
    summary.value.ambient   = sRes.data.ambient?.avg   ?? 0
    summary.value.personal  = sRes.data.personal?.avg  ?? 0
    summary.value.recollida = sRes.data.recollida?.avg ?? 0
    productes.value = bRes.data.productes  || []
    horaris.value   = bRes.data.horaris    || []
    await nextTick()
    initMap()
  } catch (e) {
    console.error('Error carregant la botiga:', e)
    errorMessage.value = 'No s’ha pogut carregar la botiga.'
  } finally {
    loading.value = false
  }
}
onMounted(fetchBotiga)
</script>

<style scoped>
/* Disseny vertical amb dues columnes */

.botiga-container {
  padding: 20px;
}

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
