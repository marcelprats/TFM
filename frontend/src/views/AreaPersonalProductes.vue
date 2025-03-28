<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import { useRouter } from "vue-router";
import ProductImportWizard from "../components/ProductImportWizard.vue";

const API_URL = "http://127.0.0.1:8000/api";
const productes = ref<{ id: number; nom: string; descripcio: string; preu: number; stock: number; imatge?: string; categoria?: string; botiga_id: number; botiga?: { nom: string } }[]>([]);
const botigues = ref<{ id: number; nom: string }[]>([]);
const newProduct = ref({ nom: "", descripcio: "", preu: 0, stock: 0, botiga_id: null });
const editProduct = ref<{ id: number; nom: string; descripcio: string; preu: number; stock: number; botiga_id: number } | null>(null);
const showAddModal = ref(false);
const showEditModal = ref(false);
const errorMessage = ref("");
const searchQuery = ref("");
const showDeleteModal = ref(false);
const deleteProductId = ref<number | null>(null);
const showImportWizard = ref(false);

const sortColumn = ref(""); // Guarda la columna que s'està ordenant
const sortDirection = ref("asc"); // Direcció inicial ascendent
const columnFilters = ref<{ [key: string]: string[] }>({}); // Filtres per columna
const showFilters = ref<{ [key: string]: boolean }>({}); // Controla visibilitat dels filtres


const fetchProductes = async () => {
  try {
    const token = localStorage.getItem("userToken");
    if (!token) return;

    const response = await axios.get(`${API_URL}/productes`, {
      headers: { Authorization: `Bearer ${token}` },
    });

    productes.value = response.data.map(producte => ({
      ...producte,
      preu: Number(producte.preu),
      botiga_nom: producte.botiga?.nom ?? "No assignada"
    }));


  } catch (error) {
    console.error("Error carregant productes:", error);
  }
};


const router = useRouter();

const fetchBotigues = async () => {
  try {
    const token = localStorage.getItem("userToken");
    const response = await axios.get(`${API_URL}/botigues-mes`, {
      headers: { Authorization: `Bearer ${token}` },
    });

    botigues.value = response.data;
  } catch (error) {
    console.error("Error carregant botigues:", error);
  }
};

const addProducte = async () => {
  try {
    const token = localStorage.getItem("userToken");
    await axios.post(`${API_URL}/productes`, newProduct.value, {
      headers: { Authorization: `Bearer ${token}` },
    });

    newProduct.value = { nom: "", descripcio: "", preu: 0, stock: 0, botiga_id: null };
    showAddModal.value = false;
    fetchProductes();
  } catch (error) {
    errorMessage.value = "Error afegint producte.";
  }
};

const openEditProduct = (producte: any) => {
  editProduct.value = {
    id: producte.id,
    nom: producte.nom,
    descripcio: producte.descripcio,
    preu: producte.preu,
    stock: producte.stock,
    botiga_id: producte.botiga?.id ?? null,
  };
  showEditModal.value = true;
};


const updateProducte = async () => {
  if (editProduct.value) {
    try {
      const token = localStorage.getItem("userToken");
      await axios.put(`${API_URL}/productes/${editProduct.value.id}`, editProduct.value, {
        headers: { Authorization: `Bearer ${token}` },
      });

      showEditModal.value = false;
      fetchProductes();
    } catch (error) {
      errorMessage.value = "Error actualitzant producte.";
    }
  }
};

const deleteProducte = async (id: number) => {
  try {
    const token = localStorage.getItem("userToken");
    await axios.delete(`${API_URL}/productes/${id}`, {
      headers: { Authorization: `Bearer ${token}` },
    });

    fetchProductes();
  } catch (error) {
    errorMessage.value = "Error eliminant producte.";
  }
};

const goToBotigues = () => {
  router.push("/area-personal-botigues");
};

const filteredProductes = computed(() => {
  return productes.value.filter(producte =>
    producte.nom.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    producte.descripcio.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

const confirmDeleteProduct = (id: number) => {
  deleteProductId.value = id;
  showDeleteModal.value = true;
};

const deleteConfirmedProduct = async () => {
  if (deleteProductId.value !== null) {
    try {
      const token = localStorage.getItem("userToken");
      await axios.delete(`${API_URL}/productes/${deleteProductId.value}`, {
        headers: { Authorization: `Bearer ${token}` },
      });

      showDeleteModal.value = false;
      fetchProductes(); // 🔹 Refresca la llista de productes
    } catch (error) {
      errorMessage.value = "Error eliminant producte.";
    }
  }
};

const sortPreu = () => {
  if (sortColumn.value !== "preu") {
    sortColumn.value = "preu";
    sortDirection.value = "asc";
  } else {
    sortDirection.value = sortDirection.value === "asc" ? "desc" : "asc";
  }

  productes.value = [...productes.value].sort((a, b) => 
    sortDirection.value === "asc" ? a.preu - b.preu : b.preu - a.preu
  );
};


// Funció per ordenar les columnes
const sortProducts = (column: string) => {
  if (sortColumn.value === column) {
    sortDirection.value = sortDirection.value === "asc" ? "desc" : "asc";
  } else {
    sortColumn.value = column;
    sortDirection.value = "asc";
  }
};

// Computed per ordenar i filtrar productes
const sortedProducts = computed(() => {
  return [...productes.value]
    .filter(producte => {
      return Object.keys(columnFilters.value).every(key => {
        return columnFilters.value[key].length === 0 || columnFilters.value[key].includes(producte[key]);
      });
    })
    .sort((a, b) => {
      if (!sortColumn.value) return 0;
      const valueA = a[sortColumn.value];
      const valueB = b[sortColumn.value];

      if (typeof valueA === "string" && typeof valueB === "string") {
        return sortDirection.value === "asc" ? valueA.localeCompare(valueB) : valueB.localeCompare(valueA);
      } else if (typeof valueA === "number" && typeof valueB === "number") {
        return sortDirection.value === "asc" ? valueA - valueB : valueB - valueA;
      }
      return 0;
    });
});

// Funció per mostrar / amagar filtres
const toggleFilterDropdown = (column: string) => {
  showFilters.value[column] = !showFilters.value[column];
};

// Funció per aplicar filtres a les columnes
const toggleColumnFilter = (column: string, value: string) => {
  if (!columnFilters.value[column]) columnFilters.value[column] = [];
  if (columnFilters.value[column].includes(value)) {
    columnFilters.value[column] = columnFilters.value[column].filter(v => v !== value);
  } else {
    columnFilters.value[column].push(value);
  }
};


onMounted(() => {
  fetchProductes();
  fetchBotigues();
});
</script>

<template>
  <div class="container">
    <h1>Gestió de Productes</h1>

    <div class="search-container">
      <input v-model="searchQuery" placeholder="Cerca producte..." />
      <button class="add-btn" @click="showAddModal = true">➕ Afegir Producte</button>
      <button class="add-btn" @click="goToBotigues">Botigues</button>
      <div>
        <button class="add-btn" @click="showImportWizard = true">📥 Importar Excel</button>
        <ProductImportWizard
          v-if="showImportWizard"
          :botigues="botigues"
          @close="showImportWizard = false"
          @refresh="fetchProductes"
        />
      </div>
    </div>

    <table class="producte-table">
      <thead>
        <tr>
          <th @click="sortProducts('nom')">
            Nom
            <button @click.stop="toggleFilterDropdown('nom')">🔍</button>
            <div v-if="showFilters['nom']" class="filter-dropdown">
              <div v-for="name in [...new Set(productes.map(p => p.nom))]" :key="name">
                <input type="checkbox" :checked="columnFilters['nom']?.includes(name)" @change="toggleColumnFilter('nom', name)" />
                {{ name }}
              </div>
            </div>
          </th>
          <th @click="sortProducts('descripcio')">Descripció</th>
          <th @click="sortPreu()">Preu (€)</th>
          <th @click="sortProducts('stock')">Stock</th>
          <th>Categoria</th>
          <th>Imatge</th>
          <th @click="sortProducts('botiga_nom')">
            Botiga
            <button @click.stop="toggleFilterDropdown('botiga_nom')">🔍</button>
            <div v-if="showFilters['botiga_nom']" class="filter-dropdown">
              <div v-for="botiga in [...new Set(productes.map(p => p.botiga_nom))]" :key="botiga">
                <input type="checkbox" :checked="columnFilters['botiga_nom']?.includes(botiga)" @change="toggleColumnFilter('botiga_nom', botiga)" />
                {{ botiga }}
              </div>
            </div>
          </th>
          <th>Accions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="producte in sortedProducts" :key="producte.id">
          <td>
            <router-link :to="{ name: 'Producte', params: { id: producte.id } }">
              {{ producte.nom }}
            </router-link>
          </td>
          <td>{{ producte.descripcio }}</td>
          <td>{{ producte.preu }}</td>
          <td>{{ producte.stock }}</td>
          <td>{{ producte.categoria || '—' }}</td> <!-- Nova columna -->
          <td>
            <img v-if="producte.imatge" :src="producte.imatge" alt="Imatge" width="40" height="40" />
            <span v-else>—</span>
          </td>
          <td>{{ producte.botiga_nom }}</td>
          <td class="actions">
            <button class="edit-btn" @click="openEditProduct(producte)">✏️ Editar</button>
            <button class="delete-btn" @click="confirmDeleteProduct(producte.id)">🗑️ Eliminar</button>
          </td>
        </tr>
      </tbody>
    </table>


<!-- Finestra modal per afegir producte -->
<div v-if="showAddModal" class="modal">
  <div class="modal-content">
    <h3>Afegir Producte</h3>

    <table class="modal-table">
      <tbody>
        <tr>
          <td><strong>Nom:</strong></td>
          <td><input v-model="newProduct.nom" placeholder="Nom del producte" /></td>
        </tr>
        <tr>
          <td><strong>Descripció:</strong></td>
          <td><textarea v-model="newProduct.descripcio" placeholder="Descripció"></textarea></td>
        </tr>
        <tr>
          <td><strong>Preu:</strong></td>
          <td><input v-model="newProduct.preu" type="number" placeholder="Preu (€)" /></td>
        </tr>
        <tr>
          <td><strong>Stock:</strong></td>
          <td><input v-model="newProduct.stock" type="number" placeholder="Quantitat" /></td>
        </tr>
        <tr>
          <td><strong>Botiga:</strong></td>
          <td>
            <select v-model="newProduct.botiga_id">
              <option v-for="botiga in botigues" :key="botiga.id" :value="botiga.id">
                {{ botiga.nom }}
              </option>
            </select>
          </td>
        </tr>
      </tbody>
    </table>

    <div class="modal-actions">
      <button @click="addProducte" class="confirm-btn">💾 Desa</button>
      <button @click="showAddModal = false" class="delete-btn">❌ Cancel·lar</button>
    </div>
  </div>
</div>

<!-- Finestra modal per editar producte -->
<div v-if="showEditModal && editProduct" class="modal">
  <div class="modal-content">
    <h3>Editar Producte</h3>

    <table class="modal-table">
      <tbody>
        <tr>
          <td><strong>Nom:</strong></td>
          <td><input v-model="editProduct.nom" placeholder="Nom del producte" /></td>
        </tr>
        <tr>
          <td><strong>Descripció:</strong></td>
          <td><textarea v-model="editProduct.descripcio" placeholder="Descripció"></textarea></td>
        </tr>
        <tr>
          <td><strong>Preu:</strong></td>
          <td><input v-model="editProduct.preu" type="number" placeholder="Preu (€)" /></td>
        </tr>
        <tr>
          <td><strong>Stock:</strong></td>
          <td><input v-model="editProduct.stock" type="number" placeholder="Quantitat" /></td>
        </tr>
        <tr>
          <td><strong>Botiga:</strong></td>
          <td>
            <select v-model="editProduct.botiga_id">
              <option v-for="botiga in botigues" :key="botiga.id" :value="botiga.id">
                {{ botiga.nom }}
              </option>
            </select>
          </td>
        </tr>
      </tbody>
    </table>

    <div class="modal-actions">
      <button @click="updateProducte" class="confirm-btn">💾 Desa</button>
      <button @click="showEditModal = false" class="delete-btn">❌ Cancel·lar</button>
    </div>
  </div>
</div>

<!-- Finestra modal de confirmació d'eliminació -->
<div v-if="showDeleteModal" class="modal">
  <div class="modal-content">
    <p>Segur que vols eliminar aquest producte?</p>
    <button @click="deleteConfirmedProduct" class="delete-btn" >Sí, eliminar</button>
    <button @click="showDeleteModal = false" class="confirm-btn">Cancel·lar</button>
  </div>
</div>

    <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
  </div>
</template>


<style scoped>
.container {
  min-height: 80vh; /* Ajusta segons sigui necessari */
  margin: auto;
  padding: 20px;
  text-align: center;
}

.add-btn {
  background: #42b983;
  color: #f9f9f9;
  border: none;
  padding: 8px 12px;
  border-radius: 5px;
  cursor: pointer;
  margin: 10px 0;
}

.add-btn:hover {
  background: #f9f9f9;
  color: #368c6e;
  outline: 2px solid #368c6e;
}

.producte-table {

  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
  text-align: left;
}

.producte-table th, .producte-table td {
  padding: 10px;
  border: 1px solid #ddd;
}

.producte-table th {
  background: #42b983;
  color: #f9f9f9;
  cursor: pointer;
  position: relative;
  padding: 10px;
}

.producte-table th button {
  background: none;
  border: none;
  cursor: pointer;
  color: white;
  margin-left: 5px;
}

.filter-dropdown {
  position: absolute;
  background: #42b983;
  border: 1px solid #ddd;
  padding: 10px;
  width: 150px;
  top: 100%;
  left: 0;
  z-index: 10;
}

.filter-dropdown div {
  display: flex;
  align-items: center;
}

.filter-dropdown input {
  margin-right: 5px;
}



.producte-table tr:nth-child(even) {
  background: #f9f9f9;
}

.producte-table tr:hover {
  background: #e3f2fd;
}

.actions {
  text-align: center;
  gap: 10px;
  
}

.edit-btn {
  background: #f0ad4e;
  color: #f9f9f9;
  margin-right: 20px;
  border: none;
}

.edit-btn:hover {
  background: #f9f9f9;
  color: #f0ad4e;
  outline: 2px solid #f0ad4e;
}

.delete-btn {
  background: #d9534f;
  color: #f9f9f9;
  border: none;
}

.delete-btn:hover {
  background: #f9f9f9;
  color: #d9534f;
  outline: 2px solid #d9534f;
}

.confirm-btn {
  background: #42b983;
  color: white;
  margin-left:20px;
  border: none;
}

.confirm-btn:hover {
  background: #f9f9f9;
  color: #42b983;
  outline: 2px solid #42b983;
}

.search-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.search-container input {
  padding: 8px;
  width: 40%;
  border: 1px solid #42b983;
  border-radius: 5px;
  outline: none;
  font-size: 16px;
  transition: border 0.3s ease-in-out;
}

.search-container input:focus {
  border-color: #368c6e;
}


/* Finestra modal */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
}

.modal-content {
  background: white;
  padding: 20px;
  border-radius: 10px;
  text-align: center;
  width: 450px;
}

.modal-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 15px;
}

.modal-table td {
  padding: 8px;
  vertical-align: middle;
}

.modal-table input,
.modal-table textarea {
  width: 100%;
  padding: 6px;
  border: 1px solid #ccc;
  border-radius: 5px;
  font-size: 14px;
}

.modal-table textarea {
  height: 80px;
  resize: none;
}

.modal-actions {
  display: flex;
  justify-content: space-between;
  margin-top: 15px;
}
</style>
