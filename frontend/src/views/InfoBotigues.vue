<template>
  <div class="info-botigues">
    <h1>Llista de Botigues</h1>

    <!-- CONTROLS -->
    <div class="controls">
      <div class="controls-top">
        <button class="btn-mapa" @click="$router.push('/mapa-botigues')">
          🗺️ Veure Mapa
        </button>

        <input
          v-model="searchQuery"
          type="text"
          placeholder="🔍 Cerca botigues..."
          class="search-input"
        />

        <button
          class="btn-toggle-filters"
          @click="showFilters = !showFilters"
        >
          {{ showFilters ? 'Amagar Filtres' : 'Veure Filtres' }}
        </button>
      </div>

      <div class="filters" :class="{ open: showFilters }">
        <div class="slider-group">
          <label>Ambient ≥ {{ minAmbient }}★</label>
          <input type="range" v-model.number="minAmbient" min="0" max="5" step="1" />
        </div>
        <div class="slider-group">
          <label>Personal ≥ {{ minPersonal }}★</label>
          <input type="range" v-model.number="minPersonal" min="0" max="5" step="1" />
        </div>
        <div class="slider-group">
          <label>Recollida ≥ {{ minRecollida }}★</label>
          <input type="range" v-model.number="minRecollida" min="0" max="5" step="1" />
        </div>
        <button class="reset-btn" @click="resetFilters">
          🔄 Reiniciar filtres
        </button>
      </div>
    </div>

    <!-- Llista de cards -->
    <div v-if="loading" class="status">Carregant botigues…</div>
    <div v-else-if="error" class="status error">{{ error }}</div>
    <div v-else-if="filteredBotigues.length === 0" class="status">
      No s'ha trobat cap botiga amb els filtres aplicats.
    </div>

    <div v-else class="botiga-grid">
      <router-link
        v-for="b in filteredBotigues"
        :key="b.id"
        :to="`/info-botiga/${b.id}`"
        class="botiga-card"
      >
        <div class="card-header">
          <img
            v-if="b.imatge"
            :src="b.imatge"
            :alt="`Imatge de ${b.nom}`"
            class="botiga-img"
          />
        </div>
        <div class="card-body">
          <h2>{{ b.nom }}</h2>
          <p v-if="b.adreca" class="adresse">{{ b.adreca }}</p>
          <div class="store-stars">
            <div class="stars-group">
              <small>Ambient</small>
              <span v-for="n in 5" :key="`a-${b.id}-${n}`">
                {{ n <= Math.round(b.avg_ambient) ? '★' : '☆' }}
              </span>
            </div>
            <div class="stars-group">
              <small>Personal</small>
              <span v-for="n in 5" :key="`p-${b.id}-${n}`">
                {{ n <= Math.round(b.avg_personal) ? '★' : '☆' }}
              </span>
            </div>
            <div class="stars-group">
              <small>Recollida</small>
              <span v-for="n in 5" :key="`r-${b.id}-${n}`">
                {{ n <= Math.round(b.avg_recollida) ? '★' : '☆' }}
              </span>
            </div>
          </div>
        </div>
      </router-link>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'

interface Botiga {
  id: number
  nom: string
  imatge?: string
  adreca?: string
  avg_ambient: number
  avg_personal: number
  avg_recollida: number
}

const API_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api'
const token    = localStorage.getItem('userToken')

const botigues      = ref<Botiga[]>([])
const loading       = ref(true)
const error         = ref<string|null>(null)
const showFilters   = ref(false)

// controls
const searchQuery   = ref('')
const minAmbient    = ref(0)
const minPersonal   = ref(0)
const minRecollida  = ref(0)

const filteredBotigues = computed(() =>
  botigues.value.filter(b => {
    if (searchQuery.value && !b.nom.toLowerCase().includes(searchQuery.value.toLowerCase())) return false
    if (b.avg_ambient   < minAmbient.value)   return false
    if (b.avg_personal  < minPersonal.value)  return false
    if (b.avg_recollida < minRecollida.value) return false
    return true
  })
)

function resetFilters() {
  searchQuery.value  = ''
  minAmbient.value   = 0
  minPersonal.value  = 0
  minRecollida.value = 0
}

onMounted(async () => {
  try {
    const res = await axios.get<Botiga[]>(`${API_URL}/botigues`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    botigues.value = res.data.map(b => ({
      ...b,
      avg_ambient:   0,
      avg_personal:  0,
      avg_recollida: 0
    }))
    await Promise.all(botigues.value.map(async b => {
      try {
        const sum = await axios.get(`${API_URL}/botigues/${b.id}/store-summary`, {
          headers: { Authorization: `Bearer ${token}` }
        })
        b.avg_ambient   = sum.data.ambient?.avg   ?? 0
        b.avg_personal  = sum.data.personal?.avg  ?? 0
        b.avg_recollida = sum.data.recollida?.avg ?? 0
      } catch { /* queda a 0 */ }
    }))
  } catch (e: any) {
    console.error('Error carregant botigues:', e)
    error.value = "No s'han pogut carregar les botigues."
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
/* Contenidor */
.info-botigues {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
  text-align: center;
}

/* CONTROLS */
.controls {
  margin-bottom: 24px;
}
.controls-top {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  justify-content: center;
  align-items: center;
}

/* Botons */
.btn-mapa,
.btn-toggle-filters,
.reset-btn {
  background: #42b983;
  color: #fff;
  border: none;
  padding: 8px 16px;
  border-radius: 24px;
  cursor: pointer;
  font-size: 0.9rem;
  transition: background .2s, transform .2s;
}
.btn-mapa:hover,
.btn-toggle-filters:hover,
.reset-btn:hover {
  background: #369a6f;
  transform: translateY(-2px);
}

/* Cerca */
.search-input {
  padding: 8px 12px;
  border: 1px solid #ccc;
  border-radius: 24px;
  flex: 1;
  max-width: 300px;
}

/* Sliders & filtres */
.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  justify-content: center;
  align-items: center;
  margin-top: 12px;
}
.slider-group {
  display: flex;
  flex-direction: column;
  align-items: center;
}
.slider-group label {
  font-size: 0.85rem;
  margin-bottom: 4px;
}
.slider-group input[type="range"] {
  width: 120px;
}
.filters {
  max-height: 0;
  overflow: hidden;
  opacity: 0;
  transition: max-height .3s ease, opacity .3s ease;
}
.filters.open {
  max-height: 200px;
  opacity: 1;
}

@media (max-width: 768px) {
  .btn-toggle-filters {
    display: inline-block;
  }
}

/* ESTATS */
.status {
  margin: 20px 0;
  font-size: 1.1rem;
}
.status.error { color: #d9534f; }

/* GRID CARTES */
.botiga-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 24px;
}

/* CARTA BOTIGA */
.botiga-card {
  display: flex;
  flex-direction: column;
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  text-decoration: none;
  color: inherit;
  transition: transform .3s, box-shadow .3s;
}
.botiga-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}

/* IMATGE */
.card-header img {
  width: 100%;
  height: 150px;
  object-fit: cover;
}

/* COS DE LA CARTA */
.card-body {
  flex: 1;
  padding: 16px;
  display: flex;
  flex-direction: column;
}
.card-body h2 {
  margin: 0 0 8px;
  font-size: 1.25rem;
}
.adresse {
  font-size: 0.9rem;
  color: #666;
  margin-bottom: 16px;
}

/* ESTRELLES */
.store-stars {
  margin-top: auto;
  display: flex;
  justify-content: space-around;
}
.stars-group {
  text-align: center;
}
.stars-group small {
  display: block;
  font-size: 0.75rem;
  color: #888;
  margin-bottom: 4px;
}
.stars-group span {
  font-size: 1rem;
  color: #f0a500;
}
</style>
