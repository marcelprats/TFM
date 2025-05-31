<!-- name=src/views/Producte.vue -->
<template>
  <div class="product-page">
    <template v-if="product">
      <!-- ─── Encabezado del producto ─────────────────────────────────── -->
      <div class="product-header">
        <!-- Columna imagen (clicable para lightbox) -->
        <div class="column image-col" @click="openImage(getImageSrc(product.imatge))">
          <div class="image-container">
            <img :src="getImageSrc(product.imatge)" :alt="product.nom" />
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
            <strong>Stock disponible:</strong> {{ product.stock ?? "No disponible" }}
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

      <!-- ─── Secció Info Botiga (millorada) ────────────────────────── -->
      <div class="shop-info-card" v-if="storeData">
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
      <div class="related-products-container" v-if="relatedProducts.length">
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
              :alt="related.nom"
              class="related-image"
            />
            <div class="related-info">
              <h3 class="related-name">
                {{ (related as any).nom ?? (related as any).name ?? "No disponible" }}
              </h3>
              <p class="related-price">
                <strong>Preu:</strong>
                {{
                  isNaN(+((related as any).preu ?? (related as any).price))
                    ? "No disponible"
                    : (+((related as any).preu ?? (related as any).price)).toFixed(2) + " €"
                }}
              </p>
              <p class="related-store">
                <strong>Botiga: </strong>
                {{ (related as any).botiga?.nom ?? (related as any).store?.name ?? "No disponible" }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- ─── Últims Productes (Carousel) ────────────────────────────── -->
      <section class="latest-products-container" v-if="latestProducts.length">
        <ProductCarousel :products="latestProducts" />
      </section>
    </template>

    <template v-else>
      <p class="loading">Carregant producte...</p>
    </template>

    <!-- ─── Lightbox per a imatge ───────────────────────────────────── -->
    <div v-if="showImageModal" class="image-modal" @click.self="closeImage">
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

const route       = useRoute()
const router      = useRouter()
const cartStore   = useCartStore()

// Producte i estat
const product         = ref<Product|null>(null)
const allProducts     = ref<Product[]>([])
const relatedProducts = ref<Product[]>([])
const quantity        = ref(1)
const addSuccessMessage = ref('')

// Botiga + mapa
const storeData    = ref<Store|null>(null)
const miniMapRef   = ref<HTMLDivElement|null>(null)
const diesSetmana  = ["Dilluns","Dimarts","Dimecres","Dijous","Divendres","Dissabte","Diumenge"]
const horarisPerDia = computed<Record<string,string>>(() => {
  if (!storeData.value) return {}
  const out: Record<string,string> = {}
  for (const d of diesSetmana) {
    out[d] = storeData.value.horaris
      .filter(h => h.dia.toLowerCase()===d.toLowerCase())
      .map(h=>`${h.obertura.slice(0,5)}–${h.tancament.slice(0,5)}`)
      .join(', ')
  }
  return out
})

// Carrega producte + botiga + mapa + related + carousel
async function loadProduct() {
  addSuccessMessage.value = ''
  product.value     = await fetchProductById(route.params.id as string)
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

function initMiniMap() {
  if (!storeData.value || !miniMapRef.value) return
  const { latitude: lat, longitude: lng, nom } = storeData.value
  const map = L.map(miniMapRef.value, {
    zoomControl: false,
    attributionControl: false
  }).setView([lat, lng], 15)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map)

  const marker = L.marker([lat, lng]).addTo(map)
  marker.bindPopup(`
    <strong>${nom}</strong><br>
    <button onclick="window.open('https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}','_blank')" 
      class="btn-maps"
    >📍 Com arribar</button>
  `).openPopup()

  setTimeout(() => map.invalidateSize(), 300)
}

function updateRelatedProducts() {
  if (!product.value) return
  const id   = product.value.id
  const same = allProducts.value.filter(
    p => p.id !== id && p.botiga?.id === product.value!.botiga?.id
  )
  const others = allProducts.value
    .filter(p => p.id !== id && p.botiga?.id !== product.value!.botiga?.id)
    .sort(() => Math.random() - 0.5)
    .slice(0, 4 - same.length)
  relatedProducts.value = [...same, ...others]
    .sort(() => Math.random() - 0.5)
    .slice(0, 4)
}

const latestProducts = computed(() =>
  [...allProducts.value]
    .sort((a,b)=>b.id - a.id)
    .slice(0,25)
    .map(p=>({
      id: p.id,
      nom: (p as any).nom ?? (p as any).name,
      preu: (p as any).preu ?? (p as any).price,
      imatge: (p as any).imatge ?? (p as any).image ?? null
    }))
)

const cartItem = computed(() =>
  cartStore.items.find(i=>i.product.id===product.value?.id)
)
const inCart = computed(()=>!!cartItem.value)
watch(cartItem, item=> quantity.value = item?.quantity ?? 1)

const showImageModal = ref(false), modalImageSrc = ref('')
function openImage(src:string){ modalImageSrc.value = src; showImageModal.value = true }
function closeImage(){ showImageModal.value = false; modalImageSrc.value = '' }

const formattedPrice = computed(()=>product.value?`${(+product.value.preu).toFixed(2)} €`:'—')

// Imatge robusta: mostra /storage/uploads/imatge.jpg si cal
function getImageSrc(path:string|null){
  const BACKEND_URL = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8000'
  if(!path) return '/img/no-imatge.jpg'
  if (path.startsWith('http://') || path.startsWith('https://')) return path
  if (path.startsWith('uploads/')) return BACKEND_URL + '/storage/' + path
  if (path.startsWith('/uploads/')) return BACKEND_URL + '/storage/' + path.substring(1)
  return BACKEND_URL + path
}
function goToProduct(id:number){ router.push(`/producte/${id}`) }
function goToCart(){ router.push('/cart') }

async function decreaseQuantity(){
  if(!inCart.value){ if(quantity.value>1) quantity.value-- }
  else {
    if(quantity.value>1){ quantity.value--; await updateCartQuantity() }
    else await removeCartItem()
  }
}
async function increaseQuantity(){
  if(product.value && quantity.value < (product.value.stock||Infinity)){
    quantity.value++
    if(inCart.value) await updateCartQuantity()
  }
}
async function updateCartQuantity(){
  if(!cartItem.value) return
  const tok = localStorage.getItem('userToken')!
  await axios.put(`/cart/${cartItem.value.id}`, { quantity: quantity.value }, {
    headers: { Authorization: `Bearer ${tok}` }
  })
  await cartStore.fetchCart()
  addSuccessMessage.value = 'Quantitat actualitzada!'
  setTimeout(() => addSuccessMessage.value = '', 2000)
}
async function handleAddItem(){
  if(!product.value) return
  await cartStore.addItem(product.value.id, quantity.value)
  await cartStore.fetchCart()
  addSuccessMessage.value = 'Producte afegit al carro!'
  setTimeout(() => addSuccessMessage.value = '', 2000)
}
async function removeCartItem(){
  if(!cartItem.value) return
  await cartStore.removeItem(cartItem.value.id)
  await cartStore.fetchCart()
  addSuccessMessage.value = 'Producte eliminat del carro'
  setTimeout(() => addSuccessMessage.value = '', 2000)
}

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

/* ─── Info Botiga Millorada ────────────────────────────────────── */
.shop-info-card {
  background: #fafbfc;
  box-shadow: 0 2px 24px rgba(0,0,0,0.06);
  border-radius: 18px;
  padding: 34px 24px 24px 24px;
  max-width: 1100px;
  width: 100%;
  margin: 0 auto 32px auto;
}
.shop-info-card h2 {
  font-size: 2rem;
  font-weight: 700;
  color: #212c3a;
  margin-bottom: 2rem;
  text-align: center;
}
.shop-info-grid {
  display: flex;
  flex-direction: row;
  gap: 40px;
  align-items: flex-start;
  justify-content: center;
  width: 100%;
}

.map-col, .hours-col {
  width: 100%;
  max-width: 420px;
}

.shop-mini-map {
  width: 100%;
  aspect-ratio: 4/3;
  min-height: 220px;
  border-radius: 10px;
  overflow: hidden;
  margin-bottom: 18px;
}

.shop-hours {
  width: 100%;
  border-collapse: collapse;
  background: white;
  border-radius: 8px;
  overflow: hidden;
  margin: 0 auto;
}
.shop-hours th, .shop-hours td {
  border: 1px solid #d0d0d0;
  padding: 12px 18px;
  text-align: left;
  font-size: 1.13rem;
}
.shop-hours th {
  background: #f4f4f4;
  font-weight: bold;
}

.more-info-btn {
  margin-top: 8px;
  display: block;
  width: 100%;
  text-align: center;
  background: #42b983;
  color: white;
  border: none;
  padding: 13px 0;
  font-size: 1.18rem;
  border-radius: 7px;
  cursor: pointer;
  transition: background-color 0.3s;
  text-decoration: none;
  font-weight: 600;
}
.more-info-btn:hover {
  background: #368c6e;
}
.btn-maps {
  background-color: #42b983;
  color: white;
  border: none;
  padding: 8px 14px;
  font-size: 18px;
  border-radius: 6px;
  cursor: pointer;
  margin-top: 8px;
  transition: background-color 0.3s;
}
.btn-maps:hover {
  background-color: #368c6e;
}
/* Responsive per mòbil */
@media (max-width: 900px) {
  .shop-info-card {
    padding: 14px 2px;
    max-width: 98vw;
  }
  .shop-info-grid {
    flex-direction: column;
    gap: 0;
  }
  .map-col, .hours-col {
    max-width: 98vw;
    margin-bottom: 16px;
  }
  .shop-mini-map {
    min-height: 140px;
  }
  .shop-hours th, .shop-hours td {
    padding: 8px 6px;
  }
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
}
</style>