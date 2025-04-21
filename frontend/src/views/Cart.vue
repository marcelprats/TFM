<template>
  <div class="cart-container">
    <h1>El teu Carro</h1>

    <!-- Botons globals a dalt si hi ha ítems -->
    <div v-if="hasItems" class="global-actions">
      <button class="btn clear-all-btn" @click="openClearAllModal">
        Buidar tot el carro
      </button>
      <button class="btn checkout-all-btn" @click="checkoutTotal">
        Finalitzar Comanda
      </button>
    </div>

    <!-- Missatge si el carro està buit -->
    <div v-if="!hasItems">
      <p>El teu carro està buit.</p>
    </div>

    <!-- Ítems agrupats per botiga -->
    <div v-else>
      <div v-for="(items, shopId) in groupedCartItems" :key="shopId" class="shop-group">
        <!-- Capçalera del grup -->
        <div class="shop-header">
          <span class="shop-name">
            Botiga:
            <template v-if="shopId !== 'sense_botiga'">
              <router-link :to="`/info-botiga/${shopId}`">
                <strong>{{ getStoreName(shopId) }}</strong>
              </router-link>
            </template>
            <template v-else>
              <strong>Sense Botiga</strong>
            </template>
          </span>
          <span class="shop-total">
            Total per botiga: <strong>{{ formatPrice(calcSelectedShopTotal(items)) }}</strong>
          </span>
          <button class="btn clear-group-btn" @click="openClearModal(shopId)">
            Buidar carro de {{ getStoreName(shopId) }}
          </button>
        </div>
        <!-- Taula amb els ítems del grup -->
        <table>
          <thead>
            <tr>
              <th>
                <!-- Checkbox "Select All" per aquest grup -->
                <input type="checkbox"
                  :checked="allSelected(shopId)"
                  @change="toggleSelectGroup(shopId, items)"
                />
              </th>
              <th>Producte</th>
              <th>Preu Unitar</th>
              <th>Quantitat</th>
              <th>Total</th>
              <th>Accions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items" :key="item.id">
              <td>
                <!-- Checkbox lligada a item.selected -->
                <input type="checkbox" v-model="item.selected" @change="updateItemSelected(item)" />
              </td>
              <td class="product-cell" @click="goToProduct(item.product.id)" style="cursor:pointer;">
                <img :src="getImageSrc(item.product.imatge)" alt="producte" class="product-image" />
                <span>{{ item.product.nom }}</span>
              </td>
              <td>{{ formatPrice(item.reserved_price) }}</td>
              <td>
                <div class="quantity-control">
                  <button class="quantity-btn" @click="decreaseQuantity(item)">−</button>
                  <input type="number"
                         v-model.number="item.quantity"
                         min="1"
                         :max="item.product.stock"
                         @change="updateCartItem(item)"
                  />
                  <button class="quantity-btn" @click="increaseQuantity(item)">+</button>
                </div>
              </td>
              <td>{{ formatPrice(item.quantity * parseFloat(item.reserved_price)) }}</td>
              <td>
                <button class="btn trash-btn" @click="confirmSingleDelete(item.id)">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Botons globals a la part inferior -->
    <div v-if="hasItems" class="global-actions">
      <button class="btn clear-all-btn" @click="openClearAllModal">
        Buidar tot el carro
      </button>
      <button class="btn checkout-all-btn" @click="checkoutTotal">
        Finalitzar Comanda
      </button>
    </div>

    <!-- Modal per buidar el grup de botiga -->
    <div class="modal-overlay" v-if="showClearModal" @click="closeClearModal">
      <div class="modal" @click.stop>
        <h2>Confirmar Buidatge</h2>
        <p>
          Estàs segur que vols buidar tots els ítems de la botiga 
          <strong>{{ getStoreName(modalShopId) }}</strong>?
        </p>
        <ul>
          <li v-for="item in (groupedCartItems[modalShopId] || [])" :key="item.id">
            {{ item.product.nom }} (x{{ item.quantity }})
          </li>
        </ul>
        <div class="modal-actions">
          <button class="btn clear-group-btn" @click="clearCartGroup(modalShopId)">
            Confirmar
          </button>
          <button class="btn" @click="closeClearModal">Cancel·lar</button>
        </div>
      </div>
    </div>

    <!-- Modal per buidar tot el carro -->
    <div class="modal-overlay" v-if="showClearAllModal" @click="closeClearAllModal">
      <div class="modal" @click.stop>
        <h2>Confirmar Buidatge Total</h2>
        <p>Estàs segur que vols buidar tots els ítems del carro?</p>
        <ul>
          <li v-for="item in (cart.cart_items || [])" :key="item.id">
            {{ item.product.nom }} (x{{ item.quantity }})
          </li>
        </ul>
        <div class="modal-actions">
          <button class="btn clear-all-btn" @click="clearAllCart">
            Confirmar
          </button>
          <button class="btn" @click="closeClearAllModal">Cancel·lar</button>
        </div>
      </div>
    </div>

    <!-- Modal per Checkout per grup -->
    <div class="modal-overlay" v-if="showCheckoutModal" @click="closeCheckoutModal">
      <div class="modal" @click.stop>
        <h2>Confirmar Checkout</h2>
        <p>
          Es crearà una ordre per la botiga 
          <strong>{{ getStoreName(modalShopId) }}</strong> amb els següents ítems seleccionats:
        </p>
        <ul>
          <li v-for="item in (groupedCartItems[modalShopId] || [])" :key="item.id" v-if="item.selected">
            {{ item.product.nom }} (x{{ item.quantity }})
          </li>
        </ul>
        <div class="modal-actions">
          <button class="btn checkout-all-btn" @click="confirmCheckout(modalShopId)">
            Confirmar Checkout
          </button>
          <button class="btn" @click="closeCheckoutModal">Cancel·lar</button>
        </div>
      </div>
    </div>

    <!-- Modal per confirmació d'eliminació individual -->
    <div class="modal-overlay" v-if="showSingleDeleteModal" @click="closeSingleDeleteModal">
      <div class="modal" @click.stop>
        <h2>Confirmar Eliminació</h2>
        <p>
          Estàs segur que vols eliminar l'ítem <strong>{{ singleDeleteItemName }}</strong>?
        </p>
        <div class="modal-actions">
          <button class="btn remove-btn" @click="deleteSingleItem">
            Confirmar
          </button>
          <button class="btn" @click="closeSingleDeleteModal">Cancel·lar</button>
        </div>
      </div>
    </div>
    
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const API_URL = 'http://127.0.0.1:8000/api';
const router = useRouter();
const cart = ref<any>(null);

const singleDeleteItemName = ref('');

/** Variables per gestió de selecció (són gestionades via el camp "selected" de cada ítem) */
const selectedItems = ref<number[]>([]);
const selectedShopIds = ref<string[]>([]);

/** Variables per modals */
const showClearModal = ref(false);
const modalShopId = ref<string>('');
const showCheckoutModal = ref(false);
const showClearAllModal = ref(false);
const showSingleDeleteModal = ref(false);
const itemToDelete = ref<number | null>(null);

/** Carrega el carro i inicialitza la selecció basant-se en el camp "selected" */
async function loadCart() {
  try {
    const token = localStorage.getItem('userToken');
    const res = await axios.get(`${API_URL}/cart`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    cart.value = res.data;
    if (cart.value && cart.value.cart_items) {
      // Afegim els IDs dels ítems seleccionats actualment
      selectedItems.value = cart.value.cart_items
        .filter((item: any) => item.selected)
        .map((item: any) => item.id);
      // Afegim també les botigues corresponents
      selectedShopIds.value = [
        ...new Set(cart.value.cart_items
          .filter((item: any) => item.selected)
          .map((item: any) =>
            item.product.botiga ? item.product.botiga.id.toString() : 'sense_botiga'
          ))
      ];
    }
  } catch (error) {
    console.error('Error carregant el carro:', error);
  }
}
onMounted(loadCart);

/** Computed per saber si hi ha ítems al carro */
const hasItems = computed(() => {
  return cart.value && cart.value.cart_items && cart.value.cart_items.length > 0;
});

/** Agrupa els ítems del carro per botiga */
const groupedCartItems = computed(() => {
  if (!cart.value || !cart.value.cart_items) return {};
  return cart.value.cart_items.reduce((groups: any, item: any) => {
    const shopId = item.product.botiga ? item.product.botiga.id : 'sense_botiga';
    if (!groups[shopId]) groups[shopId] = [];
    groups[shopId].push(item);
    return groups;
  }, {});
});

/** Obté el nom de la botiga */
const getStoreName = (shopId: string): string => {
  if (shopId === 'sense_botiga') return 'Sense Botiga';
  const group = groupedCartItems.value[shopId];
  return group && group[0] && group[0].product.botiga
    ? group[0].product.botiga.nom
    : 'No definida';
};

/** Calcula el total per botiga només amb ítems seleccionats */
const calcSelectedShopTotal = (items: any[]): number => {
  return items
    .filter((item: any) => item.selected)
    .reduce((sum, item) => sum + item.quantity * parseFloat(item.reserved_price), 0);
};

/** Funció per alternar la selecció de tots els ítems d'un grup */
function toggleSelectGroup(shopId: string, items: any[]) {
  const groupIds = items.map((i: any) => i.id);
  const allAreSelected = groupIds.every((id: number) => selectedItems.value.includes(id));
  if (allAreSelected) {
    selectedItems.value = selectedItems.value.filter((id: number) => !groupIds.includes(id));
    selectedShopIds.value = selectedShopIds.value.filter(id => id !== shopId.toString());
    items.forEach((item: any) => {
      item.selected = false;
      updateItemSelected(item);
    });
  } else {
    groupIds.forEach(id => {
      if (!selectedItems.value.includes(id)) selectedItems.value.push(id);
    });
    if (!selectedShopIds.value.includes(shopId.toString()))
      selectedShopIds.value.push(shopId.toString());
    items.forEach((item: any) => {
      item.selected = true;
      updateItemSelected(item);
    });
  }
}
function allSelected(shopId: string): boolean {
  const group = groupedCartItems.value[shopId];
  if (!group) return false;
  return group.every((item: any) => item.selected);
}

/** Quan es canvia la checkbox d'un ítem, actualitza el camp "selected" al servidor */
async function updateItemSelected(item: any) {
  try {
    const token = localStorage.getItem('userToken');
    await axios.put(`${API_URL}/cart/${item.id}`, {
      quantity: item.quantity,
      selected: item.selected
    }, {
      headers: { Authorization: `Bearer ${token}` },
    });
    await loadCart();
  } catch (error) {
    console.error('Error actualitzant selected:', error);
  }
}

/** Funcions per buidar el grup de botiga */
function openClearModal(shopId: string) {
  modalShopId.value = shopId;
  showClearModal.value = true;
}
function closeClearModal() {
  showClearModal.value = false;
  modalShopId.value = '';
}
async function clearCartGroup(shopId: string) {
  const group = groupedCartItems.value[shopId];
  if (!group || group.length === 0) return;
  try {
    const token = localStorage.getItem('userToken');
    for (const item of group) {
      await axios.delete(`${API_URL}/cart/${item.id}`, {
        headers: { Authorization: `Bearer ${token}` },
      });
    }
    await loadCart();
    const groupIds = group.map((i: any) => i.id);
    selectedItems.value = selectedItems.value.filter((id: number) => !groupIds.includes(id));
    selectedShopIds.value = selectedShopIds.value.filter(id => id !== shopId.toString());
  } catch (error) {
    console.error('Error buidant el grup:', error);
  } finally {
    closeClearModal();
  }
}

/** Funcions globals per buidar tot el carro */
function openClearAllModal() {
  showClearAllModal.value = true;
}
function closeClearAllModal() {
  showClearAllModal.value = false;
}
async function clearAllCart() {
  if (!cart.value || !cart.value.cart_items) return;
  try {
    const token = localStorage.getItem('userToken');
    for (const item of cart.value.cart_items) {
      await axios.delete(`${API_URL}/cart/${item.id}`, {
        headers: { Authorization: `Bearer ${token}` },
      });
    }
    await loadCart();
    selectedItems.value = [];
    selectedShopIds.value = [];
  } catch (error) {
    console.error('Error buidant tot el carro:', error);
  } finally {
    closeClearAllModal();
  }
}

/** Modal per Checkout per grup */
function openCheckoutModal(shopId: string) {
  if (groupHasSelection(shopId)) {
    modalShopId.value = shopId;
    showCheckoutModal.value = true;
  } else {
    alert('Selecciona almenys un ítem d’aquesta botiga per fer checkout.');
  }
}
function closeCheckoutModal() {
  showCheckoutModal.value = false;
  modalShopId.value = '';
}
function groupHasSelection(shopId: string): boolean {
  const group = groupedCartItems.value[shopId];
  if (!group) return false;
  return group.some((item: any) => item.selected);
}
/** Quan es confirma el checkout d'un grup, redirigeix a la pàgina /checkout amb el paràmetre shopId */
function confirmCheckout(shopId: string) {
  router.push(`/checkout?shopId=${shopId}`);
}

/** Botó per Checkout global */
function checkoutTotal() {
  if (selectedItems.value.length === 0) {
    alert('Selecciona almenys un ítem per fer checkout.');
    return;
  }
  if (selectedShopIds.value.length > 1) {
    router.push('/checkout?all=true');
  } else {
    router.push(`/checkout?shopId=${selectedShopIds.value[0]}`);
  }
}

/** Funcions per actualitzar la quantitat d'un ítem */
async function updateCartItem(item: any) {
  try {
    const token = localStorage.getItem('userToken');
    await axios.put(`${API_URL}/cart/${item.id}`, {
      quantity: item.quantity,
      selected: item.selected
    }, {
      headers: { Authorization: `Bearer ${token}` },
    });
    await loadCart();
  } catch (error) {
    console.error('Error actualitzant l’ítem:', error);
  }
}
function increaseQuantity(item: any) {
  if (item.quantity < item.product.stock) {
    item.quantity++;
    updateCartItem(item);
  }
}
function decreaseQuantity(item: any) {
  if (item.quantity > 1) {
    item.quantity--;
    updateCartItem(item);
  }
}

/** Eliminació individual */
function confirmSingleDelete(itemId: number) {
  const item = cart.value?.cart_items?.find((i: any) => i.id === itemId);
  itemToDelete.value = itemId;
  singleDeleteItemName.value = item ? item.product.nom : 'Ítem desconegut';
  showSingleDeleteModal.value = true;
}

async function deleteSingleItem() {
  if (itemToDelete.value === null) return;
  await removeItem(itemToDelete.value);
  closeSingleDeleteModal();
}
function closeSingleDeleteModal() {
  showSingleDeleteModal.value = false;
  itemToDelete.value = null;
}
async function removeItem(itemId: number) {
  try {
    const token = localStorage.getItem('userToken');
    await axios.delete(`${API_URL}/cart/${itemId}`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    await loadCart();
    selectedItems.value = selectedItems.value.filter((id: number) => id !== itemId);
  } catch (error) {
    console.error('Error eliminant l’ítem del carro:', error);
  }
}

/** Funció per formatar preus */
const formatPrice = (price: number | string): string => {
  const p = typeof price === 'number' ? price : parseFloat(price);
  return isNaN(p) ? 'No disponible' : p.toFixed(2) + ' €';
};

/** Funció per obtenir la imatge del producte */
const getImageSrc = (imagePath: string | null): string => {
  if (!imagePath) return '/img/no-imatge.jpg';
  if (imagePath.startsWith('/uploads/')) {
    return `${API_URL.replace('/api', '')}${imagePath}`;
  }
  if (imagePath.startsWith('http')) return imagePath;
  return `${API_URL.replace('/api', '')}/uploads/${imagePath}`;
};

/** Navegar al detall del producte */
function goToProduct(id: number) {
  router.push(`/producte/${id}`);
}
</script>

<style scoped>
.cart-container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 20px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0,0,0,0.05);
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
h1 {
  text-align: center;
  margin-bottom: 20px;
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
.trash-btn,
.remove-btn {
  background-color: #d9534f !important;
}
.clear-all-btn:hover,
.clear-group-btn:hover,
.trash-btn:hover,
.remove-btn:hover {
  background-color: #c9302c !important;
}
.checkout-all-btn,
.checkout-btn {
  background-color: #28a745;
}
.checkout-all-btn:hover,
.checkout-btn:hover {
  background-color: #218838;
}

/* Grup per botiga */
.shop-group {
  margin-bottom: 40px;
}
.shop-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background-color: #f2f2f2;
  padding: 10px 15px;
  border-radius: 4px;
  margin-bottom: 10px;
}
.shop-header .shop-name,
.shop-header .shop-total {
  font-size: 16px;
  color: #333;
}
.shop-header .shop-totals {
  text-align: right;
  display: flex;
  gap: 10px;
}

/* Taula */
table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 20px;
}
th,
td {
  border: 1px solid #ddd;
  padding: 12px;
  text-align: center;
}
th {
  background-color: #42b983;
  color: #fff;
}
.product-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}
.product-cell .product-image {
  width: 50px;
  height: 50px;
  border-radius: 4px;
  object-fit: cover;
  overflow: hidden;
}

/* Quantitat */
.quantity-control {
  display: flex;
  align-items: center;
  gap: 8px;
}
.quantity-btn {
  background-color: #42b983;
  color: #fff;
  border: none;
  padding: 6px 10px;
  font-size: 16px;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.2s ease;
}
.quantity-btn:hover {
  background-color: #368c6e;
}
.quantity-control input {
  width: 60px;
  text-align: center;
  font-size: 16px;
  padding: 4px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

/* Botons generals */
.btn {
  background-color: #42b983;
  color: #fff;
  padding: 8px 14px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.2s ease;
}
.btn:hover {
  background-color: #368c6e;
}

/* Modals */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}
.modal {
  background: #fff;
  padding: 20px 30px;
  border-radius: 8px;
  max-width: 500px;
  width: 90%;
  box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}
.modal h2 {
  margin-top: 0;
  color: #333;
}
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}
</style>
