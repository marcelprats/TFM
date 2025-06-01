

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import axios from "axios";
import { useRouter } from "vue-router";
import ProductImportWizard from "../components/ProductImportWizard.vue";
import * as XLSX from "xlsx";
import { saveAs } from "file-saver";

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
    const response = await axios.get(`/vendor/productes`, {});
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
    const response = await axios.get(`/vendor/botigues-mes`, {});
    botigues.value = response.data;
  } catch (error) {
    console.error("Error carregant botigues:", error);
  }
}

async function fetchCategories() {
  try {
    const token = localStorage.getItem("userToken");
    if (!token) return;
    const response = await axios.get(`/vendor/categories`, {});
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
function applyFilters() {}
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
    // 👇👇 CORRECTE: POST A /vendor/productes
    await axios.post(`/vendor/productes`, formData, {
      headers: { "Content-Type": "multipart/form-data" }
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
      const formData = new FormData();
      formData.append("_method", "PATCH");
      formData.append("nom", editProduct.value.nom);
      formData.append("descripcio", editProduct.value.descripcio);
      formData.append("preu", editProduct.value.preu.toString());
      formData.append("stock", editProduct.value.stock.toString());
      formData.append("categoria", editProduct.value.categoria ? editProduct.value.categoria.toString() : "");
      formData.append("subcategoria", editProduct.value.subcategoria ? editProduct.value.subcategoria.toString() : "");
      formData.append("botiga_id", editProduct.value.botiga_id ? editProduct.value.botiga_id.toString() : "");
      if (imageFile.value) {
        formData.append("imatge", imageFile.value);
      }
      await axios.post(`/vendor/productes/${editProduct.value.id}`, formData, {
        headers: {
          "Content-Type": "multipart/form-data",
        }
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
    await axios.delete(`/vendor/productes/${id}`, {
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
      await axios.delete(`/vendor/productes/${deleteProductId.value}`, {
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
      await axios.delete(`/vendor/productes/${id}`, {
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

      await axios.patch(`/vendor/productes/${id}`, updateData, {
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
            <span class="icon">🔍</span> Filtres
          </button>
        </div>
        <div class="main-actions">
          <button class="btn add-btn" @click="showAddModal = true">
            <span class="icon">➕</span> Afegir Producte
          </button>
          <button class="btn sec-btn" @click="goToBotigues">
            <span class="icon">🏬</span> Botigues
          </button>
          <button class="btn sec-btn" @click="showImportWizard = true">
            <span class="icon">📥</span> Importar Excel
          </button>
          <button class="btn sec-btn" @click="goToRegistreImportacio">
            <span class="icon">📝</span> Registre d'importació
          </button>
          <button class="btn sec-btn" @click="exportTableData">
            <span class="icon">📤</span> Exportar Dades
          </button>
        </div>
      </div>

      <!-- Panell de filtres -->
      <transition name="fade">
        <div v-if="filtersPanelVisible && filtersReady" class="filters-panel">
          <div class="filters-grid">
            <div class="filter-item">
              <label>{{ capitalize("nom") }}</label>
              <select multiple v-model="columnFilters.nom" class="input-field filter-multi">
                <option v-for="val in uniqueValues('nom')" :key="val" :value="val">
                  {{ val }}
                </option>
              </select>
            </div>
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
        <span class="icon">🗑️</span> Elimina seleccionats
      </button>
      <button class="btn bulk-btn" @click="openBulkUpdateModal">
        <span class="icon">✏️</span> Actualitza seleccionats
      </button>
    </div>

    <!-- Taula de productes -->
    <div class="table-responsive">
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
            <td data-label="">
              <input
                type="checkbox"
                :value="prod.id"
                v-model="selectedProducts"
              />
            </td>
            <td data-label="Nom">
              <router-link :to="{ name: 'Producte', params: { id: prod.id } }" class="product-link">
                {{ prod.nom }}
              </router-link>
            </td>
            <td data-label="Descripció">{{ prod.descripcio || "—" }}</td>
            <td data-label="Preu (€)">{{ prod.preu }}</td>
            <td data-label="Stock">{{ prod.stock }}</td>
            <td data-label="Categoria">{{ categoryName(prod.categoria) }}</td>
            <td data-label="Subcategoria">{{ subcategoryName(prod.subcategoria) }}</td>
            <td data-label="Botiga">{{ prod.botiga_nom }}</td>
            <td data-label="Imatge">
              <img
                v-if="prod.imatge"
                :src="prod.imatge"
                alt="Imatge"
                width="40"
                height="40"
              />
              <span v-else>—</span>
            </td>
            <td data-label="Accions" class="actions">
              <button class="btn edit-btn" @click="openEditProduct(prod)">
                <span class="icon">✏️</span> Editar
              </button>
              <button class="btn delete-btn" @click="confirmDeleteProduct(prod.id)">
                <span class="icon">🗑️</span> Eliminar
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- MODALS -->
    <!-- MODAL: Afegir Producte -->
    <transition name="modal-fade">
      <div v-if="showAddModal" class="modal" @click.self="showAddModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3><span class="icon">➕</span> Afegir Producte</h3>
            <button class="close-btn" @click="showAddModal = false">×</button>
          </div>
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
            <button class="btn confirm-btn" @click="addProducte">
              <span class="icon">💾</span> Desa
            </button>
            <button class="btn cancel-btn" @click="showAddModal = false">
              <span class="icon">❌</span> Cancel·lar
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- MODAL: Editar Producte -->
    <transition name="modal-fade">
      <div v-if="showEditModal && editProduct" class="modal" @click.self="showEditModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3><span class="icon">✏️</span> Editar Producte</h3>
            <button class="close-btn" @click="showEditModal = false">×</button>
          </div>
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
            <button class="btn confirm-btn" @click="updateProducte">
              <span class="icon">💾</span> Desa
            </button>
            <button class="btn cancel-btn" @click="showEditModal = false">
              <span class="icon">❌</span> Cancel·lar
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- MODAL: Bulk Update -->
    <transition name="modal-fade">
      <div v-if="bulkUpdateVisible" class="modal" @click.self="bulkUpdateVisible = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3><span class="icon">🛠️</span> Actualització Massiva</h3>
            <button class="close-btn" @click="bulkUpdateVisible = false">×</button>
          </div>
          <div class="bulk-update-fields form-grid">
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
              <span class="icon">💾</span> Actualitza
            </button>
            <button class="btn cancel-btn" @click="bulkUpdateVisible = false">
              <span class="icon">❌</span> Cancel·la
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- MODAL: Confirmació d'eliminació -->
    <transition name="modal-fade">
      <div v-if="showDeleteModal" class="modal" @click.self="showDeleteModal = false">
        <div class="modal-content" @click.stop>
          <div class="modal-header">
            <h3><span class="icon">🗑️</span> Eliminar Producte</h3>
            <button class="close-btn" @click="showDeleteModal = false">×</button>
          </div>
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
    </transition>

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

<style scoped>
/* --------- GENERAL --------- */
body, html {
  background: #f8f9fa;
  padding: 0;
  margin: 0;
  font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
  color: #222;
}
.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 24px 12px 40px 12px;
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 3px 16px 0 rgba(0,0,0,0.08);
  min-height: 100vh;
}
.page-header {
  text-align: center;
  margin-bottom: 24px;
}
.page-header h1 {
  color: #5cb85c;
  font-size: 2.2em;
  font-weight: 800;
  letter-spacing: 0.5px;
  margin-bottom: 14px;
}
.top-bar {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: flex-end;
  gap: 16px;
  margin-bottom: 24px;
}
.search-filters {
  display: flex;
  align-items: center;
  gap: 9px;
  flex-shrink: 0;
}
.search-input {
  max-width: 210px;
  padding: 8px 12px;
  border-radius: 7px;
  border: 1.5px solid #e0e0e0;
  font-size: 1em;
  transition: border 0.18s;
  background: #fff;
}
.search-input:focus {
  border-color: #5cb85c;
}
.main-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
  width: 100%;
  justify-content: flex-end;
}
@media (max-width: 700px) {
  .main-actions {
    flex-direction: column;
    align-items: stretch;
    gap: 8px;
    width: 100%;
    margin-top: 10px;
  }
}

/* --------- BOTONS --------- */
.btn {
  border: none;
  border-radius: 8px;
  padding: 10px 16px;
  font-size: 1em;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s, color 0.15s;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  outline: none;
  background: #e0f8de;
  color: #222;
}
.btn:active { background: #b9ecb1; }
.add-btn, .confirm-btn, .filter-btn, .apply-btn {
  background: #b8efc5;
  color: #222;
}
.add-btn:hover, .confirm-btn:hover, .filter-btn:hover, .apply-btn:hover {
  background: #94df9f;
}
.sec-btn, .bulk-btn {
  background: #b7dcfc;
  color: #222;
}
.sec-btn:hover, .bulk-btn:hover {
  background: #83beea;
}
.edit-btn {
  background: #b8efc5;
  color: #222;
}
.edit-btn:hover { background: #94df9f;}
.delete-btn, .cancel-btn {
  background: #ed6a5a !important;
  color: white !important;
}
.delete-btn:hover, .cancel-btn:hover { background: #d34b36 !important; color: white !important;}
.clear-btn {
  background: #f7c6c6 !important;
  color: #111 !important;
  border: none !important;
}
.clear-btn:hover {
  background: #f4aaaa !important;
  color: #000 !important;
}
.btn .icon { font-size: 1.15em; margin-right: 2px; }

/* --------- BULK ACTIONS --------- */
.bulk-actions {
  background: #ffe082;
  border: none;
  padding: 10px 15px;
  margin-bottom: 20px;
  border-radius: 10px;
  display: flex;
  gap: 15px;
  align-items: center;
  font-size: 15px;
  font-weight: 500;
}
@media (max-width: 600px) {
  .bulk-actions {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
    font-size: 14px;
  }
}

/* --------- FILTERS --------- */
.filters-panel {
  background: #f6fff3;
  border: none;
  box-shadow: none;
  padding: 22px 18px 14px 18px;
  margin-bottom: 22px;
  border-radius: 10px;
}
.filters-panel .input-field,
.filters-panel select,
.filters-panel input[type="number"] {
  border: 1.2px solid #e2e8e4 !important;
  border-radius: 7px;
  box-shadow: none !important;
  background: #fff;
  font-size: 15px;
}
.filters-panel .input-field:focus,
.filters-panel select:focus,
.filters-panel input[type="number"]:focus {
  border-color: #a6d7a8 !important;
  outline: none;
}
.filter-multi {
  height: 64px;
  padding: 7px;
}
.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(175px, 1fr));
  gap: 14px;
  margin-bottom: 10px;
}
@media (max-width: 700px) {
  .filters-grid { grid-template-columns: 1fr; }
}
.filter-item label {
  font-size: 14px;
  font-weight: 700;
  margin-bottom: 4px;
  color: #3b7e2d;
  letter-spacing: 0.1px;
}
.filters-actions {
  display: flex;
  gap: 14px;
  justify-content: flex-end;
  margin-top: 10px;
}
.apply-btn {
  background: #e9fae7;
  color: #2d6027;
  font-weight: 650;
  border: none;
  border-radius: 8px;
  box-shadow: none;
  padding: 10px 22px;
  font-size: 16px;
}
.apply-btn:hover {
  background: #c6eec5;
  color: #20521a;
}
.clear-btn {
  background: #f7c6c6;
  color: #111 !important;
  font-weight: 650;
  border: none !important;
  border-radius: 8px;
  box-shadow: none;
  padding: 10px 22px;
  font-size: 16px;
}
.clear-btn:hover {
  background: #f4aaaa;
  color: #000 !important;
}
.filters-panel .btn {
  box-shadow: none;
  border: none;
}
@media (max-width: 500px) {
  .filters-panel { padding: 14px 2px; }
  .apply-btn, .clear-btn { padding: 9px 8px; font-size: 14px;}
}

/* --------- PRODUCT LINK --------- */
.product-link {
  color: #5cb85c;
  text-decoration: underline;
  font-weight: 700;
  transition: color 0.1s;
  word-break: break-all;
}
.product-link:hover { color: #399b22; }

/* --------- TAULA PRODUCTES --------- */
.table-responsive {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  background: #f8f9fa;
  border-radius: 10px;
  margin-bottom: 24px;
}
.producte-table {
  width: 100%;
  border-collapse: collapse;
  background: #fff;
  font-size: 15px;
  border-radius: 10px;
  box-shadow: 0 2px 8px 0 rgba(0,0,0,0.04);
  overflow: hidden;
}
.producte-table th, .producte-table td {
  padding: 12px 8px;
  border: 1px solid #e0e0e0;
  text-align: left;
}
.producte-table th {
  background: #b8efc5;
  color: #222;
  font-weight: 700;
  user-select: none;
  cursor: pointer;
}
.producte-table tr:nth-child(even) { background: #f9fff4; }
.producte-table tr:hover { background: #e9fbe9; }
.actions {
  display: flex;
  gap: 10px;
  justify-content: center;
  margin-top: 8px;
}
.producte-table img {
  border-radius: 6px;
  object-fit: cover;
  box-shadow: 0 2px 8px rgba(0,0,0,0.09);
  border: 1.5px solid #e0e0e0;
}

/* --------- RESPONSIVE TAULA: format targeta a mòbil --------- */
@media (max-width: 900px) {
  .table-responsive { padding: 0; }
  .producte-table, .producte-table thead, .producte-table tbody, .producte-table th, .producte-table td, .producte-table tr {
    display: block;
  }
  .producte-table thead { display: none; }
  .producte-table tr {
    margin-bottom: 15px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 8px 0 rgba(0,0,0,0.07);
    border: 1px solid #e0e0e0;
    padding: 6px 0 4px 0;
  }
  .producte-table td {
    border: none;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 14px;
    font-size: 15px;
    position: relative;
    min-height: 32px;
    word-break: break-word;
    white-space: pre-line;
  }
  .producte-table td:before {
    content: attr(data-label);
    font-weight: 700;
    color: #5cb85c;
    flex: 0 0 50%;
    padding-right: 8px;
    font-size: 14px;
    min-width: 110px;
    text-align: left;
  }
  .actions {
    margin: 10px 0 0 0;
    justify-content: flex-end;
    gap: 8px;
    flex-wrap: wrap;
    width: 100%;
    padding: 0 8px 10px 8px;
    box-sizing: border-box;
  }
  .actions .btn {
    flex: 1 1 48%;
    min-width: 110px;
    margin-bottom: 0;
    justify-content: center;
  }
}

/* --------- MODALS --------- */
.modal {
  position: fixed;
  top: 0; left: 0; width: 100vw; height: 100vh;
  background: rgba(0,0,0,0.55);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
  animation: modalFadeIn 0.17s;
}
@keyframes modalFadeIn {
  from { opacity: 0; }
  to   { opacity: 1; }
}
.modal-content {
  background: #fff;
  padding: 28px 22px 18px 22px;
  border-radius: 14px;
  width: 100%;
  max-width: 460px;
  box-shadow: 0 6px 32px 0 rgba(0,0,0,0.17);
  max-height: 90vh;
  overflow: auto;
  position: relative;
  animation: modalPop 0.22s;
}
@keyframes modalPop {
  from { transform: scale(0.97) translateY(32px); opacity: 0;}
  to   { transform: none; opacity: 1;}
}
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}
.modal-header h3 {
  margin: 0;
  font-size: 21px;
  font-weight: 700;
  color: #2e7d32;
  letter-spacing: 0.2px;
  display: flex;
  align-items: center;
  gap: 6px;
}
.close-btn {
  background: transparent;
  border: none;
  font-size: 1.6em;
  cursor: pointer;
  color: #888;
  transition: color 0.21s;
  padding: 2px 10px;
  border-radius: 4px;
}
.close-btn:hover {
  color: #e53935;
  background: #f8f8f8;
}
.form-grid {
  display: grid;
  grid-gap: 14px;
}
@media (min-width: 768px) {
  .form-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 767px) {
  .form-grid { display: block; }
}
.form-group {
  margin-bottom: 7px;
}
.form-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  color: #444;
}
.input-field {
  padding: 9px 10px;
  border: 1.3px solid #bdbdbd;
  border-radius: 4px;
  font-size: 15px;
  width: 100%;
  background: #fafbfc;
  transition: border 0.13s;
}
.input-field:focus {
  border-color: #43a047;
  outline: none;
}
.modal-actions {
  display: flex;
  justify-content: space-between;
  margin-top: 19px;
  gap: 12px;
}
@media (max-width: 480px) {
  .modal-content {
    max-width: 99vw;
    padding: 10px 2px 10px 2px;
  }
}

/* --------- ERROR --------- */
.error {
  color: #e53935;
  padding: 16px 0 0 0;
  text-align: center;
  font-weight: 600;
  font-size: 1.08em;
}
</style>