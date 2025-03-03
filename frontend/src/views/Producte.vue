<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRoute } from "vue-router";
import { fetchProductById } from "../services/authService";

const route = useRoute();
const product = ref(null);

onMounted(async () => {
  const productId = route.params.id;
  product.value = await fetchProductById(productId);
});
</script>

<template>
  <div class="product-page">
    <template v-if="product">
      <div class="product-container">
        <h1>{{ product.nom }}</h1>
        <div class="product-details">
          <div class="left-column">
            <p><strong>Preu:</strong> {{ product.preu }} €</p>
            <p><strong>Botiga:</strong> {{ product.botigues ? product.botigues.map(b => b.nom).join(', ') : 'N/A' }}</p>
            <p><strong>Venedor:</strong> {{ product.vendor ? product.vendor.name : 'N/A' }}</p>
          </div>
          <div class="right-column">
            <p><strong>Descripció:</strong> {{ product.descripcio }}</p>
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
.product-page {
  width: 100%;
  min-height: 85vh;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding-top: 100px;
}

.product-container {
  width: 800px; /* Ample fix */
  background: white;
  padding: 40px;
  border-radius: 10px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.product-info h1 {
  font-size: 36px;
  font-weight: bold;
  margin-bottom: 20px;
}

.product-details {
  display: flex;
  justify-content: space-between;
  text-align: left;
  gap: 40px;
}

.left-column {
  width: 40%;
}

.right-column {
  width: 60%;
}

.product-info p {
  font-size: 18px;
  margin: 10px 0;
}

.price {
  font-size: 24px;
  font-weight: bold;
  color: black; /* Ja no és verd */
}


</style>
