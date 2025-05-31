<template>
  <div class="cart-container">
    <!-- Header -->
    <h1>El teu Carro ({{ cartStore.itemCount }} ítems)</h1>

    <!-- Accions globals desktop -->
    <div v-if="hasItems" class="global-actions desktop-only">
      <button class="btn clear-all-btn" @click="openClearAllModal">
        Buidar tot el carro
      </button>
      <button class="btn checkout-all-btn" @click="checkoutTotal">
        Finalitzar Comanda
      </button>
    </div>

    <!-- Carro buit -->
    <div v-if="!hasItems" class="empty-state">
      <p>El teu carro està buit.</p>
    </div>

    <!-- Desktop: taula agrupada per botiga -->
    <div v-else class="desktop-view">
      <div
        v-for="(items, shopId) in groupedCartItems"
        :key="shopId"
        class="shop-group"
      >
        <div class="shop-header">
          <span class="shop-name">
            Botiga:
            <template v-if="shopId !== 'sense_botiga'">
              <router-link :to="`/info-botiga/${shopId}`">
                <strong>{{ getStoreName(shopId) }}</strong>
              </router-link>
            </template>
            <template v-else><strong>Sense Botiga</strong></template>
          </span>
          <span class="shop-total">
            Total per botiga:
            <strong>{{ formatPrice(calcShopTotal(items)) }}</strong>
          </span>
          <button class="btn clear-group-btn" @click="openClearModal(shopId)">
            Buidar carro de {{ getStoreName(shopId) }}
          </button>
        </div>

        <table>
          <thead>
            <tr>
              <th><input type="checkbox" :checked="allSelected(shopId)" @change="toggleSelectGroup(shopId, items)" /></th>
              <th>Producte</th>
              <th>Preu Unitar</th>
              <th>Quantitat</th>
              <th class="reservation-col">Reserva (10%)</th>
              <th>Total</th>
              <th>Accions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items" :key="item.id">
              <td><input type="checkbox" v-model="item.selected" @change="updateCartItem(item)" /></td>
              <td class="product-cell" @click="goToProduct(item.product.id)">
                <img :src="getImageSrc(item.product.imatge)" alt="producte" class="product-image" />
                <span>{{ item.product.nom }}</span>
              </td>
              <td>{{ formatPrice(item.reserved_price) }}</td>
              <td>
                <div class="quantity-control">
                  <button class="quantity-btn" :disabled="item.quantity<=1" @click="decreaseQuantity(item)">−</button>
                  <input type="number" v-model.number="item.quantity" :min="1" :max="item.product.stock" @change="onQuantityChange(item)" />
                  <button class="quantity-btn" :disabled="item.quantity>=item.product.stock" @click="increaseQuantity(item)">+</button>
                </div>
              </td>
              <td class="reservation-col">{{ formatPrice(item.quantity * Number(item.product.preu) * 0.1) }}</td>
              <td>{{ formatPrice(item.quantity * Number(item.product.preu)) }}</td>
              <td>
                <button class="btn trash-btn" @click="confirmSingleDelete(item.id)">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Mobile (i desktop si vols cards): cards agrupades per botiga -->
    <div class="cards-view mobile-only">
      <div v-for="(items, shopId) in groupedCartItems" :key="shopId" class="shop-cards">
        <div class="shop-header cards-header">
          <h2>
            {{ getStoreName(shopId) }} —
            <small>{{ formatPrice(calcShopTotal(items)) }}</small>
          </h2>
          <button class="btn clear-group-btn" @click="openClearModal(shopId)">
            Buidar carro de {{ getStoreName(shopId) }}
          </button>
        </div>
        <div class="cards-grid">
          <div v-for="item in items" :key="item.id" class="card">
            <div class="card-header">
              <input type="checkbox" v-model="item.selected" @change="updateCartItem(item)" />
              <button class="btn trash-btn" @click="confirmSingleDelete(item.id)">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
            <img :src="getImageSrc(item.product.imatge)" alt="producte" class="card-image" />
            <h3 @click="goToProduct(item.product.id)">{{ item.product.nom }}</h3>
            <p>Unitat: {{ formatPrice(item.reserved_price) }}</p>
            <div class="quantity-control">
              <button class="quantity-btn" :disabled="item.quantity<=1" @click="decreaseQuantity(item)">−</button>
              <input type="number" v-model.number="item.quantity" :min="1" :max="item.product.stock" @change="onQuantityChange(item)" />
              <button class="quantity-btn" :disabled="item.quantity>=item.product.stock" @click="increaseQuantity(item)">+</button>
            </div>
            <p>Reserva: {{ formatPrice(item.quantity * Number(item.product.preu) * 0.1) }}</p>
            <p>Total: {{ formatPrice(item.quantity * Number(item.product.preu)) }}</p>
          </div>
        </div>
      </div>
    </div>
    <!-- Accions globals desktop -->
    <div v-if="hasItems" class="global-actions desktop-only">
      <button class="btn clear-all-btn" @click="openClearAllModal">
        Buidar tot el carro
      </button>
      <button class="btn checkout-all-btn" @click="checkoutTotal">
        Finalitzar Comanda
      </button>
    </div>
    <!-- Accions globals móvil (fixes a baix) -->
    <div v-if="hasItems" class="global-actions mobile-only mobile-fixed">
      <button class="btn clear-all-btn" @click="openClearAllModal">
        Buidar tot el carro
      </button>
      <button class="btn checkout-all-btn" @click="checkoutTotal">
        Finalitzar Comanda
      </button>
    </div>

    <!-- Modals ... (igual que abans) -->
    <!-- Clear one shop -->
    <div class="modal-overlay" v-if="showClearModal" @click="closeClearModal">
      <div class="modal" @click.stop>
        <h2>Confirmar Buidatge</h2>
        <p>Estàs segur que vols buidar tots els ítems de la botiga <strong>{{ getStoreName(modalShopId) }}</strong>?</p>
        <ul>
          <li v-for="item in (groupedCartItems[modalShopId] || [])" :key="item.id">
            {{ item.product.nom }} (x{{ item.quantity }})
          </li>
        </ul>
        <div class="modal-actions">
          <button class="btn clear-group-btn" @click="clearCartGroup(modalShopId)">Confirmar</button>
          <button class="btn" @click="closeClearModal">Cancel·lar</button>
        </div>
      </div>
    </div>
    <!-- Clear all -->
    <div class="modal-overlay" v-if="showClearAllModal" @click="closeClearAllModal">
      <div class="modal" @click.stop>
        <h2>Confirmar Buidatge Total</h2>
        <p>Estàs segur que vols buidar tots els ítems del carro?</p>
        <ul>
          <li v-for="item in cartStore.items" :key="item.id">
            {{ item.product.nom }} (x{{ item.quantity }})
          </li>
        </ul>
        <div class="modal-actions">
          <button class="btn clear-all-btn" @click="clearAllCart">Confirmar</button>
          <button class="btn" @click="closeClearAllModal">Cancel·lar</button>
        </div>
      </div>
    </div>
    <!-- Delete single -->
    <div class="modal-overlay" v-if="showSingleDeleteModal" @click="closeSingleDeleteModal">
      <div class="modal" @click.stop>
        <h2>Confirmar Eliminació</h2>
        <p>Estàs segur que vols eliminar l'ítem <strong>{{ singleDeleteItemName }}</strong>?</p>
        <div class="modal-actions">
          <button class="btn remove-btn" @click="deleteSingleItem">Confirmar</button>
          <button class="btn" @click="closeSingleDeleteModal">Cancel·lar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useCartStore } from '../stores/cartStore'
import { useToast } from 'vue-toastification'

const router    = useRouter()
const toast     = useToast()
const cartStore = useCartStore()

// 👇 FUNCIO PER MOSTRAR IMATGES
const BACKEND_URL = import.meta.env.VITE_BACKEND_URL
const DEFAULT_IMAGE = '/img/no-imatge.jpg'

function getImageSrc(path: string | null) {
  if (!path) return DEFAULT_IMAGE
  // Si ja és una URL absoluta (http, https), retorna-la directament
  if (path.startsWith('http://') || path.startsWith('https://')) return path
  // Si és una ruta relativa però NO comença per '/', afegeix '/'
  const cleanPath = path.startsWith('/') ? path : `/${path}`
  return BACKEND_URL + cleanPath
}

// Modals & state
const showClearModal        = ref(false)
const showClearAllModal     = ref(false)
const showSingleDeleteModal = ref(false)
const modalShopId           = ref<string>('')
const singleDeleteItemName  = ref<string>('')
let itemToDelete: number | null = null

onMounted(() => {
  cartStore.fetchCart().then(() => {
    // Mostra totes les imatges dels productes carregats al carro
    cartStore.items.forEach(item => {
      console.log('imatge:', item.product.imatge)
    })
  })
})

// Computed
const hasItems = computed(() => cartStore.items.length > 0)
const groupedCartItems = computed<Record<string, any[]>>(() =>
  cartStore.items.reduce((groups, item) => {
    const sid = item.product.botiga?.id?.toString() || 'sense_botiga'
    ;(groups[sid] ||= []).push(item)
    return groups
  }, {} as Record<string, any[]>)
)

// Helpers
function getStoreName(sid: string) {
  if (sid === 'sense_botiga') return 'Sense Botiga'
  return groupedCartItems.value[sid][0].product.botiga.nom
}
function formatPrice(val: number | string) {
  const n = typeof val === 'number' ? val : parseFloat(val)
  return isNaN(n) ? '—' : n.toFixed(2) + ' €'
}
function calcShopTotal(items: any[]) {
  return items.filter(i => i.selected).reduce((s, i) => s + i.quantity * Number(i.product.preu), 0)
}
function allSelected(sid: string) {
  return groupedCartItems.value[sid].every(i => i.selected)
}

// Server & handlers
async function updateCartItem(item: any) {
  await axios.put(`/cart/${item.id}`, {
    quantity: item.quantity,
    selected: item.selected
  })
  await cartStore.fetchCart()
}

async function toggleSelectGroup(sid: string, items: any[]) {
  const all = allSelected(sid)
  for (const i of items) {
    i.selected = !all
    await updateCartItem(i)
  }
}

function onQuantityChange(item: any) {
  if (item.quantity > item.product.stock) {
    toast.error('No hi ha tant stock!')
    item.quantity = item.product.stock
  }
  updateCartItem(item)
}
function increaseQuantity(item: any) {
  if (item.quantity < item.product.stock) {
    item.quantity++
    updateCartItem(item)
  } else toast.error('Has arribat al màxim!')
}
function decreaseQuantity(item: any) {
  if (item.quantity > 1) {
    item.quantity--
    updateCartItem(item)
  }
}

function openClearModal(sid: string) {
  modalShopId.value = sid
  showClearModal.value = true
}
function closeClearModal() {
  showClearModal.value = false
  modalShopId.value = ''
}
async function clearCartGroup(sid: string) {
  for (const i of groupedCartItems.value[sid]) {
    await axios.delete(`/cart/${i.id}`)
  }
  await cartStore.fetchCart()
  closeClearModal()
}

function openClearAllModal() {
  showClearAllModal.value = true
}
function closeClearAllModal() {
  showClearAllModal.value = false
}
async function clearAllCart() {
  for (const i of cartStore.items) {
    await axios.delete(`/cart/${i.id}`)
  }
  await cartStore.fetchCart()
  closeClearAllModal()
}

function confirmSingleDelete(id: number) {
  itemToDelete = id
  singleDeleteItemName.value = cartStore.items.find(i => i.id === id)?.product.nom || ''
  showSingleDeleteModal.value = true
}
function closeSingleDeleteModal() {
  showSingleDeleteModal.value = false
  itemToDelete = null
}
async function deleteSingleItem() {
  if (!itemToDelete) return
  await axios.delete(`/cart/${itemToDelete}`)
  await cartStore.fetchCart()
  closeSingleDeleteModal()
}

function checkoutTotal() {
  router.push('/checkout?all=true')
}
function goToProduct(id: number) {
  router.push(`/producte/${id}`)
}
</script>

<style scoped>
.cart-container { max-width:1000px; margin:0 auto; padding:20px; background:#fff; }
h1 { text-align:center; margin-bottom:20px; }
.empty-state { text-align:center; font-size:1.1rem; }

/* Globals */
.global-actions { display:flex; justify-content:flex-end; gap:10px; margin:20px 0; }
.btn { color:#fff!important; }
.clear-all-btn, .clear-group-btn, .trash-btn, .remove-btn { background:#d9534f!important; }
.clear-all-btn:hover, .clear-group-btn:hover, .trash-btn:hover, .remove-btn:hover { background:#c9302c!important; }
.checkout-all-btn { background:#28a745!important; }
.checkout-all-btn:hover { background:#218838!important; }

/* Desktop-only / Mobile-only */
.desktop-only { display:flex; }
.mobile-only { display:none; }

/* Desktop tables */
.desktop-view table { width:100%; border-collapse:collapse; margin-bottom:20px; }
.desktop-view th, .desktop-view td { border:1px solid #ddd; padding:12px; text-align:center; }
.desktop-view th { background:#42b983; color:#fff; }
.product-cell { display:flex; align-items:center; gap:10px; cursor:pointer; }
.product-image { width:50px; height:50px; object-fit:cover; border-radius:4px; }
.quantity-control { display:flex; align-items:center; gap:8px; }
.quantity-btn { background:#42b983; color:#fff; border:none; padding:6px 10px; border-radius:4px; cursor:pointer; }
.quantity-btn:disabled { opacity:0.5; cursor:not-allowed; }
.quantity-btn:hover:not(:disabled) { background:#368c6e; }

/* Cards view */
.cards-view { display:none; }
.cards-header { display:flex; justify-content:space-between; align-items:center; }
.cards-grid { display:grid; gap:16px; }
.card { background:#fafafa; border:1px solid #ddd; border-radius:8px; padding:12px; }
.card-header { display:flex; justify-content:space-between; }
.card-image { width:100%; height:120px; object-fit:cover; border-radius:4px; margin:8px 0; }
.card h3 { cursor:pointer; margin:8px 0; }
.card p { margin:4px 0; }


/* 1. Desktop: empènyer el botó “clear-group-btn” a la dreta */
.shop-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.shop-header .shop-name,
.shop-header .shop-total {
  margin-right: 1rem;
}
/* aquest utilitari mou el clear-group-btn al final */
.shop-header .clear-group-btn {
  margin-left: auto;
}
/* 2. Mobile: contenidor només del fons darrere els botons */
.mobile-fixed {
  position: fixed;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  background: #fff;
  padding: 8px 16px;
  border: 1px solid #ddd;
  border-radius: 8px ;
  display: flex;
  align-items: center;
  gap: 10px;
  z-index: 100;
}
/* perquè no ocupin tot l’amplada */
.mobile-fixed .btn {
  flex: none;
}

/* Responsive */
@media (max-width: 768px) {
  .desktop-view { display: none; }
  .mobile-only { display: block; }
  .cards-grid { grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); }
}
@media (min-width: 769px) {
  .mobile-only { display: none; }
}
/* Reserva-col ocult en mobile */
@media (max-width: 560px) {
  .reservation-col { display: none; }
}
</style>