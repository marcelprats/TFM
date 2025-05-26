<template>
  <section class="reviews-component">
    <div v-if="loading" class="loading">Carregant valoracions...</div>
    <div v-else>
      <!-- Formulari si comprat i no revisat -->
      <div v-if="purchased && !reviewed" class="review-form-container">
        <form @submit.prevent="submitReview" class="review-form">
          <label>ID Item Reserva:</label>
          <input
            type="text"
            v-model="reserveItemId"
            readonly
            class="readonly-input full-width"
          />
          <label>Puntuació:</label>
          <select v-model.number="rating" class="full-width">
            <option v-for="n in 5" :key="n" :value="n">{{ n }}★</option>
          </select>
          <label>Comentari:</label>
          <textarea v-model="comment" rows="3" class="full-width"></textarea>
          <label>Puja fitxers:</label>
          <input
            type="file"
            multiple
            accept="image/*,video/*"
            @change="handleFiles"
            class="full-width"
          />
          <button type="submit" class="btn full-width">Enviar Review</button>
          <p v-if="error" class="error">{{ error }}</p>
        </form>
      </div>
      <!-- Llista reviews si no cal formulari -->
      <div v-else>
        <div v-if="reviews.length">
          <div v-for="rev in reviews" :key="rev.id" class="review-card">
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
import { defineProps, ref, onMounted, watch } from 'vue'
import axios from 'axios'


// Props
const props = defineProps<{ productId: number }>()

// State
const reviews = ref<any[]>([])
const loading = ref(true)
const myOrders = ref<any[]>([])
const myReviews = ref<any[]>([])
const purchased = ref(false)
const reviewed = ref(false)
const reserveItemId = ref<number|string>('')
const rating = ref(5)
const comment = ref('')
const error = ref('')
const files = ref<File[]>([])

axios.defaults.baseURL = 'http://127.0.0.1:8000/api'
axios.defaults.headers.common['Authorization'] = `Bearer ${localStorage.getItem('userToken')}`

// Helpers
function getReserveItems(order: any): any[] {
  return order.reserveItems || order.reserve_items || []
}

async function fetchUserData() {
  try {
    const [ordRes, revRes] = await Promise.all([
      axios.get('/my-orders'),
      axios.get('/my-reviews')
    ])
    myOrders.value = ordRes.data
    myReviews.value = revRes.data
    // Assign reserveItemId
    const found = myOrders.value
      .flatMap(o => getReserveItems(o))
      .find((ri: any) => ri.product_id === props.productId)
    if (found) reserveItemId.value = found.id
  } catch (e) {
    console.error('Error fetching user data:', e)
  }
}

function computeFlags() {
  purchased.value = myOrders.value.some(o =>
    getReserveItems(o).some((ri: any) => ri.product_id === props.productId)
  )
  reviewed.value = myReviews.value.some(r => r.product_id === props.productId)
}

async function fetchReviews() {
  loading.value = true
  try {
    if (purchased.value && !reviewed.value) {
      reviews.value = []
    } else {
      const res = await axios.get(`/productes/${props.productId}/reviews`)
      reviews.value = res.data
    }
  } catch (e) {
    console.error('Error fetching reviews:', e)
    reviews.value = []
  } finally {
    loading.value = false
  }
}

function handleFiles(e: Event) {
  const t = e.target as HTMLInputElement
  files.value = t.files ? Array.from(t.files) : []
}

async function submitReview() {
  error.value = ''
  const form = new FormData()
  form.append('reserveItemId', String(reserveItemId.value))
  form.append('rating', String(rating.value))
  form.append('comment', comment.value)
  files.value.forEach(f => form.append('files[]', f))

  try {
    const res = await axios.post('/reviews', form, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    if (res.status === 201) {
      reviewed.value = true
      await fetchReviews()
    }
  } catch (e: any) {
    error.value = e.response?.data?.error || 'Error enviant review'
  }
}

function formatDate(dateStr: string) {
  const date = new Date(dateStr)
  return date.toLocaleString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',

  })
}

// Lifecycle
onMounted(async () => {
  await fetchUserData()
  computeFlags()
  await fetchReviews()
})

// Re-fetch if productId changes
watch(() => props.productId, async () => {
  await fetchUserData()
  computeFlags()
  await fetchReviews()
})
</script>

<style scoped>
.reviews-component { margin: 2rem 0; }
.loading { color: #333; }
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
</style>
