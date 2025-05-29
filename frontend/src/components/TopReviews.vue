<template>
  <div class="top-reviews" ref="container">
    <div class="review-wrapper" :style="{ transform: `rotateY(${angle}deg)` }">
      <div v-if="loading" class="loading">Carregant valoracions…</div>
      <div v-else-if="!sortedReviews.length" class="no-reviews">No hi ha valoracions</div>
      <template v-else>
        <div
          v-for="(rev, idx) in sortedReviews"
          :key="idx"
          class="review-card"
          :style="{ transform: `rotateY(${idx * faceAngle}deg) translateZ(var(--cube-depth))` }"
        >
          <div class="stars">
            <span v-for="n in 5" :key="n">{{ n <= rev.rating ? '★' : '☆' }}</span>
          </div>
          <router-link class="product-name" :to="`/producte/${rev.product_id}`">
            {{ productNames[rev.product_id] || 'Veure producte' }}
          </router-link>
          <p class="comment">“{{ rev.comment }}”</p>
          <small class="byline">— {{ rev.reviewer_name }}</small>
        </div>
      </template>
    </div>

    <button class="nav prev" :disabled="sortedReviews.length <= 1" @click="prev">‹</button>
    <button class="nav next" :disabled="sortedReviews.length <= 1" @click="next">›</button>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import axios from 'axios'

const loading = ref(true)
const reviews = ref<any[]>([])
const productNames = ref<Record<number, string>>({})
const currentIndex = ref(0)
const angle = ref(0)
let timer: ReturnType<typeof setInterval>

async function load() {
  try {
    const res = await axios.get('/reviews')
    reviews.value = res.data
    const ids = Array.from(new Set(reviews.value.map(r => r.product_id)))
    for (const id of ids) {
      try {
        const p = await axios.get(`/productes/${id}`)
        productNames.value[id] = p.data.nom || 'Producte'
      } catch {
        productNames.value[id] = 'Producte'
      }
    }
  } catch (e) {
    console.error('Error carregant reviews:', e)
    reviews.value = []
  } finally {
    loading.value = false
  }
}

const sortedReviews = computed(() => {
  const groups: Record<number, any[]> = {}
  for (const r of reviews.value) {
    ;(groups[r.rating] ??= []).push(r)
  }
  const rates = Object.keys(groups).map(Number).sort((a, b) => b - a)
  const out: any[] = []
  rates.forEach(rate => {
    const group = groups[rate]
    group.sort(() => Math.random() - 0.5)
    out.push(...group)
  })
  return out
})

const faceCount = computed(() => sortedReviews.value.length || 1)
const faceAngle = computed(() => 360 / faceCount.value)

function next() {
  if (!sortedReviews.value.length) return
  currentIndex.value = (currentIndex.value + 1) % sortedReviews.value.length
  angle.value -= faceAngle.value
}
function prev() {
  if (!sortedReviews.value.length) return
  currentIndex.value = (currentIndex.value - 1 + sortedReviews.value.length) % sortedReviews.value.length
  angle.value += faceAngle.value
}

onMounted(async () => {
  await load()
  timer = setInterval(next, 7000)
})

onUnmounted(() => {
  clearInterval(timer)
})
</script>

<style scoped>
.top-reviews {
  --cube-depth: 150px;
  position: relative;
  width: 100%;
  padding-top: 100%;
  perspective: 1000px;
  background: #fafafa;
  overflow: hidden;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.review-wrapper {
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 100%;
  transform-style: preserve-3d;
  transition: transform 2s ease;
}
.loading,
.no-reviews {
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  color: #666;
  font-size: 1rem;
  z-index: 10;
}
.review-card {
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  box-sizing: border-box;
  text-align: center;
  backface-visibility: hidden;
  background: white;
  border-radius: 12px;
}
.stars {
  font-size: 2rem;
  color: #ffb400;
  margin-bottom: 0.75rem;
}
.product-name {
  display: block;
  font-size: 1.125rem;
  font-weight: bold;
  margin-bottom: 0.75rem;
  color: #42b983;
  text-decoration: none;
}
.product-name:hover {
  text-decoration: underline;
}
.comment {
  font-size: 1rem;
  line-height: 1.4;
  margin-bottom: 0.5rem;
  color: #333;
}
.byline {
  font-size: 0.875rem;
  color: #555;
}
.nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(255,255,255,0.8);
  border: none;
  font-size: 2rem;
  padding: 0 0.5rem;
  cursor: pointer;
  border-radius: 4px;
  z-index: 10;
}
.nav.prev { left: 0.5rem; }
.nav.next { right: 0.5rem; }
.nav:disabled {
  opacity: 0.4;
  cursor: default;
}
.nav:hover:not(:disabled) {
  background: rgba(255,255,255,1);
}
</style>
