<template>
  <div class="checkout-container">
    <h1>Finalitzar Comanda</h1>

    <!-- Botons globals: buidar tots -->
    <div class="global-actions" v-if="hasSelectedItems">
      <button type="button" class="clear-all-btn" @click="clearAllItems">
        Buidar carro
      </button>
    </div>

    <!-- Resum financer global -->
    <div class="financial-summary">
      <p><strong>Total Reservat:</strong> {{ formatPrice(totalReserved) }}</p>
      <p><strong>Deposit a pagar online (10%):</strong> {{ formatPrice(depositAmount) }}</p>
      <p><strong>Resta a Pagar al Local:</strong> {{ formatPrice(remainder) }}</p>
    </div>

    <!-- Resum dels ítems seleccionats agrupats per botiga -->
    <div v-if="hasSelectedItems" class="order-summary">
      <h2>Resum de la Comanda</h2>
      <div
        v-for="(items, shopId) in groupedSelectedItems"
        :key="shopId"
        class="shop-group"
      >
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
              Total per botiga:
              <strong>{{ formatPrice(calcSelectedShopTotal(items)) }}</strong>
            </span>
            <span class="shop-deposit">
              Deposit:
              <strong>{{ formatPrice(calcSelectedShopTotal(items) * 0.1) }}</strong>
            </span>
          </div>
          <button
            type="button"
            class="clear-group-btn"
            @click="clearGroupItems(shopId)"
          >
            Buidar Botiga
          </button>
        </div>
        <table class="summary-table">
          <thead>
            <tr>
              <th>Producte</th>
              <th>Preu Unitar</th>
              <th>Quantitat</th>
              <th>Total</th>
              <th>Acció</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items" :key="item.id">
              <td @click="goToProduct(item.product.id)" style="cursor: pointer;">
                {{ item.product.nom }}
              </td>
              <td>{{ formatPrice(item.reserved_price) }}</td>
              <td>{{ item.quantity }}</td>
              <td>{{ formatPrice(item.quantity * parseFloat(item.reserved_price)) }}</td>
              <td>
                <button
                  type="button"
                  class="trash-btn"
                  @click="removeItem(item.id)"
                >
                  🗑️
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div v-else>
      <p>No hi ha ítems seleccionats per fer la comanda.</p>
    </div>

    <!-- Barra de progress -->
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

    <!-- Condicions -->
    <div class="terms">
      <input type="checkbox" id="acceptConditions" v-model="acceptedConditions" />
      <label for="acceptConditions">
        Accepto les
        <a href="#" @click.prevent="openConditionsModal">condicions de reserva</a>
        <span class="obligatory-msg">* (Obligatori)</span>
      </label>
      <div v-if="errorMessage" class="error-message">
        {{ errorMessage }}
      </div>
    </div>

    <button type="button" class="btn checkout-btn" @click="handleCheckout">
      Pagar Deposit i Confirmar Reserva
    </button>

    <!-- Modal stock -->
    <div v-if="showStockModal" class="modal-overlay" @click.self="closeStockModal">
      <div class="modal-content">
        <h2>Avis de Stock</h2>
        <div class="modal-body">
          <ul>
            <li v-for="issue in stockIssues" :key="issue.productId">
              <template v-if="issue.available > 0">
                El producte <strong>{{ issue.productName }}</strong> només té
                <strong>{{ issue.available }}</strong> unitats disponibles. La
                quantitat s'ha ajustat.
              </template>
              <template v-else>
                El producte <strong>{{ issue.productName }}</strong> ja no està
                en stock i s'ha eliminat del carro.
              </template>
            </li>
          </ul>
        </div>
        <div class="modal-footer">
          <button class="btn modal-close-btn" @click="closeStockModal">
            Tancar
          </button>
        </div>
      </div>
    </div>

    <!-- Modal condicions -->
    <div
      v-if="showConditionsModal"
      class="modal-overlay"
      @click.self="closeConditionsModal"
    >
      <div class="modal-content">
        <h2>Condicions de Reserva</h2>
        <div class="modal-body">
          <p>Benvolgut client, abans de confirmar la teva reserva, si us plau, llegeix atentament les condicions:</p>
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
import { ref, computed, onMounted } from "vue";
import axios from "axios";
import { useRouter } from "vue-router";
import { useCartStore } from "../stores/cartStore";

const router = useRouter();
const cartStore = useCartStore();
const API_URL = import.meta.env.VITE_API_URL || "http://127.0.0.1:8000/api";

// Reactive state
const cart = ref<any[]>([]);
const buyerType = ref("user");
const acceptedConditions = ref(false);
const showConditionsModal = ref(false);
const showStockModal = ref(false);
const errorMessage = ref("");
const stockIssues = ref<any[]>([]);
const stockMessage = ref("");

// On mount, fetch store cart and sync
onMounted(async () => {
  buyerType.value = localStorage.getItem("user_role") || "user";
  await cartStore.fetchCart();
  loadCart();
});

// Only selected items
function loadCart() {
  cart.value = cartStore.items.filter((i) => i.selected);
}

// Helpers
const formatPrice = (price: number | string) => {
  const p = typeof price === "number" ? price : parseFloat(price);
  return isNaN(p) ? "No disponible" : p.toFixed(2) + " €";
};

// Computed sums based only on selectedItems
const selectedItems = computed(() => cart.value);

const totalReserved = computed(() =>
  selectedItems.value.reduce(
    (sum: number, item: any) =>
      sum + item.quantity * parseFloat(item.reserved_price),
    0
  )
);

const depositAmount = computed(() =>
  +(totalReserved.value * 0.1).toFixed(2)
);

const remainder = computed(() =>
  +(totalReserved.value - depositAmount.value).toFixed(2)
);

const depositPercentage = computed(() =>
  totalReserved.value ? (depositAmount.value / totalReserved.value) * 100 : 0
);

const remainderPercentage = computed(() => 100 - depositPercentage.value);

const groupedSelectedItems = computed(() =>
  selectedItems.value.reduce((groups: Record<string, any[]>, item) => {
    const shopId = item.product.botiga?.id ?? "sense_botiga";
    (groups[shopId] = groups[shopId] || []).push(item);
    return groups;
  }, {})
);

const hasSelectedItems = computed(() => selectedItems.value.length > 0);

const getStoreName = (shopId: string) =>
  shopId === "sense_botiga"
    ? "Sense Botiga"
    : groupedSelectedItems.value[shopId][0]?.product.botiga.nom ||
      "No definida";

const calcSelectedShopTotal = (items: any[]) =>
  items.reduce((sum, i) => sum + i.quantity * i.reserved_price, 0);

// Global actions
async function clearAllItems() {
  await axios.delete(`${API_URL}/cart`, {
    headers: { Authorization: `Bearer ${localStorage.getItem("userToken")}` },
  });
  await cartStore.fetchCart();
  loadCart();
}

async function clearGroupItems(shopId: string) {
  const items = groupedSelectedItems.value[shopId] || [];
  await Promise.all(
    items.map((i) =>
      axios.delete(`${API_URL}/cart/${i.id}`, {
        headers: {
          Authorization: `Bearer ${localStorage.getItem("userToken")}`,
        },
      })
    )
  );
  await cartStore.fetchCart();
  loadCart();
}

async function removeItem(itemId: number) {
  await axios.delete(`${API_URL}/cart/${itemId}`, {
    headers: { Authorization: `Bearer ${localStorage.getItem("userToken")}` },
  });
  await cartStore.fetchCart();
  loadCart();
}

// Modals
function openConditionsModal() {
  showConditionsModal.value = true;
}
function closeConditionsModal() {
  showConditionsModal.value = false;
}
function acceptConditions() {
  acceptedConditions.value = true;
  errorMessage.value = "";
  closeConditionsModal();
}

function closeStockModal() {
  showStockModal.value = false;
  stockIssues.value = [];
}

// Stock check before purchase
async function checkStock() {
  return axios.put(
    `${API_URL}/cart/check-stock`,
    { itemIds: selectedItems.value.map((i) => i.id) },
    {
      headers: {
        Authorization: `Bearer ${localStorage.getItem("userToken")}`,
      },
    }
  );
}

// Final checkout
async function handleCheckout() {
  errorMessage.value = "";
  stockMessage.value = "";

  if (!hasSelectedItems.value) {
    alert("No hi ha ítems seleccionats");
    return;
  }
  if (!acceptedConditions.value) {
    errorMessage.value = "Has d’acceptar les condicions de reserva.";
    return;
  }

  // Verify stock
  try {
    await checkStock();
  } catch (err: any) {
    if (err.response?.status === 409) {
      const out = err.response.data.outOfStock;
      stockIssues.value = out;
      for (const issue of out) {
        const item = selectedItems.value.find(
          (i) => i.product.id === issue.productId
        );
        if (!item) continue;
        if (issue.available > 0) {
          // adjust quantity
          await axios.put(
            `${API_URL}/cart/${item.id}`,
            { quantity: issue.available },
            {
              headers: {
                Authorization: `Bearer ${localStorage.getItem("userToken")}`,
              },
            }
          );
        } else {
          await removeItem(item.id);
        }
      }
      await cartStore.fetchCart();
      loadCart();
      stockMessage.value = "Items ajustats segons stock disponible.";
      showStockModal.value = true;
      return;
    }
    errorMessage.value = "Error comprovant stock. Intenta-ho més tard.";
    return;
  }

  // Proceed to checkout
  try {
    const { data } = await axios.post(
      `${API_URL}/cart/checkout`,
      {
        selectedIds: selectedItems.value.map((i) => i.id),
        buyer_type: buyerType.value,
      },
      {
        headers: {
          Authorization: `Bearer ${localStorage.getItem("userToken")}`,
        },
      }
    );
    const { baseOrderNumber, orderIds } = data;
    if (orderIds.length === 1)
      router.push({ name: "OrderConfirmation", params: { id: orderIds[0] } });
    else
      router.push({
        name: "OrdersOverview",
        query: { base: baseOrderNumber },
      });
  } catch {
    errorMessage.value = "Error realitzant la comanda.";
  }
}

// Navigation
function goToProduct(id: number) {
  router.push({ name: "ProductDetail", params: { id } });
}
</script>


<style scoped>
/* (Els teus estils es mantenen sense canvis) */
.checkout-container {
  max-width: 600px;
  margin: 30px auto;
  padding: 20px;
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
  color: #000; /* "Botiga:" en negre */
  font-size: 16px;
}
.shop-header .shop-totals {
  text-align: right;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.shop-header .shop-total,
.shop-header .shop-deposit {
  font-size: 16px;
  color: #333;
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

/* Botons globals */
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

/* Barra de progress */
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
.obligatory-msg {
  font-size: 12px;
  color: red;
  margin-left: 5px;
}

/* Modal de Condicions */
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
  animation: modalFadeIn 0.3s ease;
}
@keyframes modalFadeIn {
  from { opacity: 0; transform: translateY(-20px); }
  to   { opacity: 1; transform: translateY(0); }
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
