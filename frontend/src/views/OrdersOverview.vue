<template>
  <div class="orders-overview">
    <h1 class="title">Historial de Comandes</h1>
    
    <!-- Missatge informatiu en cas de tenir un número base (checkout recent) -->
    <p v-if="baseOrderNumber" class="info">
      Les ordres mostrades corresponen al checkout amb base: <strong>{{ baseOrderNumber }}</strong>
    </p>
    
    <div v-if="filteredOrders.length === 0" class="no-orders">
      <p>No s'han trobat comandes per al checkout recent.</p>
    </div>
    
    <div v-else class="orders-list">
      <div v-for="order in filteredOrders" :key="order.id" class="order-card">
        <div class="order-header">
          <h2 class="order-number">Comanda {{ order.order_number }}</h2>
          <p class="order-date"><strong>Data:</strong> {{ formatDate(order.created_at) }}</p>
          <p class="order-total"><strong>Total:</strong> {{ formatPrice(order.total_amount) }}</p>
          <p class="order-status"><strong>Estat:</strong> {{ order.status }}</p>
        </div>
        <!-- Si l'ordre té la reserva amb els ítems, es mostren els productes reservats -->
        <div v-if="order.reserve && order.reserve.reserve_items && order.reserve.reserve_items.length" class="order-products">
          <h3>Productes</h3>
          <ul>
            <li v-for="item in order.reserve.reserve_items" :key="item.id">
              {{ item.product.nom }} – Quantitat: {{ item.quantity }} – Preu Unitari: {{ formatPrice(item.reserved_price) }}
            </li>
          </ul>
        </div>
        <!-- Accions per veure el tiquet i el resum de la comanda -->
        <div class="order-actions">
          <router-link :to="`/order-confirmation/${order.id}`" class="btn ticket-btn">
            Veure Tiquet
          </router-link>
          <router-link :to="`/order-summary/${order.id}`" class="btn summary-btn">
            Veure Resum
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useRoute } from 'vue-router';

const API_URL = 'http://127.0.0.1:8000/api';
const orders = ref<any[]>([]);
const route = useRoute();

/** Carrega totes les comandes de l'usuari */
async function loadOrders() {
  try {
    const token = localStorage.getItem("userToken");
    const response = await axios.get(`${API_URL}/orders`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    orders.value = response.data;
  } catch (error) {
    console.error("Error carregant les comandes:", error);
  }
}

onMounted(() => {
  loadOrders();
});

/** Extreiem el número d'ordre base del query parameter (ex: ?base=ORD-0000000001) */
const baseOrderNumber = computed(() => {
  return route.query.base ? route.query.base.toString() : '';
});

/** Si es passa baseOrderNumber, filtrem només les ordres que comencen amb aquest valor.
 *  Si no hi ha aquest valor, es mostren totes.
 */
const filteredOrders = computed(() => {
  if (!baseOrderNumber.value) return orders.value;
  return orders.value.filter(order =>
    order.order_number.startsWith(baseOrderNumber.value)
  );
});

/** Funció per formatar preus */
function formatPrice(price: number | string): string {
  const p = typeof price === "number" ? price : parseFloat(price);
  if (isNaN(p)) return "No disponible";
  return p.toFixed(2) + " €";
}

/** Funció per formatar dates */
function formatDate(dateStr: string): string {
  const d = new Date(dateStr);
  return isNaN(d.getTime()) ? '' : d.toLocaleString();
}
</script>

<style scoped>
.orders-overview {
  max-width: 900px;
  margin: 0 auto;
  padding: 20px;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  text-align: center;
}

.title {
  margin-bottom: 20px;
  font-size: 28px;
  color: #333;
}

.info {
  font-size: 16px;
  color: #555;
  margin-bottom: 15px;
}

.no-orders p {
  font-size: 18px;
  color: #333;
  margin-top: 30px;
}

.orders-list {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.order-card {
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 15px 20px;
  text-align: left;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.order-header {
  margin-bottom: 15px;
}

.order-number {
  font-size: 20px;
  color: #333;
  margin-bottom: 5px;
}

.order-date,
.order-total,
.order-status {
  font-size: 16px;
  color: #666;
  margin: 3px 0;
}

.order-products h3 {
  font-size: 18px;
  margin-bottom: 5px;
  color: #333;
}

.order-products ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.order-products li {
  font-size: 15px;
  color: #555;
  margin-bottom: 3px;
}

.order-actions {
  margin-top: 15px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.btn {
  display: inline-block;
  text-decoration: none;
  padding: 8px 12px;
  border-radius: 4px;
  font-size: 15px;
  color: #fff;
  transition: background 0.2s ease;
}

.ticket-btn {
  background-color: #007bff;
}
.ticket-btn:hover {
  background-color: #0069d9;
}

.summary-btn {
  background-color: #28a745;
}
.summary-btn:hover {
  background-color: #218838;
}
</style>
