<template>
  <div class="container">
    <h1>Botiga</h1>

    <!-- Search + Toggle Filters -->
    <div class="search-bar">
      <input
        v-model="searchQuery"
        placeholder="🔍 Cercar producte..."
        aria-label="Cerca producte"
      />
      <button @click="showFilters = !showFilters">
        {{ showFilters ? 'Amagar filtres' : 'Mostrar filtres' }}
      </button>
    </div>

    <!-- Filtres -->
    <div v-if="showFilters" class="filters">
      <!-- Categories -->
      <div class="filter-group">
        <label for="cats"><strong>Categories:</strong></label>
        <Multiselect
          id="cats"
          v-model="selectedCategories"
          :options="categories"
          track-by="id"
          label="nom"
          placeholder="Selecciona categories"
          :multiple="true"
          :close-on-select="false"
        />
      </div>

      <!-- Subcategories -->
      <div class="filter-group">
        <label for="subs"><strong>Subcategories:</strong></label>
        <Multiselect
          id="subs"
          v-model="selectedSubcategories"
          :options="filteredSubcategories"
          track-by="id"
          label="nom"
          placeholder="Selecciona subcategories"
          :multiple="true"
          :close-on-select="false"
        />
      </div>

      <!-- Preus -->
      <div class="filter-group price-group">
        <div class="price-range">
          <label><strong>Preu mínim:</strong> {{ selectedMinPrice }} €</label>
          <input
            type="range"
            v-model.number="selectedMinPrice"
            :min="minPrice"
            :max="maxPrice"
            step="1"
          />
        </div>
        <div class="price-range">
          <label><strong>Preu màxim:</strong> {{ selectedMaxPrice }} €</label>
          <input
            type="range"
            v-model.number="selectedMaxPrice"
            :min="minPrice"
            :max="maxPrice"
            step="1"
          />
        </div>
      </div>

      <!-- Stock -->
      <div class="filter-group checkbox-group">
        <label>
          <input type="checkbox" v-model="filterByStock" />
          <strong>Disponibles</strong>
        </label>
      </div>

      <!-- Reset -->
      <div class="filter-group reset-group">
        <button class="reset-button" @click="resetFilters">
          Reiniciar
        </button>
      </div>
    </div>

    <!-- Loading Spinner -->
    <div v-if="loading" class="loading-spinner">
      <span class="spinner"></span> Carregant…
    </div>

    <!-- Result Count + Ordering -->
    <div v-else class="result-order">
      <p class="result-count">
        {{ totalResults }} producte{{ totalResults === 1 ? '' : 's' }} trobat{{ totalResults === 1 ? '' : 's' }}.
      </p>
      <div class="order-controls">
        <label for="order-select"><strong>Ordenar per:</strong></label>
        <div class="select-wrapper">
          <select
            id="order-select"
            v-model="orderBy"
            @change="onOrderChange"
          >
            <option value="nom">Nom</option>
            <option value="preu">Preu</option>
            <option value="stock">Stock</option>
            <option value="antiguitat">Antiguitat</option>
            <option value="novetat">Novetats</option>
          </select>
          <button class="arrow-btn" @click="toggleOrderDir" :aria-label="orderDir==='asc'?'Ascendent':'Descendent'">
            <i :class="arrowClass"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- No results -->
    <p v-if="!loading && totalResults === 0" class="no-results">
      Cap producte trobat amb aquests filtres.
    </p>

    <!-- Product Grid -->
    <div class="product-grid" v-if="!loading && totalResults > 0">
      <div
        v-for="product in sortedProducts"
        :key="product.id"
        class="product-card"
        @click="goToProducte(product.id)"
      >
        <img
          :src="getImageSrc(product.imatge)"
          class="product-image"
          :alt="`Imatge de ${product.nom}`"
        />
        <h2>{{ product.nom }}</h2>
        <p class="preu">{{ product.preu }} €</p>
        <p :class="['stock-label', getStockClass(product.stock)]">
          Stock: {{ product.stock }}
        </p>
        <p
          v-if="product.botiga"
          class="botiga-link"
          @click.stop="goToBotiga(product.botiga_id!)"
        >
          🏪 {{ product.botiga.nom }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import Multiselect from 'vue-multiselect';
import { useRouter, useRoute } from 'vue-router';
import { useProducts } from '../composables/useProducts';

const router = useRouter();
const route = useRoute();
const showFilters = ref(false);

const {
  loading,
  categories,
  filteredSubcategories,
  selectedCategories,
  selectedSubcategories,
  searchQuery,
  filterByStock,
  minPrice,
  maxPrice,
  selectedMinPrice,
  selectedMaxPrice,
  filteredProducts,
  totalResults,
  resetFilters,
} = useProducts();

// === Inicialitza searchQuery des del query param "q" ===
onMounted(() => {
  const q = (route.query.q as string) || '';
  if (q) searchQuery.value = q;
});

// Si canvia la URL manualment, sincronitza:
watch(() => route.query.q, q => {
  searchQuery.value = (q as string) || '';
});

// Ordering state
type OrderKey = 'nom' | 'preu' | 'stock' | 'antiguitat' | 'novetat';
const orderBy = ref<OrderKey>('nom');
const prevOrder = ref<OrderKey>('nom');
const orderDir = ref<'asc'|'desc'>('asc');

function onOrderChange() {
  if (orderBy.value === prevOrder.value) {
    orderDir.value = orderDir.value === 'asc' ? 'desc' : 'asc';
  } else {
    orderDir.value = orderBy.value === 'antiguitat' ? 'asc'
                   : orderBy.value === 'novetat'   ? 'desc'
                   : 'asc';
    prevOrder.value = orderBy.value;
  }
}

function toggleOrderDir() {
  if (orderBy.value === 'antiguitat' || orderBy.value === 'novetat') {
    orderBy.value = orderBy.value === 'antiguitat' ? 'novetat' : 'antiguitat';
    orderDir.value = orderBy.value === 'novetat' ? 'desc' : 'asc';
  } else {
    orderDir.value = orderDir.value === 'asc' ? 'desc' : 'asc';
  }
}

const arrowClass = computed(() =>
  orderDir.value === 'asc' ? 'arrow-up' : 'arrow-down'
);

const sortedProducts = computed(() => {
  return [...filteredProducts.value].sort((a, b) => {
    let va: any = a[orderBy.value];
    let vb: any = b[orderBy.value];

    if (orderBy.value === 'preu') {
      va = parseFloat(a.preu as any);
      vb = parseFloat(b.preu as any);
    }
    if (orderBy.value === 'antiguitat' || orderBy.value === 'novetat') {
      va = a.id; vb = b.id;
    }

    if (va < vb) return orderDir.value === 'asc' ? -1 : 1;
    if (va > vb) return orderDir.value === 'asc' ? 1 : -1;
    return 0;
  });
});

function goToBotiga(id: number) { router.push(`/info-botiga/${id}`); }
function goToProducte(id: number) { router.push(`/producte/${id}`); }

const BACKEND_URL = 'http://localhost:8000';
const DEFAULT_IMAGE = '/img/no-imatge.jpg';
function getImageSrc(path: string|null) {
  if (!path) return DEFAULT_IMAGE;
  return path.startsWith('/') ? BACKEND_URL + path
                              : BACKEND_URL + '/uploads/' + path;
}
function getStockClass(stock: number) {
  if (stock > 10) return 'stock-verde';
  if (stock > 0)  return 'stock-taronja';
  return 'stock-vermell';
}
</script>

<style scoped>
@import 'vue-multiselect/dist/vue-multiselect.css';

.container {
  max-width: 1200px;
  margin: auto;
  padding: 20px;
}

/* Search bar */
.search-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  justify-content: space-between;
  margin-bottom: 20px;
}
.search-bar input {
  flex: 1;
  min-width: 220px;
  padding: 10px;
}
.search-bar button {
  padding: 10px 15px;
  background-color: #42b983;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}

/* Result + ordenació */
.result-order {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}
.order-controls {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}
.select-wrapper {
  position: relative;
  display: inline-flex;
  align-items: center;
}
.select-wrapper select {
  appearance: none;
  padding: 6px 32px 6px 12px;
  border: 1px solid #ccc;
  border-radius: 4px;
  background: white;
  font-size: 0.9rem;
}
.arrow-btn {
  background: none;
  border: none;
  margin-left: 8px;
  cursor: pointer;
  font-size: 1.2rem;
  color: #42b983;
  transition: transform 0.2s ease;
}
.arrow-up::before { content: '▲'; }
.arrow-down::before { content: '▼'; }
.arrow-btn:hover { transform: scale(1.3); }

/* Filtres */
.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  background: #f8f8f8;
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 20px;
}
.filter-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 240px;
}
.price-group {
  flex-direction: row;
  gap: 20px;
  align-items: center;
  flex-wrap: wrap;
}
.price-range {
  display: flex;
  flex-direction: column;
}
.checkbox-group {
  justify-content: center;
}
.reset-group {
  align-self: flex-end;
}
.reset-button {
  background: #e74c3c;
  color: white;
  padding: 10px 20px;
  font-weight: bold;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s ease;
}
.reset-button:hover { background: #c0392b; }

/* Spinner */
.loading-spinner {
  text-align: center;
  margin: 20px 0;
}
.spinner {
  display: inline-block;
  width: 24px; height: 24px;
  border: 3px solid #ccc;
  border-top-color: #42b983;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Resultats */
.result-count,
.no-results {
  font-weight: bold;
  margin-bottom: 1rem;
}

/* Grid de productes */
.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 20px;
}
.product-card {
  background: white;
  border-radius: 10px;
  padding: 15px;
  text-align: center;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.product-card:hover {
  transform: scale(1.02);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.product-image {
  width: 100%;
  height: 160px;
  object-fit: cover;
  border-radius: 8px;
  margin-bottom: 10px;
}
.stock-label {
  font-weight: bold;
  margin: 8px 0;
}
.stock-verde { color: green; }
.stock-taronja { color: orange; }
.stock-vermell { color: red; }
.botiga-link {
  margin-top: 10px;
  font-size: 14px;
  color: #007bff;
  cursor: pointer;
  text-decoration: underline;
}
.botiga-link:hover { color: #0056b3; }

@media (max-width: 768px) {
  .filters { flex-direction: column; }
  .price-group { flex-direction: column; }
  .filter-group { width: 100%; }
}
</style>
