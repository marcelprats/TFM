
<script setup lang="ts">
import { ref, onMounted, computed, watch } from "vue";
import axios from "axios";
import { useRouter } from "vue-router";
import Multiselect from "vue-multiselect";

const router = useRouter();

const products = ref([]);
const categories = ref<any[]>([]);
const allSubcategories = ref<any[]>([]);
const filteredSubcategories = ref<any[]>([]);

const selectedCategories = ref<any[]>([]);
const selectedSubcategories = ref<any[]>([]);
const searchQuery = ref("");
const filterByStock = ref(false);
const showFilters = ref(false);

const minPrice = ref(0);
const maxPrice = ref(0);
const selectedMinPrice = ref(0);
const selectedMaxPrice = ref(0);

const DEFAULT_IMAGE = "/img/no-imatge.jpg";
const BACKEND_URL = "http://localhost:8000";

const getImageSrc = (imagePath: string | null): string => {
  if (!imagePath) return DEFAULT_IMAGE;
  if (imagePath.startsWith("/uploads/")) return `${BACKEND_URL}${imagePath}`;
  if (imagePath.startsWith(BACKEND_URL)) return imagePath;
  return `${BACKEND_URL}/uploads/${imagePath}`;
};

onMounted(async () => {
  const prodRes = await axios.get("/productes");
  const catRes = await axios.get("/categories");

  products.value = prodRes.data;
  const allCats = catRes.data;

  categories.value = allCats.filter((c: any) => c.parent_id === null);
  allSubcategories.value = allCats.filter((c: any) => c.parent_id !== null);

  if (products.value.length > 0) {
    const prices = products.value.map((p: any) => parseFloat(p.preu));
    minPrice.value = Math.min(...prices);
    maxPrice.value = Math.max(...prices);
    selectedMinPrice.value = minPrice.value;
    selectedMaxPrice.value = maxPrice.value;
  }
});

watch(selectedCategories, () => {
  const selectedIds = selectedCategories.value.map((cat: any) => cat.id);
  filteredSubcategories.value = allSubcategories.value.filter((sub: any) =>
    selectedIds.includes(sub.parent_id)
  );
  selectedSubcategories.value = selectedSubcategories.value.filter((sub: any) =>
    filteredSubcategories.value.some((f: any) => f.id === sub.id)
  );
});

const filteredProducts = computed(() => {
  return products.value.filter((p: any) => {
    const nameMatch = p.nom.toLowerCase().includes(searchQuery.value.toLowerCase());
    const priceMatch = parseFloat(p.preu) >= selectedMinPrice.value && parseFloat(p.preu) <= selectedMaxPrice.value;
    const stockMatch = !filterByStock.value || p.stock > 0;

    const categoryMatch = selectedCategories.value.length === 0 ||
      selectedCategories.value.some((cat: any) => cat.id === p.categoria);

    const subcategoryMatch = selectedSubcategories.value.length === 0 ||
      selectedSubcategories.value.some((sub: any) => sub.id === p.subcategoria);

    return nameMatch && priceMatch && stockMatch && categoryMatch && subcategoryMatch;
  });
});

const getStockClass = (stock: number) => {
  if (stock > 10) return "stock-verde";
  if (stock > 0) return "stock-taronja";
  return "stock-vermell";
};

const goToBotiga = (botigaId: number) => {
  router.push(`/info-botiga/${botigaId}`);
};

const goToProducte = (producteId: number) => {
  router.push(`/producte/${producteId}`);
};

const resetFilters = () => {
  selectedCategories.value = [];
  selectedSubcategories.value = [];
  searchQuery.value = "";
  filterByStock.value = false;
  selectedMinPrice.value = minPrice.value;
  selectedMaxPrice.value = maxPrice.value;
};
</script>

<template>
  <div class="container">
    <h1>Botiga</h1>

    <div class="search-bar">
      <input v-model="searchQuery" placeholder="🔍 Cercar producte..." />
      <button @click="showFilters = !showFilters">
        {{ showFilters ? "Amagar filtres" : "Mostrar filtres" }}
      </button>
    </div>

    <div v-if="showFilters" class="filters">
      <div class="filter-group">
        <label><strong>Categories:</strong></label>
        <Multiselect
          v-model="selectedCategories"
          :options="categories"
          track-by="id"
          label="nom"
          placeholder="Selecciona categories"
          :multiple="true"
          :close-on-select="false"
        />
      </div>

      <div class="filter-group">
        <label><strong>Subcategories:</strong></label>
        <Multiselect
          v-model="selectedSubcategories"
          :options="filteredSubcategories"
          track-by="id"
          label="nom"
          placeholder="Selecciona subcategories"
          :multiple="true"
          :close-on-select="false"
        />
      </div>

      <div class="filter-group price-group">
        <div class="price-range">
          <label><strong>Preu mínim:</strong> {{ selectedMinPrice }} €</label>
          <input type="range" v-model.number="selectedMinPrice" :min="minPrice" :max="maxPrice" step="1" />
        </div>

        <div class="price-range">
          <label><strong>Preu màxim:</strong> {{ selectedMaxPrice }} €</label>
          <input type="range" v-model.number="selectedMaxPrice" :min="minPrice" :max="maxPrice" step="1" />
        </div>
      </div>

      <div class="filter-group checkbox-group">
        <label>
          <input type="checkbox" v-model="filterByStock" />
          <strong>Només amb estoc</strong>
        </label>
      </div>

      <div class="filter-group reset-group">
        <button class="reset-button" @click="resetFilters">Reiniciar filtres</button>
      </div>
    </div>

    <div class="product-grid">
      <div
        v-for="product in filteredProducts"
        :key="product.id"
        class="product-card"
        @click="goToProducte(product.id)"
      >
        <img :src="getImageSrc(product.imatge)" class="product-image" />
        <h2>{{ product.nom }}</h2>
        <p class="preu">{{ product.preu }} €</p>
        <p :class="['stock-label', getStockClass(product.stock)]">Stock: {{ product.stock }}</p>
        <p v-if="product.botiga" class="botiga-link" @click.stop="goToBotiga(product.botiga_id)">
          🏪 {{ product.botiga.nom }}
        </p>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import "vue-multiselect/dist/vue-multiselect.css";

.container {
  max-width: 1200px;
  margin: auto;
  padding: 20px;
}
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
.reset-button:hover {
  background: #c0392b;
}
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
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.product-card:hover {
  transform: scale(1.02);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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
.stock-verde {
  color: green;
}
.stock-taronja {
  color: orange;
}
.stock-vermell {
  color: red;
}
.botiga-link {
  margin-top: 10px;
  font-size: 14px;
  color: #007bff;
  cursor: pointer;
  text-decoration: underline;
}
.botiga-link:hover {
  color: #0056b3;
}
@media (max-width: 768px) {
  .filters {
    flex-direction: column;
  }
  .price-group {
    flex-direction: column;
  }
  .filter-group {
    width: 100%;
  }
}
</style>
