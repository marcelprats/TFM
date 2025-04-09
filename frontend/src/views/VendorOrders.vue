<template>
  <div class="vendor-orders">
    <h1>Informació de vendes</h1>

    <!-- Control superior: Botó per mostrar/ocultar filtres, camp de cerca i botó QR -->
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

    <!-- Modal per el lector QR -->
    <div v-if="showScanner" class="scanner-modal">
      <div class="scanner-wrapper">
        <!-- Component QR que obre la càmera i llegeix el codi -->
        <qrcode-stream @decode="onDecode" @init="onInit" class="qr-stream" />
        <!-- Overlay amb les línies a les cantonades per delimitar l’àrea d’escaneig -->
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
          <option v-for="store in stores" :key="store.id" :value="store.id">
            {{ store.nom }}
          </option>
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

    <!-- Spinner de càrrega -->
    <div v-if="loading" class="spinner">
      <div class="loader"></div>
      <p>Carregant comandes...</p>
    </div>

    <!-- Missatge d'error -->
    <div v-else-if="errorMessage" class="error-message">
      <p>{{ errorMessage }}</p>
    </div>

    <!-- Vista Desktop: Taula d'ordres -->
    <div v-else-if="!isMobile" class="desktop-view">
      <table class="orders-table">
        <thead>
          <tr>
            <th @click="changeSort('order_number')">
              Codi de Comanda <span v-if="sortField==='order_number'">{{ sortDirection==='asc' ? '↑' : '↓' }}</span>
            </th>
            <th @click="changeSort('total_amount')">
              Total <span v-if="sortField==='total_amount'">{{ sortDirection==='asc' ? '↑' : '↓' }}</span>
            </th>
            <th>Botiga</th>
            <th @click="changeSort('status')">
              Estat <span v-if="sortField==='status'">{{ sortDirection==='asc' ? '↑' : '↓' }}</span>
            </th>
            <th @click="changeSort('created_at')">
              Data Creació <span v-if="sortField==='created_at'">{{ sortDirection==='asc' ? '↑' : '↓' }}</span>
            </th>
            <th>Accions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="order in paginatedOrders" :key="order.id">
            <td>{{ order.order_number }}</td>
            <td>{{ formatPrice(order.total_amount) }}</td>
            <td>
              {{ order.reserve && order.reserve.botiga ? order.reserve.botiga.nom : 'N/A' }}
            </td>
            <td>
              <span class="badge" :class="badgeClass(order.status)">
                {{ order.status }}
              </span>
            </td>
            <td>{{ new Date(order.created_at).toLocaleString() }}</td>
            <td>
              <button class="action-btn large" @click="viewSummary(order.id)">Resum</button>
              <button class="action-btn large" @click="viewTicket(order.id)">Tiquet</button>
              <!-- Si l'estat és pending, es mostrarà l'opció d'actualitzar estat (amb modal complet) -->
              <button v-if="order.status === 'pending'" class="action-btn extra" @click="openUpdateModal(order)">
                Actualitza Estat
              </button>
              <!-- Si l'estat és reserved, es mostrarà l'opció d'entregar la comanda -->
              <button v-else-if="order.status === 'reserved'" class="action-btn extra" @click="openDeliverModal(order)">
                Entregar Comanda
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Vista mòbil: Targetes d'ordres -->
    <div v-else class="mobile-list">
      <div class="order-card" v-for="order in paginatedOrders" :key="order.id">
        <div class="card-row">
          <div class="order-summary-col">
            <p><strong>Codi:</strong> {{ order.order_number }}</p>
            <p><strong>Total:</strong> {{ formatPrice(order.total_amount) }}</p>
            <p><strong>Data:</strong> {{ new Date(order.created_at).toLocaleDateString() }}</p>
            <p><strong>Total Productes:</strong> {{ order.reserve && order.reserve.reserve_items ? order.reserve.reserve_items.length : 0 }}</p>
          </div>
          <div class="order-products-col">
            <div class="product-grid">
              <template v-if="getReserveItems(order).length">
                <div class="product-card" v-for="(item, idx) in getLimitedReserveItems(order)" :key="idx">
                  <router-link v-if="item.product" :to="`/producte/${item.product.id}`" class="product-link">
                    <p>{{ item.product.nom }}</p>
                  </router-link>
                  <span v-else>No Producte</span>
                </div>
                <div v-if="getReserveItems(order).length > 2" class="more-products">
                  <p>+{{ getReserveItems(order).length - 2 }} més</p>
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
          <button v-if="order.status === 'pending'" class="action-btn extra" @click="openUpdateModal(order)">
            Actualitza Estat
          </button>
          <button v-else-if="order.status === 'reserved'" class="action-btn extra" @click="openDeliverModal(order)">
            Entregar Comanda
          </button>
        </div>
      </div>
    </div>

    <!-- Paginació -->
    <div v-if="!loading && paginatedOrders.length > 0" class="pagination">
      <button @click="prevPage" :disabled="currentPage === 1">Anterior</button>
      <span>Pàgina {{ currentPage }} de {{ totalPages }}</span>
      <button @click="nextPage" :disabled="currentPage === totalPages">Següent</button>
    </div>
    <div v-else-if="!loading && paginatedOrders.length === 0" class="no-orders">
      <p>No hi ha comandes registrades.</p>
    </div>

    <!-- Modal per actualitzar estat (per comandes pendents) -->
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
          <div v-if="updateModalOrder.status === 'pending'">
            <label>
              <input type="radio" value="reserve" v-model="selectedAction" />
              Confirmar Reserva
            </label>
            <label>
              <input type="radio" value="cancel" v-model="selectedAction" />
              Cancel·lar Comanda
            </label>
          </div>
        </div>
        <!-- Si s'ha seleccionat cancel·lació -->
        <div class="modal-field" v-if="selectedAction === 'cancel'">
          <label for="cancelReason">Motiu de cancel·lació:</label>
          <select id="cancelReason" v-model="selectedCancelReason">
            <option value="">Selecciona un motiu</option>
            <option value="falta_de_stock">Falta de stock</option>
            <option value="client_no_present">El client no s'ha presentat</option>
            <option value="altres">Altres</option>
          </select>
        </div>
        <!-- Si s'ha seleccionat reserva, mostra la llista de productes amb checkbox -->
        <div class="modal-field" v-if="updateModalOrder.status === 'pending' && selectedAction === 'reserve'">
          <p>Marca els productes que es confirmen per la reserva:</p>
          <div v-for="(item, idx) in updateModalOrder.reserve.reserve_items" :key="idx" class="checkbox-field">
            <label>
              <input type="checkbox" v-model="confirmedProducts" :value="item.id" />
              {{ item.product ? item.product.nom : 'Producte no disponible' }} (Quantitat: {{ item.quantity }})
            </label>
          </div>
        </div>
        <div class="modal-actions">
          <button class="btn primary-btn" @click="saveOrderStatus">Desa</button>
          <button class="btn close-btn" @click="closeUpdateModal">Cancel·la</button>
        </div>
      </div>
    </div>

    <!-- Modal per entregar comanda (per comandes reservades) -->
    <div v-if="showDeliverModal" class="modal-overlay" @click.self="closeDeliverModal">
      <div class="modal-content">
        <h2>Entregar Comanda</h2>
        <div class="order-info-modal">
          <p><strong>Codi:</strong> {{ deliverModalOrder.order_number }}</p>
          <p><strong>Total:</strong> {{ formatPrice(deliverModalOrder.total_amount) }}</p>
          <p><strong>Data:</strong> {{ new Date(deliverModalOrder.created_at).toLocaleString() }}</p>
        </div>

        <div class="modal-field product-list">
          <h4>Llista de Productes</h4>
          <div v-if="deliverModalOrder.reserve && deliverModalOrder.reserve.reserve_items && deliverModalOrder.reserve.reserve_items.length">
            <ul>
              <li v-for="(item, idx) in deliverModalOrder.reserve.reserve_items" :key="idx">
                {{ item.product ? item.product.nom : 'Producte no disponible' }}
                (Quantitat: {{ item.quantity }})
              </li>
            </ul>
          </div>
          <div v-else>
            <p>No hi ha productes.</p>
          </div>
        </div>

        <p>Revisa aquesta informació i, si està correcte, prem "Entregar Comanda" per completar la comanda.</p>
        <div class="modal-actions">
          <button class="btn primary-btn" @click="deliverOrder">Entregar Comanda</button>
          <button class="btn close-btn" @click="closeDeliverModal">Cancel·la</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import { QrcodeStream } from 'vue3-qrcode-reader';

const router = useRouter();
const errorMessage = ref('');
const loading = ref(false);
const API_URL = 'http://127.0.0.1:8000/api';

// Responsivitat
const isMobile = ref(window.innerWidth < 768);
window.addEventListener('resize', () => {
  isMobile.value = window.innerWidth < 768;
});

// Variables per filtres, cerca i botigues
const stores = ref<any[]>([]);
const selectedStoreId = ref('');
const searchOrderNumber = ref('');
const showFilters = ref(false);
// Filtres avançats
const filterStatus = ref('');
const startDate = ref('');
const endDate = ref('');

function toggleFilters() {
  showFilters.value = !showFilters.value;
}

async function loadStores() {
  try {
    const token = localStorage.getItem('userToken');
    const response = await axios.get(`${API_URL}/botigues-mes`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    stores.value = response.data;
  } catch (error) {
    console.error('Error carregant les botigues:', error);
  }
}

// Carrega les comandes del venedor
const orders = ref<any[]>([]);
const sortField = ref('created_at');
const sortDirection = ref<'asc' | 'desc'>('desc');
const currentPage = ref(1);
const itemsPerPage = ref(10);

function formatPrice(price: number | string): string {
  const p = typeof price === 'number' ? price : parseFloat(price);
  return isNaN(p) ? 'No disponible' : p.toFixed(2) + ' €';
}

function changeSort(field: string) {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortField.value = field;
    sortDirection.value = 'asc';
  }
}

// Funció per obtenir els reserve_items (el backend retorna "reserve_items")
function getReserveItems(orderItem: any): any[] {
  return orderItem.reserve && orderItem.reserve.reserve_items
    ? orderItem.reserve.reserve_items
    : [];
}

function getLimitedReserveItems(orderItem: any): any[] {
  return getReserveItems(orderItem).slice(0, 2);
}

const sortedOrders = computed(() => {
  let filtered = [...orders.value];
  if (selectedStoreId.value) {
    filtered = filtered.filter(order =>
      order.reserve && order.reserve.botiga && order.reserve.botiga.id == selectedStoreId.value
    );
  }
  if (searchOrderNumber.value) {
    const query = searchOrderNumber.value.toLowerCase();
    filtered = filtered.filter(order =>
      order.order_number.toLowerCase().includes(query)
    );
  }
  if (filterStatus.value) {
    filtered = filtered.filter(order => order.status === filterStatus.value);
  }
  if (startDate.value) {
    filtered = filtered.filter(order =>
      new Date(order.created_at) >= new Date(startDate.value)
    );
  }
  if (endDate.value) {
    filtered = filtered.filter(order =>
      new Date(order.created_at) <= new Date(endDate.value)
    );
  }
  return filtered.sort((a, b) => {
    let fieldA = a[sortField.value];
    let fieldB = b[sortField.value];
    if (sortField.value === 'total_amount') {
      fieldA = parseFloat(fieldA);
      fieldB = parseFloat(fieldB);
    }
    if (sortField.value === 'created_at') {
      fieldA = new Date(fieldA).getTime();
      fieldB = new Date(fieldB).getTime();
    }
    if (fieldA < fieldB) return sortDirection.value === 'asc' ? -1 : 1;
    if (fieldA > fieldB) return sortDirection.value === 'asc' ? 1 : -1;
    return 0;
  });
});

const totalPages = computed(() => {
  return Math.ceil(sortedOrders.value.length / itemsPerPage.value) || 1;
});

const paginatedOrders = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  return sortedOrders.value.slice(start, start + itemsPerPage.value);
});

function prevPage() {
  if (currentPage.value > 1) currentPage.value--;
}

function nextPage() {
  if (currentPage.value < totalPages.value) currentPage.value++;
}

const badgeClass = (status: string): string => {
  switch (status) {
    case 'pending':
      return 'badge-pending';
    case 'reserved':
      return 'badge-reserved';
    case 'completed':
      return 'badge-completed';
    case 'cancelled':
      return 'badge-cancelled';
    default:
      return '';
  }
};

async function loadOrders() {
  loading.value = true;
  try {
    const token = localStorage.getItem('userToken');
    const response = await axios.get(`${API_URL}/vendor/orders`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    orders.value = response.data;
  } catch (error) {
    console.error('Error carregant les comandes:', error);
    errorMessage.value = 'Error carregant les comandes. Si us plau, intenta-ho més tard.';
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  loadStores();
  loadOrders();
});

// Funcions d'acció per comandes
function viewSummary(orderId: number) {
  router.push(`/order-summary/${orderId}`);
}

function viewTicket(orderId: number) {
  router.push(`/order-confirmation/${orderId}`);
}

function reorder(orderId: number) {
  alert(`Recomanant els productes de la comanda ${orderId}`);
}

// Modal per Actualitzar Estat (per comandes pendents)
const showUpdateModal = ref(false);
const updateModalOrder = ref<any>(null);
const selectedAction = ref(''); // 'reserve' o 'cancel'
const newOrderStatus = ref('');
const selectedCancelReason = ref('');
const confirmedProducts = ref<number[]>([]);

function openUpdateModal(order: any) {
  updateModalOrder.value = order;
  if (order.status === 'pending') {
    selectedAction.value = 'reserve';
    // Per reserva, obtenim els IDs dels items (backend retorna "reserve_items")
    confirmedProducts.value =
      order.reserve && order.reserve.reserve_items
        ? order.reserve.reserve_items.map((item: any) => item.id)
        : [];
  }
  showUpdateModal.value = true;
}

function closeUpdateModal() {
  showUpdateModal.value = false;
}

// Modal per Entregar Comanda (per comandes reservades)
const showDeliverModal = ref(false);
const deliverModalOrder = ref<any>(null);

function openDeliverModal(order: any) {
  deliverModalOrder.value = order;
  showDeliverModal.value = true;
}

function closeDeliverModal() {
  showDeliverModal.value = false;
}

async function saveOrderStatus() {
  // Aquest mètode s'aplica per actualitzar estats des del modal d'actualització (per pendents)
  if (selectedAction.value === 'cancel' && !selectedCancelReason.value) {
    alert('Si us plau, selecciona un motiu per cancel·lar la comanda.');
    return;
  }
  if (selectedAction.value === 'reserve' && confirmedProducts.value.length === 0) {
    alert('Si us plau, selecciona almenys un producte per confirmar la reserva.');
    return;
  }
  let updatedStatus = '';
  if (selectedAction.value === 'reserve') {
    updatedStatus = 'reserved';
  } else if (selectedAction.value === 'cancel') {
    updatedStatus = 'cancelled';
  }
  try {
    const token = localStorage.getItem('userToken');
    await axios.patch(
      `${API_URL}/orders/${updateModalOrder.value.id}`,
      {
        status: updatedStatus,
        cancellation_reason: selectedAction.value === 'cancel' ? selectedCancelReason.value : null,
        confirmed_product_ids: selectedAction.value === 'reserve' ? confirmedProducts.value : null,
      },
      { headers: { Authorization: `Bearer ${token}` } }
    );
    updateModalOrder.value.status = updatedStatus;
    alert('Estat actualitzat correctament.');
    closeUpdateModal();
  } catch (err) {
    console.error("Error actualitzant l’estat:", err);
    alert("Error actualitzant l’estat. Intenta-ho més tard.");
  }
}

async function deliverOrder() {
  // Aquesta funció actualitza directament el estat de la comanda a "completed" per les comandes reservades
  try {
    const token = localStorage.getItem('userToken');
    await axios.patch(
      `${API_URL}/orders/${deliverModalOrder.value.id}`,
      {
        status: 'completed'
      },
      { headers: { Authorization: `Bearer ${token}` } }
    );
    deliverModalOrder.value.status = 'completed';
    alert('Comanda entregada correctament.');
    closeDeliverModal();
  } catch (err) {
    console.error("Error actualitzant l’estat de l’entrega:", err);
    alert("Error actualitzant l’estat. Intenta-ho més tard.");
  }
}

// Funcionalitat QR (lector)
const showScanner = ref(false);
function openScanner() {
  showScanner.value = true;
}
function closeScanner() {
  showScanner.value = false;
}
function onDecode(result: string) {
  console.log('QR decoded:', result);
  searchOrderNumber.value = result;
  showScanner.value = false;
}
function onInit(promise: Promise<any>) {
  promise.catch(error => {
    console.error('Error iniciant el lector QR:', error);
    alert('Error iniciant el lector QR');
  });
}

// Funció per reiniciar filtres avançats
function resetFilters() {
  selectedStoreId.value = '';
  filterStatus.value = '';
  startDate.value = '';
  endDate.value = '';
}
</script>

<style scoped>
/* Container principal */
.vendor-orders {
  max-width: 1000px;
  margin: 40px auto;
  padding: 20px;
  background-color: #ffffff;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  font-family: 'Roboto', sans-serif;
}

/* Header Controls */
.header-controls {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 15px;
}
.search-input {
  flex-grow: 1;
  padding: 8px;
  border: 1px solid #dddddd;
  border-radius: 4px;
}
.toggle-filters-btn,
.scan-btn {
  padding: 8px 16px;
  background-color: #007bff;
  color: #ffffff;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.3s;
}
.toggle-filters-btn:hover,
.scan-btn:hover {
  background-color: #0056b3;
}

/* Modal QR */
.scanner-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.scanner-wrapper {
  position: relative;
  width: 300px;
  height: 300px;
  margin: 0 auto;
  border: 2px solid #dddddd;
  border-radius: 8px;
  overflow: hidden;
}
.qr-stream video {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
}
.corner {
  width: 30px;
  height: 30px;
  border: 3px solid #00ff00;
  position: absolute;
}
.corner.top-left {
  top: 10px;
  left: 10px;
  border-right: none;
  border-bottom: none;
}
.corner.top-right {
  top: 10px;
  right: 10px;
  border-left: none;
  border-bottom: none;
}
.corner.bottom-left {
  bottom: 10px;
  left: 10px;
  border-right: none;
  border-top: none;
}
.corner.bottom-right {
  bottom: 10px;
  right: 10px;
  border-left: none;
  border-top: none;
}
.close-btn {
  margin-top: 15px;
  padding: 8px 16px;
  background-color: #dc3545;
  color: #ffffff;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.3s;
}
.close-btn:hover {
  background-color: #c82333;
}

/* Advanced Filters */
.advanced-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  padding: 15px;
  border: 1px solid #dddddd;
  border-radius: 8px;
  margin-bottom: 20px;
  background-color: #f9f9f9;
}
.filter-field {
  flex: 1 1 200px;
  display: flex;
  flex-direction: column;
}
.filter-field label {
  font-weight: 600;
  margin-bottom: 5px;
  color: #333333;
}
.filter-field input,
.filter-field select {
  padding: 8px;
  border: 1px solid #cccccc;
  border-radius: 4px;
}

/* Desktop Orders Table */
.orders-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
}
.orders-table th,
.orders-table td {
  border: 1px solid #dddddd;
  padding: 10px;
  text-align: center;
  cursor: pointer;
}
.orders-table th {
  background-color: #f0f0f0;
}
.orders-table th span {
  font-size: 0.8em;
  margin-left: 5px;
}

/* Mobile Order Cards */
.mobile-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}
.order-card {
  display: flex;
  flex-direction: column;
  border: 1px solid #dddddd;
  border-radius: 8px;
  padding: 15px;
  background-color: #f9f9f9;
  transition: box-shadow 0.3s;
}
.order-card:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}
.card-row {
  display: flex;
  flex-direction: column;
  gap: 8px;
  border-bottom: 1px solid #dddddd;
  padding-bottom: 10px;
  margin-bottom: 10px;
}
.order-summary-col {
  flex: 1;
}
.order-summary-col p {
  margin: 5px 0;
}
.order-products-col {
  flex: 2;
}
.product-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
  gap: 8px;
}
.product-card {
  background-color: #e9ecef;
  border-radius: 4px;
  padding: 5px;
  font-size: 0.8em;
  text-align: center;
}
.more-products p {
  font-size: 0.75em;
  color: #555555;
  text-align: center;
}

/* Order Actions */
.order-actions {
  display: flex;
  justify-content: center;
  gap: 20px;
  margin-top: 10px;
}
.order-actions .action-btn {
  padding: 8px 16px;
  font-size: 1em;
  cursor: pointer;
  border: none;
  border-radius: 4px;
  transition: background-color 0.3s;
}
.action-btn.large {
  font-size: 1em;
}
.action-btn.extra {
  background-color: #ff9800;
  color: #ffffff;
}
.action-btn.extra:hover {
  background-color: #e68900;
}
.action-btn:not(.extra) {
  background-color: #007bff;
  color: #ffffff;
}
.action-btn:not(.extra):hover {
  background-color: #0056b3;
}

/* Pagination */
.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 10px;
}
.pagination button {
  padding: 8px 12px;
  border: none;
  background-color: #28a745;
  color: #ffffff;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.3s;
}
.pagination button:disabled {
  background-color: #cccccc;
  cursor: not-allowed;
}

/* Modal per Actualitzar Estat (per comandes pendents) */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
}
.modal-content {
  background: #ffffff;
  padding: 20px;
  border-radius: 8px;
  width: 90%;
  max-width: 400px;
}
.modal-content h2 {
  margin-top: 0;
  text-align: center;
}
.order-info-modal {
  margin-bottom: 15px;
  border-bottom: 1px solid #dddddd;
  padding-bottom: 10px;
}
.modal-field {
  margin-bottom: 15px;
  display: flex;
  flex-direction: column;
}
.modal-field label {
  margin-bottom: 5px;
  font-weight: 600;
  color: #333333;
}
.modal-field select,
.modal-field input[type="radio"],
.modal-field input[type="checkbox"] {
  padding: 8px;
  border: 1px solid #cccccc;
  border-radius: 4px;
}
.checkbox-field {
  margin: 5px 0;
}
.modal-actions {
  display: flex;
  justify-content: space-around;
  margin-top: 20px;
}
.modal-actions .btn {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}
.primary-btn {
  background-color: #007bff;
  color: #ffffff;
}
.primary-btn:hover {
  background-color: #0056b3;
}
.close-btn {
  background-color: #dc3545;
  color: #ffffff;
}
.close-btn:hover {
  background-color: #c82333;
}

/* Modal per Entregar Comanda (per comandes reservades) */
/* Es fa una modal similar però sense checkbox ni desplegable de cancel·lació */
.modal-content.deliver-modal {
  /* Si voleu diferenciar-la una mica, podeu afegir estils addicionals aquí */
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .vendor-orders {
    margin: 20px;
    padding: 15px;
  }
}
</style>
