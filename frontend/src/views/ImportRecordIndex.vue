<template>
  <div class="import-record-index">
    <h1>Registres d'importació</h1>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Data</th>
          <th>Total Importats</th>
          <th>Total Errors</th>
          <th>Fitxer</th>
          <th>Accions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="record in importRecords" :key="record.id">
          <td>{{ record.id }}</td>
          <td>{{ record.created_at }}</td>
          <td>{{ record.total_importats }}</td>
          <td>{{ record.total_errors }}</td>
          <td>{{ record.fitxer || '—' }}</td>
          <td>
            <button class="btn" @click="viewRecord(record.id)">Veure Detall</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const API_URL = 'http://127.0.0.1:8000/api';

const router = useRouter();
const importRecords = ref<any[]>([]);

onMounted(async () => {
  try {
    const response = await axios.get(`${API_URL}/importacions`, {
      headers: { Authorization: `Bearer ${localStorage.getItem('userToken')}` },
    });
    importRecords.value = response.data;

  } catch (error) {
    console.error('Error carregant els registres d’importació:', error);
  }
});

function viewRecord(id: number) {
  router.push({ name: 'ImportRecordDetail', params: { id } });
}
</script>

<style scoped>
.import-record-index {
  padding: 20px;
}
.import-record-index h1 {
  margin-bottom: 20px;
}
.import-record-index table {
  width: 100%;
  border-collapse: collapse;
}
.import-record-index th,
.import-record-index td {
  border: 1px solid #ccc;
  padding: 8px;
  text-align: left;
}
</style>
