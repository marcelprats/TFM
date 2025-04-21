<template>
  <div class="import-record-index">
    <h1>Registres d'importació</h1>
    <table>
      <thead>
        <tr>
          <!-- Columna d'ordre fixa (numeració) -->
          <th>Nº</th>
          <th @click="sortBy('created_at')">
            Data <span v-if="sortColumn==='created_at'">{{ sortDirection==='asc' ? '▲' : '▼' }}</span>
          </th>
          <th @click="sortBy('total_importats')">
            Total Importats <span v-if="sortColumn==='total_importats'">{{ sortDirection==='asc' ? '▲' : '▼' }}</span>
          </th>
          <th @click="sortBy('total_errors')">
            Total Errors <span v-if="sortColumn==='total_errors'">{{ sortDirection==='asc' ? '▲' : '▼' }}</span>
          </th>
          <th @click="sortBy('fitxer')">
            Fitxer <span v-if="sortColumn==='fitxer'">{{ sortDirection==='asc' ? '▲' : '▼' }}</span>
          </th>
          <th>Accions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(record, index) in sortedRecords" :key="record.id">
          <!-- Utilitza l'índex per mostrar la numeració fixa -->
          <td>{{ index + 1 }}</td>
          <td>{{ formatDate(record.created_at) }}</td>
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
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import dayjs from 'dayjs';

const API_URL = 'http://127.0.0.1:8000/api';

const router = useRouter();
const importRecords = ref<any[]>([]);

// Variables per a l'ordenació
const sortColumn = ref<string>('');
const sortDirection = ref<string>('asc');

onMounted(async () => {
  try {
    const token = localStorage.getItem('userToken');
    const response = await axios.get(`${API_URL}/vendor/importacions`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    importRecords.value = response.data;
  } catch (error) {
    console.error('Error carregant els registres d’importació:', error);
  }
});

// Funció per ordenar per columna
function sortBy(column: string) {
  if (sortColumn.value === column) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortColumn.value = column;
    sortDirection.value = 'asc';
  }
}

const sortedRecords = computed(() => {
  if (!sortColumn.value) return importRecords.value;
  return importRecords.value.slice().sort((a, b) => {
    let aVal = a[sortColumn.value];
    let bVal = b[sortColumn.value];
    if (typeof aVal === 'number' && typeof bVal === 'number') {
      return sortDirection.value === 'asc' ? aVal - bVal : bVal - aVal;
    }
    aVal = aVal ? aVal.toString() : '';
    bVal = bVal ? bVal.toString() : '';
    return sortDirection.value === 'asc'
      ? aVal.localeCompare(bVal)
      : bVal.localeCompare(aVal);
  });
});

// Mètode per formatejar la data
function formatDate(dateStr: string): string {
  return dayjs(dateStr).format('DD/MM/YYYY HH:mm');
}

function viewRecord(id: number) {
  router.push(`/import-record/${id}`);
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

.import-record-index th {
  background-color: #42b983;
  color: #fff;
  cursor: pointer;
}

.btn {
  background-color: #42b983;
  color: #fff;
  padding: 8px 12px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.2s ease;
}

.btn:hover {
  background-color: #368c6e;
}
</style>
