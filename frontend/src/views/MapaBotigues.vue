<template>
  <div class="map-wrapper">
    <h1>Mapa de Botigues</h1>

    <!-- 🔍 Filtres -->
    <div class="filters">
      <button
        @click="toggleObertAra"
        :class="{ active: obertAra }"
      >
        ✅ Obert ara
      </button>
      <button
        @click="toggleMostrarHorari"
        :class="{ active: mostrarHorari }"
      >
        🕒 Horari
      </button>

      <template v-if="mostrarHorari">
        <select v-model="filtreDia">
          <option value="">Tots els dies</option>
          <option v-for="d in diesSetmana" :key="d" :value="d">{{ d }}</option>
        </select>
        <select v-model="filtreHora">
          <option value="">Totes les hores</option>
          <option v-for="h in hores" :key="h" :value="parseInt(h)">{{ h }}h</option>
        </select>
      </template>

      <button @click="obtenirUbicacio">📍 La meva ubicació</button>
    </div>

    <!-- 🗺️ Mapa + Llista -->
    <div class="map-layout">
      <div id="mapa" class="mapa-container"></div>

      <aside class="sidebar-list">
        <div class="order-buttons">
          <button @click="orderBy = 'nom'" :class="{ active: orderBy === 'nom' }">🔤 Nom</button>
          <button @click="handleOrderByDistancia" :class="{ active: orderBy === 'distancia' }">📍 Distància</button>
        </div>

        <div class="sidebar-top">
          <input
            v-model="llistaQuery"
            type="text"
            placeholder="🔍 Cerca una botiga..."
            class="search-sidebar"
          />
        </div>

        <div class="sidebar-scroll">
          <ul class="botiga-cards">
            <li
              v-for="b in botiguesFiltrades"
              :key="b.id"
              :id="`botiga-${b.id}`"
              class="botiga-card"
              :class="{ selected: selectedBotigaId === b.id }"
              @click="toggleBotigaDetall(b)"
            >
              <div class="botiga-header">
                <h4 class="botiga-nom">{{ b.nom }}</h4>
                <p class="botiga-distancia" v-if="distancies[b.id]">📍 {{ distancies[b.id] }} km</p>
              </div>

              <div v-if="selectedBotigaId === b.id" class="detall-botiga">
                <table class="horari-taula">
                  <tbody>
                    <tr v-for="dia in diesSetmana" :key="dia">
                      <td class="dia"><strong>{{ dia }}</strong></td>
                      <td class="horari">{{ horarisPerBotiga(b)[dia] }}</td>
                    </tr>
                  </tbody>
                </table>
                <a :href="`/info-botiga/${b.id}`" class="detall-enllac">🔗 Veure informació completa</a>
              </div>
            </li>
          </ul>
        </div>
      </aside>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed, nextTick } from 'vue'
import axios from 'axios'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

// State
const botigues = ref<any[]>([])
const filtreDia = ref('')
const filtreHora = ref('')
const obertAra = ref(false)
const mostrarHorari = ref(false)
const llistaQuery = ref('')
const userLocation = ref<{ lat: number; lng: number } | null>(null)
const distancies = ref<Record<number, number>>({})
const map = ref<L.Map | null>(null)
const orderBy = ref<'nom' | 'distancia'>('nom')
const selectedBotigaId = ref<number | null>(null)

// Constants
const diesSetmana = [
  'Dilluns','Dimarts','Dimecres','Dijous',
  'Divendres','Dissabte','Diumenge'
]
const hores = Array.from({ length: 24 }, (_, i) => `${i}`)

// Toggle filters
function toggleObertAra() {
  obertAra.value = !obertAra.value
  if (obertAra.value) {
    mostrarHorari.value = false
    filtreDia.value = ''
    filtreHora.value = ''
  }
}
function toggleMostrarHorari() {
  mostrarHorari.value = !mostrarHorari.value
  if (mostrarHorari.value) {
    obertAra.value = false
  } else {
    filtreDia.value = ''
    filtreHora.value = ''
  }
}

// Order by distance
function handleOrderByDistancia() {
  if (!userLocation.value) obtenirUbicacio()
  orderBy.value = 'distancia'
}

// Filtered list
const botiguesFiltrades = computed(() => {
  const q = llistaQuery.value.toLowerCase()
  const ara = new Date()
  const diaActual = diesSetmana[(ara.getDay() + 6) % 7]
  const minutsActuals = ara.getHours() * 60 + ara.getMinutes()

  return botigues.value
    .filter(b => b.nom.toLowerCase().includes(q))
    .filter(b => {
      const horaris = Array.isArray(b.horaris) ? b.horaris : []
      if ((mostrarHorari.value || obertAra.value) && horaris.length === 0) return false
      if (obertAra.value) {
        return horaris.some((h: any) =>
          h.dia.toLowerCase() === diaActual.toLowerCase() &&
          parseInt(h.obertura) * 60 <= minutsActuals &&
          minutsActuals < parseInt(h.tancament) * 60
        )
      }
      if (mostrarHorari.value) {
        return horaris.some((h: any) => {
          const diaOk = !filtreDia.value || h.dia.toLowerCase() === filtreDia.value.toLowerCase()
          const horaOk =
            !filtreHora.value ||
            (parseInt(h.obertura) <= parseInt(filtreHora.value) &&
             parseInt(filtreHora.value) < parseInt(h.tancament))
          return diaOk && horaOk
        })
      }
      return true
    })
    .sort((a, b) => {
      if (orderBy.value === 'distancia') {
        return (distancies.value[a.id] || Infinity) - (distancies.value[b.id] || Infinity)
      }
      return a.nom.localeCompare(b.nom)
    })
})

// Load shops from API
async function carregarBotigues() {
  try {
    const res = await axios.get('/botigues')
    botigues.value = res.data
    await nextTick()
    renderMap()
  } catch (e) {
    console.error('Error carregant botigues:', e)
  }
}

// Distance calculation
function calcularDistancia(lat1: number, lon1: number, lat2: number, lon2: number) {
  const R = 6371
  const dLat = (lat2 - lat1) * Math.PI / 180
  const dLon = (lon2 - lon1) * Math.PI / 180
  const a =
    Math.sin(dLat / 2) ** 2 +
    Math.cos(lat1 * Math.PI / 180) *
    Math.cos(lat2 * Math.PI / 180) *
    Math.sin(dLon / 2) ** 2
  return Math.round(R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)) * 10) / 10
}

// Format schedules
function horarisPerBotiga(b: any) {
  const out: Record<string, string> = {}
  diesSetmana.forEach(d => {
    const hDia = (Array.isArray(b.horaris) ? b.horaris : [])
      .filter((h: any) => h.dia.toLowerCase() === d.toLowerCase())
    out[d] = hDia.length
      ? hDia.map((h: any) => `${h.obertura.slice(0,5)} - ${h.tancament.slice(0,5)}`)
          .join(', ')
      : 'Tancat'
  })
  return out
}

// Render Leaflet map
function renderMap() {
  map.value?.remove()
  map.value = L.map('mapa').setView([41.3851, 2.1734], 13)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
  }).addTo(map.value!)

  const coords: [number,number][] = []

  if (userLocation.value) {
    const { lat, lng } = userLocation.value
    coords.push([lat, lng])
    const redIcon = L.icon({
      iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
      shadowUrl: 'https://unpkg.com/leaflet@1.9.3/dist/images/marker-shadow.png',
      iconSize: [25,41], iconAnchor: [12,41], popupAnchor: [1,-34], shadowSize: [41,41]
    })
    L.marker([lat, lng], { icon: redIcon })
      .addTo(map.value!)
      .bindPopup('📍 Estàs aquí')
      .openPopup()
  }

  botiguesFiltrades.value.forEach(b => {
    if (!b.latitude || !b.longitude) return
    coords.push([b.latitude, b.longitude])
    if (userLocation.value) {
      distancies.value[b.id] = calcularDistancia(
        userLocation.value.lat, userLocation.value.lng,
        b.latitude, b.longitude
      )
    }
    const m = L.marker([b.latitude, b.longitude]).addTo(map.value!)
    m.bindPopup(`<b><a href="/info-botiga/${b.id}">${b.nom}</a></b>`)
    m.on('click', () => toggleBotigaDetall(b))
    b.marker = m
  })

  if (coords.length) {
    map.value!.fitBounds(coords, { padding: [30,30] })
  }
}

// Get user location
function obtenirUbicacio() {
  navigator.geolocation.getCurrentPosition(
    pos => {
      userLocation.value = { lat: pos.coords.latitude, lng: pos.coords.longitude }
      orderBy.value = 'distancia'
      renderMap()
    },
    err => console.error(err)
  )
}

// Toggle shop detail
async function toggleBotigaDetall(b: any) {
  selectedBotigaId.value = selectedBotigaId.value === b.id ? null : b.id
  await nextTick()

  if (b.marker && map.value) {
    map.value.flyTo([b.latitude, b.longitude], 16, { animate: true })
    b.marker.openPopup()
  }

  const cardEl = document.getElementById(`botiga-${b.id}`)
  if (cardEl) cardEl.scrollIntoView({ behavior: 'smooth', block: 'start' })

  const filtersEl = document.querySelector('.filters') as HTMLElement
  const headerH = (document.querySelector('.main-header') as HTMLElement).offsetHeight
  if (filtersEl) {
    const y = filtersEl.getBoundingClientRect().top + window.pageYOffset
    window.scrollTo({ top: y - headerH, behavior: 'smooth' })
  }
}

// React to filter changes
watch(
  [filtreDia, filtreHora, obertAra, mostrarHorari, llistaQuery, orderBy],
  renderMap
)

// Initial load
onMounted(carregarBotigues)
</script>


<style scoped>
html, body { overflow-x:hidden; width:100%; }
.map-wrapper { padding:20px; }

.filters {
  display:flex; gap:10px; margin-bottom:15px; flex-wrap:wrap;
}
.filters select, .filters button {
  padding:8px 12px; border-radius:6px; border:1px solid #ccc;
  font-weight:bold; cursor:pointer;
}
.filters button.active {
  background-color:#42b983; color:white; border:none;
}

.map-layout { display:flex; gap:20px; }

.mapa-container {
  flex:2; height:80vh; border-radius:10px;
  box-shadow:0 4px 14px rgba(0,0,0,0.1);
}

.sidebar-list {
  flex:1; background:rgba(255,255,255,0.95);
  border-radius:10px; max-height:80vh;
  display:flex; flex-direction:column;
  box-shadow:0 2px 10px rgba(0,0,0,0.15);
}
.sidebar-top {
  padding:12px; border-bottom:1px solid #ddd;
  display:flex; justify-content:center;
}
.search-sidebar {
  width:90%; padding:8px 10px;
  border-radius:6px; border:1px solid #ccc;
  font-size:14px;
}
.sidebar-scroll {
  padding:12px; overflow-y:auto; flex-grow:1;
}

.botiga-cards {
  list-style:none; padding:0; margin:0;
  display:flex; flex-direction:column; gap:10px;
}
.botiga-card {
  background:#fff; padding:12px 14px; border-radius:8px;
  box-shadow:0 1px 6px rgba(0,0,0,0.08);
  cursor:pointer; transition:transform 0.2s ease,box-shadow 0.2s ease;
}
.botiga-card:hover {
  transform:translateY(-2px);
  box-shadow:0 3px 12px rgba(0,0,0,0.15);
}
.botiga-card.selected {
  border:2px solid #42b983;
}

.botiga-header {
  display:flex; justify-content:space-between; align-items:center;
}
.botiga-nom {
  margin:0; font-size:16px; font-weight:600; color:#333;
}
.botiga-distancia {
  font-size:13px; color:#666; margin-top:4px;
}

.horari-taula {
  width:100%; border-collapse:collapse; margin-top:10px; font-size:14px;
}
.horari-taula td {
  padding:6px 4px; border-bottom:1px solid #eee; vertical-align:top;
}
.horari-taula .dia {
  width:100px; font-weight:bold; color:#333;
}
.horari-taula .horari { color:#444; }

.detall-enllac {
  display:inline-block; margin-top:16px;
  font-weight:bold; text-decoration:none; color:#42b983;
}
.detall-enllac:hover { text-decoration:underline; }

.order-buttons {
  display:flex; justify-content:center; gap:8px; margin:12px 0;
}
.order-buttons button.active {
  background-color:#42b983; color:white; border:none;
}

@media (max-width:900px) {
  .map-layout { flex-direction:column; }
  .mapa-container { height:50vh; flex:none; }
  .sidebar-list { max-height:none; flex:none; margin-top:10px; }
  .search-sidebar { width:100%; }
  .sidebar-scroll { max-height:50vh; }
}

.detall-botiga {
  animation:fadeIn 0.3s ease;
}
@keyframes fadeIn {
  from { opacity:0; transform:translateY(5px); }
  to   { opacity:1; transform:translateY(0); }
}
</style>
