<template>
  <section class="home-products-section">
    <h2 class="section-title">Últims Productes</h2>

    <div
      class="carousel-wrapper"
      @mouseenter="stopAuto"
      @mouseleave="startAuto"
    >
      <!-- Fletxa anterior -->
      <button
        class="nav-button prev"
        @click="goPrev"
        @mouseenter="stopAuto"
        @mouseleave="startAuto"
      >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
          <path fill="white" d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
        </svg>
      </button>

      <!-- Pista de slides -->
      <div class="carousel-track" ref="track">
        <div
          v-for="product in products"
          :key="product.id"
          class="carousel-slide"
          @mouseenter="stopAuto"
          @mouseleave="startAuto"
          @click="goToProduct(product.id)"
        >
          <ProducteCard :product="product" />
        </div>
      </div>

      <!-- Fletxa següent -->
      <button
        class="nav-button next"
        @click="goNext"
        @mouseenter="stopAuto"
        @mouseleave="startAuto"
      >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
          <path fill="white" d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6z"/>
        </svg>
      </button>
    </div>
  </section>
</template>

<script setup lang="ts">
import { defineProps, ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import ProducteCard from './ProducteCard.vue'

interface Product {
  id: number
  nom: string
  preu?: number | string
  imatge?: string | null
}

const props = defineProps<{ products: Product[] }>()
const products = props.products
const track = ref<HTMLDivElement|null>(null)
let autoInterval: ReturnType<typeof setInterval> | null = null
const router = useRouter()

/** Mou un sol slide en la direcció indicada */
function scrollByOne(direction: 1|-1) {
  if (!track.value) return
  const slide = track.value.querySelector<HTMLElement>('.carousel-slide')
  if (!slide) return
  const style = getComputedStyle(slide)
  const gap = parseFloat(style.marginRight)
  const width = slide.clientWidth + gap
  track.value.scrollBy({ left: direction * width, behavior: 'smooth' })
}

function goNext() { scrollByOne(1) }
function goPrev() { scrollByOne(-1) }

/** Navega a la pàgina del producte */
function goToProduct(id: number) {
  router.push(`/producte/${id}`)
}

/** Auto-scroll cada 3s, es para al passar el ratolí per sobre */
function startAuto() {
  stopAuto()
  autoInterval = setInterval(() => scrollByOne(1), 3000)
}
function stopAuto() {
  if (autoInterval) {
    clearInterval(autoInterval)
    autoInterval = null
  }
}

onMounted(() => startAuto())
onBeforeUnmount(() => stopAuto())
</script>

<style scoped>
.home-products-section {
  margin: 2rem 0;
}

.section-title {
  font-size: 1.75rem;
  margin-bottom: 1rem;
  color: #064e3b;
}

.carousel-wrapper {
  position: relative;
  overflow: visible;
  padding: 1rem 2rem;
}

.carousel-track {
  display: flex;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  scroll-behavior: smooth;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
  padding: 1rem 0;
}
.carousel-track::-webkit-scrollbar {
  display: none;
}

.carousel-slide {
  flex: 0 0 auto;
  scroll-snap-align: start;
  margin-right: 16px;
  transition: transform 0.3s ease;
  cursor: pointer;
}
.carousel-slide:hover {
  transform: scale(1.05);
  z-index: 1;
}

.nav-button {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: #42b983;
  border: none;
  padding: 0.4rem;
  cursor: pointer;
  z-index: 10;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s, opacity 0.2s;
}
.nav-button:hover {
  background: #369e6b;
  opacity: 0.9;
}
.nav-button.prev { left: 0.5rem; }
.nav-button.next { right: 0.5rem; }

.nav-button svg {
  width: 1.5rem;
  height: 1.5rem;
}
.nav-button svg path {
  fill: white !important;
  stroke: none;
}

@media (max-width: 450px) {
  .carousel-track {
    scroll-snap-type: x mandatory;
    scroll-padding-left: 0;
  }
  .carousel-slide {
    scroll-snap-align: center;
  }
}
</style>
