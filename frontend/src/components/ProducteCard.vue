<template>
  <div class="product-card">
    <img :src="imageSrc" alt="Imatge" class="product-image" />
    <h3 class="product-name">{{ product.nom }}</h3>
    <p class="product-price">{{ formattedPrice }}</p>
  </div>
</template>

<script setup lang="ts">
import { defineProps, computed } from 'vue'

interface Product {
  nom: string
  preu?: number | string
  imatge?: string | null
}

// URL base per a fitxers pujavăls
const BACKEND_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8000'
const DEFAULT_IMAGE = '/img/no-imatge.jpg'

function getImageSrc(path: string | null | undefined): string {
  if (!path) return DEFAULT_IMAGE
  if (path.startsWith('/uploads/')) return BACKEND_URL + path
  if (path.startsWith(BACKEND_URL)) return path
  return BACKEND_URL + '/uploads/' + path
}

const props = defineProps<{ product: Product }>()

const imageSrc = computed(() => getImageSrc(props.product.imatge))
const formattedPrice = computed(() => {
  const n = Number(props.product.preu)
  return isNaN(n) ? '—' : n.toFixed(2) + ' €'
})
</script>

<style scoped>
.product-card {
  background: white;
  border-radius: .5rem;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  padding: 1rem;
  text-align: center;
  max-width: 200px;
}
.product-image {
  width: 100%;
  height: 120px;
  object-fit: cover;
  border-radius: .25rem;
  margin-bottom: .5rem;
}
.product-name {
  margin: .5rem 0;
  font-weight: 600;
  color: #333;
  font-size: 1rem;
}
.product-price {
  font-size: .95rem;
  color: #42b983;
}
</style>
