<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { fetchProducts } from "../services/authService";

const products = ref([]);
const searchQuery = ref("");
const selectedStore = ref("");
const selectedSeller = ref("");
const stores = ref([]);
const sellers = ref([]);

onMounted(async () => {
  products.value = await fetchProducts();
  stores.value = [...new Set(products.value.map(p => p.store))];
  sellers.value = [...new Set(products.value.map(p => p.seller))];
});

const filteredProducts = computed(() => {
  return products.value
    .filter((product) => product && product.name) // Evita productes indefinits
    .filter((product) => {
      return (
        product.name.toLowerCase().includes(searchQuery.value.toLowerCase()) &&
        (selectedStore.value === "" || product.store === selectedStore.value) &&
        (selectedSeller.value === "" || product.seller === selectedSeller.value)
      );
    });
});

</script>

<template>
  <div class="store-page">
    <h1>Botiga</h1>
    <div class="filters">
      <input v-model="searchQuery" type="text" placeholder="Cercar producte..." />
      <select v-model="selectedStore">
        <option value="">Totes les botigues</option>
        <option v-for="store in stores" :key="store" :value="store">{{ store }}</option>
      </select>
      <select v-model="selectedSeller">
        <option value="">Tots els venedors</option>
        <option v-for="seller in sellers" :key="seller" :value="seller">{{ seller }}</option>
      </select>
    </div>
    <table class="product-table">
      <thead>
        <tr>
          <th>Nom</th>
          <th>Preu</th>
          <th>Botiga</th>
          <th>Venedor</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="product in filteredProducts" :key="product.id">
          <td>{{ product.name }}</td>
          <td>{{ product.price }} €</td>
          <td>{{ product.store }}</td>
          <td>{{ product.seller }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.store-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.filters {
  display: flex;
  gap: 15px;
  margin-bottom: 20px;
}

.filters input, .filters select {
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 5px;
}

.product-table {
  width: 100%;
  border-collapse: collapse;
}

.product-table th, .product-table td {
  border: 1px solid #ddd;
  padding: 10px;
  text-align: left;
}

.product-table th {
  background-color: #f4f4f4;
}
</style>
