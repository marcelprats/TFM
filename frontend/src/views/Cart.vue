<template>
  <div class="cart-container">
    <h1>El teu Carret</h1>
    
    <!-- Botó Bulk Delete: només es mostra si hi ha ítems seleccionats -->
    <div v-if="selectedItems.length > 0" class="bulk-delete-trigger">
      <button class="btn remove-btn" @click="openBulkDeleteModal">
        Eliminar els seleccionats ({{ selectedItems.length }} ítem(s), Quantitat total: {{ totalSelectedQuantity }})
      </button>
    </div>
    
    <!-- Espai extra entre el botó i la taula -->
    <div v-if="selectedItems.length > 0" style="margin-bottom: 20px;"></div>
    
    <div v-if="cart && cart.cart_items && cart.cart_items.length > 0">
      <table>
        <thead>
          <tr>
            <th>
              <input type="checkbox" v-model="selectAll" @change="toggleSelectAll" />
            </th>
            <th>Producte</th>
            <th>Preu Unitari</th>
            <th>Quantitat</th>
            <th>Total</th>
            <th>Accions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in cart.cart_items" :key="item.id">
            <td>
              <input type="checkbox" :value="item.id" v-model="selectedItems" />
            </td>
            <td class="product-cell" @click="goToProduct(item.product.id)" style="cursor: pointer;">
              <img :src="getImageSrc(item.product.imatge)" :alt="item.product.nom" class="product-image" />
              <span>{{ item.product.nom }}</span>
            </td>
            <td>{{ formatPrice(item.reserved_price) }}</td>
            <td>
              <div class="quantity-control">
                <button class="quantity-btn" @click="decreaseQuantity(item)">−</button>
                <input type="number" v-model.number="item.quantity" min="1" @change="updateCartItem(item)" />
                <button class="quantity-btn" @click="increaseQuantity(item)">+</button>
              </div>
            </td>
            <td>{{ formatPrice(item.quantity * parseFloat(item.reserved_price)) }}</td>
            <td>
              <button class="btn remove-btn" @click="confirmSingleDelete(item.id)">Eliminar</button>
            </td>
          </tr>
        </tbody>
      </table>
      <div class="cart-summary">
        <p><strong>Total:</strong> {{ formatPrice(cartTotal) }}</p>
        <button class="btn checkout-btn" @click="checkout">Finalitzar Comanda</button>
      </div>
    </div>
    <div v-else>
      <p>El teu carret està buit.</p>
    </div>

    <!-- Modal de confirmació per eliminació massiva -->
    <div class="modal-overlay" v-if="showBulkDeleteModal" @click="closeBulkDeleteModal">
    <div class="modal" @click.stop>
        <h2>Confirmar eliminació</h2>
        <p>Estàs segur que vols eliminar els següents ítems?</p>
        <ul>
        <li v-for="item in selectedItemsDetails" :key="item.id">
            {{ item.product.nom }} (x{{ item.quantity }})
        </li>
        </ul>
        <p><strong>Quantitat total a eliminar:</strong> {{ totalSelectedQuantity }}</p>
        <div class="modal-actions">
        <button class="btn remove-btn" @click="bulkDelete">Confirmar Eliminació</button>
        <button class="btn" @click="closeBulkDeleteModal">Cancel·lar</button>
        </div>
    </div>
    </div>

    <!-- Modal per confirmar eliminació individual -->
    <div class="modal-overlay" v-if="showSingleDeleteModal" @click="closeSingleDeleteModal">
    <div class="modal" @click.stop>
        <h2>Confirmar Eliminació</h2>
        <p>Estàs segur que vols eliminar l'ítem <strong>{{ singleDeleteItemName }}</strong>?</p>
        <div class="modal-actions">
        <button class="btn remove-btn" @click="deleteSingleItem">Confirmar</button>
        <button class="btn" @click="closeSingleDeleteModal">Cancel·lar</button>
        </div>
    </div>
    </div>
        </div>

</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const API_URL = 'http://127.0.0.1:8000/api';
const router = useRouter();
const cart = ref<any>(null);
const selectAll = ref<boolean>(false);
const selectedItems = ref<number[]>([]);
const showBulkDeleteModal = ref<boolean>(false);
const showSingleDeleteModal = ref<boolean>(false);
const itemToDelete = ref<number | null>(null);

async function loadCart() {
  try {
    const token = localStorage.getItem('userToken');
    const response = await axios.get(`${API_URL}/cart`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    cart.value = response.data;
  } catch (error) {
    console.error('Error carregant el carret:', error);
  }
}

onMounted(loadCart);

// Mètodes per la selecció
function toggleSelectAll() {
  if (selectAll.value) {
    selectedItems.value = cart.value.cart_items.map((item: any) => item.id);
  } else {
    selectedItems.value = [];
  }
}

const totalSelectedQuantity = computed(() => {
  if (!cart.value || !cart.value.cart_items) return 0;
  return cart.value.cart_items
    .filter((item: any) => selectedItems.value.includes(item.id))
    .reduce((sum: number, item: any) => sum + item.quantity, 0);
});

const selectedItemsDetails = computed(() => {
  if (!cart.value || !cart.value.cart_items) return [];
  return cart.value.cart_items.filter((item: any) =>
    selectedItems.value.includes(item.id)
  );
});

const cartTotal = computed(() => {
  if (!cart.value || !cart.value.cart_items) return 0;
  return cart.value.cart_items.reduce(
    (sum: number, item: any) =>
      sum + item.quantity * parseFloat(item.reserved_price),
    0
  );
});

function formatPrice(price: number | string): string {
  const p = typeof price === 'number' ? price : parseFloat(price);
  if (isNaN(p)) return 'No disponible';
  return p.toFixed(2) + ' €';
}

function getImageSrc(imagePath: string | null): string {
  if (!imagePath) return '/img/no-imatge.jpg';
  if (imagePath.startsWith('/uploads/')) {
    return `${API_URL.replace('/api', '')}${imagePath}`;
  }
  if (imagePath.startsWith('http')) return imagePath;
  return `${API_URL.replace('/api', '')}/uploads/${imagePath}`;
}

function goToProduct(id: number) {
  router.push(`/producte/${id}`);
}

async function updateCartItem(item: any) {
  try {
    const token = localStorage.getItem('userToken');
    await axios.put(`${API_URL}/cart/${item.id}`, { quantity: item.quantity }, {
      headers: { Authorization: `Bearer ${token}` },
    });
    await loadCart();
  } catch (error) {
    console.error('Error actualitzant l’ítem:', error);
  }
}

function increaseQuantity(item: any) {
  item.quantity++;
  updateCartItem(item);
}

function decreaseQuantity(item: any) {
  if (item.quantity > 1) {
    item.quantity--;
    updateCartItem(item);
  }
}

async function removeItem(itemId: number) {
  try {
    const token = localStorage.getItem('userToken');
    await axios.delete(`${API_URL}/cart/${itemId}`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    await loadCart();
    selectedItems.value = selectedItems.value.filter(id => id !== itemId);
  } catch (error) {
    console.error('Error eliminant l’ítem del carret:', error);
  }
}

// Eliminació individual
function confirmSingleDelete(itemId: number) {
  itemToDelete.value = itemId;
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

const singleDeleteItemName = computed(() => {
  if (!cart.value || !cart.value.cart_items) return '';
  const item = cart.value.cart_items.find((i: any) => i.id === itemToDelete.value);
  return item ? item.product.nom : '';
});

// Bulk Delete
function openBulkDeleteModal() {
  showBulkDeleteModal.value = true;
}

function closeBulkDeleteModal() {
  showBulkDeleteModal.value = false;
}

async function bulkDelete() {
  try {
    const token = localStorage.getItem('userToken');
    for (const id of selectedItems.value) {
      await axios.delete(`${API_URL}/cart/${id}`, {
        headers: { Authorization: `Bearer ${token}` },
      });
    }
    alert('Ítems eliminats correctament.');
    closeBulkDeleteModal();
    selectedItems.value = [];
    selectAll.value = false;
    await loadCart();
  } catch (error) {
    console.error('Error eliminant els ítems del carret:', error);
    alert('Error eliminant els ítems.');
  }
}

function checkout() {
  router.push('/checkout');
}
</script>

<style scoped>
.cart-container {
  max-width: 1000px;
  margin: 0 auto;
  padding: 20px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

h1 {
  text-align: center;
  margin-bottom: 20px;
}

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
  overflow: hidden;
  border-radius: 4px;
}

.product-cell img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

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

.remove-btn {
  background-color: #d9534f;
}

.remove-btn:hover {
  background-color: #c9302c;
}

.cart-summary {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 20px;
}

.checkout-btn {
  background-color: #28a745;
}

.checkout-btn:hover {
  background-color: #218838;
}

/* Estils per al modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
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
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
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

.bulk-delete-trigger {
  text-align: center;
  margin-bottom: 10px;
}
</style>
