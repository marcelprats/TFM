<template>
  <div class="import-record-container">
    <h2>Registre d'Importació #{{ id }}</h2>
    <div v-if="record">
      <p><strong>Venedor:</strong> {{ record.vendor_id }}</p>
      <p><strong>Botiga:</strong> {{ record.botiga_id }}</p>
      <p><strong>Total Importats:</strong> {{ record.total_importats }}</p>
      <p><strong>Total Errors:</strong> {{ record.total_errors }}</p>
      <p><strong>Observacions:</strong> {{ record.observacions }}</p>
      <!-- Afegeix altres camps que vulguis mostrar -->
    </div>
    <div v-else>
      <p>Carregant registre...</p>
    </div>
    <button class="btn" @click="$router.back()">Tornar</button>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const id = route.params.id;
const record = ref(null);

const API_URL = 'http://127.0.0.1:8000/api';

onMounted(async () => {
  try {
    const token = localStorage.getItem('userToken');
    const response = await axios.get(`${API_URL}/importacions/${id}`, {
      headers: { Authorization: `Bearer ${localStorage.getItem('userToken')}` },
    });
    record.value = response.data.importRecord;
    products.value = response.data.products;
    record.value = response.data;
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
</style>
