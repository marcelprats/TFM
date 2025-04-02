<template>
  <div class="import-record-container">
    <h2>Registre d'Importació #{{ id }}</h2>
    <div v-if="record">
      <p><strong>Venedor:</strong> {{ record.vendor_id }}</p>
      <p><strong>Botiga:</strong> {{ record.botiga_id }}</p>
      <p><strong>Total Importats:</strong> {{ record.total_importats }}</p>
      <p><strong>Total Errors:</strong> {{ record.total_errors }}</p>
      <p><strong>Observacions:</strong> {{ record.observacions || '—' }}</p>
    </div>
    <div v-else>
      <p>Carregant registre...</p>
    </div>
    <div v-if="products.length > 0">
      <h3>Productes importats</h3>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Nom</th>
            <th>Preu</th>
            <th>Stock</th>
            <!-- Pots afegir més columnes si cal -->
          </tr>
        </thead>
        <tbody>
          <tr v-for="(prod, index) in products" :key="prod.id">
            <td>{{ index + 1 }}</td>
            <td>{{ prod.nom }}</td>
            <td>{{ prod.preu }}</td>
            <td>{{ prod.stock }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <button class="btn" @click="$router.back()">Tornar</button>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const router = useRouter();
const id = route.params.id;
const record = ref(null);
const products = ref<any[]>([]);
const API_URL = 'http://127.0.0.1:8000/api';

onMounted(async () => {
  try {
    const token = localStorage.getItem('userToken');
    const response = await axios.get(`${API_URL}/importacions/${id}`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    // Suposem que el controlador API retorna un JSON amb:
    // { importRecord: {…}, products: [ … ] }
    record.value = response.data.importRecord;
    products.value = response.data.products;
  } catch (error) {
    console.error("Error carregant el registre d'importació", error);
  }
});
</script>

<style scoped>
.import-record-container {
  padding: 20px;
  max-width: 600px;
  margin: 0 auto;
}

.import-record-container h2 {
  margin-bottom: 20px;
}

table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
}

th,
td {
  border: 1px solid #ccc;
  padding: 8px;
  text-align: left;
}

.btn {
  margin-top: 20px;
  padding: 10px 15px;
  background-color: #42b983;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}
</style>
