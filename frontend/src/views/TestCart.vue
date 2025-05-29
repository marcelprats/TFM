<template>
  <div class="test-cart">
    <h1>Test Carret</h1>
    <form @submit.prevent="handleAddItem">
      <div>
        <label for="productId">Product ID:</label>
        <input
          type="number"
          id="productId"
          v-model.number="productId"
          required
        />
      </div>
      <div>
        <label for="quantity">Quantitat:</label>
        <input
          type="number"
          id="quantity"
          v-model.number="quantity"
          required
          min="1"
        />
      </div>
      <button type="submit">Afegir al Carret</button>
    </form>
    <hr />
    <button @click="loadCart">Carregar Carret</button>
    <div v-if="cart">
      <h2>Contingut del Carret:</h2>
      <pre>{{ cart }}</pre>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import axios from 'axios'

const productId = ref<number>(0)
const quantity = ref<number>(1)
const cart = ref<any>(null)

async function handleAddItem() {
  try {
    await axios.post('/cart', {
      product_id: productId.value,
      quantity: quantity.value,
    })
    alert('Producte afegit al carret!')
  } catch (error) {
    console.error('Error afegint al carret:', error)
    alert('Error afegint al carret')
  }
}

async function loadCart() {
  try {
    const response = await axios.get('/cart')
    cart.value = response.data
  } catch (error) {
    console.error('Error carregant el carret:', error)
    alert('Error carregant el carret')
  }
}
</script>

<style scoped>
.test-cart {
  max-width: 600px;
  margin: 20px auto;
  padding: 20px;
  border: 1px solid #ddd;
  border-radius: 8px;
  background-color: #fff;
}
.test-cart form > div {
  margin-bottom: 10px;
}
.test-cart label {
  margin-right: 8px;
}
.test-cart button {
  padding: 8px 12px;
  border: none;
  border-radius: 4px;
  background-color: #42b983;
  color: #fff;
  cursor: pointer;
  transition: background 0.2s;
}
.test-cart button:hover {
  background-color: #368c6e;
}
</style>
