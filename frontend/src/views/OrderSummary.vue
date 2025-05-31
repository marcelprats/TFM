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

// Modal QR
const showQRModal = ref(false);
const qrCodeDataUrl = ref('');

// Format preus
function formatPrice(price: number | string): string {
  const p = typeof price === 'number' ? price : parseFloat(price);
  return isNaN(p) ? 'No disponible' : p.toFixed(2) + ' €';
}

async function loadOrder() {
  try {
    const response = await axios.get(`/orders/${route.params.id}`);
    order.value = response.data;
  } catch (err: any) {
    if (err.response?.status === 403) {
      errorMessage.value = 'Accés no autoritzat: aquesta comanda no pertany a vostè.';
    } else {
      errorMessage.value = 'Error carregant la comanda. Si us plau, intenta-ho més tard.';
    }
  }
}

onMounted(loadOrder);

async function downloadSummary() {
  try {
    const el = document.querySelector<HTMLElement>('.order-summary-container');
    if (!el) return;
    const canvas = await html2canvas(el, { scale: 2 });
    const img = canvas.toDataURL('image/png');
    const pdf = new jsPDF({ orientation: 'portrait', unit: 'px', format: [canvas.width, canvas.height] });
    pdf.addImage(img, 'PNG', 0, 0, canvas.width, canvas.height);
    pdf.save(`${order.value.order_number}-resum.pdf`);
  } catch (e) {
    console.error('Error descarregant el resum:', e);
  }
}

async function generateQRCode() {
  if (!order.value?.order_number) return;
  try {
    qrCodeDataUrl.value = await QRCode.toDataURL(order.value.order_number, { width: 300 });
  } catch (e) {
    console.error('Error generant el QR:', e);
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
  padding: 28px 18px 22px 18px;
  background-color: #fafbfc;
  border-radius: 16px;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  color: #333;
  box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

h1 {
  text-align: center;
  margin-bottom: 24px;
  color: #212c3a;
  font-size: 2rem;
  font-weight: 700;
}

.summary-card {
  background-color: #fff;
  border: 1px solid #e0e0e0;
  border-radius: 14px;
  padding: 24px;
  box-shadow: 0 1px 6px rgba(0,0,0,0.06);
}

.summary-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #ececec;
  padding-bottom: 10px;
  margin-bottom: 14px;
}

.summary-header .header-item {
  display: flex;
  align-items: center;
  gap: 10px;
}

.summary-header .label {
  font-weight: 600;
  font-size: 15px;
  color: #666;
}

.summary-header .value {
  font-weight: 500;
  font-size: 15px;
  color: #212c3a;
}

.qr-btn {
  background-color: #42b983;
  color: #fff;
  border: none;
  padding: 5px 14px;
  border-radius: 5px;
  font-size: 0.97rem;
  margin-left: 12px;
  cursor: pointer;
  font-weight: 600;
  transition: background-color 0.18s;
}
.qr-btn:hover {
  background-color: #368c6e;
}

.summary-body {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  margin-bottom: 18px;
  gap: 12px;
}

.summary-body .info {
  flex: 1 1 42%;
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

.payment-message {
  width: 100%;
  margin-top: 10px;
  font-weight: 600;
  font-size: 15px;
  color: #007bff;
  text-align: center;
  background: #eaf9f2;
  border-radius: 6px;
  padding: 7px 0;
}

.summary-products {
  margin-top: 18px;
}

.summary-products h2 {
  margin-bottom: 10px;
  font-size: 1.18rem;
  color: #444;
  text-align: left;
  font-weight: 600;
}

.summary-products table {
  width: 100%;
  border-collapse: collapse;
  background: #fff;
  border-radius: 6px;
  overflow: hidden;
  box-shadow: 0 0.5px 2px rgba(0,0,0,0.04);
}

.summary-products th,
.summary-products td {
  border: 1px solid #e0e0e0;
  padding: 10px 7px;
  text-align: center;
  font-size: 15px;
}

.summary-products th {
  background-color: #f0f0f0;
  font-weight: 700;
}

.product-link {
  color: #007bff;
  text-decoration: none;
  font-weight: 500;
}
.product-link:hover { text-decoration: underline; }

.summary-actions {
  display: flex;
  justify-content: space-between;
  margin-top: 24px;
  flex-wrap: wrap;
  gap: 10px;
}

.summary-actions .btn {
  padding: 10px 20px;
  font-size: 1rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  margin: 4px 0;
  text-decoration: none;
  text-align: center;
  font-weight: 600;
  transition: background 0.18s;
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
  color: #42b983;
  text-decoration: none;
  font-weight: 500;
}
.shop-link:hover { text-decoration: underline; }

.error-message {
  color: #e53935;
  font-weight: bold;
  text-align: center;
  margin-bottom: 20px;
}

.modal-overlay {
  position: fixed;
  top: 0; left: 0;
  width: 100vw; height: 100vh;
  background-color: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.modal-content.qr-modal {
  background: #fff;
  padding: 24px 18px 15px 18px;
  border-radius: 10px;
  max-width: 380px;
  width: 92vw;
  text-align: center;
  box-shadow: 0 2px 14px rgba(0,0,0,0.06);
}
.qr-code-image {
  max-width: 100%;
  height: auto;
  margin: 12px 0 16px 0;
}
.close-modal-btn {
  background-color: #e53935;
  color: #fff;
  font-weight: 600;
  border: none;
  border-radius: 5px;
  padding: 9px 20px;
  cursor: pointer;
  margin-top: 9px;
}
.close-modal-btn:hover {
  background-color: #c62828;
}

/* Responsive */
@media (max-width: 768px) {
  .order-summary-container {
    padding: 10px 2vw;
    max-width: 99vw;
  }
  .summary-card {
    padding: 10px 4vw;
  }
  .summary-header {
    flex-direction: column;
    gap: 9px;
    align-items: flex-start;
  }
  .summary-body {
    flex-direction: column;
    gap: 0;
  }
  .summary-products th,
  .summary-products td {
    padding: 7px 2px;
    font-size: 14px;
  }
  .modal-content.qr-modal {
    max-width: 98vw;
    padding: 12px 2vw 8px 2vw;
  }
}
</style>
