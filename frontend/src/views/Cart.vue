<template>
  <div class="cart-container">
    <h1>El teu Carro ({{ cartStore.itemCount }} ítems)</h1>

    <!-- Carro buit -->
    <div v-if="!hasItems" class="empty-state">
      <p>El teu carro està buit.</p>
    </div>

    <!-- Bulk actions -->
    <div v-if="hasItems && selectedItems.length > 0" class="bulk-actions">
      <span>{{ selectedItems.length }} seleccionats</span>
      <button class="btn bulk-delete-btn" @click="bulkDelete">
        <i class="fa-solid fa-trash"></i> Eliminar seleccionats
      </button>
    </div>
    <!-- Nous botons seleccionar/deseleccionar -->
    <div v-if="hasItems" class="bulk-selection-actions">
      <button class="btn select-all-btn" @click="selectAll">Seleccionar tot</button>
      <button class="btn deselect-all-btn" @click="deselectAll">Deseleccionar tot</button>
    </div>

    <!-- Desktop: Taula agrupada per botiga -->
    <div v-if="hasItems" class="desktop-view">
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
              <th>
                <input
                  type="checkbox"
                  :checked="allSelected(shopId)"
                  @change="toggleSelectGroup(shopId, items)"
                />
              </th>
              <th>Producte</th>
              <th>Preu Unitar</th>
              <th>Quantitat</th>
              <th class="reservation-col">Reserva (10%)</th>
              <th>Total</th>
              <th>Accions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, idx) in items" :key="item.id" :class="{ 'first-item': idx === 0 }">
              <td>
                <input type="checkbox" v-model="item.selected" @change="updateCartItem(item)" />
              </td>
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

    <!-- Mobile: Cards agrupades per botiga -->
    <div class="cards-view mobile-only" v-if="hasItems">
      <div v-for="(items, shopId) in groupedCartItems" :key="shopId" class="shop-cards">
        <div class="shop-header cards-header">
          <h2>
            {{ getStoreName(shopId) }}
            <small>{{ formatPrice(calcShopTotal(items)) }}</small>
          </h2>
          <button class="btn clear-group-btn" @click="openClearModal(shopId)">
            Buidar carro de {{ getStoreName(shopId) }}
          </button>
        </div>
        <div class="cards-grid">
          <div v-for="(item, idx) in items" :key="item.id" class="card" :class="{ 'first-card': idx === 0 }">
            <div class="card-header">
              <input type="checkbox" v-model="item.selected" @change="updateCartItem(item)" />
              <button class="btn trash-btn" @click="confirmSingleDelete(item.id)">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
            <img :src="getImageSrc(item.product.imatge)" alt="producte" class="card-image" />
            <div class="card-content">
              <h3 @click="goToProduct(item.product.id)">{{ item.product.nom }}</h3>
              <div class="card-details">
                <span class="unit-price">Unitat: <strong>{{ formatPrice(item.reserved_price) }}</strong></span>
                <span class="reservation">Reserva: <strong>{{ formatPrice(item.quantity * Number(item.product.preu) * 0.1) }}</strong></span>
                <span class="total">Total: <strong>{{ formatPrice(item.quantity * Number(item.product.preu)) }}</strong></span>
              </div>
              <div class="quantity-control">
                <button class="quantity-btn" :disabled="item.quantity<=1" @click="decreaseQuantity(item)">−</button>
                <input type="number" v-model.number="item.quantity" :min="1" :max="item.product.stock" @change="onQuantityChange(item)" />
                <button class="quantity-btn" :disabled="item.quantity>=item.product.stock" @click="increaseQuantity(item)">+</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Barra d'accions a sota (sempre visible), sense fons ni barra, només botons -->
    <div class="actions-bottom-bar" ref="actionsRefMobile" v-if="hasItems">
      <button class="btn clear-all-btn" @click="openClearAllModal">
        Buidar carro
      </button>
      <button class="btn checkout-all-btn" @click="checkoutTotal">
        Finalitzar Comanda
      </button>
    </div>
    <!-- Flotant només quan la barra no es veu -->
    <transition name="fade">
      <div v-if="hasItems && !actionsAreVisible" class="actions-bottom-bar actions-floating">
        <button class="btn clear-all-btn" @click="openClearAllModal">
          Buidar carro
        </button>
        <button class="btn checkout-all-btn" @click="checkoutTotal">
          Finalitzar Comanda
        </button>
      </div>
    </transition>

    <!-- Modals igual que abans -->
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
import { ref, onMounted, computed, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useCartStore } from '../stores/cartStore'
import { useToast } from 'vue-toastification'

const router    = useRouter()
const toast     = useToast()
const cartStore = useCartStore()

const BACKEND_URL = import.meta.env.VITE_BACKEND_URL
const DEFAULT_IMAGE = '/img/no-imatge.jpg'

function getImageSrc(path: string | null) {
  if (!path) return DEFAULT_IMAGE
  if (path.startsWith('http://') || path.startsWith('https://')) return path
  const cleanPath = path.startsWith('/') ? path : `/${path}`
  return BACKEND_URL + cleanPath
}

const showClearModal        = ref(false)
const showClearAllModal     = ref(false)
const showSingleDeleteModal = ref(false)
const modalShopId           = ref<string>('')
const singleDeleteItemName  = ref<string>('')
let itemToDelete: number | null = null

const selectedItems = computed(() => cartStore.items.filter(i => i.selected))
const hasItems = computed(() => cartStore.items.length > 0)
const groupedCartItems = computed<Record<string, any[]>>(() =>
  cartStore.items.reduce((groups, item) => {
    const sid = item.product.botiga?.id?.toString() || 'sense_botiga'
    ;(groups[sid] ||= []).push(item)
    return groups
  }, {} as Record<string, any[]>)
)

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
function selectAll() {
  cartStore.items.forEach(i => { i.selected = true })
  cartStore.items.forEach(updateCartItem)
}
function deselectAll() {
  cartStore.items.forEach(i => { i.selected = false })
  cartStore.items.forEach(updateCartItem)
}
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
async function bulkDelete() {
  if (selectedItems.value.length === 0) return
  if (!confirm(`Eliminar ${selectedItems.value.length} productes seleccionats del carro?`)) return
  for (const i of selectedItems.value) {
    await axios.delete(`/cart/${i.id}`)
  }
  await cartStore.fetchCart()
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
const actionsRefMobile = ref<HTMLElement | null>(null)
const actionsAreVisible = ref(true)
let observer: IntersectionObserver | null = null
function setupIntersectionObserver() {
  if (observer) observer.disconnect()
  observer = null
  if (!actionsRefMobile.value) {
    actionsAreVisible.value = false
    return
  }
  observer = new window.IntersectionObserver(
    entries => {
      actionsAreVisible.value = entries[0].isIntersecting
    },
    { root: null, threshold: 0.08 }
  )
  observer.observe(actionsRefMobile.value)
}
onMounted(() => {
  cartStore.fetchCart()
  nextTick(() => setupIntersectionObserver())
})
watch(actionsRefMobile, () => {
  nextTick(() => setupIntersectionObserver())
})
</script>

<style scoped>
/* --- Barra accions: només botons, sense fons ni barra ni shadow --- */
.actions-bottom-bar {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0;
  margin: 24px 10px 0 10px;
  padding: 0;
  background: none !important;
  border: none !important;
  box-shadow: none !important;
  position: static;
}
.actions-bottom-bar .btn {
  flex: 1 1 50%;
  margin: 0;
  border-radius: 19px;
  font-size: 1.12em;
  padding: 17px 0 15px 0;
  min-width: 0;
}
.clear-all-btn,
.clear-group-btn,
.trash-btn,
.remove-btn {
  background: #d9534f !important;
  color: #fff !important;
  border-radius: 19px;
}
.clear-all-btn:hover,
.clear-group-btn:hover,
.trash-btn:hover,
.remove-btn:hover {
  background: #c9302c !important;
}

.checkout-all-btn {
  margin-left: 8px;
  background: #28a745 !important;
  color: #fff !important;
  border-radius: 19px;
}
.checkout-all-btn:hover {
  background: #218838 !important;
}
/* Flotant */
.actions-floating {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  margin-left: auto;
  margin-right: auto;
  max-width: 700px;
  margin-bottom: 15px;
  z-index: 200;
  background: none !important;
  box-shadow: none !important;
  border: none !important;
  padding-left: 10px;
  padding-right: 10px;
  animation: upIn 0.22s;
}
@keyframes upIn {
  from { opacity: 0; transform: translateY(80px);}
  to   { opacity: 1; transform: translateY(0);}
}
@media (max-width: 560px) {
  .actions-bottom-bar,
  .actions-floating {
    max-width: 100vw;
    margin-left: 6px;
    margin-right: 6px;
  }
}

/* --- La resta d'estils originals --- */
.cart-container { max-width:1000px; margin:0 auto; padding:20px; background:#fff; }
h1 { text-align:center; margin-bottom:20px; }
.empty-state { text-align:center; font-size:1.1rem; margin-top: 2rem; color: #888; }

/* Bulk actions */
.bulk-actions {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 15px;
  margin-bottom: 18px;
  background: #f4f8f6;
  border-radius: 8px;
  padding: 12px 16px;
  font-weight: 500;
  box-shadow: 0 2px 8px #42b98311;
}
.bulk-delete-btn {
  background: #d9534f !important;
  color: #fff !important;
  border-radius: 6px;
  padding: 7px 20px;
  font-weight: 700;
  font-size: 1rem;
  transition: background 0.18s;
}
.bulk-delete-btn:hover { background: #c9302c !important; }

.bulk-selection-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-bottom: 18px;
}
.select-all-btn, .deselect-all-btn {
  background: #42b983 !important;
  color: #fff !important;
  border-radius: 6px;
  padding: 7px 20px;
  font-weight: 700;
  font-size: 1rem;
  transition: background 0.18s;
}
.select-all-btn:hover, .deselect-all-btn:hover { background: #368c6e !important; }

.desktop-view table { width:100%; border-collapse:collapse; margin-bottom:20px; }
.desktop-view th, .desktop-view td { border:1px solid #ddd; padding:12px; text-align:center; }
.desktop-view th { background:#42b983; color:#fff; }
.product-cell { display:flex; align-items:center; gap:10px; cursor:pointer; }
.product-image { width:52px; height:52px; object-fit:cover; border-radius:4px; }
.quantity-control { display:flex; align-items:center; gap:8px; }
.quantity-btn { background:#42b983; color:#fff; border:none; padding:7px 14px; border-radius:6px; cursor:pointer; font-size:1.21em; font-weight:700; }
.quantity-btn:disabled { opacity:0.5; cursor:not-allowed; }
.quantity-btn:hover:not(:disabled) { background:#368c6e; }

.shop-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #f6fffa;
  border-radius: 11px 11px 0 0;
  padding: 14px 18px;
  margin-bottom: 0;
  font-size: 1.09em;
  border-bottom: 1.5px solid #c0f3e1;
}
.shop-header .shop-name,
.shop-header .shop-total { margin-right: 1rem; }
.shop-header .clear-group-btn { margin-left: auto; }

.cards-view { display:none; }
.cards-header { display:flex; justify-content:space-between; align-items:center; }
.cards-grid { display:grid; gap:24px; }
.card {
  background: linear-gradient(120deg, #f6fffa 83%, #e8fefb 100%);
  border:1.5px solid #d9f5ee;
  border-radius:20px;
  padding:26px 16px 20px;
  box-shadow:0 8px 36px #42b98318;
  margin-bottom: 18px;
  transition: box-shadow 0.15s, border 0.12s;
  display: flex;
  flex-direction: column;
  align-items: stretch;
  position: relative;
}
.card:hover { box-shadow: 0 10px 36px #42b98322; border-color: #a7e9d8; }
.card-header { display:flex; justify-content:space-between; align-items:center; margin-bottom: 6px;}
.card-header input[type=checkbox] { width: 22px; height: 22px;}
.card .trash-btn { padding: 6px 13px; font-size: 1.18em; }
.card-image {
  width:100%; height:138px; object-fit:cover; border-radius:13px; margin:13px 0 14px;
  box-shadow: 0 2px 12px #42b98309;
  background: #fff;
}
.card-content { display: flex; flex-direction: column; gap: 11px; }
.card h3 {
  cursor:pointer; margin:0 0 5px 0; font-size:1.15em; font-weight: 700; color: #274549;
  transition: color 0.18s;
  margin-bottom: 3px;
}
.card h3:hover { color: #42b983; }
.card-details {
  display: flex; flex-direction: column; gap: 2px;
  font-size: 1.04em; color: #222;
  margin-bottom: 3px;
  font-weight: 500;
}
.card-details span { display:block; }
.unit-price strong, .reservation strong, .total strong {
  color: #274549; font-weight: 700;
}
.quantity-control input[type=number] {
  width: 42px; text-align: center; border-radius: 5px; border: 1.5px solid #b1ede8; padding: 4px 0;
  background: #fcfffc;
  font-size: 1.05em;
}
.quantity-control { margin-top: 5px;}

.fade-enter-active, .fade-leave-active { transition: opacity .28s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.desktop-view tr.first-item td,
.desktop-view tr.first-item th {
  border-top: none !important;
  border-radius: 0 !important;
}
.desktop-view tr.first-item td:first-child,
.desktop-view tr.first-item th:first-child {
  border-top-left-radius: 0 !important;
}
.desktop-view tr.first-item td:last-child,
.desktop-view tr.first-item th:last-child {
  border-top-right-radius: 0 !important;
}

.card.first-card {
  border-top-left-radius: 0 !important;
  border-top-right-radius: 0 !important;
  border-top: none !important;
}

@media (max-width: 900px) {
  .cart-container { padding: 10px 2vw; }
  .shop-header { padding: 10px 7px; font-size: 1em; }
}
@media (max-width: 768px) {
  .desktop-view { display: none; }
  .mobile-only { display: block; }
  .cards-view { display: block; margin-bottom: 100px; }
  .cards-grid { grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
  .product-image { width:40px; height:40px;}
  .mobile-bottom-space { display: block; }
}
@media (min-width: 769px) {
  .mobile-only { display: none; }
}
@media (max-width: 560px) {
  .reservation-col { display: none; }
  .shop-header, .cards-header { flex-direction: column; gap: 8px; align-items: flex-start; }
  .actions-bottom-bar,
  .actions-floating {
    max-width: 100vw;
    margin-left: 6px;
    margin-right: 6px;
  }
  .cards-grid { grid-template-columns: 1fr; }
}
</style>