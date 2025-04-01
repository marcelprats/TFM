<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import axios from "axios";
import { useRouter } from "vue-router";
import ProductImportWizard from "../components/ProductImportWizard.vue";
import * as XLSX from "xlsx";
import { saveAs } from "file-saver";

const API_URL = "http://127.0.0.1:8000/api";

// Interfaces
interface Botiga {
  id: number;
  nom: string;
}

interface Category {
  id: number;
  nom: string;
  slug: string;
  parent_id: number | null;
}

interface Producte {
  id: number;
  nom: string;
  descripcio: string;
  preu: number;
  stock: number;
  imatge?: string;
  categoria: number | null;     // id de la categoria (convertit a number)
  subcategoria: number | null;  // id de la subcategoria (convertit a number)
  botiga_id: number | null;
  botiga?: { nom: string };
  botiga_nom?: string;
}

// Variables reactives
const productes = ref<Producte[]>([]);
const botigues = ref<Botiga[]>([]);
const categories = ref<Category[]>([]);

const newProduct = ref({
  nom: "",
  descripcio: "",
  preu: 0,
  stock: 0,
  categoria: null as number | null,
  subcategoria: null as number | null,
  imatge: "",
  botiga_id: null as number | null,
});

const editProduct = ref<{
  id: number;
  nom: string;
  descripcio: string;
  preu: number;
  stock: number;
  categoria: number | null;
  subcategoria: number | null;
  imatge: string;
  botiga_id: number | null;
} | null>(null);

const showAddModal = ref(false);
const showEditModal = ref(false);
const errorMessage = ref("");
const searchQuery = ref("");
const showDeleteModal = ref(false);
const deleteProductId = ref<number | null>(null);
const showImportWizard = ref(false);

const sortColumn = ref("");
const sortDirection = ref("asc");
const columnFilters = ref<{ [key: string]: string[] }>({});
const showFilters = ref<{ [key: string]: boolean }>({});

const imageFile = ref<File | null>(null);

const router = useRouter();

// Carregar productes des de l'API
const fetchProductes = async () => {
  try {
    const token = localStorage.getItem("userToken");
    if (!token) return;
    const response = await axios.get(`${API_URL}/productes`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    productes.value = response.data.map((producte: any) => ({
      ...producte,
      preu: Number(producte.preu),
      botiga_nom: producte.botiga?.nom ?? "No assignada",
      // Convertim els camps categoria i subcategoria a número si tenen valor
      categoria: producte.categoria ? Number(producte.categoria) : null,
      subcategoria: producte.subcategoria ? Number(producte.subcategoria) : null,
    }));
  } catch (error) {
    console.error("Error carregant productes:", error);
  }
};

// Carregar botigues
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

// Carregar categories
const fetchCategories = async () => {
  try {
    const token = localStorage.getItem("userToken");
    if (!token) return;
    const response = await axios.get(`${API_URL}/categories`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    categories.value = response.data;
  } catch (error) {
    console.error("Error carregant categories:", error);
  }
};

// Funció per afegir producte amb FormData
const addProducte = async () => {
  try {
    const token = localStorage.getItem("userToken");
    if (!token) return;
    
    const formData = new FormData();
    formData.append("nom", newProduct.value.nom);
    formData.append("descripcio", newProduct.value.descripcio);
    formData.append("preu", newProduct.value.preu.toString());
    formData.append("stock", newProduct.value.stock.toString());
    formData.append("categoria", newProduct.value.categoria?.toString() || "");
    formData.append("subcategoria", newProduct.value.subcategoria?.toString() || "");
    formData.append("botiga_id", newProduct.value.botiga_id?.toString() || "");
    if (imageFile.value) {
      formData.append("imatge", imageFile.value);
    } else {
      formData.append("imatge", newProduct.value.imatge);
    }
    
    await axios.post(`${API_URL}/productes`, formData, {
      headers: { 
        Authorization: `Bearer ${token}`,
        "Content-Type": "multipart/form-data"
      },
    });
    
    newProduct.value = {
      nom: "",
      descripcio: "",
      preu: 0,
      stock: 0,
      categoria: null,
      subcategoria: null,
      imatge: "",
      botiga_id: null,
    };
    imageFile.value = null;
    showAddModal.value = false;
    fetchProductes();
  } catch (error) {
    errorMessage.value = "Error afegint producte.";
    console.error("Error addProducte:", error);
  }
};

// Funció per obrir el modal d'edició
const openEditProduct = (producte: any) => {
  editProduct.value = {
    id: producte.id,
    nom: producte.nom,
    descripcio: producte.descripcio,
    preu: producte.preu,
    stock: producte.stock,
    categoria: producte.categoria || null,
    subcategoria: producte.subcategoria || null,
    imatge: producte.imatge || "",
    botiga_id: producte.botiga?.id ?? null,
  };
  showEditModal.value = true;
};

// Funció per actualitzar producte
const updateProducte = async () => {
  console.log("updateProducte triggered");
  if (editProduct.value) {
    try {
      const token = localStorage.getItem("userToken");
      if (!token) return;

      const formData = new FormData();
      // Mètode override perquè Laravel processi correctament el multipart en PUT
      formData.append("_method", "PUT");

      // Afegeix els camps obligatoris
      formData.append("nom", editProduct.value.nom);
      formData.append("descripcio", editProduct.value.descripcio);
      formData.append("preu", editProduct.value.preu.toString());
      formData.append("stock", editProduct.value.stock.toString());
      formData.append("categoria", editProduct.value.categoria ? editProduct.value.categoria.toString() : "");

      // Només afegeix subcategoria si té valor (altrament, es deixa fora perquè Laravel ho tractarà com a null)
      if (editProduct.value.subcategoria) {
        formData.append("subcategoria", editProduct.value.subcategoria.toString());
      }

      formData.append("botiga_id", editProduct.value.botiga_id ? editProduct.value.botiga_id.toString() : "");

      // Gestiona la imatge: si s'ha seleccionat un fitxer, l'envia; si no, si existeix una URL, l'envia; si està buit, no l'envia
      if (imageFile.value) {
        formData.append("imatge", imageFile.value);
      } else if (editProduct.value.imatge && editProduct.value.imatge !== "") {
        formData.append("imatge", editProduct.value.imatge);
      }

      // Log de depuració: mostra el contingut del FormData
      for (let [key, value] of formData.entries()) {
        console.log(key, value);
      }

      // Envía la petició amb POST i el mètode override
      await axios.post(`${API_URL}/productes/${editProduct.value.id}`, formData, {
        headers: { 
          Authorization: `Bearer ${token}`,
          "Content-Type": "multipart/form-data"
        },
      });

      console.log("PUT (via POST) request completed successfully");
      showEditModal.value = false;
      imageFile.value = null;
      fetchProductes();
    } catch (error: any) {
      console.error("Error updating product:", error.response || error);
      errorMessage.value = "Error actualitzant producte.";
    }
  }
};

// Funció per eliminar producte
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
      fetchProductes();
    } catch (error) {
      errorMessage.value = "Error eliminant producte.";
    }
  }
};

const exportTableData = () => {
  // Mapeja les dades dels productes a un format pla
  const data = productes.value.map(prod => ({
    ID: prod.id,
    Nom: prod.nom,
    Descripcio: prod.descripcio,
    Preu: prod.preu,
    Stock: prod.stock,
    Imatge: prod.imatge,
    Categoria: prod.categoria,
    Subcategoria: prod.subcategoria,
    Botiga: prod.botiga_nom
  }));

  // Crea una worksheet i un workbook amb XLSX
  const worksheet = XLSX.utils.json_to_sheet(data);
  const workbook = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(workbook, worksheet, "Productes");

  // Escriu el workbook i dispara la descàrrega
  const wbout = XLSX.write(workbook, { bookType: 'xlsx', type: 'array' });
  saveAs(new Blob([wbout]), "productes.xlsx");
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

const sortProducts = (column: string) => {
  if (sortColumn.value === column) {
    sortDirection.value = sortDirection.value === "asc" ? "desc" : "asc";
  } else {
    sortColumn.value = column;
    sortDirection.value = "asc";
  }
};

const sortedProducts = computed(() => {
  return [...productes.value]
    .filter(producte => {
      return Object.keys(columnFilters.value).every(key => {
        return columnFilters.value[key].length === 0 || columnFilters.value[key].includes((producte as any)[key]);
      });
    })
    .sort((a, b) => {
      if (!sortColumn.value) return 0;
      const valueA = (a as any)[sortColumn.value];
      const valueB = (b as any)[sortColumn.value];
      if (typeof valueA === "string" && typeof valueB === "string") {
        return sortDirection.value === "asc" ? valueA.localeCompare(valueB) : valueB.localeCompare(valueA);
      } else if (typeof valueA === "number" && typeof valueB === "number") {
        return sortDirection.value === "asc" ? valueA - valueB : valueB - valueA;
      }
      return 0;
    });
});

const toggleFilterDropdown = (column: string) => {
  showFilters.value[column] = !showFilters.value[column];
};

const toggleColumnFilter = (column: string, value: string) => {
  if (!columnFilters.value[column]) columnFilters.value[column] = [];
  if (columnFilters.value[column].includes(value)) {
    columnFilters.value[column] = columnFilters.value[column].filter(v => v !== value);
  } else {
    columnFilters.value[column].push(value);
  }
};

// Computed per obtenir les categories principals (sense parent)
const parentCategories = computed(() => {
  return categories.value.filter(cat => cat.parent_id === null);
});

// Funció per obtenir les subcategories d'una categoria principal
const getSubcategories = (categoryId: number | null) => {
  if (!categoryId) return [];
  return categories.value.filter(cat => cat.parent_id === categoryId);
};

const onFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    imageFile.value = target.files[0];
    // Genera una URL temporal per a la previsualització
    if (showAddModal.value) {
      newProduct.value.imatge = URL.createObjectURL(imageFile.value);
    } else if (showEditModal.value && editProduct.value) {
      editProduct.value.imatge = URL.createObjectURL(imageFile.value);
    }
  }
};

onMounted(() => {
  fetchProductes();
  fetchBotigues();
  fetchCategories();
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
        <button class="add-btn" @click="exportTableData">📤 Exportar Dades</button>
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
          <th>Subcategoria</th>
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
          <td>
            <span v-if="producte.categoria">
              {{ categories.find(cat => cat.id === producte.categoria)?.nom || '—' }}
            </span>
            <span v-else>—</span>
          </td>
          <td>
            <span v-if="producte.subcategoria">
              {{ categories.find(cat => cat.id === producte.subcategoria)?.nom || '—' }}
            </span>
            <span v-else>—</span>
          </td>
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
    <!-- Modal per afegir producte -->
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
              <td><strong>Categoria:</strong></td>
              <td>
                <select v-model="newProduct.categoria">
                  <option disabled :value="null">Selecciona una categoria</option>
                  <option v-for="cat in parentCategories" :key="cat.id" :value="cat.id">
                    {{ cat.nom }}
                  </option>
                </select>
              </td>
            </tr>
            <tr>
              <td><strong>Subcategoria:</strong></td>
              <td>
                <select v-model="newProduct.subcategoria">
                  <option disabled :value="null">Selecciona una subcategoria</option>
                  <option v-for="subcat in getSubcategories(newProduct.categoria)" :key="subcat.id" :value="subcat.id">
                    {{ subcat.nom }}
                  </option>
                </select>
              </td>
            </tr>
            <tr>
              <td><strong>Imatge:</strong></td>
              <td>
                <input type="file" @change="onFileChange" />
                <br>
                <input v-model="newProduct.imatge" placeholder="O bé, introdueix una URL" />
              </td>
            </tr>
            <tr>
              <td><strong>Botiga:</strong></td>
              <td>
                <select v-model="newProduct.botiga_id">
                  <option disabled value="">Selecciona una botiga</option>
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
    <!-- Modal per editar producte -->
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
              <td><strong>Categoria:</strong></td>
              <td>
                <select v-model="editProduct.categoria">
                  <option disabled :value="null">Selecciona una categoria</option>
                  <option v-for="cat in parentCategories" :key="cat.id" :value="cat.id">
                    {{ cat.nom }}
                  </option>
                </select>
              </td>
            </tr>
            <tr>
              <td><strong>Subcategoria:</strong></td>
              <td>
                <select v-model="editProduct.subcategoria">
                  <option disabled :value="null">Selecciona una subcategoria</option>
                  <option v-for="subcat in getSubcategories(editProduct.categoria)" :key="subcat.id" :value="subcat.id">
                    {{ subcat.nom }}
                  </option>
                </select>
              </td>
            </tr>
            <tr>
              <td><strong>Imatge:</strong></td>
              <td>
                <input type="file" @change="onFileChange" />
                <br>
                <input v-model="editProduct.imatge" placeholder="O bé, introdueix una URL" />
              </td>
            </tr>
            <tr>
              <td><strong>Botiga:</strong></td>
              <td>
                <select v-model="editProduct.botiga_id">
                  <option disabled value="">Selecciona una botiga</option>
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
    <!-- Modal per confirmar eliminació -->
    <div v-if="showDeleteModal" class="modal">
      <div class="modal-content">
        <p>Segur que vols eliminar aquest producte?</p>
        <button @click="deleteConfirmedProduct" class="delete-btn">Sí, eliminar</button>
        <button @click="showDeleteModal = false" class="confirm-btn">Cancel·lar</button>
      </div>
    </div>
    <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
  </div>
</template>

<style scoped>
.container {
  min-height: 80vh;
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

.producte-table th,
.producte-table td {
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
  margin-left: 20px;
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

.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.5);
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

.error {
  color: red;
  margin-top: 20px;
}
</style>
