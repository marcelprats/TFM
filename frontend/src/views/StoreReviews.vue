<template>
  <div class="store-reviews-component">
    <!-- 1. Títol de la botiga -->
    <h1 class="store-title">{{ storeName }}</h1>

    <!-- 2. Secció de gràfic -->
    <section class="summary-section" v-if="reviews.length">
      <h2>Mitjanes per àmbit</h2>
      <div class="chart-container">
        <canvas ref="chartRef"></canvas>
      </div>
    </section>
    <section class="summary-section" v-else>
      <h2>Mitjanes per àmbit</h2>
      <p class="no-data">No hi ha dades de puntuacions encara.</p>
    </section>

    <!-- 3. Llista de valoracions recents -->
    <section class="reviews-section">
      <h2>Valoracions recents</h2>
      <div v-if="reviews.length" class="reviews-list">
        <div v-for="rev in reviews" :key="rev.id" class="review-card">
          <div class="store-details">
            <div v-for="det in rev.storeDetails" :key="det.category" class="detail-line">
              <span class="category">{{ formatCategory(det.category) }}:</span>
              <span class="score">{{ det.score }}★</span>
            </div>
          </div>
          <small class="date">{{ formatDate(rev.created_at) }}</small>
        </div>
      </div>
      <p v-else class="no-data">No hi ha cap valoració de la botiga encara.</p>
    </section>
  </div>
</template>

<script lang="ts">
import { defineComponent, ref, onMounted, nextTick } from 'vue'
import axios from 'axios'
import Chart from 'chart.js/auto'

export default defineComponent({
  name: 'StoreReviews',
  props: {
    botigaId: { type: Number as () => number, required: true }
  },
  setup(props) {
    const storeName = ref<string>('Totaki test')
    const reviews = ref<any[]>([])
    const chartRef = ref<HTMLCanvasElement | null>(null)
    let chartInstance: Chart | null = null

    async function fetchData() {
      try {
        // Carrega nom de la botiga i reviews
        const [storeRes, reviewsRes] = await Promise.all([
          axios.get(`/botigues/${props.botigaId}`),
          axios.get(`/botigues/${props.botigaId}/reviews`)
        ])
        storeName.value = storeRes.data.nom || 'Totaki test'
        reviews.value = reviewsRes.data

        // Espera a que el canvas estigui al DOM i dibuixa
        await nextTick()
        renderChart()
      } catch (e) {
        console.error('Error carregant dades botiga:', e)
      }
    }

    function computeSummary() {
      const sums: Record<string, { total: number; count: number }> = {}
      reviews.value.forEach(r =>
        r.storeDetails.forEach((d: any) => {
          if (!sums[d.category]) sums[d.category] = { total: 0, count: 0 }
          sums[d.category].total += d.score
          sums[d.category].count++
        })
      )
      return Object.entries(sums).map(([cat, { total, count }]) => ({
        category: cat,
        avg: total / count
      }))
    }

    function renderChart() {
      const summary = computeSummary()
      const labels = summary.map(s => formatCategory(s.category))
      const data = summary.map(s => parseFloat(s.avg.toFixed(2)))

      if (chartInstance) chartInstance.destroy()
      if (chartRef.value && data.length) {
        chartInstance = new Chart(chartRef.value, {
          type: 'bar',
          data: {
            labels,
            datasets: [
              {
                label: 'Mitjana puntuació',
                data,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
              }
            ]
          },
          options: {
            responsive: true,
            scales: {
              y: {
                beginAtZero: true,
                max: 5,
                ticks: { stepSize: 1 }
              }
            },
            plugins: {
              legend: { display: false }
            }
          }
        })
      }
    }

    const formatCategory = (cat: string) =>
      cat.charAt(0).toUpperCase() + cat.slice(1)

    const formatDate = (dt: string) =>
      new Date(dt).toLocaleDateString('ca-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      })

    onMounted(fetchData)

    return { storeName, reviews, chartRef, formatCategory, formatDate }
  }
})
</script>

<style scoped>
.store-reviews-component {
  max-width: 800px;
  margin: 0 auto;
  padding: 1rem;
}
.store-title {
  font-size: 2rem;
  text-align: center;
  margin-bottom: 1.5rem;
}
.summary-section,
.reviews-section {
  margin-bottom: 2rem;
}
.chart-container {
  position: relative;
  width: 100%;
  height: 300px;
}
canvas {
  width: 100% !important;
  height: 100% !important;
}
.reviews-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.review-card {
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 1rem;
  background: #fafafa;
}
.store-details {
  display: flex;
  justify-content: space-around;
  margin-bottom: 0.5rem;
}
.detail-line {
  display: flex;
  gap: 0.25rem;
}
.category {
  font-weight: 600;
}
.score {
  color: #007bff;
}
.date {
  display: block;
  text-align: right;
  color: #555;
  font-size: 0.9rem;
}
.no-data,
.no-reviews {
  text-align: center;
  color: #666;
}
</style>
