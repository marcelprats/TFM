<template>
  <div class="home-container">

    <!-- Hero Section -->
    <section class="home-hero">
      <div class="hero-content">
        <h1>Descobreix el <span>gust</span> del teu barri</h1>
        <p>Explora productes de comerços locals i dona suport a la teva comunitat.</p>
        <button @click="router.push('/botiga')" class="hero-button">
          Comença ara
        </button>
      </div>
      <router-link to="/about" style="display: contents">
        <picture>
          <source media="(min-width: 768px)" srcset="/img/hero-i.png" />
          <img src="/img/hero-image-m.png" alt="Descobreix els comerços locals" class="hero-image" style="cursor:pointer" />
        </picture>
      </router-link>
    </section>

    <!-- Cercador + Mapa -->
    <section class="home-map-section">
      <div class="home-filter-wrapper">
        <h2 class="filter-title">Comerços locals a prop teu</h2>
        <p class="filter-subtitle">
          Filtra per nom de botiga i localitza-la al mapa.
        </p>
        <input
          v-model="filter"
          ref="filterInput"
          type="text"
          placeholder="🔍 Cerca botiga..."
          class="filter-input"
        />
        <ul v-if="filter && suggestions.length" class="suggestion-list">
          <li
            v-for="s in suggestions"
            :key="s.id"
            class="suggestion-item"
            @click="onSuggestionClick(s)"
          >
            {{ s.nom }}
          </li>
        </ul>
        <button @click="router.push('/mapa-botigues')" class="filter-button">
          Veure mapa complet
        </button>
      </div>
      <div class="home-map-wrapper">
        <StoresMap ref="mapRef" :stores="filteredStores" class="map-full" />
      </div>
    </section>

    <!-- Cerca Productes + TopReviews -->
    <section 
      class="home-search-reviews" 
      :class="{ 'search-active': searchActive }"
    >
      <!-- Carousel de valoracions -->
      <div class="col right">
        <TopReviews />
      </div>

      <!-- Cercador live -->
      <div class="col left">
        <section class="home-product-search">
          <h2 class="section-title">Cerca Productes</h2>
          <div class="search-input-wrapper">
            <input
              v-model="homeQuery"
              type="text"
              placeholder="🔍 Cerca producte..."
              class="home-search-input"
            />
            <span v-if="loadingProducts" class="input-spinner"></span>
            <button
              class="explore-btn"
              :disabled="!homeQuery.trim()"
              @click="router.push({ path: '/botiga', query: { q: homeQuery.trim() } })"
            >
              Explorar Botiga
            </button>
          </div>
          <div v-if="homeQuery" class="search-counter">
            Trobats: {{ homeResultsSorted.length }}
          </div>

          <!-- Horitzontal desktop (amagat en cerca activa) -->
          <div v-if="showResults && homeResultsSorted.length" class="search-results-desktop">
            <button class="scroll-btn left" @click="scroll(-1)">
              <i class="fa-solid fa-chevron-left"></i>
            </button>
            <div class="cards-container" ref="cardsContainer">
              <div
                v-for="p in homeResultsSorted"
                :key="p.id"
                class="search-card"
                @click="goToProducte(p.id)"
              >
                <img :src="getImageSrc(p.imatge)" class="search-image"/>
                <div class="search-info">
                  <h3 class="search-name">{{ p.nom }}</h3>
                  <p class="search-price">{{ p.preu }} €</p>
                </div>
              </div>
            </div>
            <button class="scroll-btn right" @click="scroll(1)">
              <i class="fa-solid fa-chevron-right"></i>
            </button>
          </div>

          <!-- Mobile (i en cerca activa) -->
          <div v-if="showResults && homeResultsSorted.length" class="search-results-mobile">
            <div
              v-for="p in homeResultsSorted"
              :key="p.id"
              class="search-card-vertical"
              @click="goToProducte(p.id)"
            >
              <img :src="getImageSrc(p.imatge)" class="search-image-vertical"/>
              <div class="search-info-vertical">
                <h3>{{ p.nom }}</h3>
                <p>{{ p.preu }} €</p>
              </div>
            </div>
          </div>
        </section>
      </div>
    </section>

    <!-- Carousel d'Últims Productes -->
    <ProductCarousel
      v-if="latestProducts.length"
      :products="latestProducts"
      class="product-carousel"
    />
    <p v-else class="loading">Carregant productes…</p>

    <!-- CTA Contacte -->
    <section class="home-contact-cta">
      <div class="contact-cta-content">
        <h2>Contacta amb <span class="highlight">Totaki</span></h2>
        <p>
          Tens algun dubte o proposta? Escriu‐ns un missatge i et respondrem en menys de 24 h.
        </p>
        <button @click="router.push('/contacte')" class="cta-button">
          Envia'ns un missatge
        </button>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import StoresMap from '../components/MapaBotigues.vue'
import ProductCarousel from '../components/ProductCarousel.vue'
import TopReviews from '../components/TopReviews.vue'
import { useProducts } from '../composables/useProducts'

const router = useRouter()

// Stores + mapa
const mapRef = ref<any>(null)
const filterInput = ref<HTMLInputElement | null>(null)
const stores = ref<{ id: number; nom: string; latitude: number; longitude: number }[]>([])
const filter = ref('')

// Últims productes per al carousel
const latestProducts = ref<any[]>([])
const BACKEND_URL = import.meta.env.VITE_BACKEND_URL
const DEFAULT_IMAGE = '/img/no-imatge.jpg'

// Productes live search
const { products: allProducts, loading: loadingProducts } = useProducts()
const homeQuery = ref('')

const filteredStores = computed(() =>
  stores.value.filter(s =>
    s.nom.toLowerCase().includes(filter.value.toLowerCase())
  )
)
const suggestions = computed(() =>
  filter.value
    ? filteredStores.value
        .sort((a, b) => a.nom.localeCompare(b.nom))
        .slice(0, 50)
    : []
)

function onSuggestionClick(s: typeof stores.value[0]) {
  filter.value = ''
  filterInput.value?.classList.add('highlight')
  setTimeout(() => filterInput.value?.classList.remove('highlight'), 800)
  setTimeout(() => mapRef.value?.zoomToStore(s), 100)
}

const homeResultsSorted = computed(() => {
  const term = homeQuery.value.trim().toLowerCase()
  if (!term) return []
  return allProducts.value
    .filter(p => p.nom.toLowerCase().includes(term))
    .sort((a, b) => a.nom.localeCompare(b.nom))
})

function goToProducte(id: number) {
  router.push(`/producte/${id}`)
}

// Scroll horitzontal (desktop cards)
const cardsContainer = ref<HTMLElement | null>(null)
function scroll(dir: number) {
  const c = cardsContainer.value
  if (!c) return
  c.scrollBy({ left: c.clientWidth * 0.8 * dir, behavior: 'smooth' })
}

// utilitats imatge
function getImageSrc(path: string | null) {
  if (!path) return DEFAULT_IMAGE
  return path.startsWith('/')
    ? BACKEND_URL + path
    : `${BACKEND_URL}/${path}`
}

// Carrega botigues i productes en paral·lel
onMounted(async () => {
  const [storesRes, productsRes] = await Promise.all([
    axios.get('/botigues'),
    axios.get('/productes')
  ])
  stores.value = storesRes.data
  latestProducts.value = productsRes.data
    .slice()
    .sort((a, b) => b.id - a.id)
    .slice(0, 25)
  // Fit bounds només si hi ha botigues
  if (stores.value.length) {
    const coords = stores.value.map(s => [s.latitude, s.longitude] as [number, number])
    setTimeout(() => mapRef.value?.fitBounds(coords, { padding: [40, 40] }), 200)
  }
})

// Control per amagar horitzontal i mostrar vertical
const showResults = computed(() => homeQuery.value.trim().length > 0)
const searchActive = computed(
  () => showResults.value && homeResultsSorted.value.length > 0
)
</script>

<style scoped>
.home-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem;
}
.loading {
  text-align: center;
  color: #6b7280;
  padding: 2rem 0;
}
/* ─── Hero ──────────────────────────────────────────────────────── */
.home-hero {
  display: flex;
  flex-direction: column-reverse;
  align-items: center;
  background: #e6fffa;
  border-radius: .5rem;
  margin: 2rem 0;
  overflow: hidden;
}
.hero-content {
  padding: 2rem;
  text-align: center;
}
.hero-content h1 {
  font-size: 2.5rem;
  color: #064e3b;
}
.hero-content h1 span {
  color: #42b983;
}
.hero-content p {
  margin: 1rem 0;
  color: #374151;
}
.hero-button {
  background: #42b983;
  color: #fff;
  border: none;
  padding: .75rem 1.5rem;
  border-radius: .25rem;
  cursor: pointer;
  transition: background .2s;
}
.hero-button:hover {
  background: #369e6b;
}
.hero-image {
  width: 100%;
  height: auto;
}
/* Amaga la hero image en mòbil */
@media (max-width: 767px) {
  .hero-image { display: none; }
}
@media(min-width:768px) {
  .home-hero { flex-direction: row; }
  .hero-content, .hero-image { flex: 1; }
}

/* ─── Cercador + Mapa ──────────────────────────────────────────── */
.home-map-section {
  display: flex;
  flex-direction: column;
  gap: 2rem;
  margin-bottom: 2rem;
}
@media(min-width:768px) {
  .home-map-section { flex-direction: row; align-items: stretch; }
}
@media(max-width:768px) {
  .home-map-wrapper { height: 300px !important; flex: none !important; }
}
.home-filter-wrapper {
  flex: 1;
  background: #f9fafb;
  padding: 1.5rem;
  border-radius: .5rem;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
.filter-title {
  font-size: 1.5rem;
  color: #064e3b;
  margin-bottom: .5rem;
}
.filter-subtitle {
  margin-bottom: 1rem;
  color: #374151;
}
.filter-input {
  width: 100%;
  padding: .5rem;
  border: 1px solid #d1d5db;
  border-radius: .25rem;
  margin-bottom: .5rem;
}
@keyframes highlightInput {
  0%   { box-shadow: 0 0 0 0 rgba(66,185,131,0); }
  50%  { box-shadow: 0 0 8px 2px rgba(66,185,131,0.6); }
 100%  { box-shadow: 0 0 0 0 rgba(66,185,131,0); }
}
.filter-input.highlight {
  animation: highlightInput .8s ease-out;
}
.suggestion-list {
  list-style: none;
  margin: 0 0 1rem;
  padding: 0;
  border: 1px solid #d1d5db;
  border-radius: .25rem;
  max-height: 160px;
  overflow-y: auto;
  background: #fff;
}
.suggestion-list li + li {
  border-top: 1px solid #eee;
}
.suggestion-item {
  padding: .5rem;
  cursor: pointer;
}
.suggestion-item:hover {
  background: #e6fffa;
}
.filter-button {
  padding: .5rem 1rem;
  background: #42b983;
  color: #fff;
  border: none;
  border-radius: .25rem;
  cursor: pointer;
  margin-top: .5rem;
}
.filter-button:hover {
  background: #369e6b;
}
.home-map-wrapper {
  flex: 2;
  display: flex;
  border-radius: .5rem;
  overflow: hidden;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
  min-height: 300px;
}
.map-full {
  width: 100%;
  height: 100%;
}

/* ─── Cerca live + Valoracions  ───────────────────────────────── */
.home-search-reviews {
  display: flex;
  gap: 2rem;
  margin: 3rem 0;
}
.home-search-reviews .col {
  flex: 1;
}
.home-search-reviews.search-active .search-results-desktop {
  display: none !important;
}
.home-search-reviews.search-active .search-results-mobile {
  display: block !important;
  max-height: 300px;
  overflow-y: auto;
}

/* ─── Cerca Productes ─────────────────────────────────────────── */
.home-product-search {
  margin: 0; /* ja gestionat per .home-search-reviews */
}
.section-title {
  font-size: 1.75rem;
  color: #064e3b;
  margin-bottom: 1rem;
}
.search-input-wrapper {
  display: flex;
  gap: .5rem;
  max-width: 500px;
  margin-bottom: 1rem;
}
.home-search-input,
.explore-btn {
  padding: .75rem 1rem;
  font-size: 1rem;
  box-sizing: border-box;
  border-radius: .375rem;
}
.home-search-input {
  flex: 1;
  border: 1px solid #d1d5db;
}
.explore-btn {
  background: #42b983;
  color: white;
  border: none;
  cursor: pointer;
  transition: background .2s;
}
.explore-btn:hover {
  background: #369e6b;
}
.input-spinner {
  position: absolute;
  top: 50%;
  right: 12px;
  width: 18px;
  height: 18px;
  margin-top: -9px;
  border: 2px solid #ccc;
  border-top-color: #42b983;
  border-radius: 50%;
  animation: spin .7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.search-counter {
  font-weight: 500;
  margin-bottom: .5rem;
  color: #374151;
}

/* ─── Resultats desktop (horitzontal) ─────────────────────────── */
.search-results-desktop {
  display: flex;
  align-items: center;
}
.cards-container {
  display: flex;
  overflow-x: auto;
  scroll-behavior: smooth;
  gap: 1rem;
  padding: .5rem;
}
.cards-container::-webkit-scrollbar {
  height: 8px;
}
.cards-container::-webkit-scrollbar-thumb {
  background: rgba(0,0,0,0.2);
  border-radius: 4px;
}
.scroll-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #42b983;
  cursor: pointer;
  padding: .25rem;
}
.search-card {
  flex: 0 0 160px;
  background: #fff;
  margin: 0 .5rem;
  border-radius: .5rem;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  cursor: pointer;
  transition: transform .2s;
}
.search-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 10px rgba(0,0,0,0.15);
}
.search-image {
  width: 100%;
  aspect-ratio: 1;
  object-fit: cover;
}
.search-info {
  text-align: center;
  padding: .5rem;
}
.search-name {
  font-size: .95rem;
  margin: .5rem 0 .25rem;
  color: #333;
}
.search-price {
  font-size: .9rem;
  color: #42b983;
  font-weight: bold;
}

/* ─── Resultats mòbil (vertical) ──────────────────────────────── */
.search-results-mobile {
  display: none;
}
.search-card-vertical {
  display: flex;
  gap: 1rem;
  align-items: center;
  background: #fff;
  border-radius: .5rem;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  padding: .5rem;
  margin-bottom: .5rem;
  cursor: pointer;
  transition: transform .2s;
}
.search-card-vertical:hover {
  transform: translateY(-2px);
}
.search-image-vertical {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: .25rem;
}
.search-info-vertical {
  flex: 1;
}
.search-info-vertical h3 {
  margin: 0;
  font-size: 1rem;
  color: #333;
}
.search-info-vertical p {
  margin: 4px 0 0;
  color: #42b983;
  font-weight: 500;
}
@media(max-width:768px) {
  .search-results-desktop { display: none; }
  .search-results-mobile {
    display: block;
    max-height: 300px;
    overflow-y: auto;
  }
  .home-search-reviews {
    flex-direction: column;
  }
}

/* ─── Carousel Últims ─────────────────────────────────────────── */
.product-carousel {
  margin: 2rem 0;
}

/* ─── CTA Contacte ─────────────────────────────────────────────── */
.home-contact-cta {
  margin: 4rem 0;
  background: #f0fdf4;
  border-radius: .5rem;
  text-align: center;
  padding: 2rem 1rem;
  box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}
.contact-cta-content h2 {
  font-size: 2rem;
  color: #064e3b;
  margin-bottom: .5rem;
}
.contact-cta-content .highlight {
  color: #42b983;
}
.cta-button {
  background: #42b983;
  color: #fff;
  border: none;
  padding: .75rem 1.5rem;
  border-radius: .25rem;
  cursor: pointer;
  transition: background .2s;
}
.cta-button:hover {
  background: #369e6b;
}
</style>