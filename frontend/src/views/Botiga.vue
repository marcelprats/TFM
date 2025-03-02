<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { fetchProducts } from "../services/authService";

const products = ref([]);
const searchQuery = ref("");
const selectedStores = ref<string[]>([]);
const selectedSellers = ref<string[]>([]);
const selectedMinPrice = ref<number | null>(null);
const selectedMaxPrice = ref<number | null>(null);
const stores = ref<string[]>([]);
const sellers = ref<string[]>([]);
const showStoreDropdown = ref(false);
const showSellerDropdown = ref(false);

onMounted(async () => {
  products.value = await fetchProducts();
  stores.value = [...new Set(products.value.map(p => p.store))];
  sellers.value = [...new Set(products.value.map(p => p.seller))];
});

const toggleSelection = (list: string[], value: string) => {
  if (list.includes(value)) {
    list.splice(list.indexOf(value), 1);
  } else {
    list.push(value);
  }
};

const filteredProducts = computed(() => {
  return products.value.filter((product) => {
    return (
      product.name.toLowerCase().includes(searchQuery.value.toLowerCase()) &&
      (selectedStores.value.length === 0 || selectedStores.value.includes(product.store)) &&
      (selectedSellers.value.length === 0 || selectedSellers.value.includes(product.seller)) &&
      (selectedMinPrice.value === null || product.price >= selectedMinPrice.value) &&
      (selectedMaxPrice.value === null || product.price <= selectedMaxPrice.value)
    );
  });
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
      <div class="dropdown">
        <button @click="showStoreDropdown = !showStoreDropdown">Filtrar per botiga ▼</button>
        <div v-if="showStoreDropdown" class="dropdown-content">
          <div v-for="store in stores" :key="store" @click="toggleSelection(selectedStores, store)" :class="{ 'selected': selectedStores.includes(store) }">
            {{ store }}
          </div>
        </div>
      </div>
      <div class="dropdown">
        <button @click="showSellerDropdown = !showSellerDropdown">Filtrar per venedor ▼</button>
        <div v-if="showSellerDropdown" class="dropdown-content">
          <div v-for="seller in sellers" :key="seller" @click="toggleSelection(selectedSellers, seller)" :class="{ 'selected': selectedSellers.includes(seller) }">
            {{ seller }}
          </div>
        </div>
      </div>
      <div>
        <label for="min-price">Preu mínim:</label>
        <input id="min-price" v-model.number="selectedMinPrice" type="number" placeholder="Mínim" />
      </div>
      <div>
        <label for="max-price">Preu màxim:</label>
        <input id="max-price" v-model.number="selectedMaxPrice" type="number" placeholder="Màxim" />
      </div>
    </div>
    <div class="product-grid">
      <div v-for="product in filteredProducts" :key="product.id" class="product-card">
        <h2>{{ product.name }}</h2>
        <p class="price">{{ product.price }} €</p>
        <p class="store">Botiga: {{ product.store }}</p>
        <p class="seller">Venedor: {{ product.seller }}</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.store-page {
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
}

.product-card:hover {
  transform: scale(1.05);
}

.product-card h2 {
  font-size: 18px;
  margin-bottom: 10px;
}

.product-card .price {
  font-size: 16px;
  font-weight: bold;
  color: #42b983;
}

.product-card .store, .product-card .seller {
  font-size: 14px;
  color: #666;
}
</style>
