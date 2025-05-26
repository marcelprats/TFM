<template>
  <div class="all-valoracions-page">
    <h1 class="title">Totes les valoracions</h1>
    <div v-if="loading" class="loading">Carregant...</div>
    <div v-else>
      <div v-if="reviews.length">
        <div v-for="rev in reviews" :key="rev.id" class="review-card">
          <div class="stars">
            <span v-for="n in 5" :key="n">{{ n <= rev.rating ? '★' : '☆' }}</span>
          </div>
          <p class="comment">{{ rev.comment }}</p>
          <small class="byline">
            <router-link :to="`/producte/${rev.product_id}`" class="product-link">
              {{ rev.product_name }}
            </router-link>
            &nbsp;–&nbsp;{{ rev.reviewer_name }} el {{ formatDate(rev.created_at) }}
          </small>
          <div v-if="rev.files && rev.files.length" class="media-gallery">
            <div v-for="(f, idx) in rev.files" :key="idx">
              <img v-if="f.type.startsWith('image')" :src="f.url" class="media-image" />
              <video v-else-if="f.type.startsWith('video')" controls class="media-video">
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
</template>

<script lang="ts">
import { defineComponent, ref, onMounted } from 'vue'
import axios from 'axios'

export default defineComponent({
  name: 'AllValoracions',
  setup() {
    const loading = ref(true)
    const reviews = ref<any[]>([])

    axios.defaults.baseURL = '/api'

    const fetchAllReviews = async () => {
      loading.value = true
      try {
        const res = await axios.get('/reviews')
        reviews.value = res.data
      } catch (e) {
        console.error('Error carregant totes les valoracions:', e)
      } finally {
        loading.value = false
      }
    }

    onMounted(fetchAllReviews)

    const formatDate = (dt: string) =>
      new Date(dt).toLocaleDateString('ca-ES', { day: '2-digit', month: '2-digit', year: 'numeric' })

    return { loading, reviews, formatDate }
  }
})
</script>

<style scoped>
.all-valoracions-page {
  max-width: 900px;
  margin: 0 auto;
  padding: 1rem;
}
.title {
  font-size: 2rem;
  margin-bottom: 1rem;
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
  margin-bottom: 0.5rem;
}
.comment {
  margin: 0.5rem 0;
}
.byline {
  font-size: 0.875rem;
  color: #555;
}
.product-link {
  color: #007bff;
  text-decoration: none;
}
.product-link:hover {
  text-decoration: underline;
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
.no-reviews {
  text-align: center;
  color: #777;
  margin-top: 2rem;
}
.loading {
  text-align: center;
  color: #333;
  margin: 2rem 0;
}
</style>
