<template>
  <div class="orders-section">
    <h1>Historial de Comandes</h1>

    <FiltersBar
      v-model:searchQuery="searchQuery"
      v-model:paymentMethod="filterPaymentMethod"
      v-model:status="filterStatus"
      v-model:startDate="startDate"
      v-model:endDate="endDate"
    />

    <MobileOrdersList
      v-if="isMobile"
      :orders="paginatedOrders"
      @view-summary="viewSummary"
      @view-ticket="viewTicket"
    />
    <DesktopOrdersTable
      v-else
      :orders="paginatedOrders"
      :sortField="sortField"
      :sortDirection="sortDirection"
      @change-sort="changeSort"
      @view-summary="viewSummary"
      @view-ticket="viewTicket"
    />

    <div v-if="loading" class="spinner">
      <div class="loader"></div>
      <p>Carregant comandes...</p>
    </div>
    <p v-else-if="error" class="error">{{ error }}</p>
    <p v-else-if="paginatedOrders.length === 0">
      No hi ha comandes registrades.
    </p>

    <Pagination
      v-if="!loading && paginatedOrders.length"
      :current="currentPage"
      :total="totalPages"
      @prev="prevPage"
      @next="nextPage"
    />
    
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import FiltersBar from './orders/FiltersBar.vue';
import MobileOrdersList from './orders/MobileOrdersList.vue';
import DesktopOrdersTable from './orders/DesktopOrdersTable.vue';
import Pagination from './orders/Pagination.vue';
import axios from 'axios';

const router = useRouter();

const orders = ref<any[]>([]);
const loading = ref(false);
const error = ref('');

const searchQuery = ref('');
const filterPaymentMethod = ref('');
const filterStatus = ref('');
const startDate = ref('');
const endDate = ref('');
const sortField = ref('created_at');
const sortDirection = ref<'asc' | 'desc'>('desc');
const currentPage = ref(1);
const itemsPerPage = ref(5);
const isMobile = ref(window.innerWidth < 768);
window.addEventListener('resize', () => {
  isMobile.value = window.innerWidth < 768;
});

async function loadOrders() {
  loading.value = true;
  try {
    const { data } = await axios.get('/my-orders');
    orders.value = data;
  } catch {
    error.value = 'Error carregant l’historial de comandes.';
  } finally {
    loading.value = false;
  }
}

onMounted(loadOrders);

const filtered = computed(() =>
  orders.value.filter(order => {
    const orderNum = String(order.order_number).toLowerCase();
    const numeric = orderNum.replace(/^ord-/, '');
    const q = searchQuery.value.toLowerCase();
    const bySearch = orderNum.includes(q) || numeric.includes(q);
    const byPay = filterPaymentMethod.value
      ? order.payment_method === filterPaymentMethod.value
      : true;
    const byStatus = filterStatus.value
      ? order.status === filterStatus.value
      : true;
    let byDate = true;
    if (startDate.value) {
      byDate = new Date(order.created_at) >= new Date(startDate.value);
    }
    if (byDate && endDate.value) {
      byDate = new Date(order.created_at) <= new Date(endDate.value);
    }
    return bySearch && byPay && byStatus && byDate;
  })
);

const sorted = computed(() =>
  [...filtered.value].sort((a, b) => {
    let fa: any = a[sortField.value];
    let fb: any = b[sortField.value];
    if (sortField.value === 'total_amount') {
      fa = parseFloat(fa);
      fb = parseFloat(fb);
    }
    if (sortField.value === 'created_at') {
      fa = new Date(fa).getTime();
      fb = new Date(fb).getTime();
    }
    if (fa < fb) return sortDirection.value === 'asc' ? -1 : 1;
    if (fa > fb) return sortDirection.value === 'asc' ? 1 : -1;
    return 0;
  })
);

const totalPages = computed(() =>
  Math.max(1, Math.ceil(sorted.value.length / itemsPerPage.value))
);
const paginatedOrders = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  return sorted.value.slice(start, start + itemsPerPage.value);
});

function changeSort(field: string) {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortField.value = field;
    sortDirection.value = 'asc';
  }
}

function prevPage() {
  if (currentPage.value > 1) currentPage.value--;
}
function nextPage() {
  if (currentPage.value < totalPages.value) currentPage.value++;
}
function viewSummary(id: number) {
  router.push(`/order-summary/${id}`);
}
function viewTicket(id: number) {
  router.push(`/order-confirmation/${id}`);
}
</script>

<style scoped>
.orders-section h1 {
  text-align: center;
  margin-bottom: 20px;
  color: #444;
}
/* Estils específics d’historial de comandes */
</style>
