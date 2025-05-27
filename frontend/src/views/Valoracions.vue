<template>
  <section class="reviews-component">
    <div v-if="loading" class="loading">Carregant valoracions...</div>
    <div v-else>
      <!-- Product Header -->
      <div class="product-header">
        <img :src="product.imatge_url || '/img/no-imatge.jpg'" alt="Imatge del producte" class="product-image-header" />
        <div class="product-info">
          <h1 class="product-title">{{ product.nom }}</h1>
          <p class="product-summary">
            Has comprat aquest producte <strong>{{ purchaseCount }}</strong> vegades i has valorat-lo <strong>{{ reviewCount }}</strong> vegades.
          </p>
        </div>
      </div>

      <!-- Formulari si hi ha reserves completes pendents de review -->
      <div v-if="pendingItems.length > 0" class="review-form-container">
        <form @submit.prevent="submitReview" class="review-form">
          <label>ID Item Reserva:</label>
          <input type="text" v-model="reserveItemId" readonly class="readonly-input full-width" />

          <label>Puntuació de producte:</label>
          <select v-model.number="rating" class="full-width">
            <option v-for="n in 5" :key="n" :value="n">{{ n }}★</option>
          </select>

          <label>Comentari:</label>
          <textarea v-model="comment" rows="3" class="full-width"></textarea>

          <label>Puja fitxers:</label>
          <input type="file" multiple accept="image/*,video/*" @change="handleFiles" class="full-width" />

          <!-- Valoració de la botiga -->
          <h2>Valora la botiga</h2>
          <label>Ambient de la botiga:</label>
          <select v-model.number="storeAmbient" class="full-width">
            <option v-for="n in 5" :key="n" :value="n">{{ n }}★</option>
          </select>
          <label>Personal:</label>
          <select v-model.number="storePersonal" class="full-width">
            <option v-for="n in 5" :key="n" :value="n">{{ n }}★</option>
          </select>
          <label>Recollida de la comanda:</label>
          <select v-model.number="storeRecollida" class="full-width">
            <option v-for="n in 5" :key="n" :value="n">{{ n }}★</option>
          </select>

          <button type="submit" class="btn full-width">Enviar Review</button>
          <p v-if="error" class="error">{{ error }}</p>
        </form>
      </div>

      <!-- Llista reviews si no cal formulari -->
      <div v-else>
        <h2>Comentaris publicats</h2>
        <div v-if="reviewsList.length">
          <div v-for="rev in reviewsList" :key="rev.id" class="review-card">
            <div class="stars">
              <span v-for="n in 5" :key="n">{{ n <= rev.rating ? '★' : '☆' }}</span>
            </div>
            <p class="comment">{{ rev.comment }}</p>
            <small class="byline">
              <strong>{{ rev.reviewer_name }}</strong> – {{ formatDate(rev.created_at) }}
            </small>
          </div>
        </div>
        <div v-else class="no-reviews">
          <p>No hi ha cap valoració encara.</p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { defineProps, ref, onMounted, computed } from 'vue';
import axios from 'axios';

// Props
const props = defineProps<{ productId: number }>();

// State
const loading = ref(true);
const product = ref<any>(null);
const orders = ref<any[]>([]);
const userReviews = ref<any[]>([]);
const reviewsList = ref<any[]>([]);

const reserveItemId = ref<string>('');
const rating = ref(5);
const comment = ref('');
const files = ref<File[]>([]);
const storeAmbient = ref(5);
const storePersonal = ref(5);
const storeRecollida = ref(5);
const error = ref('');

axios.defaults.baseURL = 'http://127.0.0.1:8000/api';
axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('userToken')}`;

// Helpers
function getReserveItems(order: any): any[] {
  return order.reserve_items ?? order.reserve?.reserve_items ?? [];
}

// Computed lists
const reserveItems = computed(() =>
  orders.value
    .filter(o => o.status === 'completed')
    .flatMap(o => getReserveItems(o))
    .filter((ri: any) => ri.product_id === props.productId)
);

const pendingItems = computed(() =>
  reserveItems.value.filter(ri =>
    !userReviews.value.some(r => r.reserve_item_id === ri.id)
  )
);

const purchaseCount = computed(() => reserveItems.value.length);
const reviewCount = computed(() =>
  userReviews.value.filter(r =>
    reserveItems.value.some(ri => ri.id === r.reserve_item_id)
  ).length
);

// Fetch data
async function fetchData() {
  loading.value = true;
  try {
    const [prodRes, ordRes, revUserRes, revProdRes] = await Promise.all([
      axios.get(`/productes/${props.productId}`),
      axios.get('/my-orders'),
      axios.get('/my-reviews'),
      axios.get(`/productes/${props.productId}/reviews`)
    ]);
    product.value = prodRes.data;
    orders.value = ordRes.data || [];
    userReviews.value = revUserRes.data || [];
    reviewsList.value = revProdRes.data || [];
    reserveItemId.value = pendingItems.value.length > 0 ? String(pendingItems.value[0].id) : '';
  } catch (e) {
    console.error(e);
    error.value = 'Error carregant dades.';
  } finally {
    loading.value = false;
  }
}

function handleFiles(e: Event) {
  const t = e.target as HTMLInputElement;
  files.value = t.files ? Array.from(t.files) : [];
}

async function submitReview() {
  error.value = '';
  if (!reserveItemId.value) return;
  const form = new FormData();
  form.append('reserveItemId', reserveItemId.value);
  form.append('rating', String(rating.value));
  form.append('comment', comment.value);
  form.append('store_ambient', String(storeAmbient.value));
  form.append('store_personal', String(storePersonal.value));
  form.append('store_recollida', String(storeRecollida.value));
  files.value.forEach(f => form.append('files[]', f));
  try {
    await axios.post('/reviews', form, { headers: { 'Content-Type': 'multipart/form-data' } });
    await fetchData();
  } catch (e: any) {
    error.value = e.response?.data?.error || 'Error enviant review';
  }
}

onMounted(fetchData);

// Format date for display
function formatDate(dateStr: string): string {
  const date = new Date(dateStr);
  return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
}
</script>

<style scoped>
.reviews-component { margin: 20px; }
.loading { color: #333; }

/* Product Header Styles */
.product-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
}
.product-image-header {
  width: 100px;
  height: 100px;
  object-fit: cover;
  border-radius: 8px;
}
.product-info {
  display: flex;
  flex-direction: column;
}
.product-title {
  font-size: 1.5rem;
  margin: 0;
  color: #333;
}
.product-summary {
  font-size: 1rem;
  color: #666;
  margin-top: 0.25rem;
}

.review-form-container { border: 1px dashed #aaa; padding: 1rem; background: #fffbea; margin-bottom: 1.5rem; }
.review-form label { display: block; margin-top: 0.5rem; }
.full-width { width: 100%; }
.review-form button { margin-top: 1rem; }

.review-card { border: 1px solid #e0e0e0; border-radius: 6px; padding: 1rem; margin-bottom: 1rem; background: #fafafa; }
.stars { font-size: 1.25rem; color: #ffb400; margin-bottom: 0.5rem; }
.comment { margin: 0.5rem 0; }
.byline { font-size: 0.875rem; color: #555; }
.error { color: red; margin-top: 0.5rem; }
.no-reviews { font-style: italic; color: #666; }

  /* Responsive */
  @media (max-width: 600px) {
    .product-header {
      flex-direction: column;
      align-items: flex-start;
    }
    .product-image-header {
      width: 100%;
      height: auto;
      max-height: 200px;
    }
    .product-title {
      font-size: 1.25rem;
    }
    .product-summary {
      font-size: 0.9rem;
    }
    .review-form,
    .review-card {
      padding: 0.75rem;
    }
    .review-form-container,
    .review-card {
      margin: 1rem 0;
    }
  }
</style>
