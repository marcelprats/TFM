<template>
  <div class="product-page">
    <template v-if="product">
      <div class="product-header">
        <!-- Columna per a la imatge -->
        <div class="column image-col">
          <div class="image-container">
            <img :src="getImageSrc(product.imatge)" :alt="product.nom" />
          </div>
        </div>

        <!-- Columna d'informació del producte -->
        <div class="column info-col">
          <h1 class="product-title">{{ product.nom }}</h1>
          <p class="price"><strong>Preu:</strong> {{ formattedPrice }}</p>
          <p class="store">
            <strong>Botiga: </strong>
            <router-link
              v-if="product.botiga"
              :to="`/info-botiga/${product.botiga.id}`"
              class="store-link"
            >
              {{ product.botiga.nom }}
            </router-link>
            <span v-else>No disponible</span>
          </p>
          <p class="vendor">
            <strong>Venedor: </strong>
            <router-link
              v-if="product.vendor"
              :to="`/info-venedor/${product.vendor.id}`"
              class="vendor-link"
            >
              {{ product.vendor.name }}
            </router-link>
            <span v-else>No disponible</span>
          </p>
          <!-- Mostrem el stock real -->
          <p class="stock">
            <strong>Stock disponible:</strong> {{ product.stock || "No disponible" }}
          </p>
        </div>

        <!-- Columna per afegir al carro -->
        <div class="column reserve-col">
          <div class="reserve-section">
            <label for="quantity" class="reserve-label">
              <strong>Quantitat a reservar:</strong>
            </label>
            <div class="quantity-selector">
              <button class="quantity-btn" @click="decreaseQuantity">−</button>
              <input
                id="quantity"
                type="number"
                v-model.number="quantity"
                min="1"
                :max="product.stock"
              />
              <button class="quantity-btn" @click="increaseQuantity">+</button>
            </div>
            <template v-if="!inCart">
              <button class="btn reserve-btn" @click="handleAddItem">
                Afegir al Carro
              </button>
            </template>
            <template v-else>
              <button class="btn view-cart-btn" @click="goToCart">
                Veure Carro
              </button>
              <button class="btn trash-btn" @click="removeCartItem">
                <i class="fa-solid fa-trash" style="color: white;"></i>
              </button>
            </template>
            <div v-if="addSuccessMessage" class="add-message">
              {{ addSuccessMessage }}
            </div>
          </div>
        </div>
      </div>

      <!-- Secció de descripció -->
      <div class="description-section">
        <h2>Descripció</h2>
        <p>{{ product.descripcio }}</p>
      </div>

      <!-- Contenidor de productes relacionats -->
      <div class="related-products-container" v-if="relatedProducts.length > 0">
        <div class="related-products">
          <h2>Productes que et poden interessar</h2>
          <div class="related-grid">
            <div v-for="related in relatedProducts" :key="related.id" class="related-card" @click="goToProduct(related.id)">
              <img 
                :src="related.imatge ? related.imatge : '/img/no-imatge.jpg'" 
                :alt="related.nom" 
                class="related-image"
              />

              <div class="related-info">
              <h3 class="related-name">{{ related.name || "Nom no disponible" }}</h3>

              <p class="related-price">
                <strong>Preu:</strong> 
                {{ related.price && !isNaN(parseFloat(related.price)) ? parseFloat(related.price).toFixed(2) + " €" : "No disponible" }}
              </p>

              <p class="related-store">
                <strong>Botiga: </strong> 
                <span v-if="related.store">
                  {{ related.store.name }}
                </span>
                <span v-else>No disponible</span>
              </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>
    <template v-else>
      <p class="loading">Carregant producte...</p>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { fetchProductById, fetchProducts } from '../services/authService';

interface Product {
  id: number;
  nom: string;
  descripcio: string;
  preu: number | string;
  imatge: string | null;
  stock?: number; // Stock real del producte
  botiga?: { id: number; nom: string };
  vendor?: { id: number; name: string };
}

const route = useRoute();
const router = useRouter();
const product = ref<Product | null>(null);
const allProducts = ref<Product[]>([]);
const relatedProducts = ref<Product[]>([]);
const quantity = ref<number>(1);
const addSuccessMessage = ref('');
const inCart = ref(false);
const cartItemId = ref<number | null>(null);

const API_URL = 'http://127.0.0.1:8000/api';
const BACKEND_URL = 'http://127.0.0.1:8000';

function shuffleArray<T>(array: T[]): T[] {
  return array.sort(() => Math.random() - 0.5);
}

async function loadProduct() {
  try {
    addSuccessMessage.value = '';
    const productId = route.params.id;
    product.value = await fetchProductById(productId);
    await loadAllProducts();
    updateRelatedProducts();
    await loadCartQuantity();
  } catch (error) {
    console.error('Error carregant el producte:', error);
  }
}

async function loadAllProducts() {
  try {
    const productsData = await fetchProducts();
    allProducts.value = productsData;
  } catch (error) {
    console.error('Error carregant tots els productes:', error);
  }
}

function updateRelatedProducts() {
  if (!product.value || !allProducts.value.length) return;
  const currentProductId = product.value.id;
  const storeId = product.value.botiga?.id;
  let sameStore = allProducts.value.filter(
    (p) => p.id !== currentProductId && p.botiga?.id === storeId
  );
  let others = allProducts.value.filter((p) => p.id !== currentProductId);
  others = shuffleArray(others).slice(0, 4 - sameStore.length);
  relatedProducts.value = shuffleArray([...sameStore, ...others]).slice(0, 4);
}

const filteredRelatedProducts = computed(() => {
  return relatedProducts.value;
});

function formatPrice(price: number | string): string {
  const p = typeof price === 'number' ? price : parseFloat(price);
  if (isNaN(p)) return 'No disponible';
  return p.toFixed(2) + ' €';
}

const formattedPrice = computed(() => {
  if (!product.value) return '—';
  return formatPrice(product.value.preu);
});

function getImageSrc(imagePath: string | null): string {
  if (!imagePath) return '/img/no-imatge.jpg';
  if (imagePath.startsWith('/uploads/')) {
    return `${BACKEND_URL}${imagePath}`;
  }
  if (imagePath.startsWith(BACKEND_URL)) {
    return imagePath;
  }
  return `${BACKEND_URL}/uploads/${imagePath}`;
}

function goToProduct(id: number) {
  router.push(`/producte/${id}`);
}

function goToCart() {
  router.push('/cart');
}

function decreaseQuantity() {
  if (quantity.value > 0) {
    quantity.value--;
    if (inCart.value) {
      // Si la quantitat arriba a 0, eliminem l'ítem i reiniciem a 1
      if (quantity.value === 0) {
        removeCartItem();
        quantity.value = 1;
      } else {
        updateCartQuantity();
      }
    }
  }
}

function increaseQuantity() {
  if (product.value && product.value.stock && quantity.value < product.value.stock) {
    quantity.value++;
    if (inCart.value) {
      updateCartQuantity();
    }
  }
}

// Carrega la quantitat actual del producte al carro
async function loadCartQuantity() {
  try {
    const token = localStorage.getItem('userToken');
    const response = await axios.get(`${API_URL}/cart`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    const cartData = response.data;
    const found = cartData.cart_items.find((item: any) =>
      product.value && item.product.id === product.value.id
    );
    if (found) {
      quantity.value = found.quantity;
      inCart.value = true;
      cartItemId.value = found.id;
    } else {
      quantity.value = 1;
      inCart.value = false;
      cartItemId.value = null;
    }
  } catch (error) {
    console.error('Error carregant la quantitat del carro:', error);
  }
}

// Actualitza la quantitat del producte al carro
async function updateCartQuantity() {
  try {
    if (!product.value || !cartItemId.value) return;
    const token = localStorage.getItem('userToken');
    await axios.put(`${API_URL}/cart/${cartItemId.value}`, {
      quantity: quantity.value,
    }, {
      headers: { Authorization: `Bearer ${token}` },
    });
  } catch (error) {
    console.error('Error actualitzant la quantitat del carro:', error);
  }
}

// Elimina l'ítem del carro
async function removeCartItem() {
  try {
    if (!cartItemId.value) return;
    const token = localStorage.getItem('userToken');
    await axios.delete(`${API_URL}/cart/${cartItemId.value}`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    inCart.value = false;
    cartItemId.value = null;
  } catch (error) {
    console.error('Error eliminant el producte del carro:', error);
  }
}

async function handleAddItem() {
  if (!product.value) return;
  const token = localStorage.getItem('userToken');
  try {
    const response = await axios.post(`${API_URL}/cart`, {
      product_id: product.value.id,
      quantity: quantity.value,
    }, {
      headers: { Authorization: `Bearer ${token}` },
    });
    addSuccessMessage.value = 'Producte afegit al carro!';
    // Sincronitza la quantitat segons el que hi ha al carro
    await loadCartQuantity();
    setTimeout(() => {
      addSuccessMessage.value = '';
    }, 3000);
  } catch (error) {
    console.error('Error afegint al carro:', error);
    addSuccessMessage.value = 'Error afegint al carro';
    setTimeout(() => {
      addSuccessMessage.value = '';
    }, 3000);
  }
}

onMounted(loadProduct);
watch(() => route.params.id, loadProduct);
</script>

<style scoped>
.product-page {
  width: 100%;
  min-height: 80vh;
  padding: 50px 20px;
  background-color: #f4f4f4;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.product-header {
  display: flex;
  flex-wrap: wrap;
  gap: 24px;
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  width: 100%;
  max-width: 1100px;
}

.column {
  flex: 1;
  min-width: 280px;
}

.image-col .image-container {
  width: 100%;
  height: 320px;
  border-radius: 12px;
  overflow: hidden;
  position: relative;
}

.image-col img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s ease;
  cursor: zoom-in;
}

.image-col img:hover {
  transform: scale(1.1);
}

.info-col {
  display: flex;
  flex-direction: column;
  justify-content: center;
  gap: 10px;
}

.product-title {
  font-size: 28px;
  font-weight: 600;
  color: #2d2d2d;
}

.price {
  font-size: 24px;
  color: #2e7d32;
  font-weight: 500;
}

.store-link,
.vendor-link {
  color: #2c7a7b;
  text-decoration: none;
  transition: 0.3s;
}

.store-link:hover,
.vendor-link:hover {
  text-decoration: underline;
  color: #1a535c;
}

.stock {
  font-size: 16px;
  color: #555;
  margin-top: 4px;
}

.reserve-col {
  display: flex;
  align-items: center;
  justify-content: center;
}

.reserve-section {
  border: 1px solid #ddd;
  background: #fafafa;
  border-radius: 12px;
  padding: 20px;
  width: 100%;
  max-width: 280px;
  text-align: center;
}

.reserve-label {
  margin-bottom: 10px;
  font-weight: 500;
  color: #333;
}

.quantity-selector {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
  margin-bottom: 16px;
}

.quantity-btn {
  background-color: #42b983;
  color: white;
  border: none;
  padding: 8px 14px;
  font-size: 18px;
  border-radius: 6px;
  cursor: pointer;
  transition: 0.3s;
}

.quantity-btn:hover {
  background-color: #368c6e;
}

.quantity-selector input {
  width: 60px;
  padding: 6px;
  text-align: center;
  font-size: 16px;
  border-radius: 6px;
  border: 1px solid #ccc;
}

.btn {
  font-size: 16px;
  padding: 10px 16px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  transition: 0.2s ease;
  margin-top: 8px;
}

.reserve-btn {
  background-color: #2e7d32;
  color: white;
}

.reserve-btn:hover {
  background-color: #27632a;
}

.view-cart-btn {
  background-color: #0288d1;
  color: white;
}

.view-cart-btn:hover {
  background-color: #0277bd;
}

.trash-btn {
  background-color: #e53935;
  color: white;
}

.trash-btn:hover {
  background-color: #c62828;
}

.add-message {
  margin-top: 10px;
  color: #2e7d32;
  font-weight: 500;
}

/* Descripció */
.description-section {
  width: 100%;
  max-width: 1100px;
  background: white;
  border-radius: 12px;
  padding: 24px;
  margin-top: 20px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.description-section h2 {
  font-size: 22px;
  margin-bottom: 12px;
  color: #333;
}

/* Productes relacionats */
.related-products-container {
  width: 100%;
  max-width: 1100px;
  background: white;
  padding: 24px;
  border-radius: 12px;
  margin-top: 24px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.related-products h2 {
  font-size: 20px;
  margin-bottom: 20px;
  font-weight: 600;
}

.related-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  justify-content: center;
}

.related-card {
  width: 200px;
  border: 1px solid #ddd;
  border-radius: 8px;
  overflow: hidden;
  transition: 0.3s ease-in-out;
  background: #fff;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);
  cursor: pointer;
}

.related-card:hover {
  transform: scale(1.03);
}

.related-image {
  width: 100%;
  height: 150px;
  object-fit: cover;
}

.related-info {
  padding: 10px;
}

.related-name {
  font-size: 16px;
  font-weight: 600;
  margin: 4px 0;
  color: #2d2d2d;
}

.related-price {
  color: #42b983;
  font-weight: 500;
  font-size: 14px;
}

.related-store {
  font-size: 13px;
  color: #666;
}

.loading {
  font-size: 18px;
  margin-top: 40px;
  color: #333;
}

/* Responsive */
@media (max-width: 768px) {
  .product-header {
    flex-direction: column;
  }
}
</style>
