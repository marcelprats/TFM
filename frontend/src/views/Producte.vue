<template>
  <div class="product-page">
    <template v-if="product">
      <!-- ─── Encabezado del producto ─────────────────────────────────── -->
      <div class="product-header">
        <!-- Columna imagen (clicable para lightbox) -->
        <div
          class="column image-col"
          @click="openImage(getImageSrc(product.imatge))"
        >
          <div class="image-container">
            <img
              :src="getImageSrc(product.imatge)"
              :alt="product.nom"
            />
          </div>
        </div>

        <!-- Columna info -->
        <div class="column info-col">
          <h1 class="product-title">{{ product.nom }}</h1>
          <p class="price"><strong>Preu:</strong> {{ formattedPrice }}</p>
          <p class="store">
            <strong>Botiga: </strong>
            <router-link
              v-if="product.botiga"
              :to="`/info-botiga/${product.botiga.id}`"
              class="store-link"
            >
              {{ product.botiga.nom }}
            </router-link>
            <span v-else>No disponible</span>
          </p>
          <p class="stock">
            <strong>Stock disponible:</strong>
            {{ product.stock ?? "No disponible" }}
          </p>
        </div>

        <!-- Columna reservar -->
        <div class="column reserve-col">
          <div class="reserve-section">
            <label class="reserve-label">
              <strong>Quantitat a reservar:</strong>
            </label>
            <div class="quantity-selector">
              <button class="quantity-btn" @click="decreaseQuantity">−</button>
              <input
                type="number"
                v-model.number="quantity"
                :min="1"
                :max="product.stock"
              />
              <button class="quantity-btn" @click="increaseQuantity">+</button>
            </div>

            <button
              v-if="!inCart"
              class="btn reserve-btn"
              @click="handleAddItem"
            >
              Afegir al Carro
            </button>

            <template v-else>
              <button class="btn view-cart-btn" @click="goToCart">
                Veure Carro
              </button>
              <button class="btn trash-btn" @click="removeCartItem">
                <i class="fa-solid fa-trash"></i>
              </button>
            </template>

            <div v-if="addSuccessMessage" class="add-message">
              {{ addSuccessMessage }}
            </div>
          </div>
        </div>
      </div>

      <!-- ─── Secció Descripció ───────────────────────────────────────── -->
      <div class="description-section">
        <h2>Descripció</h2>
        <p>{{ product.descripcio }}</p>
      </div>

      <!-- ─── Secció Valoracions ───────────────────────────────────────── -->
      <div class="description-section">
        <h2>Valoracions</h2>
        <Valoracions :productId="product.id" />
      </div>

      <!-- ─── Secció Info Botiga ─────────────────────────────────────── -->
      <div class="shop-info" v-if="storeData">
        <h2>Informació de {{ product.botiga?.nom }}</h2>
        <div class="shop-info-grid">
          <!-- Mini-mapa + botó -->
          <div class="map-col">
            <div ref="miniMapRef" class="shop-mini-map"></div>
            <router-link
              :to="`/info-botiga/${product.botiga!.id}`"
              class="more-info-btn"
            >
              Més informació
            </router-link>
          </div>
          <!-- Taula setmanal -->
          <div class="hours-col">
            <table class="shop-hours">
              <thead>
                <tr><th>Dia</th><th>Horari</th></tr>
              </thead>
              <tbody>
                <tr v-for="dia in diesSetmana" :key="dia">
                  <td>{{ dia }}</td>
                  <td>{{ horarisPerDia[dia] || 'Tancat' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Productes relacionats -->
      <div
        class="related-products-container"
        v-if="relatedProducts.length"
      >
        <h2>Productes que et poden interessar</h2>
        <div class="related-grid">
          <div
            v-for="related in relatedProducts"
            :key="related.id"
            class="related-card"
            @click="goToProduct(related.id)"
          >
            <img
              :src="getImageSrc(related.imatge)"
              :alt="related.nom ?? related.name"
              class="related-image"
            />
            <div class="related-info">
              <h3 class="related-name">
                {{ related.nom ?? related.name ?? "No disponible" }}
              </h3>
              <p class="related-price">
                <strong>Preu:</strong>
                {{
                  isNaN(+((related.preu as any) || (related as any).price))
                    ? "No disponible"
                    : (+((related.preu as any) || (related as any).price)).toFixed(2) + " €"
                }}
              </p>
              <p class="related-store">
                <strong>Botiga: </strong>
                {{ related.botiga?.nom ?? (related as any).store?.name ?? "N/A" }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- ─── Últims Productes (Carousel) ────────────────────────────── -->
      <section
        class="latest-products-container"
        v-if="latestProducts.length"
      >
        <ProductCarousel :products="latestProducts" />
      </section>
    </template>

    <template v-else>
      <p class="loading">Carregant producte...</p>
    </template>

    <!-- ─── Lightbox per a imatge ───────────────────────────────────── -->
    <div
      v-if="showImageModal"
      class="image-modal"
      @click.self="closeImage"
    >
      <button class="close-btn" @click="closeImage">&times;</button>
      <img :src="modalImageSrc" class="modal-img" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import L from 'leaflet'
import { fetchProductById, fetchProducts } from '../services/authService'
import { useCartStore } from '../stores/cartStore'
import ProductCarousel from '../components/ProductCarousel.vue'
import Valoracions from '../components/Valoracions.vue'

interface Store {
  id: number
  nom: string
  latitude: number
  longitude: number
  horaris: { dia: string; obertura: string; tancament: string }[]
}

interface Product {
  id: number
  nom: string
  descripcio: string
  preu: number | string
  imatge: string | null
  stock?: number
  botiga?: { id: number; nom: string }
  vendor?: { id: number; name: string }
}

// 1) Agafem la BACKEND URL des de l'entorn
const BACKEND = import.meta.env.VITE_BACKEND_URL!

const route = useRoute()
const router = useRouter()
const cartStore = useCartStore()

const product = ref<Product|null>(null)
const allProducts = ref<Product[]>([])
const relatedProducts = ref<Product[]>([])
const quantity = ref(1)
const addSuccessMessage = ref('')

const storeData = ref<Store|null>(null)
const miniMapRef = ref<HTMLDivElement|null>(null)
const diesSetmana = [
  "Dilluns","Dimarts","Dimecres","Dijous",
  "Divendres","Dissabte","Diumenge"
]

const horarisPerDia = computed<Record<string,string>>(() => {
  if (!storeData.value) return {}
  return storeData.value.horaris.reduce((out, h) => {
    const dia = h.dia.charAt(0).toUpperCase() + h.dia.slice(1)
    const slot = `${h.obertura.slice(0,5)}–${h.tancament.slice(0,5)}`
    out[dia] = out[dia] ? out[dia] + ', ' + slot : slot
    return out
  }, {} as Record<string,string>)
})

async function loadProduct() {
  addSuccessMessage.value = ''
  product.value = await fetchProductById(route.params.id as string)
  allProducts.value = await fetchProducts()

  if (product.value?.botiga) {
    const { data } = await axios.get<Store>(
      `/botigues/${product.value.botiga.id}`
    )
    storeData.value = data
    await nextTick()
    initMiniMap()
  }

  updateRelatedProducts()
  await cartStore.fetchCart()
}

function initMiniMap() { /* ... sense canvis ... */ }
function updateRelatedProducts() { /* ... */ }

const latestProducts = computed(() =>
  [...allProducts.value]
    .sort((a,b)=>b.id - a.id)
    .slice(0,25)
    .map(p=>({
      id: p.id,
      nom: p.nom,
      preu: p.preu,
      imatge: p.imatge
    }))
)

const cartItem = computed(() =>
  cartStore.items.find(i=>i.product.id===product.value?.id)
)
const inCart = computed(()=>!!cartItem.value)
watch(cartItem, item=> quantity.value = item?.quantity ?? 1)

const showImageModal = ref(false)
const modalImageSrc = ref('')

function openImage(src: string){
  modalImageSrc.value = src
  showImageModal.value = true
}
function closeImage(){
  showImageModal.value = false
  modalImageSrc.value = ''
}

const formattedPrice = computed(()=>
  product.value
    ? `${(+product.value.preu).toFixed(2)} €`
    : '—'
)

// 2) Prefixar imatges amb BACKEND
function getImageSrc(path: string|null): string {
  if (!path) return '/img/no-imatge.jpg'
  return path.startsWith('http')
    ? path
    : `${BACKEND}${path}`
}

function goToProduct(id: number){ router.push(`/producte/${id}`) }
function goToCart(){ router.push('/cart') }

async function decreaseQuantity(){ /* ... */ }
async function increaseQuantity(){ /* ... */ }
async function handleAddItem(){ /* ... */ }
async function removeCartItem(){ /* ... */ }
async function updateCartQuantity(){ /* ... */ }

onMounted(loadProduct)
watch(()=>route.params.id, loadProduct)
</script>

<style scoped>
.product-page {
  background: white;
  padding: 50px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

/* ─── Header ───────────────────────────────────────────────────── */
.product-header {
  display: flex;
  flex-wrap: wrap;
  gap: 24px;
  padding: 24px;
  width: 100%;
  max-width: 1100px;
}
.column { flex: 1; min-width: 280px; }
.image-col { cursor: zoom-in; }
.image-col .image-container {
  width: 100%; height: 320px;
  border-radius: 12px; overflow: hidden;
}
.image-col img {
  width: 100%; height: 100%;
  object-fit: cover; transition: transform .5s;
}
.image-col img:hover { transform: scale(1.1) }
.info-col { display: flex; flex-direction: column; gap: 10px; }
.product-title { font-size: 28px; font-weight: 600; color: #2d2d2d; }
.price { font-size: 24px; color: #2e7d32; font-weight: 500; }
.store-link, .vendor-link {
  color: #42b983; text-decoration: none; transition: .3s;
}
.store-link:hover, .vendor-link:hover {
  color: #368c6e; text-decoration: underline;
}
.stock { font-size: 16px; color: #555; margin-top: 4px; }

.reserve-col { display: flex; align-items: center; justify-content: center; }
.reserve-section {
  border: 1px solid #ddd; background: #fafafa;
  border-radius: 12px; padding: 20px;
  width: 100%; max-width: 280px; text-align: center;
}
.reserve-label { margin-bottom: 10px; font-weight: 500; color: #333; }
.quantity-selector {
  display: flex; gap: 8px; justify-content: center; align-items: center;
  margin-bottom: 16px;
}
.quantity-btn {
  background: #42b983; color: white; border: none;
  padding: 8px 14px; font-size: 18px; border-radius: 6px;
  cursor: pointer; transition: .3s;
}
.quantity-btn:hover { background: #368c6e; }
.quantity-selector input {
  width: 60px; padding: 6px; text-align: center;
  font-size: 16px; border: 1px solid #ccc; border-radius: 6px;
}

.btn {
  font-size: 16px; padding: 10px 16px; border-radius: 6px;
  border: none; cursor: pointer; transition: .2s; margin-top: 8px;
}
.reserve-btn { background: #42b983; color: white; }
.reserve-btn:hover { background: #368c6e; }
.view-cart-btn { background: #42b983; color: white; margin-right: 4px; }
.view-cart-btn:hover { background: #368c6e; }
.trash-btn { background: #e53935; color: white; margin-left: 4px; }
.trash-btn:hover { background: #c62828; }
.add-message { margin-top: 10px; color: #42b983; font-weight: 500; }

/* ─── Descripció ────────────────────────────────────────────────── */
.description-section {
  width: 100%; max-width: 1100px;
  padding: 24px; margin-top: 20px;
}
.description-section h2 {
  font-size: 22px; color: #333; margin-bottom: 12px;
}

/* ─── Info Botiga ──────────────────────────────────────────────── */
.shop-info {
  width: 100%; max-width: 1100px;
  margin: 2rem auto; padding: 24px;
}
.shop-info-grid {
  display: flex; gap: 24px;
  flex-wrap: wrap; align-items: flex-start;
}
.hours-col { flex: 1; min-width: 280px; display: flex; flex-direction: column; }
.shop-hours {
  width: 100%; border-collapse: collapse;
  display: block; overflow-y: auto;
}
.shop-hours th, .shop-hours td {
  border: 1px solid #ddd; padding: 8px; text-align: left;
}
.shop-hours th { background: #f4f4f4; }
.map-col { flex: 1; min-width: 280px; display: flex; flex-direction: column; }
.shop-mini-map {
  width: 100%; aspect-ratio: 4 / 3;
  min-height: 200px; border-radius: 8px; overflow: hidden;
}
.more-info-btn {
  margin-top: 1rem; align-self: flex-start;
}

/* Botó dins del popup de Leaflet */
.btn-maps,
.more-info-btn {
  background-color: #42b983;
  color: white;
  border: none;
  padding: 8px 14px;
  font-size: 18px;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}
.btn-maps:hover,
.more-info-btn:hover {
  background-color: #368c6e;
}

/* ─── Relacionats ───────────────────────────────────────────────── */
.related-products-container {
  width: 100%; max-width: 1100px;
  padding: 24px; margin-top: 24px;
}
.related-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 20px;
}
.related-card {
  background: #fff; border: 1px solid #ddd; border-radius: 8px;
  overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,0.06);
  cursor: pointer; transition: transform .3s;
}
.related-card:hover { transform: scale(1.03); }
.related-image { width: 100%; height: 150px; object-fit: cover; }
.related-info { padding: 10px; }
.related-name { font-size: 16px; font-weight: 600; color: #2d2d2d; }
.related-price { color: #42b983; font-weight: 500; }

/* ─── Últims Productes ───────────────────────────────────────── */
.latest-products-container {
  width: 100%; max-width: 1100px;
  margin: 2rem auto; padding: 0 20px;
}

/* ─── Lightbox Styles ───────────────────────────────────────────── */
.image-modal {
  position: fixed; top: 0; left: 0;
  width: 100vw; height: 100vh;
  background: rgba(0,0,0,0.8);
  display: flex; align-items: center; justify-content: center;
  z-index: 10000;
}
.modal-img {
  max-width: 90%; max-height: 90%;
  border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,0.5);
}
.close-btn {
  position: absolute; top: 1rem; right: 1rem;
  background: transparent; border: none;
  font-size: 2rem; color: white; cursor: pointer; line-height: 1;
}

/* ─── Responsive ──────────────────────────────────────────────── */
@media (max-width: 768px) {
  .product-header { flex-direction: column; }
  .related-grid {
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  }
  .shop-info-grid {
    flex-direction: column;
  }
  .hours-col,
  .map-col {
    flex: 1 1 100%;
  }
  .shop-mini-map {
    height: 250px;  /* quasi quadrat */
    width: 100%;
    margin: 0 auto;
  }
}
</style>
