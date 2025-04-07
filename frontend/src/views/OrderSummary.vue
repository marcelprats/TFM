<template>
  <div class="order-summary-container">
    <h1>Resum de la Comanda</h1>
    <div v-if="errorMessage" class="error-message">
      {{ errorMessage }}
    </div>
    <div v-else-if="order" class="summary-card">
      <div class="summary-header">
        <div class="header-item">
          <p class="label">Codi:</p>
          <p class="value">
            {{ order.order_number }}
            <button class="qr-btn" @click="openQRModal">
              Veure QR
            </button>
          </p>
        </div>
        <div class="header-item">
          <p class="label">Data:</p>
          <p class="value">{{ new Date(order.created_at).toLocaleString() }}</p>
        </div>
      </div>
      <div class="summary-body">
        <div class="info">
          <p class="label">Total:</p>
          <p class="value">{{ formatPrice(order.total_amount) }}</p>
        </div>
        <div class="info">
          <p class="label">Mètode de Pagament:</p>
          <p class="value">{{ order.payment_method }}</p>
        </div>
        <div class="info">
          <p class="label">Estat:</p>
          <p class="value">{{ order.status }}</p>
        </div>
        <div class="info">
          <p class="label">Botiga:</p>
          <p class="value">
            <router-link :to="`/info-botiga/${order.reserve.botiga.id}`" class="shop-link">
              {{ order.reserve.botiga.nom }}
            </router-link>
          </p>
        </div>
        <div class="payment-message">
          <p>
            Has pagat el 10% online ({{ formatPrice(order.total_amount * 0.1) }}) i el restant s'ha de pagar a la botiga.
          </p>
        </div>
      </div>
      <div class="summary-products">
        <h2>Productes</h2>
        <table v-if="order.reserve && order.reserve.reserve_items && order.reserve.reserve_items.length">
          <thead>
            <tr>
              <th>Producte</th>
              <th>Quantitat</th>
              <th>Preu Unitari</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(item, idx) in order.reserve.reserve_items" :key="idx">
              <td>
                <router-link :to="`/producte/${item.product.id}`" class="product-link">
                  {{ item.product.nom }}
                </router-link>
              </td>
              <td>{{ item.quantity }}</td>
              <td>{{ formatPrice(item.reserved_price) }}</td>
              <td>{{ formatPrice(item.quantity * parseFloat(item.reserved_price)) }}</td>
            </tr>
          </tbody>
        </table>
        <div v-else>
          <p>No s'han trobat productes associats.</p>
        </div>
      </div>
      <div class="summary-actions">
        <router-link :to="`/order-confirmation/${order.id}`" class="btn view-ticket-btn">
          Veure Tiquet
        </router-link>
        <button @click="downloadSummary" class="btn download-btn">
          Descarregar Resum
        </button>
      </div>
    </div>
    <div v-else>
      <p>Carregant informació de la comanda...</p>
    </div>

    <!-- Modal QR -->
    <div v-if="showQRModal" class="modal-overlay" @click.self="closeQRModal">
      <div class="modal-content qr-modal">
        <h2>Codi QR de la Comanda</h2>
        <img :src="qrCodeDataUrl" alt="QR Code" class="qr-code-image" />
        <p>Presenta aquest codi a la botiga per fer la recollida</p>
        <button class="btn close-modal-btn" @click="closeQRModal">Tancar</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRoute } from 'vue-router';
import jsPDF from 'jspdf';
import html2canvas from 'html2canvas';
import QRCode from 'qrcode';

const route = useRoute();
const order = ref<any>(null);
const errorMessage = ref('');
const API_URL = 'http://127.0.0.1:8000/api';

// Variables per al modal QR
const showQRModal = ref(false);
const qrCodeDataUrl = ref('');

// Funció per formatar preus
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

async function downloadSummary() {
  try {
    const summaryElement = document.querySelector<HTMLElement>('.order-summary-container');
    if (!summaryElement) return;
    const canvas = await html2canvas(summaryElement, { scale: 2 });
    const imgData = canvas.toDataURL('image/png');
    const pdf = new jsPDF({
      orientation: 'portrait',
      unit: 'px',
      format: [canvas.width, canvas.height],
    });
    pdf.addImage(imgData, 'PNG', 0, 0, canvas.width, canvas.height);
    pdf.save(`${order.value.order_number}-resum.pdf`);
  } catch (err) {
    console.error('Error descarregant el resum:', err);
  }
}

async function generateQRCode() {
  if (order.value && order.value.order_number) {
    try {
      // Genera el QR amb una mida més gran
      qrCodeDataUrl.value = await QRCode.toDataURL(order.value.order_number, { width: 300 });
    } catch (err) {
      console.error('Error generant el QR:', err);
    }
  }
}

function openQRModal() {
  generateQRCode();
  showQRModal.value = true;
}

function closeQRModal() {
  showQRModal.value = false;
}
</script>

<style scoped>
.order-summary-container {
  max-width: 800px;
  margin: 40px auto;
  padding: 20px;
  background-color: #f7f7f7;
  border-radius: 8px;
  font-family: 'Roboto', sans-serif;
  color: #333;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

h1 {
  text-align: center;
  margin-bottom: 20px;
  color: #444;
}

.summary-card {
  background-color: #fff;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 20px;
}

.summary-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #ddd;
  padding-bottom: 10px;
  margin-bottom: 10px;
}

.summary-header .header-item {
  display: flex;
  align-items: center;
  gap: 10px;
}

.summary-header .label {
  font-weight: 600;
  font-size: 14px;
  color: #666;
}

.summary-header .value {
  font-weight: 500;
  font-size: 14px;
  color: #333;
}

.summary-body {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  margin-bottom: 20px;
}

.summary-body .info {
  flex: 1 1 45%;
  margin-bottom: 10px;
}

.summary-body .info .label {
  font-weight: 600;
  font-size: 14px;
  color: #666;
}

.summary-body .info .value {
  font-weight: 500;
  font-size: 16px;
  color: #333;
}

/* Canvia el color del missatge de pagament a un to més neutre */
.payment-message {
  width: 100%;
  margin-top: 10px;
  font-weight: 600;
  font-size: 16px;
  color: #333;
  text-align: center;
}

.summary-products {
  margin-top: 20px;
}

.summary-products h2 {
  margin-bottom: 10px;
  font-size: 18px;
  color: #444;
  text-align: left;
}

.summary-products table {
  width: 100%;
  border-collapse: collapse;
}

.summary-products th,
.summary-products td {
  border: 1px solid #ddd;
  padding: 8px;
  text-align: center;
  font-size: 14px;
}

.summary-products th {
  background-color: #f0f0f0;
}

.summary-actions {
  display: flex;
  justify-content: space-between;
  margin-top: 20px;
  flex-wrap: wrap;
}

.summary-actions .btn {
  padding: 10px 20px;
  font-size: 14px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  margin: 5px;
  text-decoration: none;
  text-align: center;
}

.view-ticket-btn {
  background-color: #007bff;
  color: #fff;
}

.view-ticket-btn:hover {
  background-color: #0056b3;
}

.download-btn {
  background-color: #28a745;
  color: #fff;
}

.download-btn:hover {
  background-color: #218838;
}

.shop-link {
  text-transform: uppercase;
  color: #007bff;
  text-decoration: none;
}

.shop-link:hover {
  text-decoration: underline;
}

.error-message {
  color: red;
  font-weight: bold;
  text-align: center;
  margin-bottom: 20px;
}

/* Modal QR */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content.qr-modal {
  background: #fff;
  padding: 20px;
  border-radius: 8px;
  max-width: 400px;
  width: 90%;
  text-align: center;
}

.qr-code-image {
  max-width: 100%;
  height: auto;
  margin-bottom: 10px;
}

.close-modal-btn {
  background-color: #dc3545;
  color: #fff;
}

/* Responsive */
@media (max-width: 768px) {
  .order-summary-container {
    padding: 15px;
  }
}
</style>
