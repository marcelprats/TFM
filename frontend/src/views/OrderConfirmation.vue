<template>
  <div>
    <!-- Contingut del tiquet amb estil retro -->
    <div ref="ticket" class="order-confirmation retro-ticket">
      <h1>Confirmació de la Comanda</h1>
      <div v-if="errorMessage" class="error-message">
        {{ errorMessage }}
      </div>
      <div v-else-if="order">
        <div class="ticket-header">
          <p><strong>Codi:</strong> {{ order.order_number }}</p>
          <p><strong>Data:</strong> {{ new Date(order.created_at).toLocaleString() }}</p>
        </div>
        <div class="ticket-body">
          <p><strong>Total:</strong> {{ formatPrice(order.total_amount) }}</p>
          <p><strong>Mètode:</strong> {{ order.payment_method }}</p>
          <p><strong>Estat:</strong> {{ order.status }}</p>
          <p class="payment-info">
            Has pagat el 10% online ({{ formatPrice(order.total_amount * 0.1) }}) i el restant s'ha de pagar a la botiga:
            <!-- Enllaç a la pàgina de la botiga -->
            <router-link
              :to="`/info-botiga/${order.reserve.botiga.id}`"
              class="shop-link"
            >
              {{ order.reserve.botiga.nom }}
            </router-link>
          </p>
        </div>
        <div class="ticket-products">
          <h2>Productes</h2>
          <ul v-if="order.reserve && order.reserve.reserve_items && order.reserve.reserve_items.length">
            <li v-for="(item, i) in order.reserve.reserve_items" :key="i">
              {{ item.product.nom }} <span v-if="item.quantity"> x{{ item.quantity }}</span>
            </li>
          </ul>
          <p v-else>No s'han trobat productes associats.</p>
        </div>
        <!-- Secció QR: codifica el número de comanda -->
        <div class="ticket-qr">
          <qrcode-vue :value="order.order_number" :size="200" />
          <p class="qr-caption">Presenta aquest tiquet a la botiga per a la recollida</p>
        </div>
        <p class="ticket-note">Gràcies per la seva compra!</p>
      </div>
      <div v-else>
        <p>Carregant informació de la comanda...</p>
      </div>
    </div>
    
    <!-- Botó per descarregar el tiquet -->
    <button @click="downloadTicket" class="download-btn">Descarregar Tiquet</button>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRoute } from 'vue-router';
import jsPDF from 'jspdf';
import html2canvas from 'html2canvas';
import QrcodeVue from 'qrcode.vue';

const route = useRoute();
const order = ref<any>(null);
const errorMessage = ref('');
const API_URL = 'http://127.0.0.1:8000/api';

function formatPrice(price: number | string): string {
  const p = typeof price === 'number' ? price : parseFloat(price);
  if (isNaN(p)) return 'No disponible';
  return p.toFixed(2) + ' €';
}

async function loadOrder() {
  try {
    const token = localStorage.getItem('userToken');
    const response = await axios.get(`${API_URL}/orders/${route.params.id}`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    order.value = response.data;
  } catch (error) {
    if (error.response && error.response.status === 403) {
      errorMessage.value = 'Accés no autoritzat: aquesta comanda no pertany a vostè.';
    } else {
      errorMessage.value = 'Error carregant la comanda. Si us plau, intenta-ho més tard.';
    }
  }
}

onMounted(loadOrder);

async function downloadTicket() {
  try {
    const ticketElement = document.querySelector<HTMLElement>('.retro-ticket');
    if (!ticketElement) return;
    const canvas = await html2canvas(ticketElement, { scale: 2 });
    const imgData = canvas.toDataURL('image/png');
    const pdf = new jsPDF({
      orientation: 'portrait',
      unit: 'px',
      format: [canvas.width, canvas.height]
    });
    pdf.addImage(imgData, 'PNG', 0, 0, canvas.width, canvas.height);
    pdf.save(`${order.value.order_number}.pdf`);
  } catch (err) {
    console.error('Error descarregant el tiquet:', err);
  }
}
</script>

<style scoped>
.retro-ticket {
  max-width: 600px;
  margin: 20px auto;
  padding: 20px;
  background-color: #fff;
  border: 3px dashed #333;
  border-radius: 8px;
  font-family: 'Courier New', Courier, monospace;
  text-align: center;
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.retro-ticket h1 {
  font-size: 28px;
  margin-bottom: 10px;
  color: #333;
  text-transform: uppercase;
}

.ticket-header {
  border-bottom: 2px dashed #333;
  padding-bottom: 10px;
  margin-bottom: 10px;
}

.ticket-header p,
.ticket-body p,
.ticket-products p,
.ticket-products li {
  margin: 8px 0;
  font-size: 16px;
  color: #333;
}

.ticket-body {
  margin-bottom: 10px;
}

.payment-info {
  margin-top: 10px;
  font-weight: bold;
}

.shop-link {
  text-transform: uppercase;
  color: #007bff;
  text-decoration: none;
}

.shop-link:hover {
  text-decoration: underline;
}

.ticket-products {
  border-top: 2px dashed #333;
  padding-top: 10px;
  margin-top: 10px;
  text-align: left;
}

.ticket-products ul {
  list-style: none;
  padding: 0;
}

.ticket-products li {
  padding: 4px 0;
}

.ticket-qr {
  margin-top: 20px;
  text-align: center;
}

.qr-caption {
  font-size: 14px;
  margin-top: 5px;
  color: #333;
}

.ticket-note {
  margin-top: 20px;
  font-size: 14px;
  font-style: italic;
}

.download-btn {
  display: block;
  margin: 30px auto;
  padding: 10px 20px;
  background-color: #28a745;
  border: none;
  border-radius: 4px;
  color: #fff;
  font-size: 16px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.download-btn:hover {
  background-color: #218838;
}

.error-message {
  color: red;
  font-weight: bold;
  margin-top: 20px;
}
</style>
