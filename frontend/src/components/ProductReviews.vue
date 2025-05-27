<template>
  <div class="valoracions-page">
    <h1 class="title">Valoracions de Productes</h1>

    <div v-if="loading" class="loading">Carregant dades...</div>
    <p v-else-if="error" class="error">{{ error }}</p>
    <div v-else>
      <!-- Pendents de valorar -->
      <section class="reviews-list">
        <h2 class="sub-title">Pendent de valorar</h2>
        <ul>
          <li v-for="product in pending" :key="product.id">
            <router-link :to="`/valoracions/${product.id}`" class="product-link">
              {{ product.name }}
            </router-link>
          </li>
          <li v-if="!pending.length" class="empty">Cap producte pendent</li>
        </ul>
      </section>

      <!-- Ja valorats -->
      <section class="reviews-list mt-4">
        <h2 class="sub-title">Ja valorats</h2>
        <ul>
          <li v-for="product in reviewedList" :key="product.id">
            <router-link :to="`/valoracions/${product.id}`" class="product-link">
              {{ product.name }}
            </router-link>
          </li>
          <li v-if="!reviewedList.length" class="empty">Encara no has fet cap valoració</li>
        </ul>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

interface ReserveItem {
  id: number;
  product: { id: number; nom?: string; name?: string };
}
interface Order {
  status: string;
  reserve?: { reserve_items: ReserveItem[] };
  reserve_items?: ReserveItem[];
}
interface Review {
  reserve_item_id: number;
}
interface ProductInfo {
  id: number;
  name: string;
}

const loading = ref(true);
const error = ref('');
const orders = ref<Order[]>([]);
const reviews = ref<Review[]>([]);

axios.defaults.baseURL = 'http://127.0.0.1:8000/api';
axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('userToken')}`;

async function fetchData() {
  try {
    const [ordRes, revRes] = await Promise.all([
      axios.get<Order[]>('/my-orders'),
      axios.get<Review[]>('/my-reviews')
    ]);
    orders.value = ordRes.data || [];
    reviews.value = revRes.data || [];
  } catch (e) {
    error.value = 'Error carregant les valoracions.';
  } finally {
    loading.value = false;
  }
}

onMounted(fetchData);

// All unique completed products
const allProducts = computed<ProductInfo[]>(() => {
  const map = new Map<number, string>();
  orders.value
    .filter(o => o.status === 'completed')
    .forEach(order => {
      const items = order.reserve_items ?? order.reserve?.reserve_items ?? [];
      items.forEach(item => {
        const prod = item.product;
        const name = prod.nom || prod.name || `Product ${prod.id}`;
        map.set(prod.id, name);
      });
    });
  return Array.from(map, ([id, name]) => ({ id, name }));
});

// Map of reserve_item_id to product_id
const reserveMap = computed(() => {
  const m = new Map<number, number>();
  orders.value.forEach(order => {
    const items = order.reserve_items ?? order.reserve?.reserve_items ?? [];
    items.forEach(item => m.set(item.id, item.product.id));
  });
  return m;
});

// List of reviewed product IDs
const reviewedIds = computed<number[]>(
  () => reviews.value
    .map(r => reserveMap.value.get(r.reserve_item_id))
    .filter((id): id is number => id !== undefined)
);

// Pending and reviewed lists
const pending = computed(() => allProducts.value.filter(p => !reviewedIds.value.includes(p.id)));
const reviewedList = computed(() => allProducts.value.filter(p => reviewedIds.value.includes(p.id)));
</script>

<style scoped>
/* Mantenim estils de valoracions-page per coherència */
.sub-title {
  font-size: 1.25rem;
  margin-bottom: 0.75rem;
  color: #28a745;
  border-bottom: 1px solid #ddd;
  padding-bottom: 4px;
}
.reviews-list {
  background-color: #fff;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 1rem;
}
.mt-4 {
  margin-top: 1rem;
}
.reviews-list ul {
  list-style: none;
  padding: 0;
}
.reviews-list li {
  margin-bottom: 0.5rem;
}
.empty {
  color: #888;
  font-style: italic;
}
.product-link {
  color: #007bff;
  text-decoration: none;
}
.product-link:hover {
  text-decoration: underline;
}
.loading {
  font-style: italic;
  text-align: center;
  margin: 1rem 0;
}
.error {
  color: #dc3545;
  margin-bottom: 1rem;
  text-align: center;
}
</style>
