<template>
  <div class="checkout-container">
    <h1>Finalitzar Comanda</h1>
    
    <!-- Resum financer global -->
    <div class="financial-summary">
      <p><strong>Total Reservat:</strong> {{ formatPrice(totalReserved) }}</p>
      <p><strong>Deposit a pagar online (10%):</strong> {{ formatPrice(depositAmount) }}</p>
      <p><strong>Resta a Pagar al Local:</strong> {{ formatPrice(remainder) }}</p>
    </div>

    <!-- Resum dels ítems seleccionats agrupats per botiga -->
    <div v-if="hasSelectedItems" class="order-summary">
      <h2>Resum de la Comanda</h2>
      <div v-for="(items, shopId) in groupedSelectedItems" :key="shopId" class="shop-group">
        <!-- Capçalera del grup: mostra el nom (com a enllaç) i els totals: Total i Deposit -->
        <div class="shop-header">
          <span class="shop-name">
            Botiga:
            <template v-if="shopId !== 'sense_botiga'">
              <router-link :to="`/info-botiga/${shopId}`" class="store-link">
                <strong>{{ getStoreName(shopId) }}</strong>
              </router-link>
            </template>
            <template v-else>
              <strong>Sense Botiga</strong>
            </template>
          </span>
          <div class="shop-totals">
            <span class="shop-total">
              Total per botiga: <strong>{{ formatPrice(calcSelectedShopTotal(items)) }}</strong>
            </span>
            <span class="shop-deposit">
              Deposit: <strong>{{ formatPrice(calcSelectedShopTotal(items) * 0.1) }}</strong>
            </span>
          </div>
        </div>
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
            <tr v-for="item in items" :key="item.id">
              <td>{{ item.product.nom }}</td>
              <td>{{ formatPrice(item.reserved_price) }}</td>
              <td>{{ item.quantity }}</td>
              <td>{{ formatPrice(item.quantity * parseFloat(item.reserved_price)) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div v-else>
      <p>No hi ha ítems seleccionats per fer la comanda.</p>
    </div>
    
    <!-- Barra de progress sota les taules -->
    <div class="progress-container" v-if="hasSelectedItems">
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
    
    <!-- Secció de condicions -->
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
          <p>
            <em>Si els productes provenen de diferents botigues, es generarà una ordre per cada botiga.</em>
          </p>
        </div>
        <div class="modal-footer">
          <button class="btn modal-cancel-btn" @click="closeModal">
            Cancel·lar
          </button>
          <button class="btn modal-accept-btn" @click="acceptConditions">
            Accepto
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

/** Variables reactives */
const cart = ref<any>(null);
const reserve = ref({
  id: null,
  total_reserved: 0,
  deposit_amount: 0,
  status: 'pending',
});

/** Variables per gestionar condicions */
const acceptedConditions = ref(false);
const showModal = ref(false);
const errorMessage = ref('');

/** Funció per formatar preus */
const formatPrice = (price: number | string): string => {
  const p = typeof price === 'number' ? price : parseFloat(price);
  if (isNaN(p)) return 'No disponible';
  return p.toFixed(2) + ' €';
};

/** Carrega el carret */
async function loadCart() {
  try {
    const token = localStorage.getItem('userToken');
    const response = await axios.get(`${API_URL}/cart`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    cart.value = response.data;
    if (cart.value && cart.value.cart_items) {
      const selectedTotal = cart.value.cart_items
        .filter((item: any) => item.selected)
        .reduce((sum: number, item: any) => sum + item.quantity * parseFloat(item.reserved_price), 0);
      reserve.value.total_reserved = selectedTotal;
      reserve.value.deposit_amount = +(selectedTotal * 0.1).toFixed(2);
    }
  } catch (error) {
    console.error('Error carregant el carret:', error);
  }
}
onMounted(loadCart);

/** Càlculs globals */
const totalReserved = computed(() => {
  if (!cart.value || !cart.value.cart_items) return 0;
  return cart.value.cart_items
    .filter((item: any) => item.selected)
    .reduce((sum: number, item: any) => sum + item.quantity * parseFloat(item.reserved_price), 0);
});
const depositAmount = computed(() => +(totalReserved.value * 0.1).toFixed(2));
const remainder = computed(() => totalReserved.value - depositAmount.value);
const depositPercentage = computed(() =>
  totalReserved.value > 0 ? (depositAmount.value / totalReserved.value) * 100 : 0
);
const remainderPercentage = computed(() => 100 - depositPercentage.value);

/** Agrupa els ítems seleccionats per botiga */
const groupedSelectedItems = computed(() => {
  if (!cart.value || !cart.value.cart_items) return {};
  const selected = cart.value.cart_items.filter((item: any) => item.selected);
  return selected.reduce((groups: any, item: any) => {
    const shopId = item.product.botiga ? item.product.botiga.id : 'sense_botiga';
    if (!groups[shopId]) groups[shopId] = [];
    groups[shopId].push(item);
    return groups;
  }, {});
});

/** Obté el nom de la botiga */
const getStoreName = (shopId: string): string => {
  if (shopId === 'sense_botiga') return 'Sense Botiga';
  const group = groupedSelectedItems.value[shopId];
  return group && group[0] && group[0].product.botiga
    ? group[0].product.botiga.nom
    : 'No definida';
};

/** Calcula el total per grup, només amb ítems seleccionats */
const calcSelectedShopTotal = (items: any[]): number => {
  return items
    .filter((item: any) => item.selected)
    .reduce((sum, item) => sum + item.quantity * parseFloat(item.reserved_price), 0);
};

/** Computed per saber si hi ha almenys un ítem seleccionat */
const hasSelectedItems = computed(() => {
  return cart.value && cart.value.cart_items && cart.value.cart_items.some((item: any) => item.selected);
});

/** Funcions per gestionar el modal de condicions */
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

async function handleCheckout() {
  const selectedCount = cart.value?.cart_items.filter((item: any) => item.selected).length;
  if (!selectedCount) {
    alert('No hi ha ítems seleccionats per fer checkout.');
    return;
  }
  if (!acceptedConditions.value) {
    errorMessage.value = 'Has d’acceptar les condicions de reserva per poder continuar.';
    return;
  }
  errorMessage.value = '';
  try {
    const token = localStorage.getItem('userToken');
    const checkoutResponse = await axios.post(
      `${API_URL}/cart/checkout`,
      { groups: groupedSelectedItems.value },
      { headers: { Authorization: `Bearer ${token}` } }
    );
    alert(checkoutResponse.data.message);
    // Si només s'ha creat una ordre, redirigeix a la pàgina de confirmació individual.
    if (checkoutResponse.data.orderId) {
      router.push(`/order-confirmation/${checkoutResponse.data.orderId}`);
    } else if (checkoutResponse.data.orders) {
      // Si s'han creat diverses ordres, redirigeix passant el baseOrderNumber com a query parameter:
      router.push({ name: 'OrdersOverview', query: { base: checkoutResponse.data.baseOrderNumber } });
    }
  } catch (error) {
    console.error('Error finalitzant la comanda:', error);
    alert('Error finalitzant la comanda. Si us plau, intenta-ho més tard.');
  }
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
  box-shadow: 0 4px 8px rgba(0,0,0,0.05);
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  text-align: center;
}

.financial-summary {
  margin: 10px 0;
  font-size: 16px;
}

.order-summary {
  margin-bottom: 20px;
}

.shop-group {
  margin-bottom: 30px;
}

.shop-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: #f2f2f2;
  padding: 10px 15px;
  border-radius: 4px;
  margin-bottom: 10px;
}
.shop-header .shop-name {
  color: #000; /* El text "Botiga:" apareix en negre */
  font-size: 16px;
}
.shop-header .shop-totals {
  text-align: right;
}
.shop-header .shop-total,
.shop-header .shop-deposit {
  font-size: 16px;
  color: #333;
}
.shop-header .shop-deposit {
  margin-left: 10px;
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
.summary-table th {
  background-color: #42b983;
  color: #fff;
}

/* Botons globals i per botiga (els botons de buidar i eliminar són en vermell) */
.global-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin: 20px 0;
}
.clear-all-btn,
.clear-group-btn,
.trash-btn {
  background-color: #d9534f;
  color: #fff;
  padding: 8px 14px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.2s ease;
}
.clear-all-btn:hover,
.clear-group-btn:hover,
.trash-btn:hover {
  background-color: #c9302c;
}
.checkout-all-btn,
.checkout-btn {
  background-color: #28a745;
  color: #fff;
  padding: 8px 14px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.2s ease;
}
.checkout-all-btn:hover,
.checkout-btn:hover {
  background-color: #218838;
}

/* Barra de progress sota les taules */
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

/* Secció de condicions */
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
.note {
  font-size: 12px;
  color: #555;
  margin-left: 5px;
}

/* Botó de checkout */
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

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.5);
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
  margin-top: 20px;
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
