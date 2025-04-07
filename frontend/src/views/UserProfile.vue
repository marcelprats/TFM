<template>
  <div class="profile-page">
    <aside class="sidebar">
      <nav>
        <ul>
          <li :class="{ active: selectedMenu === 'profile' }" @click="selectedMenu = 'profile'">
            Dades Usuari
          </li>
          <li :class="{ active: selectedMenu === 'orders' }" @click="selectedMenu = 'orders'">
            Historial de Compres
          </li>
        </ul>
      </nav>
    </aside>
    <main class="content">
      <!-- Secció Dades Usuari -->
      <div v-if="selectedMenu === 'profile'">
        <h1>Perfil d'Usuari</h1>
        <div v-if="user">
          <p><strong>Nom:</strong> {{ user.name }}</p>
          <p><strong>Email:</strong> {{ user.email }}</p>
          <p><strong>Rol:</strong> {{ role }}</p>
        </div>
        <div v-else>
          <p>Carregant informació...</p>
        </div>
      </div>
      
      <!-- Secció Historial de Comandes -->
      <div v-else-if="selectedMenu === 'orders'">
        <h1>Historial de Comandes</h1>
        
        <!-- Filtres externs -->
        <div class="filters">
          <input
            type="text"
            v-model="searchQuery"
            placeholder="Cerca per codi de comanda..."
            class="filter-input"
          />
          <select v-model="filterPaymentMethod" class="filter-select">
            <option value="">Tots els mètodes</option>
            <option value="online">Online</option>
            <option value="cash">Cash</option>
          </select>
          <select v-model="filterStatus" class="filter-select">
            <option value="">Tots els estats</option>
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="cancelled">Cancelled</option>
          </select>
          <div class="date-filters">
            <label>
              Data Inici:
              <input type="date" v-model="startDate" class="filter-date" />
            </label>
            <label>
              Data Fi:
              <input type="date" v-model="endDate" class="filter-date" />
            </label>
          </div>
        </div>
        
        <!-- Vista per pantalles amples: taula -->
        <div v-if="!isMobile">
          <table class="orders-table">
            <thead>
              <tr>
                <th @click="changeSort('order_number')">
                  Codi de Comanda <span v-if="sortField==='order_number'">{{ sortDirection==='asc' ? '↑' : '↓' }}</span>
                </th>
                <th @click="changeSort('total_amount')">
                  Total <span v-if="sortField==='total_amount'">{{ sortDirection==='asc' ? '↑' : '↓' }}</span>
                </th>
                <th @click="changeSort('payment_method')">
                  Mètode de Pagament <span v-if="sortField==='payment_method'">{{ sortDirection==='asc' ? '↑' : '↓' }}</span>
                </th>
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
              <tr v-for="orderItem in paginatedOrders" :key="orderItem.id">
                <td>{{ orderItem.order_number }}</td>
                <td>{{ formatPrice(orderItem.total_amount) }}</td>
                <td>{{ orderItem.payment_method }}</td>
                <td>
                  <span class="badge" :class="badgeClass(orderItem.status)">
                    {{ orderItem.status }}
                  </span>
                </td>
                <td>{{ new Date(orderItem.created_at).toLocaleString() }}</td>
                <td>
                  <button class="action-btn" @click="viewDetails(orderItem.id)">Detalls</button>
                  <button class="action-btn" @click="reorder(orderItem.id)">Recomanar</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Vista per dispositius mòbils: mode targeta -->
        <div v-else class="mobile-list">
          <div
            class="order-card"
            v-for="orderItem in paginatedOrders"
            :key="orderItem.id"
            @click="viewDetails(orderItem.id)"
          >
            <div class="card-left">
              <p><strong>Codi:</strong> {{ orderItem.order_number }}</p>
              <p><strong>Total:</strong> {{ formatPrice(orderItem.total_amount) }}</p>
              <p><strong>Data:</strong> {{ new Date(orderItem.created_at).toLocaleDateString() }}</p>
            </div>
            <div class="card-right">
              <template v-if="getReserveItems(orderItem).length">
                <div
                  class="product-card"
                  v-for="(item, i) in getReserveItems(orderItem)"
                  :key="i"
                  v-if="i < 3"
                >
                  <p>{{ item.product.nom }}</p>
                </div>
                <div v-if="getReserveItems(orderItem).length > 3" class="more-products">
                  <p>+{{ getReserveItems(orderItem).length - 3 }} més</p>
                </div>
              </template>
              <template v-else>
                <p>No hi ha productes.</p>
              </template>
            </div>
          </div>
        </div>
        
        <!-- Spinner de càrrega -->
        <div v-if="loadingOrders" class="spinner">
          <div class="loader"></div>
          <p>Carregant comandes...</p>
        </div>
        
        <div v-else-if="errorMessage">
          <p class="error-message">{{ errorMessage }}</p>
        </div>
        
        <!-- Paginació -->
        <div v-if="!loadingOrders && paginatedOrders.length > 0" class="pagination">
          <button @click="prevPage" :disabled="currentPage === 1">Anterior</button>
          <span>Pàgina {{ currentPage }} de {{ totalPages }}</span>
          <button @click="nextPage" :disabled="currentPage === totalPages">Següent</button>
        </div>
        
        <div v-else-if="!loadingOrders && paginatedOrders.length === 0">
          <p>No hi ha comandes registrades.</p>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import { getUser, getUserType } from '../services/authService';

const router = useRouter();
const user = ref(getUser());
const userType = ref(getUserType());
const role = ref(userType.value === 'vendor' ? 'Venedor' : 'Comprador');
const selectedMenu = ref('profile');

const orders = ref<any[]>([]);
const loadingOrders = ref(false);
const errorMessage = ref('');
const API_URL = 'http://127.0.0.1:8000/api';

// Variable per detectar si és mòbil
const isMobile = ref(window.innerWidth < 768);
window.addEventListener('resize', () => {
  isMobile.value = window.innerWidth < 768;
});

// Variables de filtratge, ordenació i paginació
const searchQuery = ref('');
const filterPaymentMethod = ref('');
const filterStatus = ref('');
const startDate = ref('');
const endDate = ref('');
const sortField = ref('created_at');
const sortDirection = ref<'asc' | 'desc'>('desc');
const currentPage = ref(1);
const itemsPerPage = ref(5);

// Funció per formatar preus
function formatPrice(price: number | string): string {
  const p = typeof price === 'number' ? price : parseFloat(price);
  if (isNaN(p)) return 'No disponible';
  return p.toFixed(2) + ' €';
}

// Funció per canviar l'ordenació clicant la capçalera
function changeSort(field: string) {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortField.value = field;
    sortDirection.value = 'asc';
  }
}

// Funció auxiliar per obtenir els ítems de reserva
function getReserveItems(orderItem: any): any[] {
  return orderItem.reserve && orderItem.reserve.reserveItems ? orderItem.reserve.reserveItems : [];
}

// Computed: Filtrar les comandes
const filteredOrders = computed(() => {
  return orders.value.filter(order => {
    const orderNumber = order.order_number.toLowerCase();
    const numericOrder = orderNumber.replace(/^ord-/, '');
    const query = searchQuery.value.toLowerCase();
    const matchesSearch =
      orderNumber.includes(query) || numericOrder.includes(query);
      
    const matchesPayment = filterPaymentMethod.value ? order.payment_method === filterPaymentMethod.value : true;
    const matchesStatus = filterStatus.value ? order.status === filterStatus.value : true;
    
    let matchesDate = true;
    if (startDate.value) {
      matchesDate = new Date(order.created_at) >= new Date(startDate.value);
    }
    if (matchesDate && endDate.value) {
      matchesDate = new Date(order.created_at) <= new Date(endDate.value);
    }
    
    return matchesSearch && matchesPayment && matchesStatus && matchesDate;
  });
});

// Computed: Ordenar les comandes
const sortedOrders = computed(() => {
  return [...filteredOrders.value].sort((a, b) => {
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

// Computed: Paginació
const totalPages = computed(() => {
  return Math.ceil(sortedOrders.value.length / itemsPerPage.value) || 1;
});

const paginatedOrders = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  return sortedOrders.value.slice(start, start + itemsPerPage.value);
});

// Funcions de paginació
function prevPage() {
  if (currentPage.value > 1) currentPage.value--;
}

function nextPage() {
  if (currentPage.value < totalPages.value) currentPage.value++;
}

// Funció per assignar classes als badges
function badgeClass(status: string): string {
  switch (status) {
    case 'pending': return 'badge-pending';
    case 'paid': return 'badge-paid';
    case 'cancelled': return 'badge-cancelled';
    default: return '';
  }
}

// Carrega l'historial de comandes
async function loadOrders() {
  loadingOrders.value = true;
  try {
    const token = localStorage.getItem('userToken');
    const response = await axios.get(`${API_URL}/orders`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    orders.value = response.data;
  } catch (error) {
    console.error('Error carregant l\'historial de comandes:', error);
    errorMessage.value = 'Error carregant l\'historial de comandes. Si us plau, intenta-ho més tard.';
  } finally {
    loadingOrders.value = false;
  }
}

onMounted(() => {
  loadOrders();
});

// Accions addicionals
function viewDetails(orderId: number) {
  router.push(`/order-confirmation/${orderId}`);
}

function reorder(orderId: number) {
  alert(`Recomanant els productes de la comanda ${orderId}`);
}
</script>

<style scoped>
/* Estructura bàsica */
.profile-page {
  display: flex;
  max-width: 1200px;
  margin: 40px auto;
  border: 1px solid #ddd;
  border-radius: 8px;
  background-color: #fff;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
}

/* Menú lateral */
.sidebar {
  width: 250px;
  border-right: 1px solid #ddd;
  padding: 20px;
  background-color: #f8f8f8;
}

.sidebar ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.sidebar li {
  padding: 10px;
  margin-bottom: 10px;
  cursor: pointer;
  border-radius: 4px;
  transition: background-color 0.3s ease;
}

.sidebar li.active,
.sidebar li:hover {
  background-color: #28a745;
  color: #fff;
}

/* Contingut principal */
.content {
  flex: 1;
  padding: 20px;
}

/* Filtres */
.filters {
  margin-bottom: 20px;
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  align-items: center;
}

.filter-input,
.filter-select,
.filter-date {
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

/* Taula d'ordres */
.orders-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 10px;
}

.orders-table th,
.orders-table td {
  border: 1px solid #ddd;
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

/* Botons d'acció dins la taula */
.action-btn {
  padding: 6px 10px;
  margin: 2px;
  border: none;
  border-radius: 4px;
  background-color: #007bff;
  color: #fff;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.action-btn:hover {
  background-color: #0056b3;
}

/* Vista mòbil: Mode targeta per comandes */
.mobile-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.order-card {
  display: flex;
  flex-wrap: wrap;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 15px;
  background-color: #f9f9f9;
  cursor: pointer;
  transition: box-shadow 0.3s ease;
}

.order-card:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

/* Divisió de columnes dins la targeta */
.card-left, .card-right {
  flex: 1;
  min-width: 200px;
  margin: 5px;
}

.card-left p {
  margin: 5px 0;
}

.card-right {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
}

.product-card {
  background-color: #e9ecef;
  border-radius: 4px;
  padding: 5px 8px;
  font-size: 0.85em;
}

/* Paginació */
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
  color: #fff;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.pagination button:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}

/* Missatges d'error */
.error-message {
  color: red;
  font-weight: bold;
  margin-top: 20px;
}

/* Spinner */
.spinner {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-top: 20px;
}

.loader {
  border: 6px solid #f3f3f3;
  border-top: 6px solid #28a745;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin-bottom: 10px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Badges */
.badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.9em;
  color: #fff;
}

.badge-pending {
  background-color: #ffc107;
}

.badge-paid {
  background-color: #28a745;
}

.badge-cancelled {
  background-color: #dc3545;
}

/* Responsive */
@media (max-width: 768px) {
  .profile-page {
    flex-direction: column;
  }
  .sidebar {
    width: 100%;
    border-right: none;
    border-bottom: 1px solid #ddd;
  }
  .content {
    padding: 10px;
  }
}
</style>
