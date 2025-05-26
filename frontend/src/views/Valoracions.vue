<template>
  <div class="valoracions-page">
    <h1 class="title">
      Valoracions de:
      <router-link
        v-if="product?.nom"
        :to="`/producte/${product.id}`"
        class="product-link"
      >
        {{ product.nom }}
      </router-link>
    </h1>
    <img
      v-if="product?.imatge_url"
      :src="product.imatge_url"
      alt="Imatge del producte"
      class="product-image"
    />
    <img
      v-else
      src="/img/no-imatge.jpg"
      alt="Sense imatge"
      class="product-image"
    />

    <div v-if="loading" class="loading">Carregant...</div>
    <div v-else>
      <!-- Formulari de valoració si pot valorar -->
      <div v-if="purchased && !reviewed" class="review-form-container">
        <form @submit.prevent="submitReview" class="review-form">
          <label>ID Reserva Item:</label>
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

          <label>Puja imatges o vídeo:</label>
          <input
            type="file"
            ref="fileInput"
            multiple
            accept="image/*,video/*"
            @change="handleFiles"
            class="full-width"
          />

          <button type="submit" class="full-width">Enviar review</button>
          <p v-if="error" class="error">{{ error }}</p>
        </form>
      </div>

      <!-- Llista de reviews i no pot valorar -->
      <div v-else>
        <div v-if="reviews.length">
          <div v-for="rev in reviews" :key="rev.id" class="review-card">
            <div class="stars">
              <span v-for="n in 5" :key="n">{{ n <= rev.rating ? '★' : '☆' }}</span>
            </div>
            <p class="comment">{{ rev.comment }}</p>
            <small class="byline">
              <router-link :to="`/producte/${product.id}`">
                {{ product.nom }}
              </router-link>
              &nbsp;–&nbsp;{{ rev.reviewer_name }} el {{ formatDate(rev.created_at) }}
            </small>
            <!-- mostra multimèdia si existeix -->
            <div v-if="rev.files && rev.files.length" class="media-gallery">
              <div v-for="(f, idx) in rev.files" :key="idx">
                <img
                  v-if="f.type.startsWith('image')"
                  :src="f.url"
                  class="media-image"
                />
                <video
                  v-else-if="f.type.startsWith('video')"
                  controls
                  class="media-video"
                >
                  <source :src="f.url" :type="f.type" />
                </video>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="no-reviews">
          <p>No hi ha cap valoració encara.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

export default defineComponent({
  name: 'Valoracions',
  props: {
    productId: {
      type: Number as () => number | null,
      default: null
    }
  },
  setup(props) {
    const route = useRoute()
    const loading = ref(true)
    const reviews = ref<any[]>([])
    const myOrders = ref<any[]>([])
    const myReviews = ref<any[]>([])
    const purchased = ref(false)
    const reviewed = ref(false)

    const reserveItemId = ref<number | string>('')
    const rating = ref(5)
    const comment = ref('')
    const error = ref('')
    const files = ref<File[]>([]) // multimèdia

    const product = ref<any>(null)

    axios.defaults.baseURL = '/api'

    // Carregar dades producte
    const fetchProduct = async () => {
      try {
        const res = await axios.get(`/productes/${props.productId}`)
        product.value = res.data
      } catch (e) {
        console.error('Error producte:', e)
      }
    }

    // Carregar comandes i reviews d'usuari
    const fetchUserData = async () => {
      try {
        const [ordRes, revRes] = await Promise.all([
          axios.get('/my-orders'),
          axios.get('/my-reviews')
        ])
        myOrders.value = ordRes.data
        myReviews.value = revRes.data

        // Autoassignar reserveItemId si hi ha reserva completada
        const found = myOrders.value
          .flatMap(o => o.reserve_items || o.reserveItems || (o.reserve && (o.reserve.reserve_items || o.reserve.reserveItems)) || [])
          .find((ri: any) => ri.product_id === props.productId)
        if (found) reserveItemId.value = found.id
      } catch (e) {
        console.error('Error carregant dades d’usuari:', e)
      }
    }

    // Helper per obtenir ítems de reserva
    function getReserveItems(order: any): any[] {
      if (Array.isArray(order.reserveItems)) return order.reserveItems
      if (Array.isArray(order.reserve_items)) return order.reserve_items
      if (order.reserve && Array.isArray(order.reserve.reserveItems)) return order.reserve.reserveItems
      if (order.reserve && Array.isArray(order.reserve.reserve_items)) return order.reserve.reserve_items
      return []
    }

    // Computar flags
    const computeFlags = () => {
      if (props.productId === null) return
      purchased.value = myOrders.value.some(order => {
        const items = getReserveItems(order)
        return items.some((ri: any) => ri.product_id === props.productId)
      })
      reviewed.value = myReviews.value.some((r: any) => r.product_id === props.productId)
      console.log('👉 purchased:', purchased.value, '– reviewed:', reviewed.value)
    }

    // Carregar reviews globals o per producte
    const fetchReviews = async () => {
      loading.value = true
      try {
        let url = '/reviews'
        if (props.productId !== null) url = `/productes/${props.productId}/reviews`
        if (purchased.value && !reviewed.value && props.productId !== null) {
          reviews.value = []
        } else {
          const res = await axios.get(url)
          reviews.value = res.data
        }
      } catch (e) {
        console.error('Error carregant reviews:', e)
        reviews.value = []
      } finally {
        loading.value = false
      }
    }

    // Gestionar fitxers
    const handleFiles = (e: Event) => {
      const target = e.target as HTMLInputElement
      files.value = target.files ? Array.from(target.files) : []
    }

    // Enviar review amb FormData
    const submitReview = async () => {
      error.value = ''
      const form = new FormData()
      form.append('reserveItemId', String(reserveItemId.value))
      form.append('rating', String(rating.value))
      form.append('comment', comment.value)
      files.value.forEach((f, i) => form.append('files[]', f))

      try {
        const res = await axios.post('/reviews', form, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        if (res.status === 201) {
          reviewed.value = true
          fetchReviews()
        }
      } catch (e: any) {
        error.value = e.response?.data?.error || 'Error enviant review'
      }
    }

    onMounted(async () => {
      await fetchProduct()
      await fetchUserData()
      computeFlags()
      fetchReviews()
    })

    watch(() => route.params.productId, async () => {
      await fetchProduct()
      await fetchUserData()
      computeFlags()
      fetchReviews()
    })

    const formatDate = (dt: string) =>
      new Date(dt).toLocaleDateString('ca-ES', { day: '2-digit', month: '2-digit', year: 'numeric' })

    return {
      loading,
      reviews,
      purchased,
      reviewed,
      reserveItemId,
      rating,
      comment,
      error,
      files,
      product,
      handleFiles,
      submitReview,
      formatDate
    }
  }
})
</script>

<style scoped>
.valoracions-page {
  max-width: 800px;
  margin: 0 auto;
  padding: 1rem;
}
.title {
  font-size: 1.75rem;
  margin-bottom: 0.5rem;
}
.product-link {
  color: #007bff;
  text-decoration: none;
}
.product-link:hover {
  text-decoration: underline;
}
.product-image {
  max-width: 200px;
  border-radius: 8px;
  margin-bottom: 1rem;
}
.full-width {
  width: 100%;
}
.readonly-input {
  background: #f0f0f0;
  border: 1px solid #ccc;
  padding: 0.5rem;
  margin-top: 0.25rem;
}
.review-form-container {
  border: 1px dashed #aaa;
  padding: 1rem;
  margin-bottom: 1.5rem;
  background: #fffbea;
}
.review-form label {
  display: block;
  margin-top: 0.5rem;
}
.review-form select,
.review-form textarea {
  margin-top: 0.25rem;
}
.review-form button {
  margin-top: 1rem;
  padding: 0.5rem 1.25rem;
  border: none;
  border-radius: 4px;
  display: block;
  width: 100%;
}
.error {
  color: red;
  margin-top: 0.5rem;
}
.review-card {
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  padding: 1rem;
  margin-bottom: 1rem;
  background: #fafafa;
}
.stars {
  font-size: 1.25rem;
  color: #ffb400;
}
.media-gallery {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.5rem;
}
.media-image {
  max-width: 100px;
  border-radius: 4px;
}
.media-video {
  max-width: 200px;
  border-radius: 4px;
}
</style>
