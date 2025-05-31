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
          <li v-for="item in pendingReserveItems" :key="item.id">
            <router-link :to="`/valoracions/${item.id}`" class="product-link">
              {{ item.productName }}
            </router-link>
          </li>
          <li v-if="!pendingReserveItems.length" class="empty">Cap producte pendent</li>
        </ul>
      </section>

      <!-- Ja valorats -->
      <section class="reviews-list mt-4">
        <h2 class="sub-title">Ja valorats</h2>
        <ul>
          <li v-for="item in reviewedReserveItems" :key="item.id">
            <router-link :to="`/valoracions/${item.id}`" class="product-link">
              {{ item.productName }}
            </router-link>
          </li>
          <li v-if="!reviewedReserveItems.length" class="empty">Encara no has fet cap valoració</li>
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

const loading = ref(true);
const error = ref('');
const orders = ref<Order[]>([]);
const reviews = ref<Review[]>([]);

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

// Tots els reserve_items de comandes completades (mostrem cada unitat per valorar)
const allCompletedReserveItems = computed(() => {
  return orders.value
    .filter(o => o.status === 'completed')
    .flatMap(order => order.reserve_items ?? order.reserve?.reserve_items ?? [])
    .map(item => ({
      id: item.id,
      productId: item.product.id,
      productName: item.product.nom || item.product.name || `Producte ${item.product.id}`,
    }));
});

// Tots els reserve_item_id ja valorats
const reviewedReserveItemIds = computed(() =>
  reviews.value.map(r => r.reserve_item_id)
);

// Pendents: reserve_items que no han estat valorats
const pendingReserveItems = computed(() =>
  allCompletedReserveItems.value.filter(item => !reviewedReserveItemIds.value.includes(item.id))
);

// Fets: reserve_items que sí han estat valorats
const reviewedReserveItems = computed(() =>
  allCompletedReserveItems.value.filter(item => reviewedReserveItemIds.value.includes(item.id))
);
</script>

<style scoped>
.valoracions-page {
  max-width: 600px;
  margin: 40px auto;
  padding: 20px;
  background: #fafbfc;
  border-radius: 16px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}
.title {
  font-size: 2rem;
  font-weight: 700;
  color: #212c3a;
  margin-bottom: 24px;
  text-align: center;
}
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