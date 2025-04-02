<template>
  <!-- Overlay que ocupa tota la pantalla -->
  <div class="wizard-overlay" @click="reset">
    <!-- Modal, que impedeix la propagació del clic -->
    <div class="wizard-modal" @click.stop>
      <!-- Header amb títol i botó de tancament -->
      <header class="wizard-header">
        <h2>
          {{ t('import.title') }}
          <span v-if="file" class="file-name"> - {{ file.name }}</span>
        </h2>
        <button class="close-btn" @click="reset">✖</button>
      </header>

      <!-- Barra de passos -->
      <nav class="wizard-nav">
        <ul>
          <li
            v-for="(step, index) in steps"
            :key="index"
            :class="{
              active: currentStep === index + 1,
              completed: currentStep > index + 1
            }"
            @click="goToStep(index + 1)"
          >
            <span v-if="currentStep > index + 1" class="checkmark">✅</span>
            <span class="step-label">{{ index + 1 }}. {{ t(step.label) }}</span>
          </li>
        </ul>
      </nav>

      <!-- Contingut dels passos -->
      <section class="wizard-step">
        <!-- PAS 1: Selecció de Fitxer -->
        <div v-if="currentStep === 1" class="step">
          <label class="step-label">{{ t('import.selectFile') }}</label>
          <!-- Drop zone amb drag & drop -->
          <div
            class="drop-zone"
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDropFile"
            @click="triggerFileInput"
          >
            <p v-if="dragging" class="drop-message">{{ t('import.dropHere') }}</p>
            <p v-else-if="!file">{{ t('import.dragOrClick') }}</p>
            <p v-else>{{ file.name }}</p>
            <!-- Input file invisible per pujar amb clic -->
            <input type="file" accept=".xlsx,.xls" @change="handleFileChange" class="hidden-file-input" />
          </div>
          <div class="nav-buttons">
            <button class="btn btn-disabled">{{ t('common.previous') }}</button>
            <button class="btn" @click="nextStep" :disabled="!form.botiga_id || !file">
              {{ t('common.next') }}
            </button>
          </div>
        </div>

        <!-- PAS 2: Mapatge de Columnes -->
        <div v-else-if="currentStep === 2" class="step">
          <h4 class="step-title">{{ t('import.mapColumns') }}</h4>
          <div v-for="field in modelFields" :key="field" class="mapping-row">
            <label class="mapping-label">{{ t('fields.' + field) }}</label>
            <select v-model="mapping[field]" class="input-field">
              <option value="">{{ t('import.ignore') }}</option>
              <option v-for="header in headers" :key="header" :value="header">{{ header }}</option>
            </select>
          </div>
          <div class="nav-buttons">
            <button class="btn" @click="prevStep">⬅️ {{ t('common.previous') }}</button>
            <button class="btn" @click="goToStep(3)">{{ t('common.next') }} ➡️</button>
          </div>
        </div>

        <!-- PAS 3: Selecció de botiga, categoria i subcategoria -->
        <div v-else-if="currentStep === 3" class="step">
          <!-- Selecció de Botiga -->
          <label class="step-label">{{ t('import.selectStore') }}</label>
          <select v-model="form.botiga_id" class="input-field">
            <option disabled value="">{{ t('import.chooseStore') }}</option>
            <option v-for="b in botigues" :key="b.id" :value="b.id">{{ b.nom }}</option>
          </select>

          <!-- Selecció de Categoria Global (opcional) -->
          <label class="step-label">{{ t('import.optionalCategory') }}</label>
          <select v-model="form.categoria" class="input-field">
            <option disabled :value="null">{{ t('import.chooseCategory') }}</option>
            <option v-for="cat in parentCategories" :key="cat.id" :value="cat.id">
              {{ cat.nom }}
            </option>
          </select>

          <!-- Selecció de Subcategoria Global (opcional) -->
          <label class="step-label">{{ t('import.optionalSubcategory') }}</label>
          <select v-model="form.subcategoria" :disabled="!form.categoria" class="input-field">
            <option disabled :value="null">{{ t('import.chooseSubcategory') }}</option>
            <option v-for="sub in filteredSubcategories" :key="sub.id" :value="sub.id">
              {{ sub.nom }}
            </option>
          </select>

          <div class="nav-buttons">
            <button class="btn" @click="prevStep">⬅️ {{ t('common.previous') }}</button>
            <!-- Botó Next desactivat si no s'ha seleccionat botiga -->
            <button class="btn" @click="mapAndPreview" :disabled="!form.botiga_id">
              {{ t('common.next') }} ➡️
            </button>
          </div>
        </div>

        <!-- PAS 4: Previsualització i Edició -->
        <div v-else-if="currentStep === 4" class="step">
          <h4 class="step-title">{{ t('import.preview') }}</h4>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <!-- Columna per la numeració -->
                  <th>#</th>
                  <th v-for="col in modelFields" :key="col">{{ t('fields.' + col) }}</th>
                  <th>{{ t('fields.categoria') }}</th>
                  <th>{{ t('fields.subcategoria') }}</th>
                  <th>{{ t('common.actions') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(product, index) in preview" :key="index">
                  <!-- Mostra la numeració (index + 1) -->
                  <td>{{ index + 1 }}</td>
                  <td v-for="field in modelFields" :key="field">
                    <input
                      class="input-field"
                      :class="{ error: hasError(index, field) }"
                      v-model="product[field]"
                      @blur="sanitizeField(index, field)"
                    />
                  </td>
                  <td>
                    <select v-model="product.categoria" class="input-field">
                      <option disabled :value="null">{{ t('import.chooseCategory') }}</option>
                      <option v-for="cat in parentCategories" :key="cat.id" :value="cat.id">{{ cat.nom }}</option>
                    </select>
                  </td>
                  <td>
                    <select v-model="product.subcategoria" :disabled="!product.categoria" class="input-field">
                      <option disabled :value="null">{{ t('import.chooseSubcategory') }}</option>
                      <option v-for="sub in categories.filter(c => c.parent_id == product.categoria)" :key="sub.id" :value="sub.id">
                        {{ sub.nom }}
                      </option>
                    </select>
                  </td>
                  <td>
                    <button class="btn btn-icon" @click="removeRow(index)">🗑️</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <p class="note">* {{ t('import.editNote') }}</p>
          <div class="nav-buttons">
            <button class="btn" @click="prevStep">⬅️ {{ t('common.previous') }}</button>
            <button class="btn" @click="handleUpload">{{ t('import.upload') }} 🚀</button>
          </div>
        </div>

        <!-- PAS 5: Resultat de la Importació -->
        <div v-else-if="currentStep === 5" class="step">
          <p class="result-message">{{ result.message }}</p>
          <p class="result-success">{{ t('import.success', { count: result.importats }) }}</p>
          <div v-if="result.errors.length" class="error-section">
            <h4>{{ t('import.errorsFound') }}</h4>
            <ul>
              <li v-for="error in result.errors" :key="error.fila">
                {{ t('import.row') }} {{ error.fila }}: {{ formatError(error.errors) }}
              </li>
            </ul>
            <button class="btn download-btn" @click="downloadErrorExcel">
              {{ t('import.downloadErrors') }} 📥
            </button>
          </div>
          <button class="btn btn-large" @click="reset">{{ t('common.close') }} ❌</button>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { saveAs } from 'file-saver';
import * as XLSX from 'xlsx';

const { t } = useI18n();
const emit = defineEmits(['close', 'refresh']);
const props = defineProps<{ botigues: { id: number; nom: string }[] }>();

const API_URL = 'http://127.0.0.1:8000/api';

const currentStep = ref(1);
const form = ref({
  botiga_id: '',
  categoria: null as number | null,
  subcategoria: null as number | null,
});
const file = ref<File | null>(null);
const headers = ref<string[]>([]);
const mapping = ref<Record<string, string>>({});
const preview = ref<any[]>([]);
const result = ref({ importats: 0, errors: [] as any[], message: '' });
const categories = ref<any[]>([]);

const modelFields = ['nom', 'preu', 'stock', 'descripcio', 'imatge'];
const steps = [
  { label: 'import.step1' },
  { label: 'import.step2' },
  { label: 'import.step3' },
  { label: 'import.step4' },
  { label: 'import.step5' },
];

// Carrega les categories del backend
onMounted(async () => {
  try {
    const token = localStorage.getItem('userToken');
    const response = await axios.get(`${API_URL}/categories`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    categories.value = response.data;
  } catch {
    console.warn("No s'han pogut carregar les categories");
  }
});

// Categories pare i subcategories
const parentCategories = computed(() => categories.value.filter(cat => cat.parent_id == null));
const filteredSubcategories = computed(() => categories.value.filter(cat => cat.parent_id == form.value.categoria));

// Navegació entre passos
function goToStep(n: number) {
  currentStep.value = n;
}
function nextStep() {
  // Validació del pas 1
  if (currentStep.value === 1 && (!form.value.botiga_id || !file.value)) {
    alert(t('import.errorStep1'));
    return;
  }
  currentStep.value++;
}
function prevStep() {
  currentStep.value--;
}
function reset() {
  currentStep.value = 1;
  file.value = null;
  headers.value = [];
  mapping.value = {};
  preview.value = [];
  result.value = { importats: 0, errors: [], message: '' };
  emit('close');
}

// Gestió del fitxer amb drag & drop
const dragging = ref(false);
function handleFileChange(e: Event) {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    file.value = target.files[0];
    extractHeaders();
  }
}
function onDropFile(e: DragEvent) {
  e.preventDefault();
  dragging.value = false;
  if (e.dataTransfer && e.dataTransfer.files.length > 0) {
    file.value = e.dataTransfer.files[0];
    extractHeaders();
  }
}
function triggerFileInput() {
  const input = (document.querySelector('.hidden-file-input') as HTMLInputElement);
  if (input) {
    input.click();
  }
}
function extractHeaders() {
  if (!file.value) return;
  const reader = new FileReader();
  reader.onload = (e) => {
    const data = new Uint8Array(e.target?.result as ArrayBuffer);
    const workbook = XLSX.read(data, { type: 'array' });
    const sheet = workbook.Sheets[workbook.SheetNames[0]];
    const json = XLSX.utils.sheet_to_json(sheet, { header: 1 });
    headers.value = json[0] as string[];
    autoMapFields();
    currentStep.value = 2;
  };
  reader.readAsArrayBuffer(file.value);
}

function autoMapFields() {
  const fieldKeywords: Record<string, string[]> = {
    nom: ['nom', 'name', 'product'],
    preu: ['preu', 'price', 'cost'],
    stock: ['stock', 'unitats', 'quantitat', 'total'],
    descripcio: ['descripcio', 'description', 'info'],
    categoria: ['categoria', 'category'],
    imatge: ['imatge', 'image', 'img'],
  };
  modelFields.forEach(field => {
    const keywords = fieldKeywords[field];
    const match = headers.value.find(h =>
      keywords.some(k => h.toLowerCase().includes(k))
    );
    if (match) mapping.value[field] = match;
  });
}

function availableHeaders(currentField: string) {
  const selected = Object.entries(mapping)
    .filter(([key]) => key !== currentField)
    .map(([, val]) => val);
  return headers.value.filter(h => !selected.includes(h));
}

// Genera la previsualització assignant els valors globals de categoria i subcategoria
function mapAndPreview() {
  if (!file.value) return;
  const reader = new FileReader();
  reader.onload = (e) => {
    const data = new Uint8Array(e.target?.result as ArrayBuffer);
    const workbook = XLSX.read(data, { type: 'array' });
    const sheet = workbook.Sheets[workbook.SheetNames[0]];
    const raw = XLSX.utils.sheet_to_json(sheet, { header: 1 });
    const dataRows = raw.slice(1);
    preview.value = dataRows.map(row => {
      const obj: any = {};
      modelFields.forEach(field => {
        const header = mapping.value[field];
        if (header) {
          const index = headers.value.indexOf(header);
          if (index !== -1) obj[field] = row[index];
        }
      });
      if (obj.preu) obj.preu = parseFloat(String(obj.preu).replace(',', '.')) || 0;
      if (obj.stock) {
        const clean = String(obj.stock).replace(/[^\d.]/g, '').replace(',', '.');
        obj.stock = Number.isFinite(parseFloat(clean)) ? Math.round(parseFloat(clean)) : 0;
      }
      obj.categoria = form.value.categoria;
      obj.subcategoria = form.value.subcategoria !== null ? form.value.subcategoria : null;
      return obj;
    });
    console.log("Preview final:", preview.value);
    currentStep.value = 4;
  };
  reader.readAsArrayBuffer(file.value);
}

function sanitizeField(index: number, field: string) {
  const value = preview.value[index][field];
  if (field === 'preu') preview.value[index][field] = parseFloat(String(value).replace(',', '.')) || 0;
  if (field === 'stock') preview.value[index][field] = parseInt(String(value).replace(/[^\d]/g, '')) || 0;
}

function hasError(index: number, field: string) {
  const row = result.value.errors.find(e => e.fila === index + 2);
  return row?.errors?.[field];
}

function formatError(errors: Record<string, string>) {
  return Object.entries(errors).map(([k, v]) => `${k}: ${v}`).join(', ');
}

function validateProducts(): boolean {
  for (let i = 0; i < preview.value.length; i++) {
    const product = preview.value[i];
    // Comprovem que "nom", "preu" i "stock" no siguin buits, i que "categoria" i "subcategoria" siguin diferents de null

  if (!product.nom || product.preu === null || product.stock === null || product.categoria === null || product.subcategoria === null) {
    alert(`El producte de la fila ${i + 1} no té tots els camps obligatoris. Si no pots assignar categoria/subcategoria global, has d'editar cada producte individualment.`);
    return false;
  }

  }
  return true;
}

function handleUpload() {
  // Afegim la validació extra abans d'enviar
  if (!validateProducts()) {
    return; // Si algun producte no compleix la validació, aturem l'enviament
  }

  const formData = new FormData();
  if (file.value) formData.append('fitxer', file.value);
  formData.append('botiga_id', form.value.botiga_id);
  formData.append('categoria', form.value.categoria ? form.value.categoria.toString() : '');
  formData.append('subcategoria', form.value.subcategoria !== null ? form.value.subcategoria.toString() : '');
  formData.append('preview', JSON.stringify(preview.value));
  
  console.log("FormData enviat:");
  for (let [key, value] of formData.entries()) {
    console.log(key, value);
  }
  
  const token = localStorage.getItem('userToken');
  axios.post(`${API_URL}/import-productes`, formData, {
    headers: { Authorization: `Bearer ${token}` },
  })
    .then(res => {
      result.value = res.data;
      currentStep.value = 5;
      emit('refresh');
    })
    .catch(() => {
      result.value.message = t('import.errorUpload');
      currentStep.value = 5;
    });
}


function downloadErrorExcel() {
  const errorSheet = preview.value.map((row, i) => {
    const base = { ...row };
    const error = result.value.errors.find(e => e.fila === i + 2);
    if (error) base['__ERRORS__'] = JSON.stringify(error.errors);
    return base;
  });
  const worksheet = XLSX.utils.json_to_sheet(errorSheet);
  const workbook = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(workbook, worksheet, 'Errors');
  const wbout = XLSX.write(workbook, { bookType: 'xlsx', type: 'array' });
  saveAs(new Blob([wbout]), "errors.xlsx");
}

function removeRow(index: number) {
  preview.value.splice(index, 1);
}

onMounted(() => {
  // Es poden carregar altres dades si cal, per exemple botigues, categories...
});
</script>

<style scoped>
/* Contenidor principal del wizard */
.wizard-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.65);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  z-index: 1000;
}
.wizard-modal {
  background: #ffffff;
  padding: 30px;
  max-width: 700px;
  width: 100%;
  border-radius: 10px;
  position: relative;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  z-index: 1100;
  max-height: 90vh; /* Altura màxima */
  overflow-y: auto; /* Afegit per desplaçament vertical */
}


/* Capçalera */
.wizard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}
.close-btn {
  background: transparent;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #555;
}
.file-name {
  font-size: 16px;
  color: #888;
  margin-left: 10px;
}

/* Navegació de passos */
.wizard-nav ul {
  list-style: none;
  display: flex;
  justify-content: space-around;
  padding: 0;
  margin: 0 0 20px;
}
.wizard-nav li {
  padding: 10px 15px;
  border-bottom: 2px solid #ccc;
  cursor: pointer;
  transition: border-color 0.3s ease;
  font-weight: 500;
  color: #666;
}
.wizard-nav li.active {
  border-color: #42b983;
  color: #333;
}
.wizard-nav li.completed {
  color: #42b983;
}

/* Contingut dels passos */
.wizard-step {
  margin-bottom: 20px;
}
.mapping-row {
  margin-bottom: 15px;
  display: flex;
  flex-direction: column;
}
.mapping-label {
  margin-bottom: 5px;
  font-weight: 500;
  color: #333;
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

/* Drop Zone */
.drop-zone {
  border: 2px dashed #ccc;
  border-radius: 4px;
  padding: 20px;
  text-align: center;
  color: #999;
  cursor: pointer;
  margin-bottom: 15px;
  transition: border-color 0.3s ease;
}
.drop-zone:hover {
  border-color: #42b983;
}
.hidden-file-input {
  display: none;
}

/* Taula de previsualització */
.table-container {
  overflow-x: auto;
}
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
  font-size: 14px;
}
th, td {
  border: 1px solid #ddd;
  padding: 10px;
  text-align: center;
}
th {
  background: #42b983;
  color: #fff;
  position: relative;
}
.filter-dropdown {
  position: absolute;
  background: #42b983;
  border: 1px solid #ddd;
  padding: 8px;
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

/* Botons */
.nav-buttons {
  display: flex;
  justify-content: space-between;
  margin-top: 20px;
}
.btn {
  background: #42b983;
  color: #fff;
  padding: 10px 15px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  transition: background 0.2s ease;
}
.btn:hover {
  background: #368c6e;
}
.btn:disabled, .btn.btn-disabled {
  background: #ccc;
  cursor: not-allowed;
}
.btn-large {
  padding: 12px 20px;
  font-size: 16px;
}
.btn-icon {
  background: transparent;
  border: none;
  cursor: pointer;
  font-size: 18px;
}

/* Missatges i notes */
.note {
  font-size: 12px;
  color: #888;
  margin-top: 10px;
  text-align: center;
}
.result-message {
  font-size: 16px;
  font-weight: 600;
  color: #333;
  margin-bottom: 10px;
}
.result-success {
  font-size: 14px;
  color: #42b983;
  margin-bottom: 20px;
}
.error-section h4 {
  color: #d9534f;
  margin-bottom: 10px;
}

/* Responsive */
@media (max-width: 768px) {
  .wizard-modal {
    max-width: 90%;
    padding: 20px;
  }
  .wizard-nav ul {
    flex-direction: column;
    align-items: center;
  }
  .wizard-nav li {
    margin-bottom: 5px;
  }
  table, th, td {
    font-size: 12px;
    padding: 8px;
  }
}
</style>
