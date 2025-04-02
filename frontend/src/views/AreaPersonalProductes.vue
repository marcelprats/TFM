<template>
  <div class="container">
    <!-- Capçalera i barra superior -->
    <header class="page-header">
      <h1>Gestió de Productes</h1>
      <div class="top-bar">
        <div class="search-filters">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cerca producte..."
            class="search-input"
          />
          <button class="btn filter-btn" @click="toggleFiltersPanel">
            Filtres
          </button>
        </div>
        <div class="actions">
          <button class="btn add-btn" @click="showAddModal = true">
            ➕ Afegir Producte
          </button>
          <button class="btn" @click="goToBotigues">Botigues</button>
          <button class="btn" @click="showImportWizard = true">
            📥 Importar Excel
          </button>
          <button class="btn" @click="goToRegistreImportacio">Registre d'importació</button>
          <button class="btn" @click="exportTableData">
            📤 Exportar Dades
          </button>
        </div>
      </div>

      <!-- Panell de filtres -->
      <transition name="fade">
        <div v-if="filtersPanelVisible && filtersReady" class="filters-panel">
          <div class="filters-grid">
            <!-- Filtrar per Nom -->
            <div class="filter-item">
              <label>{{ capitalize("nom") }}</label>
              <select multiple v-model="columnFilters.nom" class="input-field filter-multi">
                <option v-for="val in uniqueValues('nom')" :key="val" :value="val">
                  {{ val }}
                </option>
              </select>
            </div>
            <!-- Filtrar per Preu -->
            <div class="filter-item">
              <label>{{ capitalize("preu") }}</label>
              <div class="range-wrapper">
                <input
                  type="number"
                  v-model.number="columnFilters.preu.min"
                  :placeholder="`Min: ${preuMin}`"
                  class="input-field filter-range"
                />
                <span class="range-sep">-</span>
                <input
                  type="number"
                  v-model.number="columnFilters.preu.max"
                  :placeholder="`Max: ${preuMax}`"
                  class="input-field filter-range"
                />
              </div>
            </div>
            <!-- Filtrar per Stock -->
            <div class="filter-item">
              <label>{{ capitalize("stock") }}</label>
              <div class="range-wrapper">
                <input
                  type="number"
                  v-model.number="columnFilters.stock.min"
                  :placeholder="`Min: ${stockMin}`"
                  class="input-field filter-range"
                />
                <span class="range-sep">-</span>
                <input
                  type="number"
                  v-model.number="columnFilters.stock.max"
                  :placeholder="`Max: ${stockMax}`"
                  class="input-field filter-range"
                />
              </div>
            </div>
            <!-- Filtrar per Categoria -->
            <div class="filter-item">
              <label>{{ capitalize("categoria") }}</label>
              <select multiple v-model="columnFilters.categoria" class="input-field filter-multi">
                <option
                  v-for="cat in parentCategories"
                  :key="cat.id"
                  :value="String(cat.id)"
                >
                  {{ cat.nom }}
                </option>
              </select>
            </div>
            <!-- Filtrar per Subcategoria -->
            <div class="filter-item">
              <label>{{ capitalize("subcategoria") }}</label>
              <select multiple v-model="columnFilters.subcategoria" class="input-field filter-multi">
                <option
                  v-for="sub in categories.filter(c => c.parent_id !== null)"
                  :key="sub.id"
                  :value="String(sub.id)"
                >
                  {{ sub.nom }}
                </option>
              </select>
            </div>
            <!-- Filtrar per Botiga -->
            <div class="filter-item">
              <label>{{ capitalize("botiga_nom") }}</label>
              <select multiple v-model="columnFilters.botiga_nom" class="input-field filter-multi">
                <option v-for="val in uniqueValues('botiga_nom')" :key="val" :value="val">
                  {{ val }}
                </option>
              </select>
            </div>
          </div>
          <div class="filters-actions">
            <button class="btn apply-btn" @click="applyFilters">Aplica Filtres</button>
            <button class="btn clear-btn" @click="clearFilters">Neteja Filtres</button>
          </div>
        </div>
      </transition>
    </header>

    <!-- Bulk actions -->
    <div v-if="selectedProducts.length" class="bulk-actions">
      <span>{{ selectedProducts.length }} productes seleccionats</span>
      <button class="btn bulk-btn" @click="bulkDelete">
        Elimina seleccionats
      </button>
      <button class="btn bulk-btn" @click="openBulkUpdateModal">
        Actualitza seleccionats
      </button>
    </div>

    <!-- Taula de productes -->
    <table class="producte-table">
      <thead>
        <tr>
          <th>
            <input
              type="checkbox"
              @change="toggleSelectAll"
              :checked="allSelected"
            />
          </th>
          <th @click="sortProducts('nom')">
            Nom <span v-if="sortColumn==='nom'">({{ sortDirection }})</span>
          </th>
          <th @click="sortProducts('descripcio')">
            Descripció <span v-if="sortColumn==='descripcio'">({{ sortDirection }})</span>
          </th>
          <th @click="sortProducts('preu')">
            Preu (€) <span v-if="sortColumn==='preu'">({{ sortDirection }})</span>
          </th>
          <th @click="sortProducts('stock')">
            Stock <span v-if="sortColumn==='stock'">({{ sortDirection }})</span>
          </th>
          <th>Categoria</th>
          <th>Subcategoria</th>
          <th @click="sortProducts('botiga_nom')">
            Botiga <span v-if="sortColumn==='botiga_nom'">({{ sortDirection }})</span>
          </th>
          <th>Imatge</th>
          <th>Accions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="prod in sortedProducts" :key="prod.id">
          <td>
            <input
              type="checkbox"
              :value="prod.id"
              v-model="selectedProducts"
            />
          </td>
          <!-- El nom com a enllaç al detall del producte -->
          <td>
            <router-link :to="{ name: 'Producte', params: { id: prod.id } }" class="product-link">
              {{ prod.nom }}
            </router-link>
          </td>
          <td>{{ prod.descripcio || "—" }}</td>
          <td>{{ prod.preu }}</td>
          <td>{{ prod.stock }}</td>
          <td>{{ categoryName(prod.categoria) }}</td>
          <td>{{ subcategoryName(prod.subcategoria) }}</td>
          <td>{{ prod.botiga_nom }}</td>
          <td>
            <img v-if="prod.imatge" :src="prod.imatge" alt="Imatge" width="40" height="40" />
            <span v-else>—</span>
          </td>
          <td class="actions">
            <button class="btn edit-btn" @click="openEditProduct(prod)">
              ✏️ Editar
            </button>
            <button class="btn delete-btn" @click="confirmDeleteProduct(prod.id)">
              🗑️ Eliminar
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Modal per Afegir Producte -->
    <div v-if="showAddModal" class="modal" @click.self="showAddModal = false">
      <div class="modal-content" @click.stop>
        <h3>Afegir Producte</h3>
        <div class="form-grid">
          <div class="form-group">
            <label>Nom</label>
            <input v-model="newProduct.nom" placeholder="Nom del producte" class="input-field" />
          </div>
          <div class="form-group">
            <label>Descripció</label>
            <textarea v-model="newProduct.descripcio" placeholder="Descripció" class="input-field"></textarea>
          </div>
          <div class="form-group">
            <label>Preu (€)</label>
            <input v-model.number="newProduct.preu" type="number" placeholder="Preu" class="input-field" />
          </div>
          <div class="form-group">
            <label>Stock</label>
            <input v-model.number="newProduct.stock" type="number" placeholder="Quantitat" class="input-field" />
          </div>
          <div class="form-group">
            <label>Categoria</label>
            <select v-model="newProduct.categoria" class="input-field">
              <option disabled :value="null">Selecciona una categoria</option>
              <option v-for="cat in parentCategories" :key="cat.id" :value="cat.id">
                {{ cat.nom }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Subcategoria</label>
            <select v-model="newProduct.subcategoria" class="input-field">
              <option disabled :value="null">Selecciona una subcategoria</option>
              <option v-for="subcat in getSubcategories(newProduct.categoria)" :key="subcat.id" :value="subcat.id">
                {{ subcat.nom }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Imatge</label>
            <input type="file" @change="onFileChange" class="input-field" />
            <input v-model="newProduct.imatge" placeholder="O bé, introdueix una URL" class="input-field" />
          </div>
          <div class="form-group">
            <label>Botiga</label>
            <select v-model="newProduct.botiga_id" class="input-field">
              <option disabled value="">Selecciona una botiga</option>
              <option v-for="bot in botigues" :key="bot.id" :value="bot.id">
                {{ bot.nom }}
              </option>
            </select>
          </div>
        </div>
        <div class="modal-actions">
          <button class="btn confirm-btn" @click="addProducte">💾 Desa</button>
          <button class="btn delete-btn" @click="showAddModal = false">❌ Cancel·lar</button>
        </div>
      </div>
    </div>

    <!-- Modal per Editar Producte -->
    <div v-if="showEditModal && editProduct" class="modal" @click.self="showEditModal = false">
      <div class="modal-content" @click.stop>
        <h3>Editar Producte</h3>
        <div class="form-grid">
          <div class="form-group">
            <label>Nom</label>
            <input v-model="editProduct.nom" placeholder="Nom del producte" class="input-field" />
          </div>
          <div class="form-group">
            <label>Descripció</label>
            <textarea v-model="editProduct.descripcio" placeholder="Descripció" class="input-field"></textarea>
          </div>
          <div class="form-group">
            <label>Preu (€)</label>
            <input v-model.number="editProduct.preu" type="number" placeholder="Preu" class="input-field" />
          </div>
          <div class="form-group">
            <label>Stock</label>
            <input v-model.number="editProduct.stock" type="number" placeholder="Quantitat" class="input-field" />
          </div>
          <div class="form-group">
            <label>Categoria</label>
            <select v-model="editProduct.categoria" class="input-field">
              <option disabled :value="null">Selecciona una categoria</option>
              <option v-for="cat in parentCategories" :key="cat.id" :value="cat.id">
                {{ cat.nom }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Subcategoria</label>
            <select v-model="editProduct.subcategoria" class="input-field">
              <option disabled :value="null">Selecciona una subcategoria</option>
              <option v-for="sub in getSubcategories(editProduct.categoria)" :key="sub.id" :value="sub.id">
                {{ sub.nom }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Imatge</label>
            <input type="file" @change="onFileChange" class="input-field" />
            <input v-model="editProduct.imatge" placeholder="O bé, introdueix una URL" class="input-field" />
          </div>
          <div class="form-group">
            <label>Botiga</label>
            <select v-model="editProduct.botiga_id" class="input-field">
              <option disabled value="">Selecciona una botiga</option>
              <option v-for="bot in botigues" :key="bot.id" :value="bot.id">
                {{ bot.nom }}
              </option>
            </select>
          </div>
        </div>
        <div class="modal-actions">
          <button class="btn confirm-btn" @click="updateProducte">💾 Desa</button>
          <button class="btn delete-btn" @click="showEditModal = false">❌ Cancel·lar</button>
        </div>
      </div>
    </div>

    <!-- Modal per Bulk Update (tancar fent clic fora) -->
    <div v-if="bulkUpdateVisible" class="modal" @click.self="bulkUpdateVisible = false">
      <div class="modal-content" @click.stop>
        <h3>Actualització Massiva</h3>
        <div class="bulk-update-fields">
          <div class="form-group">
            <label>Preu (€):</label>
            <input
              type="number"
              v-model.number="bulkUpdateValues.preu"
              class="input-field"
            />
          </div>
          <div class="form-group">
            <label>Stock:</label>
            <input
              type="number"
              v-model.number="bulkUpdateValues.stock"
              class="input-field"
            />
          </div>
          <div class="form-group">
            <label>Categoria:</label>
            <select v-model="bulkUpdateValues.categoria" class="input-field">
              <option disabled :value="null">Selecciona una categoria</option>
              <option v-for="cat in parentCategories" :key="cat.id" :value="cat.id">
                {{ cat.nom }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label>Subcategoria:</label>
            <select v-model="bulkUpdateValues.subcategoria" class="input-field">
              <option disabled :value="null">Selecciona una subcategoria</option>
              <option
                v-for="sub in getSubcategories(bulkUpdateValues.categoria)"
                :key="sub.id"
                :value="sub.id"
              >
                {{ sub.nom }}
              </option>
            </select>
          </div>
          <!-- Nova secció per Botiga -->
          <div class="form-group">
            <label>Botiga:</label>
            <select v-model="bulkUpdateValues.botiga" class="input-field">
              <option disabled :value="null">Selecciona una botiga</option>
              <option v-for="bot in botigues" :key="bot.id" :value="bot.id">
                {{ bot.nom }}
              </option>
            </select>
          </div>
        </div>
        <div class="modal-actions">
          <button class="btn confirm-btn" @click="bulkUpdateConfirm">
            Actualitza
          </button>
          <button class="btn delete-btn" @click="bulkUpdateVisible = false">
            Cancel·la
          </button>
        </div>
      </div>
    </div>


    <!-- Modal de confirmació d'eliminació -->
    <div v-if="showDeleteModal" class="modal" @click.self="showDeleteModal = false">
      <div class="modal-content" @click.stop>
        <p>Segur que vols eliminar aquest producte?</p>
        <div class="modal-actions">
          <button class="btn delete-btn" @click="deleteConfirmedProduct">
            Sí, eliminar
          </button>
          <button class="btn confirm-btn" @click="showDeleteModal = false">
            Cancel·lar
          </button>
        </div>
      </div>
    </div>

    <!-- Wizard d'Importació Excel -->
    <ProductImportWizard
      v-if="showImportWizard"
      :botigues="botigues"
      @close="showImportWizard = false"
      @refresh="fetchProductes"
    />

    <!-- Missatge d'error global -->
    <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
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
  categoria: number | null;
  subcategoria: number | null;
  botiga_id: number | null;
  botiga_nom?: string;
}

// Dades reactives
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

// Ordenació
const sortColumn = ref("");
const sortDirection = ref("asc");

// Filtres
const columnFilters = ref<Record<string, any>>({
  nom: [],
  preu: { min: 0, max: 0 },
  stock: { min: 0, max: 0 },
  categoria: [],
  subcategoria: [],
  botiga_nom: []
});
const filtersPanelVisible = ref(false);

// Ens assegurem que els filtres només es mostren quan hi ha productes carregats
const filtersReady = computed(() => productes.value.length > 0);

// Selecció múltiple
const selectedProducts = ref<number[]>([]);
const imageFile = ref<File | null>(null);

// Actualització massiva
const bulkUpdateVisible = ref(false);
const bulkUpdateValues = ref({
  preu: null as number | null,
  stock: null as number | null,
  categoria: null as number | null,
  subcategoria: null as number | null,
  botiga: null as number | null,
});

const router = useRouter();

// Funció per capitalitzar text
function capitalize(value: string): string {
  if (!value) return "";
  return value.charAt(0).toUpperCase() + value.slice(1);
}

// Carregar dades
async function fetchProductes() {
  try {
    const token = localStorage.getItem("userToken");
    if (!token) return;
    const response = await axios.get(`${API_URL}/productes`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    productes.value = response.data.map((prod: any) => ({
      ...prod,
      preu: Number(prod.preu),
      botiga_nom: prod.botiga?.nom ?? "No assignada",
      categoria: prod.categoria ? Number(prod.categoria) : null,
      subcategoria: prod.subcategoria ? Number(prod.subcategoria) : null,
    }));
    // Actualitza els filtres numèrics un cop es carreguen els productes
    columnFilters.value.preu = { min: preuMin.value, max: preuMax.value };
    columnFilters.value.stock = { min: stockMin.value, max: stockMax.value };
  } catch (error) {
    console.error("Error carregant productes:", error);
  }
}

async function fetchBotigues() {
  try {
    const token = localStorage.getItem("userToken");
    const response = await axios.get(`${API_URL}/botigues-mes`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    botigues.value = response.data;
  } catch (error) {
    console.error("Error carregant botigues:", error);
  }
}

async function fetchCategories() {
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
}

// Funció per obtenir valors únics d'un camp (per als filtres)
const uniqueValues = (field: string): string[] => {
  return [...new Set(productes.value.map((p: any) => p[field] || ""))].filter(
    (val) => val !== ""
  );
};

// Calcula mínim/màxim per preu i stock
const preuMin = computed(() => {
  const prices = productes.value.map(p => p.preu).filter(p => p != null);
  return prices.length ? Math.min(...prices) : 0;
});
const preuMax = computed(() => {
  const prices = productes.value.map(p => p.preu).filter(p => p != null);
  return prices.length ? Math.max(...prices) : 0;
});
const stockMin = computed(() => {
  const stocks = productes.value.map(p => p.stock).filter(s => s != null);
  return stocks.length ? Math.min(...stocks) : 0;
});
const stockMax = computed(() => {
  const stocks = productes.value.map(p => p.stock).filter(s => s != null);
  return stocks.length ? Math.max(...stocks) : 0;
});

// Quan es munta, carreguem les dades i resetejem els filtres
onMounted(() => {
  fetchProductes();
  fetchBotigues();
  fetchCategories();
  // Inicialment, neteja els filtres (sense cap valor seleccionat)
  clearFilters();
});

// Computed: productes filtrats i ordenats (exportarà aquests)
const sortedProducts = computed(() => {
  return productes.value
    .filter((prod) => {
      const searchLower = searchQuery.value.toLowerCase();
      if (
        !prod.nom.toLowerCase().includes(searchLower) &&
        !(prod.descripcio || "").toLowerCase().includes(searchLower) &&
        !String(prod.preu).includes(searchLower) &&
        !String(prod.stock).includes(searchLower) &&
        !(prod.botiga_nom || "").toLowerCase().includes(searchLower)
      ) {
        return false;
      }
      if (columnFilters.value.nom.length > 0 && !columnFilters.value.nom.includes(String(prod.nom))) {
        return false;
      }
      if (columnFilters.value.preu) {
        const val = prod.preu;
        if (columnFilters.value.preu.min != null && val < columnFilters.value.preu.min) return false;
        if (columnFilters.value.preu.max != null && val > columnFilters.value.preu.max) return false;
      }
      if (columnFilters.value.stock) {
        const val = prod.stock;
        if (columnFilters.value.stock.min != null && val < columnFilters.value.stock.min) return false;
        if (columnFilters.value.stock.max != null && val > columnFilters.value.stock.max) return false;
      }
      if (columnFilters.value.categoria.length > 0 && !columnFilters.value.categoria.includes(String(prod.categoria))) {
        return false;
      }
      if (columnFilters.value.subcategoria.length > 0 && !columnFilters.value.subcategoria.includes(String(prod.subcategoria))) {
        return false;
      }
      if (columnFilters.value.botiga_nom.length > 0 && !columnFilters.value.botiga_nom.includes(String(prod.botiga_nom))) {
        return false;
      }
      return true;
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

// Ordenació per columna
function sortProducts(column: string) {
  if (sortColumn.value === column) {
    sortDirection.value = sortDirection.value === "asc" ? "desc" : "asc";
  } else {
    sortColumn.value = column;
    sortDirection.value = "asc";
  }
}

// Panell de filtres
function toggleFiltersPanel() {
  filtersPanelVisible.value = !filtersPanelVisible.value;
}
function applyFilters() {
  // La lògica de filtratge ja s'aplica a sortedProducts
}
function clearFilters() {
  columnFilters.value.nom = [];
  columnFilters.value.preu = { min: preuMin.value, max: preuMax.value };
  columnFilters.value.stock = { min: stockMin.value, max: stockMax.value };
  columnFilters.value.categoria = [];
  columnFilters.value.subcategoria = [];
  columnFilters.value.botiga_nom = [];
}

// Categories i subcategories
const parentCategories = computed(() =>
  categories.value.filter((cat) => cat.parent_id === null)
);
function getSubcategories(categoryId: number | null) {
  if (!categoryId) return [];
  return categories.value.filter((cat) => cat.parent_id === categoryId);
}
function categoryName(id: number | null) {
  const cat = categories.value.find((c) => c.id === id);
  return cat ? cat.nom : "—";
}
function subcategoryName(id: number | null) {
  const sub = categories.value.find((c) => c.id === id);
  return sub ? sub.nom : "—";
}

// Selecció múltiple
const allSelected = computed(() =>
  sortedProducts.value.length > 0 &&
  sortedProducts.value.every((p) => selectedProducts.value.includes(p.id))
);
function toggleSelectAll(e: Event) {
  const target = e.target as HTMLInputElement;
  if (target.checked) {
    selectedProducts.value = sortedProducts.value.map((p) => p.id);
  } else {
    selectedProducts.value = [];
  }
}

// Afegir producte
async function addProducte() {
  try {
    const token = localStorage.getItem("userToken");
    if (!token) return;
    const formData = new FormData();
    formData.append("nom", newProduct.value.nom);
    formData.append("descripcio", newProduct.value.descripcio);
    formData.append("preu", newProduct.value.preu.toString());
    formData.append("stock", newProduct.value.stock.toString());
    formData.append("categoria", newProduct.value.categoria ? newProduct.value.categoria.toString() : "");
    formData.append("subcategoria", newProduct.value.subcategoria ? newProduct.value.subcategoria.toString() : "");
    formData.append("botiga_id", newProduct.value.botiga_id ? newProduct.value.botiga_id.toString() : "");
    if (imageFile.value) {
      formData.append("imatge", imageFile.value);
    } else if (newProduct.value.imatge) {
      formData.append("imatge", newProduct.value.imatge);
    }
    await axios.post(`${API_URL}/productes`, formData, {
      headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "multipart/form-data",
      },
    });
    // Reset del formulari
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
}

// Obrir modal d'edició
function openEditProduct(prod: any) {
  editProduct.value = {
    id: prod.id,
    nom: prod.nom,
    descripcio: prod.descripcio,
    preu: prod.preu,
    stock: prod.stock,
    categoria: prod.categoria || null,
    subcategoria: prod.subcategoria || null,
    imatge: prod.imatge || "",
    botiga_id: prod.botiga_id || null,
  };
  showEditModal.value = true;
}

// Actualitzar producte
async function updateProducte() {
  if (editProduct.value) {
    try {
      const token = localStorage.getItem("userToken");
      if (!token) return;
      const formData = new FormData();
      formData.append("_method", "PUT");
      formData.append("nom", editProduct.value.nom);
      formData.append("descripcio", editProduct.value.descripcio);
      formData.append("preu", editProduct.value.preu.toString());
      formData.append("stock", editProduct.value.stock.toString());
      formData.append("categoria", editProduct.value.categoria ? editProduct.value.categoria.toString() : "");
      formData.append("subcategoria", editProduct.value.subcategoria ? editProduct.value.subcategoria.toString() : "");
      formData.append("botiga_id", editProduct.value.botiga_id ? editProduct.value.botiga_id.toString() : "");
      if (imageFile.value) {
        formData.append("imatge", imageFile.value);
      } else if (editProduct.value.imatge) {
        formData.append("imatge", editProduct.value.imatge);
      }
      await axios.post(`${API_URL}/productes/${editProduct.value.id}`, formData, {
        headers: {
          Authorization: `Bearer ${token}`,
          "Content-Type": "multipart/form-data",
        },
      });
      showEditModal.value = false;
      imageFile.value = null;
      fetchProductes();
    } catch (error: any) {
      errorMessage.value = "Error actualitzant producte.";
      console.error("Error updating product:", error.response || error);
    }
  }
}

// Eliminar producte
async function deleteProducte(id: number) {
  try {
    const token = localStorage.getItem("userToken");
    await axios.delete(`${API_URL}/productes/${id}`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    fetchProductes();
  } catch (error) {
    errorMessage.value = "Error eliminant producte.";
  }
}
function confirmDeleteProduct(id: number) {
  deleteProductId.value = id;
  showDeleteModal.value = true;
}
async function deleteConfirmedProduct() {
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
}

// Exportar només els productes mostrats (sortedProducts) amb nom dinàmic
function exportTableData() {
  const timestamp = new Date();
  const formattedTimestamp =
    ("0" + timestamp.getHours()).slice(-2) +
    ("0" + timestamp.getMinutes()).slice(-2) +
    ("0" + timestamp.getSeconds()).slice(-2) +
    "-" +
    ("0" + timestamp.getDate()).slice(-2) +
    ("0" + (timestamp.getMonth() + 1)).slice(-2) +
    timestamp.getFullYear();
  const fileName = `productes-${formattedTimestamp}.xlsx`;
  const data = sortedProducts.value.map((prod) => ({
    ID: prod.id,
    Nom: prod.nom,
    Descripcio: prod.descripcio,
    Preu: prod.preu,
    Stock: prod.stock,
    Imatge: prod.imatge,
    Categoria: prod.categoria ? (categories.value.find((c) => c.id === prod.categoria)?.nom || prod.categoria) : "",
    Subcategoria: prod.subcategoria ? (categories.value.find((c) => c.id === prod.subcategoria)?.nom || prod.subcategoria) : "",
    Botiga: prod.botiga_nom,
  }));
  const worksheet = XLSX.utils.json_to_sheet(data);
  const workbook = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(workbook, worksheet, "Productes");
  const wbout = XLSX.write(workbook, { bookType: "xlsx", type: "array" });
  saveAs(new Blob([wbout]), fileName);
}

// Bulk Delete
async function bulkDelete() {
  if (!selectedProducts.value.length) return;
  if (!confirm("Estàs segur que vols eliminar els productes seleccionats?")) return;
  const token = localStorage.getItem("userToken");
  for (const id of selectedProducts.value) {
    try {
      await axios.delete(`${API_URL}/productes/${id}`, {
        headers: { Authorization: `Bearer ${token}` },
      });
    } catch (error) {
      console.error("Error eliminant producte amb id " + id, error);
    }
  }
  selectedProducts.value = [];
  fetchProductes();
}

// Bulk Update
function openBulkUpdateModal() {
  bulkUpdateVisible.value = true;
}
async function bulkUpdateConfirm() {
  if (!selectedProducts.value.length) return;
  const token = localStorage.getItem("userToken");
  if (!token) return;

  for (const id of selectedProducts.value) {
    try {
      // Obtenim el producte actual
      const product = productes.value.find((p) => p.id === id);
      if (!product) continue;

      // Construeix l'objecte d'actualització combinant els valors del bulk i els actuals
      const updateData: any = {
        // Actualitza només si hi ha un nou valor definit, sinó, manté l'original
        preu: bulkUpdateValues.value.preu !== null
          ? bulkUpdateValues.value.preu
          : product.preu,
        stock: bulkUpdateValues.value.stock !== null
          ? bulkUpdateValues.value.stock
          : product.stock,
        categoria: bulkUpdateValues.value.categoria !== null
          ? bulkUpdateValues.value.categoria
          : product.categoria,
        subcategoria: bulkUpdateValues.value.subcategoria !== null
          ? bulkUpdateValues.value.subcategoria
          : product.subcategoria,
        botiga_id: bulkUpdateValues.value.botiga !== null
          ? bulkUpdateValues.value.botiga
          : product.botiga_id,
        // Assegurem-nos d'enviar els camps obligatoris que no es modifiquen en el bulk
        nom: product.nom,
        descripcio: product.descripcio,
        // imatge: product.imatge,
      };

      // Si no hi ha cap camp a actualitzar (per exemple, si tot és null en el bulk), podem saltar-lo
      if (Object.keys(updateData).length === 0) continue;

      await axios.patch(`${API_URL}/productes/${id}`, updateData, {
        headers: { Authorization: `Bearer ${token}` },
      });
    } catch (error: any) {
      console.error("Error actualitzant producte amb id " + id, error.response?.data || error);
    }
  }
  bulkUpdateVisible.value = false;
  selectedProducts.value = [];
  fetchProductes();
}

// Gestió del fitxer d’imatge
function onFileChange(e: Event) {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    imageFile.value = target.files[0];
  }
}

// Navegació a Botigues
function goToBotigues() {
  router.push("/area-personal-botigues");
}

function goToRegistreImportacio() {
  router.push("/import-record");
}
</script>

<style scoped>
/* Animació fade per al panell de filtres */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter,
.fade-leave-to {
  opacity: 0;
}

/* Contenidor principal */
.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Capçalera i barra superior */
.page-header {
  text-align: center;
  margin-bottom: 20px;
  color: #333;
}
.top-bar {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: center;
  gap: 15px;
  margin-bottom: 20px;
}

/* Cerca i filtres */
.search-filters {
  display: flex;
  gap: 10px;
  align-items: center;
}
.search-input {
  flex: 1 1 300px;
  padding: 10px;
  font-size: 16px;
  border: 1px solid #42b983;
  border-radius: 5px;
  transition: border 0.3s ease;
}
.search-input:focus {
  border-color: #368c6e;
}
.filter-btn {
  padding: 10px 15px;
  background: #42b983;
  color: #fff;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  transition: background 0.3s ease;
}
.filter-btn:hover {
  background: #368c6e;
}

/* Panell de filtres */
.filters-panel {
  background: #f5f5f5;
  border: 1px solid #ddd;
  padding: 15px;
  margin-bottom: 20px;
  border-radius: 5px;
}
.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 15px;
  margin-bottom: 10px;
}
.filter-item {
  display: flex;
  flex-direction: column;
}
.filter-item label {
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 4px;
  color: #333;
}
.range-wrapper {
  display: flex;
  align-items: center;
}
.range-sep {
  margin: 0 6px;
  color: #888;
  font-weight: bold;
}
.filter-multi {
  padding: 6px;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 14px;
  min-width: 100px;
  height: 80px;
  overflow-y: auto;
}
.filter-range {
  padding: 6px;
  border: 1px solid #ccc;
  border-radius: 4px;
  width: 60px;
  font-size: 14px;
}
.filters-actions {
  display: flex;
  gap: 15px;
  justify-content: flex-end;
}
.apply-btn {
  background-color: #42b983;
  color: #fff;
  border: none;
  padding: 8px 12px;
  border-radius: 4px;
  cursor: pointer;
}
.clear-btn {
  background-color: #d9534f;
  color: #fff;
  border: none;
  padding: 8px 12px;
  border-radius: 4px;
  cursor: pointer;
}

/* Bulk actions */
.bulk-actions {
  background: #fffbe6;
  border: 1px solid #f0e68c;
  padding: 10px 15px;
  margin-bottom: 20px;
  border-radius: 5px;
  display: flex;
  gap: 15px;
  align-items: center;
}

/* Taula de productes */
.producte-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 20px;
  font-size: 14px;
}
.producte-table th,
.producte-table td {
  padding: 12px;
  border: 1px solid #ddd;
  text-align: left;
}
.producte-table th {
  background: #42b983;
  color: #fff;
  position: relative;
  cursor: pointer;
}
.producte-table tr:nth-child(even) {
  background: #f9f9f9;
}
.producte-table tr:hover {
  background: #e3f2fd;
}

/* Enllaç del producte */
.product-link {
  color: #007bff;
  text-decoration: none;
}
.product-link:hover {
  text-decoration: underline;
}

/* Botons d'acció */
.actions {
  display: flex;
  gap: 10px;
  justify-content: center;
}
.btn {
  background: #42b983;
  color: #fff;
  border: none;
  padding: 8px 12px;
  cursor: pointer;
  border-radius: 4px;
  transition: background 0.3s ease;
}
.btn:hover {
  background: #368c6e;
}
.add-btn {
  background: #42b983;
}
.edit-btn {
  background: #f0ad4e;
}
.edit-btn:hover {
  background: #e89c3b;
}
.delete-btn {
  background: #d9534f;
}
.delete-btn:hover {
  background: #c9302c;
}
.confirm-btn {
  background: #5bc0de;
}
.confirm-btn:hover {
  background: #46b8da;
}
.bulk-btn {
  background: #5bc0de;
}
.bulk-btn:hover {
  background: #46b8da;
}

/* Modal */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  z-index: 1000;
}
.modal-content {
  background: #fff;
  padding: 30px;
  border-radius: 10px;
  width: 100%;
  max-width: 700px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  max-height: 80vh;
  overflow: auto;
}

/* Graella per als modals amb dues columnes */
.form-grid {
  display: grid;
  grid-gap: 20px;
}
@media (min-width: 1024px) {
  .form-grid {
    grid-template-columns: 1fr 1fr;
  }
}
@media (max-width: 1023px) {
  .form-grid {
    display: block;
  }
}
.form-group {
  margin-bottom: 15px;
}
.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
}
.input-field {
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 14px;
  width: 100%;
  transition: border 0.2s ease-in-out;
}
.input-field:focus {
  border-color: #42b983;
  outline: none;
}
.modal-actions {
  display: flex;
  justify-content: space-between;
  margin-top: 20px;
}

/* Responsive */
@media (max-width: 768px) {
  .top-bar {
    flex-direction: column;
    align-items: stretch;
  }
  .search-filters {
    flex-direction: column;
  }
  .search-input {
    width: 100%;
  }
  .producte-table th,
  .producte-table td {
    padding: 8px;
    font-size: 12px;
  }
  .modal-content {
    max-width: 90%;
  }
  .filters-grid {
    grid-template-columns: 1fr;
  }
}
</style>
