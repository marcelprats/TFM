<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from "vue";
import { fetchProducts } from "../services/authService";

const products = ref([]);
const searchQuery = ref("");
const selectedStores = ref<string[]>([]);
const stores = ref<string[]>([]);
const showStoreDropdown = ref(false);

// Variables per al filtre de preus
const minPrice = ref(0);
const maxPrice = ref(500);
const selectedMinPrice = ref(0);
const selectedMaxPrice = ref(500);

onMounted(async () => {
  products.value = await fetchProducts();

  // Determina el preu més baix i més alt entre tots els productes
  if (products.value.length > 0) {
    minPrice.value = Math.min(...products.value.map(p => p.price));
    maxPrice.value = Math.max(...products.value.map(p => p.price));

    // Inicialitzar els filtres amb aquests valors
    selectedMinPrice.value = minPrice.value;
    selectedMaxPrice.value = maxPrice.value;
  }

  updateFilteredStores();
});

const updateFilteredStores = () => {
  const uniqueStores = new Set();
  products.value.forEach(product => {
    if (product.stores && product.stores.length > 0) {
      product.stores.forEach(store => uniqueStores.add(store.name));
    }
  });
  stores.value = [...uniqueStores];
};

const toggleSelection = (list: Ref<string[]>, value: string) => {
  if (!list.value.includes(value)) {
    list.value.push(value);
  } else {
    list.value = list.value.filter(item => item !== value);
  }
};

const handleStoreSelection = (store: string) => {
  toggleSelection(selectedStores, store);
};

const filteredProducts = computed(() => {
  return products.value.filter((product) => {
    return (
      product.name.toLowerCase().includes(searchQuery.value.toLowerCase()) &&
      (selectedStores.value.length === 0 || product.stores?.some(store => selectedStores.value.includes(store.name))) &&
      product.price >= selectedMinPrice.value &&
      product.price <= selectedMaxPrice.value
    );
  });
});

const closeDropdowns = (event: MouseEvent) => {
  const target = event.target as HTMLElement;
  if (!target.closest(".dropdown")) {
    showStoreDropdown.value = false;
  }
};

onUnmounted(() => {
  window.removeEventListener("click", closeDropdowns);
});
</script>

<template>
  <div class="store-page">
    <h1>Botiga</h1>

    <div class="search-bar">
      <label for="search">Cercar producte:</label>
      <input id="search" v-model="searchQuery" type="text" placeholder="Cercar producte..." />
    </div>

    <div class="filters">
      <div class="dropdown" @mouseenter="showStoreDropdown = true" @mouseleave="showStoreDropdown = false">
        <button>Filtrar per botiga ▼</button>
        <div v-if="showStoreDropdown" class="dropdown-content">
          <div v-for="store in stores" :key="store" @click="handleStoreSelection(store)" :class="{ 'selected': selectedStores.includes(store) }">
            {{ store }}
          </div>
        </div>
      </div>

      <div class="price-filter">
        <label>Preu mínim: {{ selectedMinPrice }} €</label>
        <input type="range" v-model.number="selectedMinPrice" :min="minPrice" :max="maxPrice" step="1">

        <label>Preu màxim: {{ selectedMaxPrice }} €</label>
        <input type="range" v-model.number="selectedMaxPrice" :min="minPrice" :max="maxPrice" step="1">
      </div>
    </div>

    <div class="product-grid">
      <router-link v-for="product in filteredProducts" :key="product.id" :to="{ name: 'Producte', params: { id: product.id } }" class="product-card">
        <h2>{{ product.name }}</h2>
        <p class="price">{{ product.price }} €</p>
        <p class="store">
          Botiga:
          <template v-if="product.stores.length > 0">
            <router-link v-for="store in product.stores" :key="store.id" :to="'/info-botiga/' + store.id">
              {{ store.name }}
            </router-link>
          </template>
          <span v-else>No assignada</span>
        </p>
      </router-link>
    </div>
  </div>
</template>

<style scoped>
.store-page {
  min-height: 80vh;
  width: 100%;
  margin: 0 auto;
  padding: 20px;
}

.search-bar {
  margin-bottom: 20px;
  text-align: center;
}

.search-bar input {
  width: 50%;
  padding: 10px;
  font-size: 16px;
  border: 1px solid #ccc;
  border-radius: 5px;
  outline: none;
  transition: 0.3s;
}

.search-bar input:focus {
  border-color: #42b983;
  box-shadow: 0 0 8px rgba(66, 185, 131, 0.5);
}

.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  margin-bottom: 20px;
  background: #f8f8f8;
  padding: 15px;
  border-radius: 8px;
  justify-content: center;
}

.dropdown {
  position: relative;
}

.dropdown button {
  padding: 10px;
  background-color: #42b983;
  color: white;
  border: none;
  cursor: pointer;
  border-radius: 5px;
}

.dropdown-content {
  position: absolute;
  background: white;
  border: 1px solid #ddd;
  border-radius: 5px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  padding: 10px;
  width: 150px;
  max-height: 200px;
  overflow-y: auto;
  z-index: 1000;
}

.dropdown-content div {
  padding: 5px;
  cursor: pointer;
  transition: background 0.3s;
}

.dropdown-content div:hover, .dropdown-content div.selected {
  background-color: #42b983;
  color: white;
}

.price-filter {
  display: flex;
  align-items: center;
  gap: 20px;
}

.price-filter input[type="range"] {
  width: 150px;
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 20px;
  padding: 20px;
}

.product-card {
  background: white;
  border-radius: 10px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  padding: 20px;
  text-align: center;
  border: 1px solid #ddd;
  transition: 0.3s;
  text-decoration: none;
  color: inherit;
}

.product-card:hover {
  transform: scale(1.05);
}
</style>
