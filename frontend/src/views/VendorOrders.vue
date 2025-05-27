<template>
  <div class="vendor-orders">
    <h1>Informació de vendes</h1>

    <!-- Control superior -->
    <div class="header-controls">
      <button class="btn toggle-filters-btn" @click="toggleFilters">
        {{ showFilters ? 'Oculta Filtres' : 'Mostra Filtres' }}
      </button>
      <input
        type="text"
        v-model="searchOrderNumber"
        placeholder="Cerca per Codi de Comanda..."
        class="search-input"
      />
      <button class="btn scan-btn" @click="openScanner">
        Escaneja Codi
      </button>
    </div>

    <!-- Modal QR -->
    <div v-if="showScanner" class="scanner-modal">
      <div class="scanner-wrapper">
        <qrcode-stream
          v-if="scannerReady"
          @decode="onDecode"
          @init="onInit"
          class="qr-stream"
        />
        <div v-else class="qr-loader">Activant càmera...</div>
        <div class="overlay">
          <div class="corner top-left"></div>
          <div class="corner top-right"></div>
          <div class="corner bottom-left"></div>
          <div class="corner bottom-right"></div>
        </div>
      </div>
      <button class="btn close-btn" @click="closeScanner">Tanca</button>
    </div>

    <!-- Panell de filtres avançats -->
    <div v-if="showFilters" class="advanced-filters">
      <div class="filter-field">
        <label for="selectedStore">Filtra per Botiga:</label>
        <select id="selectedStore" v-model="selectedStoreId">
          <option value="">Totes les botigues</option>
          <option v-for="store in stores" :key="store.id" :value="store.id">{{ store.nom }}</option>
        </select>
      </div>
      <div class="filter-field">
        <label for="filterStatus">Filtra per Estat:</label>
        <select id="filterStatus" v-model="filterStatus">
          <option value="">Tots els estats</option>
          <option value="pending">Pendent</option>
          <option value="reserved">Reservada</option>
          <option value="completed">Completada</option>
          <option value="cancelled">Cancel·lada</option>
        </select>
      </div>
      <div class="filter-field">
        <label>Data Inici:</label>
        <input type="date" v-model="startDate" />
      </div>
      <div class="filter-field">
        <label>Data Fi:</label>
        <input type="date" v-model="endDate" />
      </div>
      <div class="filter-field">
        <button class="btn reset-btn" @click="resetFilters">Reinicia Filtres</button>
      </div>
    </div>

    <!-- Spinner -->
    <div v-if="loading" class="spinner">
      <div class="loader"></div>
      <p>Carregant comandes...</p>
    </div>

    <!-- Error genèric (API) -->
    <div v-else-if="errorMessage" class="error-message">
      <p>{{ errorMessage }}</p>
    </div>

    <!-- Paginació -->
    <div v-if="!loading && paginatedOrders.length" class="pagination">
      <button @click="prevPage" :disabled="currentPage===1">Anterior</button>
      <span>Pàgina {{ currentPage }} de {{ totalPages }}</span>
      <button @click="nextPage" :disabled="currentPage===totalPages">Següent</button>
    </div>

    <!-- Modal de missatges -->
    <div v-if="showMessageModal" class="modal-overlay" @click.self="showMessageModal = false">
      <div class="modal-content">
        <h2>Missatge</h2>
        <p>{{ messageModalText }}</p>
        <div class="modal-actions">
          <button class="btn primary-btn" @click="showMessageModal = false">Tanca</button>
        </div>
      </div>
    </div>

    <!-- Vista desktop -->
    <div v-else-if="!isMobile" class="desktop-view">
      <table class="orders-table">
        <thead>
          <tr>
            <th @click="changeSort('order_number')" class="sortable">
              Codi de Comanda <span v-if="sortField==='order_number'">{{ sortDirection==='asc' ? '↑' : '↓' }}</span>
            </th>
            <th @click="changeSort('total_amount')" class="sortable">
              Total <span v-if="sortField==='total_amount'">{{ sortDirection==='asc' ? '↑' : '↓' }}</span>
            </th>
            <th>Botiga</th>
            <th @click="changeSort('status')" class="sortable">
              Estat <span v-if="sortField==='status'">{{ sortDirection==='asc' ? '↑' : '↓' }}</span>
            </th>
            <th @click="changeSort('created_at')" class="sortable">
              Data Creació <span v-if="sortField==='created_at'">{{ sortDirection==='asc' ? '↑' : '↓' }}</span>
            </th>
            <th>Accions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="order in paginatedOrders" :key="order.id">
            <td>{{ order.order_number }}</td>
            <td>{{ formatPrice(order.total_amount) }}</td>
            <td>{{ order.reserve?.botiga?.nom || 'N/A' }}</td>
            <td>
              <span class="badge" :class="badgeClass(order.status)">{{ order.status }}</span>
            </td>
            <td>{{ new Date(order.created_at).toLocaleString() }}</td>
            <td>
              <button class="action-btn large" @click="viewSummary(order.id)">Resum</button>
              <button class="action-btn large" @click="viewTicket(order.id)">Tiquet</button>
              <button
                v-if="order.status==='pending'"
                class="action-btn extra"
                @click="openUpdateModal(order)"
              >Actualitza Estat</button>
              <button
                v-else-if="order.status==='reserved'"
                class="action-btn extra"
                @click="openDeliverModal(order)"
              >Entregar Comanda</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Vista mòbil -->
    <div v-else class="mobile-list">
      <div class="order-card" v-for="order in paginatedOrders" :key="order.id">
        <div class="card-row">
          <div class="order-summary-col">
            <p><strong>Codi:</strong> {{ order.order_number }}</p>
            <p><strong>Total:</strong> {{ formatPrice(order.total_amount) }}</p>
            <p><strong>Data:</strong> {{ new Date(order.created_at).toLocaleDateString() }}</p>
            <p><strong>Productes:</strong> {{ getReserveItems(order).length }}</p>
          </div>
          <div class="order-products-col">
            <div class="product-grid">
              <template v-if="getReserveItems(order).length">
                <div class="product-card" v-for="(item, idx) in getLimitedReserveItems(order)" :key="idx">
                  <router-link v-if="item.product" :to="`/producte/${item.product.id}`">
                    {{ item.product.nom }}
                  </router-link>
                  <span v-else>No Producte</span>
                </div>
                <div v-if="getReserveItems(order).length > 2" class="more-products">
                  +{{ getReserveItems(order).length - 2 }} més
                </div>
              </template>
              <template v-else>
                <p class="no-products">No hi ha productes.</p>
              </template>
            </div>
          </div>
        </div>
        <div class="order-actions">
          <button class="action-btn large" @click="viewSummary(order.id)">Resum</button>
          <button class="action-btn large" @click="viewTicket(order.id)">Tiquet</button>
          <button
            v-if="order.status==='pending'"
            class="action-btn extra"
            @click="openUpdateModal(order)"
          >Actualitza Estat</button>
          <button
            v-else-if="order.status==='reserved'"
            class="action-btn extra"
            @click="openDeliverModal(order)"
          >Entregar Comanda</button>
        </div>
      </div>
    </div>

    <!-- Paginació -->
    <div v-if="!loading && paginatedOrders.length" class="pagination">
      <button @click="prevPage" :disabled="currentPage===1">Anterior</button>
      <span>Pàgina {{ currentPage }} de {{ totalPages }}</span>
      <button @click="nextPage" :disabled="currentPage===totalPages">Següent</button>
    </div>

    <!-- Modal de missatges -->
    <div v-if="showMessageModal" class="modal-overlay" @click.self="showMessageModal = false">
      <div class="modal-content">
        <h2>Missatge</h2>
        <p>{{ messageModalText }}</p>
        <div class="modal-actions">
          <button class="btn primary-btn" @click="showMessageModal = false">Tanca</button>
        </div>
      </div>
    </div>

    <!-- Modal per Actualitzar Estat -->
    <div v-if="showUpdateModal" class="modal-overlay" @click.self="closeUpdateModal">
      <div class="modal-content">
        <h2>Actualitza Estat de la Comanda</h2>
        <div class="order-info-modal">
          <p><strong>Codi:</strong> {{ updateModalOrder.order_number }}</p>
          <p><strong>Total:</strong> {{ formatPrice(updateModalOrder.total_amount) }}</p>
          <p><strong>Data:</strong> {{ new Date(updateModalOrder.created_at).toLocaleString() }}</p>
        </div>
        <div class="modal-field">
          <p>Acció:</p>
          <label>
            <input type="radio" value="reserve" v-model="selectedAction" /> Confirmar Reserva
          </label>
          <label>
            <input type="radio" value="cancel" v-model="selectedAction" /> Cancel·lar Comanda
          </label>
        </div>
        <div class="modal-field" v-if="selectedAction==='cancel'">
          <label for="cancelReason">Motiu de cancel·lació:</label>
          <select id="cancelReason" v-model="selectedCancelReason">
            <option value="">Selecciona un motiu</option>
            <option value="falta_de_stock">Falta de stock</option>
            <option value="client_no_present">Client no present</option>
            <option value="altres">Altres</option>
          </select>
        </div>
        <div class="modal-field" v-if="selectedAction==='reserve'">
          <p>Marca els productes que es confirmen:</p>
          <div v-for="item in updateModalOrder.reserve.reserve_items" :key="item.id" class="checkbox-field">
            <label>
              <input type="checkbox" v-model="confirmedProducts" :value="item.id" /> {{ item.product.nom }} ({{ item.quantity }})
            </label>
          </div>
        </div>
        <div class="modal-actions">
          <button class="btn primary-btn" @click="saveOrderStatus">Desa</button>
          <button class="btn close-btn" @click="closeUpdateModal">Cancel·la</button>
        </div>
      </div>
    </div>

    <!-- Modal per Entregar Comanda -->
    <div v-if="showDeliverModal" class="modal-overlay" @click.self="closeDeliverModal">
      <div class="modal-content">
        <h2>Entregar Comanda</h2>
        <div class="order-info-modal">
          <p><strong>Codi:</strong> {{ deliverModalOrder.order_number }}</p>
          <p><strong>Total:</strong> {{ formatPrice(deliverModalOrder.total_amount) }}</p>
          <p><strong>Data:</strong> {{ new Date(deliverModalOrder.created_at).toLocaleString() }}</p>
        </div>
        <div class="modal-field product-list">
          <h4>Productes</h4>
          <ul>
            <li v-for="item in deliverModalOrder.reserve.reserve_items" :key="item.id">
              {{ item.product.nom }} ({{ item.quantity }})
            </li>
          </ul>
        </div>
        <div class="modal-actions">
          <button class="btn primary-btn" @click="deliverOrder">Entregar</button>
          <button class="btn close-btn" @click="closeDeliverModal">Cancel·la</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'
import { QrcodeStream } from 'vue3-qrcode-reader'

const router = useRouter()
const API_URL = 'http://127.0.0.1:8000/api'

// Global state
const showMessageModal = ref(false)
const messageModalText = ref('')
const errorMessage = ref('')
const loading = ref(false)
const isMobile = ref(window.innerWidth < 768)
window.addEventListener('resize', () => {
  isMobile.value = window.innerWidth < 768
})

// Filter state
const stores = ref<any[]>([])
const selectedStoreId = ref('')
const searchOrderNumber = ref('')
const showFilters = ref(false)
const filterStatus = ref('')
const startDate = ref('')
const endDate = ref('')

// Orders state
const orders = ref<any[]>([])
const sortField = ref<'order_number'|'total_amount'|'status'|'created_at'>('created_at')
const sortDirection = ref<'asc'|'desc'>('desc')
const currentPage = ref(1)
const itemsPerPage = ref(10)

// Modals for update & deliver
const showUpdateModal = ref(false)
const updateModalOrder = ref<any>(null)
const selectedAction = ref<'reserve'|'cancel'>('reserve')
const selectedCancelReason = ref('')
const confirmedProducts = ref<number[]>([])

const showDeliverModal = ref(false)
const deliverModalOrder = ref<any>(null)

// Helpers
function formatPrice(price: number|string) {
  const p = typeof price === 'number' ? price : parseFloat(price as string)
  return isNaN(p) ? 'No disponible' : p.toFixed(2) + ' €'
}
function changeSort(field: typeof sortField.value) {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortDirection.value = 'asc'
  }
}
function getReserveItems(o: any) {
  return o.reserve?.reserve_items || []
}
function getLimitedReserveItems(o: any) {
  return getReserveItems(o).slice(0,2)
}
const sortedOrders = computed(() => {
  let f = [...orders.value]
  if (selectedStoreId.value) {
    f = f.filter(o => o.reserve?.botiga?.id == selectedStoreId.value)
  }
  if (searchOrderNumber.value) {
    f = f.filter(o =>
      o.order_number.toLowerCase().includes(searchOrderNumber.value.toLowerCase())
    )
  }
  if (filterStatus.value) {
    f = f.filter(o => o.status === filterStatus.value)
  }
  if (startDate.value) {
    f = f.filter(o => new Date(o.created_at) >= new Date(startDate.value))
  }
  if (endDate.value) {
    f = f.filter(o => new Date(o.created_at) <= new Date(endDate.value))
  }
  return f.sort((a,b) => {
    let aV: any = a[sortField.value], bV: any = b[sortField.value]
    if (sortField.value === 'created_at') {
      aV = new Date(aV).getTime()
      bV = new Date(bV).getTime()
    }
    return sortDirection.value === 'asc' ? aV - bV : bV - aV
  })
})
const totalPages = computed(() =>
  Math.max(1, Math.ceil(sortedOrders.value.length / itemsPerPage.value))
)
const paginatedOrders = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  return sortedOrders.value.slice(start, start + itemsPerPage.value)
})
function prevPage() {
  if (currentPage.value > 1) currentPage.value--
}
function nextPage() {
  if (currentPage.value < totalPages.value) currentPage.value++
}
const badgeClass = (s: string) =>
  ({
    pending: 'badge-pending',
    reserved: 'badge-reserved',
    completed: 'badge-completed',
    cancelled: 'badge-cancelled'
  }[s] || '')

// Loading data
async function loadStores() {
  try {
    const token = localStorage.getItem('userToken')
    const res = await axios.get(`${API_URL}/botigues-mes`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    stores.value = res.data
  } catch {
    messageModalText.value = 'Error carregant botigues.'
    showMessageModal.value = true
  }
}
async function loadOrders() {
  loading.value = true
  try {
    const token = localStorage.getItem('userToken')
    const res = await axios.get(`${API_URL}/vendor/orders`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    orders.value = res.data
  } catch {
    errorMessage.value = 'Error carregant comandes. Intenta-ho més tard.'
  } finally {
    loading.value = false
  }
}

// Filters
function toggleFilters() {
  showFilters.value = !showFilters.value
}
function resetFilters() {
  selectedStoreId.value = ''
  filterStatus.value = ''
  startDate.value = ''
  endDate.value = ''
}

// QR Scanner
const showScanner = ref(false)
const scannerReady = ref(false)
async function openScanner() {
  try {
    await navigator.mediaDevices.getUserMedia({ video: true })
    scannerReady.value = true
    showScanner.value = true
  } catch {
    messageModalText.value =
      '⚠️ No s’ha pogut accedir a la càmera. Revisa permisos.'
    showMessageModal.value = true
  }
}
function closeScanner() {
  showScanner.value = false
  scannerReady.value = false
}
function onDecode(res: string) {
  searchOrderNumber.value = res
  closeScanner()
}
function onInit(p: Promise<any>) {
  p.catch(() => {
    messageModalText.value =
      '⚠️ Error iniciant lector QR. Comprova la càmera.'
    showMessageModal.value = true
    closeScanner()
  })
}

// Update status
function openUpdateModal(o: any) {
  updateModalOrder.value = o
  selectedAction.value = 'reserve'
  confirmedProducts.value = o.reserve?.reserve_items.map((i: any) => i.id) || []
  showUpdateModal.value = true
}
function closeUpdateModal() {
  showUpdateModal.value = false
}
async function saveOrderStatus() {
  if (selectedAction.value === 'cancel' && !selectedCancelReason.value) {
    messageModalText.value = 'Selecciona motiu de cancel·lació.'
    showMessageModal.value = true
    return
  }
  if (selectedAction.value === 'reserve' && !confirmedProducts.value.length) {
    messageModalText.value = 'Selecciona productes per reservar.'
    showMessageModal.value = true
    return
  }

  const status = selectedAction.value === 'cancel' ? 'cancelled' : 'reserved'
  try {
    const token = localStorage.getItem('userToken')
    await axios.put(
      `${API_URL}/orders/${updateModalOrder.value.id}`,
      {
        status,
        cancellation_reason: selectedCancelReason.value || null,
        confirmed_product_ids:
          status === 'reserved' ? confirmedProducts.value : null
      },
      { headers: { Authorization: `Bearer ${token}` } }
    )
    // Update local state
    updateModalOrder.value.status = status
    const idx = orders.value.findIndex(o => o.id === updateModalOrder.value.id)
    if (idx !== -1) orders.value[idx].status = status

    closeUpdateModal()
    messageModalText.value = 'Estat actualitzat correctament.'
    showMessageModal.value = true
  } catch {
    messageModalText.value = 'Error actualitzant estat.'
    showMessageModal.value = true
  }
}

// Deliver order
function openDeliverModal(o: any) {
  deliverModalOrder.value = o
  showDeliverModal.value = true
}
function closeDeliverModal() {
  showDeliverModal.value = false
}
async function deliverOrder() {
  try {
    const token = localStorage.getItem('userToken')
    await axios.put(
      `${API_URL}/orders/${deliverModalOrder.value.id}`,
      { status: 'completed' },
      { headers: { Authorization: `Bearer ${token}` } }
    )
    // Update local state
    deliverModalOrder.value.status = 'completed'
    const idx = orders.value.findIndex(o => o.id === deliverModalOrder.value.id)
    if (idx !== -1) orders.value[idx].status = 'completed'

    closeDeliverModal()
    messageModalText.value = 'Comanda entregada correctament.'
    showMessageModal.value = true
  } catch {
    messageModalText.value = 'Error entregant comanda.'
    showMessageModal.value = true
  }
}

onMounted(() => {
  loadStores()
  loadOrders()
})
</script>

<style scoped>
/* Tipografia i colors globals */
.vendor-orders {
  max-width: 1000px;
  margin: 40px auto;
  padding: 20px;
  font-family: 'Roboto', sans-serif;
  color: #2c3e50;
  background: #fafafa;
  border-radius: 8px;
}

/* Títol */
.vendor-orders > h1 {
  font-size: 1.75rem;
  margin-bottom: 1rem;
  text-align: center;
  color: #34495e;
}

/* Header Controls */
.header-controls {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}
.header-controls .btn {
  background: #3498db;
  color: #fff;
}
.header-controls .btn:hover {
  background: #2980b9;
}
.search-input {
  flex: 1;
  min-width: 200px;
  padding: 0.5rem 0.75rem;
  border: 1px solid #ccc;
  border-radius: 4px;
}

/* Modal QR */
.scanner-modal {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.75);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.scanner-wrapper {
  position: relative;
  width: 320px; height: 320px;
  background: #fff;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}
.qr-loader {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #888;
}

/* Filtres Avançats */
.advanced-filters {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1rem;
  padding: 1rem;
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 8px;
  margin-bottom: 2rem;
}
.advanced-filters label {
  font-weight: 500;
  margin-bottom: 0.25rem;
}
.advanced-filters select,
.advanced-filters input[type="date"] {
  padding: 0.5rem;
  border: 1px solid #ccc;
  border-radius: 4px;
}
.reset-btn {
  background: #e74c3c;
  color: #fff;
}
.reset-btn:hover {
  background: #c0392b;
}

/* Spinner */
.spinner {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin: 2rem 0;
}
.loader {
  width: 48px; height: 48px;
  border: 6px solid #eee;
  border-top-color: #3498db;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Taula d'ordres (desktop) */
.desktop-view .orders-table {
  width: 100%;
  border-collapse: collapse;
  background: #fff;
  border-radius: 8px;
  overflow: hidden;
}
.orders-table th,
.orders-table td {
  padding: 0.75rem 1rem;
  text-align: center;
  border-bottom: 1px solid #eee;
}
.orders-table th {
  background: #ecf0f1;
  cursor: pointer;
  user-select: none;
}
.orders-table tr:hover {
  background: #f1f7fc;
}

/* Badges d'estat */
.badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.85em;
  color: #fff;
  text-transform: capitalize;
}
.badge-pending   { background: #f1c40f; }
.badge-reserved  { background: #3498db; }
.badge-completed { background: #2ecc71; }
.badge-cancelled { background: #e74c3c; }

/* Targetes mòbil */
.mobile-list {
  display: grid;
  gap: 1rem;
}
.order-card {
  background: #fff;
  border-radius: 8px;
  padding: 1rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.card-row {
  margin-bottom: 1rem;
}
.product-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}
.product-card {
  background: #ecf0f1;
  padding: 0.5rem;
  border-radius: 4px;
  font-size: 0.85em;
}

/* Botons d'acció */
.action-btn {
  padding: 0.5rem 1rem;
  margin: 4px;
  border: none;
  border-radius: 4px;
  color: #fff;
  transition: background 0.2s;
}
.action-btn.large {
  font-size: 1rem;
}
.action-btn:not(.extra) {
  background: #3498db;
}
.action-btn:not(.extra):hover {
  background: #2980b9;
}
.action-btn.extra {
  background: #f39c12;
}
.action-btn.extra:hover {
  background: #d68910;
}

/* Paginació */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin: 2rem 0 1rem;
}
.pagination button {
  padding: 0.5rem 1rem;
  border: none;
  background: #2ecc71;
  color: #fff;
  border-radius: 4px;
  cursor: pointer;
}
.pagination button:disabled {
  background: #95a5a6;
  cursor: not-allowed;
}

/* Modals */
.modal-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
}
.modal-content {
  background: #fff;
  width: 90%;
  max-width: 400px;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}
.modal-content h2 {
  margin-top: 0;
  margin-bottom: 1rem;
  font-size: 1.25rem;
  color: #34495e;
}
.modal-field label {
  margin-bottom: 0.5rem;
  font-weight: 500;
}
.modal-actions {
  display: flex;
  justify-content: space-between;
  margin-top: 1.5rem;
}
.primary-btn {
  background: #3498db;
  color: #fff;
  width: 48%;
}
.primary-btn:hover {
  background: #2980b9;
}
.close-btn {
  background: #e74c3c;
  color: #fff;
  width: 48%;
}
.close-btn:hover {
  background: #c0392b;
}

/* Responsive */
@media (max-width: 768px) {
  .orders-table { font-size: 0.9rem; }
  .header-controls { gap: 8px; }
}
</style>
