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
import { defineProps, ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
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
const track = ref<HTMLDivElement | null>(null)
let autoInterval: ReturnType<typeof setInterval> | null = null
const router = useRouter()
const currentIdx = ref(0)
let isUserScrolling = false
let scrollTimeout: ReturnType<typeof setTimeout> | null = null

function scrollToIdx(idx: number, opts: ScrollToOptions = { behavior: 'smooth' }) {
  if (!track.value) return
  const slides = track.value.querySelectorAll<HTMLElement>('.carousel-slide')
  const slide = slides[idx]
  if (!slide) return
  const offsetLeft = slide.offsetLeft
  track.value.scrollTo({ left: offsetLeft, behavior: opts.behavior })
  currentIdx.value = idx
}

/**
 * Mou un sol slide en la direcció indicada i actualitza l'índex
 */
function scrollByOne(direction: 1 | -1) {
  if (!track.value) return
  const slides = track.value.querySelectorAll<HTMLElement>('.carousel-slide')
  if (!slides.length) return
  let nextIdx = currentIdx.value + direction
  if (nextIdx >= slides.length || nextIdx < 0) {
    scrollToIdx(0)
    return
  }
  scrollToIdx(nextIdx)
}

function goNext() { scrollByOne(1) }
function goPrev() { scrollByOne(-1) }

function goToProduct(id: number) {
  router.push(`/producte/${id}`)
}

function startAuto() {
  stopAuto()
  autoInterval = setInterval(() => {
    // Sempre calcula l'índex visible actual
    updateCurrentIdxFromScroll()
    scrollByOne(1)
  }, 3000)
}
function stopAuto() {
  if (autoInterval) {
    clearInterval(autoInterval)
    autoInterval = null
  }
}

/**
 * Calcula a quin index està actualment la vista del carousel
 */
function updateCurrentIdxFromScroll() {
  if (!track.value) return
  const slides = track.value.querySelectorAll<HTMLElement>('.carousel-slide')
  let minDiff = Infinity
  let idx = 0
  slides.forEach((slide, i) => {
    const diff = Math.abs(track.value!.scrollLeft - slide.offsetLeft)
    if (diff < minDiff) {
      minDiff = diff
      idx = i
    }
  })
  currentIdx.value = idx
}

/**
 * Quan l'usuari fa scroll manual, atura autoscroll i actualitza index després
 */
function onTrackScroll() {
  if (!isUserScrolling) {
    stopAuto()
    isUserScrolling = true
  }
  if (scrollTimeout) clearTimeout(scrollTimeout)
  // Espera a que pari d'arrossegar per actualitzar index i reactivar auto
  scrollTimeout = setTimeout(() => {
    updateCurrentIdxFromScroll()
    isUserScrolling = false
    startAuto()
  }, 300)
}

/**
 * Quan passa el ratolí per sobre, atura auto, quan surt, reactivació i ajusta a la posició
 */
function onPointerEnter() {
  stopAuto()
}
function onPointerLeave() {
  updateCurrentIdxFromScroll()
  startAuto()
}

onMounted(() => {
  nextTick(() => scrollToIdx(0))
  if (track.value) {
    track.value.addEventListener('scroll', onTrackScroll)
  }
  startAuto()
})
onBeforeUnmount(() => {
  stopAuto()
  if (track.value) {
    track.value.removeEventListener('scroll', onTrackScroll)
  }
})
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
