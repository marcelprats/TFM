<script setup lang="ts">
import { ref, onMounted, computed, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { fetchProductById, fetchProducts } from "../services/authService";

const route = useRoute();
const router = useRouter();
const product = ref(null);
const allProducts = ref([]);
const relatedProducts = ref([]);

// Funció per barrejar una llista aleatòriament
const shuffleArray = (array) => {
  return array.sort(() => Math.random() - 0.5);
};

const loadProduct = async () => {
  const productId = route.params.id;
  product.value = await fetchProductById(productId);
  allProducts.value = await fetchProducts();
  updateRelatedProducts();
};

// Funció per trobar productes de la mateixa botiga i barrejar-los
const updateRelatedProducts = () => {
  if (!product.value || !allProducts.value.length) return;

  const botigaId = product.value.botigues?.length ? product.value.botigues[0].id : null;

  let sameStoreProducts = allProducts.value.filter(p =>
    p.id !== product.value.id && p.botigues?.some(b => b.id === botigaId)
  );

  let extraProducts = shuffleArray(allProducts.value.filter(p => p.id !== product.value.id))
    .slice(0, 4 - sameStoreProducts.length);

  relatedProducts.value = shuffleArray([...sameStoreProducts, ...extraProducts]).slice(0, 4);

  console.log("Productes relacionats després d'assignar:", relatedProducts.value);
};


// Funció per anar a un producte relacionat
const goToProduct = (id: number) => {
  console.log("Navegant a producte amb ID:", id); // DEBUG
  router.push(`/producte/${id}`);
};


// Carregar el producte quan es carrega la pàgina
onMounted(loadProduct);

// Recarregar quan es canvia de producte
watch(() => route.params.id, loadProduct);
</script>

<template>
  <div class="product-page">
    <template v-if="product">
      <div class="product-container">
        <h1 class="product-title">{{ product.nom }}</h1>
        <div class="product-details">
          <div class="product-image">
            <img 
              :src="product.imatge ? product.imatge : '/img/no-imatge.jpg'" 
              :alt="product.nom" 
            />
          </div>

          <div class="product-info">
            <p class="price"><strong>Preu:</strong> {{ product.preu }} €</p>

            <p>
              <strong>Botiga:</strong>
              <template v-if="product.botiga">
                <router-link :to="'/info-botiga/' + product.botiga.id" class="link">
                  {{ product.botiga.nom }}
                </router-link>
              </template>
              <span v-else>No disponible</span>
            </p>

            <p>
              <strong>Venedor:</strong>
              <template v-if="product.vendor">
                <router-link 
                  :to="'/info-venedor/' + product.vendor.id" 
                  class="link">
                  {{ product.vendor.name }}
                </router-link>
              </template>
              <span v-else>No disponible</span>
            </p>
          </div>
        </div>

        <div class="description-box">
          <h2>Descripció</h2>
          <p>{{ product.descripcio }}</p>
        </div>
      </div>

      <!-- 🛍️ CONTENIDOR DE PRODUCTES RELACIONATS -->
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
                <strong>Botiga:</strong> 
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
      <p>Carregant producte...</p>
    </template>
  </div>
</template>



<style scoped>
/* Contenidor principal */
.product-page {
  width: 100%;
  min-height: 80vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 50px 20px;
}

/* Contenidor del producte */
.product-container {
  width: 900px;
  background: white;
  padding: 40px;
  border-radius: 12px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
  text-align: center;
  margin-bottom: 40px;
}

/* Títol */
.product-title {
  font-size: 32px;
  font-weight: bold;
  margin-bottom: 20px;
}

/* Detalls del producte */
.product-details {
  display: flex;
  align-items: center;
  justify-content: space-between;
  text-align: left;
  gap: 40px;
  margin-bottom: 30px;
}

/* Imatge del producte */
.product-image img {
  width: 250px;
  height: 250px;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid #ddd;
}

/* Informació */
.product-info p {
  font-size: 18px;
  margin: 10px 0;
}

.price {
  font-size: 24px;
  font-weight: bold;
  color: black;
}

/* Descripció */
.description-box {
  background: white;
  padding: 20px;
  border-radius: 8px;
  text-align: left;
}

/* 🔹 Contenidor separat per a productes relacionats */
.related-products-container {
  margin-top: 40px;
  padding: 20px;
  background: white;
  border-radius: 10px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.related-products h2 {
  font-size: 22px;
  font-weight: bold;
  margin-bottom: 20px;
}

.related-grid {
  display: flex;
  justify-content: center;
  gap: 15px;
  flex-wrap: wrap;
}

.related-card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  padding: 15px;
  text-align: center;
  width: 200px;
  cursor: pointer;
  transition: transform 0.2s ease-in-out;
}

.related-card:hover {
  transform: scale(1.05);
}

.related-image {
  width: 100%;
  height: 150px;
  object-fit: cover;
  border-radius: 5px;
}

.related-info {
  padding: 10px;
}

.related-name {
  font-size: 18px;
  font-weight: bold;
}

.related-price {
  font-size: 16px;
  color: #42b983;
}

.related-store {
  font-size: 14px;
  color: #666;
}
</style>
