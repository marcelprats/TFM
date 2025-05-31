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
  await axios.delete(`/cart`);
  await cartStore.fetchCart();
  loadCart();
}

async function clearGroupItems(shopId: string) {
  const items = groupedSelectedItems.value[shopId] || [];
  await Promise.all(
    items.map((i) =>
      axios.delete(`/cart/${i.id}`))
  );
  await cartStore.fetchCart();
  loadCart();
}

async function removeItem(itemId: number) {
  await axios.delete(`/cart/${itemId}`);
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
    `/cart/check-stock`,
    { itemIds: selectedItems.value.map((i) => i.id) },
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
            `/cart/${item.id}`,
            { quantity: issue.available },           
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
      `/cart/checkout`,
      {
        selectedIds: selectedItems.value.map((i) => i.id),
        buyer_type: buyerType.value,
      },
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
.checkout-container {
  max-width: 700px;
  margin: 40px auto;
  padding: 28px 24px 18px 24px;
  background: #fafbfc;
  border-radius: 16px;
  box-shadow: 0 2px 16px rgba(0,0,0,0.07);
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.checkout-container h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #212c3a;
  margin-bottom: 20px;
  text-align: center;
}

.global-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin: 16px 0 14px 0;
}

.clear-all-btn,
.clear-group-btn,
.trash-btn {
  background-color: #e53935;
  color: #fff;
  padding: 8px 14px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 500;
  font-size: 15px;
  transition: background 0.2s;
}
.clear-all-btn:hover,
.clear-group-btn:hover,
.trash-btn:hover {
  background-color: #c62828;
}

.financial-summary {
  margin: 18px 0 18px 0;
  font-size: 17px;
  background: #eaf9f2;
  border-radius: 7px;
  padding: 14px 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
  align-items: center;
}

.order-summary {
  margin-bottom: 26px;
  width: 100%;
}

.order-summary h2 {
  font-size: 1.3rem;
  font-weight: 600;
  color: #2d2d2d;
  margin: 18px 0 12px 0;
  text-align: left;
}

.shop-group {
  margin-bottom: 32px;
  background: #f7f7f9;
  border-radius: 12px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.04);
  padding: 12px 0 20px 0;
}

.shop-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: #f2f2f2;
  padding: 12px 22px;
  border-radius: 7px;
  margin-bottom: 10px;
}
.shop-header .shop-name {
  color: #222;
  font-size: 17px;
}
.shop-header .shop-totals {
  text-align: right;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.shop-header .shop-total,
.shop-header .shop-deposit {
  font-size: 16px;
  color: #333;
  line-height: 1.2;
}

.summary-table {
  width: 96%;
  margin: 0 auto;
  border-collapse: collapse;
  margin-top: 10px;
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 0.5px 2px rgba(0,0,0,0.05);
}
.summary-table th,
.summary-table td {
  border: 1px solid #e0e0e0;
  padding: 10px 6px;
  text-align: center;
  font-size: 15px;
}
.summary-table th {
  background-color: #42b983;
  color: #fff;
  font-weight: 600;
}
.summary-table tr:hover td {
  background: #f4f7ff;
}

.checkout-btn {
  margin-top: 30px;
  background-color: #42b983;
  color: #fff;
  padding: 13px 0;
  border: none;
  border-radius: 7px;
  font-size: 1.17rem;
  width: 100%;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
}
.checkout-btn:hover {
  background-color: #368c6e;
}

/* Barra de progress */
.progress-container {
  margin: 22px 0 18px 0;
}
.progress-bar {
  display: flex;
  height: 22px;
  width: 100%;
  background-color: #e0e0e0;
  border-radius: 12px;
  overflow: hidden;
}
.progress-deposit {
  background-color: #42b983;
  height: 100%;
  transition: width 0.3s;
}
.progress-remainder {
  background-color: #ffc107;
  height: 100%;
  transition: width 0.3s;
}
.progress-labels {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
  margin-top: 6px;
  color: #333;
}

/* Secció de condicions */
.terms {
  margin: 22px 0 0 0;
  text-align: left;
  position: relative;
  font-size: 15px;
}
.terms input[type="checkbox"].error {
  outline: 2px solid red;
}
.error-message {
  color: #e53935;
  font-size: 14px;
  margin-top: 6px;
  text-align: left;
}
.obligatory-msg {
  font-size: 13px;
  color: #e53935;
  margin-left: 5px;
}

/* Modal de Condicions */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(0,0,0,0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.modal-content {
  background: #fff;
  padding: 26px 20px 18px 20px;
  border-radius: 10px;
  max-width: 540px;
  width: 94vw;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
  animation: modalFadeIn 0.3s;
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
  margin: 16px 0 14px 0;
  text-align: left;
  border-top: 1px solid #eee;
  border-bottom: 1px solid #eee;
  padding: 10px 2px;
  color: #555;
  font-size: 15px;
}
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 18px;
}
.btn.modal-accept-btn {
  background-color: #42b983;
  color: #fff;
  padding: 8px 18px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 600;
  transition: background 0.2s;
}
.btn.modal-accept-btn:hover {
  background-color: #368c6e;
}
.btn.modal-cancel-btn, .btn.modal-close-btn {
  background-color: #e53935;
  color: #fff;
  padding: 8px 18px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-weight: 600;
  transition: background 0.2s;
}
.btn.modal-cancel-btn:hover, .btn.modal-close-btn:hover {
  background-color: #c62828;
}

/* Responsive adjustments */
@media (max-width: 900px) {
  .checkout-container {
    padding: 12px 2vw;
    max-width: 99vw;
  }
  .order-summary {
    margin-bottom: 12px;
  }
  .shop-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
    padding: 12px 10px;
  }
  .summary-table th,
  .summary-table td {
    padding: 7px 2px;
    font-size: 14px;
  }
  .modal-content {
    max-width: 98vw;
    padding: 14px 2vw 10px 2vw;
  }
  .modal-body {
    font-size: 14px;
  }
}</style>
