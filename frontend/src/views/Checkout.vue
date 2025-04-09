<template>
  <div class="checkout-container">
    <h1>Finalitzar Comanda</h1>
    
    <!-- Resum de la comanda -->
    <div class="financial-summary">
      <p><strong>Total Reservat:</strong> {{ formatPrice(totalReserved) }}</p>
      <p><strong>Deposit a pagar online (10%):</strong> {{ formatPrice(depositAmount) }}</p>
      <p><strong>Resta a Pagar al Local:</strong> {{ formatPrice(remainder) }}</p>
    </div>

    <!-- Resum dels items del carret -->
    <div v-if="cart && cart.cart_items && cart.cart_items.length > 0" class="order-summary">
      <h2>Resum de la Comanda</h2>
      <table class="summary-table">
        <thead>
          <tr>
            <th>Producte</th>
            <th>Preu Unitar</th>
            <th>Quantitat</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in cart.cart_items" :key="item.id">
            <td>{{ item.product.nom }}</td>
            <td>{{ formatPrice(item.reserved_price) }}</td>
            <td>{{ item.quantity }}</td>
            <td>{{ formatPrice(item.quantity * parseFloat(item.reserved_price)) }}</td>
          </tr>
        </tbody>
      </table>

      <!-- Barra de progress per visualitzar els percentatges -->
      <div class="progress-container">
        <div class="progress-bar">
          <div class="progress-deposit" :style="{ width: depositPercentage + '%' }"></div>
          <div class="progress-remainder" :style="{ width: remainderPercentage + '%' }"></div>
        </div>
        <div class="progress-labels">
          <span>Deposit: {{ formatPrice(depositAmount) }}</span>
          <span>Resta: {{ formatPrice(remainder) }}</span>
          <span>Total: {{ formatPrice(totalReserved) }}</span>
        </div>
      </div>
      
      <!-- Secció de condicions (la checkbox està deshabilitada per forçar a llegir el modal) -->
      <div class="terms">
        <input
          type="checkbox"
          id="acceptConditions"
          :checked="acceptedConditions"
          :class="{ error: errorMessage }"
          disabled
        />
        <label for="acceptConditions">
          Accepto les <a href="#" @click.prevent="openModal">condicions de reserva</a>
        </label>
        <div v-if="errorMessage" class="error-message">
          {{ errorMessage }}
        </div>
      </div>
      
      <button class="btn checkout-btn" @click="handleCheckout">
        Pagar Deposit i Confirmar Reserva
      </button>
    </div>
    
    <div v-else>
      <p>El teu carret està buit.</p>
    </div>
    
    <!-- Modal de Condicions de Reserva -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content">
        <h2>Condicions de Reserva</h2>
        <div class="modal-body">
          <p>
            Benvolgut client, abans de confirmar la teva reserva, si us plau, llegeix atentament les condicions:
          </p>
          <p>1. El pagament en línia correspon al 10% del total reservat. Aquest import es pagarà a través del sistema en línia.</p>
          <p>2. La resta del pagament haurà de ser efectuat al local en el moment de la recollida o consum dels productes.</p>
          <p>3. La reserva és vàlida durant 48 hores. Després d'aquest període, la reserva es cancel·larà automàticament sense reemborsament.</p>
          <p>4. En cas de cancel·lació, només es reemborsarà el pagament en línia, segons la política de cancel·lació.</p>
          <p>5. Acceptar aquestes condicions implica que has llegit i entès totes les polítiques de reserva i pagament.</p>
        </div>
        <div class="modal-footer">
          <button class="btn modal-accept-btn" @click="acceptConditions">
            Accepto
          </button>
          <button class="btn modal-cancel-btn" @click="closeModal">
            Cancel·lar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const API_URL = 'http://127.0.0.1:8000/api';

// Variables reactives
const cart = ref<any>(null);
const reserve = ref({
  id: null,
  total_reserved: 0,
  deposit_amount: 0,
  status: 'pending',
});

// Càlculs basats en el total de la reserva
const totalReserved = computed(() => reserve.value.total_reserved);
const depositAmount = computed(() => +(totalReserved.value * 0.1).toFixed(2));
const remainder = computed(() => totalReserved.value - depositAmount.value);
const depositPercentage = computed(() =>
  totalReserved.value > 0 ? (depositAmount.value / totalReserved.value) * 100 : 0
);
const remainderPercentage = computed(() => 100 - depositPercentage.value);

// Variables per gestionar les condicions
const acceptedConditions = ref(false);
const showModal = ref(false);
const errorMessage = ref('');

// Funció per formatar preus
function formatPrice(price: number | string): string {
  const p = typeof price === 'number' ? price : parseFloat(price);
  if (isNaN(p)) return 'No disponible';
  return p.toFixed(2) + ' €';
}

// Carrega el carret des del backend
async function loadCart() {
  try {
    const token = localStorage.getItem('userToken');
    const response = await axios.get(`${API_URL}/cart`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    const cartData = response.data;
    if (cartData) {
      cart.value = cartData;
      // Calcula el total de la comanda a partir del camp total_price del carret
      reserve.value.total_reserved = parseFloat(cartData.total_price) || 0;
      reserve.value.deposit_amount = +(reserve.value.total_reserved * 0.1).toFixed(2);
    }
  } catch (error) {
    console.error('Error carregant el carret:', error);
  }
}

onMounted(loadCart);

// Funció de checkout amb validació d'inventari
async function handleCheckout() {
  // Primer, validem que cada ítem del carret no excedeixi el stock disponible
  for (const item of cart.value.cart_items) {
    if (item.quantity > item.product.stock) {
      alert(
        `No hi ha prou stock per al producte ${item.product.nom}. Disponibil: ${item.product.stock}.`
      );
      return;
    }
  }

  if (!acceptedConditions.value) {
    errorMessage.value = 'Has d’acceptar les condicions de reserva per poder continuar.';
    return;
  }
  errorMessage.value = '';

  try {
    const token = localStorage.getItem('userToken');
    
    // 1. Crear la reserva via POST a /api/reserves
    const reserveResponse = await axios.post(
      `${API_URL}/reserves`,
      {
        // Obté vendor_id i botiga_id a partir del primer ítem del carret.
        vendor_id: cart.value.cart_items[0].product.vendor_id, 
        botiga_id: cart.value.botiga_id || cart.value.cart_items[0].product.botiga_id,
        total_price: totalReserved.value,
        reservation_fee: depositAmount.value,
        status: 'pending',
      },
      { headers: { Authorization: `Bearer ${token}` } }
    );
    
    const createdReserve = reserveResponse.data;
    // Actualitza l'objecte reserva amb la resposta
    reserve.value = {
      id: createdReserve.id,
      total_reserved: createdReserve.total_reserved,
      deposit_amount: createdReserve.deposit_amount,
      status: createdReserve.status,
    };

    // 2. Crear la comanda passant l'id de la reserva
    const orderResponse = await axios.post(
      `${API_URL}/orders`,
      {
        reserve_id: reserve.value.id,
        total_amount: reserve.value.total_reserved,
        payment_method: 'online',
        transaction_id: 'fake-transaction-id',
        status: 'pending',
      },
      { headers: { Authorization: `Bearer ${token}` } }
    );
    const order = orderResponse.data.order;
    alert(orderResponse.data.message);

    // 3. Actualitzar l'inventari per a cada producte del carret
    for (const item of cart.value.cart_items) {
      const newStock = item.product.stock - item.quantity;
      await axios.patch(
        `${API_URL}/productes/${item.product.id}`,
        { stock: newStock },
        { headers: { Authorization: `Bearer ${token}` } }
      );
    }

    // 4. Eliminar el carret
    await axios.delete(`${API_URL}/cart`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    cart.value = null;
    
    // Redirigeix a la pàgina de confirmació de la comanda
    router.push(`/order-confirmation/${order.id}`);
  } catch (error) {
    console.error('Error finalitzant la comanda:', error);
    alert('Error finalitzant la comanda. Si us plau, intenta-ho més tard.');
  }
}

// Funcions per gestionar el modal de condicions
function openModal() {
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
}

function acceptConditions() {
  acceptedConditions.value = true;
  errorMessage.value = '';
  closeModal();
}
</script>

<style scoped>
.checkout-container {
  max-width: 600px;
  margin: 30px auto;
  padding: 20px;
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  text-align: center;
}

.order-summary {
  margin-bottom: 20px;
}

.summary-table {
  width: 100%;
  border-collapse: collapse;
  margin: 10px 0;
}

.summary-table th,
.summary-table td {
  border: 1px solid #ddd;
  padding: 8px;
  text-align: center;
}

.financial-summary {
  margin: 10px 0;
  font-size: 16px;
}

.progress-container {
  margin: 20px 0;
}

.progress-bar {
  display: flex;
  height: 20px;
  width: 100%;
  background-color: #eee;
  border-radius: 10px;
  overflow: hidden;
}

.progress-deposit {
  background-color: #28a745;
  height: 100%;
  transition: width 0.3s ease;
}

.progress-remainder {
  background-color: #ffc107;
  height: 100%;
  transition: width 0.3s ease;
}

.progress-labels {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
  margin-top: 5px;
}

.terms {
  margin: 20px 0;
  text-align: left;
  position: relative;
}

.terms input[type="checkbox"].error {
  outline: 2px solid red;
}

.error-message {
  color: red;
  font-size: 14px;
  margin-top: 5px;
  text-align: left;
}

.btn.checkout-btn {
  background-color: #28a745;
  color: #fff;
  padding: 12px 20px;
  font-size: 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.2s ease;
  margin-top: 10px;
}

.btn.checkout-btn:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}

.btn.checkout-btn:hover:enabled {
  background-color: #218838;
}

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

.modal-content {
  background: #fff;
  padding: 20px;
  border-radius: 8px;
  max-width: 600px;
  width: 90%;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
}

.modal-content h2 {
  margin-top: 0;
  color: #333;
}

.modal-body {
  flex: 1;
  overflow-y: auto;
  margin: 20px 0;
  text-align: left;
  border-top: 1px solid #ddd;
  border-bottom: 1px solid #ddd;
  padding: 10px;
  color: #555;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.btn.modal-accept-btn {
  background-color: #28a745;
  color: #fff;
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.2s ease;
}

.btn.modal-accept-btn:hover {
  background-color: #218838;
}

.btn.modal-cancel-btn {
  background-color: #d9534f;
  color: #fff;
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.2s ease;
}

.btn.modal-cancel-btn:hover {
  background-color: #c9302c;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .checkout-container {
    padding: 15px;
  }
  .financial-summary,
  .progress-labels {
    font-size: 14px;
  }
  .summary-table th,
  .summary-table td {
    padding: 6px;
  }
}
</style>
