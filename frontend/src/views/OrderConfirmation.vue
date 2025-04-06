<template>
  <div class="order-confirmation">
    <h1>Confirmació de la Comanda</h1>
    <div v-if="order">
      <p><strong>Codi de Comanda:</strong> {{ order.order_number }}</p>
      <p><strong>Total:</strong> {{ formatPrice(order.total_amount) }}</p>
      <p><strong>Mètode de Pagament:</strong> {{ order.payment_method }}</p>
      <p><strong>Estat:</strong> {{ order.status }}</p>
      <!-- Afegeix més informació o instruccions per al client -->
      <p>
        Un cop confirmada la comanda, rebràs més instruccions per completar el pagament del restant al local.
      </p>
    </div>
    <div v-else>
      <p>Carregant informació de la comanda...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRoute } from 'vue-router';

const route = useRoute();
const order = ref(null);
const API_URL = 'http://127.0.0.1:8000/api';

function formatPrice(price: number | string): string {
  const p = typeof price === 'number' ? price : parseFloat(price);
  if (isNaN(p)) return 'No disponible';
  return p.toFixed(2) + ' €';
}

async function loadOrder() {
  try {
    const token = localStorage.getItem('userToken');
    const response = await axios.get(`${API_URL}/orders/${route.params.id}`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    order.value = response.data;
  } catch (error) {
    console.error('Error carregant la comanda:', error);
  }
}

onMounted(loadOrder);
</script>


<style scoped>
.order-confirmation {
  max-width: 600px;
  margin: 40px auto;
  padding: 20px;
  text-align: center;
  border: 1px solid #ddd;
  border-radius: 8px;
  background-color: #fff;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
}

.order-details p {
  font-size: 16px;
  margin: 10px 0;
  color: #333;
}

.next-steps {
  text-align: left;
  margin-top: 30px;
}

.next-steps h2 {
  margin-bottom: 10px;
  color: #333;
}

.next-steps ol {
  padding-left: 20px;
}

.next-steps li {
  margin-bottom: 10px;
  color: #555;
}

.home-link {
  display: inline-block;
  margin-top: 30px;
  padding: 10px 20px;
  background-color: #28a745;
  color: #fff;
  text-decoration: none;
  border-radius: 4px;
  transition: background-color 0.3s ease;
}

.home-link:hover {
  background-color: #218838;
}
</style>
