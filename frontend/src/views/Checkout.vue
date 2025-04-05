<template>
  <div class="checkout-container">
    <h1>Finalitzar Comanda</h1>
    
    <div v-if="cart && reserve.id" class="order-summary">
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
      
      <div class="financial-summary">
        <p><strong>Total Reservat:</strong> {{ formatPrice(totalReserved) }}</p>
        <p><strong>Deposit (10%):</strong> {{ formatPrice(depositAmount) }}</p>
        <p><strong>Resta a Pagar al Local:</strong> {{ formatPrice(remainder) }}</p>
      </div>
      
      <!-- Barra de progrés -->
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
      
      <div class="checkout-info">
        <p>Pagament en línia: <strong>10% del total (Deposit)</strong></p>
        <p>La resta es pagarà al local.</p>
      </div>
      
      <!-- Secció de condicions -->
      <div class="terms">
        <!-- La checkbox està deshabilitada perquè només es pot marcar des del modal -->
        <input
          type="checkbox"
          id="acceptConditions"
          :checked="acceptedConditions"
          disabled
          :class="{ error: !acceptedConditions && errorMessage }"
        />
        <label for="acceptConditions">
          Accepto les <a href="#" @click.prevent="openModal">condicions de reserva</a>
        </label>
        <div v-if="!acceptedConditions && errorMessage" class="error-message">
          {{ errorMessage }}
        </div>
      </div>
      
      <button class="btn checkout-btn" @click="handleCheckout">
        Pagar Deposit i Confirmar Reserva
      </button>
    </div>
    
    <div v-else>
      <p>Carregant informació del carret...</p>
    </div>
    
    <!-- Modal de Condicions de Reserva -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content">
        <h2>Condicions de Reserva</h2>
        <div class="modal-body">
          <p>
            <strong>Termes i Condicions de Reserva:</strong>
          </p>
          <p>
            1. La reserva dels productes implica un pagament inicial del 10% del valor total reservat.
          </p>
          <p>
            2. El pagament restant (90%) s'ha de efectuar al local, en el moment de la recollida o compra dels productes.
          </p>
          <p>
            3. La reserva tindrà una validesa de 48 hores des de la confirmació, després de la qual es cancelarà automàticament si no es compleix el pagament.
          </p>
          <p>
            4. Els preus reservats són vàlids només per a la reserva realitzada i no inclouen possibles descomptes o ofertes posteriors.
          </p>
          <p>
            5. La reserva és personal i intransferible. En cas de cancel·lació, el deposit no serà retornat.
          </p>
          <p>
            6. Acceptant aquestes condicions, l'usuari confirma que ha llegit, entès i acceptat tots els termes de reserva.
          </p>
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

// Variables per emmagatzemar el carret i calcular la reserva
const cart = ref<any>(null);
const reserve = ref({
  id: null,
  total_reserved: 0,
  deposit_amount: 0,
  status: 'pending',
});

// Càlculs dinàmics
const totalReserved = computed(() => reserve.value.total_reserved);
const depositAmount = computed(() => totalReserved.value > 0 ? +(totalReserved.value * 0.1).toFixed(2) : 0);
const remainder = computed(() => totalReserved.value - depositAmount.value);
const depositPercentage = computed(() => totalReserved.value > 0 ? (depositAmount.value / totalReserved.value) * 100 : 0);
const remainderPercentage = computed(() => totalReserved.value > 0 ? 100 - depositPercentage.value : 0);

// Variables per gestionar condicions
const acceptedConditions = ref(false);
const showModal = ref(false);
const errorMessage = ref('');

// Funció per formatar preus
function formatPrice(price: number | string): string {
  const p = typeof price === 'number' ? price : parseFloat(price);
  if (isNaN(p)) return 'No disponible';
  return p.toFixed(2) + ' €';
}

// Funció per carregar el carret des del backend i actualitzar la reserva
async function loadCart() {
  try {
    const token = localStorage.getItem('userToken');
    const response = await axios.get(`${API_URL}/cart`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    const cartData = response.data;
    if (cartData) {
      cart.value = cartData;
      reserve.value.id = cartData.id;
      reserve.value.total_reserved = parseFloat(cartData.total_price) || 0;
      reserve.value.deposit_amount = +(reserve.value.total_reserved * 0.1).toFixed(2);
    }
  } catch (error) {
    console.error('Error carregant el carret:', error);
  }
}

onMounted(loadCart);

// Funció per finalitzar el checkout
async function handleCheckout() {
  if (!acceptedConditions.value) {
    errorMessage.value = 'Has d’acceptar les condicions de reserva per poder continuar.';
    return;
  }
  errorMessage.value = '';
  try {
    const token = localStorage.getItem('userToken');
    const response = await axios.post(
      `${API_URL}/orders`,
      {
        reserve_id: reserve.value.id,
        total_amount: reserve.value.total_reserved,
        payment_method: 'online',
        transaction_id: 'fake-transaction-id',
        status: 'pending',
      },
      {
        headers: { Authorization: `Bearer ${token}` },
      }
    );
    alert(response.data.message);
    router.push('/order-confirmation');
  } catch (error: any) {
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

.checkout-summary {
  margin-bottom: 20px;
  padding-bottom: 10px;
  border-bottom: 1px solid #eee;
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
  margin: 15px 0;
}

.progress-bar {
  display: flex;
  height: 20px;
  border-radius: 10px;
  overflow: hidden;
  background-color: #eee;
}

.progress-deposit {
  background-color: #28a745;
  height: 100%;
}

.progress-remainder {
  background-color: #ffc107;
  height: 100%;
}

.progress-labels {
  display: flex;
  justify-content: space-between;
  margin-top: 5px;
  font-size: 14px;
  color: #333;
}

.checkout-info {
  margin: 20px 0;
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

/* Estils del modal */
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
</style>
